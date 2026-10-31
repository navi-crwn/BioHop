<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh.md">简体中文</a> · <a href="README.es.md">Español</a> · <a href="README.ar.md">العربية</a> · <a href="README.pt.md">Português</a> · <b>Français</b> · <a href="README.ja.md">日本語</a> · <a href="README.id.md">Bahasa Indonesia</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Licence MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="workflow tests"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>Une seule page avec vos liens, vos contenus intégrés, vos réseaux sociaux et un code QR — publiée sur <code>hel.ink/b/{slug}</code>.</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#exécuter-par-vous-même">Exécuter par vous-même</a> ·
  <a href="#fonctionnalités">Fonctionnalités</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#sécurité">Sécurité</a> ·
  <a href="#licence">Licence</a>
</p>

---

## Qu'est-ce que BioHop ?

BioHop est une application link-in-bio : vous composez une seule page avec vos liens, vos images, vos contenus intégrés et vos réseaux sociaux, vous choisissez un thème, puis vous la publiez sur `hel.ink/b/{slug}` — avec un code QR prêt à partager.

C'est la moitié « bio » de ce qui était autrefois une seule application (aujourd'hui [LinkHop](https://github.com/navi-crwn/LinkHop), le raccourcisseur d'URL). Les comptes vivent sur le hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink)) : il n'y a ici ni inscription ni mot de passe — vous vous connectez une fois avec votre **HOP ID** et l'application vous reconnaît. Le plan de cette scission est documenté dans [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (la phase D, c'est ce dépôt).

<a id="fonctionnalités"></a>

## Fonctionnalités

- **Des pages bio publiques sur `hel.ink/b/{slug}`, chacune avec un code QR et le suivi des clics.**
- **Un éditeur sur `/bio` pour les pages, les liens, le téléversement d'images/d'avatars/d'icônes/du logo du QR, l'aperçu en direct, la duplication et des statistiques par page.**
- **Des thèmes prédéfinis (light, dark, minimal, neon et bien d'autres) partagés par l'éditeur, son aperçu et la page publique.**
- **Pas d'inscription locale ni de mots de passe : vous vous connectez une fois avec votre HOP ID sur le hub.**
- **Des statistiques de clics avec le pays (GeoIP), plus la détection de l'appareil, du navigateur et des robots.**
- **« Shorten this URL » dans les blocs via l'API de LinkHop avec le jeton de service du membre lui-même ; si LinkHop est hors service, le bloc garde son URL d'origine.**
- **Sécurité des URL et du contenu : `abuse:scan` avec la `domain_blacklist` partagée (hop/safety), programmé toutes les heures.**
- **Purge quotidienne des données de clics (`privacy:prune`).**

<a id="exécuter-par-vous-même"></a>

## Exécuter par vous-même

Il vous faut PHP 8.3, Composer et Node 20+. Les paquets partagés `hop/auth`, `hop/ui` et `hop/safety` proviennent de leurs dépôts GitHub (`navi-crwn/hop-auth`, `hop-ui`, `hop-safety`, branche `main`, listés comme dépôts `vcs` dans `composer.json`).

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**Configurez avant de migrer.** Ouvrez le `.env` (le `.env.example` documente chaque clé ; ses commentaires servent de référence) et définissez au minimum :

| Clé | Rôle |
|---|---|
| `APP_URL` / `PUBLIC_URL` | L'adresse de cette application et l'adresse publique où les pages sont servies |
| `HOP_HUB_URL` | L'adresse de votre hub (la connexion HOP ID y mène) |
| `HOP_APP` | L'identifiant de cette application sur le hub : `biohop` |
| `HOP_SSO_SECRET` ou `HOP_SSO_SECRET_BIOHOP` | Le secret SSO partagé avec le hub, 32 caractères ou plus |
| `LINKHOP_URL` | L'adresse de votre LinkHop (active « shorten this URL » dans les blocs) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Réglages de la base de données ; `DB_MIGRATIONS_TABLE=migrations_bio` garde le journal de migrations de cette application séparé |

```bash
php artisan migrate      # n'enregistre que dans migrations_bio
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**Base de données.** BioHop est conçu pour partager une base MySQL unique avec le hub et LinkHop (chaque application tient son propre journal de migrations : `migrations_bio` ici). Lancez d'abord les migrations du hub, pour que des tables comme `users`, `roles`, `api_tokens` et `domain_blacklist` existent. C'est le design documenté ; la compatibilité des migrations entre les versions des applications n'est pas vérifiée dans ce README, vérifiez-la donc avant de pointer BioHop vers une base de données qui vous tient à cœur.

**Les tests** s'exécutent sur sqlite en mémoire ; les tables des autres applications sont créées par `database/migrations/testing/` (tests uniquement).

```bash
php artisan test
vendor/bin/pint --test
```

## Connexion (HOP ID)

Connectez-vous avec votre HOP ID sur le hub — ni inscription ni mot de passe ici. La connexion et l'éditeur utilisent le SSO standard, qui requiert que les réglages `HOP_HUB_URL`, `HOP_APP=biohop` et le secret SSO ci-dessus soient en place avant la connexion. Ouvrir `/bio` vous envoie vers le hub puis vous ramène. La déconnexion termine votre session ici puis redirige vers la déconnexion du HOP ID.

Les pages bio publiques (`/b/{slug}`) ne demandent pas de connexion. BioHop est conçu pour fonctionner avec le hub — ce n'est pas une application autonome.

<a id="faq"></a>

## FAQ

**Que deviennent mes données de clics ?** Le suivi des clics existe pour vous montrer les performances de votre page, et la commande `privacy:prune` supprime les données de clics chaque jour. Aucun historique de clics à long terme n'est conservé, volontairement.

**L'analyse de sécurité tourne-t-elle toute seule ?** Oui. `abuse:scan` est programmé toutes les heures et vérifie les pages et blocs bio par rapport à la `domain_blacklist` partagée de hop/safety. Ce sont des mécanismes, pas des garanties : aucune analyse automatisée ne promet de tout détecter.

**Puis-je utiliser BioHop sans le hub ?** Non. Éditer exige un HOP ID sur le hub, et il n'y a ni inscription locale ni mot de passe. Les pages bio publiques ne demandent pas de connexion, mais l'application est conçue pour fonctionner avec le hub, et BioHop ne peut garantir que les routes publiques ne contactent jamais le hub.

**Puis-je l'héberger moi-même ?** Oui — licence MIT, et la section ci-dessus constitue tout le guide de démarrage. Vous apportez votre propre hub, LinkHop et base de données.

<a id="famille"></a>

## La famille HOP

BioHop fait partie de la famille **HOP** d'applications open source auto-hébergeables derrière [hel.ink](https://hel.ink).

| Application | Ce qu'elle fait | Adresse | Code |
|---|---|---|---|
| **Hub** | Portail de comptes, connexion HOP ID | `hel.ink` · `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) |
| **LinkHop** | Raccourcisseur d'URL | `link.hel.ink` · `hel.ink/l/{slug}` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) |
| **BioHop** | Pages link-in-bio | `bio.hel.ink` · `hel.ink/b/{slug}` | ce dépôt |
| **PixelHop** | Hébergement d'images et de médias | `pixel.hel.ink` · `p.hel.ink` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) |
| **NoteHop** | Notes et documents privés | `note.hel.ink` · `n.hel.ink` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) |

<a id="sécurité"></a>

## Sécurité

Une vulnérabilité ? Signalez-la à titre privé via les security advisories GitHub de ce dépôt : [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Merci de ne pas ouvrir d'issues publiques pour les signalements de sécurité, et de ne pas publier de secrets, de jetons ni de détails d'exploitation en public. Consultez [SECURITY.md](SECURITY.md) pour plus de détails.

<a id="licence"></a>

## Licence

[MIT](LICENSE) — comme le reste de la famille HOP. Les licences tierces sont listées dans le fichier [LICENSE](LICENSE).

---

<p align="center"><sub>BioHop · membre de la famille HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
