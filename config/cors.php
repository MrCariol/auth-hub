<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    /*
    | Il valore di APP_DOMAIN (es. "example.com") definisce il dominio radice
    | condiviso da hub e PWA: qualunque suo sottodominio, su http o https, è
    | ammesso come origine CORS. Va impostato nel .env di ogni ambiente.
    */

    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\.)*'.preg_quote(env('APP_DOMAIN', 'localhost'), '#').'(:\d+)?$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
