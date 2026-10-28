const uiHtmlText = (text) => (globalThis.ruteamanTranslate?.(text) ?? text).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {mountHomeGallery} from './features/home-gallery';
import {ConditionMarkerManager} from './features/map/condition-marker-manager';
import {mountConditionLegend} from './features/map/condition-legend';
import {visualType,registerConditionTypes} from './features/map/condition-types';
import {ageLabel,calculateMarkerAge,safeDetailUrl} from './features/map/marker-state';
import './features/admin';
import './features/institution-registration';
import {mountGuestReport} from './features/guest-report';
import './features/account/index';
import { bindLocationButton } from './location';
import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import './leaflet-cluster';
import reportMarkerIcon from 'leaflet/dist/images/marker-icon.png';
import reportMarkerShadow from 'leaflet/dist/images/marker-shadow.png';
import { LiquidGlass } from '@ybouane/liquidglass';
window.Alpine=Alpine;Alpine.start();
mountGuestReport(L,bindLocationButton);
document.querySelectorAll('.toast').forEach(el=>setTimeout(()=>el.remove(),3500));

const conditionCatalog=JSON.parse(document.querySelector('#condition-type-data')?.textContent||'[]');
registerConditionTypes(conditionCatalog);
const root=document.querySelector('#route-app');
if(root){
 const schoolData=JSON.parse(document.querySelector('#school-map-data')?.textContent||'[]');
 const schoolSelect=document.querySelector('#school-select');
 let selectedSchool=schoolData.find(s=>String(s.id)===schoolSelect?.value)||schoolData[0]||{lat:-6.1754,lng:106.8272,name:uiText("Institusi tujuan")};
 let school=[Number(selectedSchool.lat),Number(selectedSchool.lng)],campusTarget=null,campusPinMode=false,campusMarker=null;
 // Global: pilih kampus dari pencarian manual atas -> pasang titik tujuan di peta
 window.__ruteamanSelectCampus=function(item){
  if(!item||!item.name)return;
  invalidateAnalysis();campusPinMode=false;pickMode=false;
  const lat=Number(item.latitude),lng=Number(item.longitude);
  const hasCoords=Number.isFinite(lat)&&Number.isFinite(lng);
  campusTarget={name:item.name,confirmed:hasCoords};
  const gdA=document.querySelector('#graph-dest-name');if(gdA)gdA.textContent=item.name;
  campusMarker?.remove();campusMarker=null;
  compare.disabled=true;routeLayers.forEach(l=>l.remove());routeLayers=[];
  const resEl=document.querySelector('#route-results'),sumEl=document.querySelector('#analysis-summary');
  if(resEl)resEl.replaceChildren();if(sumEl)sumEl.hidden=true;schoolRequest++;
  if(hasCoords){
   school=[lat,lng];
   campusMarker=L.circleMarker(school,{radius:12,color:'#fff',weight:3,fillColor:'#714f8e',fillOpacity:1}).addTo(map).bindPopup('<b>'+String(item.name).replace(/</g,'&lt;')+'</b><br>'+(item._kind==='sekolah'?'Titik tujuan sekolah':'Titik tujuan kampus'));
   hideSchoolPins();focusPinMarker?.remove();focusPinMarker=null;
   campusMarker.openPopup();
   map.setView(school,15);
   // Animasi tambahan untuk memastikan peta terlihat bergerak
   setTimeout(()=>{map.invalidateSize();map.setView(school,15,{animate:true});},100);
  }else{
   // Tidak ada koordinat: aktifkan mode pin otomatis agar user bisa tentukan lokasi
   hideSchoolPins();
   campusPinMode=true;
   map.getContainer().style.cursor='crosshair';
  }
  const st=document.querySelector('#campus-status-top')||document.querySelector('#campus-status');
  if(st)st.textContent=hasCoords?(item._kind==='sekolah'?'Titik tujuan: '+item.name+'. Sekarang pilih titik awal.':'Titik tujuan: '+item.name+' (koordinat kota, tentukan pin tepat bila perlu)'):'Koordinat tidak tersedia. Ketuk peta untuk menentukan lokasi '+item.name+'.';
  const stMain=document.querySelector('#route-status');
  if(stMain)stMain.textContent=hasCoords?'Tujuan dipilih: '+item.name+'. Sekarang pilih titik awal.':'Tujuan dipilih: '+item.name+'. Ketuk peta untuk menentukan lokasinya.';
 };
 document.addEventListener('campus-selected',e=>{if(e.detail)window.__ruteamanSelectCampus(e.detail);});
 document.addEventListener('school-selected',e=>{if(e.detail)window.__ruteamanSelectCampus({...e.detail,_kind:'sekolah'});});
 const map=L.map('route-map',{zoomControl:true,minZoom:5,maxZoom:19,maxBounds:[[-11.5,93],[7,142]],maxBoundsViscosity:.8}).setView(school,14);
 const clusterGroup=L.markerClusterGroup({disableClusteringAtZoom:15,maxClusterRadius:55,showCoverageOnHover:false,spiderfyOnMaxZoom:true,chunkedLoading:true}).addTo(map);
 const schoolCluster=L.markerClusterGroup({disableClusteringAtZoom:13,maxClusterRadius:50,showCoverageOnHover:false,chunkedLoading:true}).addTo(map);
 // === Global API untuk unified search (diakses dari inline script) ===
 window.__ruteamanMap={
  getMap:()=>map,
  moveTo(lat,lng,name,zoom=15){
   lat=Number(lat);lng=Number(lng);
   if(!Number.isFinite(lat)||!Number.isFinite(lng))return false;
   try{
    if(window.__rmMarker){try{map.removeLayer(window.__rmMarker);}catch(e){}}
    const m=L.circleMarker([lat,lng],{radius:13,color:'#fff',weight:3,fillColor:'#714f8e',fillOpacity:1}).addTo(map);
    if(name)m.bindPopup('<b>'+String(name).replace(/</g,'&lt;')+'</b>').openPopup();
    window.__rmMarker=m;
    try{map.flyTo([lat,lng],zoom,{duration:1.4});}catch(e){map.setView([lat,lng],zoom);}
    setTimeout(()=>{try{map.invalidateSize();}catch(e){}},150);
    return true;
   }catch(e){return false;}
  },
  selectSchoolById(id){
   const sel=document.querySelector('#school-select');
   if(sel){sel.value=String(id);sel.dispatchEvent(new Event('change',{bubbles:true}));return true;}
   return false;
  },
  getSchoolData(){
   try{return JSON.parse(document.querySelector('#school-map-data')?.textContent||'[]');}catch(e){return [];}
  }
 };
 const tileStatus=document.querySelector('#map-tile-status');let tileErrors=0,tileFallback=false;
 const attachTileStatus=(layer)=>{layer.on('loading',()=>{tileErrors=0;});layer.on('tileerror',()=>{tileErrors++;if(tileStatus){tileStatus.hidden=false;tileStatus.textContent=uiText('Peta dasar belum termuat lengkap. Marker kondisi tetap tersedia; periksa koneksi lalu coba lagi.');}if(tileErrors>=6&&!tileFallback){tileFallback=true;map.removeLayer(layer);attachTileStatus(L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(map));}});layer.on('load',()=>{if(tileStatus&&!tileErrors)tileStatus.hidden=true;});};
 attachTileStatus(L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(map));
 let resizeFrame,lastMapSize='';const resizeMap=()=>{const box=map.getContainer().getBoundingClientRect(),key=box.width+':'+box.height;if(key===lastMapSize||!box.width||!box.height)return;lastMapSize=key;cancelAnimationFrame(resizeFrame);resizeFrame=requestAnimationFrame(()=>map.invalidateSize({pan:false}));};
 const mapResize=globalThis.ResizeObserver?new ResizeObserver(resizeMap):null;mapResize?.observe(map.getContainer());document.fonts?.ready.then(resizeMap);window.addEventListener('pagehide',e=>{if(!e.persisted){mapResize?.disconnect();cancelAnimationFrame(resizeFrame);}},{once:false});
 const schoolMarkers=new Map();
 schoolData.forEach(item=>{const marker=L.circleMarker([item.lat,item.lng],{radius:10,color:'#fff',weight:3,fillColor:'#359FA0',fillOpacity:1}).addTo(schoolCluster).bindPopup(`<b>${escapeHtml(item.name)}</b><br>${uiHtmlText("Klik untuk memilih tujuan")}`);marker.on('click',()=>selectSchool(item.id,true));schoolMarkers.set(String(item.id),marker);});
 let focusPinMarker=null;
 const hideSchoolPins=()=>{if(map.hasLayer(schoolCluster))map.removeLayer(schoolCluster);};
 const showFocusPin=(la,ln,name)=>{focusPinMarker?.remove();focusPinMarker=L.circleMarker([la,ln],{radius:13,color:'#fff',weight:3,fillColor:'#1F6E70',fillOpacity:1}).addTo(map).bindPopup(`<b>${escapeHtml(name)}</b>`);};
 const clearFocusPins=()=>{focusPinMarker?.remove();focusPinMarker=null;if(!map.hasLayer(schoolCluster))map.addLayer(schoolCluster);};
 document.querySelector('#sic-close')?.addEventListener('click',clearFocusPins);
 let catchmentLayers=[],schoolRequest=0,analysisRequest=0;
 const hazardData=JSON.parse(document.querySelector('#hazard-map-data')?.textContent||'[]');
 const conditionManager=new ConditionMarkerManager(L,map,{layerGroup:clusterGroup,endpoint:root.dataset.feedEndpoint,onData:renderConditions,onStatus:(message,stale)=>{const el=document.querySelector('#condition-sync-status');if(el){el.textContent=message;el.classList.toggle('is-stale',stale);}}});
 mountConditionLegend(document.querySelector('#condition-legend'),conditionCatalog);
 function renderConditions(items){
  const list=document.querySelector('#school-hazard-list');if(!list)return;
  const recent=[...items].sort((a,b)=>(b.createdMs??0)-(a.createdMs??0)).slice(0,24);
  document.querySelector('#fit-condition-markers')?.toggleAttribute('disabled',!items.length);
  list.innerHTML=recent.length?recent.map(h=>{const v=visualType(h),url=safeDetailUrl(h.url);return `<div class="condition-list-row"><a class="mini-hazard liquid-glass" data-glass-variant="row" href="${escapeHtml(url||'#')}"><span class="condition-list-icon" style="color:${v.color}">${v.icon}</span><div><b>${escapeHtml(h.type_label||h.title)}</b><small>${escapeHtml(h.status_label)} · ${escapeHtml(ageLabel(calculateMarkerAge(h.createdMs,conditionManager.now())))}</small></div><i>›</i></a><button type="button" class="condition-focus-button" data-focus-condition="${escapeHtml(h.id)}" aria-label="${escapeHtml(uiText('Lihat di peta: :v0:',[h.type_label||h.title]))}" title="${uiHtmlText('Lihat di peta')}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="6"/><path d="M12 2v6m0 8v6M2 12h6m8 0h6"/></svg></button></div>`;}).join(''):'<p>'+uiHtmlText('Belum ada kondisi publik yang dimuat. Ini bukan jaminan kondisi jalan.')+'</p>';
 }
 const revealConditionMap=()=>{if(innerWidth<=900)document.querySelector('#route-map').scrollIntoView({block:'start',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});};
 document.querySelector('#fit-condition-markers')?.addEventListener('click',()=>{if(conditionManager.focusAll())revealConditionMap();});
 document.querySelector('#school-hazard-list')?.addEventListener('click',e=>{const trigger=e.target.closest('[data-focus-condition]');if(trigger&&conditionManager.focus(trigger.dataset.focusCondition))revealConditionMap();});
 let originMarker=null,routeLayers=[],pickMode=false;
 const lat=document.querySelector('#origin-lat'),lng=document.querySelector('#origin-lng'),label=document.querySelector('#origin-label'),compare=document.querySelector('#compare-routes'),status=document.querySelector('#route-status'),results=document.querySelector('#route-results'),summary=document.querySelector('#analysis-summary');
 function invalidateAnalysis(){analysisRequest++;compare.textContent=uiText("Analisis alternatif rute");results.classList.remove("is-loading");}
 const setOrigin=(la,ln,text,accuracy=null)=>{
  lat.value=Number(la).toFixed(7);lng.value=Number(ln).toFixed(7);label.value=text;compare.disabled=Boolean(campusTarget&&!campusTarget.confirmed);
  if(originMarker)originMarker.remove();originMarker=L.circleMarker([la,ln],{radius:9,color:'#1F6E70',weight:4,fillColor:'#fff',fillOpacity:1}).addTo(map).bindPopup(uiText("Titik awal sekali pakai"));
  status.textContent=accuracy&&accuracy>50?uiText("Akurasi sekitar :v0: m. Geser pilihan dengan mengetuk peta bila kurang tepat.", [Math.round(accuracy)]):uiText("Titik awal siap. Tekan “Analisis alternatif rute”.");
 };
 const selectSchool=(id,focus=false)=>{
  invalidateAnalysis();compare.disabled=!lat.value||!lng.value;
  campusTarget=null;campusPinMode=false;campusMarker?.remove();campusMarker=null;
  selectedSchool=schoolData.find(item=>String(item.id)===String(id))||selectedSchool;school=[Number(selectedSchool.lat),Number(selectedSchool.lng)];
  const gdB=document.querySelector('#graph-dest-name');if(gdB)gdB.textContent=selectedSchool.name;
  if(schoolSelect)schoolSelect.value=String(selectedSchool.id);
  globalThis.__ruteamanReflectSchoolSearch?.(selectedSchool);
  schoolMarkers.forEach((marker,key)=>marker.setStyle({radius:key===String(selectedSchool.id)?13:10,fillColor:key===String(selectedSchool.id)?'#1F6E70':'#359FA0'}));
  if(focus)showFocusPin(Number(selectedSchool.lat),Number(selectedSchool.lng),selectedSchool.name);
  routeLayers.forEach(layer=>layer.remove());routeLayers=[];results.innerHTML='';summary.hidden=true;
  status.textContent=uiText("Tujuan dipilih: :v0:. :v1:", [selectedSchool.name, lat.value?'Tekan “Analisis alternatif rute”.':'Sekarang pilih titik awal.']);
  catchmentLayers.forEach(layer=>layer.remove());catchmentLayers=[];
  // Batasan wilayah (catchment) disembunyikan sesuai permintaan: peta tampil lebih luas tanpa garis batas
  // schoolData.filter(...).forEach(...); // DINONAKTIFKAN
  if(focus){try{map.flyTo(school,16,{duration:1.4});}catch(e){map.setView(school,16);}schoolMarkers.get(String(selectedSchool.id))?.openPopup();}
  conditionManager.setSchool(selectedSchool.id,String(selectedSchool.id)===String(schoolData.find(s=>String(s.id)===schoolSelect?.value)?.id)&&schoolRequest===0?hazardData:[]);conditionManager.refresh();
  loadSchoolBoard();
 };
 async function loadSchoolBoard(){
  if(campusTarget){status.textContent=uiText('Tujuan kampus tidak memiliki rute tersimpan sekolah. Gunakan Analisis graph jalan.');return;}
  invalidateAnalysis();
  const sequence=++schoolRequest,id=selectedSchool.id;
  results.innerHTML='<p>Memuat rute tersimpan…</p>';
  try{
   const r=await fetch(`${root.dataset.savedEndpoint}?school=${encodeURIComponent(id)}`,{headers:{Accept:'application/json'}});
   if(!r.ok)throw new Error(uiText("Data wilayah tidak dapat dimuat."));
   const board=await r.json();if(sequence!==schoolRequest)return;
   renderRoutes(board.routes||[],false);
   status.textContent=board.routes.length?uiText(":v0: · :v1: · diperbarui :v2:", [selectedSchool.name, board.source, new Date(board.fetched_at).toLocaleTimeString('id-ID')]):uiText(":v0: belum memiliki rute tersimpan. Kamu dapat memakai analisis graph jika provider tersedia.", [selectedSchool.name]);
  }catch(error){if(sequence!==schoolRequest)return;results.innerHTML='';status.textContent=uiText(":v0: Daftar hazard yang sudah dimuat tetap tersedia.", [error.message]);}
 }
 document.querySelector('#show-saved-routes')?.addEventListener('click',()=>{loadSchoolBoard();conditionManager.refresh();});
 selectSchool(selectedSchool.id,false);
 const focusedSlug=new URLSearchParams(location.search).get('hazard');if(focusedSlug){conditionManager.cancelAutoFrame();const h=conditionManager.markers.get(focusedSlug);if(h){map.setView(h.layer.getLatLng(),17);conditionManager.open(focusedSlug);}}
 schoolSelect?.addEventListener('change',()=>selectSchool(schoolSelect.value,true));
 const campusQuery=document.querySelector('#campus-query'),campusCountry=document.querySelector('#campus-country'),campusResults=document.querySelector('#campus-results'),campusStatus=document.querySelector('#campus-status'),campusMore=document.querySelector('#campus-more'),campusSearch=document.querySelector('#campus-search');let campusPage=1,campusSearchKey='',campusSearchSequence=0;
 async function searchCampuses(next=false){const query=campusQuery.value.trim(),country=campusCountry.value.trim().toUpperCase();if(query.length<3||query.length>150||(country&&!/^[A-Z]{2}$/.test(country))){campusStatus.textContent=uiText('Masukkan nama kampus minimal 3 karakter dan kode negara dua huruf, atau kosongkan negara.');return;}const key=JSON.stringify([query,country]);if(!next||key!==campusSearchKey){campusPage=1;campusResults.replaceChildren();}else campusPage++;campusSearchKey=key;const sequence=++campusSearchSequence;campusSearch.disabled=true;campusMore.hidden=true;campusStatus.textContent=uiText('Mencari institusi di direktori ROR…');try{const u=new URL(root.dataset.campusEndpoint,location.origin);u.searchParams.set('query',query);if(country)u.searchParams.set('country',country);u.searchParams.set('page',String(campusPage));const response=await fetch(u,{headers:{Accept:'application/json'}}),data=await response.json();if(sequence!==campusSearchSequence)return;if(!response.ok)throw Error(data.error?.message||uiText('Direktori tidak tersedia.'));for(const item of data.data||[]){const button=document.createElement('button');button.type='button';button.className='btn btn-soft';button.textContent=[item.name,item.city,item.country_name].filter(Boolean).join(' · ');button.addEventListener('click',()=>{invalidateAnalysis();campusPinMode=false;pickMode=false;campusTarget={name:item.name,confirmed:false};campusQuery.value=item.name;campusMarker?.remove();campusMarker=null;compare.disabled=true;routeLayers.forEach(l=>l.remove());routeLayers=[];results.replaceChildren();summary.hidden=true;schoolRequest++;if(Number.isFinite(Number(item.latitude))&&Number.isFinite(Number(item.longitude))){map.setView([Number(item.latitude),Number(item.longitude)],13);setTimeout(()=>{map.invalidateSize();},100);}campusStatus.textContent=uiText('Ini referensi kota, bukan lokasi kampus yang pasti. Tekan Tentukan pin tujuan kampus di peta.');});campusResults.append(button);}campusMore.hidden=!data.has_more;campusStatus.textContent=(data.data||[]).length?uiText('Hasil ROR, bukan daftar lengkap. Pilih institusi lalu tetapkan pin kampus yang tepat.'):uiText('Tidak ditemukan di ROR. Kamu tetap bisa mengisi nama dan menentukan pin kampus manual.');}catch(e){campusStatus.textContent=e.message;}finally{if(sequence===campusSearchSequence)campusSearch.disabled=false;}}
 campusSearch?.addEventListener('click',()=>searchCampuses(false));campusMore?.addEventListener('click',()=>searchCampuses(true));document.querySelector('#campus-pin')?.addEventListener('click',()=>{invalidateAnalysis();campusTarget={name:campusQuery.value.trim()||uiText('Kampus pilihan'),confirmed:false};campusPinMode=true;pickMode=false;compare.disabled=true;schoolRequest++;routeLayers.forEach(l=>l.remove());routeLayers=[];results.replaceChildren();summary.hidden=true;campusStatus.textContent=uiText('Ketuk lokasi tujuan kampus yang sebenarnya di peta. Titik ini hanya dipakai untuk analisis perjalanan.');});
 document.querySelector('#pick-school')?.addEventListener('click',()=>{conditionManager.cancelAutoFrame();status.textContent=uiText("Klik salah satu penanda sekolah / kampus berwarna teal di peta.");map.fitBounds(L.latLngBounds(schoolData.map(item=>[item.lat,item.lng])),{padding:[45,45]});});
 bindLocationButton(document.querySelector('#use-location'),root.querySelector('[data-location-help]'),p=>{
  conditionManager.cancelAutoFrame();setOrigin(p.coords.latitude,p.coords.longitude,uiText("Lokasi perangkat (sekali pakai)"),p.coords.accuracy);
  map.setView([p.coords.latitude,p.coords.longitude],16);
 });
 document.querySelector('#pick-map').addEventListener('click',()=>{campusPinMode=false;pickMode=true;status.textContent=uiText("Ketuk jalan pada peta untuk menentukan titik awal.");map.getContainer().style.cursor='crosshair';});
 map.on('click',e=>{if(campusPinMode){campusPinMode=false;school=[e.latlng.lat,e.latlng.lng];campusTarget={name:document.querySelector('#campus-query').value.trim()||uiText('Kampus pilihan'),confirmed:true};campusMarker?.remove();campusMarker=L.circleMarker(school,{radius:10,color:'#fff',weight:3,fillColor:'#714f8e'}).addTo(map).bindPopup(escapeHtml(campusTarget.name));compare.disabled=!lat.value||!lng.value;document.querySelector('#campus-status').textContent=uiText('Pin kampus dipilih. Pilih titik awal, lalu gunakan Analisis graph jalan.');return;}if(!pickMode)return;pickMode=false;map.getContainer().style.cursor='';setOrigin(e.latlng.lat,e.latlng.lng,uiText("Titik yang dipilih di peta"));});
 // Pencarian tempat untuk titik awal (geocoding OpenStreetMap, pola RuteAman Umum)
 const originResults=document.querySelector('#origin-results');
 let originSearchTimer=null,originSearchSeq=0,originChosenLabel='';
 const hideOriginResults=()=>{if(originResults){originResults.hidden=true;originResults.innerHTML='';}};
 const originDistanceKm=(la,ln)=>{if(!school||school.length<2)return null;const R=6371,toRad=x=>x*Math.PI/180;const dLa=toRad(la-school[0]),dLn=toRad(ln-school[1]);const a=Math.sin(dLa/2)**2+Math.cos(toRad(school[0]))*Math.cos(toRad(la))*Math.sin(dLn/2)**2;const km=2*R*Math.asin(Math.sqrt(a));return isFinite(km)?km:null;};
 label?.addEventListener('input',()=>{
  if(label.value!==originChosenLabel){lat.value='';lng.value='';compare.disabled=true;if(originMarker){originMarker.remove();originMarker=null;}}
  if(!originResults)return;
  const q=label.value.trim();clearTimeout(originSearchTimer);
  if(q.length<3){hideOriginResults();return;}
  originSearchTimer=setTimeout(async()=>{
   const seq=++originSearchSeq;
   try{
    const r=await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&countrycodes=id&q=${encodeURIComponent(q)}`,{headers:{Accept:'application/json'}});
    if(!r.ok)throw new Error('geocode');
    const items=await r.json();if(seq!==originSearchSeq)return;
    originResults.innerHTML='';
    if(!items.length){originResults.innerHTML=`<p class="op-empty">${escapeHtml(uiText('Tempat tidak ditemukan. Coba kata kunci lain, atau pakai GPS / pilih di peta.'))}</p>`;}
    items.forEach(item=>{
     const parts=String(item.display_name||'').split(',').map(s=>s.trim()).filter(Boolean);
     const name=parts[0]||uiText('Tempat tanpa nama');const addr=parts.slice(1,3).join(', ');
     const dist=originDistanceKm(Number(item.lat),Number(item.lon));
     const btn=document.createElement('button');btn.type='button';
     btn.innerHTML=`<span aria-hidden="true">📍</span><span><span class="op-name">${escapeHtml(name)}</span><span class="op-addr">${escapeHtml(addr)}</span></span>${dist!==null?`<span class="op-dist">${dist<10?dist.toFixed(1).replace('.',','):Math.round(dist)} km</span>`:''}`;
     btn.addEventListener('click',()=>{originChosenLabel=name;setOrigin(Number(item.lat),Number(item.lon),name);hideOriginResults();});
     originResults.appendChild(btn);
    });
    originResults.hidden=false;
   }catch(e){if(seq===originSearchSeq){originResults.innerHTML=`<p class="op-empty">${escapeHtml(uiText('Pencarian tempat gagal. Periksa koneksi, atau pakai GPS / pilih di peta.'))}</p>`;originResults.hidden=false;}}
  },450);
 });
 document.addEventListener('click',e=>{if(originResults&&!originResults.hidden&&!e.target.closest('#origin-results')&&!e.target.closest('.origin-field'))hideOriginResults();});
 // Pil mode perjalanan (menyinkronkan <select> lama yang tetap dipakai logika analisis)
 const profileSelect=document.querySelector('#route-profile'),pillWrap=document.querySelector('#mode-pills');
 if(pillWrap&&profileSelect){
  const syncPills=()=>pillWrap.querySelectorAll('[data-profile]').forEach(b=>b.classList.toggle('active',b.dataset.profile===profileSelect.value));
  pillWrap.querySelectorAll('[data-profile]').forEach(b=>b.addEventListener('click',()=>{profileSelect.value=b.dataset.profile;syncPills();profileSelect.dispatchEvent(new Event('change',{bubbles:true}));}));
  syncPills();
 }
 compare.addEventListener('click',async()=>{
  if(!lat.value||!lng.value||(campusTarget&&!campusTarget.confirmed))return;const sequence=++analysisRequest;schoolRequest++;compare.disabled=true;compare.textContent=uiText("Menganalisis graph…");status.textContent=uiText("Mencari alternatif, menghitung waktu, lalu menempelkan hazard…");results.classList.add('is-loading');results.innerHTML='<div class="route-skeleton" aria-hidden="true"></div><div class="route-skeleton" aria-hidden="true"></div>';summary.hidden=true;routeLayers.forEach(layer=>layer.remove());routeLayers=[];
  try{
   const response=await fetch(root.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({school:selectedSchool.id||null,origin:{lat:Number(lat.value),lng:Number(lng.value)},destination:{lat:school[0],lng:school[1]},profile:document.querySelector('#route-profile').value})});
   const data=await response.json();if(sequence!==analysisRequest)return;if(!response.ok)throw new Error(data.error?.message||uiText("Perbandingan rute gagal."));renderRoutes(data.routes||[]);status.textContent=uiText(":v0: alternatif dianalisis · sumber :v1: · :v2:", [data.routes.length, data.source, new Date(data.fetched_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})]);
  }catch(error){if(sequence!==analysisRequest)return;results.innerHTML='';status.innerHTML=`<b>${uiHtmlText("Perbandingan rute sedang tidak tersedia.")}</b><br>${escapeHtml(error.message)} ${uiHtmlText("Peta dan daftar hazard tetap dapat digunakan.")}`;}
  finally{if(sequence===analysisRequest){results.classList.remove('is-loading');compare.disabled=Boolean(campusTarget&&!campusTarget.confirmed);compare.textContent=uiText("Analisis alternatif rute");}}
 });
 function renderRoutes(routes,fitCamera=true){
  const colors=['#1F6E70','#B8480F','#7B5A9B'];let bounds=L.latLngBounds([]);
  routeLayers.forEach(layer=>layer.remove());routeLayers=[];
  results.innerHTML=routes.map((route,index)=>{
   const minutes=route.duration_s?Math.max(1,Math.round(route.duration_s/60)):null,km=(route.distance_m/1000).toLocaleString(globalThis.ruteamanLocale || 'id-ID',{minimumFractionDigits:1,maximumFractionDigits:1}),load=route.load||{},hazards=route.hazards||[],labelText=load.label_code==='no_report'?uiText("Belum ada laporan aktif"):uiText("Beban :v0: · :v1:", [load.value, load.label]);
   const steps=(route.steps||[]).slice(0,8).map((step,i)=>`<li><b>${i+1}</b><span>${escapeHtml(step.instruction)}<small>${formatDistance(step.distance_m)}</small></span></li>`).join('');
   const details=(load.details||[]).map(d=>`<tr><td>${escapeHtml(d.type)}</td><td>${d.severity}</td><td>${Number(d.c).toFixed(2)}</td><td>${Number(d.f).toFixed(2)}</td><td>${d.w}</td><td>${Number(d.h).toFixed(2)}</td></tr>`).join('');
   return `<article class="route-card graph-route-card liquid-glass" data-glass-variant="card" tabindex="0" data-route-index="${index}"><div><span class="route-letter ${index?'alt':''}">${escapeHtml(String(route.id).replace(/^Rute\s+/i,''))}</span><span class="load ${load.label_code==='heavy'?'heavy':load.label_code==='moderate'?'moderate':'light'}">${escapeHtml(labelText)}</span></div><div class="route-primary"><h2>${minutes??km}<small>${minutes?uiText(" menit"):uiText(" km")}</small></h2><b>${minutes?uiText(":v0: km", [km]):route.extra_distance_m?`+${formatDistance(route.extra_distance_m)}`:route.is_demo?uiText("Jarak skenario"):uiText("Jarak tersimpan")}${minutes&&route.extra_distance_m?uiText(" (+:v0:)", [formatDistance(route.extra_distance_m)]):''}</b></div>${route.is_demo?`<p class="route-source">${escapeHtml(uiText("SIMULASI DEMO — belum audit lapangan"))}</p>`:''}<p>${hazards.length} ${uiHtmlText("hazard aktif di sepanjang rute · data dianalisis saat ini")}${route.expired_nearby?` · <span style="color:#b7791f">${route.expired_nearby} ${uiHtmlText("riwayat di sekitar rute, tidak dihitung aktif")}</span>`:''}</p><div class="route-hazards">${hazards.length?hazards.map(h=>`<a href="${escapeHtml(h.url)}">⚠ ${escapeHtml(h.label)} · ${escapeHtml(h.status)} · ${ageText(h.last_confirmed_at)}</a>`).join(''):'<span>? Belum ada laporan aktif. Ini bukan jaminan kondisi aman. Data di rute ini masih sedikit. Simbol di peta juga dapat berupa riwayat. Laporan yang dipublikasikan tidak harus terverifikasi untuk masuk hitungan, tetapi harus masih aktif dan berada dalam radius jalur.</span>'}</div><details class="route-load-details"><summary>${uiHtmlText("Kenapa tingkatnya begini?")}</summary><div class="table-scroll"><table><thead><tr><th>${uiHtmlText("Jenis")}</th><th>${uiHtmlText("Bobot")}</th><th>${uiHtmlText("c")}</th><th>${uiHtmlText("f")}</th><th>${uiHtmlText("w")}</th><th>${uiHtmlText("h")}</th></tr></thead><tbody>${details||'<tr><td colspan="6">Belum ada hazard aktif yang dihitung.</td></tr>'}</tbody></table></div><p>${uiHtmlText("Beban = pembulatan min(100, 100 × Σh /")} ${load.saturation??9}${uiHtmlText("). Keyakinan dari bukti independen, foto, dan verifikasi; kesegaran dari umur data; cuaca dari cache/manual. Heuristik, bukan prediksi.")}</p></details><details><summary>${uiHtmlText("Geometri dan petunjuk tambahan")}</summary><ol class="direction-list">${steps||'<li>Petunjuk langkah tidak tersedia dari provider.</li>'}</ol><p>${uiHtmlText("Angka beban dihitung dari hazard aktif dalam radius")} ${escapeHtml(root.dataset.bufferMeters||25)} ${uiHtmlText("meter dari garis rute.")}</p></details></article>`;
  }).join('');
  routes.forEach((route,index)=>{const layer=L.geoJSON(route.geometry,{style:{color:colors[index]||'#7B5A9B',weight:index===0?7:6,dashArray:index===0?null:'12 9',opacity:.92}}).addTo(map);routeLayers.push(layer);bounds.extend(layer.getBounds());});
  if(originMarker)bounds.extend(originMarker.getLatLng());bounds.extend(school);if(fitCamera&&routes.length&&bounds.isValid()){conditionManager.cancelAutoFrame();map.fitBounds(bounds,{padding:[35,35],maxZoom:17});}
  document.querySelectorAll('.graph-route-card').forEach(card=>{const select=()=>{document.querySelectorAll('.graph-route-card').forEach(c=>c.classList.remove('selected'));card.classList.add('selected');conditionManager.cancelAutoFrame();map.fitBounds(routeLayers[Number(card.dataset.routeIndex)].getBounds(),{padding:[45,45]});};card.addEventListener('click',event=>{if(!event.target.closest('a,button,summary,details'))select();});card.addEventListener('keydown',event=>{if(event.target===card&&(event.key==='Enter'||event.key===' ')){event.preventDefault();select();}});});
  summary.hidden=!routes.length;
  if(routes.length){summary.hidden=false;summary.textContent=uiText("Bandingkan jarak dan Beban Hazard secara terpisah. Urutan rute tetap, bukan rekomendasi. Belum ada laporan bukan jaminan kondisi aman.");}
 }
}
function formatDistance(m){return m>=1000?uiText(":v0: km", [(m/1000).toFixed(1)]):uiText(":v0: m", [Math.round(m)]);}
function ageText(value){if(!value)return uiText("belum dikonfirmasi");const minutes=Math.max(0,Math.round((Date.now()-new Date(value).getTime())/60000));return minutes<60?uiText(":v0: menit lalu", [minutes]):minutes<1440?uiText(":v0: jam lalu", [Math.round(minutes/60)]):uiText(":v0: hari lalu", [Math.round(minutes/1440)]);}
function escapeHtml(value){return String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));}

document.querySelectorAll('[data-school-directory]').forEach(directory=>{
 const form=directory.closest('form');
 const province=directory.querySelector('#register-province'),regency=directory.querySelector('#register-regency'),district=directory.querySelector('#register-district');
 const query=directory.querySelector('#school-directory-query'),npsn=directory.querySelector('#school-directory-npsn'),schoolName=directory.querySelector('#school-directory-name');
 const backup=form.querySelector('[name="selected_school_npsn"]');
 const results=directory.querySelector('[data-directory-results]'),status=directory.querySelector('[data-directory-status]');
 const selected=directory.querySelector('[data-directory-selected]'),selectedName=directory.querySelector('[data-selected-name]'),selectedNpsn=directory.querySelector('[data-selected-npsn]');
 let chosen=null,timer,searchController,searchVersion=0,regionVersion=0,regionLoading=true;
 const request=async(url,params={},signal=null)=>{
  const endpoint=new URL(url,location.origin);Object.entries(params).forEach(([key,value])=>endpoint.searchParams.set(key,value));
  const response=await fetch(endpoint,{headers:{Accept:'application/json'},signal});const payload=await response.json();
  if(!response.ok)throw new Error(payload.message||uiText("Data tidak dapat dimuat."));return payload.data||[];
 };
 const fillSelect=(element,items,placeholder,value='')=>{
  element.replaceChildren(new Option(placeholder,''));items.forEach(item=>element.add(new Option(item.name,item.code)));element.value=value;
 };
 const cancelSearch=()=>{clearTimeout(timer);searchVersion++;searchController?.abort();};
 const syncChosen=()=>{
  if(!chosen)return;
  npsn.value=chosen.npsn;schoolName.value=chosen.name;if(backup)backup.value=chosen.npsn;
  selectedName.textContent=chosen.name||uiText("Sekolah yang dipilih");selectedNpsn.textContent=uiText("NPSN :v0:", [chosen.npsn]);
  selected.hidden=false;query.value=chosen.name||chosen.npsn;query.readOnly=true;
  status.textContent=uiText("Sekolah dipilih. NPSN ini akan dikirim saat kamu menekan Daftar.");
 };
 const clearSchool=()=>{
  cancelSearch();chosen=null;npsn.value='';schoolName.value='';if(backup)backup.value='';
  selected.hidden=true;query.readOnly=false;results.replaceChildren();
 };
 const chooseSchool=item=>{
  cancelSearch();chosen={npsn:String(item.npsn).trim().toUpperCase(),name:String(item.name||''),province:province.value,regency:regency.value,district:district.value};
  results.replaceChildren();query.disabled=false;syncChosen();
 };
 const loadDistricts=async(version,value='')=>{
  district.disabled=true;query.disabled=true;fillSelect(district,[],uiText("Memuat kecamatan…"));
  if(!regency.value){fillSelect(district,[],uiText("Pilih kabupaten/kota dahulu"));return;}
  const items=await request(directory.dataset.districtsUrl,{province_code:province.value,regency_code:regency.value});
  if(version!==regionVersion)return;
  fillSelect(district,items,uiText("Pilih kecamatan"),value);district.disabled=false;query.disabled=!district.value;
 };
 const loadRegencies=async(version,value='',districtValue='')=>{
  regency.disabled=true;district.disabled=true;query.disabled=true;
  fillSelect(regency,[],uiText("Memuat kabupaten/kota…"));fillSelect(district,[],uiText("Pilih kabupaten/kota dahulu"));
  if(!province.value){fillSelect(regency,[],uiText("Pilih provinsi dahulu"));return;}
  const items=await request(directory.dataset.regenciesUrl,{province_code:province.value});
  if(version!==regionVersion)return;
  fillSelect(regency,items,uiText("Pilih kabupaten/kota"),value);regency.disabled=false;
  if(regency.value)await loadDistricts(version,districtValue);
 };
 const changeRegion=async(kind)=>{
  const version=++regionVersion;regionLoading=true;clearSchool();query.value='';status.textContent=uiText("Memuat wilayah…");
  try{
   if(kind==='province')await loadRegencies(version);
   else if(kind==='regency')await loadDistricts(version);
   else query.disabled=!district.value;
   if(version!==regionVersion)return;
   status.textContent=district.value?uiText("Ketik minimal 3 karakter nama sekolah atau NPSN."):uiText("Pilih wilayah sekolah secara berurutan.");
  }catch(error){if(version===regionVersion)status.textContent=error.message;}
  finally{if(version===regionVersion)regionLoading=false;}
 };
 const renderResults=items=>{
  results.replaceChildren();
  if(!items.length){status.textContent=uiText("Sekolah belum ditemukan untuk wilayah ini. Periksa wilayah atau coba NPSN.");return;}
  status.textContent=uiText(":v0: sekolah ditemukan. Pilih satu sekolah.", [items.length]);
  items.forEach(item=>{
   const button=document.createElement('button');button.type='button';button.className='directory-result';button.setAttribute('role','option');
   const text=document.createElement('span'),title=document.createElement('b'),meta=document.createElement('small'),arrow=document.createElement('i');
   title.textContent=item.name;meta.textContent=uiText("NPSN :v0: · :v1: · :v2:", [item.npsn, item.level||'Sekolah', item.status||'']);arrow.textContent='›';
   text.append(title,meta);button.append(text,arrow);button.addEventListener('click',()=>chooseSchool(item));results.append(button);
  });
 };
 const search=async()=>{
  if(chosen||query.readOnly)return;
  const version=++searchVersion,term=query.value.trim();results.replaceChildren();
  if(term.length<3){status.textContent=uiText("Ketik minimal 3 karakter nama sekolah atau NPSN.");return;}
  if(!province.value||!regency.value||!district.value)return;
  searchController?.abort();searchController=new AbortController();status.textContent=uiText("Mencari sekolah…");
  try{
   const items=await request(directory.dataset.schoolsUrl,{query:term,province_code:province.value,regency_code:regency.value,district_code:district.value},searchController.signal);
   if(version!==searchVersion||chosen)return;
   if(items.length){renderResults(items);return;}
   // Fallback: coba pencarian nasional jika regional kosong
   status.textContent=uiText("Tidak ditemukan di wilayah ini. Mencoba pencarian nasional…");
   try{
    const natItems=await request('/api/school-directory/search-nationwide',{query:term},searchController.signal);
    if(version===searchVersion&&!chosen){
     if(natItems.length){renderResults(natItems);}
     else{status.textContent=uiText("Sekolah belum ditemukan. Periksa kata kunci atau coba NPSN.");}
    }
   }catch(e2){if(e2.name!=='AbortError'&&version===searchVersion&&!chosen)status.textContent=uiText("Sekolah belum ditemukan untuk wilayah ini. Periksa wilayah atau coba NPSN.");}
  }catch(error){if(error.name!=='AbortError'&&version===searchVersion&&!chosen)status.textContent=error.message;}
 };
 province.addEventListener('change',()=>changeRegion('province'));
 regency.addEventListener('change',()=>changeRegion('regency'));
 district.addEventListener('change',()=>changeRegion('district'));
 query.addEventListener('input',()=>{
  // Browser autofill / late input events must not erase an already confirmed choice.
  if(chosen&&query.readOnly){syncChosen();return;}
  clearSchool();clearTimeout(timer);timer=setTimeout(search,350);
 });
 directory.querySelector('[data-change-school]')?.addEventListener('click',()=>{clearSchool();query.value='';query.disabled=!district.value;query.focus();status.textContent=uiText("Cari dan pilih sekolah pengganti.");});
 // Capture runs before the generic auth-submit lock. Rebuild POST values from the confirmed state.
 form.addEventListener('submit',event=>{
  const matches=chosen&&chosen.province===province.value&&chosen.regency===regency.value&&chosen.district===district.value;
  if(regionLoading||!matches||!/^[A-Z0-9]{8}$/.test(chosen.npsn)){
   event.preventDefault();status.textContent=regionLoading?uiText("Wilayah masih dimuat. Tunggu sebentar sebelum daftar."):uiText("Pilihan sekolah belum tersimpan. Cari dan pilih sekolah sebelum daftar.");
   query.focus();directory.scrollIntoView({behavior:'smooth',block:'center'});return;
  }
  cancelSearch();syncChosen();
 },true);
 const oldNpsn=String(directory.dataset.oldNpsn||npsn.value||backup?.value||'').trim().toUpperCase();
 if(/^[A-Z0-9]{8}$/.test(oldNpsn)){
  chosen={npsn:oldNpsn,name:directory.dataset.oldSchool||schoolName.value||'',province:directory.dataset.oldProvince||'',regency:directory.dataset.oldRegency||'',district:directory.dataset.oldDistrict||''};
  syncChosen();
 }
 (async()=>{
  const version=++regionVersion;
  try{
   const items=await request(directory.dataset.provincesUrl);if(version!==regionVersion)return;
   fillSelect(province,items,uiText("Pilih provinsi"),directory.dataset.oldProvince||'');
   if(province.value)await loadRegencies(version,directory.dataset.oldRegency||'',directory.dataset.oldDistrict||'');
   if(version!==regionVersion)return;
   if(chosen)syncChosen();else status.textContent=district.value?uiText("Ketik minimal 3 karakter nama sekolah atau NPSN."):uiText("Pilih wilayah sekolah secara berurutan.");
  }catch(error){if(version===regionVersion)status.textContent=error.message;}
  finally{if(version===regionVersion)regionLoading=false;}
 })();
});

document.querySelectorAll('[data-showcase]').forEach(showcase=>{
 localStorage.removeItem('ruteaman-showcase-theme');localStorage.removeItem('ruteaman-showcase-holo');
 showcase.querySelectorAll('.ra-project__shot').forEach(shot=>shot.addEventListener('click',()=>{
  const project=shot.closest('.ra-project');
  project.querySelectorAll('.ra-project__shot').forEach(item=>{const active=item===shot&&!item.classList.contains('is-active');item.classList.toggle('is-active',active);item.setAttribute('aria-pressed',String(active));});
 }));
});

mountHomeGallery();

const liquidGlassInstances=[];
const liquidGlassObservers=[];
const glassConfigs={
 nav:{blurAmount:.22,cornerRadius:20,brightness:.04,refraction:.42,chromAberration:.015,edgeHighlight:.08,zRadius:18,shadowOpacity:.2},
 search:{blurAmount:.25,cornerRadius:50,brightness:.03,refraction:.5,chromAberration:.02,edgeHighlight:.09,zRadius:28,shadowOpacity:.24},
 panel:{blurAmount:.25,cornerRadius:18,brightness:.04,refraction:.35,chromAberration:.012,edgeHighlight:.07,zRadius:16,shadowOpacity:.18},
 status:{blurAmount:.2,cornerRadius:12,brightness:.06,refraction:.28,chromAberration:.01,edgeHighlight:.06,zRadius:10,shadowOpacity:.12},
 notice:{blurAmount:.22,cornerRadius:12,brightness:.02,refraction:.28,chromAberration:.01,edgeHighlight:.06,zRadius:10,shadowOpacity:.12},
 legend:{blurAmount:.2,cornerRadius:14,brightness:.08,refraction:.4,chromAberration:.015,edgeHighlight:.08,zRadius:12,shadowOpacity:.18},
 action:{brightness:-.3,blurAmount:.25,cornerRadius:50,refraction:.58,chromAberration:.02,edgeHighlight:.1,zRadius:28,shadowOpacity:.3,button:true},
 card:{blurAmount:.23,cornerRadius:24,brightness:.035,refraction:.34,chromAberration:.012,edgeHighlight:.085,zRadius:18,shadowOpacity:.2},
 media:{blurAmount:.16,cornerRadius:28,brightness:.02,refraction:.28,chromAberration:.01,edgeHighlight:.07,zRadius:16,shadowOpacity:.18},
 content:{blurAmount:.2,cornerRadius:28,brightness:.04,refraction:.3,chromAberration:.012,edgeHighlight:.08,zRadius:18,shadowOpacity:.18},
 control:{blurAmount:.22,cornerRadius:18,brightness:.045,refraction:.38,chromAberration:.014,edgeHighlight:.09,zRadius:14,shadowOpacity:.18},
 row:{blurAmount:.18,cornerRadius:14,brightness:.035,refraction:.25,chromAberration:.01,edgeHighlight:.06,zRadius:10,shadowOpacity:.1},
};
async function initLiquidGlass(){
 if(matchMedia(uiText("(prefers-reduced-motion: reduce)")).matches)return;
 if(matchMedia("(pointer: coarse)").matches){document.querySelectorAll('[data-liquid-root]').forEach(rootElement=>rootElement.classList.add('liquid-glass-fallback'));return;}
 await document.fonts?.ready;
 for(const rootElement of document.querySelectorAll('[data-liquid-root]')){
  // Live Leaflet panes can contain thousands of independently updated icons.
  // Keep the existing CSS frosted-glass fallback, not a WebGL snapshot of the whole map.
  if(rootElement.closest('#route-app')){rootElement.classList.add('liquid-glass-fallback');continue;}
  const glassElements=[...rootElement.querySelectorAll('.liquid-glass')]
   .filter(element=>element.closest('[data-liquid-root]')===rootElement);
  if(!glassElements.length)continue;
  glassElements.forEach(element=>{element.dataset.config=JSON.stringify(glassConfigs[element.dataset.glassVariant]||glassConfigs.panel);});
  try{
   const instance=await LiquidGlass.init({root:rootElement,glassElements});
   liquidGlassInstances.push(instance);
   glassElements.forEach(element=>{let frame;const observer=new MutationObserver(()=>{cancelAnimationFrame(frame);frame=requestAnimationFrame(()=>instance.markChanged(element));});observer.observe(element,{childList:true,subtree:true,characterData:true,attributes:true,attributeFilter:['class','open','hidden','style']});liquidGlassObservers.push(observer);});
  }catch(error){rootElement.classList.add('liquid-glass-fallback');console.warn(uiText("LiquidGlass fallback:"),error);}
 }
}
initLiquidGlass();
const holoSections=document.querySelectorAll('[data-holo="true"]');
if('IntersectionObserver' in window&&holoSections.length){const holoObserver=new IntersectionObserver(entries=>{entries.forEach(entry=>{entry.target.classList.toggle('ra-paused',!entry.isIntersecting);});},{rootMargin:'240px'});holoSections.forEach(section=>{section.classList.add('ra-paused');holoObserver.observe(section);});}
addEventListener('pagehide',()=>{liquidGlassObservers.forEach(observer=>observer.disconnect());liquidGlassInstances.forEach(instance=>instance.destroy());},{once:true});

// Four reporting stages; no location request until the user chooses it.
const reportRoot=document.querySelector('#hazard-report-app');
if(reportRoot){
 const form=document.querySelector('#hazard-report-form'),lat=document.querySelector('#report-lat'),lng=document.querySelector('#report-lng'),status=document.querySelector('#duplicate-status'),panel=document.querySelector('#duplicate-panel'),submit=document.querySelector('#report-submit');
 const reportMap=L.map('report-location-map').setView([Number(reportRoot.dataset.lat),Number(reportRoot.dataset.lng)],15);
 const reportTiles=L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(reportMap);let reportTileErrors=0;reportTiles.on('tileerror',()=>{reportTileErrors++;if(reportTileErrors>=6){reportMap.removeLayer(reportTiles);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(reportMap);}});
 let marker=null,sequence=0,timer,allowSubmit=false;
 function drawPoint(){if(marker)marker.remove();if(lat.value&&lng.value)marker=L.marker([Number(lat.value),Number(lng.value)],{icon:L.icon({iconUrl:reportMarkerIcon,shadowUrl:reportMarkerShadow,iconSize:[25,41],iconAnchor:[12,41],shadowSize:[41,41]})}).addTo(reportMap);}
 function setPoint(la,ln){lat.value=Number(la).toFixed(7);lng.value=Number(ln).toFixed(7);drawPoint();checkDuplicate();}
 async function checkDuplicate(){
  const type=form.querySelector('[name="type_id"]:checked')?.value,seq=++sequence;
  panel.hidden=true;submit.disabled=false;
  if(!type||!lat.value||!lng.value)return null;
  status.textContent=uiText("Memeriksa laporan di sekitar titik…");
  try{
   const query=new URLSearchParams({type_id:type,latitude:lat.value,longitude:lng.value});
   const response=await fetch(`${reportRoot.dataset.duplicateUrl}?${query}`,{headers:{Accept:'application/json'}});
   const payload=await response.json();if(!response.ok)throw new Error(payload.message||uiText("Titik tidak dapat diperiksa."));
   if(seq!==sequence)return null;
   const duplicate=payload.duplicate;
   status.textContent=duplicate?(duplicate.pending_review?uiText("Laporan serupa sedang menunggu pemeriksaan. Laporan baru tidak dibuat."):uiText("Laporan serupa ditemukan. Gunakan konfirmasi di bawah.")):uiText("Belum ditemukan laporan serupa.");
   if(duplicate){panel.hidden=false;panel.querySelector('h2').textContent=duplicate.pending_review?uiText("Laporan :v0: serupa menunggu pemeriksaan.", [duplicate.label]):uiText("Sudah ada laporan :v0: di dekat sini. Masih ada?", [duplicate.label]);const detail=document.querySelector('#duplicate-detail'),confirmForm=document.querySelector('#duplicate-confirm-form');detail.href=duplicate.url;detail.textContent=duplicate.pending_review?uiText("Buka Laporanku"):uiText("Lihat laporan");confirmForm.hidden=Boolean(duplicate.pending_review);if(duplicate.confirm_url)confirmForm.action=duplicate.confirm_url;submit.disabled=true;}
   return duplicate;
  }catch(error){if(seq===sequence)status.textContent=uiText(":v0: Pemeriksaan ulang tetap dilakukan saat pengiriman.", [error.message]);return null;}
 }
 reportMap.on('click',e=>setPoint(e.latlng.lat,e.latlng.lng));
 form.querySelectorAll('[name="type_id"]').forEach(input=>input.addEventListener('change',checkDuplicate));
 [lat,lng].forEach(input=>input.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>{drawPoint();checkDuplicate();},350);}));
 bindLocationButton(document.querySelector('#report-use-location'),reportRoot.querySelector('[data-location-help]'),p=>{
  setPoint(p.coords.latitude,p.coords.longitude);
  reportMap.setView([p.coords.latitude,p.coords.longitude],16);
 });
 form.addEventListener('submit',async event=>{
  if(allowSubmit)return;event.preventDefault();submit.disabled=true;
  const duplicate=await checkDuplicate();
  if(duplicate){panel.scrollIntoView({behavior:'smooth',block:'center'});return;}
  allowSubmit=true;submit.disabled=true;form.requestSubmit();
 });
 drawPoint();checkDuplicate();
}
document.querySelector('#copy-escalation-message')?.addEventListener('click',async()=>{
 const field=document.querySelector('#escalation-message'),status=document.querySelector('#copy-escalation-status');
 try{await navigator.clipboard.writeText(field.value);status.textContent=uiText("Pesan disalin. Kirim manual ke kanal resmi, lalu tandai Diteruskan.");}
 catch{field.focus();field.select();status.textContent=uiText("Salin teks yang terpilih secara manual.");}
});

// Avoid repeated auth submissions while the first request is processing.
document.querySelectorAll('form[data-auth-submit]').forEach(form=>{
 const button=form.querySelector('button:not([type="button"])');
 if(!button)return;
 const label=button.textContent;
 form.addEventListener('submit',event=>{
  if(event.defaultPrevented)return;
  if(form.dataset.submitting==='true'){event.preventDefault();return;}
  form.dataset.submitting='true';button.disabled=true;button.textContent=uiText("Sedang diproses…");
 });
 window.addEventListener('pageshow',()=>{form.dataset.submitting='false';button.disabled=false;button.textContent=label;});
});

if(document.querySelector('[data-featured]'))import('./features/home/featured-rotator').then(m=>{const root=document.querySelector('[data-featured]');m.mountFeatured(root);if(root.querySelector('[data-area-form]'))m.mountAreaSummary(root);});
if(document.querySelector('[data-condition-list]'))import('./features/conditions/list').then(m=>m.mountList(document.querySelector('[data-condition-list]')));
if(document.querySelector('[data-condition-detail]'))import('./features/conditions/detail').then(m=>m.mountDetail(document.querySelector('[data-condition-detail]')));
