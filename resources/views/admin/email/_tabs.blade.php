@php($viewer=auth()->user())
@php($perms=app(\App\Services\PermissionService::class))
<style data-ruteaman-admin-email="v1">
/* Email & Notifikasi: satu bahasa visual untuk semua tab — kartu tenang,
   tab berbentuk pil, formulir modern. Dimuat dari partial tab bersama. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:78ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
nav.admin-actions{display:flex;flex-wrap:wrap;gap:8px}
nav.admin-actions a{border-radius:999px;min-height:40px;padding:0 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;text-decoration:none}
nav.admin-actions a.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;box-shadow:0 8px 18px rgba(31,110,112,.26)}
nav.admin-actions a.admin-secondary{background:#fff;border:1.5px solid #DFE9E4;color:#14343A}
.admin-stat-grid{gap:14px}
.admin-stat{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:18px}
.admin-stat strong{font-size:30px;font-weight:600;letter-spacing:-.01em}
.admin-stat span{font-size:12.5px;font-weight:600;color:#49696B}
.admin-stat small{font-size:12.5px;color:#7A918B}
.admin-main h2{font-size:19px;font-weight:600;letter-spacing:-.01em;color:#12383A}
.admin-table-wrap{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:8px 22px 14px;overflow-x:auto}
.admin-panel .admin-table-wrap{background:transparent;border:0;box-shadow:none;padding:0;border-radius:0}
.admin-table th{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;color:#7A918B;background:#F7FAF8;padding:12px 14px;border-bottom:1px solid #E7F0EA;text-align:left}
.admin-table td{padding:14px;border-top:1px solid #EAF1EC;vertical-align:top;font-size:13.5px}
.admin-table tbody tr{transition:background .15s ease}
.admin-table tbody tr:hover{background:#F8FBF9}
.admin-table td small{color:#7A918B;overflow-wrap:anywhere}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px}
.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;border-radius:12px;min-height:44px;padding:0 18px;font-size:14px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.admin-secondary{background:#fff;border:1.5px solid #DFE9E4;border-radius:12px;min-height:40px;padding:0 14px;font-size:13px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.admin-danger{background:#FDF0EA;border:1.5px solid #F0D5C8;border-radius:12px;min-height:40px;padding:0 14px;font-size:13px;font-weight:600;color:#B4552D;cursor:pointer}
.admin-table .admin-secondary,.admin-table .admin-danger{min-height:36px;padding:0 12px;font-size:12.5px;border-radius:10px}
.admin-filter{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:16px;display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin-bottom:16px}
.admin-filter label{font-size:12.5px;font-weight:600;color:#49696B;display:grid;gap:6px;flex:1 1 150px}
.admin-filter input,.admin-filter select{width:100%;box-sizing:border-box;height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;background:#fff;color:#12383A}
.admin-filter input:focus,.admin-filter select:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-filter>button.admin-secondary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;min-height:46px;box-shadow:0 8px 18px rgba(31,110,112,.26)}
.admin-editor label{font-size:13px;font-weight:600;color:#49696B;display:grid;gap:6px}
.admin-editor label small{font-weight:400;color:#7A918B;font-size:12.5px;line-height:1.5}
.admin-editor input,.admin-editor select,.admin-editor textarea{width:100%;box-sizing:border-box;min-height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:10px 12px;font:inherit;font-size:14px;font-weight:400;background:#fff;color:#12383A}
.admin-editor input:focus,.admin-editor select:focus,.admin-editor textarea:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-editor input[type="checkbox"]{width:18px;height:18px;min-height:0;accent-color:#1F6E70;padding:0}
.admin-editor fieldset.admin-scope{border:1px solid #E3EFE9;border-radius:16px;padding:16px;margin:0 0 14px;background:#FBFDFB}
.admin-editor fieldset.admin-scope legend{font-size:13px;font-weight:700;color:#12383A;padding:0 6px}
.admin-editor fieldset.admin-scope label{display:inline-flex;align-items:center;gap:8px;margin:0 18px 10px 0;font-weight:500;color:#24484B}
.recipient-all-reports{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:16px;padding:16px 18px;margin:6px 0 4px}
.admin-editor .recipient-all-reports>label{display:flex;flex-direction:row;align-items:center;justify-content:flex-start;text-align:left;gap:10px;font-size:14px;color:#12383A}
.admin-sticky-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:18px;margin-top:6px;border-top:1px solid #EAF1EC}
.admin-alert{border-radius:14px;padding:12px 14px;font-size:13.5px;line-height:1.6}
.admin-muted{font-size:13px;color:#7A918B;line-height:1.6}
.admin-panel pre{background:#F7FAF8;border:1px solid #E3EFE9;border-radius:14px;padding:14px 16px;font-size:12.5px;line-height:1.6;overflow-x:auto}
.admin-topline .admin-secondary{border-radius:999px;min-height:38px}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
</style>
<nav class="admin-actions" aria-label="{{ __('Tab email') }}" style="margin-bottom:16px;">
@if($perms->can($viewer,'email.monitor'))<a class="admin-{{ ($tab??'')==='ringkasan'?'primary':'secondary' }}" href="{{ route('admin.email.index') }}">{{ __('Ringkasan') }}</a>@endif
@if($perms->can($viewer,'email.recipients'))<a class="admin-{{ ($tab??'')==='penerima'?'primary':'secondary' }}" href="{{ route('admin.email.recipients.index') }}">{{ __('Penerima Notifikasi') }}</a>@endif
@if($perms->can($viewer,'email.monitor'))<a class="admin-{{ ($tab??'')==='riwayat'?'primary':'secondary' }}" href="{{ route('admin.email.history') }}">{{ __('Riwayat Pengiriman') }}</a>@endif
@if($perms->can($viewer,'email.manage'))<a class="admin-{{ ($tab??'')==='kirim'?'primary':'secondary' }}" href="{{ route('admin.email.compose') }}">{{ __('Kirim Informasi') }}</a>@endif
@if($perms->can($viewer,'email.monitor'))<a class="admin-{{ ($tab??'')==='konfigurasi'?'primary':'secondary' }}" href="{{ route('admin.email.config') }}">{{ __('Konfigurasi Email') }}</a>@endif
</nav>
@if(session('status'))<p class="admin-alert success" role="status">{{ session('status') }}</p>@endif
