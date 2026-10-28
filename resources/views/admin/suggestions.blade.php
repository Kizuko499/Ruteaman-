@extends('admin.layout')
@section('title', __('Usulan Institusi'))
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
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">{{ __('MANAJEMEN DIREKTORI') }}</p>
        <h1>{{ __('Usulan Institusi') }}</h1>
        <p>{{ __('Tinjau usulan institusi baru dan laporan kesalahan data.') }}
        @if($newCount)<strong>{{ $newCount }} baru</strong>@endif</p>
    </div>
    <a href="{{ route('admin.suggestions.export') }}" class="admin-secondary">{{ __('Ekspor CSV') }}</a>
</div>

<form method="get" class="admin-filter">
    <label>{{ __('Cari') }}
        <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ __('Kode tiket / nama / NPSN') }}">
    </label>
    <label>{{ __('Kategori') }}
        <select name="category">
            <option value="">{{ __('Semua') }}</option>
            @foreach(\App\Models\InstitutionSuggestion::categories() as $k => $l)
                <option value="{{ $k }}" @selected(request('category') === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ __('Status') }}
        <select name="status">
            <option value="">{{ __('Semua') }}</option>
            @foreach(\App\Models\InstitutionSuggestion::statuses() as $k => $l)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </label>
    <button class="admin-secondary">{{ __('Terapkan') }}</button>
</form>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('Kode tiket') }}</th>
                    <th>{{ __('Kategori') }}</th>
                    <th>{{ __('Institusi') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Tanggal') }}</th>
                    <th>{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suggestions as $s)
                <tr>
                    <td data-label="Kode tiket">
                        <a href="{{ route('admin.suggestions.show', $s) }}"><strong>{{ $s->ticket_number }}</strong></a>
                    </td>
                    <td data-label="Kategori">{{ \App\Models\InstitutionSuggestion::categories()[$s->category] ?? $s->category }}</td>
                    <td data-label="Institusi">
                        <strong>{{ $s->proposed_name }}</strong>
                        @if($s->npsn)<small>NPSN {{ $s->npsn }}</small>@endif
                        @if($s->description)<small>{{ \Str::limit($s->description, 80) }}</small>@endif
                    </td>
                    <td data-label="Status">{{ \App\Models\InstitutionSuggestion::statuses()[$s->status] ?? $s->status }}</td>
                    <td data-label="Tanggal">{{ $s->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td data-label="Aksi">
                        @if($s->category === 'new_school' && in_array($s->status, ['new', 'reviewing']))
                        <form method="post" action="{{ route('admin.suggestions.approve', $s) }}" style="margin-bottom:4px">
                            @csrf
                            <button class="admin-primary" style="font-size:12px;padding:4px 8px">{{ __('Setujui & buatkan institusi') }}</button>
                        </form>
                        @endif
                        <form method="post" action="{{ route('admin.suggestions.update', $s) }}" style="display:flex;gap:4px">
                            @csrf
                            @method('PATCH')
                            <select name="status" style="font-size:12px;padding:4px">
                                @foreach(\App\Models\InstitutionSuggestion::statuses() as $k => $l)
                                    <option value="{{ $k }}" @selected($s->status === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                            <button class="admin-secondary" style="font-size:12px;padding:4px 8px">{{ __('OK') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="admin-empty">{{ __('Belum ada usulan.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $suggestions->links() }}
</section>
@endsection
