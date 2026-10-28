@php
    // Snapshots are historical. For legacy entries, a current account name is
    // explicitly labelled as current and fetched only within the viewer's scope.
    $activityViewer = auth()->user();
    $activityPermissions = app(\App\Services\PermissionService::class);
    $activityIds = $logs->flatMap(function ($entry) {
        return [
            empty($entry->actor_name) ? $entry->actor_id : null,
            empty($entry->target_label) && $entry->entity === 'user' ? $entry->entity_id : null,
        ];
    })->filter()->unique()->values();
    $activityNames = collect();
    if ($activityViewer && $activityIds->isNotEmpty()) {
        $activityAccounts = \App\Models\User::query()->whereIn('id', $activityIds);
        if (!$activityPermissions->allScope($activityViewer)) {
            $activityPermissions->scope($activityAccounts, $activityViewer, 'school_id');
        }
        $activityNames = $activityAccounts->pluck('display_name', 'id');
    }
@endphp
<div class="admin-log-list">
@forelse($logs as $log)
    @php
        $activityActor = $log->actor_name ?: ($activityNames->get($log->actor_id) ?: ($log->actor_id ? __('Pelaku #').$log->actor_id : __('Pelaku tidak tercatat')));
        $activityActorCurrent = empty($log->actor_name) && $activityNames->has($log->actor_id);
        $activityTargetCurrent = empty($log->target_label) && $log->entity === 'user' && $activityNames->has($log->entity_id);
        $activityEntity = match ($log->entity) {
            'user' => __('Akun'), 'school' => __('Sekolah'), 'hazard' => 'Kondisi',
            'audit' => 'Audit', default => $log->entity ?: __('Target'),
        };
        $activityTarget = $log->target_label ?: ($activityTargetCurrent ? $activityNames->get($log->entity_id) : $activityEntity.($log->entity_id !== null ? ' #'.$log->entity_id : ''));
        $activityAt = $log->occurred_at ?? $log->created_at;
    @endphp
    <div class="admin-log-row">
        <span class="admin-log-dot" aria-hidden="true"></span>
        <div style="min-width:0;overflow-wrap:anywhere">
            <strong>{{ (config('ruteaman_audit.labels', [])[$log->action] ?? $log->action) }}</strong>
            <small>{{ __('Pelaku:') }} {{ $activityActor }}@if($activityActorCurrent) {{ __('(nama akun saat ini)') }}@endif</small>
            <small>{{ __('Target:') }} {{ $activityTarget }}@if($activityTargetCurrent) {{ __('(nama akun saat ini)') }}@endif</small>
            @if(empty($log->actor_name) || empty($log->target_label))
                <small>{{ __('Entri lama/belum lengkap — snapshot nama belum tersedia sepenuhnya.') }}</small>
            @endif
        </div>
        @if($activityAt)
            <time datetime="{{ $activityAt->toIso8601String() }}">{{ $activityAt->copy()->setTimezone('Asia/Jakarta')->format('d/m H:i') }} {{ __('WIB') }}</time>
        @endif
    </div>
@empty
    <p class="admin-empty">{{ __('Belum ada aktivitas yang dapat ditampilkan.') }}</p>
@endforelse
</div>
