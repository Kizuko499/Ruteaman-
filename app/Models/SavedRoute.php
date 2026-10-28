<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SavedRoute extends Model {
 protected $fillable=['school_id','name','geometry','distance_m','duration_s','source','is_demo'];
 protected function casts():array{return ['geometry'=>'array','is_demo'=>'boolean'];}
}
