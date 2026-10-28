@extends('admin.layout')
@section('title',__('Metrik Sekolah'))
@section('content')
<div class="admin-page-head"><h1>{{ __('Metrik ·') }} {{ $school->name }}</h1><p>{{ __('Kondisi yang pertama dilaporkan per hari, 7 hari terakhir. Zona waktu') }} {{ $school->timezone }}{{ __('. Tidak merepresentasikan seluruh kondisi jalan.') }}</p></div><section class="admin-panel"><table class="admin-table"><thead><tr><th>{{ __('Tanggal') }}</th><th>{{ __('Kondisi pertama dilaporkan') }}</th></tr></thead><tbody>@foreach($days as $day)<tr><td data-label="Tanggal">{{ $day['date'] }}</td><td data-label="Kondisi">{{ $day['first_reports'] }}</td></tr>@endforeach</tbody></table></section>
@endsection
