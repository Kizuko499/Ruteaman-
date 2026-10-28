<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WeatherSnapshot extends Model{public $timestamps=false;protected $fillable=['area_code','condition','rain_flag','source','fetched_at','valid_until'];protected function casts():array{return ['rain_flag'=>'boolean','fetched_at'=>'datetime','valid_until'=>'datetime'];}}