<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreHazardRequest extends FormRequest
{
 public function authorize():bool{return $this->user()?->parental_consent_at!==null;}
 public function rules():array{return ['type_id'=>['required','exists:hazard_types,id'],'latitude'=>['required','numeric','between:-90,90'],'longitude'=>['required','numeric','between:-180,180'],'location_label'=>['nullable','string','max:120','not_regex:/[<>\r\n]/'],'description'=>['nullable','string','max:500'],'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],'observed_at'=>['nullable','date','before_or_equal:now']];}
}