<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CampusCatalogEntry extends Model {
 protected $table='campus_catalog';protected $guarded=[];
 protected function casts():array {return ['aliases'=>'array','reference_latitude'=>'float','reference_longitude'=>'float'];}
 public function school():BelongsTo {return $this->belongsTo(School::class);}
}
