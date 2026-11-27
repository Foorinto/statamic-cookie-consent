# Foorintodev Cookie Consent (addon Statamic)

Bandeau de consentement aux cookies pour Statamic 6, **gratuit (licence MIT)**.

- **Bandeau** avec « Tout accepter », « Tout refuser » (même poids visuel) et
  « Personnaliser », qui ouvre un choix par catégorie : Nécessaires (toujours actifs),
  Préférences, Mesure d'audience, Marketing. Une catégorie sans service n'apparaît pas.
- **Seulement quand il sert** : pas de bandeau tant qu'aucun service ne demande l'accord,
  et un interrupteur dans le CP pour le couper.
- **Aucun script tiers avant l'accord** : chaque service est chargé dans le navigateur,
  seulement si sa catégorie est acceptée. Au refus, ses cookies sont effacés.
- **Compatible avec le cache statique complet** : tout se décide dans le navigateur, la
  page en cache est la même pour tous les visiteurs.
- **Réglages dans le CP** (global « Cookies ») : textes du bandeau, libellés des boutons,
  noms et descriptions des catégories, services. Textes par défaut en anglais, français et
  allemand, selon la langue du site.
- **Services intégrés** : Meta Pixel et Google Analytics 4. Coller l'identifiant suffit :
  le service apparaît dans sa catégorie, ses cookies déjà décrits. Un **service
  personnalisé** accueille n'importe quel autre outil (son code n'est chargé qu'après
  l'accord).
- **Nouvelle demande d'accord** automatique quand un service est ajouté, retiré ou change
  de catégorie. Un choix est gardé 6 mois.
- **Liste des cookies** pour la politique de confidentialité, générée et toujours à jour
  (balise et bloc pour le page builder).
- **Détection** des cookies et données stockées non déclarés chez les visiteurs (noms
  seulement), signalés dans le CP.
- **Accessible** : clavier, lecteurs d'écran, mode contrasté ; réouvrable depuis le pied de
  page.

## Installer

L'addon n'est pas sur Packagist : il s'installe avec Composer depuis son dépôt GitHub
(public).

1. Déclarer le dépôt dans le `composer.json` du projet :
   ```bash
   composer config repositories.foorintodev-cookie-consent vcs https://github.com/Foorinto/statamic-cookie-consent.git
   ```
2. Installer. Le JavaScript et la CSS sont publiés dans
   `public/vendor/statamic-cookie-consent/` :
   ```bash
   composer require foorintodev/statamic-cookie-consent:^1.0
   ```
3. Créer le global « Cookies » (CP › Globals › Cookies) :
   ```bash
   php artisan cookie-consent:install
   ```
4. Dans le layout, juste après l'ouverture de `<body>` (ou après le lien d'évitement) :
   ```antlers
   {{ cookie_consent }}
   ```
   et dans le pied de page, le bouton qui rouvre le bandeau (caché tant que le JavaScript
   n'a pas démarré, absent quand le bandeau n'est pas proposé) :
   ```antlers
   {{ cookie_consent:open class="footer-link" }}
   ```
   Dans une liste, pour ne pas laisser d'élément vide :
   ```antlers
   {{ if {cookie_consent:active} }}<li>{{ cookie_consent:open }}</li>{{ /if }}
   ```
5. Dans le CP, remplir le global « Cookies » : lien vers la politique de confidentialité,
   services.

Après une mise à jour de l'addon, republier le JavaScript et la CSS :

```bash
php artisan vendor:publish --tag=statamic-cookie-consent --force
```

## Réglages dans le CP (global « Cookies »)

- **Bandeau** : interrupteur « Afficher le bandeau » (voir plus bas), titre, message, lien
  et texte du lien vers la politique de confidentialité, libellés des boutons et du bouton
  qui rouvre le bandeau. Champ vide = texte par défaut dans la langue du site (affiché en
  exemple dans le champ).
- **Catégories** : nom et description de chaque catégorie. Nécessaires contient toujours le
  site lui-même (le choix du visiteur, le cookie de session et `XSRF-TOKEN`, noms et durées
  lus dans la config de Laravel).
