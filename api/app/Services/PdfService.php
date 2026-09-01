<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class PdfService
{
    public function render(string $html): string
    {
        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(14, 16, 14, 16)
            ->showBackground()
            ->waitUntilNetworkIdle();

        $edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe';
        if (is_file($edge)) {
            $shot->useChrome()->setChromePath($edge);
        }

        // Browsershot loads the SPA shell from a file:// temp page; ES module
        // scripts are CORS-gated, so allow module loads from the local origin
        // (localhost Vite dev / served assets) without a cross-origin block.
        $shot->addChromiumArguments([
            'disable-web-security',
            'allow-file-access-from-files',
        ]);

        return $shot->pdf();
    }
}
