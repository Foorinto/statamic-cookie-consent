<?php

return [

    'banner' => [
        'title' => 'Nous utilisons des cookies',
        'message' => 'Nous utilisons des cookies et des technologies similaires pour faire fonctionner ce site et, avec votre accord, pour comprendre comment il est utilisé et mesurer nos publicités. Vous pouvez tout accepter, tout refuser ou choisir par catégorie, et changer d\'avis à tout moment.',
        'policy_label' => 'Politique de confidentialité',
        'accept_all_label' => 'Tout accepter',
        'decline_all_label' => 'Tout refuser',
        'customize_label' => 'Personnaliser',
        'save_label' => 'Enregistrer mes choix',
        'reopen_label' => 'Gérer les cookies',
        'always_active_label' => 'Toujours actifs',
        'show_cookies_label' => 'Voir les cookies',
    ],

    'categories' => [
        'necessary' => [
            'name' => 'Nécessaires',
            'description' => 'Indispensables au fonctionnement du site et à la mémorisation de vos choix. Ils ne peuvent pas être désactivés.',
        ],
        'preferences' => [
            'name' => 'Préférences',
            'description' => 'Mémorisent vos réglages, comme la langue ou l\'affichage.',
        ],
        'analytics' => [
            'name' => 'Mesure d\'audience',
            'description' => 'Nous aident à comprendre comment le site est utilisé, pour l\'améliorer. Les données sont agrégées.',
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Mesurent les résultats de nos publicités et vous montrent des annonces adaptées sur d\'autres plateformes.',
        ],
    ],

    'list' => [
        'name' => 'Nom',
        'service' => 'Service',
        'purpose' => 'Finalité',
        'duration' => 'Durée',
        'set_by' => 'déposé par :host',
        'policy' => 'Politique de confidentialité de :provider',
        'no_cookies' => 'Aucun cookie déclaré pour : :services.',
    ],

    'storage' => [
        'cookie' => 'Cookie',
        'local' => 'Stockage local',
        'session' => 'Stockage de session',
    ],

    'durations' => [
        'session' => 'Jusqu\'à la fermeture du navigateur',
        'minute' => ':count minute|:count minutes',
        'hour' => ':count heure|:count heures',
        'day' => ':count jour|:count jours',
        'month' => ':count mois|:count mois',
        'year' => ':count an|:count ans',
    ],

    'services' => [
        'site' => [
            'name' => 'Ce site',
            'session' => 'Garde votre session pendant l\'envoi d\'un formulaire et le protège contre les abus.',
            'xsrf' => 'Protège les formulaires contre les requêtes envoyées depuis d\'autres sites.',
            'consent' => 'Mémorise vos choix sur les cookies.',
        ],
        'meta_pixel' => [
            'name' => 'Pixel Meta',
            '_fbp' => 'Identifie votre navigateur pour que Meta puisse mesurer et diffuser nos publicités.',
            '_fbc' => 'Mémorise la publicité Facebook ou Instagram sur laquelle vous avez cliqué pour venir sur ce site.',
            'fr' => 'Utilisé par Meta pour diffuser et mesurer les publicités, et améliorer leur pertinence.',
        ],
        'google_analytics' => [
            'name' => 'Google Analytics',
            '_ga' => 'Distingue les visiteurs, pour compter les visites et mesurer l\'utilisation du site.',
            '_ga_id' => 'Garde l\'état de votre visite en cours.',
        ],
    ],

];
