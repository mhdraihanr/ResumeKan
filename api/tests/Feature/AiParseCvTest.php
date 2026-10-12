<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Import CV adalah satu-satunya endpoint yang menerima file + memanggil AI
 * sekaligus, jadi paling banyak mode gagalnya. Test di sini mengunci seluruh
 * peta status: 401, 422, 429, 502, 503, dan 200.
 */
class AiParseCvTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/ai/parse-cv';

    private const TEKS_PANJANG = 'Backend engineer dengan pengalaman lima tahun membangun layanan '
        . 'Laravel dan PostgreSQL, memimpin migrasi arsitektur monolit ke modular, '
        . 'serta menurunkan waktu respons API dari 800ms menjadi 120ms.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.api_key' => 'kunci-uji',
            'ai.base_url' => 'https://ai.test/v1',
            'ai.model' => 'model-uji',
        ]);
    }

    private function actingUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function fakeAiSukses(array $cvData): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($cvData)]]],
            ], 200),
        ]);
    }

    public function test_tanpa_auth_membalas_401(): void
    {
        $this->postJson(self::URL, [])->assertStatus(401);
    }

    public function test_pdf_berhasil_mengembalikan_data_dan_warnings_kosong(): void
    {
        $this->actingUser();
        $this->fakeAiSukses($this->cvDataLengkap());

        $response = $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.personal.name', 'Budi Santoso')
            ->assertJsonPath('data.experiences.0.position', 'Backend Engineer')
            ->assertJsonPath('warnings', []);
    }

    public function test_docx_berhasil(): void
    {
        $this->actingUser();
        $this->fakeAiSukses($this->cvDataLengkap());

        $response = $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.docx', $this->docxBytes(self::TEKS_PANJANG)),
        ]);

        $response->assertStatus(200)->assertJsonPath('data.personal.name', 'Budi Santoso');
    }

    public function test_field_kosong_dilaporkan_di_warnings(): void
    {
        $this->actingUser();

        $data = $this->cvDataLengkap();
        $data['personal']['email'] = '';
        $data['personal']['address'] = '';
        $data['education'] = [];

        $this->fakeAiSukses($data);

        $response = $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ]);

        $response->assertStatus(200);

        $warnings = $response->json('warnings');
        $this->assertContains('Email', $warnings);
        $this->assertContains('Alamat', $warnings);
        $this->assertContains('Pendidikan', $warnings);
        $this->assertNotContains('Nama', $warnings);
    }

    public function test_ekstensi_tidak_didukung_membalas_422(): void
    {
        $this->actingUser();

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('catatan.txt', str_repeat('teks ', 50)),
        ])->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_file_lebih_dari_lima_mb_membalas_422(): void
    {
        $this->actingUser();

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->create('besar.pdf', 6 * 1024),
        ])->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_pdf_scan_membalas_422_dengan_pesan_jelas(): void
    {
        $this->actingUser();

        $response = $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('scan.pdf', $this->pdfBytes('')),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'text-based'));
    }

    public function test_ai_api_key_kosong_membalas_503(): void
    {
        $this->actingUser();
        config(['ai.api_key' => '']);

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ])->assertStatus(503)->assertJsonPath('message', 'AI belum dikonfigurasi');
    }

    public function test_ai_gagal_membalas_502(): void
    {
        $this->actingUser();
        Http::fake(['ai.test/*' => Http::response(['error' => 'boom'], 500)]);

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ])->assertStatus(502)->assertJsonPath('message', 'AI service unavailable');
    }

    public function test_ai_balas_sampah_membalas_502(): void
    {
        $this->actingUser();
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Maaf, saya tidak bisa.']]],
            ], 200),
        ]);

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ])->assertStatus(502);
    }

    public function test_file_ditolak_php_membalas_422_dengan_pesan_jujur(): void
    {
        $this->actingUser();

        // `UploadedFile::fake()` selalu error=0, jadi tidak pernah menempuh
        // jalur ini. Padahal di server nyata, berkas > upload_max_filesize
        // (default 2M) ditolak PHP sebelum Laravel melihatnya. Pesannya tidak
        // boleh menyebut "5 MB" — berkasnya bisa saja hanya 2.5 MB.
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, 'x');

        $file = new UploadedFile($path, 'besar.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

        $response = $this->post(self::URL, ['file' => $file]);

        $response->assertStatus(422)->assertJsonValidationErrors('file');

        $pesan = $response->json('errors.file.0');
        $this->assertStringNotContainsString('5 MB', $pesan);
        $this->assertStringContainsString('batas server', $pesan);

        unlink($path);
    }

    public function test_request_keempat_dalam_satu_menit_membalas_429(): void
    {
        $this->actingUser();
        $this->fakeAiSukses($this->cvDataLengkap());

        for ($i = 0; $i < 3; $i++) {
            $this->post(self::URL, [
                'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
            ])->assertStatus(200);
        }

        $response = $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ]);

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_file_tidak_ditinggalkan_di_disk(): void
    {
        $this->actingUser();
        $this->fakeAiSukses($this->cvDataLengkap());

        $sebelum = $this->daftarFileStorage();

        $this->post(self::URL, [
            'file' => UploadedFile::fake()->createWithContent('cv-rahasia.pdf', $this->pdfBytes(self::TEKS_PANJANG)),
        ])->assertStatus(200);

        $this->assertSame($sebelum, $this->daftarFileStorage());
    }

    public function test_route_terdaftar_dengan_middleware_benar(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/ai/parse-cv' && in_array('POST', $r->methods())
        );

        $this->assertNotNull($route, 'Route POST /api/v1/ai/parse-cv tidak terdaftar.');
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        $this->assertContains('throttle:ai-parse', $route->gatherMiddleware());
    }

    /** @return array<int, string> */
    private function daftarFileStorage(): array
    {
        $path = storage_path('app');
        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /** @return array<string, mixed> */
    private function cvDataLengkap(): array
    {
        return [
            'personal' => [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'phone' => '08123456789',
                'address' => 'Jakarta',
            ],
            'summary' => 'Backend engineer.',
            'experiences' => [[
                'company' => 'PT Contoh',
                'position' => 'Backend Engineer',
                'startDate' => '2020-01',
                'endDate' => 'Present',
                'description' => 'Membangun API.',
            ]],
            'education' => [[
                'institution' => 'Universitas Contoh',
                'degree' => 'S1 Teknik Informatika',
                'year' => '2019',
            ]],
            'skills' => [['label' => 'Hard skills', 'items' => 'Laravel, PostgreSQL']],
            'languages' => 'Indonesia, Inggris',
            'certificates' => [],
            'projects' => [],
            'organizations' => [],
        ];
    }

    private function pdfBytes(string $teks): string
    {
        $objek = [];
        $stream = $teks === '' ? '' : 'BT /F1 12 Tf 72 720 Td (' . $teks . ') Tj ET';

        $objek[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objek[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objek[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] '
            . '/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objek[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objek[5] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offset = [];

        foreach ($objek as $nomor => $isi) {
            $offset[$nomor] = strlen($pdf);
            $pdf .= $nomor . " 0 obj\n" . $isi . "\nendobj\n";
        }

        $posisiXref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $posisiXref . "\n%%EOF";

        return $pdf;
    }

    /**
     * DOCX realistis: `[Content_Types].xml` memetakan `word/document.xml` ke
     * MIME DOCX, jadi `File::types(['docx'])` lolos. Zip polos tanpa peta itu
     * terdeteksi `application/zip` dan ditolak validasi — bukan perilaku yang
     * ingin kita uji di sini.
     */
    private function docxBytes(string $teks): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" '
            . 'ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>'
        );
        $zip->addFromString(
            'word/document.xml',
            '<?xml version="1.0"?><w:document '
            . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'
            . htmlspecialchars($teks, ENT_XML1) . '</w:t></w:r></w:p></w:body></w:document>'
        );
        $zip->close();

        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }
}
