const kind=document.querySelector('#register-institution-type');
if(kind){const school=document.querySelector('[data-school-directory]'),campus=document.querySelector('#register-campus-fields');const apply=()=>{const c=kind.value==='campus';school.hidden=c;school.disabled=c;campus.hidden=!c;campus.disabled=!c;};kind.addEventListener('change',apply);apply();}
// Pencarian & pemilihan kampus ditangani SATU implementasi di register.blade.php.
// Blok duplikat di berkas ini sudah dihapus: dulu keduanya berebut hasil pencarian
// dan versi bundle menimpa pilihan versi inline (register kampus tersendat).
