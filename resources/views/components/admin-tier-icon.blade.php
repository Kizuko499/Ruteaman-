@props(['tier'=>4])
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch((int)$tier)
@case(1)<path d="M3 6l5 4 4-7 4 7 5-4-2 12H5L3 6zm3 15h12"/>@break
@case(2)<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18z"/>@break
@case(3)<path d="M3 10l9-7 9 7M5 10v11h14V10M10 21v-6h4v6M8 11h1m6 0h1"/>@break
@default<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0116 0v2"/>
@endswitch
</svg>
