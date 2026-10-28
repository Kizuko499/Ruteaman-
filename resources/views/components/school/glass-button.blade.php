@props(['href'=>null,'primary'=>false,'type'=>'button'])
@if($href)<a href="{{ $href }}" {{ $attributes->class(['school-glass-button','school-glass-button--primary'=>$primary]) }}>@else<button type="{{ $type }}" {{ $attributes->class(['school-glass-button','school-glass-button--primary'=>$primary]) }}>@endif
<span class="school-glass-button__label">{{ $slot }}</span><span class="school-glass-button__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 5 7 7-7 7"/></svg></span><span class="school-glass-button__rim" aria-hidden="true"></span>
@if($href)</a>@else</button>@endif
