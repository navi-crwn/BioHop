<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <b>Español</b> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Licencia MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="workflow de pruebas"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="El panel de BioHop: clics de esta semana frente a la anterior, una lista de configuración y una tarjeta por cada página bio" width="100%"></picture></p>

<p align="center">Una página para tus enlaces, incrustaciones, redes sociales y un código QR. En el servicio alojado se publica en <code>hel.ink/b/{slug}</code>.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Instalación</a> ·
  <a href="#features">Funciones</a> ·
  <a href="#run-your-own">Ejecútalo tú mismo</a> ·
  <a href="#privacy">Privacidad</a> ·
  <a href="#faq">Preguntas frecuentes</a> ·
  <a href="#security">Seguridad</a> ·
  <a href="#license">Licencia</a>
</p>

---

<a id="install"></a>

## Instalación

Un solo comando. Primero revisa tu equipo, te hace unas preguntas y puede configurar por ti el hub de HOP ID:

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

Desde un clon: `./install.sh`. Actualiza con `./update.sh` y elimina con `./uninstall.sh`. Añade `--yes` para aceptar todos los valores por defecto; `--help` muestra las opciones. El instalador necesita PHP 8.3 o superior, Composer, Node 20 o superior y cerca de 1 GB de espacio libre en disco. `--dry-run` muestra los pasos sin ejecutarlos.

> [!NOTE]
> BioHop guarda sus cuentas en un hub de HOP ID ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). No existe un modo sin hub. El instalador configura un hub junto a BioHop (lo predeterminado, alrededor de un minuto) o se conecta a uno que ya tengas. Para instalar el hub y varias apps de una vez, usa `install-family.sh` del repositorio HEL.ink.

## Qué es BioHop

