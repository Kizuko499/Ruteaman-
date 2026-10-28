<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\{AccountNotificationService,AvatarService};
class AccountResource extends JsonResource {
 public function toArray($request):array{return ['id'=>$this->id,'display_name'=>$this->display_name,'email'=>$this->email,'pending_email'=>$this->pending_email,'email_verified'=>(bool)$this->email_verified_at,'role'=>$this->role->value,'school'=>$this->school?->name,'institution_type'=>$this->school?->institution_type,'country_code'=>$this->school?->country_code,'avatar_url'=>$this->avatar_path?route('account.avatar.show',['user'=>$this->id,'size'=>96]):($this->avatar_preset?asset('images/avatars/'.$this->avatar_preset.'.svg'):null),'avatar_preset'=>$this->avatar_preset,'appearance'=>$this->appearance,'locale'=>$this->locale,'unread_count'=>app(AccountNotificationService::class)->unread($this->resource),'can_upload'=>app(AvatarService::class)->allowed($this->resource)];}
}
