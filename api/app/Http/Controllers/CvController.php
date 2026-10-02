<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvRequest;
use App\Http\Resources\CvResource;
use App\Models\Cv;
use App\Services\PdfService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Js;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CvController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $cvs = $request->user()->cvs()->latest()->get();

        return CvResource::collection($cvs);
    }

    public function store(StoreCvRequest $request): JsonResponse
    {
        if ($request->user()->cvs()->count() >= 10) {
            return response()->json([
                'message' => 'Maksimal 10 CV per user.',
                'errors' => ['title' => ['Maksimal 10 CV per user.']],
            ], 422);
        }

        $cv = $request->user()->cvs()->create($this->payload($request));

        return response()->json(['cv' => new CvResource($cv)], 201);
    }

    public function show(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        return response()->json(['cv' => new CvResource($cv)]);
    }

    public function update(StoreCvRequest $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $cv->update($this->payload($request));

        return response()->json(['cv' => new CvResource($cv)]);
    }

    /**
     * Kolom `title` NOT NULL, sedangkan draft boleh disimpan tanpa judul.
     * Lengkapi dengan placeholder agar draft parsial tetap bisa dipersist.
     */
    private function payload(StoreCvRequest $request): array
    {
        $data = $request->validated();

        if ($request->isDraft()) {
            $data['title'] = filled($data['title'] ?? null) ? $data['title'] : 'CV Tanpa Judul';
        }

        return $data;
    }

    public function destroy(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $cv->delete();

        return response()->json(null, 204);
    }

    public function pdf(Request $request, Cv $cv): Response|JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        // Gate kelengkapan: tolak PDF setengah jadi. Selaras dengan cek klien di
        // `CvForm.isComplete()` — terutama untuk tombol Dashboard yang tak punya
        // form untuk divalidasi. 422 + pesan field, bukan PDF.
        $missing = $cv->missingForPdf();
        if ($missing !== []) {
            return response()->json([
                'message' => 'Lengkapi dulu sebelum mengunduh.',
                'errors' => $missing,
            ], 422);
        }

        $name = preg_replace('/[^\p{L}\p{N} _-]/u', '', $cv->data['personal']['name'] ?? 'CV') ?: 'CV';
        $html = $this->resolvePrintHtml($cv->data ?? [], $cv->template ?? 'modern', $cv->language ?? 'id');

        // Render SEBELUM header dikirim. Dulu ini `streamDownload(fn () => echo ...)`
        // yang mengirim `Content-Type: application/pdf` lebih dulu lalu merender di
        // dalam closure. Kalau Browsershot gagal (Chromium tak ditemukan, timeout,
        // OOM) header PDF sudah terkirim dan isinya cuma error -> browser menolak
        // dengan ERR_INVALID_RESPONSE, dan exception di dalam closure tak bisa lagi
        // diubah jadi respons JSON. Menghitung byte lebih dulu membuat kegagalan
        // bisa ditangkap dan dibalas sebagai 502 JSON yang jelas.
        try {
            $pdf = app(PdfService::class)->render($html);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal membuat PDF. Coba lagi sebentar lagi.',
            ], 502);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $name . '_CV.pdf"',
        ]);
    }

    public function print(Request $request, Cv $cv)
    {
        // Signed URL already validated by middleware; data is embedded so the print app needs no authenticated fetch.
        $data = $cv->data ?? [];
        $template = $cv->template ?? 'modern';

        $printHtml = $this->resolvePrintHtml($data, $template, $cv->language ?? 'id');

        return response($printHtml)->header('Content-Type', 'text/html');
    }

    // Generate Cloudinary upload signature for authenticated (signed) client upload.
    // Only the params actually signed must be echoed back to the upload call.
    public function uploadSignature(Request $request): JsonResponse
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            return response()->json(['message' => 'Cloudinary belum dikonfigurasi.'], 500);
        }

        $timestamp = time();
        $params = ['timestamp' => $timestamp];

        // Sign folder (optional, keeps files organized). Sort keys alphabetically.
        if ($folder = config('cloudinary.folder')) {
            $params['folder'] = $folder;
        }
        ksort($params);
        $toBeSigned = collect($params)->map(fn ($v, $k) => $k . '=' . $v)->implode('&');
        $signature = sha1($toBeSigned . $apiSecret);

        return response()->json([
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'timestamp' => (string) $timestamp,
            'signature' => $signature,
            'folder' => $folder ?? '',
        ]);
    }

    private function resolvePrintHtml(array $data, string $template, string $language = 'id'): string
    {
        // Prefer built dist (production) — but only if assets are reachable
        $candidates = [
            base_path('../web/dist/print.html'),
            public_path('print/index.html'),
            base_path('../web/dist/print/index.html'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $html = file_get_contents($path);
                // Shell dev hanya untuk mesin lokal yang menjalankan Vite dev server;
                // di produksi selalu pakai dist hasil build. Menebak lewat HTTP probe
                // tidak aman: SPA catch-all (try_files → index.html) membalas 200 untuk
                // path apa pun, sehingga produksi keliru dianggap sebagai dev server.
                if (app()->environment('local') && $this->isViteDev()) {
                    return $this->minimalPrintShell($data, $template, $language);
                }
                return $this->injectData($html, $data, $template, $language);
            }
        }

        // Dev fallback: load Vite dev server print.html and inject data
        $viteUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/print.html';
        $html = @file_get_contents($viteUrl);
        if ($html !== false && str_contains($html, '/src/print-main.ts')) {
            return $this->injectData($html, $data, $template, $language);
        }

        return $this->minimalPrintShell($data, $template, $language);
    }

    private function isViteDev(): bool
    {
        // Status 200 saja tidak cukup: SPA catch-all juga membalas 200 dengan
        // index.html. Vite dev server yang asli mengirim modul HMR-nya sendiri,
        // jadi kita pastikan body-nya memang milik Vite.
        $viteUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/@vite/client';
        $body = @file_get_contents($viteUrl);

        return $body !== false && str_contains($body, 'import.meta.hot');
    }

    private function injectData(string $html, array $data, string $template, string $language = 'id'): string
    {
        $json = Js::from($data)->toHtml();
        $tpl = Js::from($template)->toHtml();
        $lang = Js::from($language)->toHtml();
        $script = "<script>window.__CV_DATA__={$json};window.__CV_TEMPLATE__={$tpl};window.__CV_LANGUAGE__={$lang};</script>";

        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', $script . '</head>', $html);
        } else {
            $html = $script . $html;
        }

        // Asset paths are relative (/assets/...) — rewrite to SPA origin so
        // Browsershot (loading from API origin) can fetch them.
        $spa = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
        $html = preg_replace('#(src|href)="/(assets/|vite\.svg|favicon\.ico)#', '$1="' . $spa . '/$2', $html);

        return $html;
    }

    private function minimalPrintShell(array $data, string $template, string $language = 'id'): string
    {
        $json = Js::from($data)->toHtml();
        $tpl = Js::from($template)->toHtml();
        $lang = Js::from($language)->toHtml();
        $vite = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
        return <<<HTML
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script>window.__CV_DATA__={$json};window.__CV_TEMPLATE__={$tpl};window.__CV_LANGUAGE__={$lang};</script>
</head><body><div id="print-app"></div><script type="module" src="{$vite}/src/print-main.ts"></script></body></html>
HTML;
    }

    public function translate(Request $request, Cv $cv, TranslationService $translator): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $target = $request->input('target', 'en');
        if (! in_array($target, ['id', 'en'], true)) {
            $target = 'en';
        }

        try {
            $data = $translator->translate($cv->data ?? [], $target);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Layanan terjemahan tidak tersedia'], 502);
        }

        // No save — frontend duplicates the CV with the translated data.
        return response()->json(['data' => $data]);
    }

    private function authorizeOwner(Request $request, Cv $cv): void
    {
        if ($cv->user_id !== $request->user()->id) {
            abort(403, 'Bukan pemilik CV ini.');
        }
    }
}
