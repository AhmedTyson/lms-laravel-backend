<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LMS Phone Number & Country Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration options for phone number validation and normalization.
    | Default country is Egypt (EG) with fallback/international support.
    |
    */
    'phone' => [
        'default_country' => env('DEFAULT_PHONE_COUNTRY', 'EG'),
        'allowed_countries' => array_filter(explode(',', env('ALLOWED_PHONE_COUNTRIES', 'EG'))),
        'allow_international' => (bool) env('ALLOW_INTERNATIONAL_PHONE', true),
    ],

    // Future: Phase 11 mailer flips this on; login then withholds tokens until verified.
    'auth' => [
        'require_verified' => (bool) env('AUTH_REQUIRE_VERIFIED', false),
    ],
];
