@extends('layouts.app')
@section('title',__('Daftar'))
@section('content')
@php($registerPhoto=app(\App\Services\SitePresentationService::class)->registerPhoto())
<div class="auth-page">
<style>
/* Polesan modern minimalis halaman daftar — hanya tampilan */
.auth-page .auth-card{background:transparent;border:0;border-radius:0;box-shadow:none;max-width:520px;width:100%;padding:48px 24px;gap:13px;align-self:start;margin-inline:auto}
.auth-page .auth-card h1{font-family:Poppins,Inter,sans-serif;font-size:2rem;font-weight:700;letter-spacing:-.01em;color:#14343A;margin:16px 0 0}
.auth-page .auth-card label{font-size:.84rem;font-weight:600;color:#34565A}
.auth-page .auth-card input:not([type=checkbox]),.auth-page .auth-card select{width:100%;box-sizing:border-box;height:52px;border:1px solid #DFE9E4;border-radius:14px;padding:0 14px;font-size:.95rem;font-family:Inter,sans-serif;color:#14343A;background:#fff;margin-top:7px;transition:border-color .15s,box-shadow .15s}
.auth-page .auth-card select{padding-right:36px}
.auth-page .auth-card input:not([type=checkbox]):focus,.auth-page .auth-card select:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.auth-page .auth-card .campus-search-wrap input{padding-right:40px}
.auth-page .auth-card .check{display:flex;align-items:flex-start;gap:9px;font-size:.9rem;font-weight:500;color:#49696B;line-height:1.55}
.auth-page .auth-card .check input{width:18px;height:18px;margin-top:2px;flex:none;accent-color:#1F6E70}
.auth-page .auth-card .error{background:#FDECEA;color:#8C3A1B;border-radius:12px;padding:12px 14px;font-size:.9rem}
.auth-page .auth-card .btn{width:100%;min-height:52px;border:0;border-radius:14px;background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;font-family:Inter,sans-serif;font-size:1rem;font-weight:700;cursor:pointer;box-shadow:0 10px 22px rgba(31,110,112,.25)}
.auth-page .auth-card>p:last-child{border-top:1px solid #EEF4F0;padding-top:14px;text-align:center;font-size:.9rem;color:#5F7476}
.auth-page .auth-card>p:last-child a{font-weight:700;color:var(--teal-dark,#1F6E70);text-decoration:none}
.auth-page .auth-card fieldset{border:1px solid #E3EFE9;border-radius:18px;background:#F8FBF9;padding:18px 16px 16px;margin:6px 0 2px;min-width:0}
.auth-page .auth-card fieldset legend{font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:1rem;color:#14343A;padding:0 8px}
.auth-page .auth-card fieldset p{font-size:.88rem;color:#5F7476;line-height:1.6;margin:0 0 10px}
.auth-page .auth-card fieldset label{margin-top:12px}
.auth-page .auth-card .text-link{color:var(--teal-dark,#1F6E70);font-weight:600;font-size:.88rem}
.auth-page .auth-card .directory-grid{display:grid;grid-template-columns:1fr;gap:2px}
.auth-page .auth-card .directory-status{font-size:.86rem;color:#5F7476;margin-top:10px}
.auth-page .auth-card .directory-selected{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#fff;border:1px solid #DFE9E4;border-radius:14px;padding:12px 14px;margin-top:12px}
.auth-page .auth-card .cf-turnstile{margin:2px 0}
/* Search bar kampus/sekolah: ikon kaca pembesar + hasil menempel di bawah bar */
.auth-page .auth-card .campus-select-row{display:none}
.auth-page .auth-card .campus-search-wrap .campus-search-ic{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#7D9496;display:grid;place-items:center;pointer-events:none;z-index:1}
.auth-page .auth-card .campus-search-wrap input{padding-left:44px}
.auth-page .auth-card .campus-search-results{gap:8px}
.auth-page .auth-card .campus-search-results:not(:empty){background:#fff;border:1px solid #DFE9E4;border-radius:16px;padding:8px;box-shadow:0 16px 34px rgba(18,56,58,.13);max-height:330px;overflow:auto}
.auth-page{min-height:100vh;min-height:100dvh}
@media (max-width:640px){
 body.account-layout #main:has(>.auth-page){padding-bottom:calc(var(--mobile-nav-clearance) + env(safe-area-inset-bottom))}
}
@media(max-width:900px){
 .auth-page{display:block;min-height:100dvh;padding:26px 14px 40px}
 .auth-page .auth-photo{display:none}
 .auth-page .auth-card{background:#fff;border:1px solid #E3EFE9;border-radius:22px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 22px rgba(18,56,58,.07),0 28px 54px rgba(18,56,58,.10);max-width:none;padding:26px 20px 30px;gap:12px;margin:0}
 .auth-page .auth-card h1{font-size:1.72rem;margin-top:14px}
 .auth-page .auth-card input:not([type=checkbox]),.auth-page .auth-card select{font-size:16px}
}
</style>
 <div class="auth-photo"><img src="{{ $registerPhoto['url'] }}" alt="{{ $registerPhoto['alt'] }}"><div><p class="eyebrow">{{ __('Data minimal') }}</p><h2>{{ __('Pilih sekolah atau kampusmu.') }}</h2><p>{{ __('Sekolah memakai direktori nasional. Kampus memakai daftar institusi yang sudah didaftarkan pengelola. Kami tidak meminta alamat rumah atau nomor telepon.') }}</p></div></div>
 <form class="auth-card" data-auth-submit method="post" action="{{ route('register') }}">
  @csrf
  <input type="hidden" name="selected_school_npsn" value="{{ old('school_npsn', old('selected_school_npsn')) }}" autocomplete="off">
  <a class="brand" href="{{ route('home') }}"><span class="brand-mark">R</span><b>Rute<span>Aman</span></b></a>
  <h1>{{ __('Buat akun') }}</h1>
  @if($errors->any())<div class="error" role="alert">{{ __($errors->first()) }}</div>@endif
  <label>{{ __('Nama panggilan') }}<input name="display_name" value="{{ old('display_name') }}" required autocomplete="name"></label>
  @php($registrationType=old('institution_type',request('institution_type','school'))==='campus'?'campus':'school')
  <label for="register-institution-type">{{ __('Jenis institusi') }}<select id="register-institution-type" name="institution_type"><option value="school" @selected($registrationType==='school')>{{ __('Sekolah') }}</option><option value="campus" @selected($registrationType==='campus')>{{ __('Kampus') }}</option></select></label>
  <noscript><p><a href="{{ route('register',['institution_type'=>'school']) }}">{{ __('Daftar dengan sekolah') }}</a> · <a href="{{ route('register',['institution_type'=>'campus']) }}">{{ __('Daftar dengan kampus') }}</a></p></noscript>
  <fieldset id="register-campus-fields" class="school-directory" @if($registrationType!=='campus') hidden disabled @endif><legend>{{ __('Kampus') }}</legend>
   <p>{{ __('Pilih kampus terdaftar. Pilihan ini bukan bukti kamu mahasiswa atau anggota kampus. Kampus yang belum ada perlu ditambahkan pengelola beserta lokasi dan wilayahnya.') }}</p>
<a class="text-link catalog-entry-link" href="{{ route('campus-catalog.index',['country'=>'ID']) }}">{{ __('Cari daftar kampus Indonesia / dunia') }}</a>
   <label>{{ __('Cari kampus terdaftar') }}<span class="campus-search-wrap"><span class="campus-search-ic" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg></span><input type="search" id="register-campus-query" autocomplete="off" placeholder="{{ __('Ketik nama kampus') }}"><button type="button" id="register-campus-clear" class="campus-search-clear" aria-label="{{ __('Hapus pencarian') }}" hidden>&times;</button></span></label>
   <div id="register-campus-results" class="campus-search-results" role="listbox"></div>
   <p id="register-campus-status" role="status" aria-live="polite">{{ collect($campuses??[])->isEmpty()?__('Belum ada kampus aktif. Minta pengelola menambahkan kampus.'):__('Kampus tidak memakai NPSN. Pilih hasil yang sesuai, lalu isi akun di bawah.') }}</p>
   <label class="campus-select-row">{{ __('Kampus tujuan') }}<select name="campus_id" id="register-campus-id"><option value="">{{ __('Pilih kampus') }}</option>@foreach($campuses??[] as $c)<option value="{{ $c->id }}" @selected((string)old('campus_id',request('campus_id'))===(string)$c->id)>{{ $c->name }} · {{ $c->country_code }}{{ $c->campus_code?' · '.$c->campus_code:'' }}</option>@endforeach</select></label>
   <label>{{ __('Fakultas') }}<input type="search" name="faculty" id="register-faculty" value="{{ old('faculty') }}" list="faculty-suggestions" autocomplete="off" placeholder="{{ __('Contoh: Fakultas Teknik') }}"><datalist id="faculty-suggestions"><option value="Fakultas Teknik"><option value="Fakultas Ekonomi dan Bisnis"><option value="Fakultas Hukum"><option value="Fakultas Kedokteran"><option value="Fakultas Ilmu Komputer"><option value="Fakultas Ilmu Sosial dan Ilmu Politik"><option value="Fakultas Psikologi"><option value="Fakultas Pertanian"><option value="Fakultas Matematika dan Ilmu Pengetahuan Alam"><option value="Fakultas Farmasi"><option value="Fakultas Kesehatan Masyarakat"><option value="Fakultas Keguruan dan Ilmu Pendidikan"><option value="Fakultas Sastra"><option value="Fakultas Teknologi Industri"><option value="Sekolah Pascasarjana"><option value="Sekolah Vokasi"></datalist></label>
   <p class="faculty-help">{{ __('Tulis fakultas tempat kamu kuliah di kampus tersebut.') }}</p>
  </fieldset>
@push('styles')
<style>
.campus-search-results{display:flex;flex-direction:column;gap:10px;margin-top:10px}
.campus-result-item{display:flex;align-items:center;gap:13px;text-align:left;background:#ffffff;border:1px solid #e6edec;border-radius:14px;padding:13px 14px;cursor:pointer;width:100%;box-shadow:0 1px 4px rgba(18,56,58,.05);transition:border-color .15s,box-shadow .15s,transform .15s}
.campus-result-item:hover{border-color:#bcd8d5;box-shadow:0 4px 14px rgba(18,56,58,.09);transform:translateY(-1px)}
.campus-result-item:active{transform:translateY(0) scale(.99);box-shadow:0 1px 4px rgba(18,56,58,.05)}
.campus-result-number{flex-shrink:0;width:26px;font-size:12px;font-weight:700;color:#c3cfce;font-variant-numeric:tabular-nums;letter-spacing:.05em}
.campus-result-info{flex:1;display:flex;flex-direction:column;gap:4px;min-width:0}
.campus-result-name{font-size:15px;font-weight:600;color:#16292a;line-height:1.4;letter-spacing:-.015em}
.campus-result-name mark{background:linear-gradient(180deg,transparent 55%,#d9efe9 55%);color:inherit;font-weight:700;padding:0 1px;border-radius:2px}
.campus-result-sub{display:flex;align-items:center;gap:6px;font-size:12px;color:#93a3a4;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.campus-dot{flex-shrink:0;width:7px;height:7px;border-radius:50%}
.campus-dot.is-reg{background:#34a853;box-shadow:0 0 0 3px rgba(52,168,83,.15)}
.campus-dot.is-ror{background:#4285f4;box-shadow:0 0 0 3px rgba(66,133,244,.15)}
.campus-sep{color:#d3dbdb}
.campus-id{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;color:#a7b6b7}
.campus-result-arrow{flex-shrink:0;width:20px;height:20px;position:relative;opacity:.3;transition:opacity .15s,transform .15s}
.campus-result-arrow::after{content:'';position:absolute;top:5px;left:5px;width:7px;height:7px;border-top:1.5px solid #4a5e5f;border-right:1.5px solid #4a5e5f;transform:rotate(45deg);border-radius:1px}
.campus-result-item:hover .campus-result-arrow{opacity:.75;transform:translateX(2px)}


.campus-search-wrap{position:relative;display:block}
.campus-search-wrap input{width:100%;padding-right:36px}
.campus-search-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:24px;height:24px;border-radius:50%;border:0;background:#e3eaea;color:#5a6e6f;font-size:16px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center}
.campus-search-clear:hover{background:#d0dbdb;color:#1a2e2f}
#register-campus-id:disabled{opacity:.85;background:#f0f4f4;cursor:not-allowed}
</style>
@endpush
@push('scripts')
<script>
(function(){
 var q=document.getElementById('register-campus-query'),sel=document.getElementById('register-campus-id'),st=document.getElementById('register-campus-status'),box=document.getElementById('register-campus-results'),clearBtn=document.getElementById('register-campus-clear');
 if(!q||!sel)return;
 if(clearBtn)clearBtn.addEventListener('click',function(){unlockSelect();q.focus();});
 var timer=null,ctrl=null;
 function msg(t){if(st)st.textContent=t;}
 var lastTerm='';
 function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
 function hiName(name,term){
  if(!term||term.length<3)return escHtml(name);
  var idx=name.toLowerCase().indexOf(term.toLowerCase());
  if(idx<0)return escHtml(name);
  return escHtml(name.slice(0,idx))+'<mark>'+escHtml(name.slice(idx,idx+term.length))+'</mark>'+escHtml(name.slice(idx+term.length));
 }
 function render(items){
  box.innerHTML='';
  items.forEach(function(item){
   var b=document.createElement('button');b.type='button';b.className='campus-result-item';b.setAttribute('role','option');
   var num=document.createElement('span');num.className='campus-result-number';num.textContent=String(item.number).padStart(2,'0');
   var info=document.createElement('span');info.className='campus-result-info';
   var name=document.createElement('span');name.className='campus-result-name';name.innerHTML=hiName(item.name,lastTerm);
   var sub=document.createElement('span');sub.className='campus-result-sub';
   var dot=document.createElement('i');dot.className='campus-dot '+(item.source==='registered'?'is-reg':'is-ror');
   var stxt=document.createElement('span');stxt.textContent=item.source==='registered'?'Terdaftar':(String(item.id).indexOf('pddikti:')===0?'PDDIKTI':'ROR');
   sub.appendChild(dot);sub.appendChild(stxt);
   var loc=item.source==='registered'?(item.campus_code||item.city||''):(item.city||'');
   if(loc){var sep=document.createElement('span');sep.className='campus-sep';sep.textContent='\u00b7';sub.appendChild(sep);var l=document.createElement('span');l.textContent=loc;sub.appendChild(l);}
   var idt=item.source==='registered'?String(item.id):String(item.id).replace('https://ror.org/','');
   var sep2=document.createElement('span');sep2.className='campus-sep';sep2.textContent='\u00b7';sub.appendChild(sep2);
   var idEl=document.createElement('span');idEl.className='campus-id';idEl.textContent=idt;sub.appendChild(idEl);
   var arrow=document.createElement('span');arrow.className='campus-result-arrow';arrow.setAttribute('aria-hidden','true');
   info.appendChild(name);info.appendChild(sub);
   b.appendChild(num);b.appendChild(info);b.appendChild(arrow);
   b.addEventListener('click',function(){select(item);});
   box.appendChild(b);
  });
 }
 function lockSelect(){q.setAttribute('readonly','readonly');clearBtn.hidden=false;}
 function unlockSelect(){sel.value='';q.removeAttribute('readonly');q.value='';box.innerHTML='';clearBtn.hidden=true;msg('Ketik minimal 3 karakter nama kampus atau ID.');}
 function select(item){
  if(item.source==='registered'){
   var opt=null;
   for(var i=0;i<sel.options.length;i++){if(sel.options[i].value==String(item.id)){opt=sel.options[i];break;}}
   if(!opt){opt=new Option(item.name+' \u00b7 '+(item.country_code||'ID'),item.id);sel.add(opt);}
   sel.value=String(item.id);msg('Kampus dipilih: '+item.name+'. Untuk ganti, hapus pencarian dulu.');box.innerHTML='';lockSelect();
  }else{
   msg('Menambahkan kampus ke daftar...');
   var csrf=(document.querySelector('input[name=_token]')||{}).value||'';
   fetch('/api/register-campus-provision',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({ror_id:item.id,name:item.name,country_code:item.country_code||'ID',city:item.city,latitude:item.latitude,longitude:item.longitude})}).then(function(r){return r.json().then(function(d){return{ok:r.ok,d:d};});}).then(function(res){
    if(!res.ok)throw new Error(res.d.message||'Gagal menambahkan kampus.');
    var opt=new Option(item.name+' \u00b7 '+(item.country_code||'ID'),res.d.id);sel.add(opt);sel.value=String(res.d.id);
    msg((res.d.created?'Kampus ditambahkan dan dipilih: ':'Kampus dipilih: ')+item.name+'. Untuk ganti, hapus pencarian dulu.');box.innerHTML='';lockSelect();
   }).catch(function(e){msg(e.message);});
  }
 }
 function search(){
  if(sel.disabled)return;
  var term=q.value.trim();box.innerHTML='';lastTerm=term;
  if(term.length<3){msg('Ketik minimal 3 karakter nama kampus atau ID.');return;}
  if(ctrl)ctrl.abort();ctrl=new AbortController();msg('Mencari kampus...');
  fetch('/api/register-campus-search?query='+encodeURIComponent(term),{signal:ctrl.signal,headers:{'Accept':'application/json'}}).then(function(r){return r.json().then(function(d){return{ok:r.ok,d:d};});}).then(function(res){
   if(!res.ok)throw new Error(res.d.message||'Pencarian gagal.');
   var results=res.d.data||[];
   if(results.length){render(results);msg('Ditemukan '+results.length+' kampus. Pilih salah satu.');}
   else{msg('Kampus tidak ditemukan. Coba kata kunci lain.');}
  }).catch(function(e){if(e.name!=='AbortError')msg(e.message);});
 }
 q.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(search,500);});
 q.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();clearTimeout(timer);search();}});
})();
</script>
@endpush
@push('scripts')
<script>
(function(){
 var q=document.getElementById('register-campus-query'),box=document.getElementById('register-campus-results');
 if(!q||!box)return;
 box.addEventListener('click',function(e){
  var b=e.target.closest('.campus-result-item');if(!b)return;
  var n=b.querySelector('.campus-result-name')||b.querySelector('strong');
  if(n)q.value=n.textContent.trim();
 });
})();
</script>
@endpush
  <fieldset @if($registrationType==='campus') hidden disabled @endif class="school-directory liquid-root" data-liquid-root
    data-school-directory
    data-provinces-url="{{ route('school-directory.provinces') }}"
    data-regencies-url="{{ route('school-directory.regencies') }}"
    data-districts-url="{{ route('school-directory.districts') }}"
    data-schools-url="{{ route('school-directory.schools') }}"
    data-old-province="{{ old('province_code') }}"
    data-old-regency="{{ old('regency_code') }}"
    data-old-district="{{ old('district_code') }}"
    data-old-npsn="{{ old('school_npsn') }}"
    data-old-school="{{ old('school_name') }}">
   <legend>{{ __('Sekolah') }}</legend>
   <p class="directory-help">{{ __('Ketik nama sekolah atau NPSN untuk mencari di seluruh Indonesia, atau pilih wilayah di bawah untuk mempersempit.') }}</p>
   <label>{{ __('Cari sekolah nasional') }}
    <span class="campus-search-wrap">
     <span class="campus-search-ic" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg></span><input type="search" id="reg-school-nat-query" autocomplete="off" placeholder="{{ __('Contoh: MAN 2 Rembang atau 20363196') }}">
     <button type="button" id="reg-school-nat-clear" class="campus-search-clear" aria-label="{{ __('Hapus') }}" hidden>&times;</button>
    </span>
   </label>
   <p id="reg-school-nat-status" class="directory-status" role="status" aria-live="polite"></p>
   <div id="reg-school-nat-results" class="campus-search-results" role="listbox" aria-label="{{ __('Hasil pencarian nasional') }}"></div>
   <p class="directory-help" style="margin-top:12px" data-region-block>{{ __('Atau pilih wilayah, cari sekolah, lalu pilih hasilnya. Setelah dipilih, nama dan NPSN dikunci sampai kamu menekan Ganti.') }}</p>
   <div class="directory-grid directory-grid--regions" data-region-block>
    <label>{{ __('Provinsi') }}<select id="register-province" name="province_code" required><option value="">{{ __('Memuat provinsi…') }}</option></select></label>
    <label>Kabupaten/Kota<select id="register-regency" name="regency_code" required disabled><option value="">{{ __('Pilih provinsi dahulu') }}</option></select></label>
    <label>{{ __('Kecamatan') }}<select id="register-district" name="district_code" required disabled><option value="">{{ __('Pilih kabupaten/kota dahulu') }}</option></select></label>
   </div>
   <label for="school-directory-query" data-region-block>{{ __('Cari sekolah atau NPSN') }}
    <input id="school-directory-query" name="school_query" value="{{ old('school_query') }}" type="search" placeholder="{{ __('Contoh: SMAN 8 Jakarta atau 20102568') }}" minlength="3" autocomplete="off" disabled>
   </label>
   <input autocomplete="off" type="hidden" id="school-directory-npsn" name="school_npsn" value="{{ old('school_npsn') }}">
   <input type="hidden" id="school-directory-name" name="school_name" value="{{ old('school_name') }}">
   <div class="directory-status" data-directory-status data-region-block role="status" aria-live="polite">{{ __('Pilih provinsi untuk memulai.') }}</div>
   <div class="directory-selected liquid-glass" data-glass-variant="row" data-directory-selected @if(!old('school_npsn')) hidden @endif>
    <span><small>{{ __('Sekolah dipilih') }}</small><b data-selected-name>{{ old('school_name') }}</b><em data-selected-npsn>{{ old('school_npsn') ? __('NPSN ').old('school_npsn') : '' }}</em></span>
    <button type="button" class="text-link" data-change-school>{{ __('Ganti') }}</button>
   </div>
   <div class="directory-results" data-directory-results role="listbox" aria-label="{{ __('Hasil pencarian sekolah') }}"></div>
  </fieldset>
  <label>{{ __('Email') }}<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
  <label>{{ __('Kata sandi') }}<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
  <label>{{ __('Ulangi kata sandi') }}<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
  <label class="check"><input type="checkbox" name="parental_consent" value="1" required> {{ __('Saya menyetujui ketentuan akun dan telah memenuhi persetujuan orang tua/wali bila diwajibkan oleh usia atau kebijakan institusi.') }}</label>
  @include('partials.turnstile')
<button class="btn btn-dark">{{ __('Daftar') }}</button>
  <p>{{ __('Sudah punya akun?') }} <a href="{{ route('login') }}">{{ __('Masuk') }}</a></p>
 </form>
</div>
@push('scripts')
<script>
(function(){
 const q=document.getElementById('reg-school-nat-query'),st=document.getElementById('reg-school-nat-status'),
       box=document.getElementById('reg-school-nat-results'),clearBtn=document.getElementById('reg-school-nat-clear');
 const npsnF=document.getElementById('school-directory-npsn'),nameF=document.getElementById('school-directory-name');
 const fieldset=q?q.closest('fieldset'):null;
 if(!q||!npsnF)return;
 // Sembunyikan bagian wilayah saat nasional dipakai, sampai pilihan nasional dihapus (tombol x)
 const regionBlocks=fieldset?Array.from(fieldset.querySelectorAll('[data-region-block]')):[];
 const regionQuery=fieldset?fieldset.querySelector('#school-directory-query'):null;
 const provSel=fieldset?fieldset.querySelector('#register-province'):null;
 const regSel=fieldset?fieldset.querySelector('#register-regency'):null;
 const disSel=fieldset?fieldset.querySelector('#register-district'):null;
 function setRegionVisible(v){
  regionBlocks.forEach(el=>{el.style.display=v?'':'none';});
  if(!v){[provSel,regSel,disSel].forEach(s=>{if(s){s.disabled=true;s.required=false;}});if(regionQuery)regionQuery.disabled=true;}
  else{
   [provSel,regSel,disSel].forEach(s=>{if(s)s.required=true;});
   if(provSel)provSel.disabled=false;
   if(regSel)regSel.disabled=!provSel||!provSel.value;
   if(disSel)disSel.disabled=!regSel||!regSel.value;
   if(regionQuery)regionQuery.disabled=!disSel||!disSel.value;
  }
 }
 let timer=null,ctrl=null,locked=false;
 const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
 function msg(t){if(st)st.textContent=t;}
 function lock(name,npsn){
  locked=true;npsnF.value=npsn;nameF.value=name;
  q.value=name;q.setAttribute('readonly','readonly');clearBtn.hidden=false;box.innerHTML='';
  if(fieldset)fieldset.dataset.natLocked='1';
  setRegionVisible(false);
  // Update panel selected bawaan
  const sel=fieldset.querySelector('[data-directory-selected]');
  if(sel){sel.hidden=false;sel.querySelector('[data-selected-name]').textContent=name;sel.querySelector('[data-selected-npsn]').textContent='NPSN '+npsn;}
  msg('Sekolah dipilih: '+name+'. Untuk ganti, hapus pencarian.');
 }
 function unlock(){
  locked=false;npsnF.value='';nameF.value='';q.value='';q.removeAttribute('readonly');clearBtn.hidden=true;box.innerHTML='';
  if(fieldset)delete fieldset.dataset.natLocked;
  setRegionVisible(true);
  const sel=fieldset.querySelector('[data-directory-selected]');if(sel)sel.hidden=true;
  msg('');
 }
 clearBtn.addEventListener('click',()=>{unlock();q.focus();});
 const gantiBtn=fieldset?fieldset.querySelector('[data-change-school]'):null;
 if(gantiBtn)gantiBtn.addEventListener('click',()=>{if(locked)unlock();});
 async function search(){
  if(locked)return;
  const term=q.value.trim();box.innerHTML='';
  if(term.length<3){msg(term.length?'Ketik minimal 3 karakter.':'');return;}
  if(ctrl)ctrl.abort();ctrl=new AbortController();
  msg('Mencari di seluruh Indonesia...');
  try{
   const r=await fetch('/api/school-directory/search-nationwide?query='+encodeURIComponent(term),{signal:ctrl.signal,headers:{'Accept':'application/json'}});
   const d=await r.json();
   if(!r.ok)throw new Error('Pencarian gagal.');
   const items=d.data||[];
   if(!items.length){msg('Tidak ditemukan. Coba kata kunci lain atau NPSN.');return;}
   msg(items.length+' sekolah ditemukan. Pilih salah satu.');
   items.forEach((item,i)=>{
    const b=document.createElement('button');b.type='button';b.className='campus-result-item';b.setAttribute('role','option');
    b.innerHTML='<span class="campus-result-number">'+String(i+1).padStart(2,'0')+'</span><span class="campus-result-info"><span class="campus-result-name">'+esc(item.name)+'</span><span class="campus-result-sub"><i class="campus-dot is-ror"></i><span>NPSN '+esc(item.npsn)+'</span><span class="campus-sep">·</span><span>'+esc([item.regency,item.province].filter(Boolean).join(', '))+'</span></span></span><span class="campus-result-arrow" aria-hidden="true"></span>';
    b.addEventListener('click',()=>{lock(item.name,item.npsn);});
    box.append(b);
   });
  }catch(e){if(e.name!=='AbortError')msg('Gagal mencari. Coba lagi.');}
 }
 q.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(search,500);});
 q.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();clearTimeout(timer);search();}});
})();
</script>
@endpush

@endsection