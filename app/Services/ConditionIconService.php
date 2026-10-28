<?php

namespace App\Services;

use App\Models\HazardType;
use Illuminate\Support\Facades\Storage;

final class ConditionIconService
{
    public function visual(HazardType $type): array
    {
        $aliases = ['pothole' => 'road_damage', 'damaged_road' => 'road_damage', 'poor_lighting' => 'street_light', 'road_closure' => 'road_closed'];
        $key = $type->icon_key ?? ($aliases[$type->code] ?? $type->code);
        $catalog = config('condition_icons');
        if (! isset($catalog[$key])) {
            $key = 'warning';
        }

        return ['icon_key' => $key, 'icon_url' => $type->icon_path ? route('condition-types.icon', ['type' => $type, 'v' => basename($type->icon_path, '.webp')]) : null];
    }

    public function catalog(): array
    {
        return HazardType::orderBy('label')->get()->map(fn ($t) => ['code' => $t->code, 'label' => __($t->label), ...$this->visual($t)])->all();
    }

    public function image(HazardType $type)
    {
        $path = $type->icon_path;
        abort_unless(is_string($path) && preg_match('#^condition-icons/[a-f0-9-]{36}\.webp$#', $path), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path))->header('Content-Type', 'image/webp')->header('Cache-Control', 'no-store, private')->header('X-Content-Type-Options', 'nosniff');
    }
}
