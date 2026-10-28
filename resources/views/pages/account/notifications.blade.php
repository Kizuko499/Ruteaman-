@extends('pages.account.layout')
@section('title',__('Notifikasi'))
@section('account-heading',__('Notifikasi'))
@section('account-description',__('Pembaruan laporanmu di aplikasi. Tidak ada notifikasi push.'))
@section('account-content')
<x-account-tabs/><div role="tabpanel" id="account-tab-panel" aria-labelledby="notifications-tab">
<form method="post" action="{{ route('account.notifications.seen') }}" data-account-form data-mark-seen class="notification-toolbar">@csrf<button class="btn btn-soft">{{ __('Tandai semua sudah dibaca') }}</button><p data-form-status role="status"></p></form>
<div class="notification-list">@forelse($items as $item)<article class="settings-card notification-item {{ $item['unread']?'is-unread':'' }}"><span class="menu-icon"><x-account-icon name="bell"/></span><div><h2>{{ __($item['label']) }}</h2><p>{{ $item['hazard'] }}</p>@if($item['note'])<p>{{ __('Alasan:') }} {{ $item['note'] }}</p>@endif<p class="field-help">{{ $item['created_at']->format('d/m/Y H:i') }} <span class="unread-label">{{ $item['unread']?__('· Baru'):'' }}</span></p>@if($item['url'])<a class="text-link" href="{{ $item['url'] }}">{{ __('Lihat laporan') }}</a>@endif</div></article>@empty<div class="settings-card account-empty"><x-account-icon name="bell"/><h2>{{ __('Belum ada pembaruan.') }}</h2><p>{{ __('Konfirmasi komunitas dan verifikasi pada laporanmu akan muncul di sini.') }}</p></div>@endforelse</div>{{ $items->links() }}
</div>
@endsection
