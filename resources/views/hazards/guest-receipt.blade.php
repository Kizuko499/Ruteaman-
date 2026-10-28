@extends('layouts.app')
@section('title',__('Laporan tamu diterima'))
@section('content')
<div class="form-page guest-report-page"><p class="eyebrow">{{ __('Laporan tamu') }}</p>
 @if($receipt['duplicate'])<h1>{{ __('Kondisi serupa sudah tercatat') }}</h1><p>{{ __('Laporan baru tidak dibuat karena ada kondisi serupa di dekat titik yang dipilih. Nama dan foto pengiriman ini tidak disimpan. Tidak ada konfirmasi tambahan yang dihitung.') }}</p>
 @else<h1>{{ __('Laporan diterima') }}</h1>@if($receipt['public']??false)<p>{{ __('Laporan langsung tampil di peta sebagai') }} <strong>{{ __('belum terverifikasi') }}</strong>{{ __('. Pengelola dapat menangguhkan atau menarik laporan yang keliru. Tidak ada akun atau menu pelacakan pribadi yang dibuat.') }}</p>@else<p>{{ __('Laporanmu masuk antrean pengelola') }} <strong>{{ $receipt['school'] }}</strong> {{ __('untuk diperiksa. Laporan belum ditampilkan di peta publik sampai diverifikasi.') }}</p>@endif @endif
 <p>{{ __('Nama tamu tidak dipublikasikan. Nama dan pilihan sekolah bukan identitas terverifikasi. Konfirmasi kondisi tetap membutuhkan akun.') }}</p>
 <a class="btn btn-dark" href="{{ route('map') }}">{{ __('Kembali ke peta') }}</a><a class="text-link" href="{{ route('guest-reports.create') }}">{{ __('Buat laporan lain') }}</a>
</div>
@endsection
