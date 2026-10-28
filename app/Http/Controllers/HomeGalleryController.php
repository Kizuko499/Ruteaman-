<?php
namespace App\Http\Controllers;
use App\Models\HomeGalleryGroup;
use App\Services\HomeGalleryService;
class HomeGalleryController extends Controller {
 public function feed(HomeGalleryService $gallery){return response()->json($gallery->feed())->header('Cache-Control','private, no-store');}
 public function media(string $key,string $token,HomeGalleryService $gallery){abort_unless(array_key_exists($key,config('home_gallery.groups')),404);$g=HomeGalleryGroup::where('key',$key)->firstOrFail();return $gallery->media($g,$token);}
}
