<?php

namespace App\Console\Commands;

use App\Models\Cv;
use Illuminate\Console\Command;

/**
 * Normalisasi bentuk lama `skills: { hard, soft }` menjadi array grup
 * `[{ label, items }]` (perubahan 2026-09-18).
 *
 * Kenapa perlu ada: skema CV disimpan sebagai satu kolom JSON (`cvs.data`,
 * ADR-3), jadi bentuk lama tidak bisa diubah lewat migration SQL. Aplikasi
 * sendiri sudah membaca dua bentuk (lihat `StoreCvRequest::prepareForValidation`
 * dan `normalizeSkills()` di `web/src/types/cv.ts`), jadi command ini OPSIONAL —
 * hanya untuk merapikan data agar seragam di database.
 *
 * Idempotent: CV yang sudah berbentuk array dilewati, aman dijalankan berulang.
 */
class NormalizeCvSkills extends Command
{
    protected $signature = 'cv:normalize-skills
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Ubah skills lama {hard, soft} menjadi array grup [{label, items}]';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $converted = 0;
        $skipped = 0;
        $rows = [];

        Cv::query()->chunkById(100, function ($cvs) use ($dryRun, &$converted, &$skipped, &$rows) {
            foreach ($cvs as $cv) {
                $skills = $cv->data['skills'] ?? null;

                // Sudah array (atau tidak ada) -> tidak perlu diapa-apakan.
                if ($skills === null || is_array($skills) && array_is_list($skills)) {
                    $skipped++;

                    continue;
                }

                if (! is_array($skills)) {
                    // Bentuk tak terduga (mis. string) — jangan tebak, laporkan.
                    $rows[] = [$cv->id, $cv->title, 'DILEWATI (bentuk tak dikenal)', ''];

                    continue;
                }

                $normalized = self::normalizeLegacySkills($skills);
                $rows[] = [
                    $cv->id,
                    $cv->title,
                    self::summarize($skills),
                    self::summarize($normalized),
                ];

                if (! $dryRun) {
                    $data = $cv->data;
                    $data['skills'] = $normalized;
                    $cv->data = $data;
                    $cv->save();
                }

                $converted++;
            }
        });

        if ($rows !== []) {
            $this->table(['ID', 'Judul', 'Sebelum', 'Sesudah'], $rows);
        }

        if ($dryRun) {
            $this->info("Dry run: $converted CV akan diubah, $skipped dilewati.");
        } else {
            $this->info("Selesai: $converted CV diubah, $skipped dilewati.");
        }

        return self::SUCCESS;
    }

    /**
     * Aturan konversi yang sama dengan `StoreCvRequest::prepareForValidation()`
     * dan `normalizeSkills()` (klien) — jaga ketiganya tetap sinkron.
     *
     * @param  array<string, mixed>  $old
     * @return array<int, array{label: string, items: string}>
     */
    public static function normalizeLegacySkills(array $old): array
    {
        $groups = [
            ['label' => 'Hard skills', 'items' => $old['hard'] ?? ''],
            ['label' => 'Soft skills', 'items' => $old['soft'] ?? ''],
        ];

        // Grup yang items-nya kosong dibuang supaya tidak jadi section hampa.
        return array_values(array_filter(
            $groups,
            static fn (array $g): bool => trim((string) $g['items']) !== '',
        ));
    }

    /** Ringkasan satu-baris untuk kolom tabel, mis. `hard(3 item) + soft(2 item)`. */
    private static function summarize(mixed $skills): string
    {
        if (! is_array($skills)) {
            return '(bukan array)';
        }

        if (array_is_list($skills)) {
            $parts = [];
            foreach ($skills as $g) {
                if (! is_array($g)) {
                    continue;
                }
                $n = count(array_filter(
                    array_map('trim', explode(',', (string) ($g['items'] ?? ''))),
                    static fn (string $s): bool => $s !== '',
                ));
                $parts[] = ($g['label'] ?? '?') . "($n item)";
            }

            return $parts === [] ? '(kosong)' : implode(' + ', $parts);
        }

        $h = trim((string) ($skills['hard'] ?? ''));
        $s = trim((string) ($skills['soft'] ?? ''));

        return 'hard(' . strlen($h) . ' char) + soft(' . strlen($s) . ' char)';
    }
}