BioHop es una app de enlace en la bio. Montas una página con enlaces, imágenes, incrustaciones e iconos de redes sociales, eliges un tema y la publicas. El servicio alojado en [bio.hel.ink](https://bio.hel.ink) es el panel, y las páginas públicas están en `hel.ink/b/{slug}`. En tu propio servidor, una página se sirve en `{PUBLIC_URL}/b/{slug}`.

En BioHop no hay registro ni contraseña. Inicias sesión una vez con tu HOP ID en el hub y la app te reconoce. BioHop empezó como la mitad "bio" de una app cuya otra mitad es hoy [LinkHop](https://github.com/navi-crwn/LinkHop), el acortador de URL. El plan de esa separación está en [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (la Fase D es este repositorio).

Las capturas de abajo salen de una instalación local con datos de ejemplo inventados (la página de un estudio de cerámica). No muestran personas ni cuentas reales.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="El editor de páginas: la lista de bloques con un panel Quick Add a la izquierda y una vista previa en vivo de la página en un móvil a la derecha" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Analítica de la página durante 30 días: visitas, clics en enlaces, tasa de clics, clics a lo largo del tiempo, dispositivos, enlaces más clicados, países y cómo llegaron los visitantes" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="Una página bio pública en un móvil: perfil, un chip de estado, una barra de pestañas Links y Shop, tres botones y unas preguntas frecuentes" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="La pestaña Shop de la misma página: dos productos en una cuadrícula de dos columnas, uno de ellos marcado como Sold out" width="32%">
</p>

<a id="features"></a>

## Funciones

### Páginas y editor

- Una página pública en `/b/{slug}` con código QR (`/b/{slug}/qr` descarga un PNG) y un manifiesto de app web instalable.
- Un asistente de seis pasos para una página nueva: dirección, objetivo, plataformas, primeros enlaces, perfil y colores, con un móvil en vivo. El formulario sencillo sigue disponible en `?classic=1`.
- Importación de una página pública de Linktree como borrador.
- El editor en `/bio` es una página que se desplaza con normalidad, con una vista previa en vivo al lado. Se guarda solo alrededor de un segundo después de que dejas de escribir y envía únicamente lo que cambió. `Ctrl/Cmd+S` guarda al instante y `Ctrl/Cmd+K` abre una paleta de comandos.
- Arrastra para reordenar. Un modo de selección muestra, oculta, archiva o elimina varios bloques a la vez. Un bloque archivado sale de la página pero conserva sus clics.
- Versiones: el editor guarda una versión antes de cada sesión de edición. También puedes ponerle nombre a una versión y restaurar cualquiera.
- Las páginas se pueden duplicar. Por defecto, una cuenta tiene 3 páginas y una página tiene 100 bloques (consulta los ajustes más abajo).
- Diseño: cuatro disposiciones de cabecera (clásica, hero, banner con imagen de portada, cutout), 17 temas en el selector, fondo en color, degradado o imagen, estilos de botones y bloques, tipografías servidas por la propia app, CSS propio por página y un interruptor para la línea "Powered by".
- La interfaz está disponible en English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français y Русский.

### Bloques

BioHop tiene 29 tipos de bloque: enlace, texto, encabezado, separador, espaciador, imagen, carrusel, PDF, audio, vídeo, YouTube, Spotify, SoundCloud, música, mapa, cuenta atrás, preguntas frecuentes, HTML, código, tarjeta de contacto, correo electrónico, WhatsApp, enlace a tienda de apps, barra de objetivo, producto, colección, formulario de registro, encuesta y feed RSS o Atom.

- Un bloque de WhatsApp rota entre hasta 10 números, por turno o por peso.
- Un bloque de app lleva al visitante a la tienda que corresponde a su sistema.
- Cualquier bloque puede tener una hora de publicación y otra de caducidad.
- Un enlace puede enviar a los visitantes a otro sitio según país, dispositivo, sistema u hora del día, y puede tener su propio PIN.
- Una colección agrupa los bloques que hay debajo, apilados o en una cuadrícula de dos columnas.
- Los reproductores y mapas de YouTube, Spotify y los demás servicios solo se cargan cuando el visitante los toca.

### Tienda

Un bloque de producto tiene imagen, nombre, precio con moneda, precio anterior, variantes con precio y existencias propios, un recuento de existencias y un interruptor de agotado. Puede rellenar sus datos a partir de la dirección de la tienda. Cuando una página tiene un producto, la página pública muestra una barra de pestañas Links y Shop, y la tienda tiene su propia dirección en `/b/shop/{slug}`. BioHop no procesa pagos: un producto abre su propia dirección en una pestaña nueva y cuenta como un clic.

### Ajustes de la página

Contraseña, aviso de contenido (ninguno, sensible o adulto 18+), no listada, modo de privacidad (sin ningún recuento), una dirección a la que enviar a los visitantes mientras la página no esté publicada, un chip de estado, un botón flotante, una línea de pie de página, una imagen para compartir, un favicon, título y descripción SEO, parámetros UTM opcionales en los enlaces salientes y el píxel propio del dueño de la página de Google Analytics, Tag Manager, Facebook o TikTok (consulta [Privacidad](#privacy)).

### Formularios y webhooks

Un bloque de formulario de registro recoge un nombre, una dirección de correo electrónico y un número de teléfono, y el visitante debe marcar una casilla de consentimiento que nombra la página. El dueño descarga las entradas como CSV, las elimina y fija durante cuántos días se conservan (de 1 a 365, 90 por defecto). Una página también puede enviar cada entrada nueva, como JSON firmado, a una dirección que elige el dueño. El programador de tareas reintenta las entregas fallidas, y el editor muestra un registro de entregas con un botón de prueba y otro de reenvío.

### Analítica

Por página: visitas, clics en enlaces, tasa de clics, clics a lo largo del tiempo, dispositivos, países, enlaces más clicados, sitios de referencia, clics recientes y cómo llegaron los visitantes (por el propio enlace, el código QR, una etiqueta NFC con `?src=nfc` o la app web instalada). Los clics de bots no cuentan, un nuevo clic del mismo visitante en menos de 10 minutos no cuenta, y una dirección cuenta como máximo 20 clics por bloque en 10 minutos. Lo que guarda el registro de clics está en [Privacidad](#privacy).

### Enlaces cortos

"Shorten this URL" en un bloque llama a la API de LinkHop con el token de servicio del propio miembro (ámbito `link:create`, creado en el primer uso y guardado en `bio_linkhop_tokens`). Si no se puede contactar con LinkHop, el bloque conserva su dirección original y el editor lo indica. Un enlace a hel.ink o a una app HOP se conserva tal como se escribió.

### Seguridad y operación

- `abuse:scan` revisa las páginas bio y los bloques con las reglas y la `domain_blacklist` compartida de `hop/safety`. Se ejecuta cada hora sobre lo que cambió y una vez al día sobre todo. Las consultas a Google Safe Browsing, abuse.ch URLhaus y Spamhaus DBL son opcionales.
- Los visitantes pueden denunciar una página en `/report-abuse` (Cloudflare Turnstile protege el formulario cuando se configuran sus claves).
- El trabajo de administración se hace en Imperium, el panel de control de HOP. BioHop responde a sus peticiones firmadas `/api/hop/*`, y `/admin` envía allí a los administradores.
- Las subidas se rechazan con un 507 cuando el disco está casi lleno (consulta [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). Las imágenes subidas se reducen a WebP, y los archivos SVG se reconstruyen a partir de una lista de permitidos antes de guardarse.
- El lector de feeds y el emisor de webhooks se conectan a las direcciones que comprobaron, de modo que un nombre no pueda resolverse a otra dirección entre la comprobación y la petición.
- La política de seguridad de contenido no nombra ningún host de CDN, de tipografías ni de iconos. Las bibliotecas van incluidas en la app.
- La página de inicio de bio.hel.ink es un recorrido interactivo con la página oficial de HEL.ink dentro de un móvil. Con "reducir movimiento" activado, cada escena muestra su estado final.

<a id="run-your-own"></a>

## Ejecútalo tú mismo

Necesitas PHP 8.3 o superior, Composer y Node 20 o superior. Los paquetes compartidos `hop/auth`, `hop/ui` y `hop/safety` vienen de sus repositorios de GitHub (`navi-crwn/hop-auth`, `hop-ui` y `hop-safety`, listados como repositorios `vcs` en `composer.json`). El instalador de arriba hace todo esto por ti. Para hacerlo a mano:

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Abre `.env` antes de migrar. `.env.example` documenta cada clave. Define al menos estas:

| Clave | Qué es |
|---|---|
| `APP_URL`, `PUBLIC_URL` | La dirección del panel y la dirección en la que se sirven las páginas públicas |
| `ASSET_URL` | Dónde se sirven las subidas y los archivos compilados; vacío significa `APP_URL` |
| `HOP_HUB_URL` | La dirección de tu hub (el inicio de sesión con HOP ID se hace allí) |
| `HOP_APP` | El slug de esta app en el hub: `biohop` |
| `HOP_SSO_SECRET` o `HOP_SSO_SECRET_BIOHOP` | El secreto SSO compartido con el hub, de 32 caracteres o más |
| `LINKHOP_URL` | Tu LinkHop, para "shorten this URL" (`LINKHOP_ENABLED=false` lo desactiva) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Ajustes de la base de datos. `DB_MIGRATIONS_TABLE=migrations_bio` mantiene el registro de migraciones de esta app separado del del hub |
| `TRUSTED_PROXIES` | `cloudflare`, `*` o una lista de direcciones de proxy, cuando funcionas detrás de un proxy |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Páginas por cuenta (3), bloques por página (100) y la dirección más corta que puede reclamar un miembro (5) |
| `BIO_ALLOW_CUSTOM_CSS` | Ponlo en `false` para rechazar CSS propio y dejar de enviar a los visitantes el CSS que ya existe |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile para el formulario de denuncia. En producción, si falta el secret, el formulario rechaza los envíos |

Las claves opcionales cubren GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), las consultas de reputación de enlaces (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), el margen de espacio para subidas (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`) y la página oficial que muestra la página de inicio (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). Un operador también puede cambiar en tiempo de ejecución, desde Imperium, la dirección más corta, las páginas por cuenta, los bloques por página y el interruptor del CSS propio.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Mantenerlo en marcha

BioHop necesita un worker de colas y el programador de tareas. En producción, ejecuta `php artisan queue:work --tries=3` y `php artisan schedule:work` bajo systemd o supervisor, o usa una sola línea de cron: `* * * * * cd /path/to/BioHop && php artisan schedule:run`. El `start.sh` del instalador arranca juntos el servidor web, el worker y el programador, para una prueba en una sola máquina.

El programador ejecuta estos comandos:

| Comando | Cuándo | Qué hace |
|---|---|---|
| `abuse:scan --since=2h` | cada hora | Revisa las páginas y bloques que cambiaron |
| `abuse:scan --full --purge` | a diario, 04:10 | Revisa todo y elimina los elementos desactivados por abuso desde hace más que el plazo de limpieza |
| `abuse:feeds` | a diario, 02:15 | Actualiza los feeds de amenazas de `hop/safety` |
| `privacy:prune` | a diario, 03:30 | Elimina los registros de clics de más de 30 días, las entradas de formulario que superaron su plazo de conservación y las entregas de webhook de más de 7 días |
| `bio:webhooks:retry` | cada minuto | Envía los webhooks cuyo próximo intento ha llegado |

Otros comandos, que se ejecutan a mano: `bio:official-page` rellena la página oficial de HEL.ink a partir del manifiesto compartido, `bio:optimize-images` convierte las subidas antiguas a WebP y `bio:unwrap-family-links` restaura los enlaces a sitios HOP que habían quedado envueltos en enlaces cortos. Los tres solo informan, salvo que pases `--apply`.

### Detrás de Cloudflare: páginas públicas desde el borde

Una página bio pública se construye en cada petición. Servida desde un solo lugar, eso es rápido cerca del servidor y lento lejos de él: medida desde el origen la página responde en 36 a 175 ms, mientras que un visitante en Asia, cuya petición viaja por un borde de Cloudflare hasta Alemania, espera de 0,3 a 3,2 s. Por eso BioHop deja que un borde conserve una página sencilla durante un minuto. Una página publicada sin contraseña, aviso de contenido, formulario ni encuesta, pedida por un visitante sin sesión, sin inicio de sesión y sin idioma elegido, se envía con `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` y `Vary: Cookie`, y sin cookie. Cualquier otra respuesta sigue siendo `no-cache, private`. La visita se cuenta con una pequeña petición desde la página (`POST /b/{slug}/view`, sin framework de JavaScript) con las mismas reglas de una vez por visitante que un clic, así que una página que vino del borde cuenta igual; una página que el origen sirve por sí mismo cuenta la visita como antes.

Cloudflare solo guarda HTML cuando una Cache Rule lo indica. Crea una para la zona:

- **Si**: el hostname es `hel.ink` o `bio.hel.ink` (tus `PUBLIC_URL` y `APP_URL`), la ruta URI empieza por `/b/`, y la cabecera Cookie no contiene `biohop_session` (tu `SESSION_COOKIE`), `hop_lang` ni `remember_web`.
- **Entonces**: cache eligibility *Eligible for cache*; Edge TTL *Use cache-control header if present, bypass cache if not*; Browser TTL *Respect origin*.

Una regla que evita la caché siempre que haya cualquier cookie también es correcta, solo que entonces los visitantes que pulsaron un bloque (`hel_vid`) o eligieron un idioma también se la saltan. Cloudflare no respeta `Vary: Cookie`, así que la condición de cookie en la regla es lo que mantiene la página de una persona con sesión o una página traducida apartada de la copia compartida.

Para que un cambio se vea al momento y no al cabo del minuto, define `CLOUDFLARE_ZONE_ID` y `CLOUDFLARE_API_TOKEN` (un token de API con *Zone → Cache Purge* para esa zona). Guardar una página, publicarla o retirarla, o añadir, cambiar o quitar un bloque encola entonces un purge por URL de la página, su tienda y sus entradas QR, NFC y de app instalada en ambos hosts (`PurgeEdgeCache`, un trabajo por guardado; necesita el worker de la cola). Un purge fallido se registra y nada más; el guardado nunca espera a Cloudflare. Con las dos claves vacías solo se envían las cabeceras y un cambio se ve cuando acaba el minuto.

### Base de datos

BioHop está pensado para compartir una base de datos con el hub y LinkHop. Cada app lleva su propio registro de migraciones, aquí `migrations_bio`. Ejecuta primero las migraciones del hub, para que existan las tablas que pertenecen a las otras apps (entre ellas `users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist` y `abuse_flags`). Cuando instalas con `--hub new` sobre SQLite, BioHop usa el archivo de base de datos del hub. Este diseño está documentado, pero aquí no se comprueba la compatibilidad entre las versiones de las apps, así que pruébalo antes de apuntar BioHop a una base de datos que te importe.

### Actualizar y eliminar

`./update.sh` primero copia la base de datos a `backups/` (SQLite con `sqlite3 .backup`, MySQL con `mysqldump`; la copia solo puede leerla tu usuario). Después pone el sitio en modo de mantenimiento, descarga el código nuevo, instala los paquetes, compila el front end, migra y avisa a los workers de colas de que se reinicien. Si un paso falla antes de la migración, restaura el código anterior. Si falla durante o después de la migración, deja el sitio en modo de mantenimiento e imprime los pasos manuales. `--no-backup` omite la copia y `--force` actualiza aunque hayas cambiado archivos rastreados. `./uninstall.sh` conserva tus datos; `--purge` borra además `.env`, `storage/` y `backups/`, después de que escribas el nombre del sitio.

### Pruebas

Las pruebas se ejecutan sobre SQLite en memoria. Las tablas que pertenecen a otras apps las crea `database/migrations/testing/`, y solo cuando se ejecuta la batería de pruebas. La CI ejecuta `composer audit`, Pint y las pruebas con PHP 8.3.

```bash
php artisan test
vendor/bin/pint --test
```

## Inicio de sesión

Inicia sesión con tu HOP ID en el hub. Abrir `/bio` te envía al hub y de vuelta, lo que exige que `HOP_HUB_URL`, `HOP_APP=biohop` y el secreto SSO estén definidos de antemano. Cerrar sesión termina tu sesión aquí y luego en el hub. La verificación del correo y los ajustes de la cuenta se gestionan en el hub. Las páginas bio públicas en `/b/{slug}` no requieren iniciar sesión, pero BioHop está hecho para funcionar junto con el hub y no es una app independiente.

<a id="privacy"></a>

## Privacidad

Esto es lo que hace el código hoy.

- El registro de clics guarda la hora, el bloque, el país, el tipo de dispositivo, el navegador, el sitio de referencia y cómo llegó el visitante. Guarda un hash con clave de la dirección IP del visitante (HMAC-SHA256 con `APP_KEY`, acortado a 40 caracteres), nunca la dirección en sí. `privacy:prune` elimina los registros de clics a los 30 días. Los votos de encuesta y las entradas de formulario también guardan solo un hash con clave de la dirección.
- Para averiguar el país, la dirección del visitante se busca en una base de datos GeoIP local, si has instalado una, y si no se envía al primer servicio web de geolocalización que responda (consulta [`CREDITS.md`](CREDITS.md)). Esa consulta usa la dirección en claro, antes de aplicarle el hash.
- Para contar una visita a la página una sola vez al día y limitar los clics por dirección, la app guarda la dirección en claro dentro de claves de caché hasta 24 horas (visitas) y 10 minutos (clics). Con la caché en base de datos por defecto, esas claves son filas de la tabla `cache`.
- Una cookie aleatoria, `hel_vid`, dura un año y se establece en un clic. Solo sirve para eliminar duplicados en el recuento de clics. Quien lee por primera vez una página pública sencilla no recibe sesión ni cookies. Los miembros con sesión iniciada tienen filas de sesión que incluyen su dirección, como en cualquier sesión de base de datos de Laravel.
- El modo de privacidad de una página detiene el recuento de visitas y clics de esa página.
- Las denuncias de abuso guardan la dirección de correo de quien denuncia, el motivo y su dirección IP en la tabla compartida `abuse_reports`. Este repositorio no depura esa tabla.
- BioHop no añade scripts propios de publicidad ni de analítica. Las tipografías, los iconos y las banderas los sirve la app, así que el visitante no contacta con Google ni con una CDN.
- El dueño de una página puede añadir Google Analytics, Google Tag Manager, un píxel de Facebook o un píxel de TikTok a su página. El dueño de la página decide añadirlos y es responsable de ellos; BioHop solo ofrece la plataforma. Se cargan únicamente después de que el visitante acepte en un aviso de la página, y su elección se recuerda en ese navegador. Cuando se cargan, el navegador del visitante envía datos a esos servicios, conforme a sus propias políticas de privacidad. BioHop no recibe esos datos.
- El visitante debe marcar una casilla de consentimiento para enviar un formulario de registro. Las entradas se guardan para el dueño de la página, que fija el plazo de conservación, puede exportarlas como CSV y puede eliminarlas. Se eliminan junto con la página o la cuenta.

<a id="faq"></a>

## Preguntas frecuentes

**¿Puedo usar BioHop sin el hub?** No. Para editar hace falta un HOP ID en el hub, y no hay registro ni contraseña locales. Las páginas públicas no requieren iniciar sesión, pero la app está hecha para funcionar junto con el hub.

**¿Qué pasa con mis datos de clics?** Se conservan 30 días y luego se eliminan. El registro de clics guarda un hash con clave de la dirección, no la dirección. Consulta [Privacidad](#privacy) para los detalles.

**¿El análisis de seguridad se ejecuta solo?** Sí, mientras el programador esté en marcha. Usa reglas compartidas y una lista negra compartida. Ningún análisis automático lo detecta todo.

**¿Puedo exportar mis páginas?** Sí. `/settings/export` devuelve tus páginas y bloques como JSON. No incluye los registros de clics ni las entradas de formulario; las entradas de formulario tienen su propia descarga en CSV.

**¿Puedo alojarlo yo mismo?** Sí, con la licencia MIT. Pones tu propio hub, un LinkHop si quieres enlaces cortos y una base de datos. Consulta [Ejecútalo tú mismo](#run-your-own).

<a id="family"></a>

## La familia HOP

BioHop forma parte de la familia **HOP** de apps de código abierto que se pueden alojar por cuenta propia, detrás de [hel.ink](https://hel.ink).

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
| **Imperium** | private (owner only) | private | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Seguridad

¿Encontraste una vulnerabilidad? Infórmala en privado mediante los avisos de seguridad de GitHub de este repositorio: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). No abras issues públicos para informes de seguridad y no publiques secretos, tokens ni detalles de exploits en lugares públicos. [SECURITY.md](SECURITY.md) tiene los detalles.

## Registro de cambios y créditos

Los cambios están en [CHANGELOG.md](CHANGELOG.md). El software de terceros, las tipografías, los iconos y los servicios que BioHop usa o incluye, con sus licencias, están en [CREDITS.md](CREDITS.md).

<a id="license"></a>

## Licencia

[MIT](LICENSE), como el resto de la familia HOP. Las licencias de terceros están en [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · parte de la familia HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
