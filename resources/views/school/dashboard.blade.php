@extends('admin.layout')
@section('title',__('Dashboard Institusi'))
@section('content')
@php
 $viewer=auth()->user();$permissions=app(\App\Services\PermissionService::class);
 $canHazards=$permissions->can($viewer,'hazards.view',$schoolModel);
 $canEscalations=$permissions->can($viewer,'escalations.view',$schoolModel);
 $canAudit=$permissions->can($viewer,'audit.view',$schoolModel);
 $canVerify=$permissions->can($viewer,'verification.decide',$schoolModel);
 $canManage=$permissions->can($viewer,'escalations.manage',$schoolModel,true);
 $active=$schoolModel->status==='active';
@endphp
<style data-ruteaman-schooldash="v1">
/* Dashboard institusi: kaca dibuat lebih tenang — hero ramping, ubin statistik
   dan panel menyatu dengan bahasa kartu admin, lencana jadi pil lembut. */
.ra-school-dashboard .admin-main{background:#F6F8F6}
.school-dash-hero{border-radius:22px;border-color:#E3EFE9;background:rgba(255,255,255,.9);box-shadow:0 1px 2px rgba(18,56,58,.05),0 14px 34px rgba(18,56,58,.08);padding:26px 28px}
.school-dash-hero h1{font-size:30px;font-weight:600;letter-spacing:-.02em;line-height:1.15}
.school-dash-eyebrow{font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#359FA0;font-weight:700}
.school-dash-lead{font-size:14.5px;color:#5F7A76;max-width:52ch}
.school-dash-mode{font-size:13px;font-weight:600;color:#1F6E70}
.school-picker label{font-size:12.5px;font-weight:600;color:#49696B}
.school-picker input{height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;background:#fff;width:100%;box-sizing:border-box}
.school-picker input:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.school-glass-button{border-radius:14px;min-height:48px;font-size:14px;font-weight:600}
.school-glass-button--primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);box-shadow:0 8px 18px rgba(31,110,112,.26)}
.school-dash-stat{border-radius:18px;border-color:#E3EFE9;background:#fff;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:20px}
.school-dash-stat strong{font-size:30px;font-weight:600;letter-spacing:-.02em;line-height:1.1}
.school-dash-stat p{font-size:12.5px;color:#7A918B}
.school-dash-stat-label h2{font-size:13.5px;font-weight:600}
.school-dash-symbol{width:32px;height:32px;flex-basis:32px;border:0;border-radius:9px;background:#EAF3EE;color:#1F6E70;font-size:1.05rem}
.school-dash-jump a{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;padding:8px 14px;font-size:13px;font-weight:600;color:#14343A;text-decoration:none}
.school-dash-panel{border-radius:20px;border-color:#E3EFE9;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px}
.school-dash-section-head h2{font-size:17px;font-weight:600;letter-spacing:-.01em}
.school-dash-section-note{font-size:13px;color:#5F7A76}
.school-dash-count{background:#EAF4EE;color:#1F6E70;border:0;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}
.school-dash-condition{padding:16px 0;border-top:1px solid #EAF1EC}
.school-dash-condition-symbol{width:40px;height:40px;border:0;border-radius:12px;background:#EAF3EE;color:#1F6E70}
.school-dash-condition-symbol svg{width:22px;height:22px}
.school-dash-row-head h3{font-size:15px;font-weight:600}
.school-dash-status{background:#F2F7F4;border:1px solid #DFE9E4;color:#49696B;border-radius:999px;padding:4px 10px;font-size:11.5px;font-weight:600}
.school-dash-condition-body>p{font-size:13px;color:#5F7A76}
.school-dash-metadata{font-size:12.5px;color:#7A918B}
.school-dash-row-actions a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.school-dash-followup{padding:16px 0;border-top:1px solid #EAF1EC}
.school-dash-followup-state{background:#EAF4EE;color:#1F6E70;border:0;border-radius:999px;padding:4px 10px;font-size:11.5px;font-weight:700}
.school-dash-followup h3{font-size:15px;font-weight:600}
.school-dash-followup p{font-size:13px;color:#7A918B}
.school-dash-followup a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.school-dash-footer{font-size:12.5px;color:#7A918B;text-align:center}
.admin-topline .admin-secondary{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;padding:8px 14px;font-size:13px;font-weight:600;color:#14343A}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
</style>
<x-school.glass-filters/>
<noscript><style>.ra-school-dashboard [data-admin-menu],.ra-school-dashboard [data-sidebar-toggle],.ra-school-dashboard [data-sidebar-close],.ra-school-dashboard [data-open-reauth]{display:none}</style><nav class="school-dash-nojs" aria-label="{{ __('Navigasi langsung tanpa JavaScript') }}"><a href="{{ route('school.dashboard') }}">{{ __('Dashboard institusi') }}</a><a href="{{ route('map',['school'=>$schoolModel->id]) }}">{{ __('Peta sekolah') }}</a><a href="{{ route($viewer->role->admin()?'admin.account.profile':'account.profile') }}">{{ __('Akun saya') }}</a></nav></noscript>
<div class="school-dashboard" data-school-dashboard>
 <header class="school-dash-hero liquidglass" aria-labelledby="school-dashboard-title">
  <div><p class="school-dash-eyebrow">{{ __('Dashboard institusi') }} <span aria-hidden="true">/</span> {{ $viewer->role->label() }}</p><h1 id="school-dashboard-title">{{ $schoolModel->name }}</h1><p class="school-dash-lead">{{ __('Pantau kondisi jalan dan tindak lanjutnya untuk wilayah institusi ini dalam satu tempat.') }}</p>
  <p class="school-dash-mode"><span class="school-dash-dot" aria-hidden="true"></span>{{ $active?__('Pemantauan aktif'):__('Pemantauan dijeda — akses baca') }}</p></div>
   @if(($schools ?? collect())->count() > 1)
 <div class="school-picker" data-school-picker>
  <label for="school-picker-search">{{ __('Lihat dashboard institusi') }}</label>
  <input id="school-picker-search" type="search" value="{{ $schoolModel->name }}" placeholder="{{ __('Ketik nama institusi…') }}" autocomplete="off" data-school-search data-endpoint="{{ route('admin.schools.search') }}" data-target="{{ route('school.dashboard') }}">
  <div class="school-picker-results" data-school-results hidden></div>
  <noscript><a class="admin-secondary" href="{{ route('admin.schools') }}">{{ __('Pilih dari daftar institusi') }}</a></noscript>
 </div>
 @endif
<div class="school-dash-hero-actions"><x-school.glass-button :href="route('map',['school'=>$schoolModel->id])" :primary="true">{{ __('Buka peta sekolah') }}</x-school.glass-button>@if($canVerify)<x-school.glass-button :href="route('verifier.queue',['school'=>$schoolModel->id])">{{ __('Antrean verifikasi') }}</x-school.glass-button>@endif</div>
 </header>
 @if(!$active)<p class="school-dash-notice" role="status">{{ __('Pemantauan institusi ini sedang dijeda. Akun institusi hanya dapat membaca; perubahan tetap mengikuti izin di server.') }}</p>@endif
 <div class="school-dash-summary" role="group" aria-label="{{ __('Ringkasan data yang tersedia untuk akun ini') }}">
  @if($canHazards)<section class="school-dash-stat liquidglass"><div class="school-dash-stat-label"><span class="school-dash-symbol" aria-hidden="true">◎</span><h2>{{ __('Kondisi aktif') }}</h2></div><strong>{{ $hazards->count() }}</strong><p>{{ __('Laporan aktif di wilayah institusi') }}</p></section>@endif
  @if($canEscalations)<section class="school-dash-stat liquidglass"><div class="school-dash-stat-label"><span class="school-dash-symbol" aria-hidden="true">↗</span><h2>{{ __('Paket tindak lanjut') }}</h2></div><strong>{{ $escalations->count() }}</strong><p>{{ __('20 paket terbaru yang ditampilkan') }}</p></section>@endif
  @if($canAudit)<section class="school-dash-stat liquidglass"><div class="school-dash-stat-label"><span class="school-dash-symbol" aria-hidden="true">≡</span><h2>{{ __('Aktivitas terbaru') }}</h2></div><strong>{{ $logs->count() }}</strong><p>{{ __('20 aktivitas terakhir yang ditampilkan') }}</p></section>@endif
 </div>
 @if($canHazards||$canEscalations||$canAudit)<nav class="school-dash-jump" aria-label="{{ __('Bagian dashboard') }}">@if($canHazards)<a href="#school-conditions">{{ __('Kondisi aktif') }}</a>@endif @if($canEscalations)<a href="#school-followups">{{ __('Tindak lanjut') }}</a>@endif @if($canAudit)<a href="#school-activity">{{ __('Aktivitas') }}</a>@endif</nav>@endif
 <div class="school-dash-grid">
 @if($canHazards)<section class="school-dash-panel school-dash-conditions" id="school-conditions" aria-labelledby="conditions-heading"><header class="school-dash-section-head"><div><p class="school-dash-eyebrow">{{ __('Pemantauan') }}</p><h2 id="conditions-heading">{{ __('Kondisi di wilayah institusi') }}</h2></div><span class="school-dash-count">{{ $hazards->count() }} {{ __('aktif') }}</span></header><p class="school-dash-section-note">{{ __('Periksa status dan umur data. Tidak ada laporan bukan jaminan kondisi aman.') }}</p>
  <div class="school-dash-condition-list">@forelse($hazards as $h)<article class="school-dash-condition"><div class="school-dash-condition-symbol" aria-hidden="true"><x-condition-symbol :type="$h->type->code"/></div><div class="school-dash-condition-body"><div class="school-dash-row-head"><h3>{{ __($h->type->label) }}</h3><span class="school-dash-status">{{ $h->status_label }}</span></div><p>{{ $h->location_label?:__('Patokan belum diisi') }}</p>@if($h->is_demo)<span class="school-dash-demo school-dash-demo--icon" role="img" aria-label="{{ __('Data demo') }}" title="{{ __('Data demo — bukan laporan nyata') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg></span>@endif<div class="school-dash-metadata"><span>{{ $h->reports_count }} {{ __('laporan') }}</span><span>{{ $h->confirmations_count }} {{ __('konfirmasi') }}</span>@if($h->first_reported_at)<time datetime="{{ $h->first_reported_at->toIso8601String() }}">{{ $h->first_reported_at->locale(\App\Services\LanguagePreferences::carbonLocale())->diffForHumans() }}</time>@endif</div><div class="school-dash-row-actions">@if($canVerify)<a href="{{ route('verifier.queue',['school'=>$schoolModel->id]) }}">{{ __('Antrean verifikasi') }}</a>@endif @if($canManage)<a href="{{ route('hazards.package',$h) }}">{{ __('Paket eskalasi') }}</a>@endif</div></div></article>@empty<div class="school-dash-empty"><span aria-hidden="true">◎</span><h3>{{ __('Belum ada kondisi aktif') }}</h3><p>{{ __('Tidak ada laporan aktif yang dimuat untuk institusi ini. Tetap perhatikan kondisi jalan saat bepergian.') }}</p></div>@endforelse</div>
 </section>@endif
 @if($canEscalations)<section class="school-dash-panel school-dash-followups" id="school-followups" aria-labelledby="followups-heading"><header class="school-dash-section-head"><div><p class="school-dash-eyebrow">{{ __('Tindak lanjut') }}</p><h2 id="followups-heading">{{ __('Status eskalasi') }}</h2></div></header><p class="school-dash-section-note">{{ __('Pengiriman ke kanal resmi dilakukan manual. Status berasal dari catatan pengelola.') }}</p><div class="school-dash-followup-list">@forelse($escalations as $item)<article class="school-dash-followup"><span class="school-dash-followup-state">{{ match($item->response_status){'forwarded'=>__('Diteruskan'),'responded'=>__('Ditanggapi'),'no_response'=>__('Tidak ada respons'),default=>__('Draft')} }}</span><h3>{{ __($item->hazard->type->label) }}</h3><p>{{ $item->channel }}</p>@if($canManage)<a href="{{ route('hazards.package',$item->hazard) }}">{{ __('Kelola respons') }}</a>@endif</article>@empty<div class="school-dash-empty"><span aria-hidden="true">↗</span><h3>{{ __('Belum ada paket') }}</h3><p>{{ __('Paket eskalasi yang tersedia akan muncul di sini sesuai izin akun.') }}</p></div>@endforelse</div></section>@endif
 @if($canAudit)<section class="school-dash-panel school-dash-activity" id="school-activity" aria-labelledby="activity-heading"><header class="school-dash-section-head"><div><p class="school-dash-eyebrow">{{ __('Jejak perubahan') }}</p><h2 id="activity-heading">{{ __('Aktivitas wilayah') }}</h2></div><span class="school-dash-count">{{ $logs->count() }} {{ __('aktivitas ditampilkan') }}</span></header>@include('admin.log-rows')</section>@endif
 @if(!$canHazards&&!$canEscalations&&!$canAudit)<section class="school-dash-panel school-dash-empty"><span aria-hidden="true">◇</span><h2>{{ __('Akses sesuai peran') }}</h2><p>{{ __('Akun ini belum memiliki izin melihat laporan, tindak lanjut, atau log audit. Hubungi pengelola sekolah untuk meninjau akses yang diperlukan.') }}</p></section>@endif
 </div>
 <footer class="school-dash-footer"><p>{{ __('Informasi komunitas, bukan prediksi atau jaminan keselamatan perjalanan.') }}</p><span>{{ __('Cakupan: wilayah institusi yang dipilih') }}</span></footer>
</div>
<script>
(function () {
  var input = document.querySelector('[data-school-search]');
  var box = document.querySelector('[data-school-results]');
  if (!input || !box) { return; }
  var timer = null, seq = 0;
  function close() { box.hidden = true; box.innerHTML = ''; }
  input.addEventListener('focus', function () { input.select(); });
  input.addEventListener('input', function () {
    var q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { close(); return; }
    timer = setTimeout(function () {
      var mine = ++seq;
      fetch(input.dataset.endpoint + '?q=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}, credentials: 'same-origin'})
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (rows) {
          if (mine !== seq) { return; }
          box.innerHTML = '';
          if (!rows.length) {
            var empty = document.createElement('p');
            empty.className = 'school-picker-empty';
            empty.textContent = 'Tidak ada institusi yang cocok.';
            box.appendChild(empty);
          }
          rows.forEach(function (row) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'school-picker-result';
            b.textContent = row.name + (row.status === 'paused' ? ' (dijeda)' : '');
            b.addEventListener('click', function () { location.href = input.dataset.target + '?school=' + row.id; });
            box.appendChild(b);
          });
          box.hidden = false;
        })
        .catch(function () { close(); });
    }, 250);
  });
  document.addEventListener('click', function (e) { if (!box.contains(e.target) && e.target !== input) { close(); } });
  input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
})();
</script>
@endsection
