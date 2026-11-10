<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pemanggil SOAP adalah mesin, bukan form browser: bebaskan dari cek CSRF
        $middleware->validateCsrfTokens(except: [
            'soap/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 1. Semua rute /api/* selalu dijawab JSON, bukan halaman HTML / redirect 302
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 2. Satu pintu: SEMUA galat di /api/* diubah ke format RFC 7807
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null; // halaman web Blade Minggu 6 tetap memakai penanganan bawaan
            }

            // Tentukan status, slug, detail, dan rincian errors per jenis galat
            [$status, $slug, $detail, $errors] = match (true) {
                $e instanceof ValidationException => [
                    422,
                    'validation-failed',
                    'Satu atau lebih atribut input tidak memenuhi aturan validasi.',
                    $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    401,
                    'unauthenticated',
                    'Akses ditolak. Sertakan Bearer Token yang valid pada header Authorization.',
                    [],
                ],
                // Mencakup 403, 404 (termasuk ModelNotFound), 405, 429, dll.
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(),
                    match ($e->getStatusCode()) {
                        403     => 'forbidden',
                        404     => 'endpoint-not-found',
                        405     => 'method-not-allowed',
                        429     => 'too-many-requests',
                        default => 'http-error',
                    },
                    match ($e->getStatusCode()) {
                        403     => 'Anda tidak memiliki hak akses ke resource ini.',
                        404     => 'Alamat URI endpoint atau resource yang diminta tidak ditemukan.',
                        405     => "Metode {$request->method()} tidak diizinkan pada endpoint ini.",
                        429     => 'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.',
                        default => $e->getMessage() ?: 'Permintaan tidak dapat diproses.',
                    },
                    [],
                ],
                // Bug kode / database putus: jangan bocorkan detail kecuali mode debug
                default => [
                    500,
                    'internal-server-error',
                    config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
                    [],
                ],
            };

            $problem = [
                'type'     => url("/docs/errors/{$slug}"),
                'title'    => SymfonyResponse::$statusTexts[$status] ?? 'Error',
                'status'   => $status,
                'detail'   => $detail,
                'instance' => $request->getRequestUri(),
            ];

            if (!empty($errors)) {
                $problem['errors'] = $errors;
            }

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];
            $headers['Content-Type'] = 'application/problem+json';

            return response()->json($problem, $status, $headers);
        });
    })->create();