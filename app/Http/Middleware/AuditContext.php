<?php

namespace App\Http\Middleware;

use App\Exceptions\AdminException;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuditContext
{
    public function handle(Request $r, Closure $next)
    {
        $r->attributes->set('audit_request_id', (string) Str::uuid());
        try {
            $response = $next($r);
            $status = $response->getStatusCode();
            if ($r->is('admin*', 'school/users*', 'verifier*') && ! $r->attributes->get('audit_denial_recorded') && ($status === 403 || $status >= 500)) {
                try {
                    $u = $r->user();
                    app(AuditLogger::class)->record($u, $status === 403 ? 'permission.denied' : 'system.request_failed', 'request', null, ['error_code' => 'HTTP_'.$status], $u?->school_id, [], null, $status === 403 ? 'denied' : 'failed');
                    $r->attributes->set('audit_denial_recorded', true);
                } catch (\Throwable) { /* A database outage must not cause raw request logging. */
                }
            }

            return $response;
        } catch (\Throwable $e) {
            $status = $e instanceof AdminException ? $e->status : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : ($e instanceof ValidationException ? 422 : ($e instanceof AuthenticationException ? 401 : 500)));
            if ($r->is('admin*', 'school/users*', 'verifier*') && ($status === 403 || ($status !== null && $status >= 500))) {
                $u = $r->user();
                try {
                    app(AuditLogger::class)->record($u, $status === 403 ? 'permission.denied' : 'system.request_failed', 'request', null, ['error_code' => $e instanceof AdminException ? $e->errorCode : 'REQUEST_FAILED'], $u?->school_id, [], null, $status === 403 ? 'denied' : 'failed');
                    $r->attributes->set('audit_denial_recorded', true);
                } catch (\Throwable) { /* Keep the original failure; never serialize request fields to a fallback log. */
                }
            }throw $e;
        }
    }
}
