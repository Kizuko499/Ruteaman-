<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HazardConfirmation extends Model{public $timestamps=false;protected $fillable=['hazard_id','user_id','vote','created_at'];protected function casts():array{return ['created_at'=>'datetime'];}}