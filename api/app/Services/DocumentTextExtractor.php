<?php

namespace App\Services;

use App\Exceptions\UnreadableDocumentException;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

/**
 * Ubah berkas CV (PDF/DOCX) jadi teks mentah. Tidak menyentuh AI maupun
 * database supaya bisa diuji tanpa jaringan.
 */
class DocumentTextExtractor
{
    /** Batas halaman PDF. Backlog import CV: maks 5 halaman. */
    private const MAX_PAGES = 5;

    /**
     * Di bawah ini dianggap hasil scan/gambar, bukan dokumen berteks. Ambang
     * rendah (bukan nol) supaya PDF yang cuma berisi nomor halaman atau footer
     * tetap ditolak, bukan diteruskan ke AI sebagai CV kosong.
     */
    private const MIN_CHARS = 100;

    /**
     * Batas ukuran `word/document.xml` setelah zip dibuka. Upload dibatasi 5 MB,
     * tapi arsip zip bisa memuai jauh lebih besar (zip bomb) dan menghabiskan
     * memori. Ukuran dari direktori zip dibaca lebih dulu, sebelum ekstraksi.
     */
    private const MAX_DOCX_XML_BYTES = 2 * 1024 * 1024;

    /**
     * @return array{text: string, pages: int|null} `pages` null untuk DOCX
     *         karena jumlah halaman DOCX ditentukan saat dirender, bukan
     *         tersimpan di berkasnya.
     */
    public function extract(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        return match ($extension) {
            'pdf' => $this->extractPdf($file),
            'docx' => $this->extractDocx($file),
            default => throw new UnreadableDocumentException(
                'Format tidak didukung. Upload berkas PDF atau DOCX.'
            ),
        };
    }

    private function extractPdf(UploadedFile $file): array
    {
        try {
            $pdf = (new Parser())->parseContent($file->get());
            $pages = count($pdf->getPages());
        } catch (Throwable $e) {
            // pdfparser melempar tipe berbeda untuk berkas rusak, kosong, atau
            // terproteksi; semuanya berarti hal yang sama bagi user.
            throw new UnreadableDocumentException(
                'Berkas PDF tidak bisa dibaca. Pastikan berkas tidak rusak atau terproteksi.',
                0,
                $e
            );
        }

        if ($pages > self::MAX_PAGES) {
            throw new UnreadableDocumentException('Berkas maksimal ' . self::MAX_PAGES . ' halaman.');
        }

        return $this->hasil(trim($pdf->getText()), $pages);
    }

    private function extractDocx(UploadedFile $file): array
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new UnreadableDocumentException('Berkas DOCX tidak bisa dibuka.');
        }

        $stat = $zip->statName('word/document.xml');

        if ($stat === false) {
            $zip->close();

            throw new UnreadableDocumentException('Berkas DOCX tidak berisi teks yang bisa dibaca.');
        }

        // Ukuran dari direktori zip sudah diketahui sebelum ekstraksi, jadi
        // arsip yang memuai besar bisa ditolak tanpa menghabiskan memori.
        if (($stat['size'] ?? 0) > self::MAX_DOCX_XML_BYTES) {
            $zip->close();

            throw new UnreadableDocumentException('Isi berkas DOCX terlalu besar untuk diproses.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || trim($xml) === '') {
            throw new UnreadableDocumentException('Berkas DOCX tidak berisi teks yang bisa dibaca.');
        }

        return $this->hasil($this->docxXmlKeTeks($xml), null);
    }

    /**
     * Buang tag XML, tapi pertahankan batas paragraf sebagai newline supaya
     * entri (pengalaman, pendidikan) tidak menempel jadi satu baris.
     */
    private function docxXmlKeTeks(string $xml): string
    {
        $xml = preg_replace('/<\?xml[^>]*\?>/', '', $xml) ?? $xml;
        $xml = preg_replace('/<w:tab[^>]*\/>/', ' ', $xml) ?? $xml;
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;

        $teks = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Rapikan spasi berlebih di dalam baris tanpa mengubah batas paragraf.
        $baris = array_map(
            static fn (string $b): string => trim(preg_replace('/[ \t]+/', ' ', $b) ?? $b),
            explode("\n", $teks)
        );

        return trim(implode("\n", array_filter($baris, static fn (string $b): bool => $b !== '')));
    }

    /**
     * @return array{text: string, pages: int|null}
     */
    private function hasil(string $text, ?int $pages): array
    {
        if (mb_strlen($text) < self::MIN_CHARS) {
            throw new UnreadableDocumentException(
                'File tidak berisi teks yang bisa dibaca. Upload PDF text-based, bukan hasil scan.'
            );
        }

        return ['text' => $text, 'pages' => $pages];
    }
}
