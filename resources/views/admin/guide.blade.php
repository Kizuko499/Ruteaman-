@extends('admin.layout')
@section('title',__('Panduan Administrator'))
@section('content')
<style data-ruteaman-admin-guide="v1">
/* Panduan Administrator: daftar isi dan bab sebagai kartu bacaan yang
   tenang dan lapang. Semua bab, langkah, contoh, dan gambar dipertahankan. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:78ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-notice{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:16px;padding:14px 16px;font-size:13.5px;line-height:1.6;color:#24484B;margin-bottom:16px}
.admin-guide-index{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px;margin-bottom:18px}
.admin-guide-index h2{font-size:19px;font-weight:600;letter-spacing:-.01em;margin:0 0 12px}
.admin-guide-index ol{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:4px 24px}
.admin-guide-index li a{display:block;padding:9px 12px;border-radius:10px;color:#1F6E70;font-weight:500;font-size:14px;text-decoration:none;transition:background .15s ease}
.admin-guide-index li a:hover{background:#F2F8F4}
.admin-guide-chapter{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:26px;margin-bottom:18px}
.admin-guide-chapter h2{font-size:21px;font-weight:600;letter-spacing:-.01em;margin:2px 0 14px}
.admin-guide-body{font-size:15px;line-height:1.75;color:#24484B;max-width:78ch}
.admin-guide-body ol{margin:0;padding-left:22px}
.admin-guide-body li{margin-bottom:10px}
.admin-guide-body li::marker{color:#359FA0;font-weight:700}
.admin-guide-example h3{font-size:16px;font-weight:600;margin:18px 0 6px}
.admin-guide-example p{color:#5F7A76;font-size:13.5px;line-height:1.6}
.admin-guide-note{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:14px;padding:14px 16px;font-size:13.5px;line-height:1.65;color:#24484B;margin:16px 0}
.admin-guide-chapter .admin-secondary{background:#fff;border:1.5px solid #DFE9E4;border-radius:12px;min-height:40px;padding:0 14px;font-size:13px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center}
.admin-guide-chapter figure{margin:18px 0 0}
.admin-guide-chapter figure img{border:1px solid #E3EFE9;border-radius:16px;box-shadow:0 8px 22px rgba(18,56,58,.08);max-width:100%;height:auto}
.admin-guide-chapter figcaption{font-size:12.5px;color:#7A918B;text-align:center;margin-top:8px}
.admin-topline .admin-secondary{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;min-height:38px;padding:0 14px;font-size:13px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
@media(max-width:760px){.admin-guide-index ol{grid-template-columns:1fr}.admin-guide-chapter{padding:20px}}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('PETUNJUK KONSOL') }}</p><h1>{{ __('Panduan Administrator') }}</h1><p>{{ __('Panduan mengelola institusi dan akun, pin dan GeoJSON, fitur, laporan, serta pengaturan. Bagian yang tampil mengikuti izin akunmu; menu samping tetap tersedia.') }}</p></div>
<div class="admin-notice"><strong>{{ __('Gambar contoh memakai data simulasi.') }}</strong> {{ __('Nama, kontak, foto, dan koordinat contoh bukan data pengguna nyata. Gambar dapat menggambarkan tampilan sebelumnya; ikuti langkah tertulis dan data resmi institusi. Panduan ini tidak mengubah izin akun.') }}</div>
<nav class="admin-panel admin-guide-index" aria-label="{{ __('Daftar isi panduan administrator') }}"><h2>{{ __('Pilih bagian yang dibutuhkan') }}</h2><ol>@foreach($sections as $section)<li><a href="#{{ $section['id'] }}">{{ $section['title'] }}</a></li>@endforeach</ol></nav>
@foreach($sections as $section)<section class="admin-panel admin-guide-chapter" id="{{ $section['id'] }}"><p class="admin-eyebrow">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }} {{ __('/ PANDUAN') }}</p><h2>{{ $section['title'] }}</h2><div class="admin-guide-body"><div style="min-width:0"><ol>@foreach($section['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
@foreach(($section['examples'] ?? []) as $example)
<div class="admin-guide-example" style="min-width:0;max-width:100%"><h3>{{ $example['title'] }}</h3><p>{{ $example['caption'] }}</p><pre tabindex="0" role="region" aria-label="{{ $example['title'] }}" style="max-width:100%;overflow:auto;white-space:pre;padding:16px;border:1px solid #bdd9d4;border-radius:12px;background:#f2f8f6;color:#123e40;font-size:14px;line-height:1.6"><code>{{ $example['code'] }}</code></pre></div>
@endforeach
<aside class="admin-guide-note">{{ $section['note'] }}</aside><a class="admin-secondary" href="#main">{{ __('Kembali ke daftar isi') }}</a></div>@if($section['image']&&is_file(public_path('images/admin-guide/'.$section['image'])))<figure><a href="{{ asset('images/admin-guide/'.$section['image']) }}" target="_blank" rel="noopener" aria-label="Perbesar screenshot {{ $section['title'] }}"><img src="{{ asset('images/admin-guide/'.$section['image']) }}" alt="Tampilan simulasi: {{ $section['title'] }}" width="780" height="1688" loading="lazy"></a><figcaption>{{ __('Contoh demo. Ketuk gambar untuk memperbesar.') }}</figcaption></figure>@endif</div></section>@endforeach
@endsection
