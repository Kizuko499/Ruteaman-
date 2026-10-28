@extends('admin.layout')
@section('title', __('Antrean Verifikasi'))
@section('content')
<style data-ruteaman-admin-queue="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-notice{border-radius:14px}
.admin-main .queue-filter{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-end;background:#fff;border:1px solid #E3EFE9;border-radius:16px;padding:16px 18px;margin-bottom:18px;box-shadow:0 1px 2px rgba(18,56,58,.04)}
.admin-main .queue-filter label{font-size:13px;font-weight:600;color:#33554F;display:grid;gap:8px}
.admin-main .queue-filter select{border:1px solid #CBDAD5;border-radius:12px;min-height:46px;padding:9px 12px;background:#fff;min-width:240px}
.admin-main .queue-filter select:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .queue-filter button{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;cursor:pointer}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .admin-section-head>span{background:#EAF4EE;color:#1F6E70;border-radius:999px;padding:5px 12px;font-size:12.5px;font-weight:700}
.admin-main .admin-panel>details{border:1px solid #E3EFE9;border-radius:16px;background:#FBFDFC;padding:16px 18px;margin-top:16px}
.admin-main .admin-panel>details>summary{font-weight:700;color:#1F4A4C;cursor:pointer}
.admin-main .admin-field-grid label{font-size:13px;font-weight:600;color:#33554F;display:grid;gap:8px}
.admin-main .admin-panel input:not([type=hidden]){border:1px solid #CBDAD5;border-radius:12px;min-height:48px;padding:10px 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-panel input:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-panel form label{font-size:13px;font-weight:600;color:#33554F}
.admin-main .admin-panel button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 22px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel button.admin-secondary,.admin-main a.admin-secondary{border:1px solid #CBDAD5;background:#fff;border-radius:12px;min-height:44px;padding:10px 18px;font-weight:600;color:#1F4A4C;text-decoration:none;display:inline-flex;align-items:center;cursor:pointer}
.admin-main .admin-panel button.admin-secondary:hover,.admin-main a.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-panel button.admin-danger{background:#FDF0EA;border:1px solid #F0D9CB;color:#B4552D;border-radius:12px;min-height:44px;padding:10px 18px;font-weight:600;cursor:pointer}
.admin-main .admin-panel button.admin-danger:hover{background:#FAE4D8}
</style>
<div class="admin-page-head">
 <p class="admin-eyebrow">{{ auth()->user()->role->label() }}</p>
 <h1>{{ __('Antrean Verifikasi') }}</h1>
 <p>{{ $allSchools ? __('Semua institusi (cakupan Admin Utama)') : ($schoolModel?->name ?? __('Wilayah akun')) }} · {{ $hazards->total() }} {{ __('perlu diperiksa. Laporan yang kamu buat sendiri tidak bisa kamu verifikasi sendiri.') }}</p>
</div>
@if(($schools ?? collect())->count() > 1 || ($canAll ?? false))
 <form method="get" action="{{ route('verifier.queue') }}" class="queue-filter">
  <label for="queue-school">{{ __('Institusi') }}</label>
  <select id="queue-school" name="school">
   @if($canAll ?? false)<option value="" @selected(!($schoolModel ?? null))>{{ __('Semua institusi') }}</option>@endif
   @foreach($schools ?? [] as $option)<option value="{{ $option->id }}" @selected(($schoolModel ?? null)?->id === $option->id)>{{ $option->name }}</option>@endforeach
  </select>
  <button type="submit">{{ __('Terapkan') }}</button>
 </form>
@endif
<p class="admin-notice">{{ __('Laporan belum terverifikasi — termasuk laporan publik langsung dan riwayat yang sudah kedaluwarsa — tetap tersedia untuk ditinjau. Laporan lama yang dipastikan terverifikasi tidak menjadi baru: waktu pengamatannya tetap yang lama, jadi jangan anggap itu kondisi terkini tanpa bukti baru.') }}</p>
@forelse($hazards as $hazard)
 @php($isCommunity = (bool) $hazard->getAttribute('is_community'))
 <section class="admin-panel">
  <div class="admin-section-head"><h2>{{ __($hazard->type->label) }}</h2><span>{{ $hazard->status_label }}</span></div>
  <p>{{ $isCommunity ? __('Laporan warga') : ($hazard->verificationSchool?->name ?? __('Laporan institusi, asal perlu ditinjau')) }} · {{ $hazard->reports_count }} {{ __('laporan ·') }} {{ $hazard->confirmations_count }} {{ __('konfirmasi ·') }} {{ $hazard->first_reported_at->diffForHumans() }}</p>
  @if($hazard->requires_moderation)
   <p class="admin-notice">{{ __('Menunggu pemeriksaan; belum tampil publik.') }}</p>
  @endif
  <a class="admin-secondary" href="{{ route('conditions.show', $hazard) }}">{{ __('Periksa kondisi dan riwayat') }}</a>
  @if($hazard->reports->isNotEmpty())
   <details>
    <summary>{{ __('Data pelapor dan bukti (privat)') }}</summary>
    @foreach($hazard->reports as $report)
     <p>{{ $report->guest_name ?: ($report->reporter?->display_name ?? __('Pelapor anonim')) }} · {{ $report->school?->name ?? $report->reporter?->school?->name ?? __('Masyarakat / institusi tidak tercatat') }}</p>
     <p>{{ $report->reporter_id ? __('Laporan pengguna login') : __('Laporan tamu') }}</p>
     @if(is_null($report->reporter_id))
      <p>{{ __('Nama/institusi belum membuktikan identitas') }}</p>
     @endif
     @if($report->description)
      <p>{{ $report->description }}</p>
     @endif
    @endforeach
   </details>
  @endif
  @if($hazard->can_review && !$hazard->reports->contains('reporter_id', auth()->id()))
   @if($isCommunity && \Illuminate\Support\Facades\Route::has('community.moderate'))
    <form method="post" data-sensitive action="{{ route('community.moderate', $hazard) }}">
     @csrf
     <input type="hidden" name="action" value="verify">
     <label>{{ __('Alasan verifikasi (tanpa data pribadi)') }}<input name="reason" minlength="3" maxlength="500" required></label>
     <button class="admin-primary">{{ __('Verifikasi') }}</button>
    </form>
   @else
    <form method="post" action="{{ route('hazards.verify', $hazard) }}">
     @csrf
     <div class="admin-field-grid">
      <label>{{ __('Label lokasi publik') }}<input name="location_label" maxlength="120" value="{{ $hazard->location_label }}"></label>
      <label>{{ __('Catatan (tanpa data pribadi)') }}<input name="note" maxlength="1000"></label>
     </div>
     <div class="admin-actions">
      <button class="admin-primary" name="decision" value="verified">{{ __('Verifikasi') }}</button>
      <button class="admin-secondary" name="decision" value="needs_evidence">{{ __('Minta bukti') }}</button>
      <button class="admin-danger" name="decision" value="rejected">{{ __('Tolak') }}</button>
     </div>
    </form>
   @endif
  @else
   <p class="admin-notice">{{ __('Mode hanya baca: kamu belum bisa memutuskan laporan ini. Periksa izinmu, apakah fitur verifikasi menyala, dan apakah ini laporanmu sendiri.') }}</p>
  @endif
 </section>
@empty
 <section class="admin-panel"><h2>{{ __('Antrean kosong') }}</h2><p>{{ __('Tidak ada laporan yang perlu ditinjau dalam cakupan ini.') }}</p></section>
@endforelse
{{ $hazards->links() }}
@endsection
