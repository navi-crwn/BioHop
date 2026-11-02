<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <b>Bahasa Indonesia</b> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Lisensi MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="alur kerja tests"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="Dasbor BioHop: klik minggu ini dibandingkan minggu lalu, daftar periksa penyiapan, dan satu kartu untuk setiap halaman bio" width="100%"></picture></p>

<p align="center">Satu halaman untuk tautan, embed, media sosial, dan kode QR Anda. Di layanan hosting kami, halaman itu terbit di <code>hel.ink/b/{slug}</code>.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Instalasi</a> ·
  <a href="#features">Fitur</a> ·
  <a href="#run-your-own">Jalankan sendiri</a> ·
  <a href="#privacy">Privasi</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#security">Keamanan</a> ·
  <a href="#license">Lisensi</a>
</p>

---

<a id="install"></a>

## Instalasi

Satu perintah. Skrip ini memeriksa komputer Anda terlebih dahulu, mengajukan beberapa pertanyaan, dan dapat sekaligus memasang hub HOP ID untuk Anda:

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

Dari hasil clone: `./install.sh`. Perbarui dengan `./update.sh`, hapus dengan `./uninstall.sh`. Tambahkan `--yes` untuk memakai semua nilai bawaan; `--help` menampilkan daftar opsi. Penginstal memerlukan PHP 8.3 atau lebih baru, Composer, Node 20 atau lebih baru, dan sekitar 1 GB ruang disk kosong. `--dry-run` mencetak langkah-langkahnya tanpa menjalankannya.

> [!NOTE]
> BioHop menyimpan akunnya di hub HOP ID ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). Tidak ada mode tanpa hub. Penginstal memasang hub di samping BioHop (pilihan bawaan, sekitar satu menit) atau terhubung ke hub yang sudah Anda jalankan. Untuk memasang hub dan beberapa aplikasi sekaligus, gunakan `install-family.sh` dari repositori HEL.ink.

## Apa itu BioHop

