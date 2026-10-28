@extends('layouts.app')
@section('title',$hazard->type->label)
@section('content')
<div class="detail-page">@include('partials.nav')
 @if($errors->any())<p class="error">{{ __($errors->first()) }}</p>@endif
 @if(session('duplicate'))<p class="duplicate-panel" role="status">{{ session('duplicate') }}</p>@endif
 @if($hazard->is_demo)<p class="disclaimer">{{ __('SIMULASI DEMO — lokasi, foto ilustrasi, dan bukti seed bukan hasil audit/pilot nyata.') }}</p>@endif
 <article class="public-hazard">
 @php($photo=$hazard->reports->first(fn($r)=>$r->photo_path))
 @if($photo)<img src="{{ route('conditions.media',['hazard'=>$hazard,'index'=>0]) }}" alt="Foto {{ strtolower($hazard->type->label) }}">@else<div class="hazard-placeholder">!</div>@endif
 <div><p class="eyebrow">{{ __('Hazard wilayah · tanpa identitas pelapor') }}</p><h1>{{ $hazard->type->label }}</h1>
 <p>{{ $hazard->status_label }} · {{ ($hazard->last_confirmed_at??$hazard->first_reported_at)->diffForHumans() }}</p>
 <p>{{ __('Koordinat:') }} {{ number_format($hazard->latitude,6) }}, {{ number_format($hazard->longitude,6) }}</p>
 <p>{{ __('Layer bersama:') }} {{ $schools->join(' · ') }}</p>
 <div class="stats"><span><b>{{ $input['reports'] }}</b> {{ __('laporan independen') }}</span><span><b>{{ $input['confirmations'] }}</b> {{ __('konfirmasi masih ada') }}</span><span><b>{{ $hazard->severity>=3?'Tinggi':'Sedang' }}</b> {{ __('tingkat keparahan') }}</span></div>
 <p class="disclaimer">{{ __('Belum ada laporan aktif bukan jaminan kondisi aman. Beban adalah ringkasan heuristik, bukan prediksi kondisi jalan.') }}</p>
 @auth @if($active)<form class="confirm-row" method="post" action="{{ route('hazards.confirm',$hazard) }}">@csrf<button class="btn btn-dark" name="vote" value="still_there">{{ __('◎ Masih ada') }}</button><button class="btn btn-soft" name="vote" value="gone">{{ __('○ Sudah tidak ada') }}</button></form>@endif @else<a class="btn btn-dark" href="{{ route('login') }}">{{ __('Masuk untuk mengonfirmasi') }}</a>@endauth
 </div></article>
 <section class="hazard-explanation liquid-glass" data-glass-variant="card"><details open><summary>{{ __('Kenapa tingkatnya begini?') }}</summary>
 <p><b>{{ __('Beban titik ini:') }} {{ $load['value']??'—' }} · {{ $load['label'] }}</b>{{ __('. Beban rute merupakan jumlah kontribusi semua hazard aktif di sepanjang rute, bukan angka titik ini saja.') }}</p>
 @php($d=$load['details'][0]??null)
 @if($d)<div class="table-scroll"><table><thead><tr><th>{{ __('Bobot') }}</th><th>{{ __('Keyakinan c') }}</th><th>{{ __('Kesegaran f') }}</th><th>{{ __('Cuaca w') }}</th><th>{{ __('Kontribusi h') }}</th></tr></thead><tbody><tr><td>{{ $d['severity'] }}</td><td>{{ number_format($d['c'],2) }}</td><td>{{ number_format($d['f'],2) }}</td><td>{{ $d['w'] }}</td><td>{{ number_format($d['h'],2) }}</td></tr></tbody></table></div>@endif
 <p>{{ __('c = 0,10 × laporan (maks. 3) + 0,10 × konfirmasi (maks. 3) + 0,20 jika ada foto + 0,40 jika terverifikasi (maksimum 1).') }}</p>
 <p>{{ $input['weather_label'] }}{{ __('. Data berumur') }} {{ $input['age_minutes'] }} {{ __('menit; TTL jenis') }} {{ $input['ttl_minutes'] }} {{ __('menit. h = bobot × c × f × w; Beban = pembulatan min(100, 100 × Σh /') }} {{ config('ruteaman.hazard_saturation') }}).</p>
 <p>{{ __('Menunggu konfirmasi tidak dimasukkan ke Beban rute. Tiga pengguna independen (pelapor + pengonfirmasi) cukup untuk konfirmasi komunitas, tanpa menunggu Admin.') }}</p>
 </details></section>
 <section class="hazard-explanation"><h2>{{ __('Riwayat verifikasi') }}</h2>@forelse($hazard->verifications as $v)<p>{{ $v->created_at->format('d/m/Y H:i') }} · {{ match($v->decision){'verified'=>__('Terverifikasi'),'rejected'=>'Ditolak',default=>__('Perlu bukti')} }} {{ __('· Admin Sekolah') }}</p>@empty<p>{{ __('Belum ada verifikasi Admin Sekolah.') }}</p>@endforelse
 <a class="text-link" href="{{ route('map') }}">{{ __('Lihat Beban seluruh rute di peta →') }}</a></section>
</div>
@endsection
