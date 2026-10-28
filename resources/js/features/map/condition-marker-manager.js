const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {adaptCondition,calculateMarkerAge} from './marker-state.js';
import {createConditionMarker} from './condition-marker.js';
export class ConditionMarkerManager {
 constructor(L,map,{endpoint,onData=()=>{},onStatus=()=>{},pollMs=30000,layerGroup=null}={}){
  this.L=L;this.map=map;this.layerGroup=layerGroup;this.endpoint=endpoint;this.onData=onData;this.onStatus=onStatus;this.pollMs=pollMs;this.markers=new Map();this.schoolId=null;this.offset=0;this.requestId=0;this.lastPoll=0;this.destroyed=false;this.timer=null;
  this.motion=window.matchMedia(uiText("(prefers-reduced-motion: reduce)"));
  this.onPopupOpen=()=>{this.map.getContainer().closest('.map-art')?.classList.add('condition-popup-open');};this.onPopupClose=()=>{this.map.getContainer().closest('.map-art')?.classList.remove('condition-popup-open');};
  this.onMove=()=>{this.updateAllConditionMarkers();this.updateSummary();};this.onZoom=()=>{this.setZoom();this.updateAllConditionMarkers();this.updateSummary();};
  this.onVisibility=()=>{if(document.hidden){this.stop();this.controller?.abort();}else{this.start();this.refresh();}};
  this.onClick=e=>{const trigger=e.target.closest('[data-open-condition]');if(trigger)this.open(trigger.dataset.openCondition);const page=e.target.closest('[data-nearby-page]');if(page){const m=this.markers.get(page.dataset.condition);if(m){m.nearbyPage=Number(page.dataset.nearbyPage)||0;m.layer.setPopupContent(m.popup());}}};
  this.onUserCamera=()=>this.cancelAutoFrame();
  for(const event of ['pointerdown','wheel','keydown','touchstart'])map.getContainer().addEventListener(event,this.onUserCamera,{passive:true});
  this.onPageHide=e=>{if(e.persisted){this.stop();this.controller?.abort();}else this.destroy();};this.onPageShow=e=>{if(e.persisted&&!this.destroyed){this.start();this.refresh();}};
  map.on('popupopen',this.onPopupOpen);map.on('popupclose',this.onPopupClose);map.on('moveend',this.onMove);map.on('zoomend',this.onZoom);map.getContainer().addEventListener('click',this.onClick);document.addEventListener('visibilitychange',this.onVisibility);window.addEventListener('pagehide',this.onPageHide);window.addEventListener('pageshow',this.onPageShow);
  this.setZoom();this.start();
 }
 now(){return Date.now()+this.offset;}
 setZoom(){this.map.getContainer().style.setProperty('--condition-size',`${this.map.getZoom()<13?24:this.map.getZoom()<16?30:34}px`);}
 start(){this.map.getContainer().classList?.remove('condition-animation-paused');if(this.timer||this.destroyed||document.hidden)return;this.timer=setInterval(()=>{this.updateAllConditionMarkers();if(this.schoolId&&Date.now()-this.lastPoll>=Math.max(this.pollMs,(this.lastPages||0)*1500))this.refresh();},1000);}
 stop(){this.map.getContainer().classList?.add('condition-animation-paused');if(this.timer)clearInterval(this.timer);this.timer=null;}
 setSchool(id,initial=[]){const key=String(id??'');if(key===this.schoolId)return;this.requestId++;this.controller?.abort();this.schoolId=key;for(const m of this.markers.values())m.dispose();this.markers.clear();this.lastUpdated=null;this.autoFramePending=true;this.sync(initial);if(initial.length)this.focusAll(false);this.lastPoll=0;}
 sync(items,authoritative=false){
  // One malformed record does not abort a whole batch. Duplicate IDs have a single stable marker.
  const next=new Map();for(const raw of items){const d=adaptCondition(raw);if(d)next.set(d.id,d);else{const id=raw?.id??raw?.properties?.id;if(this.markers.has(id))next.set(id,this.markers.get(id).data);}}
  for(const [id,data] of next){const existing=this.markers.get(id);if(existing)existing.updateData(data);else this.markers.set(id,createConditionMarker(this.L,this.map,data,this,this.layerGroup));}
  // Full paginated public snapshot, independent of the camera. Absence means
  // revocation/deletion/scope change; zoom and age are never deletion events.
  for(const [id,m] of this.markers)if(!next.has(id)){m.dispose();this.markers.delete(id);}
  this.updateAllConditionMarkers();this.onData([...next.values()]);if(authoritative&&this.autoFramePending){this.autoFramePending=false;this.focusAll(false);}this.updateSummary();
 }
 cancelAutoFrame(){this.autoFramePending=false;}
 updateSummary(){
  const total=this.markers.size,b=this.map.getBounds();let visible=0;
  for(const m of this.markers.values())if(b.contains(m.layer.getLatLng()))visible++;
  let text=uiText(":v0: terlihat di peta · :v1: kondisi dan riwayat dimuat",[visible,total]);
  if(total&&!visible)text+=' · '+uiText('Tekan Tampilkan kondisi untuk melihat lokasinya.');
  if(this.lastUpdated)text+=' · '+uiText('diperbarui :v0:',[new Date(this.lastUpdated).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})]);
  this.onStatus(text,false);
 }
 focus(id){this.cancelAutoFrame();const m=this.markers.get(id);if(!m)return false;const zoom=Math.min(this.map.getMaxZoom?.()??19,Math.max(17,this.map.getZoom()));this.map.stop?.();this.map.setView(m.layer.getLatLng(),zoom,{animate:false});this.open(id);return true;}
 focusAll(manual=true){if(manual)this.cancelAutoFrame();const points=[...this.markers.values()].map(m=>m.layer.getLatLng());if(!points.length||typeof this.map.fitBounds!=='function')return false;this.map.stop?.();this.map.fitBounds(points,{paddingTopLeft:[24,window.innerWidth>900?120:24],paddingBottomRight:[24,100],maxZoom:16,animate:manual&&!this.motion.matches});return true;}
 async refresh(){
  if(this.destroyed||!this.schoolId||!this.endpoint||this.loading)return;
  this.loading=true;const school=this.schoolId,sequence=++this.requestId;this.controller=new AbortController();this.lastPoll=Date.now();const all=[];let cursor=null,pageCount=0;const seen=new Set();
  try{
   do{const url=new URL(this.endpoint,location.origin);url.searchParams.set('school',school);url.searchParams.set('limit','500');if(cursor)url.searchParams.set('cursor',cursor);
    const r=await fetch(url,{signal:this.controller.signal,headers:{Accept:'application/json'},cache:'no-store'});if(!r.ok)throw Error(uiText("HTTP :v0:", [r.status]));const payload=await r.json();
    if(!Array.isArray(payload.items)||String(payload.school_id)!==school)throw Error(uiText("Respons map tidak valid"));
    pageCount++;all.push(...payload.items);cursor=payload.next_cursor||null;if(cursor&&seen.has(cursor))throw Error(uiText("Kursor berulang"));seen.add(cursor);
    const generated=Date.parse(payload.generated_at);if(Number.isFinite(generated))this.offset=Math.max(-86400000,Math.min(86400000,generated-Date.now()));
   }while(cursor&&sequence===this.requestId);
   if(sequence!==this.requestId||this.destroyed)return;this.lastPages=pageCount;this.lastUpdated=Date.now();this.sync(all,true);
  }catch(e){if(sequence===this.requestId&&e.name!=='AbortError')this.onStatus(uiText("Pembaruan kondisi belum tersedia. Marker yang telah dimuat tetap tersimpan di peta."),true);}
  finally{this.loading=false;if(school!==this.schoolId&&!this.destroyed)this.refresh();}
 }
 updateAllConditionMarkers(){
  if(this.destroyed)return;const now=this.now(),bounds=this.map.getBounds().pad(.1),visible=[];
  for(const m of this.markers.values())if(bounds.contains(m.layer.getLatLng())||m.layer.isPopupOpen())visible.push(m);else{const el=m.layer.getElement();if(el?.classList.contains('has-recency-pulse')){el.classList.remove('has-recency-pulse');el.style.setProperty('--marker-pulse','0');m.lastVisual='';}}
  this.map.getContainer().classList?.toggle('condition-dense-view',visible.length>250);
  // Keep all geographically positioned markers; only limit expensive pulse/glow work.
  const pulse=new Set(this.motion.matches||visible.length>250?[]:visible.filter(m=>{const a=calculateMarkerAge(m.data.createdMs,now);return a!==null&&a<15&&m.data.status!=='resolved';}).sort((a,b)=>(b.data.createdMs??0)-(a.data.createdMs??0)).slice(0,40));
  for(const m of visible)m.updateMarkerVisual(now,pulse.has(m));
 }
 nearby(data){const point=this.map.latLngToContainerPoint([data.latitude,data.longitude]);return [...this.markers.values()].filter(m=>point.distanceTo(this.map.latLngToContainerPoint(m.layer.getLatLng()))<18).map(m=>m.data);}
 open(id){const m=this.markers.get(id);if(m){m.layer.openPopup();m.updateMarkerVisual(this.now(),false);}}
 destroy(){if(this.destroyed)return;this.destroyed=true;clearTimeout(this.viewportTimer);this.stop();this.controller?.abort();this.map.off('popupopen',this.onPopupOpen);this.map.off('popupclose',this.onPopupClose);this.map.off('moveend',this.onMove);this.map.off('zoomend',this.onZoom);this.map.getContainer().removeEventListener('click',this.onClick);for(const event of ['pointerdown','wheel','keydown','touchstart'])this.map.getContainer().removeEventListener(event,this.onUserCamera);document.removeEventListener('visibilitychange',this.onVisibility);window.removeEventListener('pagehide',this.onPageHide);window.removeEventListener('pageshow',this.onPageShow);for(const m of this.markers.values())m.dispose();this.markers.clear();}
}
