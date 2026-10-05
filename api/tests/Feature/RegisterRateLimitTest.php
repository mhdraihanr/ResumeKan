<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Register dijaga dua lapis: per menit (burst) dan per jam (akumulasi spam).
 * Tes ini memastikan keduanya benar-benar aktif dan angkanya bisa diatur lewat
 * config/security.php (env REGISTER_PER_MINUTE / REGISTER_PER_HOUR).
 */
class RegisterRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_percobaan_keenam_dalam_satu_menit_dibatasi(): void
    {
        config([
            'security.register.per_minute' => 5,
            'security.register.per_hour' => 100,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->register("user{$i}@example.com")->assertStatus(201);
        }

        $this->register('user5@example.com')->assertStatus(429);
    }

    public function test_batas_per_jam_berlaku_walau_per_menit_longgar(): void
    {
        config([
            'security.register.per_minute' => 100,
            'security.register.per_hour' => 3,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->register("jam{$i}@example.com")->assertStatus(201);
        }

        // Lapis per menit belum tersentuh, tapi lapis per jam sudah habis.
        $this->register('jam3@example.com')->assertStatus(429);
    }

    /**
     * Register butuh request stateful (sesi) — seperti browser, sertakan Origin
     * yang cocok dengan `SANCTUM_STATEFUL_DOMAINS`.
     */
    private function register(string $email)
    {
        $origin = 'http://'.collect(config('sanctum.stateful'))->first(fn ($d) => $d !== '');

        return $this->withHeaders(['Origin' => $origin])
            ->postJson('/api/v1/register', [
                'name' => 'Budi Santoso',
                'email' => $email,
                'password' => 'password-rahasia',
                'password_confirmation' => 'password-rahasia',
            ]);
    }
}
