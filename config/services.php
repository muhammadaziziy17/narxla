<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Bo'sh bo'lsa callback manzil joriy so'rov domenidan olinadi.
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: '/auth/google/callback',
    ],

    'telegram' => [
        // @BotFather → Login Widget: Client ID / Client Secret va ruxsat etilgan URL'lar.
        'client_id' => env('TELEGRAM_CLIENT_ID'),
        'client_secret' => env('TELEGRAM_CLIENT_SECRET'),
        // Bo'sh bo'lsa callback manzil joriy so'rov domenidan olinadi.
        'redirect' => env('TELEGRAM_REDIRECT_URI') ?: '/auth/telegram/callback',
        // Bot orqali xabar yuborish uchun (login uchun shart emas).
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        // Yangi baholash so'rovlari haqida xabar boradigan chat (admin).
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    ],

    'cloudflare' => [
        // Workers AI — model e'lonlarni tahlil qiladi (tez va JSON'ga ishonchli).
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'model' => env('CLOUDFLARE_MODEL', '@cf/meta/llama-3.3-70b-instruct-fp8-fast'),
    ],

    'tavily' => [
        // Bozor e'lonlarini internetdan qidirish.
        'api_key' => env('TAVILY_API_KEY'),
        // Oylik bepul kvotani tugatib qo'ymaslik uchun zaxira chegarasi.
        'monthly_limit' => (int) env('TAVILY_MONTHLY_LIMIT', 900),
        // Ishlatilgan telefon bozorlari (yangi telefon do'konlari emas).
        'domains' => ['olx.uz', 'birbir.uz'],
    ],

    'jina' => [
        // CloudFront PHP so'rovlarini bloklaydi — OLX sahifasini Jina Reader o'qiydi.
        // Kalit ixtiyoriy (kalitsiz ham bepul limit ishlaydi).
        'api_key' => env('JINA_API_KEY'),
    ],

    'gemini' => [
        // Google Gemini AI — Cloudflare ishlamay qolganda avtomatik zaxira tahlilchi
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    ],

];
