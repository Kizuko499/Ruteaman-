@props(['href', 'label' => null])
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'ra-liquid-cta']) }}
   @if($label) aria-label="{{ $label }}" @endif>
 <span class="ra-liquid-cta__text">{{ $slot }}</span>
  <span class="ra-liquid-cta__overlay" aria-hidden="true"></span>
</a>