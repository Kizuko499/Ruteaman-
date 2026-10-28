<?php
namespace App\Http\Controllers;
use App\Services\{ConditionFeedService,FeaturedHazardService};
use Illuminate\Http\Request;
final class FeaturedHazardController extends Controller {
 public function __invoke(Request $request,ConditionFeedService $feed,FeaturedHazardService $featured){
  $d=$request->validate(['school'=>'nullable|string|max:100','after'=>'nullable|string|max:100','before'=>'nullable|string|max:100']);
  return ConditionController::cachedJson($request,$featured->next($feed->school($d['school']??null),$d['before']??$d['after']??null,isset($d['before'])?-1:1));
 }
}
