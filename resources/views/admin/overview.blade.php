@extends('admin.layout')
@section('title',__('Ringkasan'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<style data-ruteaman-admin-overview="v1">
/* Ringkasan admin: permukaan tenang, kartu statistik ringkas tanpa catatan
   kaki berulang, panel berkartu lembut, sidebar lebih halus di halaman ini. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-stat-grid{gap:14px;margin-bottom:26px}
.admin-stat{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:20px;gap:4px}
.admin-stat:first-child{background:linear-gradient(160deg,#EDF6F0,#E2F0E7);border-color:transparent}
.admin-stat span{font-size:12.5px;font-weight:600;color:#5F7A76}
.admin-stat strong{font-size:32px;font-weight:600;letter-spacing:-.02em;color:#12383A;line-height:1.1}
.admin-two-columns{gap:20px}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px}
.admin-section-head h2{font-size:17px;font-weight:600;letter-spacing:-.01em}
.admin-section-head a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-log-row{padding:14px 0;border-top:1px solid #EAF1EC}
.admin-log-row strong{font-size:14px;font-weight:600}
.admin-log-row small{font-size:12.5px;color:#7A918B}
.admin-log-row time{font-size:12px;color:#7A918B;white-space:nowrap}
.admin-log-dot{width:7px;height:7px;margin-top:6px;background:#359FA0}
.admin-guidance h2{font-size:20px;font-weight:600;letter-spacing:-.01em}
.admin-guidance p{font-size:13.5px;color:#5F7A76}
.admin-guidance .admin-secondary{display:inline-flex;padding:9px 14px;border:1.5px solid #DFE9E4;border-radius:12px;font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none;background:#fff}
.admin-guidance hr{border:0;border-top:1px solid #EAF1EC;margin:18px 0}
.admin-topline .admin-secondary{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;padding:8px 14px;font-size:13px;font-weight:600;color:#14343A}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('RUANG KERJA ADMIN') }}</p><h1>{{ $viewer->role->tier()<=2?__('Ringkasan platform'):__('Ringkasan sekolah') }}</h1><p>{{ __('Ringkasan akun, institusi, dan aktivitas terbaru yang bisa Anda kelola.') }}</p></div><section class="admin-stat-grid" aria-label="{{ __('Ringkasan data') }}">@foreach($stats as $label=>$value)<div class="admin-stat"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>@endforeach</section>
<div class="admin-two-columns"><section class="admin-panel"><div class="admin-section-head"><h2>{{ __('Aktivitas terbaru') }}</h2>@if($permissions->can($viewer,'audit.view',$viewer->school))<a href="{{ route('admin.audit') }}">{{ __('Lihat log audit') }}</a>@endif</div>@include('admin.log-rows',['logs'=>$logs])</section>
@if(!empty($recentSuggestions)&&$recentSuggestions->count())
<section class="admin-panel"><div class="admin-section-head"><h2>{{ __('Usulan terbaru') }}</h2><a href="{{ route('admin.suggestions.index') }}">{{ __('Lihat semua') }}</a></div>
<div class="admin-table-wrap"><table class="admin-table"><tbody>
@foreach($recentSuggestions as $s)<tr><td><a href="{{ route('admin.suggestions.show',$s) }}"><strong>{{ $s->ticket_number }}</strong></a><br><small>{{ $s->proposed_name }}</small></td><td>{{ \App\Models\InstitutionSuggestion::statuses()[$s->status]??$s->status }}</td><td><small>{{ $s->created_at->format('d M H:i') }}</small></td></tr>
@endforeach
</tbody></table></div></section>
@endif<section class="admin-panel admin-guidance"><span class="admin-eyebrow">{{ __('IZIN & FITUR') }}</span><h2>{{ __('Dua pengaturan yang berbeda') }}</h2><p>{{ __('Izin mengatur tindakan apa yang boleh dilakukan setiap akun. Fitur mengatur layanan apa yang tersedia di institusi.') }}</p><a class="admin-secondary" href="{{ route('admin.roles') }}">{{ __('Atur tingkatan dan izin') }}</a><hr><strong>{{ __('Tindakan penting butuh konfirmasi ulang') }}</strong><p>{{ __('Konfirmasi kata sandi berlaku 10 menit. Anda tidak bisa masuk sebagai pengguna lain.') }}</p></section></div>
@endsection
