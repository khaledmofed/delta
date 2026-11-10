<?php

/*
|--------------------------------------------------------------------------
| Insurer integrations
|--------------------------------------------------------------------------
| One entry per insurer. The `adapter` class implements
| App\Insurers\Contracts\InsurerAdapter. Credentials come from .env only.
*/

return [

    'providers' => [

        'din' => [
            'name' => 'Dubai Insurance',
            'adapter' => App\Insurers\Din\DinAdapter::class,
            'enabled' => env('DIN_ENABLED', false),
            'products' => ['motor'],
            'base_url' => env('DIN_BASE_URL', 'https://uatbrokerportal.dubins.ae'),
            'username' => env('DIN_USERNAME'),
            'password' => env('DIN_PASSWORD'),
            'timeout' => (int) env('DIN_TIMEOUT', 30),
            // Insurer claims hotline shown on /claims. Leave empty until confirmed with the insurer.
            'claims_phone' => env('DIN_CLAIMS_PHONE'),
            // JWT lifetime is not documented by DIN; refresh conservatively.
            'token_ttl_minutes' => 20,
        ],

        'awnic' => [
            'name' => 'Al Wathba Insurance',
            'adapter' => App\Insurers\Awnic\AwnicAdapter::class,
            'enabled' => env('AWNIC_ENABLED', false),
            'products' => ['motor'],
            'base_url' => env('AWNIC_BASE_URL', 'https://insurance-uat.awnic.com/InsureApiBYUAT/API'),
            'partner_id' => env('AWNIC_PARTNER_ID'),
            'api_key' => env('AWNIC_API_KEY'),
            'customer_code' => env('AWNIC_CUSTOMER_CODE'),
            'timeout' => (int) env('AWNIC_TIMEOUT', 30),
            'claims_phone' => env('AWNIC_CLAIMS_PHONE'),
            'product_code' => '9018',
            'quote_process_by' => 'Delta Insurance Services',
        ],

    ],

    'payment' => [
        'success_url' => env('PAYMENT_RETURN_SUCCESS_URL'),
        'fail_url' => env('PAYMENT_RETURN_FAIL_URL'),
    ],

    // Header values never written to api_logs.
    'redact_keys' => ['password', 'Authorization', 'Api-Key', 'api_key', 'base64', 'docContent', 'data'],

];
