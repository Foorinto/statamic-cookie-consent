<?php

namespace Foorintodev\CookieConsent\Services\Types;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Services\ServiceType;
use Foorintodev\CookieConsent\Support\Duration;
use Foorintodev\CookieConsent\Support\Lang;

/**
 * Google Analytics 4. Durées d'après la documentation de Google : _ga et
 * _ga_<identifiant> 2 ans. Le second porte l'ID de mesure sans « G- ».
 */
final class GoogleAnalytics implements ServiceType
{
    public static function make(array $set, string $lang): ?Service
    {
        $measurementId = strtoupper(trim((string) ($set['measurement_id'] ?? '')));

        if (! preg_match('/^G-[A-Z0-9]{4,20}$/', $measurementId)) {
            return null;
        }

        $years = Duration::of(2, 'year', $lang);

        return new Service(
            id: (string) ($set['id'] ?? 'google_analytics'),
            type: 'google_analytics',
            name: Lang::get('services.google_analytics.name', $lang),
            category: 'analytics',
            cookies: [
                new Cookie('_ga', $years, Lang::get('services.google_analytics._ga', $lang)),
                new Cookie('_ga_'.substr($measurementId, 2), $years, Lang::get('services.google_analytics._ga_id', $lang)),
            ],
            provider: 'Google Ireland Ltd',
            privacyUrl: 'https://policies.google.com/privacy',
            loader: ['type' => 'google_analytics', 'measurementId' => $measurementId],
        );
    }
}
