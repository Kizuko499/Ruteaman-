<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SitePresentation extends Model {protected $guarded=['id'];protected $hidden=['login_photo_path','register_photo_path'];}
