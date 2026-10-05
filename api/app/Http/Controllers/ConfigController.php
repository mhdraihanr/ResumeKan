<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi publik untuk frontend. Semua nilai di sini memang boleh dilihat
 * siapa saja; tidak ada rahasia.
 */
class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            // Site key Turnstile publik. Diambil saat runtime (bukan build-time)
            // supaya bisa diganti lewat .env server tanpa rebuild bundle Vite.
            'turnstile_site_key' => (string) config('services.turnstile.site_key', ''),
            // Cap waktu terenkripsi: FE mengirimnya balik saat submit, lalu
            // middleware menghitung berapa lama form diisi (anti submit instan).
            'spam_token' => Crypt::encryptString((string) now()->getTimestamp()),
        ]);
    }
}
