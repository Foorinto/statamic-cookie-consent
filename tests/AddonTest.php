<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\ServiceProvider;
use Statamic\Facades\Addon;

class AddonTest extends TestCase
{
    public function test_l_addon_est_charge_par_statamic(): void
    {
        $this->assertNotNull(Addon::get('foorintodev/statamic-cookie-consent'));
    }

    public function test_la_config_par_defaut_est_fusionnee(): void
    {
        $this->assertSame('cookie_settings', config('foorintodev-cookie-consent.global'));
        $this->assertSame(182, config('foorintodev-cookie-consent.lifetime_days'));
        $this->assertSame('cookie-consent', config('foorintodev-cookie-consent.storage_key'));
    }

    public function test_les_fichiers_publiables_existent(): void
    {
        $paths = ServiceProvider::pathsToPublish(ServiceProvider::class, 'foorintodev-cookie-consent');

        $this->assertNotEmpty($paths);

        foreach (array_keys($paths) as $source) {
            $this->assertFileExists($source);
        }
    }
}
