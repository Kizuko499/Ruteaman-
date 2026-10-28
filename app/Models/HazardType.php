<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class HazardType extends Model{public $timestamps=false;protected $fillable=['code','label','severity_weight','ttl_minutes','rain_sensitive','night_only','icon_key','icon_path'];protected $hidden=['icon_path'];protected function casts():array{return ['rain_sensitive'=>'boolean','night_only'=>'boolean'];}public function hazards():HasMany{return $this->hasMany(Hazard::class,'type_id');}}