- **Services** :
  - *Meta Pixel* (identifiant du pixel) → Marketing ; cookies `_fbp`, `_fbc` (3 mois) et `fr`
    sur facebook.com (3 mois), stockage local `lastExternalReferrer` et
    `lastExternalReferrerTime` (provenance du visiteur, 3 mois) ;
  - *Google Analytics 4* (ID de mesure `G-…`) → Mesure d'audience ; cookies `_ga` et
    `_ga_<ID sans G->` (2 ans) ;
  - *Service personnalisé* : nom, fournisseur, catégorie, politique du fournisseur, cookies
    (nom, type, durée, domaine, rôle ; `*` dans un nom pour une partie variable, par exemple
    `_hjSession_*`) et code chargé après l'accord.

  Un service désactivé ou à l'identifiant invalide est ignoré. Ajouter, retirer un service
  ou changer sa catégorie redemande l'accord à tous les visiteurs ; modifier un texte ou un
  identifiant ne le fait pas.
- **Détection** : voir plus bas.

Le blueprint du global est fourni par l'addon. « Modifier le blueprint » dans le CP crée
`resources/blueprints/globals/cookie_settings.yaml`, qui devient prioritaire : il ne suit
plus les mises à jour de l'addon, et l'onglet Détection n'y est plus à jour.

## Quand le bandeau est proposé

Le bandeau n'est proposé aux visiteurs que si les deux conditions sont réunies :

- l'interrupteur **« Afficher le bandeau »** (onglet Bandeau du global) est allumé, ce qui
  est le cas par défaut ;
- au moins un service demande l'accord, c'est-à-dire un service rattaché à une autre
  catégorie que « Nécessaires ». Avec seulement les cookies du site, il n'y a rien à
  accepter : pas de bandeau.

Sinon : ni bandeau ni bouton pour le rouvrir (`{{ cookie_consent:open }}` ne rend rien,
`{{ cookie_consent:active }}` vaut `false`), et **aucun service soumis à l'accord n'est
chargé**, même chez un visiteur qui l'avait accepté ; leurs traces sont effacées. Les
services « Nécessaires », la détection et la liste des cookies continuent de fonctionner.
Le choix gardé par le navigateur reste : à la réactivation, il s'applique à nouveau tant
que les services n'ont pas changé.

## Habillage du bandeau

Options de `{{ cookie_consent }}` :

