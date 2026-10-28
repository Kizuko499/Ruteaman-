<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Policies\AccountPolicy;
class SchoolUserRequest extends FormRequest {
 public function authorize():bool{return $this->user() && (new AccountPolicy)->manage($this->user(),$this->route('user'));}
 public function rules():array{return ['role'=>'required|in:student,verifier','active'=>'required|boolean'];}
}
