<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Policies\HazardPublicPolicy;
use App\Services\ConditionFeedService;
use App\Services\HazardEvidenceService;
use App\Services\HazardLoadService;
use App\Services\HazardSchoolContext;
use App\Services\RelatedHazardService;
use App\Services\SpatialSql;
use Illuminate\Http\Request;

final class ConditionController extends Controller
{
    public static function cachedJson(Request $request, array $data)
    {
        $response = response()->json($data)->setEtag(hash('sha256', json_encode($data)))->header('Cache-Control', 'public, max-age=0, must-revalidate');
        $response->isNotModified($request);

        return $response;
    }

    private function filters(Request $request): array
    {
        return $request->validate(['school' => 'nullable|string|max:100', 'kategori' => 'nullable|string|max:50|exists:hazard_types,code', 'tampil' => 'nullable|in:aktif,surut,semua', 'cursor' => 'nullable|string|max:2048', 'limit' => 'nullable|integer|min:1|max:24']);
    }

    public function index(Request $request, ConditionFeedService $feed)
    {
        $filters = $this->filters($request);
        $school = $feed->school($filters['school'] ?? null);

        return response()->view('pages.conditions.index', ['feed' => $feed->feed($school, $filters), 'school' => $school, 'types' => HazardType::orderBy('label')->get(), 'filters' => $filters])->header('Cache-Control', 'private, no-store');
    }

    public function api(Request $request, ConditionFeedService $feed)
    {
        $filters = $this->filters($request);

        return self::cachedJson($request, $feed->feed($feed->school($filters['school'] ?? null), $filters));
    }

    public function show(Request $request, Hazard $hazard, ConditionFeedService $feed, HazardEvidenceService $evidence, HazardLoadService $loads, RelatedHazardService $related)
    {
        abort_unless(app(HazardPublicPolicy::class)->view($request->user(), $hazard), 404);
        $coords = SpatialSql::coordinates('latitude', 'longitude');
        $hazard = Hazard::whereKey($hazard->id)->select('hazards.*')->selectRaw($coords)->with('type')->firstOrFail();
        $school = app(HazardSchoolContext::class)->pointSchools($hazard)->orderBy('id')->first();
        $item = $feed->item($hazard);
        $input = $evidence->inputs($hazard);
        $active = Hazard::whereKey($hazard->id)->active()->exists();
        $expired = $hazard->status->value === 'expired' || (! $active && $hazard->status->value !== 'resolved');
        $load = $loads->calculate($active ? [$input] : [], (float) config('ruteaman.hazard_saturation', 9));
        $photos = $hazard->reports()->whereNotNull('photo_path')->orderBy('observed_at')->orderBy('id')->limit(6)->get()->map(fn ($r, $i) => ['url' => route('conditions.media', ['hazard' => $hazard, 'index' => $i, 'w' => 1280]), 'thumb' => route('conditions.media', ['hazard' => $hazard, 'index' => $i, 'w' => 640]), 'fallback' => route('conditions.media', ['hazard' => $hazard, 'index' => $i, 'w' => 1280, 'format' => 'jpeg']), 'fallback_thumb' => route('conditions.media', ['hazard' => $hazard, 'index' => $i, 'w' => 640, 'format' => 'jpeg']), 'alt' => $item['alt'].' · foto '.($i + 1)])->all();
        $labels = ['report.created' => 'Dilaporkan', 'confirmation.still_there' => 'Dikonfirmasi masih ada', 'confirmation.gone' => 'Dilaporkan sudah surut', 'verification.verified' => 'Diverifikasi oleh sekolah', 'verification.needs_evidence' => 'Memerlukan bukti tambahan', 'hazard.resolved' => 'Kondisi surut'];
        $timeline = AuditLog::where('entity', 'hazard')->where('entity_id', $hazard->id)->whereIn('action', array_keys($labels))->orderBy('created_at')->orderBy('id')->get(['action', 'created_at'])->map(fn ($a) => ['label' => $labels[$a->action], 'at' => $a->created_at]);
        $timeline->prepend(['label' => 'Laporan pertama dicatat', 'at' => $hazard->first_reported_at]);
        if ($school?->settings['public_escalation_status'] ?? false) {
            foreach ($hazard->escalations()->whereNotNull('forwarded_at')->get() as $e) {
                $timeline->push(['label' => 'Diteruskan ke pihak berwenang', 'at' => $e->forwarded_at]);
            }
        }
        $timeline = $timeline->sortBy('at')->values();
        $relatedItems = $related->nearest($hazard, $school);
        $comments = $hazard->comments()->with('user')->orderBy('id')->limit(100)->get();

        return response()->view('pages.conditions.show', compact('hazard', 'school', 'item', 'input', 'load', 'photos', 'timeline', 'relatedItems', 'active', 'expired', 'comments'))->header('Cache-Control', 'private, no-store');
    }

    public function refresh(Request $request, Hazard $hazard, ConditionFeedService $feed)
    {
        abort_unless(app(HazardPublicPolicy::class)->view($request->user(), $hazard), 404);

        return response()->json(['hazard' => $feed->item($hazard), 'generated_at' => now()->toIso8601String()])->header('Cache-Control', 'private, no-store');
    }

    public function related(Request $request, Hazard $hazard, RelatedHazardService $related)
    {
        abort_unless(app(HazardPublicPolicy::class)->view($request->user(), $hazard), 404);
        $school = app(HazardSchoolContext::class)->pointSchools($hazard)->first();

        return response()->json(['items' => $related->nearest($hazard,$school)])->header('Cache-Control','private, no-store');
    }
}
