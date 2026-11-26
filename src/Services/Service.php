<?php

namespace Foorintodev\CookieConsent\Services;

/**
 * Un service prêt à l'emploi : ce que le bandeau et la liste des cookies affichent,
 * et ce que le navigateur doit charger après l'accord ($loader).
 */
final class Service
{
    /**
     * @param  Cookie[]  $cookies
     * @param  array  $loader  Pour le navigateur : ['type' => 'meta_pixel', 'pixelId' => …],
     *                         ['type' => 'html', 'html' => …], ou [] si rien à charger.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $name,
        public readonly string $category,
        public readonly array $cookies = [],
        public readonly ?string $provider = null,
        public readonly ?string $privacyUrl = null,
        public readonly array $loader = [],
    ) {}

    /** Ce qui, s'il change, doit redemander l'accord aux visiteurs. */
    public function signature(): string
    {
        return "{$this->type}:{$this->id}:{$this->category}";
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'category' => $this->category,
            'provider' => $this->provider,
            'privacy_url' => $this->privacyUrl,
            'cookies' => array_map(fn (Cookie $cookie) => $cookie->toArray(), $this->cookies),
        ];
    }
}
