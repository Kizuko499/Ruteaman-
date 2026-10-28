@props(['type'=>'status','condition'=>null,'visual'=>null])
@php
if($condition)$visual=app(\App\Services\ConditionIconService::class)->visual($condition);
$shape=$visual?config('condition_icons.'.($visual['icon_key']??'warning').'.shape'):null;
@endphp
@if(!empty($visual['icon_url']))
<img class="condition-custom-icon" src="{{ $visual['icon_url'] }}" width="32" height="32" alt="" aria-hidden="true" loading="lazy">
@elseif($shape)
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $shape !!}</svg>
@else
<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
@switch($type)
@case('flood')<path d="M3 13q4-5 8 0t8 0t10 0M3 21q4-5 8 0t8 0t10 0M9 3l-3 5M23 3l-3 5"/>@break
@case('pothole')<path d="M3 23l6-15h14l6 15M13 5h6M16 10v4"/><ellipse cx="16" cy="23" rx="8" ry="4"/>@break
@case('fallen_tree')<path d="M5 25l22-6M15 22l3-13M12 10l7-6 7 8-8 5zM3 28h26"/>@break
@case('poor_lighting')<path d="M7 28V7q0-3 3-3h11M19 4l-3 7h12l-3-7M20 15v3M14 15l-2 2M28 15l2 2M3 28h8"/>@break
@default<path d="M16 4l12 12-12 12L4 16z"/><circle cx="16" cy="16" r="3"/>
@endswitch
</svg>

@endif
