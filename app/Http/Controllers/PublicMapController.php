<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\School;
use App\Services\ConditionFeedService;
use App\Services\FeaturedHazardService;
use App\Services\MapConditionService;
use App\Services\Routing\RoutingProviderInterface;
use App\Services\SpatialSql;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicMapController extends Controller
{
    private function coordinates(): string
    {
        return SpatialSql::coordinates('latitude', 'longitude');
    }

    private function activeHazards(?int $school = null, ?array $bbox = null)
    {
        $q=Hazard::select('hazards.*')->selectRaw($this->coordinates())->with('type')->withCount(['reports','confirmations'])->active()->publicListed();
        $scope=app(\App\Services\PublicMapScope::class);
        $scope->viewport($scope->apply($q,$school?School::find($school):null),$bbox);if($bbox)$q->limit(200);
        return $q->orderByDesc('last_confirmed_at')->get();
    }

    public function landing(ConditionFeedService $feed, FeaturedHazardService $featured): View
    {
        $d = request()->validate(['school' => 'nullable|string|max:100']);
        $school = $feed->school($d['school'] ?? null);

        $stats = [
            'schools' => DB::table('schools')->where('status', 'active')->where('institution_type', 'school')->count(),
            'campuses' => DB::table('schools')->where('status', 'active')->where('institution_type', 'campus')->count(),
        ];
        return view('landing', ['featured' => $featured->next($school), 'summary' => $feed->summary($school), 'area' => $feed->areaList($school), 'school' => $school, 'stats' => $stats]);
    }

    public function institutionOptions(): Response
    {
        $html = Cache::remember('landing-institution-options-v1-'.app()->getLocale(), 600, function () {
            $schools = DB::table('schools')->where('status', '!=', 'archived')->select('id', 'name', 'npsn', 'public_slug', 'institution_type', 'country_code')->orderBy('name')->get();

            return view('fragments.institution-options', ['schools' => $schools])->render();
        });

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600, s-maxage=3600']);
    }

    public function map(MapConditionService $markers): View|RedirectResponse
    {
        $input = request()->validate(['to' => 'nullable|string|max:255', 'school' => 'nullable|integer|exists:schools,id', 'hazard' => 'nullable|string|max:100']);
        $schools = DB::table('schools')->where('status', '!=', 'archived')->select('id', 'name', 'npsn', 'status', 'features','institution_type','country_code')->selectRaw($this->coordinates())->selectRaw('ST_AsGeoJSON(catchment) AS catchment_json')->orderBy('name')->get();
        $selected = (int) (request('school') ?: auth()->user()?->school_id);
        if (! $selected && ! empty($input['hazard'])) {
            $selected = (int) DB::table('schools')->whereRaw('ST_Intersects(catchment,(SELECT location FROM hazards WHERE public_slug=?))', [$input['hazard']])->orderBy('id')->value('id');
        }
        if (trim($input['to'] ?? '') !== '' && ! isset($input['school'])) {
            $normalize = fn ($text) => mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $text)));
            $needle = $normalize($input['to']);
            $matches = $schools->filter(fn ($s) => $normalize($s->name) === $needle || ($s->npsn && $normalize($s->npsn) === $needle));
            if ($matches->count() !== 1) {
                $message = $matches->count() > 1 ? 'Ada lebih dari satu institusi dengan nama ini. Pilih sekolah / kampus dari daftar peta, atau gunakan NPSN untuk sekolah.' : 'Institusi belum ditemukan di peta. Pilih sekolah / kampus terdaftar dari daftar. Kampus baru perlu ditambahkan pengelola; NPSN hanya untuk sekolah.';

                return to_route('home')->withErrors(['to' => $message])->withInput(request()->only(['to', 'from']));
            }
            $selected = (int) $matches->first()->id;
        }
        $school = $schools->firstWhere('id', $selected) ?? $schools->first();

        $bbox=$school?[max(-180,(float)$school->longitude-.08),max(-90,(float)$school->latitude-.05),min(180,(float)$school->longitude+.08),min(90,(float)$school->latitude+.05)]:null;
        return view('map', ['hazards' => $this->activeHazards($school?->id,$bbox)->take(200), 'markerPage' => $school ? $markers->page(School::findOrFail($school->id), ['limit' => 200,'bbox'=>$bbox?implode(',',$bbox):null]) : ['items' => [], 'next_cursor' => null], 'school' => $school, 'schools' => $schools]);
    }

    public function geojson(): JsonResponse
    {
        $school = request()->validate(['school' => 'nullable|integer|exists:schools,id'])['school'] ?? null;

        return response()->json(['type' => 'FeatureCollection', 'attribution' => 'RuteAman — laporan komunitas', 'fetched_at' => now()->toIso8601String(), 'features' => $this->activeHazards($school)->map(fn ($h) => [
            'type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [(float) $h->longitude, (float) $h->latitude]],
            'properties' => ['is_demo' => $h->is_demo, 'id' => $h->public_slug, 'category' => __($h->type->label), 'status' => $h->status->value, 'status_label' => $h->status_label, 'reports' => $h->reports_count, 'confirmations' => $h->confirmations_count, 'last_confirmed_at' => $h->last_confirmed_at?->toIso8601String(), 'first_reported_at' => $h->first_reported_at->toIso8601String(), 'url' => route('hazards.public', $h)],
        ])])->header('Cache-Control', 'no-store');
    }

    public function status(RoutingProviderInterface $routing): JsonResponse
    {
        return response()->json(['routing' => ['ok' => $routing->healthCheck(), 'provider' => $routing->getProviderName(), 'last_success_at' => cache('routing.last_success_at')], 'weather' => ['ok' => DB::table('weather_snapshots')->where('valid_until', '>', now())->exists(), 'last_success_at' => DB::table('weather_snapshots')->max('fetched_at')]]);
    }

    public function schools(): JsonResponse
    {
        return response()->json(['data' => DB::table('schools')->where('status', '!=', 'archived')->select('id', 'name', 'npsn', 'status', 'features','institution_type','country_code')->selectRaw($this->coordinates())->selectRaw('ST_AsGeoJSON(catchment) AS catchment_json')->orderBy('name')->get()]);
    }
    public function schoolSearch(): JsonResponse
    {
        $q = trim((string) request()->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
        $rows = DB::table('schools')->where('status', '!=', 'archived')
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('npsn', 'like', $like))
            ->orderBy('name')->limit(10)
            ->get(['id', 'name', 'npsn', 'public_slug', 'institution_type']);

        return response()->json($rows->map(fn ($s) => [
            'id' => $s->id,
            'slug' => $s->public_slug,
            'name' => $s->name,
            'meta' => $s->npsn ? 'NPSN '.$s->npsn : ($s->institution_type === 'campus' ? 'Kampus' : 'Sekolah'),
        ])->values());
    }
}
