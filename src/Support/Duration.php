<?php

namespace Foorintodev\CookieConsent\Support;

/**
 * Durées lisibles, dans la langue du site : « 2 hours », « 6 mois », « 2 Jahre ».
 * Les durées des services intégrés sont données ici en minutes ou en jours et
 * converties dans l'unité la plus naturelle.
 */
final class Duration
{
    public static function of(int $count, string $unit, ?string $lang = null): string
    {
        return Lang::choice("durations.{$unit}", $count, $lang);
    }

    public static function session(?string $lang = null): string
    {
        return Lang::get('durations.session', $lang);
    }

    public static function minutes(int $minutes, ?string $lang = null): string
    {
        if ($minutes <= 0) {
            return self::session($lang);
        }

        if ($minutes % 1440 === 0) {
            return self::days(intdiv($minutes, 1440), $lang);
        }

        return $minutes % 60 === 0
            ? self::of(intdiv($minutes, 60), 'hour', $lang)
            : self::of($minutes, 'minute', $lang);
    }

    public static function days(int $days, ?string $lang = null): string
    {
        if ($days % 365 === 0) {
            return self::of(intdiv($days, 365), 'year', $lang);
        }

        // 30, 90, 182 jours… : « 1 mois », « 3 mois », « 6 mois » (à un jour et demi près).
        $months = (int) round($days / 30.44);

        if ($months >= 1 && abs($days - $months * 30.44) <= 1.5) {
            return self::of($months, 'month', $lang);
        }

        return self::of($days, 'day', $lang);
    }
}
