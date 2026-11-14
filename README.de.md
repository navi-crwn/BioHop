<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <b>Deutsch</b> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT-Lizenz"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="Tests-Workflow"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="Das BioHop-Dashboard: Klicks dieser Woche im Vergleich zur Vorwoche, eine Einrichtungs-Checkliste und eine Karte für jede Bio-Seite" width="100%"></picture></p>

<p align="center">Eine Seite für deine Links, Einbettungen, Social-Profile und einen QR-Code. Im gehosteten Dienst wird sie unter <code>hel.ink/b/{slug}</code> veröffentlicht.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Installation</a> ·
  <a href="#features">Funktionen</a> ·
  <a href="#run-your-own">Selbst betreiben</a> ·
  <a href="#privacy">Datenschutz</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#security">Sicherheit</a> ·
  <a href="#license">Lizenz</a>
</p>

---

<a id="install"></a>

## Installation

Ein Befehl. Er prüft zuerst deinen Rechner, stellt ein paar Fragen und kann auch den HOP-ID-Hub für dich einrichten:

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

Aus einem Klon: `./install.sh`. Aktualisieren mit `./update.sh`, entfernen mit `./uninstall.sh`. Mit `--yes` gelten alle Standardwerte, `--help` zeigt die Optionen. Das Installationsskript braucht PHP 8.3 oder neuer, Composer, Node 20 oder neuer und etwa 1 GB freien Speicherplatz. `--dry-run` gibt die Schritte aus, ohne sie auszuführen.

> [!NOTE]
> BioHop speichert seine Konten in einem HOP-ID-Hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). Einen Modus ohne Hub gibt es nicht. Das Installationsskript richtet neben BioHop einen Hub ein (Standard, etwa eine Minute) oder verbindet sich mit einem Hub, den du schon betreibst. Um den Hub und mehrere Apps auf einmal zu installieren, nimm `install-family.sh` aus dem HEL.ink-Repository.

## Was BioHop ist

