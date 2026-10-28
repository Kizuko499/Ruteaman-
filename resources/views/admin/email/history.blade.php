@extends('admin.layout')
@section('title',__('Riwayat Pengiriman Email'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Riwayat Pengiriman') }}</h1><p>{{ __('Setiap catatan berasal dari pengiriman aktual. Alamat penerima selalu ditampilkan dalam bentuk tersamar demi keamanan.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'riwayat'])

<form method="get" class="admin-filter">
<label>{{ __('Status') }}<select name="status"><option value="">{{ __('Semua') }}</option>@foreach(\App\Models\EmailDeliveryLog::STATUS_LABELS as $k=>$v)<option value="{{ $k }}" @selected(($filters['status']??'')===$k)>{{ $v }}</option>@endforeach</select></label>
<label>{{ __('Kategori') }}<select name="category"><option value="">{{ __('Semua') }}</option>@foreach($categories as $c)<option value="{{ $c }}" @selected(($filters['category']??'')===$c)>{{ \App\Models\EmailDeliveryLog::categoryLabel($c) }}</option>@endforeach</select></label>
<label>{{ __('Dari') }}<input type="date" name="date_from" value="{{ $filters['date_from']??'' }}"></label>
<label>{{ __('Sampai') }}<input type="date" name="date_to" value="{{ $filters['date_to']??'' }}"></label>
<label>{{ __('Penerima / subjek') }}<input name="q" maxlength="190" value="{{ $filters['q']??'' }}" placeholder="{{ __('s***@contoh.com atau kata subjek') }}"></label>
<button class="admin-secondary" type="submit">{{ __('Saring') }}</button>
@if(app(\App\Services\PermissionService::class)->can(auth()->user(),'email.manage'))<a class="admin-secondary" href="{{ route('admin.email.export', $filters) }}">{{ __('Ekspor CSV') }}</a>@endif
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>{{ __('Waktu') }}</th><th>{{ __('Kategori') }}</th><th>{{ __('Penerima') }}</th><th>{{ __('Subjek') }}</th><th>{{ __('Status') }}</th><th>{{ __('Percobaan') }}</th><th></th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr>
<td>{{ $log->created_at?->format('d M Y H:i') }}</td>
<td>{{ $log->categoryText() }}</td>
<td>{{ $log->recipient_masked }}@if($log->notificationRecipient)<br><small>{{ $log->notificationRecipient->name }}</small>@endif</td>
<td>{{ $log->subject }}@if($log->error)<br><small>{{ $log->error }}</small>@endif</td>
<td>{{ $log->statusText() }}</td>
<td>{{ $log->attempts }}</td>
<td><div class="admin-actions">
<a class="admin-secondary" href="{{ route('admin.email.show', $log) }}">{{ __('Detail') }}</a>
@if($log->status==='failed' && $log->payload)<form method="post" action="{{ route('admin.email.retry', $log) }}">@csrf<button class="admin-secondary" type="submit">{{ __('Kirim ulang') }}</button></form>@endif
@if($log->status==='queued' && app(\App\Services\PermissionService::class)->can(auth()->user(),'email.manage'))<form method="post" action="{{ route('admin.email.cancel', $log) }}" data-confirm="{{ __('Batalkan pengiriman ini?') }}">@csrf<button class="admin-secondary" type="submit">{{ __('Batalkan') }}</button></form>@endif
</div></td>
</tr>
@empty
<tr><td colspan="7">{{ __('Belum ada catatan pengiriman yang cocok dengan saringan.') }}</td></tr>
@endforelse
</tbody></table></div>
{{ $logs->links() }}
@endsection
