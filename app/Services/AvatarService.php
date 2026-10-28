<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class AvatarService {
 public function allowed(User $user):bool{return $user->school && app(PermissionService::class)->feature($user->school,'avatar_upload',true) && (!config('account.avatar_upload_requires_consent') || $user->parental_consent_at!==null);}
 public function replace(User $user,?string $path,?string $preset=null):void {
  $old=null;
  try{DB::transaction(function()use($user,$path,$preset,&$old){$locked=User::whereKey($user->id)->lockForUpdate()->firstOrFail();$old=$locked->avatar_path;$locked->forceFill(['avatar_path'=>$path,'avatar_preset'=>$preset])->save();});}
  catch(\Throwable $e){if($path)$this->removeFiles($path);throw $e;}
  $user->refresh();if($old&&$old!==$path)$this->removeFiles($old);
 }
 public function removeFiles(?string $path):void {if(!$path)return;Storage::disk('avatars')->delete([$path,str_replace('.webp','-96.webp',$path),str_replace('.webp','-256.webp',$path)]);}
 public function upload(User $user,UploadedFile $file):void {
  if($user->school)app(PermissionService::class)->requireFeature($user->school,'avatar_upload',true);if(!$this->allowed($user))throw ValidationException::withMessages(['avatar'=>'Persetujuan orang tua belum tercatat. Pilih avatar bawaan atau hubungi sekolah.']);
  if(!function_exists('imagecreatefromstring')||!function_exists('imagewebp'))throw ValidationException::withMessages(['avatar'=>'Pemrosesan foto belum tersedia. Pilih avatar bawaan; hubungi admin sekolah.']);
  $bytes=file_get_contents($file->getRealPath());$info=@getimagesizefromstring($bytes);
  if(!$info || !in_array($info['mime'],['image/jpeg','image/png','image/webp'],true) || $info[0]<128 || $info[1]<128 || $info[0]>4096 || $info[1]>4096)throw ValidationException::withMessages(['avatar'=>'Isi foto tidak valid atau dimensinya tidak sesuai.']);
  $source=@imagecreatefromstring($bytes);if(!$source)throw ValidationException::withMessages(['avatar'=>'Foto tidak dapat diproses. Coba foto lain.']);
  $path=$user->id.'/'.Str::ulid().'.webp';$outputs=[];
  try{
   $side=min(imagesx($source),imagesy($source));$x=(imagesx($source)-$side)/2;$y=(imagesy($source)-$side)/2;
   foreach([512,96,256] as $size){$target=imagecreatetruecolor($size,$size);$white=imagecolorallocate($target,255,255,255);imagefill($target,0,0,$white);imagecopyresampled($target,$source,0,0,(int)$x,(int)$y,$size,$size,$side,$side);ob_start();$ok=imagewebp($target,null,85);$data=ob_get_clean();imagedestroy($target);if(!$ok||!$data)throw new \RuntimeException('encode failed');$output=$size===512?$path:str_replace('.webp','-'.$size.'.webp',$path);Storage::disk('avatars')->put($output,$data);$outputs[]=$output;}
   $this->replace($user,$path);
  }catch(\Throwable $e){Storage::disk('avatars')->delete($outputs);report($e);throw ValidationException::withMessages(['avatar'=>'Foto gagal disimpan. Coba lagi atau pilih avatar bawaan.']);}
  finally{imagedestroy($source);}
 }
}
