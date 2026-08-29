<?php

namespace App\Services;

use App\Models\Cv;
use Spatie\Browsershot\Browsershot;

class PdfService
{
    public function render(Cv $cv): string
    {
        $html = view('pdf.cv', ['cv' => $cv])->render();

        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(14, 16, 14, 16)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Edge (Chromium) lokal, bukan Chrome
        $edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe';
        if (is_file($edge)) {
            $shot->useChrome()->setChromePath($edge);
        }

        return $shot->pdf();
    }
}
