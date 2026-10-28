<a class="admin-secondary" href="{{ route('admin.index') }}" data-admin-back aria-label="{{ __('Kembali ke halaman admin sebelumnya') }}" style="display:inline-flex;align-items:center;gap:8px;min-height:44px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h15" /></svg>
    {{ __('Kembali') }}
</a>
@once
<script src="{{ asset('js/admin-back.js') }}?v=languages-1" defer></script>
@endonce
