@extends('layouts.app')
@section('title',__('Kondisi tidak ditemukan'))
@section('content')<div class="conditions-page"><div class="condition-empty"><p class="eyebrow">{{ __('404 · Informasi tidak tersedia') }}</p><h1>{{ __('Kondisi ini tidak dapat dibuka.') }}</h1><p>{{ __('Tautan mungkin tidak tersedia atau laporan belum dapat ditampilkan kepada publik.') }}</p><a class="btn btn-dark" href="{{ route('conditions.index') }}">{{ __('Lihat semua kondisi') }}</a><a class="condition-back" href="{{ route('home') }}">{{ __('Kembali ke beranda') }}</a></div></div>@endsection
