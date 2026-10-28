const uiHtmlText = (text) => (globalThis.ruteamanTranslate?.(text) ?? text).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {visualType,resolveConditionType} from './condition-types.js';
import {calculateMarkerAge,calculateMarkerIntensity,ageLabel,escapeHtml,safeDetailUrl} from './marker-state.js';
export function createConditionMarker(L,map,data,manager,layerGroup=null){return new ConditionMarker(L,map,data,manager,layerGroup);}
export class ConditionMarker {
 constructor(L,map,data,manager,layerGroup=null){
  this.L=L;this.map=map;this.data=data;this.manager=manager;this.lastVisual='';
  this.layer=L.marker([data.latitude,data.longitude],{icon:this.icon(),keyboard:true,riseOnHover:true,bubblingMouseEvents:false,alt:data.title,zIndexOffset:0}).addTo(layerGroup||map);
  this.layer.bindPopup(()=>this.popup(),{className:'condition-popup',maxWidth:290,minWidth:220,autoPanPadding:[24,24],closeButton:true});
  this.layer.bindTooltip(()=>uiText(":v0: · :v1:", [escapeHtml(this.category()), escapeHtml(ageLabel(calculateMarkerAge(this.data.createdMs,this.manager.now())))]),{direction:'top',offset:[0,-12],className:'condition-tooltip'});
  this.layer.on('popupopen',e=>{this.layer.closeTooltip();e.popup.options.autoPanPaddingTopLeft=[24,window.innerWidth>900?120:24];e.popup.options.autoPanPaddingBottomRight=[24,24];e.popup.update();});
  this.onAdd=()=>this.setupElement();this.layer.on('add',this.onAdd);this.setupElement();
 }
 category(){return this.data.type_label||resolveConditionType(this.data.type).label;}
 icon(){const v=visualType(this.data);return this.L.divIcon({className:'condition-marker',iconSize:[44,44],iconAnchor:[22,22],popupAnchor:[0,-18],tooltipAnchor:[0,-8],html:`<span class="condition-pin" style="--condition-color:${v.color}"><span class="condition-face"></span><span class="condition-glyph">${v.icon}</span>${this.data.historical?'<span class="condition-history-dot" aria-hidden="true"></span>':''}</span>`});}
 setupElement(){const el=this.layer.getElement();if(!el)return;el.setAttribute('role','button');el.setAttribute('aria-label',uiText(":v0: · :v1:. Buka rincian kondisi.", [this.category(), this.data.status_label]));el.dataset.conditionId=this.data.id;
  if(!el.dataset.keyboardBound){el.dataset.keyboardBound='1';el.addEventListener('keydown',e=>{if(e.key===' '){e.preventDefault();this.layer.openPopup();}if(e.key==='Escape')this.layer.closePopup();});}
 }
 popup(){
  const d=this.data,v=visualType(d),age=calculateMarkerAge(d.createdMs,this.manager.now()),url=safeDetailUrl(d.url),date=d.createdMs===null?uiText("Waktu tidak tersedia"):new Date(d.createdMs).toLocaleString(globalThis.ruteamanLocale || 'id-ID',{dateStyle:'medium',timeStyle:'short'});
  const nearby=this.manager.nearby(d).filter(x=>x.id!==d.id),page=Math.max(0,Math.min(this.nearbyPage||0,Math.floor(Math.max(0,nearby.length-1)/20)));this.nearbyPage=page;
  return `<article class="condition-popup-card"><div class="condition-popup-heading"><span style="color:${v.color}">${v.icon}</span><b>${escapeHtml(this.category())}</b></div>${d.status==='resolved'?'<strong class="condition-handled">Tertangani — bukan jaminan kondisi jalan</strong>':''}${d.is_demo?'<small>SIMULASI DEMO</small>':''}<p class="condition-popup-age" data-marker-age>${escapeHtml(ageLabel(age))}</p><dl><div><dt>${uiHtmlText("Dilaporkan")}</dt><dd>${escapeHtml(date)}</dd></div><div><dt>${uiHtmlText("Status")}</dt><dd>${escapeHtml(d.status_label)}</dd></div><div><dt>${uiHtmlText("Lokasi")}</dt><dd>${escapeHtml(d.location||uiText(":v0:, :v1:", [d.latitude.toFixed(5), d.longitude.toFixed(5)]))}</dd></div><div><dt>${uiHtmlText("Bukti")}</dt><dd>${d.reports_count} ${uiHtmlText("laporan ·")} ${d.confirmations_count} ${uiHtmlText("konfirmasi")}${d.severity!==null?uiText(" · bobot :v0:", [escapeHtml(d.severity)]):''}</dd></div></dl><p>${escapeHtml(d.description)}</p>${url?`<a href="${escapeHtml(url)}">${uiHtmlText("Lihat rincian kondisi ↗")}</a>`:''}${nearby.length?`<details class="condition-nearby"><summary>${nearby.length} ${uiHtmlText("kondisi berdekatan")}</summary>${nearby.slice(page*20,page*20+20).map(n=>`<button type="button" data-open-condition="${escapeHtml(n.id)}">${escapeHtml(n.type_label||resolveConditionType(n.type).label)} · ${escapeHtml(n.status_label)}</button>`).join('')}${nearby.length>20?`<div class="condition-nearby-pages">${page>0?`<button type="button" data-nearby-page="${page-1}" data-condition="${escapeHtml(d.id)}">${uiHtmlText("Sebelumnya")}</button>`:''}${(page+1)*20<nearby.length?`<button type="button" data-nearby-page="${page+1}" data-condition="${escapeHtml(d.id)}">${uiHtmlText("20 berikutnya")}</button>`:''}</div>`:''}</details>`:''}</article>`;
 }
 updateData(data){
  const old=this.data,changed=JSON.stringify(old)!==JSON.stringify(data),iconChanged=JSON.stringify(visualType(old))!==JSON.stringify(visualType(data))||old.historical!==data.historical;this.data=data;
  if(old.latitude!==data.latitude||old.longitude!==data.longitude)this.layer.setLatLng([data.latitude,data.longitude]);
  if(iconChanged){this.layer.setIcon(this.icon());this.lastVisual='';this.setupElement();}
  else if(changed)this.setupElement();
  if(changed&&this.layer.isPopupOpen())this.layer.setPopupContent(this.popup());
 }
 updateMarkerVisual(now,pulseAllowed){
  const el=this.layer.getElement();if(!el)return;
  const state=calculateMarkerIntensity(calculateMarkerAge(this.data.createdMs,now)),pulse=pulseAllowed?state.pulse:0;
  const fingerprint=[state.opacity.toFixed(3),state.brightness.toFixed(3),state.glow.toFixed(3),pulse.toFixed(3),state.recency].join(':');
  if(fingerprint!==this.lastVisual){this.lastVisual=fingerprint;el.style.setProperty('--marker-intensity',state.opacity.toFixed(3));el.style.setProperty('--marker-brightness',state.brightness.toFixed(3));const blend=Math.max(0,Math.min(1,(state.opacity-.72)/.16)),contrast=blend*blend*(3-2*blend);el.style.setProperty('--marker-fill',(.12+.88*contrast).toFixed(3));el.style.setProperty('--marker-white',(contrast*100).toFixed(2)+'%');el.style.setProperty('--marker-glow',state.glow.toFixed(3));el.style.setProperty('--marker-pulse',pulse.toFixed(3));el.classList.toggle('has-recency-pulse',pulse>.005);el.dataset.recency=state.recency;}
  if(this.layer.isPopupOpen()){const ageEl=this.layer.getPopup().getElement()?.querySelector('[data-marker-age]');const label=ageLabel(calculateMarkerAge(this.data.createdMs,now));if(ageEl&&ageEl.textContent!==label)ageEl.textContent=label;}
 }
 // Explicit revocation / school switch / teardown only. Never called by intensity or age functions.
 dispose(){this.layer.off();this.layer.remove();}
}
