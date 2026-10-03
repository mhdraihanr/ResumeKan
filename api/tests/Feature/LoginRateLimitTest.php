<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kunci rate limit login dulu memakai `$request->ip()` yang berubah tiap request
 * di Railway sehingga hitungan tidak menumpuk; tes ini menjaga perbaikannya.
 */
class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_percobaan_keenam_dalam_satu_menit_dibatasi(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginGagal('budi@example.com')->assertStatus(422);
        }

        $this->loginGagal('budi@example.com')->assertStatus(429);
    }

    public function test_x_real_ip_berbeda_punya_batas_terpisah(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginGagal('siti@example.com', '8.8.8.8')->assertStatus(422);
        }

        // IP lain belum menyentuh batas, jadi masih diproses sebagai 422.
        $this->loginGagal('siti@example.com', '9.9.9.9')->assertStatus(422);
    }

    public function test_x_real_ip_privat_diabaikan(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginGagal('andi@example.com', '100.64.0.' . ($i + 1))->assertStatus(422);
        }

        // Nilai privat/CGNAT tidak dipercaya, jadi hitungan tetap menumpuk.
        $this->loginGagal('andi@example.com', '100.64.0.99')->assertStatus(429);
    }

    private function loginGagal(string $email, ?string $realIp = null)
    {
        $headers = $realIp ? ['X-Real-IP' => $realIp] : [];

        return $this->withHeaders($headers)
            ->postJson('/api/v1/login', ['email' => $email, 'password' => 'salah-sekali']);
    }
}
