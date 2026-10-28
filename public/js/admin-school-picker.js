(() => {
 const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
 document.querySelectorAll('[data-school-picker]').forEach(root => {
  const query=root.querySelector('[data-school-query]'),search=root.querySelector('[data-school-search]'),select=root.querySelector('[data-school-value]'),results=root.querySelector('[data-school-results]'),message=root.querySelector('[data-school-message]');
  let pending=null,searchVersion=0;
  const reportsContext=root.dataset.pickerContext==='reports';
  const label=item=>`${item.name}${item.npsn?' · '+item.npsn:''}`;
  const choose=item=>{
   let option=[...select.options].find(o=>o.value===String(item.id));
   if(!option){option=new Option(label(item),String(item.id));select.add(option);}
   select.value=String(item.id);select.dispatchEvent(new Event('change',{bubbles:true}));select.focus();
   if(reportsContext)root.closest('form')?.requestSubmit();
  };
  const register=async(item,button)=>{
   button.disabled=true;message.textContent=uiText("Mendaftarkan institusi…");
   try{
    const response=await fetch(root.dataset.registerUrl,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({npsn:item.npsn})});
    const data=await response.json();
    if(response.status===428){pending={item,button};message.textContent=uiText("Konfirmasi kata sandi Admin Utama untuk melanjutkan.");document.querySelector('[data-open-reauth]')?.click();return;}
    if(!response.ok)throw new Error(data.message||data.error?.message||uiText("Institusi belum dapat didaftarkan."));
    choose(data.data);button.textContent='Dipilih';message.textContent=data.message||uiText("Institusi dipilih.");
   }catch(error){message.textContent=error.message||uiText("Koneksi terganggu. Coba lagi.");}finally{button.disabled=false;}
  };
  document.addEventListener('admin:reauth-success',()=>{if(pending){const next=pending;pending=null;register(next.item,next.button);}});
  document.querySelector('#admin-reauth-dialog')?.addEventListener('close',()=>{pending=null;});
  document.querySelectorAll('[data-reauth-close]').forEach(button=>button.addEventListener('click',()=>{pending=null;}));
  search.addEventListener('click',async()=>{
   const term=query.value.trim();if(term.length<3){message.textContent=uiText("Ketik minimal 3 karakter nama institusi atau NPSN.");query.focus();return;}
   const version=++searchVersion;search.disabled=true;results.replaceChildren();message.textContent=uiText("Mencari institusi…");
   try{
    const url=new URL(root.dataset.searchUrl,location.origin);url.searchParams.set('q',term);
    const response=await fetch(url,{headers:{Accept:'application/json'}});const data=await response.json();
    if(!response.ok)throw new Error(data.message||data.error?.message||uiText("Pencarian gagal."));if(version!==searchVersion)return;
    for(const item of data.data||[]){
     const card=document.createElement('div');card.style.cssText=uiText("border:1px solid #cbdad5;border-radius:8px;padding:12px;overflow-wrap:anywhere");
     const title=document.createElement('strong');title.textContent=label(item);title.style.display='block';const region=document.createElement('small');region.style.cssText='display:block;margin-top:4px';region.textContent=(item.source==='registered'?uiText("Terdaftar di RuteAman"):uiText("Direktori nasional"))+(item.region?' · '+item.region:'');
     const button=document.createElement('button');button.type='button';button.className='admin-secondary';button.style.marginTop='8px';button.textContent=reportsContext?(item.source==='registered'?uiText("Tampilkan laporan"):uiText("Daftarkan & tampilkan")):(item.source==='registered'?uiText("Pilih institusi"):uiText("Daftarkan & pilih"));
     button.addEventListener('click',()=>{if(item.source==='registered'){choose(item);message.textContent=uiText("Institusi terpilih: ")+label(item);}else if(confirm(reportsContext?uiText("Daftarkan ")+label(item)+uiText(" untuk melihat kondisi wilayahnya? Pemantauan dijeda dan pelaporan nonaktif sampai ditinjau Admin Utama. Tidak ada akun atau laporan yang dibuat otomatis."):uiText("Daftarkan ")+label(item)+uiText("? Pemantauan dijeda dan pelaporan nonaktif sampai ditinjau Admin Utama. Institusi tetap terdaftar meskipun pembuatan akun dibatalkan.")))register(item,button);});
     card.append(title,region,button);results.append(card);
    }
    const online=Number.isInteger(data.online_count)?uiText(" Hasil direktori online: :v0:.", [data.online_count]):'';
    message.textContent=(data.data?.length?uiText(":v0: institusi ditemukan.", [data.data.length]):uiText("Institusi tidak ditemukan. Coba nama resmi institusi atau NPSN."))+online+(data.message?' '+data.message:'');
   }catch(error){message.textContent=error.message||uiText("Koneksi terganggu. Coba lagi.");}finally{if(version===searchVersion)search.disabled=false;}
  });
  query.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();search.click();}});
 });
})();
