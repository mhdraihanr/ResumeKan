<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate limit register
    |--------------------------------------------------------------------------
    |
    | Batas pendaftaran akun, dipakai RateLimiter `register` di
    | AppServiceProvider. Dua lapis sengaja: `per_minute` menahan ledakan
    | instan (burst), `per_hour` menahan akumulasi spam dalam jendela lebih
    | panjang. Nilainya bisa diubah lewat .env tanpa menyentuh kode.
    |
    | Catatan: user asli mendaftar sekali, jadi angka ini boleh ketat.
    | Register juga masih dijaga Turnstile + honeypot + jeda waktu.
    |
    */

    'register' => [
        'per_minute' => (int) env('REGISTER_PER_MINUTE', 5),
        'per_hour' => (int) env('REGISTER_PER_HOUR', 20),
    ],
];
