<?php

namespace Foorintodev\CookieConsent\Detection;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Settings;
use Illuminate\Support\Str;
use Statamic\Facades\Site;

/**
 * Noms déclarés (cookies des services) et ignorés, avec le joker « * ». Ce qui n'y
 * correspond pas est « non déclaré » et remonte dans l'onglet Détection du CP.
 */
final class Names
{
    /** Caractères admis dans un nom signalé : ASCII imprimable, sans espace, 100 au plus. */
    public const VALID = '/^[\x21-\x7E]{1,100}$/';

    /**
     * Tous les noms déclarés ou ignorés, sur tous les sites (un nom déclaré sur un site
     * ne remonte pas depuis un autre).
     *
     * @return string[]
     */
    public static function known(): array
    {
        $patterns = (array) config('foorintodev-cookie-consent.ignore', []);

        foreach (Site::all() as $site) {
            $settings = Settings::forSite($site->handle());

            foreach ($settings->services() as $service) {
                foreach ($service->cookies as $cookie) {
                    /** @var Cookie $cookie */
                    $patterns[] = $cookie->name;
                }
            }

            array_push($patterns, ...$settings->ignored());
        }

        return array_values(array_unique(array_filter(array_map('trim', $patterns))));
    }

    /** @param  string[]  $patterns */
    public static function matches(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $name || Str::is($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nom tel qu'il est rangé : une longue suite hexadécimale avec au moins un chiffre
     * (souvent un identifiant propre au visiteur) devient « * ». Même règle que dans le
     * navigateur.
     */
    public static function normalize(string $name): string
    {
        return (string) preg_replace_callback(
            '/[0-9a-fA-F]{12,}/',
            fn ($m) => preg_match('/\d/', $m[0]) ? '*' : $m[0],
            trim($name),
        );
    }
}
