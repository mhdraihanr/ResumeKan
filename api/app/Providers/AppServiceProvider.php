<?php

namespace App\Providers;

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
        // Di produksi Caddy yang mengakhiri HTTPS. Tanpa ini URL yang
        // di-generate Laravel memakai http:// sehingga cookie sesi ditolak.
        // Hanya aktif saat production, jadi tidak mengubah perilaku lokal.
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
