@extends('admin.layout')
@section('title',__('Detail Usulan'))
@section('content')
<style data-ruteaman-admin-suggestions="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-page-head p strong{background:#EAF4EE;color:#1F6E70;border-radius:999px;padding:2px 10px;font-size:12px}
.admin-main .admin-page-head a.admin-secondary{border:1px solid #CBDAD5;background:#fff;border-radius:12px;min-height:44px;padding:10px 18px;font-weight:600;color:#1F4A4C;text-decoration:none;display:inline-flex;align-items:center}
.admin-main .admin-page-head a.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-filter{background:#fff;border:1px solid #E3EFE9;border-radius:18px;padding:16px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);display:grid;grid-template-columns:minmax(0,1fr) 230px 230px auto;gap:12px;align-items:end}
.admin-main .admin-filter label{font-size:12.5px;font-weight:600;color:#33554F;display:grid;gap:6px}
.admin-main .admin-filter input,.admin-main .admin-filter select{height:46px;border:1px solid #CBDAD5;border-radius:12px;padding:0 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-filter input:focus,.admin-main .admin-filter select:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-filter .admin-secondary{height:46px;border-radius:12px;padding:0 20px;background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .admin-table th{background:#F7FAF7;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;color:#51706B;padding:13px 16px}
.admin-main .admin-table td{padding:13px 16px;border-bottom:1px solid #EDF3EF}
.admin-main .admin-table tbody tr:last-child td{border-bottom:0}
.admin-main .admin-table td small{color:#6B837C}
.admin-main .admin-table td a{color:#1F6E70;text-decoration:none}
.admin-main .admin-table td a:hover{text-decoration:underline}
.admin-main .admin-table td form select{border:1px solid #CBDAD5;border-radius:10px;min-height:38px;padding:6px 8px;background:#fff;font-size:12.5px!important}
.admin-main .admin-table td form button{border-radius:10px;min-height:38px;padding:6px 14px;font-weight:600;cursor:pointer;font-size:12.5px!important}
.admin-main .admin-table td form button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;box-shadow:0 6px 14px rgba(31,110,112,.22)}
.admin-main .admin-table td form button.admin-secondary{background:#fff;border:1px solid #CBDAD5;color:#1F4A4C}
.admin-main .admin-panel>table.admin-table th{text-transform:none;letter-spacing:0;font-size:13px;color:#33554F;background:#F7FAF7}
.admin-main .admin-panel label{font-size:13px;font-weight:600;color:#33554F}
.admin-main .admin-panel form select,.admin-main .admin-panel form input[name=review_notes]{border:1px solid #CBDAD5;border-radius:12px;min-height:46px;padding:10px 12px;background:#fff;box-sizing:border-box}
.admin-main .admin-panel form select:focus,.admin-main .admin-panel form input[name=review_notes]:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-panel form button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel form button.admin-secondary{background:#fff;border:1px solid #CBDAD5;color:#1F4A4C;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;cursor:pointer}
.admin-main .admin-panel form button.admin-secondary:hover{background:#F2F8F4}
.admin-main #suggestion-map{border:1px solid #E3EFE9;border-radius:16px}
.admin-main .admin-notice{border-radius:14px}
@media (max-width:900px){.admin-main .admin-filter{grid-template-columns:1fr 1fr}}
@media (max-width:560px){.admin-main .admin-filter{grid-template-columns:1fr}}
</style>
<div class="admin-page-head"><div><p class="admin-eyebrow">{{ __('USULAN INSTITUSI') }}</p><h1>{{ $suggestion->ticket_number }}</h1><p>{{ \App\Models\InstitutionSuggestion::categories()[$suggestion->category] ?? $suggestion->category }} · {{ $suggestion->created_at->translatedFormat('d M Y H:i') }}</p></div><a href="{{ route('admin.suggestions.index') }}" class="admin-secondary">{{ __('Kembali') }}</a></div>

@if(session('success'))<p class="admin-notice">{{ session('success') }}</p>@endif
@if(session('error'))<p class="admin-warning">{{ session('error') }}</p>@endif

<section class="admin-panel">
<h2>{{ __('Data Usulan') }}</h2>
<table class="admin-table"><tbody>
<tr><th style="width:200px">{{ __('Nama institusi') }}</th><td><strong>{{ $suggestion->proposed_name }}</strong></td></tr>
<tr><th>NPSN</th><td>{{ $suggestion->npsn ?: '-' }}</td></tr>
<tr><th>{{ __('Provinsi') }}</th><td>{{ $suggestion->province ?: '-' }}</td></tr>
<tr><th>{{ __('Kabupaten/Kota') }}</th><td>{{ $suggestion->regency ?: '-' }}</td></tr>
<tr><th>{{ __('Alamat') }}</th><td>{{ $suggestion->address ?: '-' }}</td></tr>
<tr><th>{{ __('Koordinat') }}</th><td>@if($suggestion->latitude){{ $suggestion->latitude }}, {{ $suggestion->longitude }} @else - @endif</td></tr>
<tr><th>{{ __('Deskripsi') }}</th><td>{{ $suggestion->description ?: '-' }}</td></tr>
<tr><th>{{ __('Sumber') }}</th><td>@if($suggestion->source_url)<a href="{{ $suggestion->source_url }}" target="_blank">{{ $suggestion->source_url }}</a>@else - @endif</td></tr>
<tr><th>{{ __('Kontak') }}</th><td>{{ $suggestion->contact_email ?: '-' }}</td></tr>
<tr><th>{{ __('Status') }}</th><td><strong>{{ \App\Models\InstitutionSuggestion::statuses()[$suggestion->status] }}</strong></td></tr>
@if($suggestion->reviewer)<tr><th>{{ __('Ditinjau oleh') }}</th><td>{{ $suggestion->reviewer->display_name }} ({{ $suggestion->reviewed_at?->translatedFormat('d M Y H:i') }})</td></tr>@endif
@if($suggestion->review_notes)<tr><th>{{ __('Catatan') }}</th><td>{{ $suggestion->review_notes }}</td></tr>@endif
</tbody></table>
</section>

@if($suggestion->latitude && $suggestion->longitude)
<section class="admin-panel">
<h2>{{ __('Peta Lokasi Usulan') }}</h2>
<div id="suggestion-map" style="height:300px;border-radius:8px"></div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
 var map = L.map('suggestion-map').setView([{{ $suggestion->latitude }}, {{ $suggestion->longitude }}], 15);
 L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(map);
 L.marker([{{ $suggestion->latitude }}, {{ $suggestion->longitude }}]).addTo(map).bindPopup(@json($suggestion->proposed_name)).openPopup();
})();
</script>
</section>
@endif

<section class="admin-panel">
<h2>{{ __('Tindakan') }}</h2>
<div style="display:flex;gap:8px;flex-wrap:wrap">
@if($suggestion->category==='new_school'&&in_array($suggestion->status,['new','reviewing']))
<form method="post" action="{{ route('admin.suggestions.approve', $suggestion) }}">@csrf<button class="admin-primary">{{ __('Setujui & buatkan institusi') }}</button></form>
@endif
<form method="post" action="{{ route('admin.suggestions.update', $suggestion) }}" style="display:flex;gap:8px;align-items:end">@csrf @method('PATCH')
<label>{{ __('Ubah status') }}<select name="status">@foreach(\App\Models\InstitutionSuggestion::statuses() as $k=>$l)<option value="{{ $k }}" @selected($suggestion->status===$k)>{{ $l }}</option>@endforeach</select></label>
<label>{{ __('Catatan') }}<input name="review_notes" value="{{ $suggestion->review_notes }}" maxlength="2000" style="width:200px"></label>
<button class="admin-secondary">{{ __('Simpan') }}</button>
</form>
</div>
</section>
@endsection
