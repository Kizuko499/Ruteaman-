<?php
namespace App\Policies;
use App\Models\User;
use App\Enums\Role;
use App\Services\PermissionService;
class AccountPolicy {
 public function update(User $actor,User $target):bool{return $actor->active && $actor->id===$target->id;}
 public function viewAvatar(User $actor,User $target):bool{return $actor->active&&($actor->id===$target->id||($target->school&&app(PermissionService::class)->can($actor,'users.view',$target->school)));}
 public function manage(User $actor,User $target):bool {if(!$actor->active||$actor->id===$target->id||$target->role->admin()||!$target->school||str_ends_with($target->email,'@deleted.invalid'))return false;return $target->role===Role::Verifier?app(PermissionService::class)->can($actor,'users.manage_verifiers',$target->school,true):(app(PermissionService::class)->can($actor,'users.manage_students',$target->school,true)||app(PermissionService::class)->can($actor,'users.manage_verifiers',$target->school,true));}
}
