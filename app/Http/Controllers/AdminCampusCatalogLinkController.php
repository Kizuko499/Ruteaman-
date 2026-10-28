<?php
namespace App\Http\Controllers;
use App\Models\{CampusCatalogEntry,School};
use App\Services\{CampusCatalogLink,PermissionService};
use Illuminate\Http\Request;
final class AdminCampusCatalogLinkController extends Controller {
 public function create(Request $r,CampusCatalogEntry $catalog,CampusCatalogLink $link){
  $link->authorize($r->user());$d=$r->validate(['q'=>'nullable|string|max:100','page'=>'nullable|integer|min:1|max:5000']);
  $query=trim($d['q']??'');$candidates=School::where('institution_type','campus')->where('status','active')->where('country_code',$catalog->country_code);
  if($query!==''){$term='%'.strtr(mb_strtolower($query),['~'=>'~~','%'=>'~%','_'=>'~_']).'%';$candidates->where(fn($q)=>$q->whereRaw("LOWER(name) LIKE ? ESCAPE '~'",[$term])->orWhereRaw("LOWER(campus_code) LIKE ? ESCAPE '~'",[$term]));}
  if($catalog->official_code)$candidates->where(fn($q)=>$q->whereNull('campus_code')->orWhere('campus_code','')->orWhere('campus_code',$catalog->official_code));
  $candidates=$candidates->orderBy('name')->paginate(20)->withQueryString();
  return response()->view('admin.campus-catalog-link',compact('catalog','candidates','query'))->header('Cache-Control','private, no-store');
 }
 public function store(Request $r,CampusCatalogEntry $catalog,CampusCatalogLink $link){
  $link->authorize($r->user());$d=$r->validate(['school_id'=>'required|integer|exists:schools,id','identity_confirmed'=>'required|accepted','reason'=>'required|string|min:8|max:500']);
  $link->connect($r->user(),$catalog,(int)$d['school_id'],$d['reason']);
  return to_route('campus-catalog.index',['query'=>$catalog->official_code?:$catalog->name,'country'=>$catalog->country_code])->with('success','Entri katalog terhubung ke kampus yang sudah ada. Pin, wilayah, akun, dan fitur tidak diubah.');
 }
}
