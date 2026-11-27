<?php

return [

    'banner' => [
        'title' => 'Wir verwenden Cookies',
        'message' => 'Wir verwenden Cookies und ähnliche Technologien, um diese Website zu betreiben und, mit Ihrer Einwilligung, um zu verstehen, wie sie genutzt wird, und um unsere Werbung zu messen. Sie können alle akzeptieren, alle ablehnen oder nach Kategorie wählen und Ihre Wahl jederzeit ändern.',
        'policy_label' => 'Datenschutzerklärung',
        'accept_all_label' => 'Alle akzeptieren',
        'decline_all_label' => 'Alle ablehnen',
        'customize_label' => 'Anpassen',
        'save_label' => 'Auswahl speichern',
        'reopen_label' => 'Cookie-Einstellungen',
        'always_active_label' => 'Immer aktiv',
        'show_cookies_label' => 'Cookies anzeigen',
    ],

    'categories' => [
        'necessary' => [
            'name' => 'Notwendig',
            'description' => 'Für den Betrieb der Website und das Speichern Ihrer Cookie-Auswahl erforderlich. Sie können nicht deaktiviert werden.',
        ],
        'preferences' => [
            'name' => 'Präferenzen',
            'description' => 'Speichern Ihre Einstellungen, etwa Sprache oder Anzeige.',
        ],
        'analytics' => [
            'name' => 'Statistik',
            'description' => 'Helfen uns zu verstehen, wie die Website genutzt wird, um sie zu verbessern. Die Daten werden zusammengefasst.',
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Messen den Erfolg unserer Werbung und zeigen Ihnen passende Anzeigen auf anderen Plattformen.',
        ],
    ],

    'list' => [
        'name' => 'Name',
        'service' => 'Dienst',
        'purpose' => 'Zweck',
        'duration' => 'Dauer',
        'set_by' => 'gesetzt von :host',
        'policy' => 'Datenschutzerklärung von :provider',
        'no_cookies' => 'Kein Cookie angegeben für: :services.',
    ],

    'storage' => [
        'cookie' => 'Cookie',
        'local' => 'Lokaler Speicher',
        'session' => 'Sitzungsspeicher',
    ],

    'durations' => [
        'session' => 'Bis der Browser geschlossen wird',
        'minute' => ':count Minute|:count Minuten',
        'hour' => ':count Stunde|:count Stunden',
        'day' => ':count Tag|:count Tage',
        'month' => ':count Monat|:count Monate',
        'year' => ':count Jahr|:count Jahre',
    ],

    'services' => [
        'site' => [
            'name' => 'Diese Website',
            'session' => 'Hält Ihre Sitzung beim Absenden eines Formulars aufrecht und schützt es vor Missbrauch.',
            'xsrf' => 'Schützt Formulare vor Anfragen, die von anderen Websites gesendet werden.',
            'consent' => 'Speichert Ihre Cookie-Auswahl.',
        ],
        'meta_pixel' => [
            'name' => 'Meta-Pixel',
            '_fbp' => 'Erkennt Ihren Browser wieder, damit Meta unsere Werbung messen und ausspielen kann.',
            '_fbc' => 'Speichert die Facebook- oder Instagram-Anzeige, über die Sie auf diese Website gekommen sind.',
            'lastExternalReferrer' => 'Speichert, ob Sie über Facebook, Instagram, eine andere Website oder direkt gekommen sind; Meta nutzt dies zur Messung unserer Werbung.',
            'lastExternalReferrerTime' => 'Speichert, wann diese Herkunft erfasst wurde.',
            'fr' => 'Wird von Meta verwendet, um Anzeigen auszuspielen, zu messen und ihre Relevanz zu verbessern.',
        ],
        'google_analytics' => [
            'name' => 'Google Analytics',
            '_ga' => 'Unterscheidet Besucher, um Besuche zu zählen und die Nutzung der Website zu messen.',
            '_ga_id' => 'Speichert den Zustand Ihres aktuellen Besuchs.',
        ],
    ],

];
