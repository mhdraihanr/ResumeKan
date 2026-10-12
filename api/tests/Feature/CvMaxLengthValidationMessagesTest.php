<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCvRequest;

/**
 * Batas panjang teks bebas harus berpesan Indonesia (2026-10-06).
 *
 * `data.summary` (600) dan `data.languages` (200) hanya punya rule `max` tanpa
 * pesan khusus. Pesan bawaan Laravel berbahasa Inggris:
 *
 *   "The data.summary field must not be greater than 600 characters."
 *
 * Pesan itu bocor ke UI Indonesia saat simpan ditolak (mis. ringkasan hasil
 * impor AI yang melebihi batas). Tes ini mengunci pesan Indonesianya.
 */
class CvMaxLengthValidationMessagesTest extends TestCase
{
    /**
     * Validasi `data` langsung lewat Form Request (tanpa HTTP).
     *
     * @return array<string, string>
     */
    private function errors(array $data): array
    {
        $request = StoreCvRequest::create('/api/cvs', 'POST', [
            'title' => 'CV Uji',
            'template' => 'modern',
            'language' => 'id',
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

        return array_map(
            static fn (array $messages): string => $messages[0],
            $validator->errors()->toArray(),
        );
    }

    /** Payload personal minimum supaya error yang muncul hanya dari batas panjang. */
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

    public function test_ringkasan_lebih_600_memakai_pesan_indonesia(): void
    {
        $errors = $this->errors($this->personal() + [
            'summary' => str_repeat('a', 601),
        ]);

        $this->assertSame(
            'Ringkasan maksimal 600 karakter.',
            $errors['data.summary'] ?? null,
        );
    }

    public function test_bahasa_lebih_200_memakai_pesan_indonesia(): void
    {
        $errors = $this->errors($this->personal() + [
            'languages' => str_repeat('a', 201),
        ]);

        $this->assertSame(
            'Bahasa maksimal 200 karakter.',
            $errors['data.languages'] ?? null,
        );
    }

    public function test_ringkasan_600_karakter_tetap_lolos(): void
    {
        $errors = $this->errors($this->personal() + [
            'summary' => str_repeat('a', 600),
        ]);

        $this->assertArrayNotHasKey('data.summary', $errors);
    }
}
