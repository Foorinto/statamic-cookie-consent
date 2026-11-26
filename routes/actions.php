<?php

use Foorintodev\CookieConsent\Http\Controllers\ReportController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Statamic\StaticCaching\Middleware\Cache;

// /!/statamic-cookie-consent/report : signalement des noms non déclarés (cookie-consent.js).
// Envoyé depuis des pages en cache statique, sans cookie : ni session, ni jeton CSRF, ni
// cookie en réponse. 30 envois par minute et par adresse IP au plus.
Route::post('report', ReportController::class)
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class, // Laravel 13 ; ValidateCsrfToken / VerifyCsrfToken avant
        ValidateCsrfToken::class,
        VerifyCsrfToken::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        Cache::class,
    ])
    ->middleware('throttle:30,1');
