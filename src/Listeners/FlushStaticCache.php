<?php

namespace Foorintodev\CookieConsent\Listeners;

use Statamic\Events\GlobalVariablesSaved;
use Statamic\Facades\StaticCache;

/**
 * Le bandeau (sa config) et la liste des cookies sont écrits dans chaque page. Avec le
 * cache statique, un service ajouté dans le CP n'apparaîtrait qu'à la prochaine remise
 * en cache : Statamic n'invalide rien pour un global sans règle d'invalidation.
 * On vide donc tout le cache statique quand le global « Cookies » est enregistré.
 *
 * À couper (flush_static_cache = false) sur un site qui le fait déjà lui-même.
 * Découvert par Statamic (src/Listeners) : ne pas l'ajouter aussi à $listen.
 */
class FlushStaticCache
{
    public function handle(GlobalVariablesSaved $event): void
    {
        if ($event->variables->globalSet()->handle() !== config('foorintodev-cookie-consent.global')) {
            return;
        }

        if (! config('foorintodev-cookie-consent.flush_static_cache') || ! config('statamic.static_caching.strategy')) {
            return;
        }

        StaticCache::flush();
    }
}
