@props(['facts'])<dl class="condition-facts">@foreach($facts as $label=>$value)<div><dt>{{ __($label) }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl>
