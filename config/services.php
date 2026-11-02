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
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        // Modèles essayés ensuite si le principal est saturé, au-dessus du quota ou retiré
        'secours' => array_filter(array_map('trim', explode(',', (string) env('GEMINI_MODELES_SECOURS', 'gemini-3.1-flash-lite')))),
        'timeout' => 40, // secondes : une photo peut prendre plus longtemps qu'un texte
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
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
