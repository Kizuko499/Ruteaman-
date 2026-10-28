<?php
namespace App\Http\Controllers;
use App\Services\ConditionFeedService;
use Illuminate\Http\Request;
final class AreaHazardController extends Controller {
 public function __invoke(Request $request, ConditionFeedService $feed) {
  $d = $request->validate(['school' => 'nullable|string|max:100']);
  $school = $feed->school($d['school'] ?? null);
  return ConditionController::cachedJson($request, [...$feed->areaList($school), 'school' => ['slug' => $school?->public_slug, 'name' => $school?->name], 'generated_at' => now()->toIso8601String()]);
 }
}
