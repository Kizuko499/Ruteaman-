<?php

namespace App\Http\Controllers;

use App\Exceptions\AdminException;
use App\Models\School;
use App\Services\AdminAccessService;
use App\Services\AuditLogger;
use App\Services\DapodikSchoolDirectory;
use App\Services\GlobalFeatureSettings;
use App\Services\OwnerUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSchoolPickerController extends Controller
{
    public function search(Request $request, DapodikSchoolDirectory $directory)
    {
        app(OwnerUserService::class)->owner($request->user());
        $data = $request->validate(['q' => 'required|string|min:3|max:80']);
        $term = trim($data['q']);
        $local = School::where('status', '!=', 'archived')->where(function ($query) use ($term) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($term).'%'])->orWhere('npsn', 'like', '%'.$term.'%');
        })->orderBy('name')->limit(30)->get();
        $items = $local->map(fn ($school) => $this->item($school))->all();
        $message = null;
        $onlineCount = 0;
        try {
            $external = $directory->searchNationwide($term);
            $onlineCount = count($external);
            if ($external === []) {
                $message = 'Direktori online tidak menemukan kecocokan. Coba NPSN atau nama resmi sekolah; pencarian tidak dibatasi sekolah terdaftar.';
            }
            $known = School::whereIn('npsn', array_column($external, 'npsn'))->get()->keyBy('npsn');
            foreach ($external as $record) {
                $existing = $known->get($record['npsn']);
                if ($existing && $existing->status === 'archived') {
                    continue;
                }
                $items[] = $existing ? $this->item($existing) : [
                    'id' => null, 'name' => $record['name'], 'npsn' => $record['npsn'],
                    'region' => implode(' · ', array_filter([$record['district'] ?? '', $record['regency'] ?? '', $record['province'] ?? ''])),
                    'source' => 'directory',
                ];
            }
        } catch (\Throwable $error) {
            report($error);
            $message = 'Direktori nasional belum dapat dihubungi. Sekolah terdaftar tetap bisa dipilih; coba lagi nanti.';
        }

        return response()->json(['data' => collect($items)->unique(fn ($item) => $item['npsn'] ?: 'local:'.$item['id'])->take(30)->values(), 'message' => $message, 'online_count' => $onlineCount, 'registered_count' => $local->count(), 'online_checked' => true])->header('Cache-Control', 'private, no-store');
    }

    public function register(Request $request, DapodikSchoolDirectory $directory)
    {
        app(OwnerUserService::class)->owner($request->user());
        $data = $request->validate(['npsn' => ['required', 'regex:/^[A-Za-z0-9]{8}$/']]);
        $npsn = strtoupper($data['npsn']);
        if ($school = School::where('npsn', $npsn)->first()) {
            return $this->selected($school, false);
        }
        try {
            // Never trust school names or coordinates supplied by the browser.
            $record = $directory->guestPreview($npsn);
            [$school, $created] = DB::transaction(function () use ($request, $directory, $record, $npsn) {
                app(AdminAccessService::class)->lockOwners();
                app(OwnerUserService::class)->owner($request->user());
                if ($existing = School::where('npsn', $npsn)->first()) {
                    return [$existing, false];
                }
                $school = $directory->import($npsn, $record['province_code'], $record['regency_code'], $record['district_code']);
                $school->forceFill([
                    'status' => 'paused', 'created_by' => $request->user()->id,
                    'public_slug' => $school->public_slug ?: 'sekolah-'.strtolower((string) Str::ulid()),
                    'features' => array_replace(app(GlobalFeatureSettings::class)->defaults(), ['reporting' => false]),
                ])->save();
                app(AuditLogger::class)->record($request->user(), 'school.directory_registered', 'school', $school->id, ['npsn' => $npsn, 'source' => 'directory', 'review_required' => true], $school->id);

                return [$school, true];
            });
        } catch (AdminException $error) {
            throw $error;
        } catch (\Throwable $error) {
            report($error);

            return response()->json(['message' => 'Sekolah belum dapat didaftarkan. Periksa NPSN atau coba lagi nanti.'], 503);
        }

        return $this->selected($school, $created);
    }

    private function item(School $school): array
    {
        return ['id' => $school->id, 'name' => $school->name, 'npsn' => $school->npsn, 'region' => implode(' · ', array_filter([$school->district_name, $school->regency_name, $school->province_name])), 'source' => 'registered'];
    }

    private function selected(School $school, bool $created)
    {
        if ($school->status === 'archived') {
            return response()->json(['message' => 'Sekolah diarsipkan. Tinjau/pulihkan melalui menu Sekolah terlebih dahulu.'], 422);
        }

        return response()->json(['data' => $this->item($school), 'message' => $created ? 'Sekolah terdaftar dan dipilih. Pemantauan dijeda dan pelaporan nonaktif; tinjau pin/catchment serta fitur melalui menu Sekolah sebelum mengaktifkan.' : 'Sekolah terdaftar dipilih.'])->header('Cache-Control', 'private, no-store');
    }
}
