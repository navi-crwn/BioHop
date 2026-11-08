<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <b>Français</b> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Licence MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="workflow de tests"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="Le tableau de bord de BioHop : clics de cette semaine comparés à la précédente, une liste de mise en route et une carte pour chaque page bio" width="100%"></picture></p>

<p align="center">Une page pour vos liens, intégrations, réseaux sociaux et un code QR. Sur le service hébergé, elle est publiée à l'adresse <code>hel.ink/b/{slug}</code>.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Installation</a> ·
  <a href="#features">Fonctionnalités</a> ·
  <a href="#run-your-own">L'héberger vous-même</a> ·
  <a href="#privacy">Confidentialité</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#security">Sécurité</a> ·
  <a href="#license">Licence</a>
</p>

---

<a id="install"></a>

## Installation

Une seule commande. Elle vérifie d'abord votre ordinateur, pose quelques questions et peut aussi installer le hub HOP ID à votre place :

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

Depuis un clone : `./install.sh`. Mettez à jour avec `./update.sh` et supprimez avec `./uninstall.sh`. Ajoutez `--yes` pour accepter toutes les valeurs par défaut ; `--help` liste les options. L'installateur demande PHP 8.3 ou plus récent, Composer, Node 20 ou plus récent et environ 1 Go d'espace disque libre. `--dry-run` affiche les étapes sans les exécuter.

> [!NOTE]
> BioHop conserve ses comptes dans un hub HOP ID ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). Il n'existe pas de mode sans hub. L'installateur met en place un hub à côté de BioHop (le choix par défaut, environ une minute) ou se connecte à un hub que vous faites déjà tourner. Pour installer le hub et plusieurs applications d'un coup, utilisez `install-family.sh` du dépôt HEL.ink.

## Ce qu'est BioHop

