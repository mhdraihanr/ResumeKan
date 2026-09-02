<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvRequest;
use App\Http\Resources\CvResource;
use App\Models\Cv;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Js;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $cv = $request->user()->cvs()->create($request->validated());

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

        $cv->update($request->validated());

        return response()->json(['cv' => new CvResource($cv)]);
    }

    public function destroy(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $cv->delete();

        return response()->json(null, 204);
    }

    public function pdf(Request $request, Cv $cv): StreamedResponse
    {
        $this->authorizeOwner($request, $cv);

        $name = preg_replace('/[^\p{L}\p{N} _-]/u', '', $cv->data['personal']['name'] ?? 'CV') ?: 'CV';
        $html = $this->resolvePrintHtml($cv->data ?? [], $cv->template ?? 'modern', $cv->language ?? 'id');

        return response()->streamDownload(function () use ($html) {
            echo app(PdfService::class)->render($html);
        }, $name . '_CV.pdf', [
            'Content-Type' => 'application/pdf',
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
                // In dev, Vite dev server doesn't serve /assets/* from dist — use minimal shell instead
                if ($this->isViteDev()) {
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
        $viteUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/@vite/client';
        $headers = @get_headers($viteUrl);
        return $headers !== false && str_contains($headers[0] ?? '', '200');
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

    private function authorizeOwner(Request $request, Cv $cv): void
    {
        if ($cv->user_id !== $request->user()->id) {
            abort(403, 'Bukan pemilik CV ini.');
        }
    }
}
