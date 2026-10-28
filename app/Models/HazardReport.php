<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HazardReport extends Model{protected $fillable=['hazard_id','reporter_id','description','photo_path','observed_at','school_id','is_guest','guest_name'];protected $hidden=['guest_name','school_id','is_guest'];protected function casts():array{return ['observed_at'=>'datetime','is_guest'=>'boolean'];}public function hazard():BelongsTo{return $this->belongsTo(Hazard::class);}public function school():BelongsTo{return $this->belongsTo(\App\Models\School::class);}public function reporter():BelongsTo{return $this->belongsTo(User::class,'reporter_id');}}