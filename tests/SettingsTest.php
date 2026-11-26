<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\Services\Cookie;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Settings;
use Statamic\Facades\GlobalSet;

class SettingsTest extends TestCase
{
    private function names(array $cookies): array
    {
        return array_map(fn (Cookie $cookie) => $cookie->name, $cookies);
    }

    private function categoryHandles(Settings $settings): array
    {
        return array_column($settings->categories(), 'handle');
    }

    public function test_sans_global_tout_est_par_defaut(): void
    {
        $settings = Settings::forSite();

        $this->assertFalse($settings->exists());
        $this->assertSame('We use cookies', $settings->text('title'));
        $this->assertSame('Accept all', $settings->text('accept_all_label'));
        $this->assertSame('Decline all', $settings->text('decline_all_label'));
        $this->assertNull($settings->policyUrl());
        $this->assertSame(['necessary'], $this->categoryHandles($settings));
    }

    public function test_les_saisies_du_cp_remplacent_les_textes_par_defaut(): void
    {
        $this->cookieGlobal([
            'title' => 'Cookies on this site',
            'accept_all_label' => 'OK for me',
            'decline_all_label' => '   ', // vide : texte par défaut
            'marketing_name' => 'Advertising',
        ]);

        $settings = Settings::forSite();

        $this->assertSame('Cookies on this site', $settings->text('title'));
        $this->assertSame('OK for me', $settings->text('accept_all_label'));
        $this->assertSame('Decline all', $settings->text('decline_all_label'));
        $this->assertCount(count(Settings::TEXTS), $settings->texts());
    }

    public function test_le_lien_vers_la_politique(): void
    {
        $this->cookieGlobal(['policy_link' => 'https://example.com/privacy']);

        $this->assertSame('https://example.com/privacy', Settings::forSite()->policyUrl());
    }

    public function test_le_site_lui_meme_est_toujours_dans_necessaires(): void
    {
        config(['session.cookie' => 'mon_site_session', 'session.lifetime' => 120]);

        $necessary = Settings::forSite()->categories()[0];
        $site = $necessary['services'][0];

        $this->assertTrue($necessary['required']);
        $this->assertSame('site', $site->id);
        $this->assertSame(['cookie-consent', 'mon_site_session', 'XSRF-TOKEN'], $this->names($site->cookies));
        $this->assertSame('local', $site->cookies[0]->storage);
        $this->assertSame('6 months', $site->cookies[0]->duration);
        $this->assertSame('2 hours', $site->cookies[1]->duration);
        $this->assertSame([], $site->loader);
    }

    public function test_le_pixel_meta_ajoute_la_categorie_marketing(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'm1', 'type' => 'meta_pixel', 'enabled' => true, 'pixel_id' => '1858784328492314'],
        ]]);

        $settings = Settings::forSite();
        $marketing = collect($settings->categories())->firstWhere('handle', 'marketing');
        $meta = $marketing['services'][0];

        $this->assertSame(['necessary', 'marketing'], $this->categoryHandles($settings));
        $this->assertSame('Meta Pixel', $meta->name);
        $this->assertSame('Meta Platforms Ireland Ltd', $meta->provider);
        $this->assertSame(['_fbp', '_fbc', 'fr'], $this->names($meta->cookies));
        $this->assertSame('facebook.com', $meta->cookies[2]->host);
        $this->assertSame('3 months', $meta->cookies[0]->duration);
        $this->assertSame(['type' => 'meta_pixel', 'pixelId' => '1858784328492314'], $meta->loader);
    }

    public function test_google_analytics_ajoute_la_categorie_mesure_d_audience(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'g1', 'type' => 'google_analytics', 'measurement_id' => 'g-abc123xyz'],
        ]]);

        $analytics = collect(Settings::forSite()->categories())->firstWhere('handle', 'analytics');
        $ga = $analytics['services'][0];

        $this->assertSame(['_ga', '_ga_ABC123XYZ'], $this->names($ga->cookies));
        $this->assertSame('2 years', $ga->cookies[0]->duration);
        $this->assertSame(['type' => 'google_analytics', 'measurementId' => 'G-ABC123XYZ'], $ga->loader);
    }

    public function test_un_service_personnalise(): void
    {
        $this->cookieGlobal(['services' => [
            [
                'id' => 'c1', 'type' => 'custom', 'name' => 'Accessibility panel', 'provider' => 'Omonia',
                'category' => 'preferences',
                'cookies' => [
                    ['name' => 'omonia-a11y', 'storage' => 'local', 'duration' => 'Until you reset it', 'purpose' => 'Remembers your display options.'],
                    ['name' => '  '], // ligne vide : ignorée
                ],
                'script' => ['code' => '<script>console.log(1)</script>', 'mode' => 'htmlmixed'],
            ],
        ]]);

        $preferences = collect(Settings::forSite()->categories())->firstWhere('handle', 'preferences');
        $custom = $preferences['services'][0];

        $this->assertSame('Accessibility panel', $custom->name);
        $this->assertSame(['omonia-a11y'], $this->names($custom->cookies));
        $this->assertSame('local', $custom->cookies[0]->storage);
        $this->assertSame(['type' => 'html', 'html' => '<script>console.log(1)</script>'], $custom->loader);
    }

    public function test_les_services_desactives_ou_invalides_sont_ignores(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'a', 'type' => 'meta_pixel', 'enabled' => false, 'pixel_id' => '1858784328492314'],
            ['id' => 'b', 'type' => 'meta_pixel', 'pixel_id' => 'abc'],
            ['id' => 'c', 'type' => 'google_analytics', 'measurement_id' => 'UA-12345-1'],
            ['id' => 'd', 'type' => 'custom', 'name' => 'Sans catégorie', 'category' => 'inconnue'],
            ['id' => 'e', 'type' => 'type_inconnu'],
        ]]);

        $services = Settings::forSite()->services();

        $this->assertSame(['site'], array_map(fn (Service $s) => $s->id, $services));
    }

    public function test_la_version_change_quand_les_services_changent(): void
    {
        $this->cookieGlobal(['title' => 'A']);
        $initial = Settings::forSite()->version();

        $variables = GlobalSet::findByHandle('cookie_settings')->inDefaultSite();

        // Un texte modifié : même version, le bandeau ne revient pas.
        $variables->set('title', 'B')->save();
        $this->assertSame($initial, Settings::forSite()->version());

        // Un service ajouté : nouvelle version, l'accord est redemandé.
        $variables->set('services', [['id' => 'g1', 'type' => 'google_analytics', 'measurement_id' => 'G-ABC123']])->save();
        $withGa = Settings::forSite()->version();
        $this->assertNotSame($initial, $withGa);

        // Le même service avec un autre identifiant de mesure : même version.
        $variables->set('services', [['id' => 'g1', 'type' => 'google_analytics', 'measurement_id' => 'G-XYZ789']])->save();
        $this->assertSame($withGa, Settings::forSite()->version());
    }

    public function test_les_textes_par_defaut_suivent_la_langue_du_site(): void
    {
        $this->twoSites();

        $fr = Settings::forSite('fr');

        $this->assertSame('fr', $fr->lang());
        $this->assertSame('Tout accepter', $fr->text('accept_all_label'));
        $this->assertSame('Tout refuser', $fr->text('decline_all_label'));
        $this->assertSame('6 mois', $fr->categories()[0]['services'][0]->cookies[0]->duration);
        $this->assertSame('Accept all', Settings::forSite('en')->text('accept_all_label'));
    }
}
