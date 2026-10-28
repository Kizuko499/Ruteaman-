<nav class="floating-nav liquid-glass" data-glass-variant="nav" aria-label="{{ __('Navigasi utama') }}">
 <a class="brand" href="{{ route('home') }}"><span class="brand-mark">R</span><b>Rute<span>Aman</span></b></a>
 <div class="nav-links">
  <a href="{{ route('home') }}">{{ __('Beranda') }}</a><a href="{{ route('map') }}">{{ __('Peta') }}</a><a href="{{ route('home') }}#cara-kerja">{{ __('Cara kerja') }}</a>
  @auth<a href="{{ route('reports.mine') }}">{{ __('Laporanku') }}</a>@endauth
 </div>
 <div class="nav-actions">
  @guest<a class="btn btn-dark btn-small" href="{{ route('login') }}">{{ __('Masuk') }}</a>
  @else
   @if(auth()->user()->role->value==='school_admin')<a class="btn btn-dark btn-small" href="{{ route('school.dashboard') }}">{{ __('Dashboard') }}</a>
   @else<a class="btn btn-dark btn-small" href="{{ route('hazards.create') }}">{{ __('＋ Lapor') }}</a>@endif
  @endguest
 </div>
</nav>