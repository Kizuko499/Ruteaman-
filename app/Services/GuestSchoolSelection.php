<?php

namespace App\Services;

use App\Exceptions\AdminException;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GuestSchoolSelection
{
    public function nonce(Request $r): string
    {
        if (! $r->session()->has('guest_school_nonce')) {
            $r->session()->put('guest_school_nonce', Str::random(40));
        }

        return (string) $r->session()->get('guest_school_nonce');
    }

    public function ticket(Request $r, array $school): string
    {
        return Crypt::encryptString(json_encode(['school' => $school, 'nonce' => $this->nonce($r), 'expires' => now()->addHours(2)->timestamp], JSON_THROW_ON_ERROR));
    }

    public function read(Request $r, string $ticket): array
    {
        try {
            $d = json_decode(Crypt::decryptString($ticket), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($d) || ($d['expires'] ?? 0) < now()->timestamp || ! hash_equals($this->nonce($r), (string) ($d['nonce'] ?? '')) || ! is_array($d['school'] ?? null)) {
                throw new \RuntimeException;
            }

            return $d['school'];
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['school_ticket' => 'Pilihan sekolah kedaluwarsa atau tidak valid. Cari dan pilih kembali sekolah.']);
        }
    }

    public function local(School $s): array
    {
        $s = School::whereKey($s->id)->select('schools.*')->selectRaw(SpatialSql::coordinates())->selectRaw('ST_AsGeoJSON(catchment) AS catchment_json')->firstOrFail();

        return ['institution_type'=>$s->institution_type??'school','country_code'=>$s->country_code??'ID','campus_code'=>$s->campus_code,'id' => $s->id, 'npsn' => $s->npsn, 'name' => $s->name, 'province' => $s->province_name, 'regency' => $s->regency_name, 'district' => $s->district_name, 'latitude' => (float) $s->latitude, 'longitude' => (float) $s->longitude, 'catchment' => json_decode($s->catchment_json ?? 'null', true)];
    }

    public function reporting(School $s): bool
    {
        return $s->status === 'active' && ($s->features['reporting'] ?? app(GlobalFeatureSettings::class)->enabled('reporting'));
    }

    public function submit(Request $r, array $data, GuestReportService $reports): array
    {
        if (empty($data['school_ticket'])) {
            return $reports->submit($data, $r->file('photo'));
        }
        if(($data['institution_type']??'school')==='campus')throw ValidationException::withMessages(['school_ticket'=>'Kampus harus dipilih dari institusi terdaftar, bukan tiket direktori sekolah.']);
        $s = $this->read($r, $data['school_ticket']);
        $existing = School::where('npsn', $s['npsn'])->first();
        if ($existing) {
            $data['school_id'] = $existing->id;

            return $reports->submit($data, $r->file('photo'));
        }
        // Jangkauan laporan tidak dibatasi wilayah: titik mana pun diterima.
        if (! app(GlobalFeatureSettings::class)->enabled('reporting')) {
            throw ValidationException::withMessages(['school_ticket' => 'Pelaporan sedang dinonaktifkan.']);
        }
        try {
            return DB::transaction(function () use ($r, $data, $s, $reports) {
                // Serializes directory insertion, including concurrent guest submissions.
                DB::table('admin_transaction_locks')->where('id', 1)->lockForUpdate()->first();
                app(GlobalFeatureSettings::class)->forget();
                if (! app(GlobalFeatureSettings::class)->enabled('reporting')) {
                    throw ValidationException::withMessages(['school_ticket' => 'Pelaporan sedang dinonaktifkan.']);
                }
                $school = School::where('npsn', $s['npsn'])->first();
                if (! $school) {
                    $school = app(DapodikSchoolDirectory::class)->import($s['npsn'], $s['province_code'], $s['regency_code'], $s['district_code']);
                    if (! $school->public_slug) {
                        $school->forceFill(['public_slug' => 'sekolah-'.strtolower((string) Str::ulid())])->save();
                    }
                }
                $data['school_id'] = $school->id;

                return $reports->submit($data, $r->file('photo'));
            });
        } catch (ValidationException|AdminException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['school_ticket' => 'Data sekolah belum dapat diproses. Cari dan pilih kembali sekolah, lalu coba lagi.']);
        }
    }
}
