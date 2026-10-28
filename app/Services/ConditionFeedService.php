<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ConditionFeedService
{
    public static function activity(): string
    {
        return DB::getDriverName() === 'mysql' && ! SpatialSql::isMariaDb() ? 'hazards.activity_at' : self::ACTIVITY;
    }

    public const ACTIVITY = 'COALESCE(hazards.last_confirmed_at,hazards.first_reported_at)';

    public function school(?string $key): ?School
    {
        $key = $key ?: config('ruteaman.default_school_slug');
        if (! $key) {
            return School::where('status', '!=', 'archived')->orderBy('id')->first();
        }

        return School::where('status', '!=', 'archived')->where(function ($q) use ($key) {
            $q->where('public_slug', $key)->orWhere('npsn', $key);
            if (ctype_digit($key)) {
                $q->orWhere('id', (int) $key);
            }
        })->firstOrFail();
    }

    public function query(?School $school, string $mode = 'aktif', ?string $category = null)
    {
        // Base: seperti publicListed tapi sertakan expired yang pernah dipublikasi (untuk riwayat)
        $q = Hazard::with('type')
            ->where('publication_control', 'normal')
            ->where('requires_moderation', false)
            ->where(fn ($v) => $v->whereIn('status', ['new', 'confirmed', 'verified', 'disputed', 'resolved'])
                ->orWhere(fn ($e) => $e->where('status', 'expired')->whereNotNull('published_at')))
            ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'");
        // Samakan cakupan dengan peta publik (PublicMapScope): hormati mode shared/cross-school
        $q = app(\App\Services\PublicMapScope::class)->apply($q, $school);
        if ($category) {
            $q->whereHas('type', fn ($q) => $q->where('code', $category));
        }
        if ($mode === 'surut') {
            $q->where('status', 'resolved')->where('resolved_at', '>=', now()->subHours(config('ruteaman.resolved_hours', 24)));
        } elseif ($mode === 'semua') {
            // Semua kondisi: aktif + surut kapan pun + kedaluwarsa terpublikasi
            // (himpunan dasar kueri ini sudah mencakup ketiganya).
        } else {
            // Tampilkan aktif + riwayat (kedaluwarsa tapi pernah dipublikasi), seperti peta
            $q->where(function ($w) {
                $w->where(fn ($a) => $a->active())->orWhere(fn ($h) => $h->where('status', 'expired')->whereNotNull('published_at'));
            });
        }

        return $q->select('hazards.*')->selectRaw(self::activity().' AS activity_at')->withCount(['confirmations' => fn ($q) => $q->where('vote', 'still_there')])->withExists(['reports as has_photo' => fn ($q) => $q->whereNotNull('photo_path')])->orderByRaw(self::activity().' DESC')->orderByDesc('hazards.id');
    }

    /**
     * Kueri khusus kartu unggulan beranda: SEMUA hazard tanpa kecuali —
     * tanpa saringan publikasi, moderasi, status, keputusan verifikasi,
     * maupun cakupan institusi. Keputusan produk (25 Okt): kartu ini adalah
     * etalase seluruh laporan yang masuk; panel area dan umpan publik
     * tetap memakai query() yang tersaring.
     */
    public function allQuery()
    {
        return Hazard::with('type')
            ->select('hazards.*')
            ->selectRaw(self::activity().' AS activity_at')
            ->withCount(['confirmations' => fn ($q) => $q->where('vote', 'still_there')])
            ->withExists(['reports as has_photo' => fn ($q) => $q->whereNotNull('photo_path')])
            ->orderByRaw(self::activity().' DESC')
            ->orderByDesc('hazards.id');
    }

    public function item(Hazard $h): array
    {
        $photo = $h->has_photo ?? $h->reports()->whereNotNull('photo_path')->exists();
        $title = __($h->type->label).($h->location_label ? ', '.$h->location_label : '');

        return ['public_slug' => $h->public_slug, 'title' => $title, 'type' => $h->type->code, 'type_label' => __($h->type->label), 'type_visual' => app(ConditionIconService::class)->visual($h->type), 'location_label' => $h->location_label,
            'status' => $h->status->value, 'status_label' => $h->status_label, 'photo_url' => $photo ? route('conditions.media', ['hazard' => $h, 'index' => 0, 'w' => 640]) : null,
            'photo_fallback_url' => $photo ? route('conditions.media', ['hazard' => $h, 'index' => 0, 'w' => 640, 'format' => 'jpeg']) : null, 'alt' => __('Foto kondisi ').$title, 'age_text' => ($h->last_confirmed_at ?? $h->first_reported_at)->locale(LanguagePreferences::carbonLocale())->diffForHumans(),
            'confirmations_count' => $h->confirmations_count ?? $h->confirmations()->where('vote', 'still_there')->count(), 'is_demo' => $h->is_demo, 'detail_url' => route('conditions.show', $h)];
    }

    public function feed(?School $school, array $filters): array
    {
        $limit = min(24, max(1, (int) ($filters['limit'] ?? 12)));
        $mode = $filters['tampil'] ?? 'aktif';
        $category = $filters['kategori'] ?? null;
        $scope = hash('sha256', json_encode([$school?->id, $mode, $category]));
        $cursor = null;
        if (! empty($filters['cursor'])) {
            try {
                $cursor = json_decode(Crypt::decryptString($filters['cursor']), true, 16, JSON_THROW_ON_ERROR);
                if (($cursor['scope'] ?? null) !== $scope || ! isset($cursor['at'],$cursor['id'])) {
                    throw new \RuntimeException;
                }
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cursor' => 'Kursor tidak valid. Buka ulang daftar kondisi.']);
            }
        }

        return Cache::remember('conditions:'.app()->getLocale().':'.Cache::get('conditions.version', '0').':'.hash('sha256', json_encode([$scope, $limit, $cursor])), 30, function () use ($school, $mode, $category, $limit, $cursor, $scope) {
            $q = $this->query($school, $mode, $category);
            $total = (clone $q)->reorder()->count();
            if ($cursor) {
                $q->whereRaw('('.self::activity().' < ? OR ('.self::activity().' = ? AND hazards.id < ?))', [$cursor['at'], $cursor['at'], $cursor['id']]);
            }
            $rows = $q->limit($limit + 1)->get();
            $more = $rows->count() > $limit;
            $rows = $rows->take($limit);
            $last = $rows->last();

            return ['items' => $rows->map(fn ($h) => $this->item($h))->values()->all(), 'next_cursor' => $more ? Crypt::encryptString(json_encode(['scope' => $scope, 'at' => $last->activity_at, 'id' => $last->id])) : null, 'total_count' => $total, 'generated_at' => now()->toIso8601String()];
        });
    }

    /**
     * Kueri khusus panel ringkasan area: selalu ketat pada wilayah institusi terpilih.
     * Mode laporan lintas institusi (cross_school_public_reports) hanya berlaku untuk
     * peta/umpan publik, bukan untuk angka "dalam wilayah institusi" di panel ini.
     */
    private function areaQuery(?School $school)
    {
        $q = $this->query($school);
        if ($school) {
            $q->inSchool($school->id);
        } else {
            $q->whereRaw('1=0');
        }

        return $q;
    }

    /** Ringkasan + daftar hazard area untuk panel ringkasan area di beranda. */
    public function areaList(?School $school, int $limit = 10): array
    {
        $hazards = $this->areaQuery($school)->limit($limit)->get();

        return ['summary' => $this->summary($school), 'hazards' => $hazards->map(fn (Hazard $h) => $this->item($h))->all()];
    }

    public function summary(?School $school): array
    {
        $rows = $this->areaQuery($school)->get();

        return ['total' => $rows->count(), 'categories' => $rows->groupBy('type.code')->map(fn ($v) => ['label' => __($v->first()->type->label), 'count' => $v->count()])->all()];
    }

    public static function invalidateAfterCommit(): void
    {
        if (app()->runningUnitTests()) {
            self::invalidate();
        } else {
            DB::afterCommit(fn () => self::invalidate());
        }
    }

    public static function invalidate(): void
    {
        Cache::forever('conditions.version', (string) Str::ulid());
    }
}
