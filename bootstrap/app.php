<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the local reverse proxy (e.g. a Cloudflare Tunnel or Hostinger's
        // proxy) so X-Forwarded-Proto is honored and https:// URLs/assets are
        // generated correctly instead of defaulting to http://.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'client' => \App\Http\Middleware\EnsureUserIsClient::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();
