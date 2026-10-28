@extends('layouts.app')
@section('title',__('Paket eskalasi'))
@section('content')
<div class="form-page escalation-package"><a class="back" href="{{ route('school.dashboard') }}">{{ __('← Dashboard') }}</a><p class="eyebrow">{{ __('Admin Sekolah · tindak lanjut manual') }}</p><h1>{{ __('Paket eskalasi') }}</h1>
 <p class="package-lead">{{ __('Siapkan informasi publik, salin pesan siap kirim, lalu catat respons kanal resmi tanpa membuka identitas pelapor.') }}</p>
 <div class="package-stats"><div class="package-stat"><strong>{{ $hazard->escalations->count() }}</strong><span>{{ __('riwayat tindak lanjut') }}</span></div><div class="package-stat"><strong>{{ $hazard->confirmations_count ?? 0 }}</strong><span>{{ __('bukti terhubung') }}</span></div></div>
 <p>{{ __('Halaman publik berisi foto, koordinat, jumlah bukti, dan riwayat verifikasi. Tidak ada identitas pelapor. Belum ada integrasi dua arah dengan pemerintah.') }}</p>
 <div class="package-actions"><a class="btn btn-soft" href="{{ route('hazards.public',$hazard) }}">{{ __('Buka halaman publik kondisi') }}</a><button type="button" class="btn btn-dark" id="copy-escalation-message">{{ __('Salin pesan WhatsApp') }}</button></div><p id="copy-escalation-status" role="status"></p>
 <label class="package-message">{{ __('Pesan siap salin') }}<textarea id="escalation-message" rows="12" readonly>{{ $text }}</textarea></label>
 <h2>{{ __('Status tindak lanjut') }}</h2>
 <p class="package-section-lead">{{ __('Catat kanal resmi, perkembangan respons, dan pembaruan singkat agar institusi punya jejak tindak lanjut yang rapi.') }}</p>
 @forelse($hazard->escalations as $item)
 <form method="post" class="escalation-form" action="{{ route('escalations.update',$item) }}">@csrf @method('PATCH')
  <label>{{ __('Kanal resmi / tujuan') }}<input name="channel" required maxlength="100" value="{{ $item->channel }}"></label>
  <label>{{ __('Status') }}<select name="response_status">@foreach(['draft'=>'Draft','forwarded'=>'Diteruskan','responded'=>'Ditanggapi','no_response'=>__('Tidak ada respons')] as $key=>$label)<option value="{{ $key }}" @selected($item->response_status===$key)>{{ $label }}</option>@endforeach</select></label>
  <label class="wide">{{ __('Catatan respons (tanpa data siswa)') }}<textarea name="response_note" maxlength="1000">{{ $item->response_note }}</textarea></label>
  <p class="escalation-forwarded">{{ __('Diteruskan:') }} {{ $item->forwarded_at?->format('d/m/Y H:i')??'belum' }}</p><button class="btn btn-dark">{{ __('Simpan status') }}</button>
 </form>@empty<form method="post" class="escalation-form" action="{{ route('hazards.escalate',$hazard) }}">@csrf<label>{{ __('Kanal resmi') }}<input name="channel" required placeholder="{{ __('Nama kanal resmi wilayah') }}"></label><button class="btn btn-dark">{{ __('Buat draft eskalasi') }}</button></form>@endforelse
 @if($errors->any())<p class="error">{{ __($errors->first()) }}</p>@endif
</div>
<style data-ruteaman-package="v1">
.escalation-package{--pkg-line:#DFE9E4}
.escalation-package .eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0;font-weight:700;margin:10px 0 0}
.escalation-package h1{font-size:29px;font-weight:600;letter-spacing:-.015em;line-height:1.25;margin:6px 0 8px}
.package-lead{color:#5F7A76;font-size:14.5px;line-height:1.65;margin:0 0 18px}
.package-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 0 18px}
.package-stat{background:#fff;border:1px solid var(--pkg-line);border-radius:18px;padding:16px 18px;display:grid;gap:2px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.package-stat strong{font-size:27px;font-weight:600;line-height:1.1;color:#1F6E70}
.package-stat span{color:#7A918B;font-size:13px;line-height:1.45}
.escalation-package>p{color:#49696B;font-size:14px;line-height:1.65}
.package-actions{display:grid;gap:10px;margin:16px 0 4px}
.package-actions .btn{width:100%;text-align:center;justify-content:center;min-height:48px;padding:12px 18px;border-radius:14px;font-weight:600}
.package-actions .btn-soft{background:#fff;border:1.5px solid var(--pkg-line);color:#14343A;box-shadow:0 1px 2px rgba(18,56,58,.05)}
.package-actions .btn-dark{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;box-shadow:0 8px 18px rgba(31,110,112,.26)}
@media(min-width:640px){.package-actions{grid-template-columns:1fr 1fr}}
#copy-escalation-status{color:#1F6E70;font-size:13px;font-weight:600;min-height:1.2em;margin:8px 0 0}
.package-message{display:grid;gap:8px;font-weight:600;font-size:15px;color:#12383A;margin-top:16px}
#escalation-message{width:100%;box-sizing:border-box;border:1.5px solid var(--pkg-line);border-radius:16px;background:#fff;padding:16px 18px;font:inherit;font-weight:400;font-size:14px;line-height:1.65;color:#24484B;resize:vertical;box-shadow:0 1px 2px rgba(18,56,58,.04)}
.escalation-package h2{font-size:21px;font-weight:600;letter-spacing:-.01em;line-height:1.3;margin:34px 0 6px}
.package-section-lead{color:#5F7A76;font-size:14px;line-height:1.65;margin:0 0 14px}
.escalation-form{background:#fff;border:1px solid var(--pkg-line);border-radius:20px;padding:20px;display:grid;gap:14px;margin:0 0 14px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.escalation-form label{display:grid;gap:6px;font-size:13px;font-weight:600;color:#49696B}
.escalation-form input,.escalation-form select{width:100%;box-sizing:border-box;height:46px;border:1.5px solid var(--pkg-line);border-radius:12px;padding:0 13px;font:inherit;font-size:14px;font-weight:400;background:#fff;color:#12383A}
.escalation-form textarea{width:100%;box-sizing:border-box;border:1.5px solid var(--pkg-line);border-radius:12px;padding:11px 13px;font:inherit;font-size:14px;font-weight:400;background:#fff;color:#12383A;min-height:110px;resize:vertical;line-height:1.55}
.escalation-form input:focus,.escalation-form select:focus,.escalation-form textarea:focus,#escalation-message:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.escalation-forwarded{margin:0;color:#7A918B;font-size:13.5px;align-self:center}
.escalation-form .btn{justify-self:start;min-height:46px;padding:0 22px;border-radius:12px;font-weight:600;background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;box-shadow:0 8px 18px rgba(31,110,112,.26)}
@media(min-width:720px){.escalation-form{grid-template-columns:1fr 1fr}.escalation-form label.wide{grid-column:1/-1}.escalation-form .btn{justify-self:end}}
@media(max-width:640px){.escalation-form{padding:16px}.escalation-form .btn{width:100%;justify-self:stretch;text-align:center}}
</style>
@endsection
