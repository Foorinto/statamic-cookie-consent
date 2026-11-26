<?php

namespace Foorintodev\CookieConsent\Http\Controllers;

use Foorintodev\CookieConsent\Detection\Detections;
use Foorintodev\CookieConsent\Detection\Names;
use Foorintodev\CookieConsent\Services\Cookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * POST /!/statamic-cookie-consent/report  { items: [{ name, storage }], page: "/chemin" }
 * envoyé par cookie-consent.js. Répond toujours 204 : rien à apprendre pour qui essaie.
 * Sans session ni cookie (voir routes/actions.php), limité en nombre d'envois par adresse IP.
 */
class ReportController
{
    public const MAX_ITEMS = 20;

    public function __invoke(Request $request): Response
    {
        if (! config('foorintodev-cookie-consent.detection')) {
            return response()->noContent();
        }

        $items = [];

        foreach (array_slice((array) $request->input('items', []), 0, static::MAX_ITEMS) as $item) {
            $name = is_array($item) && is_string($item['name'] ?? null) ? $item['name'] : '';
            $storage = is_array($item) ? ($item['storage'] ?? null) : null;

            if (preg_match(Names::VALID, $name) && in_array($storage, Cookie::STORAGES, true)) {
                $name = Names::normalize($name);
                $items[$storage.':'.$name] = ['name' => $name, 'storage' => $storage];
            }
        }

        if ($items === []) {
            return response()->noContent();
        }

        $known = Names::known();
        $items = array_filter($items, fn ($item) => ! Names::matches($item['name'], $known));

        if ($items !== []) {
            Detections::record(array_values($items), $this->page($request->input('page')), $known);
        }

        return response()->noContent();
    }

    /** Le chemin seul : ni domaine, ni paramètres (qui peuvent contenir des données personnelles). */
    private function page(mixed $page): ?string
    {
        if (! is_string($page) || ! str_starts_with($page, '/')) {
            return null;
        }

        $path = (string) parse_url($page, PHP_URL_PATH);

        return preg_match('#^/[\x21-\x7E]{0,199}$#', $path) ? $path : null;
    }
}
