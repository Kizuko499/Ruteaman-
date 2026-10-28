<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreGuestHazardRequest extends FormRequest {
 public function authorize():bool {return $this->user()===null;}
 protected function prepareForValidation():void {$name=$this->input('guest_name','');if(is_string($name))$this->merge(['guest_name'=>trim($name)]);}
 public function rules():array {return [
  'institution_type'=>['sometimes','in:school,campus'],
  'school_id'=>['nullable','required_without:school_ticket',\Illuminate\Validation\Rule::prohibitedIf($this->filled('school_ticket')),'integer',\Illuminate\Validation\Rule::exists('schools','id')->where(fn($q)=>$q->when($this->filled('institution_type'),fn($q)=>$q->where('institution_type',$this->input('institution_type'))))],
  'school_ticket'=>['nullable',\Illuminate\Validation\Rule::prohibitedIf($this->input('institution_type')==='campus'),'required_without:school_id','string','max:12000'],
  'guest_name'=>['nullable','string','max:80','not_regex:/[<>\r\n]/'],
  'type_id'=>['required','integer','exists:hazard_types,id'],
  'latitude'=>['required','numeric','between:-90,90'], 'longitude'=>['required','numeric','between:-180,180'],
  'location_label'=>['nullable','string','max:120','not_regex:/[<>\r\n]/'],
  'description'=>['nullable','string','max:500'],
  'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
  'report_consent'=>['accepted'], 'website'=>['prohibited'], '_form_token'=>['required','string','size:40'],
 ];}
 public function messages():array {return ['school_id.required'=>'Pilih sekolah terkait.','school_id.required_without'=>'Cari dan pilih sekolah terkait.','school_ticket.required_without'=>'Cari nama sekolah atau NPSN, lalu pilih salah satu hasil.','report_consent.accepted'=>'Setujui ketentuan laporan sebelum mengirim.','website.prohibited'=>'Pengiriman tidak valid.','_form_token.required'=>'Muat ulang formulir sebelum mengirim.'];}
}