BioHop est une application de lien en bio. Vous composez une page avec des liens, des images, des intégrations et des icônes de réseaux sociaux, vous choisissez un thème et vous la publiez. Le service hébergé [bio.hel.ink](https://bio.hel.ink) est le tableau de bord, et les pages publiques se trouvent sous `hel.ink/b/{slug}`. Sur votre propre serveur, une page est servie à l'adresse `{PUBLIC_URL}/b/{slug}`.

Il n'y a ni inscription ni mot de passe dans BioHop. Vous vous connectez une fois avec votre HOP ID sur le hub, et l'application vous reconnaît. BioHop est né comme la moitié « bio » d'une application dont l'autre moitié est aujourd'hui [LinkHop](https://github.com/navi-crwn/LinkHop), le raccourcisseur d'URL. Le plan de cette séparation se trouve dans [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (la phase D est ce dépôt).

Les captures d'écran ci-dessous proviennent d'une installation locale avec des données d'exemple inventées (la page d'un atelier de céramique). Elles ne montrent aucune personne ni aucun compte réels.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="L'éditeur de page : la liste des blocs avec un panneau Quick Add à gauche et, à droite, un aperçu en direct de la page sur un téléphone" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Statistiques de la page sur 30 jours : vues, clics sur les liens, taux de clic, clics dans le temps, appareils, liens les plus cliqués, pays et provenance des visiteurs" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="Une page bio publique sur un téléphone : profil, une pastille d'état, une barre d'onglets Links et Shop, trois boutons et une FAQ" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="L'onglet Shop de la même page : deux produits dans une grille à deux colonnes, l'un d'eux marqué Sold out" width="32%">
</p>

<a id="features"></a>

## Fonctionnalités

### Pages et éditeur

- Une page publique à l'adresse `/b/{slug}` avec un code QR (`/b/{slug}/qr` télécharge un PNG) et un manifeste d'application web installable.
- Un assistant en six étapes pour une nouvelle page : adresse, objectif, plateformes, premiers liens, profil et couleurs, avec un téléphone en direct. Le formulaire simple reste disponible avec `?classic=1`.
- Import d'une page Linktree publique sous forme de brouillon.
- L'éditeur, à l'adresse `/bio`, est une page qui défile normalement, avec un aperçu en direct à côté. Il enregistre tout seul environ une seconde après que vous avez cessé de taper et n'envoie que ce qui a changé. `Ctrl/Cmd+S` enregistre aussitôt et `Ctrl/Cmd+K` ouvre une palette de commandes.
- Glissez pour réordonner. Un mode sélection affiche, masque, archive ou supprime plusieurs blocs ensemble. Un bloc archivé quitte la page mais garde ses clics.
- Versions : l'éditeur enregistre une version avant chaque session de modification. Vous pouvez aussi nommer une version et restaurer n'importe laquelle.
- Les pages peuvent être dupliquées. Par défaut, un compte a 3 pages et une page a 100 blocs (voir les réglages plus bas).
- Design : quatre dispositions d'en-tête (classique, hero, bannière avec image de couverture, cutout), 17 thèmes dans le sélecteur, un fond en couleur, en dégradé ou en image, des styles de boutons et de blocs, des polices servies par l'application elle-même, du CSS propre à chaque page et un interrupteur pour la ligne « Powered by ».
- L'interface existe en English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français et Русский.

### Blocs

BioHop propose 29 types de blocs : lien, texte, titre, séparateur, espacement, image, carrousel, PDF, audio, vidéo, YouTube, Spotify, SoundCloud, musique, carte, compte à rebours, FAQ, HTML, code, carte de contact, e-mail, WhatsApp, lien vers une boutique d'applications, barre d'objectif, produit, collection, formulaire d'inscription, sondage et flux RSS ou Atom.

- Un bloc WhatsApp alterne entre 10 numéros au plus, à tour de rôle ou selon un poids.
- Un bloc d'application envoie le visiteur vers la boutique qui correspond à son système.
- Tout bloc peut avoir une heure de publication et une heure d'expiration.
- Un lien peut envoyer les visiteurs ailleurs selon le pays, l'appareil, le système ou l'heure de la journée, et peut avoir son propre code PIN.
- Une collection regroupe les blocs placés en dessous, empilés ou dans une grille à deux colonnes.
- Les lecteurs et les cartes de YouTube, Spotify et des autres hébergeurs ne se chargent que lorsque le visiteur les touche.

### Boutique

Un bloc produit comporte une image, un nom, un prix avec sa devise, un ancien prix, des variantes avec leur propre prix et leur propre stock, une quantité en stock et un interrupteur « épuisé ». Il peut remplir ses détails à partir de l'adresse de la boutique. Dès qu'une page a un produit, la page publique affiche une barre d'onglets Links et Shop, et la boutique a sa propre adresse, `/b/shop/{slug}`. BioHop ne traite aucun paiement : un produit ouvre sa propre adresse dans un nouvel onglet et compte comme un clic.

### Réglages de la page

Mot de passe, avertissement de contenu (aucun, sensible ou adulte 18+), page non répertoriée, mode confidentialité (aucun comptage du tout), une adresse vers laquelle envoyer les visiteurs tant que la page n'est pas publiée, une pastille d'état, un bouton flottant, une ligne de pied de page, une image de partage, un favicon, un titre et une description SEO, des paramètres UTM facultatifs sur les liens sortants, ainsi que le pixel Google Analytics, Tag Manager, Facebook ou TikTok du propriétaire de la page lui-même (voir [Confidentialité](#privacy)).

### Formulaires et webhooks

Un bloc de formulaire d'inscription recueille un nom, une adresse e-mail et un numéro de téléphone, et le visiteur doit cocher une case de consentement qui nomme la page. Le propriétaire télécharge les entrées en CSV, les supprime et fixe le nombre de jours pendant lesquels elles sont conservées (de 1 à 365, 90 par défaut). Une page peut aussi envoyer chaque nouvelle entrée, sous forme de JSON signé, à une adresse choisie par le propriétaire. Le planificateur retente les envois qui ont échoué, et l'éditeur affiche un journal des envois avec un bouton de test et un bouton de renvoi.

### Statistiques

Par page : vues, clics sur les liens, taux de clic, clics dans le temps, appareils, pays, liens les plus cliqués, sites référents, clics récents et façon dont les visiteurs sont arrivés (par le lien lui-même, le code QR, une étiquette NFC avec `?src=nfc` ou l'application web installée). Les clics de robots ne comptent pas, un nouveau clic du même visiteur dans les 10 minutes ne compte pas, et une adresse compte au plus 20 clics par bloc en 10 minutes. Ce que contient le journal des clics est décrit dans [Confidentialité](#privacy).

### Liens courts

« Shorten this URL » dans un bloc appelle l'API de LinkHop avec le jeton de service propre au membre (portée `link:create`, créé à la première utilisation et conservé dans `bio_linkhop_tokens`). Si LinkHop est injoignable, le bloc garde son adresse d'origine et l'éditeur le signale. Un lien vers hel.ink ou vers une application HOP est conservé tel qu'il a été saisi.

### Sécurité et exploitation

- `abuse:scan` vérifie les pages bio et les blocs avec les règles et la `domain_blacklist` partagée de `hop/safety`. Il s'exécute toutes les heures sur ce qui a changé et une fois par jour sur l'ensemble. Les consultations de Google Safe Browsing, d'abuse.ch URLhaus et de Spamhaus DBL sont facultatives.
- Les visiteurs peuvent signaler une page sur `/report-abuse` (Cloudflare Turnstile protège le formulaire lorsque ses clés sont définies).
- L'administration se fait dans Imperium, le panneau de contrôle de HOP. BioHop répond à ses requêtes signées `/api/hop/*`, et `/admin` y envoie les administrateurs.
- Les téléversements sont refusés avec un code 507 quand le disque est presque plein (voir [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). Les images téléversées sont réduites en WebP, et les fichiers SVG sont reconstruits à partir d'une liste d'éléments autorisés avant d'être enregistrés.
- Le lecteur de flux et l'expéditeur de webhooks se connectent aux adresses qu'ils ont vérifiées, de sorte qu'un nom ne puisse pas se résoudre en une autre adresse entre la vérification et la requête.
- La politique de sécurité du contenu ne nomme aucun hôte de CDN, de polices ou d'icônes. Les bibliothèques sont incluses dans l'application.
- La page d'accueil de bio.hel.ink est une visite interactive avec la page officielle de HEL.ink dans un téléphone. Lorsque « réduire les animations » est activé, chaque scène montre son état final.

<a id="run-your-own"></a>

## L'héberger vous-même

Il vous faut PHP 8.3 ou plus récent, Composer et Node 20 ou plus récent. Les paquets partagés `hop/auth`, `hop/ui` et `hop/safety` viennent de leurs dépôts GitHub (`navi-crwn/hop-auth`, `hop-ui` et `hop-safety`, déclarés comme dépôts `vcs` dans `composer.json`). L'installateur ci-dessus fait tout cela pour vous. À la main :

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Ouvrez `.env` avant de migrer. `.env.example` documente chaque clé. Définissez au moins celles-ci :

| Clé | Rôle |
|---|---|
| `APP_URL`, `PUBLIC_URL` | L'adresse du tableau de bord et l'adresse sous laquelle les pages publiques sont servies |
| `ASSET_URL` | L'endroit d'où sont servis les téléversements et les fichiers compilés ; vide signifie `APP_URL` |
| `HOP_HUB_URL` | L'adresse de votre hub (la connexion HOP ID s'y fait) |
| `HOP_APP` | L'identifiant (slug) de cette application sur le hub : `biohop` |
| `HOP_SSO_SECRET` ou `HOP_SSO_SECRET_BIOHOP` | Le secret SSO partagé avec le hub, de 32 caractères ou plus |
| `LINKHOP_URL` | Votre LinkHop, pour « shorten this URL » (`LINKHOP_ENABLED=false` le désactive) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Réglages de la base de données. `DB_MIGRATIONS_TABLE=migrations_bio` garde le journal des migrations de cette application séparé de celui du hub |
| `TRUSTED_PROXIES` | `cloudflare`, `*` ou une liste d'adresses de proxy, lorsque vous passez par un proxy |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Pages par compte (3), blocs par page (100) et adresse la plus courte qu'un membre peut réserver (5) |
| `BIO_ALLOW_CUSTOM_CSS` | À `false`, refuse le CSS personnalisé et cesse d'envoyer aux visiteurs le CSS déjà présent |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile pour le formulaire de signalement. En production, un secret manquant fait refuser les envois par le formulaire |

Les clés facultatives concernent GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), les consultations de réputation des liens (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), la marge d'espace pour les téléversements (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`) et la page officielle que montre la page d'accueil (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). Un opérateur peut aussi modifier en cours d'exécution, depuis Imperium, l'adresse la plus courte, le nombre de pages par compte, le nombre de blocs par page et l'interrupteur du CSS personnalisé.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Le garder en marche

BioHop a besoin d'un worker de file d'attente et du planificateur. En production, lancez `php artisan queue:work --tries=3` et `php artisan schedule:work` sous systemd ou supervisor, ou utilisez une seule ligne de cron : `* * * * * cd /path/to/BioHop && php artisan schedule:run`. Le `start.sh` de l'installateur démarre ensemble le serveur web, le worker et le planificateur, pour un essai sur une seule machine.

Le planificateur exécute ces commandes :

| Commande | Quand | Ce qu'elle fait |
|---|---|---|
| `abuse:scan --since=2h` | toutes les heures | Analyse les pages et les blocs qui ont changé |
| `abuse:scan --full --purge` | chaque jour, 04:10 | Analyse tout et supprime les éléments désactivés pour abus depuis plus longtemps que le délai de purge |
| `abuse:feeds` | chaque jour, 02:15 | Actualise les flux de menaces de `hop/safety` |
| `privacy:prune` | chaque jour, 03:30 | Supprime les journaux de clics de plus de 30 jours, les entrées de formulaire dont le délai de conservation est dépassé et les envois de webhook de plus de 7 jours |
| `bio:webhooks:retry` | chaque minute | Envoie les webhooks dont la prochaine tentative est arrivée |

Autres commandes, lancées à la main : `bio:official-page` remplit la page officielle de HEL.ink à partir du manifeste partagé, `bio:optimize-images` convertit les anciens téléversements en WebP, et `bio:unwrap-family-links` rétablit les liens vers des sites HOP qui avaient été enveloppés dans des liens courts. Les trois se contentent de faire un rapport, sauf si vous ajoutez `--apply`.

### Derrière Cloudflare : les pages publiques depuis la bordure

Une page bio publique est construite à chaque requête. Servie depuis un seul endroit, c'est rapide près du serveur et lent loin de lui : mesurée depuis l'origine, la page répond en 36 à 175 ms, alors qu'un visiteur en Asie, dont la requête passe par une bordure Cloudflare jusqu'en Allemagne, attend de 0,3 à 3,2 s. BioHop laisse donc une bordure garder une page simple pendant une minute. Une page publiée sans mot de passe, avertissement de contenu, formulaire ni sondage, demandée par un visiteur sans session, sans connexion et sans langue choisie, est envoyée avec `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` et `Vary: Cookie`, et sans cookie. Toute autre réponse reste `no-cache, private`. La visite est comptée par une petite requête depuis la page (`POST /b/{slug}/view`, sans framework JavaScript) avec les mêmes règles « une fois par visiteur » qu'un clic, si bien qu'une page venue de la bordure compte quand même ; une page que l'origine sert elle-même compte la visite comme avant.

Cloudflare ne met le HTML en cache que si une Cache Rule le demande. Créez-en une pour la zone :

- **Si** : le hostname est `hel.ink` ou `bio.hel.ink` (vos `PUBLIC_URL` et `APP_URL`), le chemin URI commence par `/b/`, et l'en-tête Cookie ne contient ni `biohop_session` (votre `SESSION_COOKIE`), ni `hop_lang`, ni `remember_web`.
- **Alors** : cache eligibility *Eligible for cache* ; Edge TTL *Use cache-control header if present, bypass cache if not* ; Browser TTL *Respect origin*.

Une règle qui contourne le cache dès qu'un cookie quelconque est présent est aussi correcte, seulement les visiteurs qui ont cliqué sur un bloc (`hel_vid`) ou choisi une langue contournent alors le cache eux aussi. Cloudflare n'honore pas `Vary: Cookie` ; c'est la condition sur le cookie dans la règle qui tient la page d'une personne connectée ou une page traduite à l'écart de la copie partagée.

Pour qu'un changement apparaisse tout de suite et non au bout de la minute, renseignez `CLOUDFLARE_ZONE_ID` et `CLOUDFLARE_API_TOKEN` (un jeton d'API avec *Zone → Cache Purge* pour cette zone). Enregistrer une page, la publier ou la retirer, ajouter, modifier ou supprimer un bloc met alors en file un purge par URL de la page, de sa boutique et de ses entrées QR, NFC et application installée sur les deux hôtes (`PurgeEdgeCache`, un job par enregistrement ; il faut le worker de file). Un purge en échec est journalisé, rien de plus ; l'enregistrement n'attend jamais Cloudflare. Les deux clés vides, seuls les en-têtes sont envoyés et un changement apparaît à la fin de la minute.

### Base de données

BioHop est conçu pour partager une base de données avec le hub et LinkHop. Chaque application tient son propre journal de migrations, ici `migrations_bio`. Exécutez d'abord les migrations du hub, pour que les tables qui appartiennent aux autres applications (dont `users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist` et `abuse_flags`) existent. Quand vous installez avec `--hub new` sur SQLite, BioHop utilise le fichier de base de données du hub. Cette conception est documentée, mais la compatibilité entre les versions des applications n'est pas vérifiée ici ; testez donc avant de pointer BioHop vers une base à laquelle vous tenez.

### Mise à jour et suppression

`./update.sh` commence par copier la base de données dans `backups/` (SQLite avec `sqlite3 .backup`, MySQL avec `mysqldump` ; la copie n'est lisible que par vous). Il met ensuite le site en mode maintenance, récupère le nouveau code, installe les paquets, compile le front end, migre et demande aux workers de file d'attente de redémarrer. Si une étape échoue avant la migration, il restaure le code précédent. Si elle échoue pendant ou après la migration, il laisse le site en mode maintenance et affiche les étapes manuelles. `--no-backup` saute la copie et `--force` met à jour même si vous avez modifié des fichiers suivis. `./uninstall.sh` conserve vos données ; `--purge` supprime aussi `.env`, `storage/` et `backups/`, après que vous avez saisi le nom du site.

### Tests

Les tests s'exécutent sur SQLite en mémoire. Les tables qui appartiennent à d'autres applications sont créées par `database/migrations/testing/`, et seulement quand la suite de tests s'exécute. L'intégration continue lance `composer audit`, Pint et les tests sous PHP 8.3.

```bash
php artisan test
vendor/bin/pint --test
```

## Connexion

Connectez-vous avec votre HOP ID sur le hub. Ouvrir `/bio` vous envoie au hub puis vous en ramène, ce qui suppose que `HOP_HUB_URL`, `HOP_APP=biohop` et le secret SSO soient déjà définis. La déconnexion met fin à votre session ici, puis sur le hub. La vérification de l'e-mail et les réglages du compte sont gérés sur le hub. Les pages bio publiques à l'adresse `/b/{slug}` n'exigent aucune connexion, mais BioHop est conçu pour fonctionner avec le hub et n'est pas une application autonome.

<a id="privacy"></a>

## Confidentialité

Voici ce que fait le code aujourd'hui.

- Le journal des clics conserve l'heure, le bloc, le pays, le type d'appareil, le navigateur, le site référent et la façon dont le visiteur est arrivé. Il conserve un hachage à clé de l'adresse IP du visiteur (HMAC-SHA256 avec `APP_KEY`, raccourci à 40 caractères), jamais l'adresse elle-même. `privacy:prune` supprime les journaux de clics au bout de 30 jours. Les votes de sondage et les entrées de formulaire ne conservent eux aussi qu'un hachage à clé de l'adresse.
- Pour trouver le pays, l'adresse du visiteur est cherchée dans une base GeoIP locale si vous en avez installé une, et sinon envoyée au premier service web de géolocalisation qui répond (voir [`CREDITS.md`](CREDITS.md)). Cette recherche utilise l'adresse en clair, avant son hachage.
- Pour compter une vue de page une seule fois par jour et plafonner les clics par adresse, l'application garde l'adresse en clair dans des clés de cache, jusqu'à 24 heures (vues) et 10 minutes (clics). Avec le cache en base de données par défaut, ces clés sont des lignes de la table `cache`.
- Un cookie aléatoire, `hel_vid`, dure un an et est posé lors d'un clic. Il sert uniquement à dédoublonner le comptage des clics. Quelqu'un qui lit pour la première fois une page publique simple ne reçoit ni session ni cookie. Les membres connectés ont des lignes de session qui contiennent leur adresse, comme avec toute session en base de données de Laravel.
- Le mode confidentialité d'une page arrête le comptage des vues et des clics pour cette page.
- Les signalements d'abus conservent l'adresse e-mail de la personne qui signale, le motif et son adresse IP dans la table partagée `abuse_reports`. Ce dépôt ne purge pas cette table.
- BioHop n'ajoute aucun script publicitaire ni d'analyse qui lui soit propre. Les polices, les icônes et les drapeaux sont servis par l'application, de sorte qu'un visiteur ne contacte ni Google ni un CDN.
- Le propriétaire d'une page peut ajouter Google Analytics, Google Tag Manager, un pixel Facebook ou un pixel TikTok à sa page. Le propriétaire de la page choisit de les ajouter et en est responsable ; BioHop ne fournit que la plateforme. Ils ne se chargent qu'une fois que le visiteur a accepté dans un bandeau sur la page, et son choix est mémorisé dans ce navigateur. Lorsqu'ils se chargent, le navigateur du visiteur envoie des données à ces services, selon leurs propres politiques de confidentialité. BioHop ne reçoit pas ces données.
- Le visiteur doit cocher une case de consentement pour envoyer un formulaire d'inscription. Les entrées sont conservées pour le propriétaire de la page, qui fixe la durée de conservation, peut les exporter en CSV et peut les supprimer. Elles sont supprimées avec la page ou le compte.

<a id="faq"></a>

## FAQ

**Puis-je utiliser BioHop sans le hub ?** Non. Modifier une page demande un HOP ID sur le hub, et il n'y a ni inscription ni mot de passe locaux. Les pages publiques n'exigent aucune connexion, mais l'application est conçue pour fonctionner avec le hub.

**Que deviennent mes données de clics ?** Elles sont conservées 30 jours puis supprimées. Le journal des clics contient un hachage à clé de l'adresse, pas l'adresse. Voir [Confidentialité](#privacy) pour les détails.

**L'analyse de sécurité se lance-t-elle toute seule ?** Oui, dès que le planificateur tourne. Elle s'appuie sur des règles partagées et une liste noire partagée. Aucune analyse automatique ne détecte tout.

**Puis-je exporter mes pages ?** Oui. `/settings/export` renvoie vos pages et vos blocs en JSON. Les journaux de clics et les entrées de formulaire n'y figurent pas ; les entrées de formulaire ont leur propre téléchargement en CSV.

**Puis-je l'héberger moi-même ?** Oui, sous licence MIT. Vous apportez votre propre hub, un LinkHop si vous voulez des liens courts, et une base de données. Voir [L'héberger vous-même](#run-your-own).

<a id="family"></a>

## Auto-hébergement

<!-- self-hosting:start -->
Tout ce dont BioHop a besoin se trouve dans des dépôts publics : aucun dépôt privé, aucun jeton et aucun compte hel.ink. Les détails sont dans le guide de la famille : [docs/self-hosting.md](https://github.com/navi-crwn/HEL.ink/blob/main/docs/self-hosting.md) (en anglais).

| Élément | Pour BioHop | Rôle |
| --- | --- | --- |
| [HEL.ink](https://github.com/navi-crwn/HEL.ink) (hub, HOP ID) | **Obligatoire** : les comptes y sont, dans la même base de données | Comptes et connexion pour toutes les apps |
| [hop/auth](https://github.com/navi-crwn/hop-auth), [hop/ui](https://github.com/navi-crwn/hop-ui), [hop/safety](https://github.com/navi-crwn/hop-safety) | Installés par `composer install` depuis GitHub public | Code PHP partagé : connexion, interface, sécurité des liens |
| [Imperium](https://github.com/navi-crwn/Imperium) | Facultatif, mais les pages d'administration de BioHop s'y trouvent | Panneau d'administration de toute la famille ; nécessite le hub |
| Polices et tuiles de la famille | Rien à installer : chargés depuis `hel.ink/hop-assets` | Sans hel.ink : polices système, pas de tuiles flottantes |

- **Ordre d'installation** : d'abord le [hub](https://github.com/navi-crwn/HEL.ink), puis BioHop. `./install.sh --hub new` installe les deux.
- **Réglages minimaux** : `APP_URL`, `PUBLIC_URL`, `HOP_APP=biohop`, `HOP_HUB_URL`, `HOP_SSO_SECRET` (celui du hub), `DB_*` (la base de données du hub), `HOP_TRUSTED_HOSTS` (vos noms d'hôte); avec Imperium: `HOP_CONTROL_URL`.
- **Seul ou en famille** : BioHop ne fonctionne pas sans hub. Dans la famille, il partage la base de données et le `HOP_SSO_SECRET` du hub.
- **Sans Imperium** : `/admin` affiche un avis. Gérez les comptes dans les pages d'administration du hub et utilisez l'API signée `/api/hop/*` et les commandes artisan.
<!-- self-hosting:end -->

## La famille HOP

BioHop fait partie de la famille **HOP** d'applications open source auto-hébergeables qui se trouve derrière [hel.ink](https://hel.ink).

<!-- hop-family:start -->
<!-- Generated from HEL.ink resources/hop-assets/apps.source.json; edit there, then run scripts/hop_assets.py build. -->
| App | Address | Repository | What it does |
| --- | --- | --- | --- |
| **HOP ID** | [hel.ink](https://hel.ink) · accounts at `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) | One account for every HOP app. |
| **LinkHop** | [link.hel.ink](https://link.hel.ink) · `hel.ink/l/…` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) | Short links, QR codes and click analytics. |
| **BioHop** | [bio.hel.ink](https://bio.hel.ink) · `hel.ink/b/…` | [navi-crwn/BioHop](https://github.com/navi-crwn/BioHop) | One link-in-bio page for everything you do. |
| **PixelHop** | [pixel.hel.ink](https://pixel.hel.ink) · `p.hel.ink/…` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) | Image hosting with albums and direct links. |
| **NoteHop** | [note.hel.ink](https://note.hel.ink) · `n.hel.ink/…` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) | Encrypted notes you share with a link. |
| **FileHop** | [file.hel.ink](https://file.hel.ink) | [navi-crwn/FileHop](https://github.com/navi-crwn/FileHop) | Convert files in your browser; nothing is uploaded. |
| **Imperium** | not public-facing: optional, for admins | [navi-crwn/Imperium](https://github.com/navi-crwn/Imperium) | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Sécurité

Vous avez trouvé une vulnérabilité ? Signalez-la en privé par les avis de sécurité GitHub de ce dépôt : [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). N'ouvrez pas d'issues publiques pour des signalements de sécurité, et ne publiez ni secrets, ni jetons, ni détails d'exploitation dans des lieux publics. [SECURITY.md](SECURITY.md) donne les détails.

## Journal des modifications et crédits

Les modifications sont listées dans [CHANGELOG.md](CHANGELOG.md). Les logiciels tiers, les polices, les icônes et les services que BioHop utilise ou embarque, avec leurs licences, sont listés dans [CREDITS.md](CREDITS.md).

<a id="license"></a>

## Licence

[MIT](LICENSE), comme le reste de la famille HOP. Les licences tierces sont listées dans [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · fait partie de la famille HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
