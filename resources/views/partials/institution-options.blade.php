@foreach(['school'=>__('Sekolah'),'campus'=>__('Kampus')] as $kind=>$group)
@php($items=collect($institutions)->filter(fn($s)=>($s->institution_type??'school')===$kind))
@if($items->isNotEmpty())<optgroup label="{{ $group }}">@foreach($items as $s)
<option value="{{ ($useSlug??false)?($s->public_slug??$s->id):$s->id }}" @selected((string)($selectedId??'')===(string)$s->id)>{{ $s->name }}{{ $kind==='campus'?' · '.($s->country_code??'ID'):'' }}</option>
@endforeach</optgroup>@endif
@endforeach
