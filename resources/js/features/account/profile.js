const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};

let toastTimer;
export function feedback(text){const el=document.querySelector('#account-feedback');if(!el)return;el.textContent=text;el.hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.hidden=true,5000);}
export function syncAccount(account){
 if(!account)return;
 document.querySelectorAll('[data-own-name]').forEach(e=>e.textContent=account.display_name);
 document.querySelectorAll('[data-own-email]').forEach(e=>e.textContent=account.email);
 document.querySelectorAll('[data-pending-email]').forEach(e=>e.textContent=account.pending_email?uiText("Menunggu verifikasi: ")+account.pending_email:'');
 document.querySelectorAll('[data-email-status]').forEach(e=>e.textContent=account.email_verified?uiText("◈ Email terverifikasi"):uiText("◇ Email belum terverifikasi"));
 document.querySelectorAll('[data-own-avatar]').forEach(e=>{e.replaceChildren();if(account.avatar_url){const img=new Image();img.alt='';img.src=account.avatar_url+(account.avatar_url.includes('?')?'&':'?')+'v='+Date.now();e.append(img);}else{const span=document.createElement('span');span.textContent=Array.from(account.display_name)[0]?.toLocaleUpperCase(globalThis.ruteamanLocale || 'id')||'?';e.append(span);}if(e.getAttribute('role')==='img')e.setAttribute('aria-label',uiText("Foto profil ")+account.display_name);});
 document.querySelectorAll('[data-preset]').forEach(e=>e.setAttribute('aria-pressed',String(e.dataset.preset===account.avatar_preset)));
 document.querySelectorAll('[data-unread-count]').forEach(e=>{e.textContent=account.unread_count;e.hidden=!account.unread_count;});document.querySelectorAll('[data-unread-dot]').forEach(e=>e.hidden=!account.unread_count);document.querySelectorAll('[data-reports-tab]').forEach(e=>e.setAttribute('aria-label',account.unread_count?uiText("Laporanku, ada pembaruan baru"):'Laporanku'));
 document.querySelectorAll('[data-appearance-value]').forEach(e=>e.value=account.appearance);document.documentElement.dataset.appearance=account.appearance;document.documentElement.dataset.theme='light';try{localStorage.setItem('ruteaman.appearance',account.appearance);}catch{}
}
export async function submitAccount(url,data){
 const response=await fetch(url,{method:'POST',body:data,headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin'});
 let payload;try{payload=await response.json();}catch{throw {message:uiText("Permintaan belum selesai. Coba lagi.")};}
 if(!response.ok){if([401,419].includes(response.status)){feedback(uiText("Sesi berakhir. Masuk kembali."));location.href='/login?session_expired=1';}throw {message:payload.error?.message||payload.message||uiText("Permintaan gagal. Coba lagi."),fields:payload.error?.field_errors||payload.errors||{}};}
 return payload;
}
export function initForms(){document.querySelectorAll('[data-account-form]').forEach(form=>form.addEventListener('submit',async event=>{
 event.preventDefault();if(form.dataset.busy==='true')return;if(form.dataset.confirmAction&&!window.confirm(form.dataset.confirmAction))return;
 const status=form.querySelector('[data-form-status]');form.querySelectorAll('[data-field-error]').forEach(e=>{e.hidden=true;e.textContent='';});form.querySelectorAll('[aria-invalid]').forEach(e=>e.removeAttribute('aria-invalid'));
 const data=new FormData(form);form.dataset.busy='true';const buttons=[...form.querySelectorAll('button[type=submit],button:not([type])')];buttons.forEach(e=>e.disabled=true);if(status)status.textContent=uiText("Menyimpan…");
 try{const payload=await submitAccount(form.action,data);if(payload.account)syncAccount(payload.account);if(payload.member){const card=form.closest('.school-user-card');card.querySelector('[data-member-role]').textContent=payload.member.role==='verifier'?uiText("Verifikator"):uiText("Siswa");card.querySelector('[data-member-active]').textContent=payload.member.active?uiText("Aktif"):'Ditangguhkan';}if(status)status.textContent=payload.message||uiText("Perubahan tersimpan");feedback(payload.message||uiText("Perubahan tersimpan"));form.querySelectorAll('input[type=password]').forEach(e=>e.value='');if(form.hasAttribute('data-mark-seen')){document.querySelectorAll('.is-unread').forEach(e=>e.classList.remove('is-unread'));document.querySelectorAll('.unread-label').forEach(e=>e.textContent='');}if(form.querySelector('select[name=locale]') && payload.account?.locale){window.location.reload();return;}if(payload.redirect)location.href=payload.redirect;}
 catch(error){let first;Object.entries(error.fields||{}).forEach(([name,messages])=>{const field=[...form.elements].find(e=>e.name===name);const el=[...form.querySelectorAll('[data-field-error]')].find(e=>e.dataset.fieldError===name);if(field){field.setAttribute('aria-invalid','true');first??=field;}if(el){el.textContent=Array.isArray(messages)?messages[0]:messages;el.hidden=false;}});if(status)status.textContent=error.message||uiText("Jaringan bermasalah. Coba lagi.");feedback(error.message||uiText("Jaringan bermasalah. Coba lagi."));first?.focus();}
 finally{form.dataset.busy='false';buttons.forEach(e=>e.disabled=false);}
}));}
