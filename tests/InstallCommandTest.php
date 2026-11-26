<?php

namespace Foorintodev\CookieConsent\Tests;

use Statamic\Facades\GlobalSet;

class InstallCommandTest extends TestCase
{
    public function test_cree_le_global_cookies(): void
    {
        $this->artisan('cookie-consent:install')->assertSuccessful();

        $set = GlobalSet::findByHandle('cookie_settings');

        $this->assertNotNull($set);
        $this->assertSame('Cookies', $set->title());
        $this->assertTrue($set->blueprint()->hasField('accept_all_label'));
    }

    public function test_ne_touche_pas_a_un_global_existant(): void
    {
        $this->cookieGlobal(['title' => 'Déjà réglé']);

        $this->artisan('cookie-consent:install')
            ->expectsOutputToContain('existe déjà')
            ->assertSuccessful();

        $this->assertSame('Déjà réglé', GlobalSet::findByHandle('cookie_settings')->inDefaultSite()->get('title'));
    }

    public function test_en_multisite_les_autres_sites_heritent_du_site_par_defaut(): void
    {
        $this->twoSites();

        $this->artisan('cookie-consent:install')->assertSuccessful();

        $origins = GlobalSet::findByHandle('cookie_settings')->origins()->all();

        $this->assertSame(['en' => null, 'fr' => 'en'], $origins);
    }
}
