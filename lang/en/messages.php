<?php

// Textes par défaut, utilisés quand le champ correspondant du global « Cookies » est vide.
// Une langue par dossier (en, fr, de) ; la langue est celle du site Statamic affiché.

return [

    'banner' => [
        'title' => 'We use cookies',
        'message' => 'We use cookies and similar technologies to run this website and, with your consent, to understand how it is used and to measure our advertising. You can accept all, decline all or choose by category, and change your mind at any time.',
        'policy_label' => 'Privacy policy',
        'accept_all_label' => 'Accept all',
        'decline_all_label' => 'Decline all',
        'customize_label' => 'Customize',
        'save_label' => 'Save my choices',
        'reopen_label' => 'Cookie settings',
        'always_active_label' => 'Always active',
        'show_cookies_label' => 'See the cookies',
    ],

    'categories' => [
        'necessary' => [
            'name' => 'Necessary',
            'description' => 'Needed for the website to work and to remember your cookie choices. They cannot be switched off.',
        ],
        'preferences' => [
            'name' => 'Preferences',
            'description' => 'Remember your settings, such as language or display options.',
        ],
        'analytics' => [
            'name' => 'Analytics',
            'description' => 'Help us understand how visitors use the website, so we can improve it. The data is aggregated.',
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Measure the results of our advertising and show you relevant ads on other platforms.',
        ],
    ],

    'list' => [
        'name' => 'Name',
        'service' => 'Service',
        'purpose' => 'Purpose',
        'duration' => 'Duration',
        'set_by' => 'set by :host',
        'policy' => 'Privacy policy of :provider',
        'no_cookies' => 'No cookie declared for: :services.',
    ],

    'storage' => [
        'cookie' => 'Cookie',
        'local' => 'Local storage',
        'session' => 'Session storage',
    ],

    'durations' => [
        'session' => 'Until the browser is closed',
        'minute' => ':count minute|:count minutes',
        'hour' => ':count hour|:count hours',
        'day' => ':count day|:count days',
        'month' => ':count month|:count months',
        'year' => ':count year|:count years',
    ],

    'services' => [
        'site' => [
            'name' => 'This website',
            'session' => 'Keeps your session while you send a form, and protects it against abuse.',
            'xsrf' => 'Protects forms against requests sent from other websites.',
            'consent' => 'Remembers your cookie choices.',
        ],
        'meta_pixel' => [
            'name' => 'Meta Pixel',
            '_fbp' => 'Identifies your browser so that Meta can measure and deliver our ads.',
            '_fbc' => 'Remembers the Facebook or Instagram ad you clicked to reach this website.',
            'fr' => 'Used by Meta to deliver, measure and improve the relevance of ads.',
        ],
        'google_analytics' => [
            'name' => 'Google Analytics',
            '_ga' => 'Distinguishes visitors, to count visits and measure how the website is used.',
            '_ga_id' => 'Keeps the state of your current visit.',
        ],
    ],

];
