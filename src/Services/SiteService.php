<?php

namespace Foorintodev\CookieConsent\Services;

use Foorintodev\CookieConsent\Support\Duration;
use Foorintodev\CookieConsent\Support\Lang;

/**
 * Les données nécessaires du site lui-même, toujours listées dans « Nécessaires » :
 * le choix de cookies (stockage local) et, dès qu'un formulaire est envoyé,
 * le cookie de session de Laravel et XSRF-TOKEN. Noms et durées lus dans la config.
 */
final class SiteService
{
    public static function make(string $lang): Service
    {
        $sessionMinutes = (int) config('session.lifetime', 120);
        $sessionDuration = config('session.expire_on_close')
            ? Duration::session($lang)
            : Duration::minutes($sessionMinutes, $lang);

        return new Service(
            id: 'site',
            type: 'site',
            name: Lang::get('services.site.name', $lang),
            category: 'necessary',
            cookies: [
                new Cookie(
                    (string) config('foorintodev-cookie-consent.storage_key'),
                    Duration::days((int) config('foorintodev-cookie-consent.lifetime_days'), $lang),
                    Lang::get('services.site.consent', $lang),
                    storage: 'local',
                ),
                new Cookie((string) config('session.cookie'), $sessionDuration, Lang::get('services.site.session', $lang)),
                new Cookie('XSRF-TOKEN', $sessionDuration, Lang::get('services.site.xsrf', $lang)),
            ],
        );
    }
}
