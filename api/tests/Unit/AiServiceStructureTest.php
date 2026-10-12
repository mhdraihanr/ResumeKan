<?php

namespace Tests\Unit;

use App\Services\AiService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Strukturisasi CV adalah bagian paling rapuh dari import: keluaran AI tidak
 * pernah bisa dipercaya bentuknya. Test di sini mengunci tiga hal — gateway
 * dipanggil benar, JSON diambil walau dibungkus apa pun, dan input dipotong
 * supaya tidak menabrak batas token.
 */
class AiServiceStructureTest extends TestCase
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

    public function test_api_key_kosong_melempar_exception(): void
    {
        config(['ai.api_key' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI_API_KEY belum diisi');

        (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');
    }

    public function test_respons_json_valid_dikembalikan_sebagai_array(): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($this->cvDataContoh())],
                ]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        $this->assertSame('Budi Santoso', $hasil['personal']['name']);
        $this->assertSame('budi@example.com', $hasil['personal']['email']);
        $this->assertSame('Backend Engineer', $hasil['experiences'][0]['position']);
        $this->assertSame('Hard skills', $hasil['skills'][0]['label']);
    }

    public function test_respons_dibungkus_code_fence_tetap_terparse(): void
    {
        $konten = "```json\n" . json_encode($this->cvDataContoh()) . "\n```";

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        $this->assertSame('Budi Santoso', $hasil['personal']['name']);
    }

    public function test_objek_json_diambil_walau_ada_teks_pembuka_dan_penutup(): void
    {
        $konten = 'Berikut hasil ekstraksinya: ' . json_encode($this->cvDataContoh()) . ' Semoga membantu!';

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        $this->assertSame('Budi Santoso', $hasil['personal']['name']);
    }

    public function test_respons_sampah_melempar_exception(): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Maaf, saya tidak bisa membantu.']]],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');
    }

    public function test_respons_json_rusak_melempar_exception(): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => '{"personal": {"name": "Budi",}}']]],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');
    }

    public function test_respons_stream_sse_delta_digabung(): void
    {
        // Gateway nyata (lihat AI_BASE_URL lokal) membalas SSE, bukan JSON
        // tunggal. Kalau ini tidak ditangani, import selalu gagal di produksi.
        $potongan = ['{"personal":', '{"name":', '"Budi Santoso"}}'];
        $sse = '';

        foreach ($potongan as $bagian) {
            $sse .= 'data: ' . json_encode(['choices' => [['delta' => ['content' => $bagian]]]]) . "\n";
        }

        $sse .= "data: [DONE]\n";

        Http::fake(['ai.test/*' => Http::response($sse, 200, ['Content-Type' => 'text/event-stream'])]);

        $hasil = (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        $this->assertSame('Budi Santoso', $hasil['personal']['name']);
    }

    public function test_status_gagal_melempar_exception(): void
    {
        Http::fake(['ai.test/*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(RuntimeException::class);

        (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');
    }

    public function test_objek_di_dalam_array_tetap_terambil(): void
    {
        // Model kadang membalas `[{...}]` walau diminta satu objek.
        $konten = json_encode([$this->cvDataContoh()]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        $this->assertSame('Budi Santoso', $hasil['personal']['name']);
    }

    public function test_field_asing_dibuang_dan_tipe_dipaksa(): void
    {
        $konten = json_encode([
            'personal' => ['name' => 'Budi Santoso', 'jabatan' => 'CEO', 'phone' => 8123456789],
            'summary' => null,
            'experiences' => [['company' => 'PT Contoh', 'position' => 'Dev', 'employmentType' => 'Full Time']],
            'skills' => 'Laravel',
            'catatanAI' => 'abaikan',
        ]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso.');

        // Kunci asing tidak bocor ke bentuk CvData.
        $this->assertArrayNotHasKey('jabatan', $hasil['personal']);
        $this->assertArrayNotHasKey('catatanAI', $hasil);
        // Angka jadi string, null jadi string kosong.
        $this->assertSame('8123456789', $hasil['personal']['phone']);
        $this->assertSame('', $hasil['summary']);
        // `skills` yang seharusnya array tapi dikirim string tidak merusak bentuk.
        $this->assertSame([], $hasil['skills']);
        // Varian enum diselaraskan ke nilai yang diterima StoreCvRequest.
        $this->assertSame('Full-time', $hasil['experiences'][0]['employmentType']);
    }

    public function test_entri_tanpa_field_wajib_tetap_punya_semua_kunci(): void
    {
        $konten = json_encode(['experiences' => [['company' => 'PT Contoh']]]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('PT Contoh.');

        foreach (['company', 'position', 'location', 'employmentType', 'startDate', 'endDate', 'description'] as $field) {
            $this->assertArrayHasKey($field, $hasil['experiences'][0]);
        }
    }

    public function test_entri_berlebih_dipotong_sesuai_batas_cvdata(): void
    {
        // StoreCvRequest menolak > 10 pengalaman; AI sering mengirim lebih.
        $konten = json_encode([
            'experiences' => array_fill(0, 15, ['company' => 'PT Contoh', 'position' => 'Dev']),
        ]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('PT Contoh.');

        $this->assertCount(10, $hasil['experiences']);
    }

    public function test_teks_dipotong_sebelum_dikirim_ke_ai(): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($this->cvDataContoh())]]],
            ], 200),
        ]);

        (new AiService())->structureCvData(str_repeat('a', 20000));

        Http::assertSent(function (ClientRequest $request) {
            $pesanUser = collect($request->data()['messages'] ?? [])
                ->firstWhere('role', 'user');

            $this->assertSame('model-uji', $request->data()['model']);

            // Instruksi ikut di pesan user, jadi panjangnya = teks (dipotong)
            // + prompt sistem. Yang dikunci di sini: teks mentah tetap dipotong
            // 15000 karakter, bukan dibuang.
            return str_contains($pesanUser['content'] ?? '', str_repeat('a', 15000))
                && !str_contains($pesanUser['content'] ?? '', str_repeat('a', 15001));
        });
    }

    public function test_instruksi_dikirim_di_pesan_user_bukan_system(): void
    {
        // Gateway lokal (9Router) membuang pesan `system` untuk model
        // deepseek/glm. Kalau instruksi ditaruh di sana, model hanya melihat
        // teks CV mentah lalu membalas prosa — import gagal 502. Karena itu
        // prompt wajib ikut di pesan `user`.
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($this->cvDataContoh())]]],
            ], 200),
        ]);

        (new AiService())->structureCvData('Budi Santoso, Backend Engineer.');

        Http::assertSent(function (ClientRequest $request) {
            $messages = $request->data()['messages'] ?? [];

            $this->assertCount(1, $messages);
            $this->assertSame('user', $messages[0]['role']);
            $this->assertStringContainsString('JSON', $messages[0]['content']);
            $this->assertStringContainsString('Budi Santoso', $messages[0]['content']);

            return true;
        });
    }

    public function test_ringkasan_hasil_impor_dipotong_600_karakter(): void
    {
        // AI bisa membalas summary > 600 walau `maxlength` form 600 (nilainya
        // masuk tanpa pernah diketik). Kalau tidak dipotong di sini, simpan
        // form gagal 422 dan user tidak tahu field mana yang bermasalah.
        $konten = json_encode(['summary' => str_repeat('a', 700)]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso.');

        $this->assertSame(600, mb_strlen($hasil['summary']));
        $this->assertStringEndsWith('...', $hasil['summary']);
    }

    public function test_grup_skill_tanpa_label_tapi_ada_items_diberi_label_default(): void
    {
        // StoreCvRequest menolak label kosong. AI kadang meninggalkannya kosong
        // padahal items terisi; fallback ini mencegah user memperbaiki manual.
        $konten = json_encode([
            'skills' => [
                ['label' => 'Hard skills', 'items' => 'Laravel'],
                ['label' => '', 'items' => 'PostgreSQL, Docker'],
            ],
        ]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso.');

        $this->assertSame('Keahlian', $hasil['skills'][1]['label']);
    }

    public function test_grup_skill_label_kosong_dan_items_kosong_tetap_kosong(): void
    {
        // Grup benar-benar kosong tidak diisi label palsu — itu bukan konten.
        $konten = json_encode(['skills' => [['label' => '', 'items' => '']]]);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $konten]]],
            ], 200),
        ]);

        $hasil = (new AiService())->structureCvData('Budi Santoso.');

        $this->assertSame('', $hasil['skills'][0]['label']);
    }

    /**
     * Bentuk CvData minimal sesuai DATA_MODEL.md — cukup untuk mengunci
     * pemetaan tanpa menyalin seluruh skema.
     *
     * @return array<string, mixed>
     */
    private function cvDataContoh(): array
    {
        return [
            'personal' => [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'phone' => '08123456789',
                'address' => 'Jakarta',
            ],
            'summary' => '',
            'experiences' => [[
                'company' => 'PT Contoh',
                'position' => 'Backend Engineer',
                'startDate' => '2020-01',
                'endDate' => 'Present',
                'description' => 'Membangun API.',
            ]],
            'education' => [],
            'skills' => [['label' => 'Hard skills', 'items' => 'Laravel, PostgreSQL']],
            'languages' => '',
            'certificates' => [],
            'projects' => [],
            'organizations' => [],
        ];
    }
}
