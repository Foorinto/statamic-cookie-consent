<?php

namespace Foorintodev\CookieConsent;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;

/**
 * Rendu du bandeau : le même HTML pour tous les visiteurs (compatible avec le cache
 * statique complet). Le choix du visiteur n'est lu et appliqué que dans le navigateur,
 * par resources/dist/cookie-consent.js, à partir de la config JSON écrite dans la page.
 */
class Banner
{
    public const ASSETS_DIR = __DIR__.'/../resources/dist';

    public const PUBLIC_PATH = '/vendor/statamic-cookie-consent';

    /**
     * @param  array  $options  button_class, secondary_class, link_class, styles (bool)
     */
    public static function render(Settings $settings, array $options = []): string
    {
        $buttonClass = trim((string) ($options['button_class'] ?? '')) ?: 'cc-btn--default';

        return view('statamic-cookie-consent::banner', [
            'texts' => $settings->texts(),
            'policyUrl' => $settings->policyUrl(),
            'categories' => $settings->categories(),
            'config' => static::config($settings),
            'buttonClass' => $buttonClass,
            'secondaryClass' => trim((string) ($options['secondary_class'] ?? ''))
                ?: ($buttonClass === 'cc-btn--default' ? 'cc-btn--default cc-btn--secondary' : $buttonClass),
            'linkClass' => trim((string) ($options['link_class'] ?? '')),
            'styles' => ($options['styles'] ?? true) !== false,
            'css' => static::assetUrl('cookie-consent.css'),
            'js' => static::assetUrl('cookie-consent.js'),
        ])->render();
    }

    /**
     * Config lue par le navigateur. JSON_HEX_TAG : le code d'un service personnalisé
     * ne peut pas fermer la balise <script> qui le contient.
     */
    public static function config(Settings $settings): string
    {
        return json_encode([
            // false : le navigateur n'affiche pas le bandeau et ne charge aucun service
            // soumis à l'accord (la détection et les services « Nécessaires » restent).
            'active' => $settings->active(),
            'version' => $settings->version(),
            'storageKey' => (string) config('foorintodev-cookie-consent.storage_key'),
            'lifetimeDays' => (int) config('foorintodev-cookie-consent.lifetime_days'),
            'categories' => array_column($settings->categories(), 'handle'),
            // Détection : adresse de signalement (relative : la même page en cache sert tous
            // les domaines) et noms à ne jamais signaler.
            'report' => config('foorintodev-cookie-consent.detection') ? static::reportUrl() : null,
            'ignore' => array_values(array_unique([
                ...(array) config('foorintodev-cookie-consent.ignore', []),
                ...$settings->ignored(),
            ])),
            'services' => array_map(fn (Service $service) => [
                'id' => $service->id,
                'category' => $service->category,
                'loader' => $service->loader ?: null,
                // Seulement ce que le navigateur peut effacer lui-même au retrait de l'accord :
                // les cookies du site (pas ceux de facebook.com…) et les données stockées.
                'cookies' => array_values(array_map(
                    fn (Cookie $cookie) => ['name' => $cookie->name, 'storage' => $cookie->storage],
                    array_filter($service->cookies, fn (Cookie $cookie) => $cookie->host === null),
                )),
            ], $settings->services()),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Bouton qui rouvre le bandeau (pied de page, liste des cookies). Caché tant que
     * cookie-consent.js n'a pas démarré : sans JavaScript, il ne ferait rien. Rien du tout
     * quand le bandeau n'est pas proposé (coupé dans le CP, ou rien à accepter).
     *
     * @param  string|null  $labelHtml  libellé déjà sûr (contenu d'une balise en paire) ;
     *                                  sinon le texte « reopen_label », échappé.
     */
    public static function openButton(Settings $settings, string $class = '', ?string $labelHtml = null): string
    {
        if (! $settings->active()) {
            return '';
        }

        $label = $labelHtml ?? e($settings->text('reopen_label'));
        $class = trim($class);

        return '<button type="button"'.($class !== '' ? ' class="'.e($class).'"' : '')
            .' data-cookie-consent-open hidden>'.$label.'</button>';
    }

    public static function reportUrl(): string
    {
        $base = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');

        return $base.'/'.trim((string) config('statamic.routes.action', '!'), '/').'/statamic-cookie-consent/report';
    }

    /** Adresse publiée (public/vendor/…), avec une empreinte du fichier contre les vieux caches. */
    public static function assetUrl(string $file): string
    {
        static $hashes = [];

        $hashes[$file] ??= substr((string) @md5_file(static::ASSETS_DIR.'/'.$file), 0, 8);

        $base = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');

        return $base.static::PUBLIC_PATH.'/'.$file.'?v='.$hashes[$file];
    }
}
