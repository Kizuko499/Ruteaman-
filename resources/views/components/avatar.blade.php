@props(['user','size'=>40,'decorative'=>false,'sync'=>false])
@php
 $url=$user->avatar_path?route('account.avatar.show',['user'=>$user->id,'size'=>$size>96?256:96]):($user->avatar_preset?asset('images/avatars/'.$user->avatar_preset.'.svg'):null);
 $palette=['#8ad6d1','#fff0c5','#ffd0b6'];$background=$palette[abs(crc32($user->display_name))%3];
 $initial=mb_strtoupper(mb_substr($user->display_name,0,1));
@endphp
<span class="account-avatar avatar-{{ $size }}" style="background:{{ $background }}" @if($sync) data-own-avatar @endif @if($decorative) aria-hidden="true" @else role="img" aria-label="Foto profil {{ $user->display_name }}" @endif>
 @if($url)<img src="{{ $url }}" alt="" width="{{ $size }}" height="{{ $size }}">@else<span>{{ $initial }}</span>@endif
</span>
