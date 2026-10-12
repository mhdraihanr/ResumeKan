<?php

namespace Tests\Unit;

use App\Exceptions\UnreadableDocumentException;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Ekstraksi teks adalah gerbang pertama import CV: kalau bagian ini salah,
 * user meng-upload CV dan AI menyusun dari teks kosong. Fixture dibangun di
 * dalam test (byte mentah) supaya repo tidak menyimpan berkas biner.
 */
class DocumentTextExtractorTest extends TestCase
{
    private const TEKS_PANJANG = 'Backend engineer dengan pengalaman lima tahun membangun layanan '
        . 'Laravel dan PostgreSQL, memimpin migrasi arsitektur monolit ke modular, '
        . 'serta menurunkan waktu respons API dari 800ms menjadi 120ms.';

    public function test_pdf_berteks_mengekstrak_teks_dan_jumlah_halaman(): void
    {
        $file = UploadedFile::fake()->createWithContent('cv.pdf', $this->pdfBytes(3, self::TEKS_PANJANG));

        $hasil = (new DocumentTextExtractor())->extract($file);

        $this->assertSame(3, $hasil['pages']);
        $this->assertStringContainsString('Backend engineer', $hasil['text']);
        $this->assertStringContainsString('PostgreSQL', $hasil['text']);
    }

    public function test_pdf_tanpa_teks_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('scan.pdf', $this->pdfBytes(1, ''));

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessageMatches('/text-based/');

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_pdf_dengan_teks_terlalu_pendek_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('tipis.pdf', $this->pdfBytes(1, 'Hi'));

        $this->expectException(UnreadableDocumentException::class);

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_pdf_lebih_dari_lima_halaman_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('panjang.pdf', $this->pdfBytes(6, self::TEKS_PANJANG));

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessageMatches('/5 halaman/');

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_pdf_terproteksi_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('terkunci.pdf', $this->encryptedPdfBytes());

        $this->expectException(UnreadableDocumentException::class);

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_docx_mengekstrak_teks(): void
    {
        $file = UploadedFile::fake()->createWithContent('cv.docx', $this->docxBytes(self::TEKS_PANJANG));

        $hasil = (new DocumentTextExtractor())->extract($file);

        $this->assertStringContainsString('Backend engineer', $hasil['text']);
        $this->assertStringContainsString('PostgreSQL', $hasil['text']);
    }

    public function test_docx_tanpa_document_xml_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('rusak.docx', $this->docxBytes(null));

        $this->expectException(UnreadableDocumentException::class);

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_docx_dengan_isi_memuai_besar_ditolak(): void
    {
        // Arsip kecil di disk tapi word/document.xml memuai > 2 MB (zip bomb).
        $file = UploadedFile::fake()->createWithContent('bom.docx', $this->docxBytesBesar());

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessageMatches('/terlalu besar/');

        (new DocumentTextExtractor())->extract($file);
    }

    public function test_ekstensi_tidak_didukung_ditolak(): void
    {
        $file = UploadedFile::fake()->createWithContent('catatan.txt', str_repeat('teks panjang ', 20));

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessageMatches('/PDF atau DOCX/');

        (new DocumentTextExtractor())->extract($file);
    }

    /**
     * PDF minimal yang valid: katalog, halaman, font, dan satu content stream
     * berisi operator teks per halaman. Cukup untuk pdfparser membaca teks.
     */
    private function pdfBytes(int $jumlahHalaman, string $teks): string
    {
        $objek = [];
        $kids = [];

        for ($i = 0; $i < $jumlahHalaman; $i++) {
            $nomorHalaman = 4 + $i * 2;
            $nomorKonten = 5 + $i * 2;
            $kids[] = $nomorHalaman . ' 0 R';

            $objek[$nomorHalaman] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] '
                . '/Resources << /Font << /F1 3 0 R >> >> /Contents ' . $nomorKonten . ' 0 R >>';

            $stream = $teks === '' ? '' : 'BT /F1 12 Tf 72 720 Td (' . $teks . ') Tj ET';
            $objek[$nomorKonten] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objek[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objek[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $jumlahHalaman . ' >>';
        $objek[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objek);

        return $this->susunPdf($objek);
    }

    private function encryptedPdfBytes(): string
    {
        $objek = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>',
            4 => "<< /Length 0 >>\nstream\n\nendstream",
            5 => '<< /Filter /Standard /V 1 /R 2 /O <' . str_repeat('00', 32) . '> /U <'
                . str_repeat('00', 32) . '> /P -1 >>',
        ];

        return $this->susunPdf($objek, '/Encrypt 5 0 R');
    }

    /**
     * @param  array<int, string>  $objek  peta nomor objek -> isi objek
     */
    private function susunPdf(array $objek, string $entriTrailerTambahan = ''): string
    {
        $pdf = "%PDF-1.4\n";
        $offset = [];

        foreach ($objek as $nomor => $isi) {
            $offset[$nomor] = strlen($pdf);
            $pdf .= $nomor . " 0 obj\n" . $isi . "\nendobj\n";
        }

        $posisiXref = strlen($pdf);
        $jumlah = max(array_keys($objek)) + 1;

        $pdf .= "xref\n0 " . $jumlah . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $jumlah; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset[$i]);
        }

        $pdf .= "trailer\n<< /Size " . $jumlah . " /Root 1 0 R " . $entriTrailerTambahan . " >>\n";
        $pdf .= "startxref\n" . $posisiXref . "\n%%EOF";

        return $pdf;
    }

    /**
     * DOCX minimal (arsip zip berisi word/document.xml). `null` = arsip tanpa
     * document.xml, untuk menguji berkas rusak.
     */
    private function docxBytes(?string $teks): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="xml" ContentType="application/xml"/></Types>'
        );

        if ($teks !== null) {
            $zip->addFromString(
                'word/document.xml',
                '<?xml version="1.0"?><w:document '
                . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'
                . htmlspecialchars($teks, ENT_XML1) . '</w:t></w:r></w:p></w:body></w:document>'
            );
        }

        $zip->close();

        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /**
     * DOCX dengan `word/document.xml` memuai > 2 MB tapi terkompresi jadi
     * beberapa KB di disk. Meniru zip bomb: ukuran di disk lolos validasi
     * upload, tapi hasil ekstraksi jauh lebih besar.
     */
    private function docxBytesBesar(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString(
            'word/document.xml',
            '<?xml version="1.0"?><w:document><w:body><w:p><w:t>'
            . str_repeat('a', 3 * 1024 * 1024)
            . '</w:t></w:p></w:body></w:document>'
        );
        $zip->close();

        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }
}
