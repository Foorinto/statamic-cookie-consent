<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\ServiceProvider;
use Statamic\Facades\Antlers;
use Statamic\Facades\Cascade;
use Statamic\Facades\Site;

class BannerTest extends TestCase
{
    private function render(string $template = '{{ cookie_consent }}'): string
    {
        return (string) Antlers::parse($template, [], true);
    }

    /** La config JSON que lit le navigateur. */
    private function config(string $html): array
    {
        preg_match('#<script type="application/json" id="cookie-consent-config">(.*?)</script>#s', $html, $m);

        return json_decode($m[1] ?? 'null', true);
    }

    public function test_le_bandeau_est_cache_au_depart_et_identique_pour_tous(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('#<section id="cookie-consent" class="cc" data-cookie-consent [^>]*hidden>#', $html);
        $this->assertStringContainsString('We use cookies', $html);
        $this->assertStringContainsString('data-cc-accept>Accept all</button>', $html);
        $this->assertStringContainsString('data-cc-decline>Decline all</button>', $html);
        $this->assertStringContainsString('aria-controls="cookie-consent-prefs">Customize</button>', $html);
        $this->assertStringContainsString('data-cc-save>Save my choices</button>', $html);
        $this->assertSame($html, $this->render(), 'Le HTML doit être le même à chaque rendu (cache statique).');
    }

    public function test_le_bandeau_s_affiche_avec_les_variables_d_une_vraie_page(): void
    {
        // Sur une page, la cascade injecte chaque global sous son handle. Un global
        // qui porterait le nom de la balise (« cookie_consent ») la masquerait.
        $this->cookieGlobal(['title' => 'Des cookies ?']);

        $cascade = Cascade::withSite(Site::default())->hydrate()->toArray();

        $this->assertArrayHasKey(config('foorintodev-cookie-consent.global'), $cascade);

        $html = (string) Antlers::parse('{{ cookie_consent }}', $cascade, true);

        $this->assertStringContainsString('<section id="cookie-consent"', $html);
        $this->assertStringContainsString('Des cookies ?', $html);
    }

    public function test_tout_accepter_et_tout_refuser_ont_le_meme_habillage(): void
    {
        $html = $this->render();

        preg_match('#<button type="button" class="([^"]+)" data-cc-accept>#', $html, $accept);
        preg_match('#<button type="button" class="([^"]+)" data-cc-decline>#', $html, $decline);

        $this->assertSame($accept[1], $decline[1]);
        $this->assertStringContainsString('cc-btn--default', $accept[1]);
    }

    public function test_les_classes_de_boutons_du_site(): void
    {
        $html = $this->render('{{ cookie_consent button_class="btn btn-primary" link_class="underline" }}');

        $this->assertStringContainsString('class="cc-btn cc-btn--choice btn btn-primary" data-cc-accept', $html);
        $this->assertStringContainsString('class="cc-btn cc-btn--customize btn btn-primary" data-cc-customize', $html);
        $this->assertStringNotContainsString('cc-btn--default', $html);

        $html = $this->render('{{ cookie_consent button_class="btn" secondary_class="btn btn-outline" }}');
        $this->assertStringContainsString('class="cc-btn cc-btn--customize btn btn-outline" data-cc-customize', $html);
    }

    public function test_la_css_et_le_js_de_l_addon(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="/vendor/statamic-cookie-consent/cookie-consent\.css\?v=[0-9a-f]{8}">#', $html);
        $this->assertMatchesRegularExpression('#<script src="/vendor/statamic-cookie-consent/cookie-consent\.js\?v=[0-9a-f]{8}" defer></script>#', $html);
        $this->assertStringNotContainsString('cookie-consent.css', $this->render('{{ cookie_consent styles="false" }}'));
    }

    public function test_seules_les_categories_avec_un_service_sont_proposees(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('Always active', $html);
        $this->assertStringNotContainsString('data-cc-category=', $html);
        $this->assertSame(['necessary'], $this->config($html)['categories']);

        $this->cookieGlobal(['services' => [
            ['id' => 'm1', 'type' => 'meta_pixel', 'pixel_id' => '1858784328492314'],
        ]]);

        $html = $this->render();

        $this->assertStringContainsString('data-cc-category="marketing"', $html);
        $this->assertStringContainsString('role="switch"', $html);
        $this->assertStringNotContainsString('data-cc-category="analytics"', $html);
        $this->assertStringContainsString('<code>_fbp</code>', $html);
        $this->assertStringContainsString('See the cookies (3)', $html);
    }

