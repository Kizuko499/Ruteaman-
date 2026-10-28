{{-- android-account-fullscreen:start --}}
<style data-ruteaman-account-fullscreen="v1">
/* RuteAman: account menu fills the mobile web viewport, not the Android OS chrome. */
@media (max-width: 1023px) {
  dialog#account-menu.account-dialog {
    position: fixed !important;
    inset: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: none !important;
    height: 100vh !important;
    height: 100dvh !important;
    max-height: none !important;
    box-sizing: border-box !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: var(--surface, #fff) !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    scroll-padding-top: calc(88px + env(safe-area-inset-top, 0px));
    -webkit-overflow-scrolling: touch;
    animation: none !important;
  }
  #account-menu .account-dialog-head {
    position: sticky !important;
    top: 0 !important;
    z-index: 5;
    min-height: 76px;
    box-sizing: border-box;
    margin: 0;
    padding: calc(16px + env(safe-area-inset-top, 0px)) max(16px, env(safe-area-inset-right, 0px)) 16px max(16px, env(safe-area-inset-left, 0px));
    background: var(--surface, #fff);
    border-bottom: 1px solid var(--line, #d5e3df);
    gap: 16px;
  }
  #account-menu .account-dialog-head h2 { margin: 0; font-size: 24px; line-height: 1.3; }
  #account-menu [data-account-close] { flex: 0 0 44px; min-width: 44px; min-height: 44px; }
  #account-menu .sheet-grip { display: none !important; }
  #account-menu .account-guest {
    margin: 0;
    padding: 24px max(20px, env(safe-area-inset-right, 0px)) calc(32px + env(safe-area-inset-bottom, 0px)) max(20px, env(safe-area-inset-left, 0px));
    min-width: 0;
    gap: 16px;
    box-sizing: border-box;
  }
  #account-menu .account-guest p { line-height: 1.6; overflow-wrap: anywhere; }
  #account-menu .account-guest .btn { width: 100%; box-sizing: border-box; min-height: 48px; }
  #account-menu .account-identity,
  #account-menu .menu-section,
  #account-menu .account-menu-list,
  #account-menu .account-logout { margin-left: 16px; margin-right: 16px; }
  #account-menu .account-identity { margin-top: 16px; }
  #account-menu .account-menu-list { min-width: 0; }
  #account-menu .account-menu-list a { min-height: 48px; }
  #account-menu .account-logout { padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px)); }
  #account-menu :focus-visible { outline: 3px solid #1f6e70; outline-offset: 3px; }
}
</style>
{{-- android-account-fullscreen:end --}}
<style data-ruteaman-account-polish="v1">
/* Poles lembar akun: kepala ramping, hero tamu tenang, tombol sekelas aplikasi
   (primer gradien teal, sekunder putih bergaris) — menggantikan tombol oranye
   krem era lama yang tingginya berlebihan. */
