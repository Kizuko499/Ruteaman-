<?php
namespace App\Http\Controllers;
use App\Models\Hazard;
use App\Policies\HazardPublicPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
final class ConditionMediaController extends Controller {
 public function photo(Request $request,Hazard $hazard,int $index){
  abort_unless(app(HazardPublicPolicy::class)->view($request->user(),$hazard),404);abort_if($index<0||$index>5,404);
  $d=$request->validate(['w'=>'nullable|in:640,1280','format'=>'nullable|in:webp,jpeg']);$width=(int)($d['w']??640);$format=$d['format']??'webp';
  $path=$hazard->reports()->whereNotNull('photo_path')->orderBy('observed_at')->orderBy('id')->skip($index)->value('photo_path');
  // No fallback to the old public disk: run the supplied migration command before serving legacy photos.
  abort_unless($path&&str_starts_with($path,'hazards/')&&!str_contains($path,'..')&&Storage::disk('hazard_photos')->exists($path),404);
  $bytes=Storage::disk('hazard_photos')->get($path);$key='derivatives/'.hash('sha256',$bytes).'-'.$width.'.'.$format;
  if(!Storage::disk('hazard_photos')->exists($key)){
   $size=@getimagesizefromstring($bytes);abort_unless($size&&$size[0]*$size[1]<=12000000,404);$image=@imagecreatefromstring($bytes);abort_unless($image,404);$w=min($width,imagesx($image));$h=max(1,(int)round(imagesy($image)*$w/imagesx($image)));$scaled=imagescale($image,$w,$h);imagedestroy($image);$limit=$width===640?120*1024:200*1024;
   for($quality=82;;$quality-=8){ob_start();if($format==='jpeg')imagejpeg($scaled,null,max(20,$quality));else imagewebp($scaled,null,max(20,$quality));$result=ob_get_clean();if(strlen($result)<=$limit)break;if($quality<=20){$w=max(1,(int)($w*.8));$h=max(1,(int)($h*.8));$smaller=imagescale($scaled,$w,$h);imagedestroy($scaled);$scaled=$smaller;$quality=82;}}
   imagedestroy($scaled);Storage::disk('hazard_photos')->put($key,$result);
  }
  return response(Storage::disk('hazard_photos')->get($key))->header('Content-Type',$format==='jpeg'?'image/jpeg':'image/webp')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
 }
 public function og(Request $request,Hazard $hazard){
  abort_unless(app(HazardPublicPolicy::class)->view(null,$hazard),404);$hazard->load('type');$img=imagecreatetruecolor(1200,630);$cream=imagecolorallocate($img,255,250,235);$ink=imagecolorallocate($img,18,56,58);$teal=imagecolorallocate($img,31,110,112);imagefill($img,0,0,$cream);imagefilledrectangle($img,0,0,28,630,$teal);
  $font=public_path('fonts/LiberationSans-Bold.ttf');$text=['RuteAman · Kondisi jalan',$hazard->type->label,$hazard->status_label,($hazard->last_confirmed_at??$hazard->first_reported_at)->locale('id')->diffForHumans(),$hazard->is_demo?'SIMULASI DEMO':'Informasi komunitas — bukan jaminan kondisi jalan'];
  foreach($text as $i=>$line)if(is_file($font))imagettftext($img,$i===1?46:26,0,70,120+$i*94,$ink,$font,$line);else imagestring($img,5,70,120+$i*94,$line,$ink);
  ob_start();imagepng($img);$bytes=ob_get_clean();imagedestroy($img);return response($bytes)->header('Content-Type','image/png')->header('Cache-Control','private, no-store');
 }
}
