<?php

namespace Foorintodev\CookieConsent\Tests;

use Statamic\Facades\Blueprint;
use Statamic\Facades\GlobalSet;

class SettingsBlueprintTest extends TestCase
{
    public function test_le_global_cookies_recoit_le_blueprint_de_l_addon(): void
    {
        $this->cookieGlobal();

        $blueprint = GlobalSet::findByHandle('cookie_settings')->blueprint();

        $this->assertNotNull($blueprint);

        $this->assertSame('toggle', $blueprint->field('banner_enabled')->type());
        $this->assertTrue($blueprint->field('banner_enabled')->defaultValue());

        foreach (['title', 'message', 'policy_link', 'policy_label', 'accept_all_label', 'decline_all_label',
            'customize_label', 'save_label', 'reopen_label', 'services',
            'necessary_name', 'preferences_name', 'analytics_name', 'marketing_description'] as $handle) {
            $this->assertTrue($blueprint->hasField($handle), "Champ manquant : {$handle}");
        }
    }

    public function test_les_trois_types_de_services_sont_proposes(): void
    {
        $sets = Blueprint::find('globals.cookie_settings')->field('services')->config()['sets']['services']['sets'];

        $this->assertSame(['meta_pixel', 'google_analytics', 'custom'], array_keys($sets));
    }

    public function test_le_texte_par_defaut_est_montre_en_exemple(): void
    {
        $field = Blueprint::find('globals.cookie_settings')->field('accept_all_label');

        $this->assertSame('Accept all', $field->get('placeholder'));
    }

    public function test_le_blueprint_suit_le_handle_configure(): void
    {
        // Le blueprint de secours est enregistré au démarrage pour le handle de la config.
        $this->assertNotNull(Blueprint::find('globals.cookie_settings'));
        $this->assertNull(Blueprint::find('globals.autre_global'));
    }
}
