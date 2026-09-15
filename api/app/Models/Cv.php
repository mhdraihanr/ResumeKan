<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    protected $fillable = ['title', 'template', 'language', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Field wajib di dalam entri berulang. Cerminan `ENTRY_RULES` klien
     * (`web/src/lib/cv-validation.ts`) dan `required_with` di `StoreCvRequest`.
     *
     * @return array<string, array<string, string>>
     */
    private const ENTRY_FIELDS = [
        'experiences' => [
            'company' => 'Perusahaan',
            'position' => 'Posisi',
            'startDate' => 'Tanggal mulai',
            'endDate' => 'Tanggal selesai',
        ],
        'education' => [
            'institution' => 'Institusi',
            'degree' => 'Gelar & jurusan',
            'year' => 'Tahun',
        ],
        'organizations' => [
            'organization' => 'Organisasi',
            'role' => 'Peran',
            'period' => 'Periode',
        ],
        'certificates' => [
            'name' => 'Nama sertifikat',
            'issuer' => 'Penerbit',
            'year' => 'Tahun terbit',
        ],
        'projects' => [
            'title' => 'Nama proyek',
            'role' => 'Peran',
        ],
    ];

    /**
     * Field yang belum lengkap / salah format sebelum PDF boleh dibuat.
     * Cerminan `REQUIRED_FIELDS` + `INVALID_FORMATS` + `ENTRY_RULES` klien
     * (`web/src/lib/cv-validation.ts`) — jaga keduanya tetap sinkron.
     *
     * Format hanya dicek bila field terisi; field kosong sudah ditangkap di
     * bagian "wajib diisi".
     *
     * @return array<string, array<int, string>>
     */
    public function missingForPdf(): array
    {
        $missing = [];
        $label = fn (string $path, string $text): array => [$path => [$text]];

        if (blank($this->title)) {
            $missing += $label('title', 'Judul CV wajib diisi.');
        }

        $personal = $this->data['personal'] ?? [];
        $fields = [
            'name' => 'Nama',
            'email' => 'Email',
            'phone' => 'Telepon',
            'address' => 'Alamat',
        ];

        foreach ($fields as $key => $text) {
            if (blank($personal[$key] ?? null)) {
                $missing += $label("data.personal.$key", "$text wajib diisi.");
            }
        }

        $email = $personal['email'] ?? null;
        if (filled($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $missing += $label('data.personal.email', 'Email belum valid — contoh: nama@email.com');
        }

        $phone = $personal['phone'] ?? null;
        if (filled($phone) && ! preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
            $missing += $label('data.personal.phone', 'Telepon hanya boleh angka dan simbol + - ( ) . serta minimal 7 digit.');
        }

        // Entri berulang: hanya entri yang PUNYA isi yang dituntut lengkap.
        // Entri kosong total di-prune klien sebelum kirim, jadi bukan error.
        foreach (self::ENTRY_FIELDS as $key => $entryFields) {
            foreach ($this->data[$key] ?? [] as $i => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $hasAnyValue = false;
                foreach ($entry as $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $hasAnyValue = true;
                        break;
                    }
                }

                if (! $hasAnyValue) {
                    continue;
                }

                foreach ($entryFields as $field => $text) {
                    if (blank($entry[$field] ?? null)) {
                        $missing += $label(
                            "data.$key.$i.$field",
                            "$text ({$this->sectionLabel($key)} #" . ($i + 1) . ') wajib diisi.',
                        );
                    }
                }
            }
        }

        return $missing;
    }

    /** Label section untuk pesan entri, mis. `Sertifikat`. */
    private function sectionLabel(string $key): string
    {
        return [
            'experiences' => 'Pengalaman',
            'education' => 'Pendidikan',
            'organizations' => 'Organisasi',
            'certificates' => 'Sertifikat',
            'projects' => 'Proyek',
        ][$key] ?? $key;
    }

    /** CV siap diunduh sebagai PDF (dipakai untuk men-disable tombol). */
    public function isComplete(): bool
    {
        return $this->missingForPdf() === [];
    }
}
