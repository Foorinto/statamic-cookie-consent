<?php

namespace Foorintodev\CookieConsent;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Support\Lang;

/**
 * Liste des cookies pour la politique de confidentialité, générée à partir des mêmes
 * réglages que le bandeau : elle suit chaque service ajouté ou retiré dans le CP.
 * Un tableau par catégorie (seulement celles qui ont un service).
 */
class CookieList
{
    /**
     * @param  array  $options  heading_level (2 à 6), id, class, table_class, link_class,
     *                          open_button (bool), open_class
     */
    public static function render(Settings $settings, array $options = []): string
    {
        $level = (int) ($options['heading_level'] ?? 3);
        $id = trim((string) ($options['id'] ?? '')) ?: 'cookie-list';

        return view('statamic-cookie-consent::list', [
            'categories' => static::categories($settings),
            'level' => $level >= 2 && $level <= 6 ? $level : 3,
            'id' => $id,
            'class' => trim((string) ($options['class'] ?? '')),
            'tableClass' => trim((string) ($options['table_class'] ?? '')),
            'linkClass' => trim((string) ($options['link_class'] ?? '')),
            'labels' => [
                'name' => Lang::get('list.name', $settings->lang()),
                'service' => Lang::get('list.service', $settings->lang()),
                'purpose' => Lang::get('list.purpose', $settings->lang()),
                'duration' => Lang::get('list.duration', $settings->lang()),
            ],
            'openButton' => ($options['open_button'] ?? false)
                ? Banner::openButton($settings, (string) ($options['open_class'] ?? ''))
                : null,
        ])->render();
    }

    /** Les catégories avec une ligne par cookie et les politiques des fournisseurs. */
    public static function categories(Settings $settings): array
    {
        $lang = $settings->lang();

        return array_map(function (array $category) use ($lang) {
            $rows = [];
            $policies = [];
            $withoutCookies = [];

            foreach ($category['services'] as $service) {
                /** @var Service $service */
                foreach ($service->cookies as $cookie) {
                    /** @var Cookie $cookie */
                    $rows[] = [
                        'name' => $cookie->name,
                        'type' => Lang::get("storage.{$cookie->storage}", $lang)
                            .($cookie->host ? ' · '.Lang::get('list.set_by', $lang, ['host' => $cookie->host]) : ''),
                        'service' => $service->name,
                        'provider' => $service->provider,
                        'purpose' => $cookie->purpose,
                        'duration' => $cookie->duration,
                    ];
                }

                if ($service->cookies === []) {
                    $withoutCookies[] = $service->name;
                }

                if ($service->privacyUrl && ! isset($policies[$service->privacyUrl])) {
                    $policies[$service->privacyUrl] = [
                        'url' => $service->privacyUrl,
                        'label' => Lang::get('list.policy', $lang, ['provider' => $service->provider ?: $service->name]),
                    ];
                }
            }

            return [
                'handle' => $category['handle'],
                'name' => $category['name'],
                'description' => $category['description'],
                'rows' => $rows,
                'policies' => array_values($policies),
                'without_cookies' => $withoutCookies
                    ? Lang::get('list.no_cookies', $lang, ['services' => implode(', ', $withoutCookies)])
                    : null,
            ];
        }, $settings->categories());
    }
}
