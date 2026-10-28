<?php
namespace App\Http\Controllers;
use App\Services\RouteComparisonService;
use Illuminate\Http\{JsonResponse,Request};
class SavedRouteController extends Controller {
 public function __invoke(Request $request,RouteComparisonService $comparison):JsonResponse {
  $d=$request->validate(['school'=>'required|integer|exists:schools,id']);
  return response()->json($comparison->saved((int)$d['school']));
 }
}
