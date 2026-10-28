const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
export function mountPasswordSetup(){
 const token=document.querySelector('meta[name="csrf-token"]')?.content||'';
 document.querySelectorAll('[data-password-setup]').forEach(root=>{
  const input=root.querySelector('[name=initial_password]'),repeat=root.querySelector('[name=initial_password_confirmation]'),method=root.querySelector('[data-password-method]'),fields=root.querySelector('[data-password-fields]'),show=root.querySelector('[data-password-toggle]'),copy=root.querySelector('[data-password-copy]'),notice=root.querySelector('[data-password-once]');
  const render=()=>{const direct=method.value==='password_set';fields.hidden=!direct;[input,repeat].forEach(el=>{el.disabled=!direct;el.required=direct;if(!direct)el.value='';});fields.querySelectorAll('[name=must_change_password]').forEach(el=>el.disabled=!direct);};method.addEventListener('change',render);render();
  show.addEventListener('click',()=>{const visible=input.type==='password';input.type=visible?'text':'password';show.setAttribute('aria-pressed',String(visible));show.textContent=visible?uiText("Sembunyikan kata sandi"):uiText("Lihat kata sandi");});
  input.addEventListener('input',()=>{const n=[input.value.length>=12,/[A-Z]/.test(input.value),/[a-z]/.test(input.value),/\d/.test(input.value),/[^A-Za-z0-9]/.test(input.value)].filter(Boolean).length;root.querySelector('[data-password-strength]').textContent=uiText("Persyaratan sandi terpenuhi: :v0:/5. Server juga memeriksa nama/alias.", [n]);});
  let retryAfterAuth=false;
  document.addEventListener('admin:reauth-success',()=>{if(retryAfterAuth){retryAfterAuth=false;root.querySelector('[data-password-generate]')?.click();}});
  root.querySelector('[data-password-generate]')?.addEventListener('click',async e=>{
   const button=e.currentTarget,error=root.querySelector('#initial-password-error');button.disabled=true;
   try{const response=await fetch(button.dataset.url,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':token}});const d=await response.json();if(response.status===428){retryAfterAuth=true;document.querySelector('[data-open-reauth]')?.click();return;}if(!response.ok)throw Error(response.status===428?uiText("Konfirmasi kata sandi melalui tombol Buat akun/Simpan terlebih dahulu, lalu coba lagi."):d.message||uiText("Pembuatan sandi gagal."));input.value=d.password;repeat.value=d.password;input.type='text';show.textContent=uiText("Sembunyikan kata sandi");show.setAttribute('aria-pressed','true');input.dispatchEvent(new Event('input'));copy.hidden=false;notice.hidden=false;error.textContent='';}catch(e){error.textContent=e.message;}finally{button.disabled=false;}
  });
  copy?.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(input.value);notice.textContent=uiText("Disalin. Bagikan secara privat; tidak akan ditampilkan lagi.");}catch{input.select();notice.textContent=uiText("Pilih teks dan salin dengan Ctrl/Cmd+C.");}});
  window.addEventListener('pagehide',()=>{input.value='';repeat.value='';});
 });
}
