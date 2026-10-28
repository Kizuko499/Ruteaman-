<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ignore historical account/browser preferences. No bulk user updates.
        config(['app.locale' => 'id', 'app.fallback_locale' => 'id']);
        app()->setLocale('id');
        app('translator')->setFallback('id');

        $response = $next($request);
        $response->headers->set('Content-Language', 'id');
        $response->headers->set('Vary', 'Cookie', false);
        if ($request->cookie('ruteaman_locale') !== 'id') {
            $response->headers->setCookie(cookie('ruteaman_locale', 'id', 525600, '/', null, $request->isSecure(), true, false, 'lax'));
        }

        return $response;
    }
}
