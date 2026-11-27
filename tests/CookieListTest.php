<?php

namespace Foorintodev\CookieConsent\Tests;

use Statamic\Facades\Antlers;
use Statamic\Facades\Fieldset;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Facades\StaticCache;
use Statamic\Fields\LabeledValue;

class CookieListTest extends TestCase
{
    private function render(string $template = '{{ cookie_consent:table }}', array $data = []): string
    {
        return (string) Antlers::parse($template, $data, true);
    }

    private function metaAndGa(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'm1', 'type' => 'meta_pixel', 'pixel_id' => '1858784328492314'],
            ['id' => 'g1', 'type' => 'google_analytics', 'measurement_id' => 'G-ABC123'],
        ]]);
    }

    public function test_sans_service_seuls_les_cookies_du_site(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<h3 id="cookie-list-necessary" class="cc-list-title">Necessary</h3>', $html);
        $this->assertStringContainsString('<div class="cc-table-wrap" role="region" aria-labelledby="cookie-list-necessary" tabindex="0">', $html);
        $this->assertStringContainsString('<table class="cc-table" aria-labelledby="cookie-list-necessary">', $html);
        $this->assertStringContainsString('<th scope="col">Name</th>', $html);
        $this->assertStringContainsString('<th scope="row"><code>XSRF-TOKEN</code><span class="cc-table-note">Cookie</span></th>', $html);
        $this->assertStringContainsString('<code>cookie-consent</code><span class="cc-table-note">Local storage</span>', $html);
        $this->assertStringNotContainsString('cookie-list-marketing', $html);
        $this->assertStringNotContainsString('data-cookie-consent-open', $html);
    }

    public function test_une_categorie_par_service_avec_ses_cookies_et_sa_politique(): void
    {
        $this->metaAndGa();

        $html = $this->render();

        foreach (['necessary', 'analytics', 'marketing'] as $handle) {
            $this->assertStringContainsString("id=\"cookie-list-{$handle}\"", $html);
        }
        $this->assertStringNotContainsString('cookie-list-preferences', $html);
        $this->assertLessThan(strpos($html, 'cookie-list-marketing'), strpos($html, 'cookie-list-analytics'));

        $this->assertStringContainsString('<code>_ga_ABC123</code>', $html);
        $this->assertStringContainsString('<code>fr</code><span class="cc-table-note">Cookie · set by facebook.com</span>', $html);
        $this->assertStringContainsString('<td>Meta Pixel<span class="cc-table-note">Meta Platforms Ireland Ltd</span></td>', $html);
        $this->assertStringContainsString('<td>3 months</td>', $html);
        $this->assertStringContainsString('<li><a href="https://www.facebook.com/privacy/policy/">Privacy policy of Meta Platforms Ireland Ltd</a></li>', $html);
        $this->assertSame(1, substr_count($html, 'https://policies.google.com/privacy'));
    }

    public function test_niveau_des_titres_et_bouton(): void
    {
        $this->metaAndGa();
        $html = $this->render('{{ cookie_consent:table heading_level="2" open_button="true" open_class="btn" }}');

        $this->assertStringContainsString('<h2 id="cookie-list-necessary"', $html);
        $this->assertStringContainsString('<p class="cc-list-open"><button type="button" class="btn" data-cookie-consent-open hidden>Cookie settings</button></p>', $html);

        $this->assertStringContainsString('<h3 id="cookie-list-necessary"', $this->render('{{ cookie_consent:table heading_level="9" }}'));
    }

    public function test_les_champs_du_bloc_servent_de_parametres(): void
    {
        $this->metaAndGa();
        $data = ['cookie_list_heading_level' => '4', 'cookie_list_open_button' => true];

        $html = $this->render('{{ cookie_consent:table }}', $data);

        $this->assertStringContainsString('<h4 id="cookie-list-necessary"', $html);
        $this->assertStringContainsString('data-cookie-consent-open', $html);

        // Sur une vraie page, les champs arrivent augmentés (button_group → LabeledValue).
        $augmented = ['cookie_list_heading_level' => new LabeledValue('2', 'H2'), 'cookie_list_open_button' => false];
        $html = $this->render('{{ cookie_consent:table }}', $augmented);
        $this->assertStringContainsString('<h2 id="cookie-list-necessary"', $html);
        $this->assertStringNotContainsString('data-cookie-consent-open', $html);

        // Un paramètre de la balise passe avant le champ du bloc.
        $this->assertStringContainsString('<h2 id="cookie-list-necessary"', $this->render('{{ cookie_consent:table heading_level="2" }}', $data));
    }

    public function test_id_et_classes_du_site(): void
    {
        $html = $this->render('{{ cookie_consent:table id="cookies" class="prose" table_class="table" link_class="link" }}');

        $this->assertStringContainsString('<div class="cc-list prose">', $html);
        $this->assertStringContainsString('<h3 id="cookies-necessary"', $html);
        $this->assertStringContainsString('<table class="cc-table table" aria-labelledby="cookies-necessary">', $html);
    }

    public function test_service_personnalise_sans_cookie_et_textes_echappes(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'c1', 'type' => 'custom', 'name' => 'Widget <b>', 'category' => 'preferences', 'privacy_url' => 'https://example.com/privacy'],
            ['id' => 'c2', 'type' => 'custom', 'name' => 'Outil', 'provider' => 'Outil SA', 'category' => 'preferences', 'cookies' => [
                ['name' => '<script>x</script>', 'storage' => 'session', 'duration' => 'session', 'purpose' => 'Test'],
            ]],
        ]]);

        $html = $this->render();

        $this->assertStringContainsString('No cookie declared for: Widget &lt;b&gt;.', $html);
        $this->assertStringContainsString('<code>&lt;script&gt;x&lt;/script&gt;</code><span class="cc-table-note">Session storage</span>', $html);
        $this->assertStringContainsString('Privacy policy of Widget &lt;b&gt;', $html);
        $this->assertStringNotContainsString('<script>x', $html);
    }

    public function test_la_liste_dans_la_langue_du_site(): void
    {
        $this->twoSites();
        Site::setCurrent('fr');
        $this->metaAndGa();

        $html = $this->render();

        $this->assertStringContainsString('<th scope="col">Nom</th>', $html);
        $this->assertStringContainsString('<th scope="col">Finalité</th>', $html);
        $this->assertStringContainsString('Cookie · déposé par facebook.com', $html);
        $this->assertStringContainsString('Politique de confidentialité de Meta Platforms Ireland Ltd', $html);
        $this->assertStringContainsString('>Mesure d&#039;audience</h3>', $html);
    }

    public function test_la_boucle_pour_une_mise_en_page_maison(): void
    {
        $this->metaAndGa();

        $html = $this->render('{{ cookie_consent:categories }}[{{ handle }}{{ services }}/{{ provider }}{{ cookies }} {{ name }}({{ storage_label }}){{ /cookies }}{{ /services }}]{{ /cookie_consent:categories }}');

        $this->assertStringContainsString('[analytics/Google Ireland Ltd _ga(Cookie) _ga_ABC123(Cookie)]', $html);
        $this->assertStringContainsString('[marketing/Meta Platforms Ireland Ltd _fbp(Cookie) _fbc(Cookie) fr(Cookie)]', $html);
        $this->assertStringContainsString('cookie-consent(Local storage)', $html);
    }

    public function test_le_fieldset_du_bloc(): void
    {
        $fieldset = Fieldset::find('statamic-cookie-consent::cookie_list');

        $this->assertNotNull($fieldset);
        $this->assertSame(
            ['cookie_list_info', 'cookie_list_heading_level', 'cookie_list_open_button'],
            $fieldset->fields()->all()->keys()->all()
        );
    }

    public function test_enregistrer_le_global_vide_le_cache_statique(): void
    {
        $this->cookieGlobal();
        config(['statamic.static_caching.strategy' => 'half']);
        StaticCache::spy();

        GlobalSet::findByHandle('cookie_settings')->inDefaultSite()->data(['title' => 'Cookies ?'])->save();

        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_le_cache_statique_n_est_pas_vide_sans_raison(): void
    {
        StaticCache::spy();

        // Pas de cache statique.
        $this->cookieGlobal(['title' => 'A']);

        // Un autre global.
        config(['statamic.static_caching.strategy' => 'half']);
        $other = GlobalSet::make('autre')->title('Autre');
        $other->save();
        $other->in(Site::default()->handle())->data(['x' => 1])->save();

        // Option coupée.
        config(['foorintodev-cookie-consent.flush_static_cache' => false]);
        GlobalSet::findByHandle('cookie_settings')->inDefaultSite()->data(['title' => 'B'])->save();

        StaticCache::shouldNotHaveReceived('flush');
    }
}
