<?php

return [
    'api_key' => env('AI_API_KEY', ''),
    'base_url' => env('AI_BASE_URL', 'https://api.example.com/v1'),
    'model' => env('AI_MODEL', 'provider/model-name'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    // Batas request AI per menit per user. Dipakai RateLimiter `ai` di
    // AppServiceProvider; nilainya bisa diubah lewat .env tanpa ubah kode.
    'throttle_per_minute' => (int) env('AI_THROTTLE_PER_MINUTE', 5),
];