BioHop ist eine Link-in-Bio-App. Du baust eine Seite aus Links, Bildern, Einbettungen und Social-Symbolen, wählst ein Design und veröffentlichst sie. Der gehostete Dienst [bio.hel.ink](https://bio.hel.ink) ist das Dashboard, die öffentlichen Seiten liegen unter `hel.ink/b/{slug}`. Auf deinem eigenen Server wird eine Seite unter `{PUBLIC_URL}/b/{slug}` ausgeliefert.

In BioHop gibt es keine Registrierung und kein Passwort. Du meldest dich einmal mit deiner HOP ID am Hub an, und die App kennt dich. BioHop begann als Bio-Hälfte einer App, deren andere Hälfte heute [LinkHop](https://github.com/navi-crwn/LinkHop) ist, der URL-Kürzer. Der Plan für diese Aufteilung steht in [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (Phase D ist dieses Repository).

Die folgenden Screenshots stammen aus einer lokalen Installation mit erfundenen Beispieldaten (die Seite eines Keramikstudios). Sie zeigen keine echten Personen oder Konten.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="Der Seiteneditor: links die Liste der Blöcke mit einem Quick-Add-Bereich, rechts eine Live-Vorschau der Seite im Smartphone" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Seitenstatistik für 30 Tage: Aufrufe, Link-Klicks, Klickrate, Klicks im Zeitverlauf, Geräte, beliebteste Links, Länder und Herkunft der Besucher" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="Eine öffentliche Bio-Seite auf dem Smartphone: Profil, ein Status-Chip, eine Tab-Leiste mit Links und Shop, drei Buttons und ein FAQ" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="Der Shop-Tab derselben Seite: zwei Produkte in einem zweispaltigen Raster, eines davon als Sold out markiert" width="32%">
</p>

<a id="features"></a>

## Funktionen

### Seiten und Editor

- Eine öffentliche Seite unter `/b/{slug}` mit QR-Code (`/b/{slug}/qr` lädt ein PNG herunter) und einem installierbaren Web-App-Manifest.
- Ein Assistent in sechs Schritten für eine neue Seite: Adresse, Ziel, Plattformen, erste Links, Profil und Farben, mit Live-Smartphone. Das einfache Formular bleibt unter `?classic=1` erreichbar.
- Import einer öffentlichen Linktree-Seite als Entwurf.
- Der Editor unter `/bio` ist eine Seite, die normal scrollt, mit einer Live-Vorschau daneben. Er speichert etwa eine Sekunde nach deiner letzten Eingabe von selbst und sendet nur, was sich geändert hat. `Ctrl/Cmd+S` speichert sofort, `Ctrl/Cmd+K` öffnet eine Befehlspalette.
- Per Drag-and-drop sortieren. Ein Auswahlmodus blendet mehrere Blöcke zusammen ein oder aus, archiviert oder löscht sie. Ein archivierter Block verschwindet von der Seite, behält aber seine Klicks.
- Versionen: Der Editor speichert vor jeder Bearbeitungssitzung eine Version. Du kannst Versionen auch benennen und jede davon wiederherstellen.
- Seiten lassen sich duplizieren. Standardmäßig hat ein Konto 3 Seiten und eine Seite 100 Blöcke (siehe die Einstellungen unten).
- Gestaltung: vier Kopfbereich-Layouts (klassisch, Hero, Banner mit Titelbild, Cutout), 17 Themes in der Auswahl, Hintergrund als Farbe, Verlauf oder Bild, Button- und Blockstile, Schriften, die von der App selbst ausgeliefert werden und eigenes CSS pro Seite.
- Die Oberfläche gibt es auf English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français und Русский.

### Blöcke

BioHop bietet beim Hinzufügen 19 Blockarten: Link, Text, Überschrift, Bild, Trennlinie, YouTube, Musik (Spotify, SoundCloud, Apple Music oder YouTube Music), Audioclip, Bildkarussell, PDF, Produkt, Sammlung, Zielbalken, Zahlung empfangen, Anmeldeformular, Countdown, Kontaktkarte, Standort und FAQ. Blöcke von fünf eingestellten Arten (WhatsApp-Rotator, App-Store-Link, SoundCloud, RSS-Feed, Umfrage), die eine Seite schon hat, funktionieren weiter und lassen sich bearbeiten, aber nicht neu hinzufügen.

- Der WhatsApp-Rotator-Block ist eingestellt: Eine Seite, die einen hat, behält ihn (er wechselt zwischen bis zu 10 Nummern, der Reihe nach oder nach Gewichtung), neue lassen sich nicht mehr anlegen.
- Ein App-Block schickt Besucher in den Store, der zu ihrem System passt.
- Jeder Block kann eine Veröffentlichungs- und eine Ablaufzeit haben.
- Ein Link kann Besucher nach Land, Gerät, System oder Tageszeit woandershin schicken und eine eigene PIN haben.
- Eine Sammlung fasst die Blöcke darunter zusammen, untereinander oder in einem zweispaltigen Raster.
- Player und Karten von YouTube, Spotify und den anderen Anbietern werden erst geladen, wenn der Besucher sie antippt.

### Shop

Ein Produktblock hat ein Bild, einen Namen, einen Preis mit Währung, einen Streichpreis, Varianten mit eigenem Preis und Bestand, einen Lagerbestand und einen Schalter für „ausverkauft“. Er kann seine Angaben aus der Adresse des Shops übernehmen. Sobald eine Seite ein Produkt hat, zeigt die öffentliche Seite eine Tab-Leiste mit Links und Shop, und der Shop hat eine eigene Adresse unter `/b/shop/{slug}`. BioHop wickelt keine Zahlungen ab: Ein Produkt öffnet seine eigene Adresse in einem neuen Tab und zählt als Klick.

### Seiteneinstellungen

Passwort, Inhaltswarnung (keine, sensibel oder ab 18), nicht gelistet, Datenschutzmodus (es wird gar nicht gezählt), eine Adresse, an die Besucher geschickt werden, solange die Seite unveröffentlicht ist, ein Status-Chip, ein schwebender Button, eine Fußzeile, ein Vorschaubild zum Teilen, ein Favicon, SEO-Titel und -Beschreibung, optionale UTM-Parameter an ausgehenden Links sowie der eigene Google-Analytics-, Tag-Manager-, Facebook- oder TikTok-Pixel des Seiteninhabers (siehe [Datenschutz](#privacy)).

### Formulare und Webhooks

Ein Anmeldeformular-Block erfasst Name, E-Mail-Adresse und Telefonnummer, und der Besucher muss ein Einwilligungskästchen ankreuzen, das die Seite nennt. Der Inhaber lädt die Einträge als CSV herunter, löscht sie und legt fest, wie viele Tage sie aufbewahrt werden (1 bis 365, standardmäßig 90). Eine Seite kann außerdem jeden neuen Eintrag als signiertes JSON an eine Adresse senden, die der Inhaber wählt. Der Scheduler wiederholt fehlgeschlagene Zustellungen, und der Editor zeigt ein Zustellprotokoll mit Test- und Erneut-senden-Button.

### Statistik

Pro Seite: Aufrufe, Link-Klicks, Klickrate, Klicks im Zeitverlauf, Geräte, Länder, beliebteste Links, verweisende Seiten, letzte Klicks und wie die Besucher kamen (über den Link selbst, den QR-Code, einen NFC-Tag mit `?src=nfc` oder die installierte Web-App). Klicks von Bots zählen nicht, ein wiederholter Klick desselben Besuchers innerhalb von 10 Minuten zählt nicht, und eine Adresse zählt höchstens 20 Klicks pro Block in 10 Minuten. Was das Klickprotokoll enthält, steht unter [Datenschutz](#privacy).

### Kurzlinks

„Shorten this URL“ in einem Block ruft die LinkHop-API mit dem eigenen Service-Token des Mitglieds auf (Scope `link:create`, beim ersten Gebrauch erstellt und in `bio_linkhop_tokens` gespeichert). Ist LinkHop nicht erreichbar, behält der Block seine ursprüngliche Adresse, und der Editor sagt es. Ein Link auf hel.ink oder eine HOP-App bleibt so, wie er eingegeben wurde.

### Sicherheit und Betrieb

- `abuse:scan` prüft Bio-Seiten und Blöcke mit den Regeln und der gemeinsamen `domain_blacklist` aus `hop/safety`. Es läuft stündlich über das, was sich geändert hat, und einmal am Tag über alles. Abfragen bei Google Safe Browsing, abuse.ch URLhaus und Spamhaus DBL sind optional.
- Besucher können eine Seite unter `/report-abuse` melden (Cloudflare Turnstile schützt das Formular, wenn seine Schlüssel gesetzt sind).
- Die Administration läuft in Imperium, dem HOP-Kontrollpanel. BioHop beantwortet dessen signierte `/api/hop/*`-Anfragen, und `/admin` schickt Admins dorthin.
- Uploads werden mit einem 507 abgelehnt, wenn die Festplatte fast voll ist (siehe [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). Hochgeladene Bilder werden zu WebP verkleinert. SVG wird nur für eigene Block-Icons und das QR-Logo angenommen und vor dem Speichern aus einer Positivliste neu aufgebaut; Profilbild, Bilder, Titelbild, Hintergrund, Vorschaubild und Favicon nehmen PNG, JPG, GIF, WebP oder BMP.
- Der Feed-Leser und der Webhook-Sender verbinden sich mit den Adressen, die sie geprüft haben, sodass ein Name zwischen Prüfung und Anfrage nicht auf eine andere Adresse auflösen kann.
- Die Content Security Policy nennt keinen CDN-, Schrift- oder Icon-Host. Bibliotheken sind in die App eingebunden.
- Die Startseite unter bio.hel.ink ist eine interaktive Tour mit der offiziellen HEL.ink-Seite in einem Smartphone. Bei aktivierter Einstellung „Bewegung reduzieren“ zeigt jede Szene ihren Endzustand.

<a id="run-your-own"></a>

## Selbst betreiben

Du brauchst PHP 8.3 oder neuer, Composer und Node 20 oder neuer. Die gemeinsamen Pakete `hop/auth`, `hop/ui` und `hop/safety` kommen aus ihren GitHub-Repositories (`navi-crwn/hop-auth`, `hop-ui` und `hop-safety`, in `composer.json` als `vcs`-Repositories eingetragen). Das Installationsskript oben erledigt das alles für dich. Von Hand:

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Öffne `.env`, bevor du migrierst. `.env.example` dokumentiert jeden Schlüssel. Setze mindestens diese:

| Schlüssel | Bedeutung |
|---|---|
| `APP_URL`, `PUBLIC_URL` | Die Adresse des Dashboards und die Adresse, unter der öffentliche Seiten ausgeliefert werden |
| `ASSET_URL` | Wo Uploads und gebaute Dateien ausgeliefert werden; leer heißt `APP_URL` |
| `HOP_HUB_URL` | Die Adresse deines Hubs (die Anmeldung mit HOP ID läuft dort) |
| `HOP_APP` | Der Kurzname dieser App im Hub: `biohop` |
| `HOP_SSO_SECRET` oder `HOP_SSO_SECRET_BIOHOP` | Das mit dem Hub geteilte SSO-Secret, 32 oder mehr Zeichen |
| `LINKHOP_URL` | Dein LinkHop für „shorten this URL“ (`LINKHOP_ENABLED=false` schaltet es ab) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Datenbankeinstellungen. `DB_MIGRATIONS_TABLE=migrations_bio` hält das Migrationsprotokoll dieser App getrennt von dem des Hubs |
| `TRUSTED_PROXIES` | `cloudflare`, `*` oder eine Liste von Proxy-Adressen, wenn du hinter einem Proxy läufst |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Seiten pro Konto (3), Blöcke pro Seite (100) und die kürzeste Adresse, die ein Mitglied belegen kann (5) |
| `BIO_ALLOW_CUSTOM_CSS` | Auf `false` setzen, um eigenes CSS abzulehnen und vorhandenes CSS nicht mehr an Besucher zu senden |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile für das Meldeformular. In Produktion lässt ein fehlendes Secret das Formular Einsendungen ablehnen |

Optionale Schlüssel betreffen GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), Reputationsabfragen für Links (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), den freien Platz für Uploads (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`) und die offizielle Seite, die die Startseite zeigt (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). Ein Betreiber kann außerdem die kürzeste Adresse, die Seiten pro Konto, die Blöcke pro Seite und den Schalter für eigenes CSS im laufenden Betrieb in Imperium ändern.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Am Laufen halten

BioHop braucht einen Queue-Worker und den Scheduler. In Produktion starte `php artisan queue:work --tries=3` und `php artisan schedule:work` unter systemd oder supervisor, oder nimm eine einzige Cron-Zeile: `* * * * * cd /path/to/BioHop && php artisan schedule:run`. Das `start.sh` des Installationsskripts startet Webserver, Worker und Scheduler zusammen für einen Test auf einem Rechner.

Der Scheduler führt diese Befehle aus:

| Befehl | Wann | Was er tut |
|---|---|---|
| `abuse:scan --since=2h` | stündlich | Prüft Seiten und Blöcke, die sich geändert haben |
| `abuse:scan --full --purge` | täglich, 04:10 | Prüft alles und löscht Elemente, die länger als die Aufbewahrungsfrist wegen Missbrauchs deaktiviert sind |
| `abuse:feeds` | täglich, 02:15 | Aktualisiert die Bedrohungs-Feeds aus `hop/safety` |
| `privacy:prune` | täglich, 03:30 | Löscht Klickprotokolle, die älter als 30 Tage sind, Formulareinträge nach Ablauf ihrer Aufbewahrungsfrist und Webhook-Zustellungen, die älter als 7 Tage sind |
| `bio:webhooks:retry` | jede Minute | Sendet Webhooks, deren nächster Versuch fällig ist |

Weitere Befehle, von Hand ausgeführt: `bio:official-page` füllt die offizielle HEL.ink-Seite aus dem gemeinsamen Manifest, `bio:optimize-images` wandelt alte Uploads in WebP um, und `bio:unwrap-family-links` stellt Links auf HOP-Seiten wieder her, die in Kurzlinks verpackt wurden. Alle drei berichten nur, solange du nicht `--apply` angibst.

### Hinter Cloudflare: öffentliche Seiten vom Edge

Eine öffentliche Bio-Seite wird bei jeder Anfrage neu gebaut. Von einem Ort ausgeliefert ist das nahe am Server schnell und weit davon langsam: vom Origin gemessen antwortet die Seite in 36 bis 175 ms, während eine Besucherin in Asien, deren Anfrage über einen Cloudflare-Edge nach Deutschland läuft, 0,3 bis 3,2 s wartet. BioHop lässt einen Edge deshalb eine einfache Seite eine Minute lang behalten. Eine veröffentlichte Seite ohne Passwort, Inhaltswarnung, Formular oder Umfrage, angefragt von jemandem ohne Sitzung, ohne Anmeldung und ohne gewählte Sprache, wird mit `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` und `Vary: Cookie` gesendet, und ohne Cookie. Jede andere Antwort bleibt `no-cache, private`. Der Besuch wird durch eine kleine Anfrage aus der Seite gezählt (`POST /b/{slug}/view`, kein JavaScript-Framework) mit denselben Einmal-pro-Besucher-Regeln wie ein Klick, sodass eine Seite vom Edge trotzdem zählt; eine Seite, die der Origin selbst ausliefert, zählt den Besuch wie bisher.

Cloudflare cacht HTML nur, wenn eine Cache Rule es verlangt. Lege eine für die Zone an:

- **Wenn**: Hostname ist `hel.ink` oder `bio.hel.ink` (deine `PUBLIC_URL` und `APP_URL`), der URI-Pfad beginnt mit `/b/`, und der Cookie-Header enthält weder `biohop_session` (dein `SESSION_COOKIE`) noch `hop_lang` noch `remember_web`.
- **Dann**: Cache eligibility *Eligible for cache*; Edge TTL *Use cache-control header if present, bypass cache if not*; Browser TTL *Respect origin*.

Eine Regel, die den Cache bei jedem Cookie umgeht, ist ebenfalls richtig, nur überspringen dann auch Besucher, die einen Block angeklickt (`hel_vid`) oder eine Sprache gewählt haben, den Cache. Cloudflare beachtet `Vary: Cookie` nicht, daher hält die Cookie-Bedingung der Regel die Seite einer angemeldeten Person oder eine übersetzte Seite von der gemeinsamen Kopie getrennt.

Damit eine Änderung sofort und nicht erst nach der Minute erscheint, setze `CLOUDFLARE_ZONE_ID` und `CLOUDFLARE_API_TOKEN` (ein API-Token mit *Zone → Cache Purge* für diese Zone). Das Speichern einer Seite, ihr Veröffentlichen oder Zurückziehen und das Hinzufügen, Ändern oder Entfernen eines Blocks stellen dann einen Purge per URL für die Seite, ihren Shop und ihre QR-, NFC- und App-Einstiege auf beiden Hosts in die Queue (`PurgeEdgeCache`, ein Job pro Speichern; braucht den Queue-Worker). Ein fehlgeschlagener Purge wird protokolliert, sonst nichts; das Speichern wartet nie auf Cloudflare. Sind beide Schlüssel leer, werden nur die Header gesendet, und eine Änderung erscheint nach Ablauf der Minute.

### Datenbank

BioHop ist dafür gebaut, eine Datenbank mit dem Hub und LinkHop zu teilen. Jede App führt ihr eigenes Migrationsprotokoll, hier `migrations_bio`. Führe zuerst die Migrationen des Hubs aus, damit die Tabellen existieren, die den anderen Apps gehören (darunter `users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist` und `abuse_flags`). Wenn du mit `--hub new` auf SQLite installierst, nutzt BioHop die Datenbankdatei des Hubs. Dieser Aufbau ist dokumentiert, aber die Kompatibilität zwischen den Releases der Apps wird hier nicht geprüft. Teste es also, bevor du BioHop auf eine Datenbank richtest, an der dir etwas liegt.

### Aktualisieren und entfernen

`./update.sh` kopiert zuerst die Datenbank nach `backups/` (SQLite mit `sqlite3 .backup`, MySQL mit `mysqldump`; die Kopie ist nur für dich lesbar). Danach schaltet es die Seite in den Wartungsmodus, holt den neuen Code, installiert Pakete, baut das Frontend, migriert und weist die Queue-Worker an, neu zu starten. Schlägt ein Schritt vor der Migration fehl, stellt es den vorherigen Code wieder her. Schlägt er bei oder nach der Migration fehl, lässt es die Seite im Wartungsmodus und gibt die manuellen Schritte aus. `--no-backup` überspringt die Kopie, und `--force` aktualisiert auch dann, wenn du verfolgte Dateien geändert hast. `./uninstall.sh` behält deine Daten; `--purge` löscht zusätzlich `.env`, `storage/` und `backups/`, nachdem du den Namen der Seite eingetippt hast.

### Tests

Die Tests laufen auf SQLite im Arbeitsspeicher. Die Tabellen, die anderen Apps gehören, legt `database/migrations/testing/` an, und nur wenn die Testsuite läuft. Die CI führt `composer audit`, Pint und die Tests mit PHP 8.3 aus.

```bash
php artisan test
vendor/bin/pint --test
```

## Anmeldung

Melde dich mit deiner HOP ID am Hub an. `/bio` zu öffnen schickt dich zum Hub und zurück, wofür `HOP_HUB_URL`, `HOP_APP=biohop` und das SSO-Secret vorher gesetzt sein müssen. Beim Abmelden endet deine Sitzung hier und danach am Hub. E-Mail-Bestätigung und Kontoeinstellungen übernimmt der Hub. Öffentliche Bio-Seiten unter `/b/{slug}` brauchen keine Anmeldung, aber BioHop ist dafür gebaut, zusammen mit dem Hub zu laufen, und keine eigenständige App.

<a id="privacy"></a>

## Datenschutz

So verhält sich der Code heute.

- Das Klickprotokoll enthält die Zeit, den Block, das Land, die Geräteart, den Browser, die verweisende Seite und wie der Besucher kam. Es speichert einen Hash der IP-Adresse des Besuchers mit Schlüssel (HMAC-SHA256 mit `APP_KEY`, auf 40 Zeichen gekürzt), nie die Adresse selbst. `privacy:prune` löscht Klickprotokolle nach 30 Tagen. Umfragestimmen und Formulareinträge speichern ebenfalls nur einen Hash der Adresse mit Schlüssel.
- Um das Land zu bestimmen, wird die Adresse des Besuchers in einer lokalen GeoIP-Datenbank nachgeschlagen, falls du eine installiert hast, und sonst an den ersten Web-Geolokalisierungsdienst gesendet, der antwortet (siehe [`CREDITS.md`](CREDITS.md)). Diese Abfrage nutzt die Adresse im Klartext, bevor sie gehasht wird.
- Um einen Seitenaufruf nur einmal am Tag zu zählen und Klicks pro Adresse zu begrenzen, behält die App die Adresse im Klartext in Cache-Schlüsseln, bis zu 24 Stunden (Aufrufe) und 10 Minuten (Klicks). Beim Standard-Datenbank-Cache sind diese Schlüssel Zeilen in der Tabelle `cache`.
- Ein zufälliges Cookie, `hel_vid`, gilt ein Jahr und wird bei einem Klick gesetzt. Es hilft nur dabei, Klickzahlen zu entdoppeln. Wer eine einfache öffentliche Seite zum ersten Mal liest, bekommt weder eine Sitzung noch Cookies. Angemeldete Mitglieder haben Sitzungszeilen, die ihre Adresse enthalten, wie bei jeder Laravel-Datenbanksitzung.
- Der Datenschutzmodus einer Seite stoppt das Zählen von Aufrufen und Klicks für diese Seite.
- Missbrauchsmeldungen speichern die E-Mail-Adresse des Melders, den Grund und die IP-Adresse des Melders in der gemeinsamen Tabelle `abuse_reports`. Dieses Repository bereinigt diese Tabelle nicht.
- BioHop fügt keine eigenen Werbe- oder Analyse-Skripte hinzu. Schriften, Icons und Flaggen kommen von der App selbst, sodass ein Besucher weder Google noch ein CDN kontaktiert.
- Ein Seiteninhaber kann Google Analytics, Google Tag Manager, einen Facebook-Pixel oder einen TikTok-Pixel zu seiner Seite hinzufügen. Der Seiteninhaber entscheidet sich dafür und ist dafür verantwortlich; BioHop stellt nur die Plattform bereit. Sie werden erst geladen, nachdem der Besucher in einem Banner auf der Seite zugestimmt hat, und seine Wahl wird in diesem Browser gespeichert. Sobald sie geladen sind, sendet der Browser des Besuchers Daten an diese Dienste, nach deren eigenen Datenschutzerklärungen. BioHop erhält diese Daten nicht.
- Um ein Anmeldeformular abzuschicken, muss der Besucher ein Einwilligungskästchen ankreuzen. Die Einträge werden für den Seiteninhaber gespeichert, der die Aufbewahrungsdauer festlegt, sie als CSV exportieren und löschen kann. Sie werden mit der Seite oder dem Konto gelöscht.

<a id="faq"></a>

## FAQ

**Kann ich BioHop ohne den Hub nutzen?** Nein. Zum Bearbeiten brauchst du eine HOP ID am Hub, und es gibt keine lokale Registrierung und kein Passwort. Öffentliche Seiten brauchen keine Anmeldung, aber die App ist dafür gebaut, zusammen mit dem Hub zu laufen.

**Was passiert mit meinen Klickdaten?** Sie werden 30 Tage aufbewahrt und dann gelöscht. Das Klickprotokoll enthält einen Hash der Adresse mit Schlüssel, nicht die Adresse. Einzelheiten stehen unter [Datenschutz](#privacy).

**Läuft die Sicherheitsprüfung von selbst?** Ja, sobald der Scheduler läuft. Sie nutzt gemeinsame Regeln und eine gemeinsame Blacklist. Keine automatische Prüfung erkennt alles.

**Kann ich meine Seiten exportieren?** Ja. `/settings/export` liefert deine Seiten und Blöcke als JSON. Klickprotokolle und Formulareinträge sind nicht enthalten; Formulareinträge haben ihren eigenen CSV-Download.

**Kann ich es selbst hosten?** Ja, unter der MIT-Lizenz. Du bringst deinen eigenen Hub, ein LinkHop, falls du Kurzlinks willst, und eine Datenbank mit. Siehe [Selbst betreiben](#run-your-own).

<a id="family"></a>

## Selbst hosten

<!-- self-hosting:start -->
Alles, was BioHop braucht, liegt in öffentlichen Repositorys: kein privates Repository, kein Token und kein hel.ink-Konto. Details stehen in der Familienanleitung: [docs/self-hosting.md](https://github.com/navi-crwn/HEL.ink/blob/main/docs/self-hosting.md) (Englisch).

| Baustein | Für BioHop | Wozu |
| --- | --- | --- |
| [HEL.ink](https://github.com/navi-crwn/HEL.ink) (Hub, HOP ID) | **Erforderlich**: Die Konten liegen dort, in derselben Datenbank | Konten und Anmeldung für alle Apps |
| [hop/auth](https://github.com/navi-crwn/hop-auth), [hop/ui](https://github.com/navi-crwn/hop-ui), [hop/safety](https://github.com/navi-crwn/hop-safety) | `composer install` holt sie aus dem öffentlichen GitHub | Gemeinsamer PHP-Code: Anmeldung, Oberfläche, Link-Sicherheit |
| [Imperium](https://github.com/navi-crwn/Imperium) | Optional, aber die Admin-Seiten von BioHop liegen dort | Admin-Panel für die ganze Familie; braucht den Hub |
| Familien-Schriften und -Kacheln | Nichts zu installieren: kommen von `hel.ink/hop-assets` | Ohne hel.ink: Systemschriften, keine schwebenden Kacheln |

- **Reihenfolge**: zuerst der [Hub](https://github.com/navi-crwn/HEL.ink), dann BioHop. `./install.sh --hub new` installiert beides.
- **Mindest-Einstellungen**: `APP_URL`, `PUBLIC_URL`, `HOP_APP=biohop`, `HOP_HUB_URL`, `HOP_SSO_SECRET` (wie im Hub), `DB_*` (die Datenbank des Hubs), `HOP_TRUSTED_HOSTS` (deine Hostnamen); mit Imperium: `HOP_CONTROL_URL`.
- **Allein oder in der Familie**: BioHop läuft nicht ohne Hub. In der Familie teilt es Datenbank und `HOP_SSO_SECRET` mit dem Hub.
- **Ohne Imperium**: `/admin` zeigt einen Hinweis. Konten verwaltest du in den Admin-Seiten des Hubs, dazu gibt es die signierte `/api/hop/*`-API und die artisan-Befehle.
<!-- self-hosting:end -->

## Die HOP-Familie

BioHop gehört zur **HOP**-Familie selbst hostbarer Open-Source-Apps hinter [hel.ink](https://hel.ink).

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
| **PayHop** | [pay.hel.ink](https://pay.hel.ink) | [navi-crwn/PayHop](https://github.com/navi-crwn/PayHop) | Make a payment QR and split a bill; nothing leaves your browser. |
| **Imperium** | not public-facing: optional, for admins | [navi-crwn/Imperium](https://github.com/navi-crwn/Imperium) | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Sicherheit

Schwachstelle gefunden? Melde sie privat über die Security Advisories von GitHub für dieses Repository: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Bitte eröffne für Sicherheitsmeldungen keine öffentlichen Issues und poste keine Secrets, Tokens oder Exploit-Details an öffentlichen Orten. Einzelheiten stehen in [SECURITY.md](SECURITY.md).

## Changelog und Danksagungen

Änderungen stehen in [CHANGELOG.md](CHANGELOG.md). Die Software von Drittanbietern, Schriften, Icons und Dienste, die BioHop nutzt oder mitliefert, samt ihren Lizenzen, sind in [CREDITS.md](CREDITS.md) aufgeführt.

<a id="license"></a>

## Lizenz

[MIT](LICENSE), wie der Rest der HOP-Familie. Lizenzen von Drittanbietern stehen in [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · Teil der HOP-Familie · <a href="https://hel.ink">hel.ink</a></sub></p>
