<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\School;
use App\Models\User;

final class AuditLogger
{
    public function redact(mixed $value, string $key = ''): mixed
    {
        $key = strtolower($key);
        foreach (config('ruteaman_audit.redact_keys', []) as $s) {
            if ($key === $s || str_contains($key, $s)) {
                return '[DISAMARKAN]';
            }
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->redact($v, (string) $k);
            }

            return $value;
        }
        if (is_string($value)) {
            $value = preg_replace('~https?://[^\s]*(?:invite|reset|token)[^\s]*~i', '[DISAMARKAN]', $value);
            $value = preg_replace('/(?:password|kata sandi|token|secret|kode gabung)\s*[:=]\s*\S+/iu', '[DISAMARKAN]', $value);

            return mb_substr($value, 0, 2000);
        }

        return is_scalar($value) || $value === null ? $value : null;
    }

    public function record(?User $actor, string $action, string $targetType, ?int $targetId, array $meta = [], ?int $school = null, array $changes = [], ?string $reason = null, string $result = 'success', ?string $category = null): AuditLog
    {
        $r = app()->bound('request') ? request() : null;
        $actor = $actor?->fresh() ?? $actor;
        $target = match ($targetType) {
            'user' => $targetId ? User::find($targetId) : null,'school' => $targetId ? School::find($targetId) : null,'hazard' => $targetId ? Hazard::find($targetId) : null,default => null
        };
        $label = match ($targetType) {
            'user' => $target?->display_name,'school' => $target?->name,'hazard' => $target?->location_label ?: ($targetId ? 'Kondisi #'.$targetId : null),default => $targetId ? $targetType.' #'.$targetId : null
        };
        $category ??= match (true) {
            str_starts_with($action, 'auth.') || str_starts_with($action, 'reauth.') => 'auth',str_contains($action, 'permission') => 'permission',str_starts_with($action, 'school.') => 'school',str_starts_with($action, 'verification.') => 'verification',str_starts_with($action, 'escalation.') => 'escalation',str_starts_with($action, 'data.') || str_contains($action, 'export') => 'data',$targetType === 'user' => 'user',$targetType === 'hazard' => 'hazard',default => 'system'
        };
        $severity = $result === 'denied' ? 'warning' : (preg_match('/deleted|anonymized|tier_changed|feature_toggled|password_set|suspended/', $action) ? 'critical' : 'info');
        $safeChanges = [];
        foreach ($changes as $key => $diff) {
            if (is_array($diff) && array_key_exists('before', $diff) && array_key_exists('after', $diff) && $diff['before'] !== $diff['after']) {
                $safeChanges[$key] = ['before' => $this->redact($diff['before'], $key), 'after' => $this->redact($diff['after'], $key)];
            }
        }
        $safeMeta = $this->redact($meta);
        $safeMeta['region_id'] = $school;
        $capture = config('ruteaman_audit.capture_context');
        $ip = $capture ? $r?->ip() : null;
        if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = null;
        }

        $data = ['actor_id' => $actor?->id, 'action' => $action, 'entity' => $targetType, 'entity_id' => $targetId, 'meta_json' => $meta === [] ? null : $safeMeta, 'created_at' => now()->utc(),
            'occurred_at' => now()->utc(), 'actor_name' => $actor?->display_name, 'actor_email' => $actor?->email, 'actor_role' => $actor?->role->value,
            'category' => $category, 'severity' => $severity, 'result' => $result, 'target_type' => $targetType, 'target_id' => $targetId, 'target_label' => $label, 'changes' => $safeChanges, 'meta' => $safeMeta,
            'reason' => $reason ? $this->redact($reason) : null, 'ip_address' => $ip, 'user_agent' => $capture && $r?->userAgent() ? mb_substr($r->userAgent(), 0, 512) : null,
            'request_id' => $r?->attributes->get('audit_request_id'), 'route_name' => $r?->route()?->getName() ?? $r?->route()?->uri(), 'http_method' => $r?->route() ? $r->method() : null];
        // region_id hanya jika kolom ada di DB (kompatibilitas skema server)
        try { if (\Illuminate\Support\Facades\Schema::hasColumn('audit_logs', 'region_id')) { $data['region_id'] = $school ?? $actor?->region_id; } } catch (\Throwable $e) {}

        return \App\Models\AuditLog::create($data);
    }
}
