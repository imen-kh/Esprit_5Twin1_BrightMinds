<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    // Module Environnement : rédaction de l'alternative locale par ChatGPT (API OpenAI)
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => 20,
    ],

    // Module Traçabilité : histoire du produit et analyse des anomalies par IA (API Google Gemini)
    'gemini' => [
        'enabled' => env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
        'secours' => array_filter(array_map('trim', explode(',', (string) env('GEMINI_MODELES_SECOURS', 'gemini-3.7-flash,gemini-flash-lite-latest')))),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
    ],

    'forecast' => [
        'mode' => env('FORECAST_MODE', 'http'),
        'url' => env('FORECAST_API_URL', 'http://127.0.0.1:8001/predict'),
        'python' => env('FORECAST_PYTHON', 'auto'),
        'fallback' => (bool) env('FORECAST_FALLBACK', false),
        'timeout' => (int) env('FORECAST_API_TIMEOUT', 15),
        // Modèles essayés ensuite si le principal est saturé, au-dessus du quota ou retiré
        'timeout' => (int) env('GEMINI_TIMEOUT', 40),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
    ],

    // Module Réclamations & Avis : analyse, modération, alertes qualité et agent « Sahbi » (API DeepSeek, format OpenAI)
    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'base_url' => rtrim((string) env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'), '/'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 30),
    ],

    'openmeteo' => [
        'forecast_url' => env('OPENMETEO_FORECAST_URL', 'https://api.open-meteo.com/v1/forecast'),
        'geocode_url' => env('OPENMETEO_GEOCODE_URL', 'https://geocoding-api.open-meteo.com/v1/search'),
        'cache_minutes' => (int) env('OPENMETEO_CACHE_MINUTES', 30),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
