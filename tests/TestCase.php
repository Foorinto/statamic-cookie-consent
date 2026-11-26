<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\ServiceProvider;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    /**
     * Crée le global « Cookies » avec ces valeurs dans le site par défaut. En multisite,
     * les autres sites en héritent (comme après cookie-consent:install).
     */
    protected function cookieGlobal(array $data = []): void
    {
        $set = GlobalSet::make(config('foorintodev-cookie-consent.global'))->title('Cookies');

        if (Site::multiEnabled()) {
            $default = Site::default()->handle();
            $set->sites(Site::all()->keys()->mapWithKeys(fn ($site) => [$site => $site === $default ? null : $default])->all());
        }

        $set->save();

        $set->in(Site::default()->handle())->data($data)->save();
    }

    /** Deux sites : en (défaut) et fr. */
    protected function twoSites(): void
    {
        config(['statamic.system.multisite' => true, 'statamic.editions.pro' => true]);

        Site::setSites([
            'en' => ['name' => 'English', 'url' => '/', 'locale' => 'en_GB'],
            'fr' => ['name' => 'Français', 'url' => '/fr/', 'locale' => 'fr_FR'],
        ]);
    }
}
