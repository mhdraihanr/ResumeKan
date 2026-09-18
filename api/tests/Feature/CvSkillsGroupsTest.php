<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCvRequest;

/**
 * Kontrak `skills` sebagai array grup (2026-09-18).
 *
 * Sebelumnya `skills` berupa objek tetap `{ hard, soft }`. Sekarang array
 * `[{ label, items }]` supaya pengguna bisa menambah grup kustom (mis.
 * "Library & Frameworks"). Dua grup bawaan tetap didukung lewat migrasi di
 * `StoreCvRequest::prepareForValidation()`.
 *
 * Tes di sini menguji aturan validasi + migrasi secara langsung (tanpa HTTP),
 * karena yang menentukan kontrak adalah Form Request, bukan controller.
 */
class CvSkillsGroupsTest extends TestCase
{
    /** Payload valid minimum; `skills` ditimpa per kasus uji. */
    private function payload(array $skills): array
    {
        return [
            'title' => 'CV Uji',
            'template' => 'modern',
            'language' => 'id',
            'data' => [
                'personal' => [
                    'name' => 'Budi',
                    'email' => 'budi@email.com',
                    'phone' => '08123456789',
                    'address' => 'Jakarta',
                ],
                'skills' => $skills,
            ],
        ];
    }

    /**
     * Jalankan `prepareForValidation()` lalu validasi. Mengembalikan
     * `[$lolos, $errors, $dataSetelahNormalisasi]`.
     */
    private function validate(array $skills, bool $draft = false): array
    {
        $payload = $this->payload($skills);
        $url = '/api/cvs' . ($draft ? '?draft=1' : '');
        $request = StoreCvRequest::create($url, 'POST', $payload);
        $request->setContainer($this->app);

        (new \ReflectionMethod($request, 'prepareForValidation'))
            ->invoke($request);

        $data = $request->all();
        $validator = Validator::make($data, (new StoreCvRequest())->rules());

        return [
            ! $validator->fails(),
            $validator->errors()->toArray(),
            $data['data']['skills'] ?? null,
        ];
    }

    public function test_legacy_object_hard_soft_dikonversi_ke_array_grup(): void
    {
        [$ok, , $skills] = $this->validate([
            'hard' => 'Go, Laravel, PostgreSQL',
            'soft' => 'Komunikasi, Leadership',
        ]);

        $this->assertTrue($ok);
        $this->assertSame([
            ['label' => 'Hard skills', 'items' => 'Go, Laravel, PostgreSQL'],
            ['label' => 'Soft skills', 'items' => 'Komunikasi, Leadership'],
        ], $skills);
    }

    public function test_legacy_object_dengan_satu_sisi_kosong_membuang_grup_kosong(): void
    {
        [$ok, , $skills] = $this->validate(['hard' => 'Go', 'soft' => '']);

        $this->assertTrue($ok);
        $this->assertSame([
            ['label' => 'Hard skills', 'items' => 'Go'],
        ], $skills);
    }

    public function test_legacy_object_seluruhnya_kosong_jadi_array_kosong(): void
    {
        [$ok, , $skills] = $this->validate(['hard' => '', 'soft' => '']);

        $this->assertTrue($ok);
        $this->assertSame([], $skills);
    }

    public function test_grup_kustom_tersimpan_utuh(): void
    {
        [$ok, , $skills] = $this->validate([
            ['label' => 'Hard skills', 'items' => 'Go'],
            ['label' => 'Soft skills', 'items' => 'Komunikasi'],
            ['label' => 'Library & Frameworks', 'items' => 'Vue 3, React, Tailwind'],
            ['label' => 'Tools', 'items' => 'Docker, Git, Neovim'],
        ]);

        $this->assertTrue($ok);
        $this->assertCount(4, $skills);
        $this->assertSame('Library & Frameworks', $skills[2]['label']);
        $this->assertSame('Vue 3, React, Tailwind', $skills[2]['items']);
        $this->assertSame('Tools', $skills[3]['label']);
    }

    public function test_lima_grup_masih_diterima(): void
    {
        [$ok] = $this->validate(array_fill(0, 5, ['label' => 'Grup', 'items' => 'x']));

        $this->assertTrue($ok, '5 grup harus lolos (batas atas).');
    }

    public function test_enam_grup_ditolak(): void
    {
        [$ok, $errors] = $this->validate(
            array_fill(0, 6, ['label' => 'Grup', 'items' => 'x']),
        );

        $this->assertFalse($ok);
        $this->assertArrayHasKey('data.skills', $errors);
    }

    public function test_label_lebih_dari_40_karakter_ditolak(): void
    {
        [$ok, $errors] = $this->validate([
            ['label' => str_repeat('a', 41), 'items' => 'Vue'],
        ]);

        $this->assertFalse($ok);
        $this->assertArrayHasKey('data.skills.0.label', $errors);
    }

    public function test_label_40_karakter_diterima(): void
    {
        [$ok] = $this->validate([
            ['label' => str_repeat('a', 40), 'items' => 'Vue'],
        ]);

        $this->assertTrue($ok, 'Tepat 40 karakter harus lolos (batas atas).');
    }

    public function test_items_lebih_dari_500_karakter_ditolak(): void
    {
        [$ok, $errors] = $this->validate([
            ['label' => 'Hard skills', 'items' => str_repeat('x', 501)],
        ]);

        $this->assertFalse($ok);
        $this->assertArrayHasKey('data.skills.0.items', $errors);
    }

    public function test_label_kosong_ditolak_saat_non_draft(): void
    {
        [$ok, $errors] = $this->validate([['label' => '', 'items' => 'Vue']]);

        $this->assertFalse($ok);
        $this->assertArrayHasKey('data.skills.0.label', $errors);
    }

    public function test_items_kosong_tetap_diterima(): void
    {
        // Grup yang baru ditambah tapi belum diisi tidak boleh memblokir simpan;
        // pruning entri kosong adalah keputusan klien (lib/cv-validation.ts).
        [$ok] = $this->validate([['label' => 'Tools', 'items' => '']]);

        $this->assertTrue($ok);
    }

    public function test_skills_boleh_absen_sama_sekali(): void
    {
        $payload = $this->payload([]);
        unset($payload['data']['skills']);
        $request = StoreCvRequest::create('/api/cvs', 'POST', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, (new StoreCvRequest())->rules());

        $this->assertFalse(
            $validator->fails(),
            'skills opsional — CV tanpa keahlian tetap valid.',
        );
    }
}
