<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestHazardRequest;
use App\Models\HazardType;
use App\Models\School;
use App\Services\DapodikSchoolDirectory;
use App\Services\GlobalFeatureSettings;
use App\Services\GuestReportService;
use App\Services\GuestSchoolSelection;
use App\Services\SpatialSql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GuestReportController extends Controller
{
    public function create(Request $r, GuestSchoolSelection $selection)
    {
        if ($r->user()) {
            return to_route('hazards.create');
        }
        if ($r->has('school_query') || $r->has('school_npsn') || $r->has('institution_id')) {
            return $this->schools($r, $selection);
        }
        $directorySelected = null;
        if ($ticket = $r->old('school_ticket')) {
            try {
                $directorySelected = $selection->read($r, $ticket);
            } catch (ValidationException $e) {/* Show a fresh search instead of restoring an invalid choice. */
            }
        }
        $schools = School::where('status', 'active')->select('schools.*')->selectRaw(SpatialSql::coordinates())->selectRaw('ST_AsGeoJSON(catchment) AS catchment_json')->orderBy('name')->get()->filter(fn ($s) => $s->features['reporting'] ?? app(GlobalFeatureSettings::class)->enabled('reporting'))->values();
        $selected = (string) $r->old('school_id', $r->query('school', ''));
        $formToken = Str::random(40);
        $r->session()->put('guest_report_form', ['token' => $formToken, 'started' => now()->timestamp]);

        return response()->view('hazards.guest-create', ['schools' => $schools, 'types' => HazardType::orderBy('label')->get(), 'selected' => $selected, 'formToken' => $formToken, 'directorySelected' => $directorySelected])->header('Cache-Control', 'private, no-store');
    }

    public function store(StoreGuestHazardRequest $r, GuestReportService $reports, GuestSchoolSelection $selection)
    {
        $d = $r->validated();
        $form = $r->session()->get('guest_report_form');
        if (! is_array($form) || ! hash_equals((string) ($form['token'] ?? ''), $d['_form_token'])) {
            throw ValidationException::withMessages(['_form_token' => 'Formulir sudah dikirim atau kedaluwarsa. Muat ulang sebelum mencoba lagi.']);
        }
        if (now()->timestamp - (int) ($form['started'] ?? now()->timestamp) < 2) {
            throw ValidationException::withMessages(['_form_token' => 'Tunggu sebentar sebelum mengirim laporan.']);
        }
        $receipt = $selection->submit($r, $d, $reports);
        $r->session()->forget('guest_report_form');

        return to_route('guest-reports.receipt')->with('guest_report_receipt', $receipt)->header('Cache-Control', 'private, no-store');
    }

    public function campuses(Request $r, GuestSchoolSelection $selection)
    {
        $r->merge(['institution_type' => 'campus']);
        return $this->schools($r, $selection);
    }

    private function schools(Request $r, GuestSchoolSelection $selection)
    {
        $key = 'guest-school-search:'.hash('sha256', $r->ip());
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['data' => [], 'message' => 'Terlalu banyak pencarian. Tunggu satu menit.'], 429)->header('Retry-After', '60');
        }
        RateLimiter::hit($key, 60);
        if($r->has('institution_id')){
            $d=$r->validate(['institution_id'=>'required|integer|exists:schools,id','institution_type'=>'sometimes|in:school,campus']);$local=School::findOrFail($d['institution_id']);
            if(!$selection->reporting($local)||isset($d['institution_type'])&&($local->institution_type??'school')!==$d['institution_type'])return response()->json(['message'=>'Institusi tidak tersedia untuk pelaporan.'],422);
            return response()->json(['data'=>$selection->local($local)])->header('Cache-Control','private, no-store');
        }
        if ($r->has('school_npsn')) {
            if ($r->input('institution_type') === 'campus') return response()->json(['message'=>'NPSN hanya untuk sekolah. Cari kampus dengan nama atau kode PT.'],422);
            $d = $r->validate(['school_npsn' => ['required', 'regex:/^[A-Za-z0-9]{8}$/']]);
            $npsn = strtoupper($d['school_npsn']);
            $local = School::where('npsn', $npsn)->first();
            if ($local) {
                if (! $selection->reporting($local)) {
                    return response()->json(['message' => 'Pelaporan sekolah ini dinonaktifkan atau sekolah dijeda.'], 422);
                }

return response()->json(['data' => $selection->local($local)])->header('Cache-Control', 'private, no-store');
            }
            if (! app(GlobalFeatureSettings::class)->enabled('reporting')) {
                return response()->json(['message' => 'Pelaporan sedang dinonaktifkan.'], 422);
            }
            try {
                $school = app(DapodikSchoolDirectory::class)->guestPreview($npsn);

                return response()->json(['data' => $school, 'ticket' => $selection->ticket($r, $school)])->header('Cache-Control', 'private, no-store');
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Direktori sekolah tidak dapat dihubungi. Coba lagi.'], 503);
            }
        }
        $d = $r->validate(['school_query' => ['required', 'string', 'min:3', 'max:80'],'institution_type'=>['sometimes','in:school,campus']]);
        $kind=$d['institution_type']??'school';
        $term = trim($d['school_query']);
        $local = School::where('status', 'active')->where('institution_type',$kind)->where(function ($q) use ($term) {
            $like='%'.strtr(mb_strtolower($term),['~'=>'~~','%'=>'~%','_'=>'~_']).'%';
            $q->whereRaw("LOWER(name) LIKE ? ESCAPE '~'", [$like])->orWhereRaw("LOWER(campus_code) LIKE ? ESCAPE '~'",[$like])->orWhereRaw("LOWER(npsn) LIKE ? ESCAPE '~'",[$like]);
            if (\Illuminate\Support\Facades\Schema::hasTable('campus_catalog')) $q->orWhereExists(function ($catalog) use ($like) {
                $catalog->selectRaw('1')->from('campus_catalog')->whereRaw("campus_catalog.search_text LIKE ? ESCAPE '~'",[$like])->where(function ($linked) {
                    $linked->whereColumn('campus_catalog.school_id','schools.id')->orWhere(function ($exact) {
                        $exact->whereNull('campus_catalog.school_id')->whereColumn('campus_catalog.official_code','schools.campus_code')->whereColumn('campus_catalog.country_code','schools.country_code')
                        ->whereRaw("(SELECT COUNT(*) FROM schools AS matches WHERE matches.institution_type = 'campus' AND matches.status != 'archived' AND matches.campus_code = campus_catalog.official_code AND matches.country_code = campus_catalog.country_code) = 1");
                    });
                });
            });
        })->orderBy('name')->limit(30)->get()->filter(fn ($s) => $selection->reporting($s))->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'npsn' => $s->npsn, 'province' => $s->province_name, 'regency' => $s->regency_name, 'district' => $s->district_name, 'source' => 'registered','institution_type'=>$s->institution_type,'country_code'=>$s->country_code,'campus_code'=>$s->campus_code])->values()->all();
        if($kind==='campus')return response()->json(['data'=>$local,'message'=>$local?null:'Kampus belum ditemukan. Hanya kampus terdaftar dengan pelaporan aktif yang dapat dipilih. Minta pengelola menambahkan kampus.'])->header('Cache-Control','private, no-store');
        $warning = null;
        $external = [];
        try {
            $external = app(DapodikSchoolDirectory::class)->searchNationwide($term);
        } catch (\Throwable $e) {
            report($e);
            $warning = 'Direktori nasional sedang tidak dapat dihubungi. Sekolah terdaftar tetap bisa dipilih. Coba lagi atau cari dengan NPSN.';
        }
        $known = School::whereIn('npsn', array_column($external, 'npsn'))->get()->keyBy('npsn');
        $external = array_values(array_filter($external, fn ($s) => ! isset($known[$s['npsn']]) || $selection->reporting($known[$s['npsn']])));
        $items = collect([...$local, ...$external])->unique(fn ($s) => $s['npsn'] ?: 'local:'.$s['id'])->take(30)->values()->all();

        return response()->json(['data' => $items, 'message' => $warning])->header('Cache-Control', 'private, no-store');
    }

    public function receipt(Request $r)
    {
        $receipt = $r->session()->get('guest_report_receipt');
        if (! is_array($receipt)) {
            return to_route('guest-reports.create');
        }

        return response()->view('hazards.guest-receipt',compact('receipt'))->header('Cache-Control','private, no-store');
    }
}
