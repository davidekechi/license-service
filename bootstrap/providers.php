<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Domains\Auth\Providers\AuthServiceProvider::class,
    App\Domains\Client\Courses\Providers\CourseServiceProvider::class,
    App\Domains\Client\AIRecommendation\Providers\AIRecommendationServiceProvider::class,
    App\Domains\Client\Payments\Providers\PaymentServiceProvider::class,
    App\Domains\Client\Dashboard\Providers\DashboardServiceProvider::class,
    App\Domains\Mentor\Courses\Providers\CourseServiceProvider::class,
    App\Domains\Mentor\Dashboard\Providers\DashboardServiceProvider::class,
];
