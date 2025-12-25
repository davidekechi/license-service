<?php

declare(strict_types=1);

return [
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),
    'currency'        => env('PAYMENT_CURRENCY', 'USD'),

    'trial' => [
        'enabled' => true,
        'days'    => 30,
    ],

    'plans' => [
        'free' => [
            'price'    => 0,
            'features' => ['free_modules', 'basic_tracking'],
        ],
        'monthly' => [
            'price'    => 29.99,
            'features' => ['all_courses', 'mentor_sessions', 'certificates'],
        ],
        'annual' => [
            'price'    => 299.99,
            'features' => ['all_courses', 'mentor_sessions', 'certificates', 'priority_support'],
        ],
    ],
];
