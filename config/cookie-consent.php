<?php

// Config de l'addon, fusionnée sous la clé « foorintodev-cookie-consent »
// (publiable vers config/foorintodev-cookie-consent.php).
// Les textes, les services et leurs identifiants ne sont PAS ici : ils se
// règlent dans le CP (global « Cookies »), pour que le client puisse les gérer.

return [

    // Handle du global qui porte les réglages (textes, services). Jamais « cookie_consent » :
    // sur une page, la variable du global masquerait la balise {{ cookie_consent }}.
    'global' => env('COOKIE_CONSENT_GLOBAL', 'cookie_settings'),

    // Durée de validité d'un choix, en jours. Passé ce délai, le bandeau revient.
    // 6 mois : durée recommandée par la CNIL pour garder un choix (accord ou refus).
    'lifetime_days' => (int) env('COOKIE_CONSENT_LIFETIME_DAYS', 182),

    // Clé de stockage du choix dans le navigateur (localStorage, pas un cookie).
    'storage_key' => env('COOKIE_CONSENT_STORAGE_KEY', 'cookie-consent'),

    // Vide le cache statique quand le global est enregistré : le bandeau et la liste des
    // cookies sont écrits dans chaque page. À couper si le site le fait déjà lui-même.
    'flush_static_cache' => (bool) env('COOKIE_CONSENT_FLUSH_STATIC_CACHE', true),

    // Détection : le navigateur des visiteurs signale les noms de cookies et de données
    // stockées qui ne sont déclarés dans aucun service (jamais leur contenu). Résultat dans
    // le CP, onglet « Détection » du global. Rangé dans storage/app/statamic-cookie-consent/.
    'detection' => (bool) env('COOKIE_CONSENT_DETECTION', true),

    // Noms jamais signalés (* = n'importe quelle suite de caractères), en plus de ceux saisis
    // dans le CP. Par défaut : ce que le CP de Statamic laisse chez les rédacteurs, et que
    // leur navigateur signale ensuite depuis les pages du site (même origine).
    'ignore' => [
        'statamic.*',       // réglages du CP (localStorage)
        'remember_web_*',   // « Se souvenir de moi » à la connexion au CP
        '*.pagetree',       // arbres de pages ouverts ou repliés (localStorage)
        'avatar-hue-*',     // couleurs des avatars des utilisateurs (sessionStorage)
        'avatar-scheme-*',
        'avatar-position-*',
        'avatar-saturation-*',
        'avatar-lightness-*',
    ],

];
