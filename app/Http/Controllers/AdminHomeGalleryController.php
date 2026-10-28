<?php
namespace App\Http\Controllers;
use App\Enums\Role;
use App\Models\{HomeGalleryGroup,HomeGalleryPhoto,HazardType,School};
use App\Services\{HomeGalleryService,AdminAuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage,Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AdminHomeGalleryController extends Controller {
 private function owner(Request $r):void {abort_unless($r->user()?->fresh()->role===Role::Owner,403);abort_unless(Schema::hasTable('home_gallery_groups'),503,'Jalankan migration galeri terlebih dahulu.');}
 private function done(string $message){return to_route('admin.home-gallery')->with('success',$message);}
 public function index(Request $r,HomeGalleryService $gallery){$this->owner($r);$groups=$gallery->groups();$pools=$groups->map(fn($g)=>$gallery->pool($g));$photos=HomeGalleryPhoto::with('type')->orderBy('sort_order')->get()->groupBy('group_id');$types=HazardType::orderBy('label')->get();$schools=School::where('status','active')->orderBy('name')->get(['id','name']);return response()->view('admin.home-gallery',compact('groups','pools','photos','types','schools'))->header('Cache-Control','private, no-store');}
 public function settings(Request $r,HomeGalleryGroup $group,HomeGalleryService $gallery,AdminAuditService $audit){
  $this->owner($r);$d=$r->validate(['orientation'=>'required|in:landscape,portrait','source'=>'required|in:admin,verified,mixed','mode'=>'required|in:stay,rotate','interval_value'=>'required|integer|min:1|max:1000','interval_unit'=>'required|in:seconds,minutes,hours,days,weeks,months','category_ids'=>'nullable|array|max:30','category_ids.*'=>'integer|distinct|exists:hazard_types,id','school_id'=>'nullable|integer|exists:schools,id','fixed_token'=>'nullable|string|max:80']);
  if($d['mode']==='rotate')$d['fixed_token']=null;
  if($d['orientation']==='portrait'&&$d['source']!=='admin')throw ValidationException::withMessages(['source'=>'Foto portrait hanya dari unggahan Administrator Utama. Pilih sumber Unggahan admin.']);
  DB::transaction(function()use($r,$group,$d,$gallery,$audit){$g=HomeGalleryGroup::whereKey($group->id)->lockForUpdate()->firstOrFail();$g->fill([...$d,'category_ids'=>array_map('intval',$d['category_ids']??[]),'fixed_token'=>$d['fixed_token']??null,'rotation_anchor'=>now()]);
   if($g->mode==='stay'&&!$g->fixed_token)$g->fixed_token=$gallery->pool($g)[0]['token']??null;
   if($g->fixed_token&&!in_array($g->fixed_token,array_column($gallery->pool($g),'token'),true))throw ValidationException::withMessages(['fixed_token'=>'Foto tetap tidak tersedia untuk sumber, orientasi, atau filter ini. Pilih Otomatis / foto pertama terlebih dahulu.']);
   $g->save();$audit->write($r->user(),'home-gallery.settings','home-gallery',$g->id,['feature'=>$g->key,'enabled'=>$g->mode==='rotate']);});
  return $this->done('Pengaturan kelompok foto disimpan.');
 }
 public function upload(Request $r,HomeGalleryGroup $group,AdminAuditService $audit){
  $this->owner($r);$d=$r->validate(['photo'=>'required|image|mimes:jpeg,png,webp|max:5120','caption'=>'required|string|max:140|not_regex:/[<>]/','type_id'=>'nullable|integer|exists:hazard_types,id','sort_order'=>'required|integer|min:0|max:10000','publication_consent'=>'accepted']);
  $file=$r->file('photo');$size=@getimagesize($file->getRealPath());if(!$size||$size[0]*$size[1]>12000000)throw ValidationException::withMessages(['photo'=>'Foto tidak valid atau melebihi 12 juta piksel.']);
  $im=@imagecreatefromstring(file_get_contents($file->getRealPath()));if(!$im)throw ValidationException::withMessages(['photo'=>'Foto gagal diproses. Pastikan PHP GD/WebP tersedia.']);
  if(function_exists('exif_read_data')&&$file->getMimeType()==='image/jpeg'){
   $exif=@exif_read_data($file->getRealPath());$o=(int)($exif['Orientation']??1);
   if(in_array($o,[2,4,5,7],true))imageflip($im,in_array($o,[2,5,7],true)?IMG_FLIP_HORIZONTAL:IMG_FLIP_VERTICAL);
   $angle=match($o){3=>180,5,6=>-90,7,8=>90,default=>0};if($angle){$rotated=imagerotate($im,$angle,0);imagedestroy($im);$im=$rotated;}
  }
  $w=imagesx($im);$h=imagesy($im);$orientation=$w>=$h?'landscape':'portrait';if($orientation!==$group->orientation){imagedestroy($im);throw ValidationException::withMessages(['photo'=>'Orientasi foto harus '.$group->orientation.' untuk kelompok ini.']);}
  if(max($w,$h)>1600){$small=imagescale($im,(int)round($w*1600/max($w,$h)),(int)round($h*1600/max($w,$h)));imagedestroy($im);$im=$small;}
  $id=(string)Str::uuid();$path='home-gallery/uploads/'.$id.'.webp';ob_start();$ok=imagewebp($im,null,82);$bytes=ob_get_clean();$w=imagesx($im);$h=imagesy($im);imagedestroy($im);
  if(!$ok||!Storage::disk('local')->put($path,$bytes))throw ValidationException::withMessages(['photo'=>'Gagal menyimpan foto yang telah diproses.']);
  try{DB::transaction(function()use($r,$group,$d,$id,$path,$w,$h,$orientation,$audit){$locked=HomeGalleryGroup::whereKey($group->id)->lockForUpdate()->firstOrFail();if($locked->orientation!==$orientation||$locked->photos()->count()>=60)throw ValidationException::withMessages(['photo'=>'Orientasi kelompok berubah atau batas 60 foto sudah tercapai.']);HomeGalleryPhoto::create(['id'=>$id,'group_id'=>$group->id,'path'=>$path,'caption'=>$d['caption'],'type_id'=>$d['type_id']??null,'sort_order'=>$d['sort_order'],'uploaded_by'=>$r->user()->id,'width'=>$w,'height'=>$h,'orientation'=>$orientation,'enabled'=>true]);$locked->touch();$audit->write($r->user(),'home-gallery.upload','home-gallery',$group->id,['count'=>1]);});}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
  return $this->done('Foto ditambahkan ke kelompok pilihan. Metadata dibuang dan gambar diproses menjadi WebP.');
 }
 public function photo(Request $r,HomeGalleryPhoto $photo,AdminAuditService $audit){$this->owner($r);$d=$r->validate(['caption'=>'required|string|max:140|not_regex:/[<>]/','type_id'=>'nullable|integer|exists:hazard_types,id','sort_order'=>'required|integer|min:0|max:10000','enabled'=>'required|boolean']);DB::transaction(function()use($r,$photo,$d,$audit){HomeGalleryGroup::whereKey($photo->group_id)->lockForUpdate()->firstOrFail();$photo->update($d);$photo->group->touch();$audit->write($r->user(),'home-gallery.photo.update','home-gallery',$photo->group_id,['enabled'=>(bool)$d['enabled']]);});return $this->done('Keterangan, kondisi, urutan, dan status foto disimpan.');}
 public function delete(Request $r,HomeGalleryPhoto $photo,AdminAuditService $audit){$this->owner($r);$path=$photo->path;DB::transaction(function()use($r,$photo,$audit){$g=HomeGalleryGroup::whereKey($photo->group_id)->lockForUpdate()->firstOrFail();if($g->fixed_token==='admin-'.$photo->id)$g->fixed_token=null;$g->save();$g->touch();$photo->delete();$audit->write($r->user(),'home-gallery.photo.delete','home-gallery',$g->id,['count'=>1]);});Storage::disk('local')->delete($path);return $this->done('Foto dihapus dari galeri.');}
 public function preview(Request $r,HomeGalleryPhoto $photo,HomeGalleryService $gallery){$this->owner($r);return $gallery->image('local',$photo->path);}
}
