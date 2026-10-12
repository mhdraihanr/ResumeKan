<?php

namespace Tests\Unit;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Limiter `ai-parse` dipakai endpoint import CV. Test ini menjaga limiter
 * benar-benar terdaftar dan angkanya bisa diubah lewat config, tanpa memanggil
 * endpoint aslinya (itu tugas test Feature di Task 4).
 */
class AiParseRateLimiterTest extends TestCase
{
    public function test_limiter_ai_parse_terdaftar(): void
    {
        $this->assertNotNull(RateLimiter::limiter('ai-parse'));
    }

    public function test_batas_default_tiga_per_menit_per_user(): void
    {
        $limit = $this->limitUntukUser(7);

        $this->assertSame(3, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    public function test_kunci_memakai_id_user(): void
    {
        $limit = $this->limitUntukUser(42);

        $this->assertStringContainsString('42', $limit->key);
    }

    public function test_user_berbeda_punya_kunci_berbeda(): void
    {
        $a = $this->limitUntukUser(1);
        $b = $this->limitUntukUser(2);

        $this->assertNotSame($a->key, $b->key);
    }

    public function test_batas_bisa_diubah_lewat_config(): void
    {
        config()->set('ai.parse_throttle_per_minute', 9);

        $this->assertSame(9, $this->limitUntukUser(1)->maxAttempts);
    }

    private function limitUntukUser(int $userId): Limit
    {
        $request = Request::create('/api/v1/ai/parse-cv', 'POST');
        $request->setUserResolver(fn () => (object) ['id' => $userId]);

        $limiter = RateLimiter::limiter('ai-parse');

        return $limiter($request);
    }
}
