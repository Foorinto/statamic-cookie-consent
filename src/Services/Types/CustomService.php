<?php

namespace Foorintodev\CookieConsent\Services\Types;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Services\ServiceType;
use Foorintodev\CookieConsent\Settings;

/** Service décrit à la main dans le CP : nom, catégorie, cookies et, éventuellement, code. */
final class CustomService implements ServiceType
{
    public static function make(array $set, string $lang): ?Service
    {
        $name = trim((string) ($set['name'] ?? ''));
        $category = (string) ($set['category'] ?? '');

        if ($name === '' || ! in_array($category, Settings::CATEGORIES, true)) {
            return null;
        }

        $cookies = collect($set['cookies'] ?? [])
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['name'] ?? '')) !== '')
            ->map(fn ($row) => new Cookie(
                name: trim((string) $row['name']),
                duration: trim((string) ($row['duration'] ?? '')),
                purpose: trim((string) ($row['purpose'] ?? '')),
                storage: in_array($row['storage'] ?? null, Cookie::STORAGES, true) ? $row['storage'] : 'cookie',
                host: trim((string) ($row['host'] ?? '')) ?: null,
            ))
            ->values()
            ->all();

        // Le champ « code » peut renvoyer une chaîne ou ['code' => …, 'mode' => …].
        $script = $set['script'] ?? null;
        $script = trim((string) (is_array($script) ? ($script['code'] ?? '') : $script));

        return new Service(
            id: (string) ($set['id'] ?? 'custom-'.md5($name)),
            type: 'custom',
            name: $name,
            category: $category,
            cookies: $cookies,
            provider: trim((string) ($set['provider'] ?? '')) ?: null,
            privacyUrl: trim((string) ($set['privacy_url'] ?? '')) ?: null,
            loader: $script === '' ? [] : ['type' => 'html', 'html' => $script],
        );
    }
}
