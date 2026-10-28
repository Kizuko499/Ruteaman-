const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import '../../css/guest-school-search.css';
export function mountGuestSchoolSearch(root, schools, onSelect, onClear) {
 const panel=root.querySelector('#guest-school-search-panel');if(!panel)return;
 const query=root.querySelector('#guest-school-query'), results=root.querySelector('#guest-school-results'), status=root.querySelector('#guest-school-search-status');
 const select=root.querySelector('#guest-report-school'), ticket=root.querySelector('#guest-school-ticket'), chosen=root.querySelector('#guest-school-selected');
 const lockedBox=root.querySelector('#guest-school-locked'), unlockBtn=root.querySelector('#guest-school-unlock');
 const setLocked=locked=>{query.disabled=locked;if(searchButton)searchButton.disabled=locked;if(kind)kind.disabled=locked;if(lockedBox)lockedBox.hidden=!locked;};
 const kind=root.querySelector('#guest-institution-type'),institutionKind=()=>kind?.value||'school';
 const searchButton=root.querySelector('#guest-school-search-button');
 panel.dataset.searchVersion='4';panel.hidden=false;root.querySelector('#guest-school-local-fallback').hidden=true;
 let timer,controller,version=0;
 const initial=JSON.parse(root.querySelector('#guest-report-directory-selected')?.textContent||'null')||schools.find(s=>String(s.id)===select.value);
 const showChosen=s=>{query.value=s.name;chosen.textContent=uiText("Institusi dipilih: :v0::v1:. Untuk mengganti, klik tombol ✕.", [s.name, s.npsn?' · '+s.npsn:'']);results.replaceChildren();status.textContent='';setLocked(true);};
 if(initial){showChosen(initial);onSelect(initial);}
 const clear=()=>{select.value='';ticket.value='';setLocked(false);onClear();};
 unlockBtn?.addEventListener('click',()=>{clearTimeout(timer);controller?.abort();++version;clear();query.value='';results.replaceChildren();status.textContent=uiText("Pilihan dibatalkan. Cari sekolah / kampus lagi.");query.focus();});
 const request=async(params,signal)=>{
  const url=new URL(params.institution_type==='campus'?panel.dataset.campusUrl:panel.dataset.searchUrl,location.href);Object.entries(params).forEach(([k,v])=>url.searchParams.set(k,v));
  const response=await fetch(url,{signal,headers:{Accept:'application/json'},credentials:'same-origin'});
  const data=await response.json();if(!response.ok)throw Error(data.message||uiText("Pencarian gagal. Coba lagi."));return data;
 };
 const pick=async(s,v)=>{
  if(v!==version)return;controller?.abort();controller=new AbortController();const current=++version;
  clearTimeout(timer);status.textContent=uiText("Memeriksa data dan lokasi institusi…");results.querySelectorAll('button').forEach(b=>b.disabled=true);
  try{
   let resolved=s,newTicket='';
   const local=schools.filter(x=>(x.institution_type||'school')===institutionKind()).find(x=>(s.id&&String(x.id)===String(s.id))||(s.npsn&&x.npsn===s.npsn));
   if(local)resolved=local;
   else{const data=await request(s.id?{institution_id:s.id,institution_type:institutionKind()}:{school_npsn:s.npsn},controller.signal);resolved=data.data;newTicket=data.ticket||'';}
   if(current!==version)return;
   if((resolved.institution_type||'school')!==institutionKind())throw Error(uiText('Hasil tidak sesuai jenis institusi. Muat ulang dan cari lagi.'));
   if(newTicket){select.value='';ticket.value=newTicket;}
   else{
    if(!schools.some(x=>String(x.id)===String(resolved.id))){schools.push(resolved);const option=document.createElement('option');option.value=String(resolved.id);option.textContent=resolved.name;select.append(option);}
    select.value=String(resolved.id);ticket.value='';
   }
   showChosen(resolved);onSelect(resolved);
  }catch(e){if(e.name!=='AbortError'&&current===version){status.textContent=e.message;results.querySelectorAll('button').forEach(b=>b.disabled=false);}}
 };
 const render=(items,v)=>{
  results.replaceChildren();items.forEach(s=>{
   const button=document.createElement('button');button.type='button';button.className='guest-school-result';
   const name=document.createElement('strong');name.textContent=s.name;
   const meta=document.createElement('span');meta.textContent=[s.npsn?uiText("NPSN :v0:", [s.npsn]):(s.campus_code||''),s.institution_type==='campus'?s.country_code:'',s.district,s.regency,s.province].filter(Boolean).join(' · ');
   const badge=document.createElement('small');badge.textContent=s.source==='registered'?uiText("Terdaftar di RuteAman"):uiText("Direktori nasional");
   button.append(name,meta,badge);button.addEventListener('click',()=>pick(s,v));results.append(button);
  });
 };
 const catalogSuggestions=async(term,v,signal,registered)=>{
  if(!panel.dataset.catalogUrl)return;
  try{
   const url=new URL(panel.dataset.catalogUrl,location.href);url.searchParams.set('query',term);url.searchParams.set('country','');
   const response=await fetch(url,{signal,headers:{Accept:'application/json'},credentials:'same-origin'});
   if(!response.ok)throw Error();const data=await response.json();if(v!==version||institutionKind()!=='campus')return;
   const references=(data.data||[]).filter(s=>!registered.some(x=>String(x.id)===String(s.school_id))).slice(0,5);
   if(!references.length)return;
   const heading=document.createElement('p');heading.className='guest-catalog-heading';heading.textContent=uiText('Referensi katalog — bukan pilihan wilayah laporan');results.append(heading);
   references.forEach(s=>{
    const card=document.createElement('div');card.className='guest-catalog-reference';
    const title=document.createElement('strong');title.textContent=s.name;
    const detail=document.createElement('span');detail.textContent=[s.country_code,s.city,s.official_code,s.source.toUpperCase()].filter(Boolean).join(' · ');
    const note=document.createElement('small');note.textContent=uiText('Belum tersedia untuk pelaporan di pilihan ini. Pengelola perlu memeriksa aktivasi, pin, wilayah, dan fitur.');
    const link=document.createElement('a');const page=new URL(panel.dataset.catalogPage,location.href);page.searchParams.set('query',s.official_code||s.name);page.searchParams.set('country',s.country_code);link.href=page.href;link.className='text-link';link.textContent=uiText('Lihat detail katalog');card.append(title,detail,note,link);results.append(card);
   });
   if(!registered.length)status.textContent=uiText('Nama ditemukan di katalog, tetapi belum dapat dipilih untuk laporan. Pilih kampus yang pelaporannya aktif atau hubungi pengelola.');
  }catch(e){if(e.name!=='AbortError'&&v===version){const note=document.createElement('p');note.textContent=uiText('Referensi katalog belum dapat dimuat. Coba lagi atau buka direktori kampus.');results.append(note);}}
 };
 const search=async()=>{
  clearTimeout(timer);controller?.abort();controller=new AbortController();const current=++version;const term=query.value.trim(),searchKind=institutionKind();
  results.replaceChildren();if(term.length<3){status.textContent=uiText("Ketik minimal 3 karakter nama institusi; NPSN hanya untuk sekolah.");return;}
  const local=schools.filter(s=>(s.institution_type||'school')===institutionKind()).filter(s=>s.name.toLocaleLowerCase('id').includes(term.toLocaleLowerCase('id'))||String(s.campus_code||s.npsn||'').toUpperCase().includes(term.toUpperCase())).map(s=>({...s,source:'registered'}));
  render(local,current);status.textContent=institutionKind()==='campus'?uiText('Mencari kampus terdaftar…'):uiText("Mencari sekolah di direktori nasional…");
  try{const data=await request({school_query:term,institution_type:institutionKind()},controller.signal);if(current!==version)return;
   const matches=(data.data||[]).filter(s=>(s.institution_type||'school')===searchKind);
   render(matches,current);status.textContent=data.message||((matches.length)?uiText(":v0: institusi ditemukan. Pilih hasil dan wilayah yang sesuai.", [matches.length]):uiText("Institusi tidak ditemukan. Coba nama lengkap. Kampus harus sudah didaftarkan pengelola."));
   if(searchKind==='campus')await catalogSuggestions(term,current,controller.signal,matches);
  }catch(e){if(e.name!=='AbortError'&&current===version)status.textContent=uiText(":v0: :v1:", [e.message, local.length?'Hasil institusi terdaftar tetap dapat dipilih.':'']);}
 };
 kind?.addEventListener('change',()=>{controller?.abort();++version;clearTimeout(timer);clear();query.value='';results.replaceChildren();status.textContent=institutionKind()==='campus'?uiText('Cari kampus terdaftar. Kampus yang belum ada perlu ditambahkan pengelola.'):uiText('Cari sekolah atau NPSN.');});
 query.addEventListener('input',()=>{controller?.abort();++version;clearTimeout(timer);clear();results.replaceChildren();status.textContent='';timer=setTimeout(search,500);});
 query.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();search();}});searchButton.addEventListener('click',search);
 root.querySelector('form').addEventListener('submit',e=>{
  if(!select.value&&!ticket.value){e.preventDefault();query.focus();status.textContent=uiText("Cari lalu pilih sekolah / kampus dari hasil pencarian terlebih dahulu.");}
 });
}
