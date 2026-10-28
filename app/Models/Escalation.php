<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Escalation extends Model{public $timestamps=false;protected $fillable=['hazard_id','channel','forwarded_at','reference_id','response_status','response_note','response_at'];protected function casts():array{return ['forwarded_at'=>'datetime','response_at'=>'datetime'];}public function hazard():BelongsTo{return $this->belongsTo(Hazard::class);}}