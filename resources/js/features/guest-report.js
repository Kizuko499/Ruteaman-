const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {mountGuestSchoolSearch} from './guest-school-search';
export function mountGuestReport(L, bindLocationButton) {
 const root=document.querySelector('#guest-report-app');if(!root)return;
 const schools=JSON.parse(document.querySelector('#guest-report-school-data')?.textContent||'[]');
 const select=root.querySelector('#guest-report-school'),lat=root.querySelector('#guest-report-lat'),lng=root.querySelector('#guest-report-lng');
 const status=root.querySelector('#guest-location-status'),button=root.querySelector('#guest-use-location');
 const map=L.map('guest-report-map').setView([-2,118],5);
 const guestTiles=L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(map);let guestTileErrors=0;guestTiles.on('tileerror',()=>{guestTileErrors++;if(guestTileErrors>=6){map.removeLayer(guestTiles);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:uiText("&copy; OpenStreetMap contributors")}).addTo(map);}});
 let marker=null,area=null,directorySchool=null;
 const selected=()=>directorySchool||schools.find(s=>String(s.id)===select.value);
 const draw=()=>{
  if(marker){marker.remove();marker=null;}
  if(lat.value!==''&&lng.value!==''&&Number.isFinite(Number(lat.value))&&Number.isFinite(Number(lng.value)))marker=L.circleMarker([Number(lat.value),Number(lng.value)],{radius:9,color:'#fff',weight:3,fillColor:'#1F6E70',fillOpacity:1}).addTo(map);
 };
 const setPoint=(la,ln)=>{if(!selected()){status.textContent=uiText("Pilih sekolah / kampus terlebih dahulu.");return;}lat.value=Number(la).toFixed(7);lng.value=Number(ln).toFixed(7);draw();status.textContent=uiText("Titik dipilih. Server akan memeriksa apakah titik berada dalam wilayah institusi.");};
 const change=(initial=false)=>{
  const s=selected();button.disabled=!s;if(area){area.remove();area=null;}
  if(!initial){lat.value='';lng.value='';draw();}
  if(!s){status.textContent=uiText("Pilih sekolah / kampus terlebih dahulu.");return;}
  map.setView([s.latitude,s.longitude],15);
  status.textContent=uiText("Institusi dipilih. Ketuk lokasi kondisi di peta.");draw();
 };
 select.addEventListener('change',()=>change());map.on('click',e=>setPoint(e.latlng.lat,e.latlng.lng));
 [lat,lng].forEach(el=>el.addEventListener('input',draw));
 bindLocationButton(button,root.querySelector('[data-location-help]'),p=>{setPoint(p.coords.latitude,p.coords.longitude);map.setView([p.coords.latitude,p.coords.longitude],16);});
 let initializingSearch=true;
 mountGuestSchoolSearch(root,schools,s=>{directorySchool=String(s.id).startsWith('directory:')?s:null;change(initializingSearch);},()=>{directorySchool=null;change();});
 initializingSearch=false;
 root.querySelector('form').addEventListener('submit',e=>{if(e.defaultPrevented)return;const submit=root.querySelector('#guest-report-submit');submit.disabled=true;submit.textContent=uiText("Mengirim untuk diperiksa…");});
 change(true);
}