#account-menu .account-dialog-head h2{font-size:20px;font-weight:600;letter-spacing:-.01em}
#account-menu .icon-button{width:40px;height:40px;border:0;border-radius:50%;background:#F2F7F4;color:#49696B}
#account-menu .account-guest{gap:14px;padding-top:40px}
#account-menu .account-guest>.menu-icon{width:56px;height:56px;flex-basis:56px;border-radius:18px;background:#E4F2EC}
#account-menu .account-guest>.menu-icon .account-icon{width:26px;height:26px;stroke-width:1.7}
#account-menu .account-guest h3{font-size:22px;font-weight:600;letter-spacing:-.01em}
#account-menu .account-guest p{font-size:14px;color:#5F7A76}
#account-menu .account-guest .btn{min-height:52px;margin:0;border-radius:16px;font-size:15.5px;font-weight:600}
#account-menu .account-guest .account-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;box-shadow:0 10px 22px rgba(31,110,112,.25)}
#account-menu .account-guest .btn-soft{background:#fff;border:1.5px solid #DFE9E4;color:#14343A}
#account-menu .account-guest .btn .account-icon{width:20px;height:20px;stroke-width:1.8}
#account-menu .account-identity{background:#F7FAF8;border:1px solid #E7F0EA}
#account-menu .account-menu-list .menu-icon{border-radius:12px;background:#EAF3EE}
</style>
<dialog id="account-menu" class="account-dialog" aria-modal="true" aria-labelledby="account-menu-title">
 <div class="account-dialog-head"><span class="sheet-grip" data-sheet-grip aria-hidden="true"></span><h2 id="account-menu-title">{{ __('Akun') }}</h2><button type="button" class="icon-button" data-account-close aria-label="{{ __('Tutup menu akun') }}"><x-account-icon name="close"/></button></div>
 @auth
 @php($menuUser=auth()->user())
 <div class="account-identity"><x-avatar :user="$menuUser" :size="64" :sync="true"/><div><p class="eyebrow">{{ __('Akun Anda') }}</p><strong data-own-name>{{ $menuUser->display_name }}</strong><p>{{ $menuUser->school?->name ?? __('Sekolah belum terhubung') }} · {{ $menuUser->role->label() }}</p><small data-own-email>{{ $menuUser->email }}</small><span class="email-status" data-email-status>{{ $menuUser->email_verified_at?__('◈ Email terverifikasi'):__('◇ Email belum terverifikasi') }}</span></div></div>
 <p class="menu-section">{{ __('Halo,') }} <span data-own-name>{{ $menuUser->display_name }}</span>!</p>
 <nav class="account-menu-list" aria-label="{{ __('Menu akun') }}">
 @foreach([['home',__('Dashboard'),'home'],['user',__('Profil Saya'),'account.profile'],['bell',__('Notifikasi'),'account.notifications'],['reports',__('Laporanku'),'reports.mine'],['settings',__('Pengaturan'),'account.settings'],['shield',__('Keamanan'),'account.security'],['lock',__('Privasi dan Data'),'account.privacy'],['help',__('Bantuan dan Kontak'),'help']] as [$icon,$text,$destination])
 <a href="{{ route($destination) }}"><span class="menu-icon"><x-account-icon :name="$icon"/></span><span>{{ $text }}</span>@if($destination==='account.notifications')<span class="notification-count" data-unread-count @if(!$accountUnread) hidden @endif>{{ $accountUnread }}</span>@endif<x-account-icon name="chevron"/></a>
 @endforeach
 @if(app(\App\Services\PermissionService::class)->can($menuUser,'verification.decide',$menuUser->school))
 <span class="menu-divider"></span>
 <a href="{{ route('verifier.queue') }}"><span class="menu-icon"><x-account-icon name="check"/></span><span>{{ __('Antrean Verifikasi') }}</span><x-account-icon name="chevron"/></a>
 @if(app(\App\Services\PermissionService::class)->can($menuUser,'dashboard.view',$menuUser->school))<a href="{{ route('school.dashboard') }}"><span class="menu-icon"><x-account-icon name="dashboard"/></span><span>{{ __('Dashboard Institusi') }}{{ $menuUser->role->value==='verifier'?__(' · baca'):'' }}</span><x-account-icon name="chevron"/></a>@endif
 @endif
 @if(app(\App\Services\PermissionService::class)->can($menuUser,'dashboard.view',$menuUser->school)&&!app(\App\Services\PermissionService::class)->can($menuUser,'verification.decide',$menuUser->school))<a href="{{ route('school.dashboard') }}"><span class="menu-icon"><x-account-icon name="dashboard"/></span><span>{{ __('Dashboard Institusi') }}</span><x-account-icon name="chevron"/></a>@endif
 @if(app(\App\Services\PermissionService::class)->can($menuUser,'users.view',$menuUser->school)&&$menuUser->school_id)<a href="{{ route('school.users') }}"><span class="menu-icon"><x-account-icon name="user"/></span><span>{{ __('Manajemen Pengguna') }}</span><x-account-icon name="chevron"/></a>@endif
 @if($menuUser->role->admin())<a href="{{ route('admin.index') }}"><span class="menu-icon"><x-account-icon name="shield"/></span><span>{{ __('Konsol Admin') }}</span><x-account-icon name="chevron"/></a>@endif
 </nav>
 <form method="post" action="{{ route('logout') }}" class="account-logout">@csrf<button class="menu-logout"><span class="menu-icon"><x-account-icon name="logout"/></span>{{ __('Keluar') }}<x-account-icon name="chevron"/></button></form>
 @else
 <div class="account-guest"><span class="menu-icon"><x-account-icon name="user"/></span><h3>{{ __('Masuk atau daftar') }}</h3><p>{{ __('Masuk untuk konfirmasi dan riwayat akun.') }}</p><a class="btn account-primary" href="{{ route('login') }}">{{ __('Masuk') }}</a><a class="btn btn-soft" href="{{ route('register') }}">{{ __('Daftar') }}</a><a class="btn btn-soft" href="{{ route('home') }}"><x-account-icon name="home"/>{{ __('Dashboard') }}</a>
 
 </div>
 @endauth
</dialog>
<div id="account-feedback" class="account-feedback" role="status" aria-live="polite" hidden></div>
