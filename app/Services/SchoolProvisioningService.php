<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SchoolProvisioningService
{
    public function __construct(private PermissionService $permissions, private AdminAccountService $accounts, private AdminAuditService $audit) {}

    public function geometry(array $d): array
    {
        $lng = (float) $d['longitude'];
        $lat = (float) $d['latitude'];
        $validated = app(GeoGeometry::class)->polygon($d['catchment'], $lng, $lat);

        return [$validated['wkt'], $lng, $lat];
    }

    public function create(User $actor, array $data): array
    {
        $this->permissions->require($actor, 'schools.provision');
        if (! $this->permissions->allScope($actor)) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Sekolah baru hanya dapat dibuat oleh admin bercakupan semua sekolah.');
        }
        app(ReportPublicationPolicy::class)->validateSettings(array_replace(app(GlobalFeatureSettings::class)->defaults(), $data['features'] ?? []));
        [$wkt,$lng,$lat] = $this->geometry($data);
        [$school,$user] = DB::transaction(function () use ($actor, $data, $wkt, $lng, $lat) {
            app(AdminAccessService::class)->lockOwners();
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($actor, 'schools.provision');
            $catalog=null;
            if(!empty($data['catalog_id'])){
                $catalog=\App\Models\CampusCatalogEntry::whereKey($data['catalog_id'])->lockForUpdate()->firstOrFail();
                if($catalog->school_id)throw \Illuminate\Validation\ValidationException::withMessages(['catalog_id'=>'Entri katalog sudah terhubung. Tidak membuat kampus duplikat.']);
                if(($data['institution_type']??'school')!=='campus')throw \Illuminate\Validation\ValidationException::withMessages(['institution_type'=>'Entri katalog hanya dapat diaktifkan sebagai kampus.']);
                $data['name']=$catalog->name;$data['country_code']=$catalog->country_code;$data['campus_code']=$catalog->official_code;
            }
            $id = DB::table('schools')->insertGetId(['institution_type'=>$data['institution_type']??'school','country_code'=>strtoupper($data['country_code']??'ID'),'campus_code'=>($data['institution_type']??'school')==='campus'?($data['campus_code']??null):null,'name' => $data['name'], 'public_slug' => $data['public_slug'], 'bmkg_area_code' => $data['bmkg_area_code']??null, 'timezone' => $data['timezone'], 'location' => SpatialSql::point($lng, $lat), 'catchment' => SpatialSql::expression($wkt), 'status' => 'active', 'features' => json_encode(array_replace(app(GlobalFeatureSettings::class)->defaults(), $data['features'] ?? [])), 'settings' => '{}', 'created_by' => $actor->id]);
            $school = School::findOrFail($id);
            if($catalog){$catalog->school_id=$id;$catalog->save();}
            $user = $this->accounts->create($actor, [...$data, 'school_id' => $id], true);
            $this->audit->write($actor, 'school.created', 'school', $id, [], $id);

            return [$school, $user];
        });
        $this->invalidate();

        return ['school' => $school, 'user' => $user, ...(($data['creation_method'] ?? 'invite') === 'password_set' ? ['url' => null, 'mail_sent' => false] : $this->accounts->issue($actor, $user, false, true))];
    }

    public function update(User $actor, School $school, array $d): void
    {
        $geometry = isset($d['catchment']) ? $this->geometry($d) : null;
        DB::transaction(function () use ($actor, $school, $d, $geometry) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($u, 'schools.manage', $s, true);
            if ($u->role === Role::SchoolAdmin) {
                unset($d['public_slug']);
            }$values = array_intersect_key($d, array_flip(['name', 'public_slug', 'bmkg_area_code', 'timezone', 'address','country_code','campus_code']));
            if(($s->institution_type??'school')!=='campus')unset($values['campus_code'],$values['country_code']);
            $diff = [];
            foreach ($values as $key => $value) {
                $diff[$key] = ['before' => $s->$key, 'after' => $value];
            }$s->forceFill($values)->save();
            if ($geometry) {
                [$wkt,$lng,$lat] = $geometry;
                DB::table('schools')->where('id', $s->id)->update(['location' => SpatialSql::point($lng, $lat), 'catchment' => SpatialSql::expression($wkt)]);
            }
            $this->audit->write($u, 'school.updated', 'school', $s->id, [], $s->id, $diff, request()->input('reason'));
        });
        $this->invalidate();
    }

    /**
     * Membuat akun pengelola (Admin Sekolah/Kampus) untuk institusi yang
     * SUDAH ada — misalnya hasil impor yang belum punya akun sama sekali.
     * Mengikuti jalur provisioning yang sama dengan wizard pembuatan
     * institusi: undangan sekali tampil, izin diberikan lewat PermissionService.
     */
    public function createAccount(User $actor, School $school, array $data): array
    {
        $this->permissions->require($actor, 'schools.provision');
        if (! $this->permissions->allScope($actor)) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Akun pengelola institusi hanya dibuat oleh admin bercakupan semua sekolah.');
        }
        $user = DB::transaction(function () use ($actor, $school, $data) {
            app(AdminAccessService::class)->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $u = $this->accounts->create($a, [...$data, 'school_id' => $s->id, 'role' => 'school_admin'], true);
            $this->audit->write($a, 'school.account_created', 'school', $s->id, [], $s->id);

            return $u;
        });
        $this->invalidate();

        return ['school' => $school, 'user' => $user, ...$this->accounts->issue($actor, $user, false, true)];
    }

    /**
     * Hapus permanen institusi (untuk membersihkan duplikat). Berpengaman:
     * ditolak bila masih ada akun terkait atau laporan terkait, agar tidak
     * ada data yatim; jepretan identitas institusi disimpan ke log audit
     * sebelum record-nya hilang. Hanya tingkat platform.
     */
    public function delete(User $actor, School $school): void
    {
        DB::transaction(function () use ($actor, $school) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($u, 'schools.manage', $s, true);
            if (! in_array($u->role, [Role::Owner, Role::PlatformAdmin], true)) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Penghapusan institusi hanya untuk tingkat platform.');
            }
            $accounts = User::where('school_id', $s->id)->count();
            if ($accounts > 0) {
                throw new AdminException('SCHOOL_HAS_ACCOUNTS', 'Tidak dapat dihapus: masih ada '.$accounts.' akun terkait institusi ini. Pindahkan atau hapus akunnya dulu — atau cukup arsipkan institusinya.');
            }
            $reports = DB::table('hazard_reports')->where('school_id', $s->id)->count();
            if ($reports > 0) {
                throw new AdminException('SCHOOL_HAS_REPORTS', 'Tidak dapat dihapus: masih ada '.$reports.' laporan terkait institusi ini. Arsipkan saja agar riwayat laporannya terjaga.');
            }
            $snapshot = ['name' => $s->name, 'public_slug' => $s->public_slug, 'npsn' => $s->npsn, 'status' => $s->status];
            $s->delete();
            $this->audit->write($u, 'school.deleted', 'school', $school->id, $snapshot, null, ['name' => ['before' => $snapshot['name'], 'after' => null]], request()->input('reason'));
        });
        $this->invalidate();
    }

    public function status(User $actor, School $school, string $status): void
    {
        DB::transaction(function () use ($actor, $school, $status) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($u, 'schools.manage', $s, true);
            if (! in_array($u->role, [Role::Owner, Role::PlatformAdmin], true)) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Status sekolah hanya dikelola tingkat platform.');
            }$old = $s->status;
            $s->forceFill(['status' => $status])->save();
            $this->audit->write($u, 'school.'.match ($status) {
                'paused' => 'paused','archived' => 'archived',default => 'restored'
            }, 'school', $s->id, ['old_status' => $old, 'new_status' => $status], $s->id, ['status' => ['before' => $old, 'after' => $status]], request()->input('reason'));
        });
        $this->invalidate();
    }

    public function features(User $actor, School $school, array $features): void
    {
        DB::transaction(function () use ($actor, $school, $features) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($u, 'schools.features', $s, true);
            $old = array_replace(app(GlobalFeatureSettings::class)->defaults(), $s->features ?? []);
            app(ReportPublicationPolicy::class)->validateSettings(array_replace($old, $features));
            $s->forceFill(['features' => array_replace($old, $features)])->save();
            foreach ($features as $key => $on) {
                if ($old[$key] !== $on) {
                    $this->audit->write($u, 'school.feature_toggled', 'school', $s->id, ['feature' => $key, 'enabled' => $on], $s->id, ['features.'.$key => ['before' => $old[$key], 'after' => $on]], request()->input('reason'));
                }
            }
        });
        $this->invalidate();
    }

    public function all(User $actor, array $features): void
    {
        DB::transaction(function () use ($actor, $features) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $this->permissions->require($u, 'schools.features');
            if (! $this->permissions->allScope($u)) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Penerapan massal memerlukan cakupan semua sekolah.');
            }foreach (School::orderBy('id')->lockForUpdate()->get() as $s) {
                $this->features($u, $s, $features);
            }
        });
    }

    private function invalidate(): void
    {
        ConditionFeedService::invalidateAfterCommit();
        foreach (['schools.index', 'schools.map'] as $key) {
            Cache::forget($key);
        }
    }
}
