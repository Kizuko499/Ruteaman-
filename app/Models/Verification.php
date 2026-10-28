<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Verification extends Model{public $timestamps=false;protected $fillable=['hazard_id','verifier_id','decision','note','created_at'];protected function casts():array{return ['created_at'=>'datetime'];}}