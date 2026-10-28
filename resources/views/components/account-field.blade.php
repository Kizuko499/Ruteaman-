@props(['label','name','type'=>'text','value'=>'','help'=>'','autocomplete'=>null,'required'=>true,'max'=>null])
@php($fieldId = $name.'-'.\Illuminate\Support\Str::random(6))
<label class="account-field">{{ __($label) }}<input name="{{ $name }}" type="{{ $type }}" value="{{ $type==='password'?'':$value }}" aria-describedby="{{ $fieldId }}-help {{ $fieldId }}-error" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($required) required @endif @if($max) maxlength="{{ $max }}" @endif></label>
@if($help)<p id="{{ $fieldId }}-help" class="field-help">{{ __($help) }}</p>@else<span id="{{ $fieldId }}-help"></span>@endif
<p id="{{ $fieldId }}-error" class="field-error" data-field-error="{{ $name }}" hidden></p>
