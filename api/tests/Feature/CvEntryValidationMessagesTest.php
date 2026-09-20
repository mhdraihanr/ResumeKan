<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCvRequest;

/**
 * Pesan validasi field entri berulang harus berbahasa Indonesia (2026-09-20).
 *
 * `StoreCvRequest` semula hanya punya `messages()`/`attributes()` untuk field
 * top-level. Akibatnya field di dalam entri berulang jatuh ke pesan default
 * Laravel yang berbahasa Inggris dan menyebut path mentah:
 *
 *   "The data.certificates.0.issuer field is required when data.certificates
 *    is present."
 *
 * Pesan itu bocor ke UI Indonesia lewat alur terjemah/duplikat CV, karena
 * klien mengirim ulang `data` hasil terjemah ke `POST /api/v1/cvs`.
 *
 * Tes ini mengunci kontraknya: setiap field entri wajib punya label ramah
 * beserta nomor entri, dan pesannya berbahasa Indonesia.
 */
class CvEntryValidationMessagesTest extends TestCase
{
    /**
     * Validasi `data` secara langsung lewat Form Request (tanpa HTTP), karena
     * yang menentukan kontrak adalah Form Request.
     *
     * @return array<string, string>
     */
    private function errors(array $data): array
    {
        $request = StoreCvRequest::create('/api/cvs', 'POST', [
            'title' => 'CV Uji',
            'template' => 'modern',
            'language' => 'en',
            'data' => $data,
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        );

        // Ambil pesan pertama tiap field agar assertion-nya sederhana.
        return array_map(
            static fn (array $messages): string => $messages[0],
            $validator->errors()->toArray(),
        );
    }

    /** Payload personal minimum supaya error yang muncul hanya dari entri. */
    private function personal(): array
    {
        return [
            'personal' => [
                'name' => 'Budi',
                'email' => 'budi@email.com',
                'phone' => '08123456789',
                'address' => 'Jakarta',
            ],
        ];
    }

    public function test_sertifikat_tanpa_issuer_memakai_label_indonesia(): void
    {
        $errors = $this->errors($this->personal() + [
            'certificates' => [
                ['name' => 'Belajar SQL', 'issuer' => null, 'year' => null],
            ],
        ]);

        $this->assertSame(
            'Penerbit (Sertifikat #1) wajib diisi.',
            $errors['data.certificates.0.issuer'] ?? null,
        );
        $this->assertSame(
            'Tahun terbit (Sertifikat #1) wajib diisi.',
            $errors['data.certificates.0.year'] ?? null,
        );
    }

    public function test_nomor_entri_ikut_di_label(): void
    {
        $errors = $this->errors($this->personal() + [
            'certificates' => [
                ['name' => 'A', 'issuer' => 'Dicoding', 'year' => '2024'],
                ['name' => 'B', 'issuer' => null, 'year' => null],
            ],
        ]);

        // Entri pertama lengkap, jadi tidak boleh muncul.
        $this->assertArrayNotHasKey('data.certificates.0.issuer', $errors);
        $this->assertSame(
            'Penerbit (Sertifikat #2) wajib diisi.',
            $errors['data.certificates.1.issuer'] ?? null,
        );
    }

    public function test_semua_section_entri_punya_label_dan_nomor(): void
    {
        $errors = $this->errors($this->personal() + [
            'experiences' => [['company' => 'PT X']],
            'education' => [['institution' => 'Univ']],
            'organizations' => [['organization' => 'UKM']],
            'projects' => [['title' => 'Proyek']],
        ]);

        $expected = [
            'data.experiences.0.position' => 'Posisi (Pengalaman #1) wajib diisi.',
            'data.education.0.degree' => 'Gelar & jurusan (Pendidikan #1) wajib diisi.',
            'data.organizations.0.role' => 'Peran (Organisasi #1) wajib diisi.',
            'data.projects.0.role' => 'Peran (Proyek #1) wajib diisi.',
        ];

        foreach ($expected as $field => $message) {
            $this->assertSame($message, $errors[$field] ?? null, "field: $field");
        }
    }

    public function test_tidak_ada_pesan_inggris_atau_path_mentah(): void
    {
        $errors = $this->errors($this->personal() + [
            'certificates' => [['name' => 'A', 'issuer' => null, 'year' => null]],
            'experiences' => [['company' => 'PT X']],
            'projects' => [['title' => 'P']],
        ]);

        $this->assertNotEmpty($errors);

        foreach ($errors as $field => $message) {
            $this->assertStringNotContainsString(
                'field is required',
                $message,
                "pesan masih Inggris untuk $field",
            );
            $this->assertStringNotContainsString(
                'data.',
                $message,
                "pesan masih memakai path mentah untuk $field",
            );
            $this->assertStringContainsString(
                'wajib diisi.',
                $message,
                "pesan tidak konsisten untuk $field",
            );
        }
    }
}
