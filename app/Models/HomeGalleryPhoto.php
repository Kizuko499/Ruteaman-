<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HomeGalleryPhoto extends Model {
 public $incrementing=false;protected $keyType='string';protected $guarded=[];protected $hidden=['path','uploaded_by'];
 protected function casts():array{return ['enabled'=>'boolean','sort_order'=>'integer','width'=>'integer','height'=>'integer'];}
 public function group(){return $this->belongsTo(HomeGalleryGroup::class,'group_id');}
 public function type(){return $this->belongsTo(HazardType::class,'type_id');}
}
