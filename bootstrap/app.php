<?php

use App\Http\Middleware\AdminSession;
use App\Http\Middleware\AuditContext;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\MinimumAdminTier;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequireReauth;
use App\Http\Middleware\RequireSchoolFeature;
use App\Http\Middleware\SetAppLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetAppLocale::class, AuditContext::class, AuthenticateSession::class, AdminSession::class]);
        $middleware->alias([
            'permission' => RequirePermission::class, 'role.min' => MinimumAdminTier::class, 'reauth' => RequireReauth::class, 'school.feature' => RequireSchoolFeature::class,
            'role' => EnsureRole::class,
            'active.account' => EnsureActiveAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'initial_password', 'initial_password_confirmation', 'delete_password', 'delete_token']);
        $exceptions->render(function (Throwable $error, Request $request) {
            if (! $request->expectsJson() || ! ($request->is('account*', 'api/account', 'school/users*', 'email/verification-notification', 'admin*', 'invite*'))) {
                return null;
            }
            $fields = $error instanceof ValidationException ? $error->errors() : [];
            $status = match (true) {
                $error instanceof ValidationException => 422,$error instanceof AuthenticationException => 401,$error instanceof AuthorizationException => 403,$error instanceof HttpExceptionInterface => $error->getStatusCode(),default => 500
            };
            $code = match ($status) {
                401 => 'SESSION_EXPIRED',403 => 'FORBIDDEN_SCOPE',404 => 'NOT_FOUND',419 => 'SESSION_EXPIRED',422 => $request->is('admin*', 'invite*') ? 'VALIDATION_FAILED' : 'VALIDATION_ERROR',429 => 'RATE_LIMITED',default => 'REQUEST_FAILED'
            };
            $message = match ($status) {
                401,419 => 'Sesi berakhir. Masuk kembali untuk melanjutkan.',403 => 'Kamu tidak memiliki izin untuk tindakan ini.',422 => 'Periksa kembali isian yang ditandai.',429 => 'Terlalu banyak percobaan. Tunggu lalu coba lagi.',default => 'Permintaan tidak dapat diselesaikan. Coba lagi.'
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message, 'field_errors' => $fields], 'message' => $message], $status);
        });
        $exceptions->render(function (ThrottleRequestsException $error, Request $request) {
            $headers = $error->getHeaders();
            $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
            $authRoutes = ['register' => 'register', 'login' => 'login', 'forgot-password' => 'password.request'];
            if ($request->isMethod('post') && isset($authRoutes[$request->path()])) {
                $action = $request->is('register') ? 'daftar' : ($request->is('login') ? 'masuk' : 'reset kata sandi');
                $message = "Batas percobaan {$action} tercapai. Tunggu sekitar {$seconds} detik, lalu coba lagi. Kata sandi perlu diisi ulang.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message, 'retry_after' => $seconds], 429, $headers);
                }

                return redirect()->route($authRoutes[$request->path()])
                    ->withErrors(['rate_limit' => $message])
                    ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                    ->withHeaders($headers);
            }
            if ($request->is('api/*')) {
                return response()->json(['message' => "Permintaan terlalu sering. Tunggu sekitar {$seconds} detik, lalu coba lagi.", 'retry_after' => $seconds], 429, $headers);
            }

            return null;
        });
    })
    ->create();
