<?php

namespace Foorintodev\CookieConsent\Tags;

use Foorintodev\CookieConsent\Banner;
use Foorintodev\CookieConsent\CookieList;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Settings;
use Foorintodev\CookieConsent\Support\Lang;
use Statamic\Facades\Site;
use Statamic\Fields\LabeledValue;
use Statamic\Tags\Tags;

/**
 * {{ cookie_consent }}       le bandeau, juste après <body> (ou après le lien d'évitement).
 *   button_class="…"         classes des boutons du site pour Tout accepter / Tout refuser /
 *                            Enregistrer (remplace l'habillage par défaut) ;
 *   secondary_class="…"      classes du bouton Personnaliser (par défaut : button_class) ;
 *   link_class="…"           classes du lien vers la politique ;
 *   styles="false"           ne charge pas la CSS de l'addon (le site fournit la sienne).
 *
 * {{ cookie_consent:open class="…" }}   bouton qui rouvre le bandeau (pied de page).
 *   En paire, son contenu sert de libellé. Caché tant que le JavaScript n'a pas démarré,
 *   absent quand le bandeau n'est pas proposé.
 *
 * {{ if {cookie_consent:active} }} … {{ /if }}   le bandeau est-il proposé ? (allumé dans
 *   le CP et au moins un service qui demande l'accord) : pour ne pas laisser un élément
 *   vide autour du bouton, par exemple un <li> du pied de page.
 *
 * {{ cookie_consent:table }} la liste des cookies (politique de confidentialité) : un titre
 *   et un tableau par catégorie.
 *   heading_level="3"        niveau des titres de catégorie (2 à 6) ;
 *   open_button="true"       ajoute le bouton qui rouvre le bandeau sous la liste ;
 *   id, class, table_class, link_class, open_class : préfixe des id et classes du site.
 *   Dans un bloc qui importe le fieldset « statamic-cookie-consent::cookie_list », les
 *   champs du bloc (cookie_list_heading_level, cookie_list_open_button) servent de
 *   paramètres par défaut.
 *
 * {{ cookie_consent:categories }} … {{ /cookie_consent:categories }}   les mêmes données,
 *   pour une mise en page propre au site : handle, name, description, required, services
 *   (name, provider, privacy_url, cookies : name, duration, purpose, storage, storage_label,
 *   host).
 */
class CookieConsent extends Tags
{
    protected static $handle = 'cookie_consent';

    public function index(): string
    {
        return Banner::render($this->settings(), [
            'button_class' => $this->params->get('button_class'),
            'secondary_class' => $this->params->get('secondary_class'),
            'link_class' => $this->params->get('link_class'),
            'styles' => $this->params->bool('styles', true),
        ]);
    }

    public function open(): string
    {
        return Banner::openButton(
            $this->settings(),
            (string) $this->params->get('class', ''),
            $this->isPair ? trim((string) $this->parse()) : null,
        );
    }

    public function active(): bool
    {
        return $this->settings()->active();
    }

    public function table(): string
    {
        return CookieList::render($this->settings(), [
            'heading_level' => $this->params->get('heading_level', $this->blockField('cookie_list_heading_level')),
            'open_button' => $this->params->bool('open_button', (bool) $this->blockField('cookie_list_open_button')),
            'id' => $this->params->get('id'),
            'class' => $this->params->get('class'),
            'table_class' => $this->params->get('table_class'),
            'link_class' => $this->params->get('link_class'),
            'open_class' => $this->params->get('open_class'),
        ]);
    }

    public function categories(): array
    {
        $lang = $this->settings()->lang();

        return array_map(fn (array $category) => [
            ...$category,
            'services' => array_map(function (Service $service) use ($lang) {
                $data = $service->toArray();
                $data['cookies'] = array_map(fn (array $cookie) => [
                    ...$cookie,
                    'storage_label' => Lang::get("storage.{$cookie['storage']}", $lang),
                ], $data['cookies']);

                return $data;
            }, $category['services']),
        ], $this->settings()->categories());
    }

    /**
     * Valeur d'un champ du bloc (fieldset cookie_list) dans le contexte de la balise. Sur
     * une vraie page, les valeurs sont augmentées : un button_group donne un LabeledValue.
     */
    private function blockField(string $handle): mixed
    {
        $value = $this->context->value($handle);

        return $value instanceof LabeledValue ? $value->value() : $value;
    }

    private function settings(): Settings
    {
        return Settings::forSite(Site::current()->handle());
    }
}
