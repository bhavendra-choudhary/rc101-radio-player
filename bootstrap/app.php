<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(\App\Http\Middleware\Utf8Response::class);
        $middleware->web(\App\Http\Middleware\DeviceTokenAuthMiddleware::class);
        
        // Trust all proxies (Railway, Heroku, etc.)
        $middleware->trustProxies(at: '*');

        // Exclude logout routes from CSRF — logout is safe without a token
        $middleware->validateCsrfTokens(except: [
            'logout',
            '/logout',
            'admin/logout',
            '/admin/logout',
            'user/logout',
            '/user/logout',
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
            'device.auth' => \App\Http\Middleware\DeviceTokenAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            $isAdmin = $request->is('admin*') || str_contains($request->headers->get('referer', ''), '/admin');
            $redirectUrl = $isAdmin ? route('admin.login') : (route('user.login') ?? url('/login'));

            if ($request->expectsJson() || $request->ajax() || $request->header('HX-Request')) {
                return response()->json([
                    'message' => 'Your session has expired. Please log in again.',
                    'redirect' => $redirectUrl
                ], 401);
            }

            return redirect()->to($redirectUrl)->with('error', 'Your session has expired. Please log in again.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                $isAdmin = $request->is('admin*') || str_contains($request->headers->get('referer', ''), '/admin');
                $redirectUrl = $isAdmin ? route('admin.login') : (route('user.login') ?? url('/login'));

                if ($request->expectsJson() || $request->ajax() || $request->header('HX-Request')) {
                    return response()->json([
                        'message' => 'Your session has expired. Please log in again.',
                        'redirect' => $redirectUrl
                    ], 401);
                }

                return redirect()->to($redirectUrl)->with('error', 'Your session has expired. Please log in again.');
            }
        });
    })->create();
