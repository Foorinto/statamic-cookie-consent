<?php

namespace Foorintodev\CookieConsent\Services;

/**
 * Un type de service : transforme une ligne du champ « Services » du global en Service.
 * Renvoie null si la ligne est inutilisable (identifiant manquant ou invalide) :
 * le service est alors ignoré, jamais chargé.
 */
interface ServiceType
{
    public static function make(array $set, string $lang): ?Service;
}
