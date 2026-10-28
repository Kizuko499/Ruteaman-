/** Display only server-provided route evidence. Provider metadata stays in the API. */
export function conditionComparisonRows(routes){
 return routes.map((route,index)=>{
  const hazards=Array.isArray(route.hazards)?route.hazards:null;
  const types=new Map();for(const h of hazards||[]){const label=String(h.label||'Kondisi');types.set(label,(types.get(label)||0)+1);}
  const number=value=>{if(typeof value!=='number'&&(typeof value!=='string'||!/^\d+(\.\d+)?$/.test(value)))return null;const n=Number(value);return Number.isFinite(n)&&n>=0?n:null;};
  const load=route.load||{};
  return {name:String(route.id||index+1),distance:number(route.distance_m),duration:number(route.duration_s),count:hazards?.length??null,types:[...types],load:load.label_code==='no_report'?null:number(load.value),noReport:load.label_code==='no_report',demo:route.is_demo===true};
 });
}
export function renderConditionComparison(routes,translate=s=>s,doc=document){
 if(!routes.length)return null;
 const t=translate;const make=(tag,text,className)=>{const el=doc.createElement(tag);if(text!==undefined)el.textContent=text;if(className)el.className=className;return el;};
 const section=make('section',undefined,'route-condition-comparison');section.setAttribute('aria-label',t('Perbandingan kondisi rute'));
 section.append(make('h2',t('Perbandingan kondisi rute')));
 section.append(make('p',t(routes.length===1?'Hanya satu rute tersedia. Alternatif pembanding belum tersedia.':'Bandingkan data tiap rute. Urutan rute bukan rekomendasi keselamatan.'),'route-comparison-note'));
 const wrap=make('div',undefined,'route-comparison-scroll');wrap.tabIndex=0;wrap.setAttribute('role','region');wrap.setAttribute('aria-label',t('Ringkasan per rute'));
 const table=make('table');table.append(make('caption',t('Ringkasan per rute')));const head=make('thead'),hr=make('tr');
 const labels=['Rute','Jarak','Waktu','Kondisi aktif','Beban Kondisi','Jenis kondisi'];labels.forEach(label=>{const th=make('th',t(label));th.scope='col';hr.append(th);});head.append(hr);table.append(head);
 const body=make('tbody');const locale=globalThis.ruteamanLocale||'id-ID';const fmt=n=>n.toLocaleString(locale,{maximumFractionDigits:1});
 for(const row of conditionComparisonRows(routes)){
  const tr=make('tr');const name=make('th',/^Rute\s/i.test(row.name)?row.name:`${t('Rute')} ${row.name}`);name.scope='row';if(row.demo)name.append(make('small',t('SIMULASI DEMO — belum audit lapangan')));tr.append(name);
  const values=[row.distance===null?'—':`${fmt(row.distance/1000)} km`,row.duration===null?t('Waktu belum tersedia'):`${fmt(Math.max(1,Math.ceil(row.duration/60)))} ${t('menit')}`,row.count===null?'—':fmt(row.count),row.noReport?t('Belum ada laporan aktif'):row.load===null?'—':`${fmt(row.load)}/100`,row.types.length?row.types.map(([label,n])=>`${t(label)} × ${fmt(n)}`).join(' · '):row.count===0?t('Belum ada laporan aktif'):'—'];
  values.forEach((value,i)=>{const td=make('td',value);td.dataset.label=t(labels[i+1]);tr.append(td);});body.append(tr);
 }
 table.append(body);wrap.append(table);section.append(wrap);
 section.append(make('p',t('Hitungan hanya mencakup kondisi publik yang masih aktif dan berada dalam radius jalur. Ikon peta dapat berupa riwayat atau berada di luar radius. Belum ada laporan bukan jaminan kondisi aman.'),'route-comparison-note'));
 return section;
}