BioHop adalah aplikasi link-in-bio. Anda menyusun satu halaman dari tautan, gambar, embed, dan ikon media sosial, memilih tema, lalu menerbitkannya. Layanan hosting di [bio.hel.ink](https://bio.hel.ink) adalah dasbornya, dan halaman publik ada di `hel.ink/b/{slug}`. Di server Anda sendiri, halaman disajikan di `{PUBLIC_URL}/b/{slug}`.

Di BioHop tidak ada pendaftaran dan tidak ada kata sandi. Anda masuk sekali dengan HOP ID Anda di hub, dan aplikasi mengenali Anda. BioHop dimulai sebagai separuh "bio" dari sebuah aplikasi yang separuh lainnya kini menjadi [LinkHop](https://github.com/navi-crwn/LinkHop), pemendek URL. Rencana pemisahan itu ada di [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (Fase D adalah repositori ini).

Tangkapan layar di bawah berasal dari instalasi lokal dengan data contoh rekaan (halaman sebuah studio keramik). Tidak ada orang atau akun sungguhan di dalamnya.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="Editor halaman: daftar blok dengan panel Quick Add di kiri dan pratinjau langsung halaman di ponsel di kanan" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Analitik halaman selama 30 hari: tayangan, klik tautan, rasio klik, klik dari waktu ke waktu, perangkat, tautan teratas, negara teratas, dan asal pengunjung" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="Halaman bio publik di ponsel: profil, chip status, bilah tab Links dan Shop, tiga tombol, dan sebuah FAQ" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="Tab Shop pada halaman yang sama: dua produk dalam grid dua kolom, salah satunya bertanda Sold out" width="32%">
</p>

<a id="features"></a>

## Fitur

### Halaman dan editor

- Halaman publik di `/b/{slug}` dengan kode QR (`/b/{slug}/qr` mengunduh PNG) dan manifes aplikasi web yang dapat dipasang.
- Wizard enam langkah untuk halaman baru: alamat, tujuan, platform, tautan pertama, profil, dan warna, dengan ponsel pratinjau langsung. Formulir biasa tetap tersedia di `?classic=1`.
- Impor dari halaman Linktree publik menjadi halaman draf.
- Editor di `/bio` adalah satu halaman yang bisa digulir seperti biasa, dengan pratinjau langsung di sebelahnya. Editor menyimpan sendiri sekitar satu detik setelah Anda berhenti mengetik dan hanya mengirim yang berubah. `Ctrl/Cmd+S` menyimpan seketika dan `Ctrl/Cmd+K` membuka palet perintah.
- Seret untuk mengurutkan. Mode pilih menampilkan, menyembunyikan, mengarsipkan, atau menghapus beberapa blok sekaligus. Blok yang diarsipkan hilang dari halaman tetapi tetap menyimpan kliknya.
- Versi: editor menyimpan satu versi sebelum setiap sesi penyuntingan. Anda juga dapat memberi nama pada versi dan memulihkan versi mana pun.
- Halaman dapat diduplikasi. Secara bawaan satu akun punya 3 halaman dan satu halaman punya 100 blok (lihat pengaturan di bawah).
- Desain: empat tata letak header (klasik, hero, banner dengan gambar sampul, cutout), 17 preset tema di pemilih, wallpaper berupa warna, gradien, atau gambar, gaya tombol dan blok, font yang disajikan dari aplikasi sendiri, CSS khusus per halaman, dan sakelar untuk baris "Powered by".
- Antarmukanya tersedia dalam English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français, dan Русский.

### Blok

BioHop punya 29 jenis blok: tautan, teks, judul, pemisah, spasi, gambar, carousel, PDF, audio, video, YouTube, Spotify, SoundCloud, musik, peta, hitung mundur, FAQ, HTML, kode, kartu kontak, email, WhatsApp, tautan toko aplikasi, bilah target, produk, koleksi, formulir pendaftaran, polling, dan feed RSS atau Atom.

- Blok WhatsApp memutar hingga 10 nomor, bergiliran atau menurut bobot.
- Blok aplikasi mengarahkan pengunjung ke toko yang sesuai dengan sistem mereka.
- Blok apa pun dapat diberi waktu terbit dan waktu berakhir.
- Tautan dapat mengarahkan pengunjung ke tempat lain menurut negara, perangkat, sistem, atau jam dalam sehari, dan dapat memiliki PIN sendiri.
- Koleksi mengelompokkan blok di bawahnya, bertumpuk atau dalam grid dua kolom.
- Pemutar dan peta dari YouTube, Spotify, dan host lainnya baru dimuat setelah pengunjung mengetuknya.

### Toko

Blok produk memiliki gambar, nama, harga beserta mata uang, harga coret, varian dengan harga dan stok sendiri, jumlah stok, dan sakelar habis terjual. Blok ini dapat mengisi rinciannya dari alamat toko. Begitu sebuah halaman punya produk, halaman publik menampilkan bilah tab Links dan Shop, dan toko punya alamat sendiri di `/b/shop/{slug}`. BioHop tidak memproses pembayaran: produk membuka alamatnya sendiri di tab baru dan dihitung sebagai klik.

### Pengaturan halaman

Kata sandi, peringatan konten (tidak ada, sensitif, atau dewasa 18+), tidak terdaftar, mode privasi (tidak ada penghitungan sama sekali), alamat tujuan pengunjung selama halaman belum terbit, chip status, tombol melayang, baris kaki halaman, gambar untuk berbagi, favicon, judul dan deskripsi SEO, parameter UTM opsional pada tautan keluar, serta piksel Google Analytics, Tag Manager, Facebook, atau TikTok milik pemilik halaman sendiri (lihat [Privasi](#privacy)).

### Formulir dan webhook

Blok formulir pendaftaran mengumpulkan nama, alamat email, dan nomor telepon, dan pengunjung harus mencentang kotak persetujuan yang menyebut halaman tersebut. Pemilik mengunduh entri sebagai CSV, menghapusnya, dan menentukan berapa hari entri disimpan (1 sampai 365, bawaan 90). Halaman juga dapat mengirim setiap entri baru sebagai JSON bertanda tangan ke alamat yang dipilih pemilik. Penjadwal mengulang pengiriman yang gagal, dan editor menampilkan log pengiriman dengan tombol uji dan tombol kirim ulang.

### Analitik

Per halaman: tayangan, klik tautan, rasio klik, klik dari waktu ke waktu, perangkat, negara, tautan teratas, situs perujuk, klik terbaru, dan cara pengunjung tiba (lewat tautannya sendiri, kode QR, tag NFC dengan `?src=nfc`, atau aplikasi web yang terpasang). Klik bot tidak dihitung, klik ulang dari pengunjung yang sama dalam 10 menit tidak dihitung, dan satu alamat dihitung paling banyak 20 klik per blok dalam 10 menit. Isi log klik dijelaskan di [Privasi](#privacy).

### Tautan pendek

"Shorten this URL" di sebuah blok memanggil API LinkHop dengan token layanan milik anggota sendiri (scope `link:create`, dibuat saat pertama dipakai dan disimpan di `bio_linkhop_tokens`). Jika LinkHop tidak dapat dijangkau, blok mempertahankan alamat aslinya dan editor memberi tahu. Tautan ke hel.ink atau ke aplikasi HOP dibiarkan seperti yang diketik.

### Keamanan dan operasi

- `abuse:scan` memeriksa halaman bio dan blok dengan aturan serta `domain_blacklist` bersama dari `hop/safety`. Perintah ini berjalan setiap jam pada yang berubah dan sekali sehari pada semuanya. Pencarian ke Google Safe Browsing, abuse.ch URLhaus, dan Spamhaus DBL bersifat opsional.
- Pengunjung dapat melaporkan halaman di `/report-abuse` (Cloudflare Turnstile melindungi formulir bila kuncinya diatur).
- Pekerjaan admin dilakukan di Imperium, panel kontrol HOP. BioHop menjawab permintaan `/api/hop/*` bertanda tangan dari Imperium, dan `/admin` mengarahkan admin ke sana.
- Unggahan ditolak dengan kode 507 ketika disk hampir penuh (lihat [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). Gambar yang diunggah dikecilkan ke WebP, dan berkas SVG dibangun ulang dari daftar yang diizinkan sebelum disimpan.
- Pembaca feed dan pengirim webhook terhubung ke alamat yang sudah mereka periksa, sehingga sebuah nama tidak dapat beralih ke alamat lain di antara pemeriksaan dan permintaan.
- Content security policy tidak menyebut host CDN, font, atau ikon mana pun. Pustaka disertakan dalam aplikasi.
- Beranda di bio.hel.ink adalah tur interaktif dengan halaman resmi HEL.ink di dalam sebuah ponsel. Bila "kurangi gerakan" aktif, setiap adegan menampilkan keadaan akhirnya.

<a id="run-your-own"></a>

## Jalankan sendiri

Anda memerlukan PHP 8.3 atau lebih baru, Composer, dan Node 20 atau lebih baru. Paket bersama `hop/auth`, `hop/ui`, dan `hop/safety` berasal dari repositori GitHub masing-masing (`navi-crwn/hop-auth`, `hop-ui`, dan `hop-safety`, tercantum sebagai repositori `vcs` di `composer.json`). Penginstal di atas mengerjakan semua ini untuk Anda. Untuk melakukannya secara manual:

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Buka `.env` sebelum Anda melakukan migrasi. `.env.example` menjelaskan setiap kunci. Atur sekurang-kurangnya ini:

| Kunci | Artinya |
|---|---|
| `APP_URL`, `PUBLIC_URL` | Alamat dasbor dan alamat tempat halaman publik disajikan |
| `ASSET_URL` | Tempat unggahan dan berkas hasil build disajikan; kosong berarti `APP_URL` |
| `HOP_HUB_URL` | Alamat hub Anda (login HOP ID dilakukan di sana) |
| `HOP_APP` | Slug aplikasi ini di hub: `biohop` |
| `HOP_SSO_SECRET` atau `HOP_SSO_SECRET_BIOHOP` | Rahasia SSO yang dibagi dengan hub, 32 karakter atau lebih |
| `LINKHOP_URL` | LinkHop Anda, untuk "shorten this URL" (`LINKHOP_ENABLED=false` mematikannya) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Pengaturan basis data. `DB_MIGRATIONS_TABLE=migrations_bio` menjaga log migrasi aplikasi ini terpisah dari log hub |
| `TRUSTED_PROXIES` | `cloudflare`, `*`, atau daftar alamat proxy, bila Anda berjalan di belakang proxy |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Halaman per akun (3), blok per halaman (100), dan alamat terpendek yang dapat diklaim anggota (5) |
| `BIO_ALLOW_CUSTOM_CSS` | Atur ke `false` untuk menolak CSS khusus dan berhenti mengirim CSS yang sudah ada ke pengunjung |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile untuk formulir laporan penyalahgunaan. Di produksi, secret yang hilang membuat formulir menolak kiriman |

Kunci opsional mencakup GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), pencarian reputasi tautan (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), ruang cadangan untuk unggahan (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`), dan halaman resmi yang ditampilkan beranda (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). Operator juga dapat mengubah alamat terpendek, halaman per akun, blok per halaman, dan sakelar CSS khusus saat aplikasi berjalan, dari Imperium.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Menjaganya tetap berjalan

BioHop memerlukan worker antrean dan penjadwal. Di produksi, jalankan `php artisan queue:work --tries=3` dan `php artisan schedule:work` di bawah systemd atau supervisor, atau pakai satu baris cron: `* * * * * cd /path/to/BioHop && php artisan schedule:run`. `start.sh` dari penginstal menjalankan server web, worker, dan penjadwal sekaligus untuk uji coba di satu mesin.

Penjadwal menjalankan perintah-perintah ini:

| Perintah | Kapan | Fungsinya |
|---|---|---|
| `abuse:scan --since=2h` | setiap jam | Memindai halaman dan blok yang berubah |
| `abuse:scan --full --purge` | setiap hari, 04:10 | Memindai semuanya dan menghapus item yang dinonaktifkan karena penyalahgunaan lebih lama dari masa pembersihan |
| `abuse:feeds` | setiap hari, 02:15 | Menyegarkan feed ancaman dari `hop/safety` |
| `privacy:prune` | setiap hari, 03:30 | Menghapus log klik yang lebih tua dari 30 hari, entri formulir yang melewati masa simpannya, dan pengiriman webhook yang lebih tua dari 7 hari |
| `bio:webhooks:retry` | setiap menit | Mengirim webhook yang waktu percobaan berikutnya sudah tiba |

Perintah lain, dijalankan manual: `bio:official-page` mengisi halaman resmi HEL.ink dari manifes bersama, `bio:optimize-images` mengubah unggahan lama menjadi WebP, dan `bio:unwrap-family-links` mengembalikan tautan ke situs HOP yang sempat dibungkus menjadi tautan pendek. Ketiganya hanya melaporkan kecuali Anda menambahkan `--apply`.

### Basis data

BioHop dirancang untuk berbagi satu basis data dengan hub dan LinkHop. Setiap aplikasi menyimpan log migrasinya sendiri, di sini `migrations_bio`. Jalankan migrasi hub lebih dulu agar tabel milik aplikasi lain (antara lain `users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist`, dan `abuse_flags`) sudah ada. Bila Anda memasang dengan `--hub new` di SQLite, BioHop memakai berkas basis data milik hub. Rancangan ini terdokumentasi, tetapi kompatibilitas antarrilis aplikasi tidak diperiksa di sini, jadi ujilah sebelum Anda mengarahkan BioHop ke basis data yang penting bagi Anda.

### Memperbarui dan menghapus

`./update.sh` lebih dulu menyalin basis data ke `backups/` (SQLite dengan `sqlite3 .backup`, MySQL dengan `mysqldump`; salinan itu hanya dapat dibaca oleh Anda). Setelah itu ia menempatkan situs dalam mode pemeliharaan, menarik kode baru, memasang paket, membangun front end, melakukan migrasi, dan meminta worker antrean untuk memulai ulang. Bila satu langkah gagal sebelum migrasi, kode sebelumnya dipulihkan. Bila gagal saat atau setelah migrasi, situs dibiarkan dalam mode pemeliharaan dan langkah manualnya dicetak. `--no-backup` melewati penyalinan, dan `--force` memperbarui meskipun Anda mengubah berkas yang dilacak. `./uninstall.sh` mempertahankan data Anda; `--purge` juga menghapus `.env`, `storage/`, dan `backups/` setelah Anda mengetik nama situs.

### Pengujian

Pengujian berjalan di SQLite dalam memori. Tabel milik aplikasi lain dibuat oleh `database/migrations/testing/`, dan hanya ketika rangkaian pengujian berjalan. CI menjalankan `composer audit`, Pint, dan pengujian di PHP 8.3.

```bash
php artisan test
vendor/bin/pint --test
```

## Masuk

Masuk dengan HOP ID Anda di hub. Membuka `/bio` mengirim Anda ke hub lalu kembali, dan itu memerlukan `HOP_HUB_URL`, `HOP_APP=biohop`, serta rahasia SSO sudah diatur lebih dulu. Keluar mengakhiri sesi Anda di sini, lalu di hub. Verifikasi email dan pengaturan akun ditangani di hub. Halaman bio publik di `/b/{slug}` tidak memerlukan login, tetapi BioHop dibuat untuk berjalan bersama hub dan bukan aplikasi mandiri.

<a id="privacy"></a>

## Privasi

Berikut yang dilakukan kode saat ini.

- Log klik menyimpan waktu, blok, negara, jenis perangkat, peramban, situs perujuk, dan cara pengunjung tiba. Yang disimpan adalah hash berkunci dari alamat IP pengunjung (HMAC-SHA256 dengan `APP_KEY`, dipotong menjadi 40 karakter), bukan alamatnya sendiri. `privacy:prune` menghapus log klik setelah 30 hari. Suara polling dan entri formulir juga hanya menyimpan hash berkunci dari alamat.
- Untuk mencari negara, alamat pengunjung dicari di basis data GeoIP lokal bila Anda memasangnya, dan jika tidak, dikirim ke layanan geolokasi web pertama yang menjawab (lihat [`CREDITS.md`](CREDITS.md)). Pencarian itu memakai alamat asli sebelum di-hash.
- Untuk menghitung satu tayangan halaman sekali sehari dan membatasi klik per alamat, aplikasi menyimpan alamat asli di dalam kunci cache hingga 24 jam (tayangan) dan 10 menit (klik). Dengan cache basis data bawaan, kunci ini berupa baris di tabel `cache`.
- Sebuah cookie acak, `hel_vid`, berlaku satu tahun dan dipasang saat klik. Gunanya hanya untuk menghilangkan duplikasi hitungan klik. Pembaca yang pertama kali membuka halaman publik biasa tidak mendapat sesi maupun cookie. Anggota yang masuk memiliki baris sesi yang memuat alamatnya, seperti pada sesi basis data Laravel mana pun.
- Mode privasi pada sebuah halaman menghentikan penghitungan tayangan dan klik untuk halaman itu.
- Laporan penyalahgunaan menyimpan alamat email pelapor, alasan, dan alamat IP pelapor di tabel bersama `abuse_reports`. Repositori ini tidak membersihkan tabel itu.
- BioHop tidak menambahkan skrip iklan atau analitik sendiri. Font, ikon, dan bendera disajikan dari aplikasi, sehingga pengunjung tidak menghubungi Google atau CDN.
- Pemilik halaman dapat menambahkan Google Analytics, Google Tag Manager, piksel Facebook, atau piksel TikTok ke halamannya. Pemilik halaman yang memilih menambahkannya dan bertanggung jawab atasnya; BioHop hanya menyediakan platform. Semuanya baru dimuat setelah pengunjung setuju lewat banner di halaman, dan pilihan pengunjung diingat di peramban itu. Saat dimuat, peramban pengunjung mengirim data ke layanan tersebut menurut kebijakan privasi mereka sendiri. BioHop tidak menerima data itu.
- Pengunjung harus mencentang kotak persetujuan untuk mengirim formulir pendaftaran. Entri disimpan untuk pemilik halaman, yang menetapkan masa simpan, dapat mengekspornya sebagai CSV, dan dapat menghapusnya. Entri ikut terhapus bersama halaman atau akun.

<a id="faq"></a>

## FAQ

**Bisakah saya memakai BioHop tanpa hub?** Tidak. Menyunting memerlukan HOP ID di hub, dan tidak ada pendaftaran atau kata sandi lokal. Halaman publik tidak memerlukan login, tetapi aplikasi dibuat untuk berjalan bersama hub.

**Apa yang terjadi pada data klik saya?** Data disimpan 30 hari lalu dihapus. Log klik memuat hash berkunci dari alamat, bukan alamatnya. Lihat [Privasi](#privacy) untuk rinciannya.

**Apakah pemindaian keamanan berjalan sendiri?** Ya, selama penjadwal berjalan. Pemindaian memakai aturan bersama dan daftar hitam bersama. Tidak ada pemindaian otomatis yang menangkap semuanya.

**Bisakah saya mengekspor halaman saya?** Bisa. `/settings/export` mengembalikan halaman dan blok Anda sebagai JSON. Log klik dan entri formulir tidak termasuk; entri formulir punya unduhan CSV sendiri.

**Bisakah saya menghostingnya sendiri?** Bisa, di bawah lisensi MIT. Anda menyediakan hub sendiri, LinkHop bila Anda ingin tautan pendek, dan basis data. Lihat [Jalankan sendiri](#run-your-own).

<a id="family"></a>

## Keluarga HOP

BioHop adalah bagian dari keluarga **HOP**, aplikasi sumber terbuka yang dapat di-host sendiri di balik [hel.ink](https://hel.ink).

<!-- hop-family:start -->
<!-- Generated from HEL.ink resources/hop-assets/apps.source.json; edit there, then run scripts/hop_assets.py build. -->
| App | Address | Repository | What it does |
| --- | --- | --- | --- |
| **HOP ID** | [hel.ink](https://hel.ink) · accounts at `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) | One account for every HOP app. |
| **LinkHop** | [link.hel.ink](https://link.hel.ink) · `hel.ink/l/…` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) | Short links, QR codes and click analytics. |
| **BioHop** | [bio.hel.ink](https://bio.hel.ink) · `hel.ink/b/…` | [navi-crwn/BioHop](https://github.com/navi-crwn/BioHop) | One link-in-bio page for everything you do. |
| **PixelHop** | [pixel.hel.ink](https://pixel.hel.ink) · `p.hel.ink/…` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) | Image hosting with albums and direct links. |
| **NoteHop** | [note.hel.ink](https://note.hel.ink) · `n.hel.ink/…` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) | Encrypted notes you share with a link. |
| **Imperium** | private (owner only) | private | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Keamanan

Menemukan kerentanan? Laporkan secara privat lewat security advisories GitHub untuk repositori ini: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Jangan membuka issue publik untuk laporan keamanan, dan jangan memasang secret, token, atau rincian eksploit di tempat umum. [SECURITY.md](SECURITY.md) memuat rinciannya.

## Changelog dan kredit

Perubahan tercatat di [CHANGELOG.md](CHANGELOG.md). Perangkat lunak pihak ketiga, font, ikon, dan layanan yang dipakai atau disertakan BioHop, beserta lisensinya, tercantum di [CREDITS.md](CREDITS.md).

<a id="license"></a>

## Lisensi

[MIT](LICENSE), seperti aplikasi lain di keluarga HOP. Lisensi pihak ketiga tercantum di [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · bagian dari keluarga HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
