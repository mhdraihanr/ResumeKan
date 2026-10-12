<?php

namespace Tests\Unit;

use App\Services\AiService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ringkasan AI memakai gateway yang sama dengan strukturisasi CV. Test di sini
 * mengunci bentuk payload-nya saja — isi ringkasan adalah urusan prompt, bukan
 * unit test.
 */
class AiServiceSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.api_key' => 'kunci-uji',
            'ai.base_url' => 'https://ai.test/v1',
            'ai.model' => 'model-uji',
            'ai.timeout' => 30,
        ]);
    }

    private function fakeRingkasan(string $teks = 'Backend engineer dengan pengalaman API.'): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $teks]]],
            ], 200),
        ]);
    }

    public function test_instruksi_ringkasan_dikirim_di_pesan_user_bukan_system(): void
    {
        // Alasannya sama dengan structureCvData: gateway lokal membuang pesan
        // `system` untuk model deepseek/glm, jadi instruksi wajib ikut di
        // pesan `user`.
        $this->fakeRingkasan();

        (new AiService())->generateSummary(['personal' => ['name' => 'Budi Santoso']]);

        Http::assertSent(function (ClientRequest $request) {
            $messages = $request->data()['messages'] ?? [];

            $this->assertCount(1, $messages);
            $this->assertSame('user', $messages[0]['role']);
            $this->assertStringContainsString('ATS', $messages[0]['content']);
            $this->assertStringContainsString('Budi Santoso', $messages[0]['content']);

            return true;
        });
    }

    public function test_ringkasan_dipotong_600_karakter(): void
    {
        $this->fakeRingkasan(str_repeat('a', 700));

        $hasil = (new AiService())->generateSummary(['personal' => ['name' => 'Budi']]);

        $this->assertSame(600, mb_strlen($hasil));
        $this->assertStringEndsWith('...', $hasil);
    }
}
