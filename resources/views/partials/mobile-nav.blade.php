<style data-ruteaman-dock-toggle="v1">
/* Navbar bawah bisa disembunyikan/ditampilkan dengan animasi slide.
   Keadaan tersimpan per pengunjung (localStorage ra-dock-hidden). */
@media (max-width:640px){
 .mobile-tabs-root{pointer-events:none}
 .mobile-tabs{pointer-events:auto;transition:transform .38s cubic-bezier(.32,.72,.24,1),opacity .3s ease,visibility 0s}
 .mobile-tabs-root.dock-off .mobile-tabs{transform:translateY(calc(100% + 26px));opacity:0;pointer-events:none;visibility:hidden;transition:transform .38s cubic-bezier(.32,.72,.24,1),opacity .3s ease,visibility 0s .38s}
 .dock-btn{pointer-events:auto;position:absolute;display:grid;place-items:center;border:1px solid #dfe9e4;background:#fff;color:#1f6e70;cursor:pointer;box-shadow:0 1px 2px rgba(18,56,58,.08),0 8px 18px rgba(18,56,58,.18);-webkit-tap-highlight-color:transparent}
 .dock-btn svg{display:block}
 .dock-hide-btn{top:-13px;right:14px;width:30px;height:30px;border-radius:50%;z-index:3;transition:opacity .22s ease,transform .22s ease}
 .mobile-tabs-root.dock-off .dock-hide-btn{opacity:0;transform:scale(.6);pointer-events:none}
 .dock-show-btn{right:2px;bottom:0;width:48px;height:48px;border-radius:50%;z-index:2;opacity:0;transform:translateY(10px) scale(.85);pointer-events:none;transition:opacity .26s ease,transform .3s cubic-bezier(.32,.72,.24,1)}
 .mobile-tabs-root.dock-off .dock-show-btn{opacity:1;transform:none;pointer-events:auto}
}
@media (prefers-reduced-motion:reduce){
 .mobile-tabs,.dock-hide-btn,.dock-show-btn{transition:none!important}
}
</style>
<div class="mobile-tabs-root">
 <nav class="mobile-tabs" aria-label="{{ __('Navigasi utama seluler') }}">
  <a @if(request()->routeIs('home')) class="active" aria-current="page" @endif href="{{ route('home') }}"><span class="tab-icon"><x-account-icon name="home"/></span><b>{{ __('Dashboard') }}</b></a>
  <a @if(request()->routeIs('map')) class="active" aria-current="page" @endif href="{{ route('map') }}"><span class="tab-icon"><x-account-icon name="map"/></span><b>{{ __('Peta') }}</b></a>
  <a @if(request()->routeIs('hazards.create','guest-reports.*')) class="active" aria-current="page" @endif href="{{ auth()->check()?route('hazards.create'):route('guest-reports.create') }}"><span class="tab-icon"><x-account-icon name="plus"/></span><b>{{ __('Lapor') }}</b></a>
  <a @if(request()->routeIs('reports.mine')) class="active" aria-current="page" @endif href="{{ auth()->check()?route('reports.mine'):route('login') }}" data-reports-tab aria-label="{{ $accountUnread?'Laporanku, ada pembaruan baru':'Laporanku' }}"><span class="tab-icon"><x-account-icon name="reports"/><i class="notification-dot" data-unread-dot @if(!$accountUnread) hidden @endif aria-hidden="true"></i></span><b>{{ __('Laporanku') }}</b></a>
  <button type="button" data-account-open aria-haspopup="dialog" aria-controls="account-menu" aria-expanded="false" class="{{ request()->routeIs('account.*','school.users','help','privacy.policy','terms')?'active':'' }}"><span class="tab-icon">@auth<x-avatar :user="auth()->user()" :size="28" :sync="true" :decorative="true"/>@else<x-account-icon name="user"/>@endauth</span><b>{{ __('Akun') }}</b></button>
 </nav>
 <button type="button" class="dock-btn dock-hide-btn" data-dock-hide aria-label="{{ __('Sembunyikan navigasi') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 9l7 7 7-7"/></svg></button>
 <button type="button" class="dock-btn dock-show-btn" data-dock-show aria-label="{{ __('Tampilkan navigasi') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg></button>
</div>
<script>
(function () {
  var root = document.querySelector('.mobile-tabs-root');
  if (!root || root.dataset.dockBound) return;
  root.dataset.dockBound = '1';
  var nav = root.querySelector('.mobile-tabs');
  function apply(hidden, save) {
    root.classList.toggle('dock-off', hidden);
    if (nav) { nav.inert = hidden; nav.setAttribute('aria-hidden', hidden ? 'true' : 'false'); }
    if (save) { try { localStorage.setItem('ra-dock-hidden', hidden ? '1' : '0'); } catch (e) {} }
  }
  var startHidden = false;
  try { startHidden = localStorage.getItem('ra-dock-hidden') === '1'; } catch (e) {}
  apply(startHidden, false);
  var hideBtn = root.querySelector('[data-dock-hide]');
  var showBtn = root.querySelector('[data-dock-show]');
  if (hideBtn) hideBtn.addEventListener('click', function () { apply(true, true); });
  if (showBtn) showBtn.addEventListener('click', function () { apply(false, true); });
})();
</script>
<button type="button" class="account-header-trigger" data-account-open aria-label="{{ __('Buka menu akun') }}" aria-haspopup="dialog" aria-controls="account-menu" aria-expanded="false">@auth<x-avatar :user="auth()->user()" :size="40" :sync="true" :decorative="true"/>@else<x-account-icon name="user"/>@endauth</button>
<style data-ruteaman-accountbtn="v1">
/* Tombol akun header: kaca lembut, ikon lebih ramping */
.account-header-trigger{border-radius:16px;background:rgba(255,255,255,.82);color:var(--teal-dark,#1F6E70);box-shadow:inset 0 1px 0 rgba(255,255,255,.7),0 1px 2px rgba(18,56,58,.05),0 8px 20px rgba(18,56,58,.10);backdrop-filter:blur(12px) saturate(140%);-webkit-backdrop-filter:blur(12px) saturate(140%);transition:transform .18s ease,box-shadow .18s ease}
.account-header-trigger:hover{box-shadow:inset 0 1px 0 rgba(255,255,255,.7),0 2px 4px rgba(18,56,58,.06),0 12px 26px rgba(18,56,58,.14)}
.account-header-trigger:active{transform:scale(.95)}
.account-header-trigger .account-icon{width:22px;height:22px;stroke-width:1.7}
</style>
