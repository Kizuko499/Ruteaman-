@extends('layouts.app')
@section('title','Laporan tamu diterima')
@section('content')
<div class="form-page guest-report-page"><p class="eyebrow">Laporan tamu</p>
 @if($receipt['duplicate'])<h1>Kondisi serupa sudah tercatat</h1><p>Laporan baru tidak dibuat karena ada kondisi serupa di dekat titik yang dipilih. Nama dan foto pengiriman ini tidak disimpan. Tidak ada konfirmasi tambahan yang dihitung.</p>
 @else<h1>Laporan diterima</h1><p>Laporanmu masuk antrean pengelola <strong>{{ $receipt['school'] }}</strong> untuk diperiksa. Laporan belum ditampilkan di peta publik sampai diverifikasi.</p>@endif
 <p>Nama tamu tidak dipublikasikan. Nama dan pilihan sekolah bukan identitas terverifikasi. Konfirmasi kondisi tetap membutuhkan akun.</p>
 <a class="btn btn-dark" href="{{ route('map') }}">Kembali ke peta</a><a class="text-link" href="{{ route('guest-reports.create') }}">Buat laporan lain</a>
</div>
@endsection
