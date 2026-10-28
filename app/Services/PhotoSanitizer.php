<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
final class PhotoSanitizer
{
 public function store(UploadedFile $file):string
 {
  Storage::disk('hazard_photos')->makeDirectory('hazards');
  $path='hazards/'.Str::ulid().'.webp';$bytes=file_get_contents($file->getRealPath());$size=@getimagesizefromstring($bytes);if(!$size||$size[0]*$size[1]>12000000)throw \Illuminate\Validation\ValidationException::withMessages(['photo'=>'Foto tidak valid atau resolusinya terlalu besar.']);
  if(function_exists('imagecreatefromstring')&&($image=@imagecreatefromstring($bytes))){$width=imagesx($image);$height=imagesy($image);if($width*$height>12000000){imagedestroy($image);throw \Illuminate\Validation\ValidationException::withMessages(['photo'=>'Resolusi foto terlalu besar.']);}if(max($width,$height)>2048){$small=imagescale($image,(int)round($width*2048/max($width,$height)),(int)round($height*2048/max($width,$height)));imagedestroy($image);$image=$small;}if(!imagewebp($image,Storage::disk('hazard_photos')->path($path),82)){imagedestroy($image);throw \Illuminate\Validation\ValidationException::withMessages(['photo'=>'Gagal memproses foto.']);}imagedestroy($image);return $path;}
  throw \Illuminate\Validation\ValidationException::withMessages(['photo'=>'Foto tidak dapat disanitasi. Aktifkan ekstensi PHP GD/WebP atau kirim laporan tanpa foto.']);
 }
}