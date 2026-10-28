<div data-school-picker data-search-url="{{ route('admin.school-picker.search') }}" data-register-url="{{ route('admin.school-picker.register') }}" style="min-width:0">
    <label>{{ __('Institusi terpilih') }}
        <select name="school_id" data-school-value>
            <option value="">{{ __('Tanpa institusi (Admin Utama)') }}</option>
            @foreach($schools as $schoolOption)
                <option value="{{ $schoolOption->id }}" @selected((string)($selectedSchoolId ?? '') === (string)$schoolOption->id)>{{ $schoolOption->name }}{{ $schoolOption->npsn ? ' · '.$schoolOption->npsn : '' }}</option>
            @endforeach
        </select>
    </label>
    <label style="margin-top:12px">{{ __('Cari institusi lain berdasarkan nama atau NPSN') }}
        <input type="search" data-school-query maxlength="80" placeholder="{{ __('Contoh: nama institusi atau NPSN') }}" autocomplete="off">
    </label>
    <button type="button" class="admin-secondary" data-school-search style="margin-top:8px">{{ __('Cari institusi') }}</button>
    <small>{{ __('Minimal 3 karakter. Mencari institusi terdaftar dan direktori nasional. Hasil belum terdaftar dapat ditambahkan oleh Admin Utama.') }}</small>
    <p data-school-message role="status" aria-live="polite" style="overflow-wrap:anywhere"></p>
    <div data-school-results style="display:grid;gap:8px"></div>
    <small>{{ __('Penambahan institusi tidak otomatis membuat akun atau mengaktifkan pelaporan. Tinjau pin/wilayah pantauan dan fitur di menu Institusi.') }}</small>
</div>
@once
<script src="{{ asset('js/admin-school-picker.js') }}" defer></script>
@endonce
