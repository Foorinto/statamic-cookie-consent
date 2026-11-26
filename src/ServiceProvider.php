<?php

namespace Foorintodev\CookieConsent;

use Foorintodev\CookieConsent\Tags\CookieConsent;
use Statamic\Facades\Blueprint;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        CookieConsent::class,
    ];

    // JS et CSS du bandeau, sans compilation, copiés dans public/vendor/statamic-cookie-consent/
    // (automatiquement à l'installation, ou `vendor:publish --tag=statamic-cookie-consent --force`).
    protected $publishables = [
        __DIR__.'/../resources/dist' => '',
    ];

    // La commande src/Commands/InstallCommand (cookie-consent:install) est chargée
    // automatiquement par Statamic. Les textes par défaut sont dans lang/<langue>/messages.php
    // (namespace « statamic-cookie-consent », enregistré par Statamic).

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cookie-consent.php', 'foorintodev-cookie-consent');

        // Blueprint du global « Cookies » fourni par l'addon. Un fichier
        // resources/blueprints/globals/<handle>.yaml dans le site reste prioritaire.
        Blueprint::setFallback(
            'globals.'.config('foorintodev-cookie-consent.global'),
            fn () => SettingsBlueprint::make()
        );

        // `php artisan vendor:publish --tag=foorintodev-cookie-consent` publie la config.
        $this->publishes([
            __DIR__.'/../config/cookie-consent.php' => config_path('foorintodev-cookie-consent.php'),
        ], 'foorintodev-cookie-consent');
    }
}
