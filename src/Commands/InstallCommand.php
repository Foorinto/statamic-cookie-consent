<?php

namespace Foorintodev\CookieConsent\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;

/**
 * Crée le global « Cookies » s'il n'existe pas. Son blueprint est fourni par l'addon.
 * En multisite, le global existe dans tous les sites ; les autres sites héritent du
 * site par défaut tant qu'un texte n'y est pas traduit.
 */
class InstallCommand extends Command
{
    protected $signature = 'cookie-consent:install';

    protected $description = 'Crée le global « Cookies » de Foorintodev Cookie Consent s\'il n\'existe pas';

    public function handle(): int
    {
        $handle = config('foorintodev-cookie-consent.global');

        if (GlobalSet::findByHandle($handle)) {
            $this->components->info("Le global « {$handle} » existe déjà : rien à faire.");

            return self::SUCCESS;
        }

        $set = GlobalSet::make($handle)->title('Cookies');

        if (Site::multiEnabled()) {
            $default = Site::default()->handle();

            $set->sites(Site::all()->keys()->mapWithKeys(
                fn ($site) => [$site => $site === $default ? null : $default]
            )->all());
        }

        $set->save();

        $this->components->info("Global « {$handle} » créé : CP › Globals › Cookies.");

        return self::SUCCESS;
    }
}
