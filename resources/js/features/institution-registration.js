const kind=document.querySelector('#register-institution-type');
if(kind){const school=document.querySelector('[data-school-directory]'),campus=document.querySelector('#register-campus-fields');const apply=()=>{const c=kind.value==='campus';school.hidden=c;school.disabled=c;campus.hidden=!c;campus.disabled=!c;};kind.addEventListener('change',apply);apply();}

const campusSelect=document.querySelector('#register-campus-id'),campusQuery=document.querySelector('#register-campus-query'),campusStatus=document.querySelector('#register-campus-status');
if(campusSelect&&campusQuery){
 const t=k=>globalThis.ruteamanTranslate?.(k)??k;
 let timer,controller;
 let resultBox=document.querySelector('#register-campus-results');
 if(!resultBox){resultBox=document.createElement('div');resultBox.id='register-campus-results';resultBox.className='campus-search-results';resultBox.setAttribute('role','listbox');campusQuery.parentElement.after(resultBox);}
 const render=items=>{
  resultBox.replaceChildren();
  items.forEach(item=>{
   const btn=document.createElement('button');btn.type='button';btn.className='campus-result-item';btn.setAttribute('role','option');
   const num=document.createElement('span');num.className='campus-result-number';num.textContent=item.number;
   const info=document.createElement('span');info.className='campus-result-info';
   const name=document.createElement('strong');name.textContent=item.name;
   const meta=document.createElement('small');meta.textContent=(item.source==='registered'?'Terdaftar':'Direktori ROR')+' \u00b7 '+(item.campus_code||item.city||item.country_code||'');
   const idLabel=document.createElement('small');idLabel.className='campus-result-id';idLabel.textContent='ID: '+(item.source==='registered'?item.id:String(item.id).replace('https://ror.org/','ROR:'));
   info.append(name,meta,idLabel);btn.append(num,info);
   btn.addEventListener('click',()=>selectCampus(item));
   resultBox.append(btn);
  });
 };
 const selectCampus=async item=>{
  if(item.source==='registered'){
   let opt=[...campusSelect.options].find(o=>o.value==String(item.id));
   if(!opt){opt=new Option(item.display,item.id);campusSelect.add(opt);}
   campusSelect.value=String(item.id);
   campusStatus.textContent=t('Kampus dipilih: ')+item.name;
   resultBox.replaceChildren();
  }else{
   campusStatus.textContent=t('Menambahkan kampus ke daftar...');
   try{
    const csrf=document.querySelector('input[name=_token]')?.value||'';
    const resp=await fetch('/api/register-campus-provision',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({ror_id:item.id,name:item.name,country_code:item.country_code||'ID',city:item.city,latitude:item.latitude,longitude:item.longitude})});
    const data=await resp.json();
    if(!resp.ok)throw new Error(data.message||t('Gagal menambahkan kampus.'));
    const opt=new Option(item.name+' \u00b7 '+(item.country_code||'ID'),data.id);
    campusSelect.add(opt);campusSelect.value=String(data.id);
    campusStatus.textContent=(data.created?t('Kampus ditambahkan dan dipilih: '):t('Kampus dipilih: '))+item.name;
    resultBox.replaceChildren();
   }catch(e){campusStatus.textContent=e.message;}
  }
 };
 const search=async()=>{
  const term=campusQuery.value.trim();
  resultBox.replaceChildren();
  if(term.length<3){campusStatus.textContent=t('Ketik minimal 3 karakter nama kampus atau ID.');return;}
  controller?.abort();controller=new AbortController();
  campusStatus.textContent=t('Mencari kampus...');
  try{
   const url=new URL('/api/register-campus-search',location.href);url.searchParams.set('query',term);
   const resp=await fetch(url,{signal:controller.signal,headers:{Accept:'application/json'}});
   const data=await resp.json();
   if(!resp.ok)throw new Error(data.message||t('Pencarian gagal.'));
   const results=data.data||[];
   if(results.length){render(results);campusStatus.textContent=t('Ditemukan ')+results.length+t(' kampus. Pilih salah satu.');}
   else{campusStatus.textContent=t('Kampus tidak ditemukan. Coba kata kunci lain.');}
  }catch(e){if(e.name!=='AbortError')campusStatus.textContent=e.message;}
 };
 campusQuery.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(search,500);});
 campusQuery.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();clearTimeout(timer);search();}});
}