    public function test_la_config_du_navigateur(): void
    {
        $this->cookieGlobal(['services' => [
            ['id' => 'm1', 'type' => 'meta_pixel', 'pixel_id' => '1858784328492314'],
            ['id' => 'g1', 'type' => 'google_analytics', 'measurement_id' => 'G-ABC123'],
        ]]);

        $config = $this->config($this->render());

        $this->assertSame(['necessary', 'analytics', 'marketing'], $config['categories']);
        $this->assertSame('cookie-consent', $config['storageKey']);
        $this->assertSame(182, $config['lifetimeDays']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{10}$/', $config['version']);

        $services = collect($config['services'])->keyBy('id');

        $this->assertNull($services['site']['loader']);
        $this->assertSame(['type' => 'meta_pixel', 'pixelId' => '1858784328492314'], $services['m1']['loader']);
        $this->assertSame(['type' => 'google_analytics', 'measurementId' => 'G-ABC123'], $services['g1']['loader']);
        // fr est déposé par facebook.com : le navigateur ne peut pas l'effacer, il n'est pas transmis.
        $this->assertSame(['_fbp', '_fbc'], array_column($services['m1']['cookies'], 'name'));
    }

    public function test_le_code_d_un_service_ne_peut_pas_sortir_de_la_config(): void
    {
        $code = '<script>window.x = 1</script></script><script>alert(1)</script>';

        $this->cookieGlobal(['services' => [
            ['id' => 'c1', 'type' => 'custom', 'name' => 'Outil', 'category' => 'analytics', 'script' => $code],
        ]]);

        $html = $this->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertSame($code, collect($this->config($html)['services'])->firstWhere('id', 'c1')['loader']['html']);
    }

    public function test_le_lien_vers_la_politique(): void
    {
        $this->assertStringNotContainsString('cc-link', $this->render());

        $this->cookieGlobal(['policy_link' => '/privacy-policy', 'policy_label' => 'Our privacy policy']);

        $this->assertStringContainsString('<a href="/privacy-policy" class="cc-link ">Our privacy policy</a>', $this->render());
    }

    public function test_les_textes_saisis_sont_echappes(): void
    {
        $this->cookieGlobal(['title' => '<img src=x onerror=alert(1)>']);

        $html = $this->render();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function test_le_bouton_qui_rouvre_le_bandeau(): void
    {
        $this->assertSame(
            '<button type="button" class="footer-link" data-cookie-consent-open hidden>Cookie settings</button>',
            $this->render('{{ cookie_consent:open class="footer-link" }}')
        );

        $this->assertSame(
            '<button type="button" data-cookie-consent-open hidden>Mes cookies</button>',
            $this->render('{{ cookie_consent:open }}Mes cookies{{ /cookie_consent:open }}')
        );
    }

    public function test_le_bandeau_dans_la_langue_du_site(): void
    {
        $this->twoSites();
        Site::setCurrent('fr');

        $html = $this->render();

        $this->assertStringContainsString('Nous utilisons des cookies', $html);
        $this->assertStringContainsString('data-cc-accept>Tout accepter</button>', $html);
        $this->assertStringContainsString('Toujours actifs', $html);
        $this->assertStringContainsString('>Gérer les cookies</button>', $this->render('{{ cookie_consent:open }}'));
    }

    public function test_les_fichiers_du_navigateur_sont_publies(): void
    {
        $paths = ServiceProvider::pathsToPublish(ServiceProvider::class, 'statamic-cookie-consent');

        $this->assertSame([public_path('vendor/statamic-cookie-consent/')], array_values($paths));

        foreach (['cookie-consent.js', 'cookie-consent.css'] as $file) {
            $this->assertFileExists(array_key_first($paths).'/'.$file);
        }
    }
}
