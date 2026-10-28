@if($target && $viewer?->role === \App\Enums\Role::Owner && $target->id !== $viewer->id)
<div class="admin-actions" style="margin:0 0 24px">
    <button type="button" class="admin-danger" data-user-delete-open aria-haspopup="dialog" aria-controls="owner-delete-dialog">{{ __('Hapus akun') }}</button>
</div>
<dialog id="owner-delete-dialog" aria-labelledby="owner-delete-title" aria-describedby="owner-delete-description" data-delete-reopen="{{ $errors->has('delete_token') || $errors->has('confirmed') ? '1' : '0' }}" style="box-sizing:border-box;width:min(540px,calc(100vw - 24px));max-height:calc(100dvh - 24px);overflow:auto;padding:20px;overflow-wrap:anywhere">
    <div class="admin-dialog-head"><h2 id="owner-delete-title">{{ __('Hapus akun ini?') }}</h2><button type="button" data-user-delete-close aria-label="{{ __('Tutup peringatan') }}">×</button></div>
    <p><strong>{{ $target->display_name }}</strong><br>{{ $target->email }}</p>
    <p id="owner-delete-description">{{ __('Akun ini tidak dapat digunakan untuk masuk lagi. Identitas akan dianonimkan dan semua sesinya dicabut. Laporan serta histori audit tetap dipertahankan.') }}</p>
    <p>{{ __('Pastikan akun yang ditampilkan di atas adalah akun yang ingin Anda hapus.') }}</p>
    @error('delete_token')<p class="admin-warning" role="alert">{{ $message }}</p>@enderror
    @error('confirmed')<p class="admin-warning" role="alert">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('admin.users.quick-destroy',$target) }}" data-user-delete-form>
        @csrf
        @method('DELETE')
        <input type="hidden" name="delete_token" value="{{ $deleteToken ?? '' }}">
        <div class="admin-actions" style="flex-wrap:wrap;margin-top:16px">
            <button type="button" class="admin-secondary" data-user-delete-close data-user-delete-focus>{{ __('Batalkan') }}</button>
            <button type="submit" class="admin-danger" name="confirmed" value="1">{{ __('Lanjutkan') }}</button>
        </div>
    </form>
</dialog>
<script src="{{ asset('js/admin-user-delete.js') }}?v=languages-1" defer></script>
@endif
