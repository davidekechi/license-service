<?php

declare(strict_types=1);

return [
    'rate_limit' => [
        'api'        => env('GENERAL_LIMIT', 120),
        'brand_api'  => env('BRAND_LIMIT', 120),
        'public_api' => env('PUBLIC_LIMIT', 30),
        'activation' => env('ACTIVATION_LIMIT', 10),
    ]
];
