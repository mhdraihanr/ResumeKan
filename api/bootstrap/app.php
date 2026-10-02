<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only: tidak ada route bernama `login`. Laravel mengisi
        // `redirectGuestsTo(fn () => route('login'))` secara default di
        // ApplicationBuilder::withMiddleware(), dan callback itu DIPANGGIL dari
        // dalam Authenticate::redirectTo() -> melempar RouteNotFoundException
        // SEBELUM exception sempat dirender -> 500 "Route [login] not defined."
        // (bukan 401). Ini ketrigger tiap request TANPA cookie session, mis. tab
        // browser membuka /api/v1/cvs/{id}/pdf langsung. Karena API selalu balas
        // JSON, guest tidak pernah perlu di-redirect: kembalikan null.
        $middleware->redirectGuestsTo(fn () => null);

        // Caddy menangani HTTPS di depan dan meneruskan permintaan sebagai HTTP
        // biasa. Tanpa baris ini Laravel mengira situsnya HTTP, sehingga tautan
        // dan cookie jadi http:// dan login ditolak di produksi.
        // Aman di lokal: tidak berpengaruh kalau tidak ada proxy.
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Jaring pengaman: walaupun redirectGuestsTo sudah dinetralkan, pastikan
        // AuthenticationException selalu jadi 401 JSON, bukan halaman redirect.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return null;
        });
    })->create();
