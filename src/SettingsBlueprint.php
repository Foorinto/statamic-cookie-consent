<?php

namespace Foorintodev\CookieConsent;

use Foorintodev\CookieConsent\Detection\Detections;
use Foorintodev\CookieConsent\Detection\Report;
use Foorintodev\CookieConsent\Support\Lang;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintObject;
use Statamic\Statamic;

/**
 * Blueprint du global « Cookies », fourni par l'addon (blueprint « de secours » de Statamic).
 * Un site peut le remplacer : un fichier resources/blueprints/globals/<handle>.yaml
 * (créé par exemple depuis « Modifier le blueprint » dans le CP) est prioritaire.
 *
 * Les champs vides prennent le texte par défaut dans la langue du site (lang/<langue>/messages.php) ;
 * le texte par défaut est affiché en exemple dans chaque champ.
 */
class SettingsBlueprint
{
    public static function make(): BlueprintObject
    {
        return Blueprint::make()->setContents(static::contents());
    }

    public static function contents(): array
    {
        return [
            'tabs' => [
                'banner' => [
                    'display' => 'Bandeau',
                    'sections' => [
                        [
                            'display' => 'Affichage',
                            'fields' => [
                                [
                                    'handle' => 'banner_enabled',
                                    'field' => [
                                        'type' => 'toggle',
                                        'display' => 'Afficher le bandeau',
                                        'instructions' => 'Même allumé, le bandeau n\'apparaît que si au moins un service demande l\'accord (catégorie autre que « Nécessaires »). Coupé : ni bandeau ni bouton pour le rouvrir, et aucun service soumis à l\'accord n\'est chargé.',
                                        'default' => true,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'display' => 'Message',
                            'instructions' => 'Champ vide = texte par défaut, dans la langue du site.',
                            'fields' => [
                                static::text('title', 'Titre'),
                                static::textarea('message', 'Message'),
                                [
                                    'handle' => 'policy_link',
                                    'field' => [
                                        'type' => 'link',
                                        'display' => 'Page de la politique de confidentialité',
                                        'width' => 50,
                                    ],
                                ],
                                static::text('policy_label', 'Texte du lien vers la politique', 50),
                            ],
                        ],
                        [
                            'display' => 'Boutons',
                            'instructions' => '« Tout accepter » et « Tout refuser » ont toujours le même poids visuel.',
                            'fields' => [
                                static::text('accept_all_label', 'Tout accepter', 50),
                                static::text('decline_all_label', 'Tout refuser', 50),
                                static::text('customize_label', 'Personnaliser', 50),
                                static::text('save_label', 'Enregistrer les choix', 50),
                                static::text('reopen_label', 'Lien qui rouvre le bandeau (pied de page)', 50),
                            ],
                        ],
                        [
                            'display' => 'Panneau « Personnaliser »',
                            'fields' => [
                                static::text('always_active_label', 'Mention des cookies nécessaires', 50),
                                static::text('show_cookies_label', 'Lien qui déplie la liste des cookies', 50),
                            ],
                        ],
                    ],
                ],
                'categories' => [
                    'display' => 'Catégories',
                    'sections' => collect(Settings::CATEGORIES)->map(fn ($category) => [
                        'display' => Lang::get("categories.{$category}.name"),
                        'instructions' => $category === 'necessary'
                            ? 'Toujours active : le visiteur ne peut pas la refuser.'
                            : 'N\'apparaît dans le bandeau que si au moins un service lui est rattaché.',
                        'fields' => [
                            static::text("{$category}_name", 'Nom', 50, "categories.{$category}.name"),
                            static::textarea("{$category}_description", 'Description', "categories.{$category}.description"),
                        ],
                    ])->all(),
                ],
                'services' => [
                    'display' => 'Services',
                    'sections' => [
                        [
                            'instructions' => 'Un service n\'est chargé qu\'avec l\'accord du visiteur pour sa catégorie. Ajouter un service, le retirer ou changer sa catégorie redemande l\'accord à tous les visiteurs.',
                            'fields' => [
                                [
                                    'handle' => 'services',
                                    'field' => [
                                        'type' => 'replicator',
                                        'display' => 'Services',
                                        'button_label' => 'Ajouter un service',
                                        'collapse' => false,
                                        'sets' => [
                                            'services' => [
                                                'display' => 'Services',
                                                'sets' => static::serviceSets(),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'detection' => static::detectionTab(),
            ],
        ];
    }

    /**
     * Onglet « Détection ». Le tableau n'est calculé que dans le CP (le blueprint sert aussi
     * à chaque page du site, où il est inutile). Un site qui remplace ce blueprint par un
     * fichier YAML perd ce tableau à jour.
     */
    protected static function detectionTab(): array
    {
        $inCp = Statamic::isCpRoute();
        $count = $inCp && config('foorintodev-cookie-consent.detection') ? count(Detections::undeclared()) : 0;

        return [
            'display' => $count ? "Détection ({$count})" : 'Détection',
            'sections' => [
                [
                    'display' => 'Noms non déclarés',
                    'instructions' => 'Cookies et données stockées trouvés dans le navigateur de visiteurs sans correspondre à aucun service. Seuls les noms sont envoyés, jamais leur contenu. Pour chacun : s\'il sert, déclarez-le dans un service (onglet Services) ; sinon, retirez ce qui le dépose, ou ajoutez-le aux noms à ignorer. Il disparaît alors de la liste. Un nom non revu depuis 90 jours est oublié.',
                    'fields' => [
                        [
                            'handle' => 'detection_report',
                            'field' => [
                                'type' => 'html',
                                'html' => $inCp ? Report::html() : '',
                            ],
                        ],
                        [
                            'handle' => 'detection_ignore',
                            'field' => [
                                'type' => 'list',
                                'display' => 'Noms à ignorer',
                                'instructions' => 'Un nom par ligne ; * remplace n\'importe quelle suite de caractères (exemple : _hj*). Pour ce qui ne concerne pas les visiteurs (outils des rédacteurs, tests).',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function serviceSets(): array
    {
        return [
            'meta_pixel' => [
                'display' => 'Meta Pixel',
                'instructions' => 'Catégorie Marketing. Cookies déjà décrits : _fbp, _fbc et fr.',
                'fields' => [
                    [
                        'handle' => 'pixel_id',
                        'field' => [
                            'type' => 'text',
                            'display' => 'Identifiant du pixel',
                            'instructions' => 'Le nombre affiché sous le nom du pixel dans le Gestionnaire d\'événements de Meta.',
                            'validate' => ['required', 'regex:/^\d{10,20}$/'],
                        ],
                    ],
                ],
            ],
            'google_analytics' => [
                'display' => 'Google Analytics 4',
                'instructions' => 'Catégorie Mesure d\'audience. Cookies déjà décrits : _ga et _ga_<identifiant>.',
                'fields' => [
                    [
                        'handle' => 'measurement_id',
                        'field' => [
                            'type' => 'text',
                            'display' => 'ID de mesure',
                            'instructions' => 'Commence par G-, par exemple G-ABC123XYZ (Administration › Flux de données).',
                            'validate' => ['required', 'regex:/^G-[A-Z0-9]{4,20}$/'],
                        ],
                    ],
                ],
            ],
            'custom' => [
                'display' => 'Service personnalisé',
                'instructions' => 'N\'importe quel autre outil : déclarez ses cookies et, s\'il en a un, son code.',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Nom', 'width' => 50, 'validate' => ['required']]],
                    ['handle' => 'provider', 'field' => ['type' => 'text', 'display' => 'Fournisseur', 'width' => 50]],
                    [
                        'handle' => 'category',
                        'field' => [
                            'type' => 'select',
                            'display' => 'Catégorie',
                            'width' => 50,
                            'options' => collect(Settings::CATEGORIES)
                                ->mapWithKeys(fn ($category) => [$category => Lang::get("categories.{$category}.name")])
                                ->all(),
                            'default' => 'analytics',
                            'validate' => ['required'],
                        ],
                    ],
                    ['handle' => 'privacy_url', 'field' => ['type' => 'text', 'input_type' => 'url', 'display' => 'Politique de confidentialité du fournisseur', 'width' => 50]],
                    [
                        'handle' => 'cookies',
                        'field' => [
                            'type' => 'grid',
                            'display' => 'Cookies et données stockées',
                            'mode' => 'stacked',
                            'add_row' => 'Ajouter un cookie',
                            'fields' => [
                                ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Nom', 'instructions' => '* remplace une suite de caractères variable (exemple : _hjSession_*).', 'width' => 50, 'validate' => ['required']]],
                                [
                                    'handle' => 'storage',
                                    'field' => [
                                        'type' => 'select',
                                        'display' => 'Type',
                                        'width' => 50,
                                        'options' => [
                                            'cookie' => 'Cookie',
                                            'local' => 'Stockage local',
                                            'session' => 'Stockage de session',
                                        ],
                                        'default' => 'cookie',
                                    ],
                                ],
                                ['handle' => 'duration', 'field' => ['type' => 'text', 'display' => 'Durée', 'instructions' => 'Dans la langue du site, par exemple « 1 an ».', 'width' => 50]],
                                ['handle' => 'host', 'field' => ['type' => 'text', 'display' => 'Domaine, s\'il est déposé par un autre site', 'width' => 50]],
                                ['handle' => 'purpose', 'field' => ['type' => 'textarea', 'display' => 'Rôle']],
                            ],
                        ],
                    ],
                    [
                        'handle' => 'script',
                        'field' => [
                            'type' => 'code',
                            'display' => 'Code chargé après l\'accord',
                            'instructions' => 'Balises <script> comprises. Vide pour un service seulement déclaré.',
                            'mode' => 'htmlmixed',
                            'mode_selectable' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function text(string $handle, string $display, int $width = 100, ?string $default = null): array
    {
        return ['handle' => $handle, 'field' => [
            'type' => 'text',
            'display' => $display,
            'width' => $width,
            'placeholder' => Lang::get($default ?? "banner.{$handle}"),
        ]];
    }

    protected static function textarea(string $handle, string $display, ?string $default = null): array
    {
        return ['handle' => $handle, 'field' => [
            'type' => 'textarea',
            'display' => $display,
            'placeholder' => Lang::get($default ?? "banner.{$handle}"),
        ]];
    }
}
