<?php

namespace Foorintodev\CookieConsent\Services;

/** Un cookie (ou une donnée du stockage local / de session) déposé par un service. */
final class Cookie
{
    public const STORAGES = ['cookie', 'local', 'session'];

    public function __construct(
        public readonly string $name,
        public readonly string $duration,
        public readonly string $purpose,
        public readonly string $storage = 'cookie',
        // Domaine qui le dépose, quand ce n'est pas le site lui-même (ex. facebook.com).
        public readonly ?string $host = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'duration' => $this->duration,
            'purpose' => $this->purpose,
            'storage' => $this->storage,
            'host' => $this->host,
        ];
    }
}
