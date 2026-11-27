<?php

namespace Foorintodev\CookieConsent;

use Foorintodev\CookieConsent\Services\Catalogue;
use Foorintodev\CookieConsent\Services\Service;
use Foorintodev\CookieConsent\Services\SiteService;
use Foorintodev\CookieConsent\Support\Lang;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Fieldtypes\Link\ArrayableLink;
use Statamic\Globals\Variables;

/**
 * Réglages du bandeau pour un site : ce qui est saisi dans le global « Cookies »,
 * complété par les textes par défaut dans la langue du site. Fonctionne aussi sans
 * global (tout par défaut, seul le site lui-même dans « Nécessaires »).
 */
class Settings
{
    public const CATEGORIES = ['necessary', 'preferences', 'analytics', 'marketing'];

    public const TEXTS = [
        'title', 'message', 'policy_label',
        'accept_all_label', 'decline_all_label', 'customize_label', 'save_label', 'reopen_label',
        'always_active_label', 'show_cookies_label',
    ];

    /** @var Service[]|null */
    private ?array $services = null;

    public function __construct(
        private readonly ?Variables $variables,
        private readonly string $lang,
    ) {}

    public static function forSite(?string $handle = null): self
    {
        $site = ($handle ? Site::get($handle) : null) ?? Site::current() ?? Site::default();

        $variables = GlobalSet::findByHandle(config('foorintodev-cookie-consent.global'))
            ?->in($site->handle());

        return new self($variables, $site->lang() ?: 'en');
    }

    public function lang(): string
    {
        return $this->lang;
    }

    public function exists(): bool
    {
        return $this->variables !== null;
    }

    /** Texte du bandeau : la saisie du CP, sinon le texte par défaut. */
    public function text(string $key): string
    {
        return $this->filled($key) ?? Lang::get("banner.{$key}", $this->lang);
    }

    /** Tous les textes du bandeau, [clé => texte]. */
    public function texts(): array
    {
        return collect(self::TEXTS)->mapWithKeys(fn ($key) => [$key => $this->text($key)])->all();
    }

    /** Interrupteur « Afficher le bandeau » du CP : allumé par défaut, et sans global. */
    public function enabled(): bool
    {
        $value = $this->variables?->values()->get('banner_enabled');

        return $value === null || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** Au moins un service demande l'accord (rattaché à une autre catégorie que « Nécessaires »). */
    public function needsConsent(): bool
    {
        return collect($this->services())->contains(fn (Service $s) => $s->category !== 'necessary');
    }

    /**
     * Bandeau proposé aux visiteurs : allumé dans le CP et quelque chose à accepter. Sinon,
     * ni bandeau ni bouton pour le rouvrir, et aucun service soumis à l'accord n'est chargé.
     */
    public function active(): bool
    {
        return $this->enabled() && $this->needsConsent();
    }

    public function policyUrl(): ?string
    {
        if (! $this->variables || ! $this->variables->values()->get('policy_link')) {
            return null;
        }

        $link = $this->variables->augmentedValue('policy_link')->value();
        $url = $link instanceof ArrayableLink ? $link->url() : $link;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * Noms à ne pas signaler, saisis dans l'onglet Détection (un par ligne, « * » = joker).
     *
     * @return string[]
     */
    public function ignored(): array
    {
        $value = $this->variables?->values()->get('detection_ignore');

        return array_values(array_filter(array_map(
            fn ($name) => is_scalar($name) ? trim((string) $name) : '',
            is_array($value) ? $value : [],
        )));
    }

    /**
     * Catégories à proposer, dans l'ordre fixe, seulement celles qui ont au moins un service.
     * « necessary » est toujours présente (le site lui-même) et ne peut pas être refusée.
     */
    public function categories(): array
    {
        return collect(self::CATEGORIES)
            ->map(fn ($handle) => [
                'handle' => $handle,
                'name' => $this->filled("{$handle}_name") ?? Lang::get("categories.{$handle}.name", $this->lang),
                'description' => $this->filled("{$handle}_description") ?? Lang::get("categories.{$handle}.description", $this->lang),
                'required' => $handle === 'necessary',
                'services' => array_values(array_filter($this->services(), fn (Service $s) => $s->category === $handle)),
            ])
            ->filter(fn ($category) => $category['services'] !== [])
            ->values()
            ->all();
    }

    /** @return Service[] le site lui-même, puis les services du CP actifs et valides. */
    public function services(): array
    {
        return $this->services ??= collect($this->values('services'))
            ->filter(fn ($set) => is_array($set) && ($set['enabled'] ?? true) !== false)
            ->map(fn ($set) => Catalogue::make($set, $this->lang))
            ->filter()
            ->prepend(SiteService::make($this->lang))
            ->values()
            ->all();
    }

    /**
     * Empreinte des services proposés : elle change quand un service est ajouté, retiré
     * ou change de catégorie, et le navigateur redemande alors l'accord.
     */
    public function version(): string
    {
        $signatures = array_map(fn (Service $s) => $s->signature(), $this->services());

        return substr(sha1(implode('|', $signatures)), 0, 10);
    }

    private function values(string $handle): array
    {
        $value = $this->variables?->values()->get($handle);

        return is_array($value) ? $value : [];
    }

    private function filled(string $handle): ?string
    {
        $value = $this->variables?->values()->get($handle);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
