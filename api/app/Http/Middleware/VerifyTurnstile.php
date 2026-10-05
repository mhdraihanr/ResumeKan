<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Gerbang anti-spam untuk endpoint publik (register): honeypot + Turnstile.
 *
 * Honeypot dicek lebih dulu supaya bot yang jelas (isi field tersembunyi atau
 * submit terlalu cepat) ditolak tanpa memanggil Cloudflare — hemat kuota
 * verifikasi dan trafik sampah tidak sampai menyentuh layanan pihak ketiga.
 */
class VerifyTurnstile
{
    /** Field umpan yang harus tetap kosong; bot biasanya mengisinya. */
    public const HONEYPOT_FIELD = 'website';

    /** Field berisi token waktu dari endpoint /config. */
    public const TIMESTAMP_FIELD = 'spam_token';

    /** Submit lebih cepat dari ini dianggap bot (detik). */
    private const MIN_FILL_SECONDS = 2;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->looksLikeBot($request)) {
            return $this->reject('Permintaan terdeteksi sebagai bot.');
        }

        $secret = (string) config('services.turnstile.secret_key');

        if ($secret === '') {
            // Fail-closed di produksi: secret wajib ada, supaya lupa pasang key
            // tidak diam-diam membuka pintu spam. Lokal/test fail-open agar
            // developer tidak perlu key untuk mengembangkan.
            if (app()->environment('production')) {
                return $this->reject('Verifikasi keamanan belum dikonfigurasi.');
            }

            return $next($request);
        }

        $token = (string) $request->input('cf-turnstile-response', '');

        if ($token === '' || ! $this->verifyWithCloudflare($token, $request, $secret)) {
            return $this->reject('Verifikasi keamanan gagal. Coba lagi.');
        }

        return $next($request);
    }

    private function looksLikeBot(Request $request): bool
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return true;
        }

        $issuedAt = $this->decodeTimestamp($request->input(self::TIMESTAMP_FIELD));

        // Token hilang/rusak: jangan salah tuduh user, biarkan captcha yang menilai.
        if ($issuedAt === null) {
            return false;
        }

        return (now()->getTimestamp() - $issuedAt) < self::MIN_FILL_SECONDS;
    }

    /**
     * Waktu mulai diisi dari token yang ditandatangani server, bukan jam klien,
     * supaya jam perangkat yang meleset tidak bikin user asli ditolak.
     */
    private function decodeTimestamp(mixed $token): ?int
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            return (int) Crypt::decryptString($token);
        } catch (Throwable) {
            return null;
        }
    }

    private function verifyWithCloudflare(string $token, Request $request, string $secret): bool
    {
        try {
            $response = Http::asForm()
                ->timeout((int) config('services.turnstile.timeout', 5))
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (Throwable) {
            // Cloudflare tak terjangkau: fail-closed, jangan jadikan celah.
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }

    private function reject(string $message): Response
    {
        return response()->json(['message' => $message], 422);
    }
}
