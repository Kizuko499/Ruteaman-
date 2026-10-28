<form method="get" action="{{ route('admin.hazards') }}" class="admin-panel">
    <div @if($viewer->role===\App\Enums\Role::Owner) data-school-picker data-picker-context="reports" data-search-url="{{ route('admin.school-picker.search') }}" data-register-url="{{ route('admin.school-picker.register') }}" @endif style="min-width:0">
        <label>{{ __('Institusi terdaftar di RuteAman') }}
            <select name="school" data-school-value>
                @foreach($schools as $s)
                    <option value="{{ $s->id }}" @selected($s->id===$school->id)>{{ $s->name }}{{ $s->npsn?' · '.$s->npsn:'' }}</option>
                @endforeach
            </select>
        </label>
        <button class="admin-secondary" type="submit" style="margin-top:8px">{{ __('Tampilkan laporan') }}</button>
        @if($viewer->role===\App\Enums\Role::Owner)
            <p>{{ __('Daftar cepat di atas hanya berisi institusi yang sudah terdaftar di RuteAman. Untuk institusi lain, cari di direktori nasional berikut.') }}</p>
            <label>{{ __('Cari semua institusi berdasarkan nama atau NPSN') }}
                <input type="search" data-school-query maxlength="80" placeholder="{{ __('Contoh: MAN 1 REMBANG, SMPN 1 Surabaya, atau NPSN') }}" autocomplete="off">
            </label>
            <button type="button" class="admin-secondary" data-school-search style="margin-top:8px">{{ __('Cari di direktori online') }}</button>
            <small>{{ __('Ketik minimal 3 karakter. Pencarian mencakup semua institusi di direktori, tidak hanya yang sudah terdaftar.') }}</small>
            <p data-school-message role="status" aria-live="polite" style="overflow-wrap:anywhere"></p>
            <div data-school-results style="display:grid;gap:8px"></div>
            <small>{{ __('Institusi dari direktori yang belum terdaftar bisa didaftarkan dan ditampilkan. Pendaftaran tidak membuat akun atau laporan otomatis; pemantauan dijeda dan pelaporan nonaktif sampai lokasi, wilayah cakupan, serta fitur ditinjau Admin Utama.') }}</small>
        @else
            <small>{{ __('Daftar dibatasi institusi dalam cakupan izin akun. Admin Utama dapat mencari dan mendaftarkan institusi lain dari direktori nasional.') }}</small>
        @endif
    </div>
</form>
@if($viewer->role===\App\Enums\Role::Owner)
@once
<script src="{{ asset('js/admin-school-picker.js') }}?v=languages-1" defer></script>
@endonce
@endif
