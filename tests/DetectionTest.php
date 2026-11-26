<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\Banner;
use Foorintodev\CookieConsent\Detection\Detections;
use Foorintodev\CookieConsent\Detection\Report;
use Foorintodev\CookieConsent\Settings;
use Illuminate\Support\Carbon;
use Statamic\Facades\Blueprint;
use Statamic\Facades\User;

class DetectionTest extends TestCase
{
    private const URL = '/!/statamic-cookie-consent/report';

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(Detections::path());
    }

    protected function tearDown(): void
    {
        @unlink(Detections::path());
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function report(array $items, ?string $page = '/'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(self::URL, ['items' => $items, 'page' => $page]);
    }

    private function recorded(): array
    {
        return collect(Detections::undeclared())->mapWithKeys(fn ($e) => [$e['storage'].':'.$e['name'] => $e])->all();
    }

    public function test_un_nom_non_declare_est_range_sans_session_ni_cookie(): void
    {
        $response = $this->report([['name' => '_hjid', 'storage' => 'cookie']], '/cours?email=a@b.lu');

        $response->assertNoContent();
        $this->assertSame([], $response->headers->getCookies(), 'Aucun cookie en réponse.');

        $entry = $this->recorded()['cookie:_hjid'];
        $this->assertSame(1, $entry['count']);
        $this->assertSame(['/cours'], $entry['pages'], 'Le chemin seul, sans les paramètres.');
        $this->assertSame(Carbon::now()->toDateString(), $entry['last_seen']);
    }

    public function test_les_noms_declares_et_ignores_ne_sont_pas_ranges(): void
    {
        $this->cookieGlobal([
            'detection_ignore' => ['_test*'],
            'services' => [
                ['id' => 'm1', 'type' => 'meta_pixel', 'pixel_id' => '1858784328492314'],
                ['id' => 'c1', 'type' => 'custom', 'name' => 'Hotjar', 'category' => 'analytics', 'cookies' => [
                    ['name' => '_hjSession_*', 'storage' => 'cookie', 'duration' => '30 minutes', 'purpose' => 'Session'],
                ]],
            ],
        ]);

        $this->report([
            ['name' => 'XSRF-TOKEN', 'storage' => 'cookie'],         // le site lui-même
            ['name' => 'cookie-consent', 'storage' => 'local'],      // le choix
            ['name' => '_fbp', 'storage' => 'cookie'],               // Meta
            ['name' => '_hjSession_123', 'storage' => 'cookie'],     // joker d'un service
            ['name' => '_test_ab', 'storage' => 'local'],            // ignoré dans le CP
            ['name' => 'statamic.color_mode', 'storage' => 'local'], // ignoré par la config
            ['name' => '_clck', 'storage' => 'cookie'],
        ])->assertNoContent();

        $this->assertSame(['cookie:_clck'], array_keys($this->recorded()));
    }

    public function test_les_noms_invalides_sont_ecartes_et_les_identifiants_masques(): void
    {
        $this->report([
            ['name' => 'remember_web_59ba36addc2b2f9401580f014c7f58ea', 'storage' => 'cookie'],
            ['name' => 'avec espace', 'storage' => 'cookie'],
            ['name' => str_repeat('a', 101), 'storage' => 'cookie'],
            ['name' => 'ok', 'storage' => 'indexeddb'],
            ['name' => ['x'], 'storage' => 'cookie'],
            'texte',
            ['name' => 'user_8f14e45fceea167a5a36dedd4bea2543_prefs', 'storage' => 'local'],
            ['name' => 'deadbeefcafebabe', 'storage' => 'local'], // que des lettres : gardé tel quel
        ], 'https://autre.site/page')->assertNoContent();

        $recorded = $this->recorded();

        // remember_web_* est ignoré par la config une fois l'identifiant masqué.
        $this->assertEqualsCanonicalizing(['local:user_*_prefs', 'local:deadbeefcafebabe'], array_keys($recorded));
        $this->assertSame([], $recorded['local:user_*_prefs']['pages']);
    }

    public function test_vingt_noms_au_plus_par_envoi(): void
    {
        $items = array_map(fn ($i) => ['name' => "n{$i}", 'storage' => 'cookie'], range(1, 30));

        $this->report($items)->assertNoContent();

        $this->assertCount(20, $this->recorded());
    }

    public function test_compte_pages_plafond_et_oubli(): void
    {
        foreach (['/a', '/b', '/c', '/d', '/e', '/f', '/a'] as $page) {
            $this->report([['name' => '_x', 'storage' => 'cookie']], $page);
        }

        $entry = $this->recorded()['cookie:_x'];
        $this->assertSame(7, $entry['count']);
        $this->assertSame(['/a', '/b', '/c', '/d', '/e'], $entry['pages']);

        // Plafond du nombre de noms.
        $many = array_map(fn ($i) => ['name' => "p{$i}", 'storage' => 'cookie'], range(1, 20));
        for ($i = 0; $i < 11; $i++) {
            Detections::record(array_map(fn ($item) => ['name' => $item['name'].'_'.$i, 'storage' => 'cookie'], $many), '/');
        }
        $this->assertCount(Detections::MAX_NAMES, $this->recorded());

        // Oublié après 90 jours sans signalement.
        Carbon::setTestNow(Carbon::now()->addDays(Detections::EXPIRE_DAYS + 1));
        $this->assertSame([], Detections::undeclared());
    }

    public function test_un_nom_declare_apres_coup_disparait_de_la_liste(): void
    {
        $this->report([['name' => '_clck', 'storage' => 'cookie'], ['name' => '_clsk', 'storage' => 'cookie']]);
        $this->assertCount(2, $this->recorded());

        $this->cookieGlobal(['services' => [
            ['id' => 'c1', 'type' => 'custom', 'name' => 'Clarity', 'category' => 'analytics', 'cookies' => [
                ['name' => '_cl*', 'storage' => 'cookie', 'duration' => '1 year', 'purpose' => 'Mesure'],
            ]],
        ]]);

        $this->assertSame([], Detections::undeclared());
    }

    public function test_detection_coupee(): void
    {
        config(['foorintodev-cookie-consent.detection' => false]);

        $this->report([['name' => '_x', 'storage' => 'cookie']])->assertNoContent();

        $this->assertFileDoesNotExist(Detections::path());
        $this->assertNull(json_decode(Banner::config(Settings::forSite()), true)['report']);
        $this->assertStringContainsString('Détection coupée', Report::html());
    }

    public function test_trop_d_envois_depuis_la_meme_adresse(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->report([['name' => '_x', 'storage' => 'cookie']])->assertNoContent();
        }

        $this->report([['name' => '_x', 'storage' => 'cookie']])->assertStatus(429);
    }

    public function test_la_config_du_navigateur(): void
    {
        $this->cookieGlobal(['detection_ignore' => ['_test*', ' ']]);

        $config = json_decode(Banner::config(Settings::forSite()), true);

        $this->assertSame(self::URL, $config['report']);
        $this->assertSame(['statamic.*', 'remember_web_*', '_test*'], $config['ignore']);
    }

    public function test_le_tableau_du_cp_echappe_ce_qui_vient_des_visiteurs(): void
    {
        $this->assertStringContainsString('Aucun nom non déclaré', Report::html());

        Detections::record([['name' => '<img/src=x>', 'storage' => 'cookie']], '/<script>');

        $html = Report::html();

        $this->assertStringContainsString('<caption style="text-align:left;padding-bottom:.5rem">1 nom non déclaré</caption>', $html);
        $this->assertStringContainsString('<code>&lt;img/src=x&gt;</code>', $html);
        $this->assertStringContainsString('/&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_le_cp_montre_les_noms_detectes(): void
    {
        $this->withoutVite();
        $this->cookieGlobal();
        Detections::record([['name' => '_clck', 'storage' => 'cookie']], '/cours');

        $user = User::make()->email('essai@example.com')->makeSuper();
        $user->save();

        $response = $this->actingAs($user)->get('/cp/globals/cookie_settings');

        $response->assertOk();

        // Données de la page Inertia, telles que le CP les reçoit.
        $html = $response->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $m) || preg_match('#<script data-page="app" type="application/json">(.*?)</script>#s', $html, $m);
        $page = json_encode(json_decode(html_entity_decode($m[1] ?? ''), true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('"display":"Détection (1)"', $page);
        $this->assertStringContainsString('<code>_clck</code>', $page);
        $this->assertStringContainsString('/cours', $page);
    }

    public function test_l_onglet_detection_du_global(): void
    {
        $blueprint = Blueprint::find('globals.cookie_settings');

        $this->assertTrue($blueprint->hasField('detection_report'));
        $this->assertSame('list', $blueprint->field('detection_ignore')->type());
        $this->assertSame(['banner', 'categories', 'services', 'detection'], $blueprint->tabs()->keys()->all());
    }
}
