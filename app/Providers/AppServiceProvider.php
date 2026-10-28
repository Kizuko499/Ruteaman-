<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardConfirmation;
use App\Models\HazardReport;
use App\Models\School;
use App\Models\User;
use App\Models\Verification;
use App\Policies\AccountPolicy;
use App\Services\AccountNotificationService;
use App\Services\ReportMailNotifier;
use App\Services\AuditLogger;
use App\Services\ConditionFeedService;
use App\Services\GlobalFeatureSettings;
use App\Services\Routing\OpenRouteServiceProvider;
use App\Services\Routing\RoutingProviderInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoutingProviderInterface::class, OpenRouteServiceProvider::class);
        $this->app->scoped(GlobalFeatureSettings::class);
    }

    public function boot(): void
    {
        RateLimiter::for('guest-reports', function ($request) {
            $key = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

            return [Limit::perMinute(3)->by('guest-minute:'.$key), Limit::perDay(30)->by('guest-day:'.$key)];
        });
        foreach ([Hazard::class, HazardReport::class, HazardConfirmation::class, Verification::class, School::class] as $model) {
            $model::saved(fn () => ConditionFeedService::invalidateAfterCommit());
            $model::deleted(fn () => ConditionFeedService::invalidateAfterCommit());
        }
        Hazard::updated(function ($hazard) {
            if ($hazard->wasChanged('status') && $hazard->status->value === 'resolved' && $hazard->verifications()->latest('id')->value('decision') !== 'rejected') {
                app(AuditLogger::class)->record(null, 'hazard.resolved', 'hazard', $hazard->id);
            }
        });
        AuditLog::created(function (AuditLog $log) {
            if ($log->entity === 'hazard' && isset(AccountNotificationService::LABELS[$log->action])) {
                \Illuminate\Support\Facades\DB::afterCommit(fn () => app(ReportMailNotifier::class)->statusFromAudit($log));
            }
        });
        Gate::policy(User::class, AccountPolicy::class);
        View::composer(['components.account-menu', 'partials.mobile-nav'], function ($view) {
            $user = auth()->user();
            $view->with('accountUnread', $user ? app(AccountNotificationService::class)->unread($user) : 0);
        });
    }
}
