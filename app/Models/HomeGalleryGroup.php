<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HomeGalleryGroup extends Model {
 protected $guarded=['id','key'];
 protected function casts():array{return ['category_ids'=>'array','rotation_anchor'=>'immutable_datetime','interval_value'=>'integer'];}
 public function photos(){return $this->hasMany(HomeGalleryPhoto::class,'group_id');}
}
