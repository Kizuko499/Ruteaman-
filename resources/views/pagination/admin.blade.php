@if ($paginator->hasPages())
<nav class="admin-pagination" role="navigation" aria-label="{{ __('Navigasi halaman') }}">
<p class="admin-pagination-info">{{ __('Halaman') }} {{ $paginator->currentPage() }} {{ __('dari') }} {{ $paginator->lastPage() }} · {{ $paginator->total() }} {{ __('data') }}</p>
<div class="admin-pagination-links">
@if ($paginator->onFirstPage())
<span class="admin-page-btn is-disabled" aria-disabled="true">« {{ __('Sebelumnya') }}</span>
@else
<a class="admin-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">« {{ __('Sebelumnya') }}</a>
@endif
@foreach ($elements as $element)
@if (is_string($element))
<span class="admin-page-dots">{{ $element }}</span>
@endif
@if (is_array($element))
@foreach ($element as $page => $url)
@if ($page == $paginator->currentPage())
<span class="admin-page-btn is-current" aria-current="page">{{ $page }}</span>
@else
<a class="admin-page-btn" href="{{ $url }}">{{ $page }}</a>
@endif
@endforeach
@endif
@endforeach
@if ($paginator->hasMorePages())
<a class="admin-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Berikutnya') }} »</a>
@else
<span class="admin-page-btn is-disabled" aria-disabled="true">{{ __('Berikutnya') }} »</span>
@endif
</div>
</nav>
@endif
