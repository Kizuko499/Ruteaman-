<?php
namespace Tests;
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase {
 public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null) {
  parent::actingAs($user,$guard);
  // actingAs swaps test identities without a real login/session regeneration.
  // Align the session hash to this identity; production login regenerates it.
  return $this->withSession(['password_hash_'.($guard??'web')=>$user->getAuthPassword()]);
 }
}
