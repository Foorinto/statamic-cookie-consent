<?php

namespace Foorintodev\CookieConsent\Services\Types;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Services\ServiceType;
use Foorintodev\CookieConsent\Support\Duration;
use Foorintodev\CookieConsent\Support\Lang;

/**
 * Pixel Meta (Facebook, Instagram). Durées d'après la documentation de Meta :
 * _fbp et _fbc 90 jours sur le site, fr 90 jours sur facebook.com.
 *
 * Le script de configuration du pixel (connect.facebook.net/signals/config/<id>) range
 * aussi dans le stockage local lastExternalReferrer (« fb », « ig », « other » ou
 * « empty » : d'où vient le visiteur, joint à chaque événement) et
 * lastExternalReferrerTime (quand), qu'il efface lui-même au bout de 90 jours.
 * Déclarés ici, ils sont décrits dans la liste et effacés au refus.
 */
final class MetaPixel implements ServiceType
{
    public static function make(array $set, string $lang): ?Service
    {
        $pixelId = trim((string) ($set['pixel_id'] ?? ''));

        if (! preg_match('/^\d{10,20}$/', $pixelId)) {
            return null;
        }

        $months = Duration::days(90, $lang);

        return new Service(
            id: (string) ($set['id'] ?? 'meta_pixel'),
            type: 'meta_pixel',
            name: Lang::get('services.meta_pixel.name', $lang),
            category: 'marketing',
            cookies: [
                new Cookie('_fbp', $months, Lang::get('services.meta_pixel._fbp', $lang)),
                new Cookie('_fbc', $months, Lang::get('services.meta_pixel._fbc', $lang)),
                new Cookie('lastExternalReferrer', $months, Lang::get('services.meta_pixel.lastExternalReferrer', $lang), storage: 'local'),
                new Cookie('lastExternalReferrerTime', $months, Lang::get('services.meta_pixel.lastExternalReferrerTime', $lang), storage: 'local'),
                new Cookie('fr', $months, Lang::get('services.meta_pixel.fr', $lang), host: 'facebook.com'),
            ],
            provider: 'Meta Platforms Ireland Ltd',
            privacyUrl: 'https://www.facebook.com/privacy/policy/',
            loader: ['type' => 'meta_pixel', 'pixelId' => $pixelId],
        );
    }
}
