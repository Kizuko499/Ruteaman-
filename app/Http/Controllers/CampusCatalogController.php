<?php
namespace App\Http\Controllers;
use App\Models\CampusCatalogEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
final class CampusCatalogController extends Controller {
 public function index(Request $r){
  $d=$r->validate(['query'=>'nullable|string|max:120','country'=>'nullable|string|size:2|regex:/^[A-Za-z]{2}$/','page'=>'nullable|integer|min:1|max:5000']);$country=$r->has('country')?strtoupper(trim($d['country']??'')):'ID';$query=trim($d['query']??'');
  $q=CampusCatalogEntry::query()->with('school');if($country)$q->where('country_code',$country);
  if($query!==''){$term='%'.strtr(mb_strtolower($query),['~'=>'~~','%'=>'~%','_'=>'~_']).'%';$q->whereRaw("search_text LIKE ? ESCAPE '~'",[$term]);}
  $rows=Schema::hasTable('campus_catalog')?$q->orderByRaw("CASE WHEN country_code='ID' THEN 0 ELSE 1 END")->orderBy('name')->orderBy('id')->paginate(20)->withQueryString():null;
  $codes=$rows?->getCollection()->pluck('official_code')->filter()->unique()->values()->all()??[];
  $existing=$codes?\App\Models\School::where('institution_type','campus')->where('status','!=','archived')->whereIn('campus_code',$codes)->get()->groupBy(fn($s)=>$s->country_code.':'.$s->campus_code):collect();
  $pageNames=$rows?->getCollection()->pluck('name')->filter()->unique()->values()->all()??[];
  $byName=$pageNames?\App\Models\School::where('institution_type','campus')->where('status','!=','archived')->whereIn('name',$pageNames)->get()->groupBy(fn($s)=>mb_strtolower(trim($s->name))):collect();
  $items=$rows?->getCollection()->map(function($e)use($existing,$byName){$s=$e->school;$byCode=$e->official_code?$existing->get($e->country_code.':'.$e->official_code,collect()):collect();if(!$s&&$byCode->count()===1)$s=$byCode->first();if(!$s){$bn=$byName->get(mb_strtolower(trim($e->name)),collect());if($bn->count()>=1)$s=$bn->first();}$visible=$s&&$s->institution_type==='campus'&&$s->status!=='archived';$active=$visible&&$s->status==='active';$reporting=$active&&($s->features['reporting']??app(\App\Services\GlobalFeatureSettings::class)->enabled('reporting'));return ['id'=>$e->id,'name'=>$e->name,'official_code'=>$e->official_code,'country_code'=>$e->country_code,'city'=>$e->city,'region'=>$e->region,'source'=>$e->source,'source_url'=>$e->source_url,'data_release'=>$e->data_release,'coordinate_accuracy'=>$e->coordinate_accuracy,'school_id'=>$visible?$s->id:null,'map_url'=>$visible?route('map',['school'=>$s->id]):null,'register_url'=>$active?route('register',['institution_type'=>'campus','campus_id'=>$s->id]):null,'report_url'=>$reporting?route('guest-reports.create',['school'=>$s->id]):null,'can_link'=>$e->school_id===null&&!$s,'connect_url'=>route('admin.campus-catalog.connect',['catalog'=>$e->id]),'can_activate'=>$e->school_id===null&&!$s,'activation_url'=>route('admin.schools.create',['catalog_id'=>$e->id])];})??collect();
  if($r->expectsJson())return response()->json(['data'=>$items,'page'=>$rows?->currentPage()??1,'has_more'=>$rows?->hasMorePages()??false,'total_source_entries'=>$rows?->total()??0,'country'=>$country?:null,'coverage'=>'Source entries, not a complete directory or count of unique physical campuses. Source overlap is retained. No verified campus pins are imported.'])->header('Cache-Control','private, no-store');
  return view('pages.campus-catalog',['rows'=>$rows,'items'=>$items,'query'=>$query,'country'=>$country]);
 }
}
