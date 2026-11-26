<?php

namespace Foorintodev\CookieConsent\Services;

use Foorintodev\CookieConsent\Services\Types\CustomService;
use Foorintodev\CookieConsent\Services\Types\GoogleAnalytics;
use Foorintodev\CookieConsent\Services\Types\MetaPixel;

/**
 * Types de services connus : handle de la ligne du champ « Services » => classe.
 * Ajouter un service intégré = une classe dans Types/, une entrée ici, ses textes
 * dans lang/, son jeu de champs dans SettingsBlueprint et son chargeur côté navigateur.
 */
final class Catalogue
{
    /** @var array<string, class-string<ServiceType>> */
    private static array $types = [
        'meta_pixel' => MetaPixel::class,
        'google_analytics' => GoogleAnalytics::class,
        'custom' => CustomService::class,
    ];

    /** @param class-string<ServiceType> $class */
    public static function register(string $type, string $class): void
    {
        self::$types[$type] = $class;
    }

    public static function types(): array
    {
        return self::$types;
    }

    public static function make(array $set, string $lang): ?Service
    {
        $class = self::$types[$set['type'] ?? ''] ?? null;

        return $class ? $class::make($set, $lang) : null;
    }
}
