<?php

namespace Foorintodev\CookieConsent\Support;

/** Raccourcis vers les textes de l'addon (lang/<langue>/messages.php). */
final class Lang
{
    public const NAMESPACE = 'statamic-cookie-consent';

    public static function get(string $key, ?string $lang = null, array $replace = []): string
    {
        return __(self::NAMESPACE.'::messages.'.$key, $replace, $lang);
    }

    public static function choice(string $key, int $count, ?string $lang = null): string
    {
        return trans_choice(self::NAMESPACE.'::messages.'.$key, $count, [], $lang);
    }
}
