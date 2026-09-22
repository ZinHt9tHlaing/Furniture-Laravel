<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which origins, methods, and headers are permitted for
    | cross-origin requests. Set FRONTEND_URL in your .env to lock
    | down allowed origins in production.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    | Routes that should be handled by the CORS middleware.
    | 'api/*' covers all API endpoints; 'sanctum/csrf-cookie' is
    | required for SPA authentication with Laravel Sanctum.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    | HTTP methods allowed for cross-origin requests.
    | '*' permits GET, POST, PUT, PATCH, DELETE, OPTIONS, etc.
    */
    'allowed_methods' => ['*'],

    /*
    | Origins allowed to make cross-origin requests.
    | In production set FRONTEND_URL=https://your-frontend.com in .env.
    | Keep '*' only in local development.
    */
    'allowed_origins' => [env('FRONTEND_URL', '*')],

    /*
    | Regex patterns for dynamically matched allowed origins.
    | Useful when you have multiple subdomains, e.g.:
    |   '#^https://(.+\.)?example\.com$#'
    */
    'allowed_origins_patterns' => [],

    /*
    | Headers the browser is allowed to send with cross-origin requests.
    | '*' covers Content-Type, Authorization, Accept, X-Requested-With, etc.
    */
    'allowed_headers' => ['*'],

    /*
    | Headers the browser is allowed to read from the response.
    | Expose Authorization so clients can store Sanctum tokens.
    */
    'exposed_headers' => ['Authorization'],

    /*
    | How long (in seconds) the browser may cache a pre-flight response.
    | 0 = no caching; set to e.g. 3600 in production to reduce OPTIONS calls.
    */
    'max_age' => 0,

    /*
    | Whether the browser should include cookies / credentials in requests.
    | Required for Sanctum SPA cookie-based auth. Keep false when using
    | token-based auth with 'allowed_origins' => ['*'].
    */
    'supports_credentials' => true,

];
