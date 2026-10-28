<?php
namespace App\Http\Controllers;
use App\Services\RouteComparisonService;
use Illuminate\Http\{JsonResponse,Request};
use Illuminate\Support\Facades\Log;
use Throwable;
class RouteComparisonController extends Controller
{
 public function __invoke(Request $request,RouteComparisonService $comparison):JsonResponse
 {
  $data=$request->validate(['school'=>['nullable','integer','exists:schools,id'],'origin'=>['required','array'],'origin.lat'=>['required','numeric','between:-90,90'],'origin.lng'=>['required','numeric','between:-180,180'],'destination'=>['required','array'],'destination.lat'=>['required','numeric','between:-90,90'],'destination.lng'=>['required','numeric','between:-180,180'],'profile'=>['nullable','in:foot-walking,cycling-regular,driving-motorcycle,driving-car']]);
  try{return response()->json($comparison->compare($data['origin'],$data['destination'],$data['profile']??'foot-walking',isset($data['school'])?(int)$data['school']:null));}
  catch(Throwable $error){Log::warning('Routing provider gagal',['error_type'=>$error::class,'error'=>$error->getMessage()]);$detail=$error instanceof \RuntimeException?$error->getMessage():'Perbandingan rute sedang tidak tersedia.';return response()->json(['error'=>['code'=>'ROUTING_UNAVAILABLE','message'=>$detail]],503);}
 }
}