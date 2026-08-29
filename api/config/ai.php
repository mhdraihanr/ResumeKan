<?php

return [
    'api_key' => env('AI_API_KEY', ''),
    'base_url' => env('AI_BASE_URL', 'https://api.example.com/v1'),
    'model' => env('AI_MODEL', 'provider/model-name'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
];
