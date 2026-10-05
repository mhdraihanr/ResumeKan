<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
            URL::forceScheme('https');
        }

        // Rate limit endpoint auth. Tanpa ini brute-force password dan
        // enumerasi akun tidak dibatasi sama sekali (tidak ada throttle global
        // di grup `api`). Login dibatasi per email+IP supaya satu IP yang
        // menyerang banyak akun tetap kena, dan satu akun yang diserang dari
        // banyak IP juga kena.
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                // Batas utama per email+IP; IP stabil berkat ResolveClientIp.
                Limit::perMinute(5)->by($email . '|' . $request->ip()),
                // Cadangan per email, supaya serangan dari banyak IP tetap dibatasi.
                Limit::perMinute(10)->by('email:' . $email),
            ];
        });

        // Register dibatasi per IP karena belum ada email untuk dijadikan kunci.
        // Dua lapis: `per_minute` menahan burst, `per_hour` menahan akumulasi
        // spam. Nilainya dari config/security.php agar bisa diubah lewat .env.
        // `by` diberi prefix unik supaya kedua limit tidak berbagi key.
        RateLimiter::for('register', function (Request $request) {
            $ip = $request->ip();

            return [
                Limit::perMinute((int) config('security.register.per_minute', 5))
                    ->by('register:minute:' . $ip),
                Limit::perHour((int) config('security.register.per_hour', 20))
                    ->by('register:hour:' . $ip),
            ];
        });

        // Endpoint AI memanggil provider berbayar, jadi dibatasi per user
        // (bukan per IP) supaya kuota tidak habis karena satu akun. Nilainya
        // dari config('ai.throttle_per_minute') agar bisa diubah lewat .env.
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute((int) config('ai.throttle_per_minute', 5))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
