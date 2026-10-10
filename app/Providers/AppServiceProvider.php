<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Audit SQL: catat query yang menyentuh tabel bukus / kategoris
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'bukus') || str_contains($query->sql, 'kategoris')) {
                Log::info(sprintf(
                    '[SQL AUDIT] (%.2f ms) %s | bindings: %s',
                    $query->time,
                    $query->sql,
                    json_encode($query->bindings)
                ));
            }
        });
    }
}