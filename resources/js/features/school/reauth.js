const t=s=>globalThis.ruteamanTranslate?.(s)??s;
// Dashboard-only adapter preserving the existing admin reauthentication flow.
export function mountSchoolReauth(){
 const dialog=document.querySelector('#admin-reauth-dialog'),form=document.querySelector('#admin-reauth-form');if(!dialog||!form)return;
 let confirmedAt=Number(document.querySelector('meta[name="admin-reauth-at"]')?.content||0),pendingForm=null,pendingButton=null;
 document.querySelector('[data-open-reauth]')?.addEventListener('click',()=>{pendingForm=null;pendingButton=null;dialog.showModal();form.elements.password.focus();});
 document.querySelectorAll('[data-reauth-close]').forEach(b=>b.addEventListener('click',()=>dialog.close()));
 document.querySelectorAll('form[data-sensitive]').forEach(source=>source.addEventListener('submit',event=>{if(source.dataset.confirm&&source.dataset.confirmed!=='1'){if(!confirm(source.dataset.confirm)){event.preventDefault();return;}source.dataset.confirmed='1';}if(Date.now()/1000-confirmedAt>590){event.preventDefault();pendingForm=source;pendingButton=event.submitter;dialog.showModal();form.elements.password.focus();return;}source.dataset.saved='1';}));
 form.addEventListener('submit',async event=>{event.preventDefault();const error=form.querySelector('[data-reauth-error]'),button=form.querySelector('button.admin-primary');error.textContent='';button.disabled=true;try{const response=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:new FormData(form)});const data=await response.json();if(!response.ok){error.textContent=data.error?.message||data.message||t('Konfirmasi gagal.');return;}confirmedAt=Date.now()/1000;form.reset();dialog.close();document.dispatchEvent(new CustomEvent('admin:reauth-success'));pendingForm?.requestSubmit(pendingButton||undefined);}catch{error.textContent=t('Koneksi terganggu. Coba lagi.');}finally{button.disabled=false;}});
}
