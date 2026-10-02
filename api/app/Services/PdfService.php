<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class PdfService
{
    public function render(string $html): string
    {
        // CATATAN: jangan set windowSize() di sini. page.pdf() mengabaikan
        // viewport sepenuhnya — Chrome hanya memakai format/margins (paper) dan
        // opsi scale/preferCSSPageSize. windowSize() hanya berpengaruh untuk
        // screenshot, bukan PDF. Ukuran huruf PDF ditentukan murni oleh CSS
        // (@page + @media print) di dalam HTML: konten 673px (@96dpi) dipetakan
        // ke area cetak A4 178mm sehingga 10pt tetap 10pt tanpa penskalaan.
        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(14, 16, 14, 16)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Windows: Edge yang terpasang lewat installer. Di Linux (container
        // produksi) Edge tidak ada, jadi cari Chromium/Chrome di path biasa.
        $edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe';
        if (is_file($edge)) {
            $shot->setChromePath($edge);
        } else {
            $linuxCandidates = [
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
            ];
            foreach ($linuxCandidates as $linux) {
                if (is_file($linux)) {
                    $shot->setChromePath($linux);
                    break;
                }
            }
        }

        // Container produksi berjalan sebagai root; Chrome menolak sandbox-nya
        // sendiri dan gagal launch tanpa --no-sandbox.
        $shot->noSandbox();

        // Browsershot memuat shell SPA dari halaman temp file://, sedangkan
        // script ES module dibatasi CORS. Argumen ini mengizinkan module load
        // dari origin lokal tanpa diblokir lintas-origin.
        $shot->addChromiumArguments([
            'disable-web-security',
            'allow-file-access-from-files',
        ]);

        return $shot->pdf();
    }
}
