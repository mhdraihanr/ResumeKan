<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Melindungi endpoint register dari bot: honeypot, anti submit instan, dan
 * verifikasi Turnstile. Verifikasi Cloudflare di-fake agar tes tidak butuh
 * jaringan.
 */
class RegisterSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_mengembalikan_site_key_dan_spam_token(): void
    {
        config(['services.turnstile.site_key' => 'site-key-publik']);

        $this->getJson('/api/v1/config')
            ->assertOk()
            ->assertJsonPath('turnstile_site_key', 'site-key-publik')
            ->assertJsonStructure(['turnstile_site_key', 'spam_token']);
    }

    public function test_honeypot_terisi_ditolak(): void
    {
        $this->register([
            'website' => 'http://spam.example.com',
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_submit_instan_ditolak(): void
    {
        // Belum ada jeda: form diisi dan dikirim dalam sekejap.
        $this->register([
            'spam_token' => $this->spamToken(),
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_normal_lolos_saat_turnstile_belum_dikonfigurasi(): void
    {
        $token = $this->spamToken();
        $this->travel(3)->seconds();

        $this->register(['spam_token' => $token])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_token_turnstile_valid_lolos(): void
    {
        $this->fakeCloudflare(true);
        $token = $this->spamToken();
        $this->travel(3)->seconds();

        $this->register([
            'spam_token' => $token,
            'cf-turnstile-response' => 'token-valid',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_token_turnstile_ditolak_cloudflare(): void
    {
        $this->fakeCloudflare(false);
        $token = $this->spamToken();
        $this->travel(3)->seconds();

        $this->register([
            'spam_token' => $token,
            'cf-turnstile-response' => 'token-palsu',
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_token_turnstile_hilang_ditolak_saat_secret_terpasang(): void
    {
        $this->fakeCloudflare(true);
        $token = $this->spamToken();
        $this->travel(3)->seconds();

        $this->register(['spam_token' => $token])->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Register butuh request stateful (sesi) — seperti browser, sertakan Origin
     * yang cocok dengan `SANCTUM_STATEFUL_DOMAINS`.
     */
    private function register(array $overrides)
    {
        $origin = 'http://'.collect(config('sanctum.stateful'))->first(fn ($d) => $d !== '');

        return $this->withHeaders(['Origin' => $origin])
            ->postJson('/api/v1/register', $this->payload($overrides));
    }

    private function fakeCloudflare(bool $success): void
    {
        config(['services.turnstile.secret_key' => 'secret-uji']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => $success], 200),
        ]);
    }

    private function spamToken(): string
    {
        return (string) $this->getJson('/api/v1/config')->json('spam_token');
    }

    /** @param array<string, string> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
        ], $overrides);
    }
}
