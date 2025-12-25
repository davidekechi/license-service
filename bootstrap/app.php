<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Domains\Shared\Middleware\EnsureUserIsAdmin::class,
            'student' => \App\Domains\Shared\Middleware\EnsureUserIsStudent::class,
            'mentor' => \App\Domains\Shared\Middleware\EnsureUserIsMentor::class,
            'mentor.approved' => \App\Domains\Shared\Middleware\EnsureMentorIsApproved::class,
            'email.verified' => \App\Domains\Shared\Middleware\EnsureEmailVerified::class,
            'onboarding.completed' => \App\Domains\Shared\Middleware\EnsureOnboardingCompleted::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        \App\Domains\Auth\Providers\AuthServiceProvider::class,
        \App\Domains\Client\Courses\Providers\CourseServiceProvider::class,
        \App\Domains\Client\Dashboard\Providers\DashboardServiceProvider::class,
        \App\Domains\Client\Payments\Providers\PaymentServiceProvider::class,
        \App\Domains\Client\AIRecommendation\Providers\AIRecommendationServiceProvider::class,
        \App\Domains\Mentor\Courses\Providers\CourseServiceProvider::class,
        \App\Domains\Mentor\Dashboard\Providers\DashboardServiceProvider::class,
    ])
    ->create();
