<?php
namespace App\Http\Controllers;
use App\Enums\Role;
use App\Models\HazardType;
use App\Models\School;
use App\Services\GlobalFeatureSettings;
use App\Services\{AdminAuditService,ConditionFeedService,ConditionIconService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule,ValidationException};
class AdminConditionTypeController extends Controller {
 private function owner(Request $r):void {abort_unless($r->user()?->fresh()->role===Role::Owner,403);abort_unless(Schema::hasColumn('hazard_types','icon_key'),503,'Jalankan migration jenis kondisi terlebih dahulu.');}
 public function index(Request $r){$this->owner($r);$reportSchool=School::where('status','active')->orderBy('name')->get()->first(fn($s)=>$s->features['reporting'] ?? app(GlobalFeatureSettings::class)->enabled('reporting'));return response()->view('admin.condition-types',['types'=>HazardType::withCount('hazards')->orderBy('label')->get(),'icons'=>config('condition_icons'),'reportSchool'=>$reportSchool])->header('Cache-Control','private, no-store');}
 public function store(Request $r,AdminAuditService $audit){$this->owner($r);return $this->save($r,null,$audit);}
 public function update(Request $r,HazardType $type,AdminAuditService $audit){$this->owner($r);return $this->save($r,$type,$audit);}
 private function save(Request $r,?HazardType $type,AdminAuditService $audit){
  if(!$type&&!$r->filled('code'))$r->merge(['code'=>Str::slug($r->input('label',''),'_')]);
  $rules=['label'=>'required|string|max:80|not_regex:/[<>]/','severity_weight'=>'required|integer|min:1|max:5','ttl_minutes'=>'required|integer|min:1|max:525600','rain_sensitive'=>'required|boolean','night_only'=>'required|boolean','icon_mode'=>'required|in:preset,upload','icon_key'=>['required',Rule::in(array_keys(config('condition_icons')))],'icon'=>'nullable|image|mimes:png,jpg,jpeg,webp|max:512'];
  if(!$type)$rules['code']=['required','string','max:50','regex:/^[a-z][a-z0-9_]*$/',Rule::unique('hazard_types','code')];
  $d=$r->validate($rules);$newPath=null;
  if($d['icon_mode']==='upload'){
   if(!$r->hasFile('icon')&&!$type?->icon_path)throw ValidationException::withMessages(['icon'=>'Unggah logo terlebih dahulu, atau pilih ikon bawaan.']);
   if($r->hasFile('icon')){
    $file=$r->file('icon');$size=@getimagesize($file->getRealPath());if(!$size||$size[0]*$size[1]>1048576)throw ValidationException::withMessages(['icon'=>'Logo maksimal 1.048.576 piksel. Gunakan gambar kecil tanpa tulisan rinci.']);
    if(!function_exists('imagewebp'))throw ValidationException::withMessages(['icon'=>'PHP GD dengan dukungan WebP wajib tersedia.']);
    $im=@imagecreatefromstring(file_get_contents($file->getRealPath()));if(!$im)throw ValidationException::withMessages(['icon'=>'Logo tidak dapat diproses.']);
    // Preserve transparency, normalize EXIF orientation, and re-encode without metadata.
    if(function_exists('exif_read_data')&&$file->getMimeType()==='image/jpeg'){$o=(int)((@exif_read_data($file->getRealPath()))['Orientation']??1);if(in_array($o,[2,4,5,7],true))imageflip($im,in_array($o,[2,5,7],true)?IMG_FLIP_HORIZONTAL:IMG_FLIP_VERTICAL);$angle=match($o){3=>180,5,6=>-90,7,8=>90,default=>0};if($angle){$rot=imagerotate($im,$angle,0);imagedestroy($im);$im=$rot;}}
    $w=imagesx($im);$h=imagesy($im);$scale=256/max($w,$h);$canvas=imagecreatetruecolor(256,256);imagealphablending($canvas,false);imagesavealpha($canvas,true);imagefill($canvas,0,0,imagecolorallocatealpha($canvas,0,0,0,127));$dw=max(1,(int)round($w*$scale));$dh=max(1,(int)round($h*$scale));imagecopyresampled($canvas,$im,intdiv(256-$dw,2),intdiv(256-$dh,2),0,0,$dw,$dh,$w,$h);imagedestroy($im);ob_start();$ok=imagewebp($canvas,null,90);$bytes=ob_get_clean();imagedestroy($canvas);$newPath='condition-icons/'.Str::uuid().'.webp';if(!$ok||!Storage::disk('local')->put($newPath,$bytes))throw ValidationException::withMessages(['icon'=>'Gagal menyimpan logo.']);
   }
  }
  $oldPath=null;
  try {DB::transaction(function()use($r,$type,$d,$newPath,&$oldPath,$audit){
   $row=$type?HazardType::whereKey($type->id)->lockForUpdate()->firstOrFail():new HazardType;
   $oldPath=$row->icon_path;if($d['icon_mode']==='upload'&&!$newPath&&!$oldPath)throw ValidationException::withMessages(['icon'=>'Logo berubah pada sesi lain. Unggah logo baru atau pilih ikon bawaan.']);$data=collect($d)->except(['icon','icon_mode'])->all();
   $data['icon_path']=$d['icon_mode']==='preset'?null:($newPath??$row->icon_path);$row->fill($data);$row->save();$audit->write($r->user(),$type?'condition-type.update':'condition-type.create','hazard-type',$row->id,['count'=>1]);
   ConditionFeedService::invalidateAfterCommit();
  });}catch(\Throwable $e){if($newPath)Storage::disk('local')->delete($newPath);throw $e;}
  if($oldPath&&($d['icon_mode']==='preset'||$newPath))Storage::disk('local')->delete($oldPath);
  return to_route('admin.condition-types')->with('success',$type?'Jenis kondisi dan logo diperbarui. Laporan lama tetap tersimpan.':'Jenis kondisi ditambahkan. Sekarang tersedia di formulir laporan dan filter kondisi.');
 }
 public function icon(HazardType $type,ConditionIconService $icons){return $icons->image($type);}
}