| Option | Rôle |
|---|---|
| `button_class` | classes du site pour Tout accepter, Tout refuser et Enregistrer (à la place du style de l'addon) |
| `secondary_class` | classes de Personnaliser (par défaut : `button_class`) |
| `link_class` | classes du lien vers la politique |
| `styles="false"` | sans la CSS de l'addon |

Couleurs, police, arrondis, largeur et position : variables `--cc-*` de
`cookie-consent.css`, à redéfinir dans la CSS du site, par exemple
`.cc { --cc-accent: #1E22AA; --cc-bg: #F2ECE3; }`.

Dans le navigateur : `CookieConsent.open()`, `CookieConsent.has('analytics')`,
`CookieConsent.onChange(fn)` et l'événement `cookie-consent:change` sur `document`.

## Liste des cookies (politique de confidentialité)

Générée à partir des mêmes réglages que le bandeau : un titre et un tableau par catégorie
(Nom, Service, Finalité, Durée), puis les liens vers les politiques des fournisseurs.

```antlers
{{ cookie_consent:table heading_level="3" open_button="true" }}
```

Options : `heading_level` (2 à 6, défaut 3 : un cran sous le titre qui précède la liste),
`open_button` (bouton « Gérer les cookies » sous la liste), `id` (préfixe des id, défaut
`cookie-list`), `class`, `table_class`, `link_class`, `open_class`. Sur un écran étroit,
chaque tableau défile dans son cadre, qui prend le focus au clavier.

**Bloc pour le page builder** : l'addon fournit le fieldset
`statamic-cookie-consent::cookie_list` (niveau des titres, bouton). Dans un set du
replicator ou de Bard du site :

```yaml
cookie_list:
  display: 'Liste des cookies'
  fields:
    - import: 'statamic-cookie-consent::cookie_list'
```

et son partial (par exemple `blocks/cookie_list.antlers.html`) appelle simplement
`{{ cookie_consent:table }}` : les champs du bloc servent de paramètres.

**Mise en page maison** : `{{ cookie_consent:categories }}` boucle sur les mêmes données
(`handle`, `name`, `description`, `required`, `services` → `name`, `provider`,
`privacy_url`, `cookies` → `name`, `duration`, `purpose`, `storage`, `storage_label`,
`host`).

Libellés des colonnes : `lang/<langue>/messages.php` (section `list`), remplaçables par le
site dans `lang/vendor/statamic-cookie-consent/<langue>/messages.php`.

## Détection des cookies non déclarés

Quelques secondes après l'affichage d'une page (et après un choix), le navigateur du
visiteur relève les noms de ses cookies, de son stockage local et de son stockage de
session. Ceux qui ne correspondent à aucun service déclaré ni à aucun nom ignoré sont
envoyés au site. Ils apparaissent dans le CP, onglet **Détection** du global « Cookies »
(« Détection (3) » quand il y en a), avec le type, le nombre de signalements, le premier
et le dernier jour, et quelques pages où ils ont été vus.

Pour chaque nom : le déclarer dans un service, retirer ce qui le dépose, ou l'ajouter aux
**noms à ignorer** du même onglet. Il disparaît alors de la liste. Un nom qui n'est plus
signalé depuis 90 jours est oublié.

Ce qui est envoyé, et seulement ça : le nom et le type de stockage (jamais le contenu), et
le chemin de la page (sans domaine ni paramètres). Une longue suite hexadécimale avec un
chiffre dans un nom (souvent un identifiant du visiteur) est remplacée par `*`. L'envoi se
fait sans cookie, vers `/!/statamic-cookie-consent/report` (sans session ni jeton CSRF,
30 envois par minute et par adresse IP au plus) ; un même navigateur ne renvoie un nom
qu'une fois par semaine. Rangement : `storage/app/statamic-cookie-consent/detections.json`
(200 noms au plus).

Ignorés d'office (config `ignore`) : ce que le CP laisse chez les rédacteurs, et que leur
navigateur signale ensuite depuis les pages du site (`statamic.*`, `remember_web_*`,
`*.pagetree`, `avatar-hue-*` et les autres couleurs d'avatar). Un site qui a publié la config
garde sa propre liste : la compléter après une mise à jour. Pour couper la détection :
`COOKIE_CONSENT_DETECTION=false`.

## Cache statique

Le bandeau et la liste sont écrits dans chaque page. Quand le global « Cookies » est
enregistré, l'addon vide tout le cache statique (sinon un service ajouté n'apparaîtrait
qu'à la remise en cache suivante). Sur un site qui gère déjà lui-même ce cas :
`COOKIE_CONSENT_FLUSH_STATIC_CACHE=false`.

## Configuration

`php artisan vendor:publish --tag=foorintodev-cookie-consent` publie
`config/foorintodev-cookie-consent.php` :

| Clé | `.env` | Défaut | Rôle |
|---|---|---|---|
| `global` | `COOKIE_CONSENT_GLOBAL` | `cookie_settings` | handle du global qui porte les réglages (jamais `cookie_consent`, le nom de la balise) |
| `lifetime_days` | `COOKIE_CONSENT_LIFETIME_DAYS` | `182` | durée de validité d'un choix (6 mois) |
| `storage_key` | `COOKIE_CONSENT_STORAGE_KEY` | `cookie-consent` | clé du choix dans le `localStorage` du navigateur |
| `flush_static_cache` | `COOKIE_CONSENT_FLUSH_STATIC_CACHE` | `true` | vide le cache statique quand le global est enregistré |
| `detection` | `COOKIE_CONSENT_DETECTION` | `true` | signalement des noms non déclarés par les navigateurs |
| `ignore` | — | `statamic.*`, `remember_web_*`, `*.pagetree`, `avatar-*-…` | noms jamais signalés (en plus de ceux du CP) |

## Code

`src/Settings.php` (réglages d'un site), `src/SettingsBlueprint.php` (blueprint du global),
`src/Services/` (catalogue, un fichier par type dans `Types/`), `src/Banner.php` et
`src/CookieList.php` (rendu), `src/Detection/`, `resources/dist/` (JavaScript et CSS du
navigateur, sans compilation).

Ajouter un service intégré : une classe dans `src/Services/Types/`, une entrée dans
`Catalogue`, ses textes dans `lang/`, ses champs dans `SettingsBlueprint::serviceSets()`
et son chargeur dans `loaders` de `resources/dist/cookie-consent.js`.

## Tests

```bash
composer install   # une fois
composer test
```

## Licence

MIT, voir [LICENSE.md](LICENSE.md).
