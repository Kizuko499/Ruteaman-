<?php
namespace App\Http\Controllers;
use App\Models\School;
use App\Services\MapConditionService;
use Illuminate\Http\Request;
final class MapConditionController extends Controller {
 public function index(Request $request,MapConditionService $service){
  $d=$request->validate(['school'=>'required|integer|exists:schools,id','bbox'=>'nullable|string|max:160','cursor'=>'nullable|string|max:2048','limit'=>'nullable|integer|min:1|max:500']);
  $school=School::where('status','!=','archived')->findOrFail($d['school']);
  return response()->json($service->page($school,$d))->header('Cache-Control','private, no-store');
 }
}
