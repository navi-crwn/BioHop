<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh.md">简体中文</a> · <a href="README.es.md">Español</a> · <a href="README.ar.md">العربية</a> · <a href="README.pt.md">Português</a> · <a href="README.fr.md">Français</a> · <b>日本語</b> · <a href="README.id.md">Bahasa Indonesia</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT ライセンス"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests ワークフロー"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>リンク・埋め込み・SNS・QR コードを 1 ページに — <code>hel.ink/b/{slug}</code> で公開。</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#自分で動かす">自分で動かす</a> ·
  <a href="#機能">機能</a> ·
  <a href="#よくある質問">よくある質問</a> ·
  <a href="#セキュリティ">セキュリティ</a> ·
  <a href="#ライセンス">ライセンス</a>
</p>

---

## BioHop とは？

BioHop は link-in-bio アプリです。リンク・画像・埋め込み・SNS を 1 ページにまとめ、テーマを選び、`hel.ink/b/{slug}` で公開できます — 共有できる QR コード付き。

もともと 1 つだったアプリの「bio」側が切り出されたものです（もう一方は URL 短縮サービスの [LinkHop](https://github.com/navi-crwn/LinkHop)）。アカウントはハブ（[HEL.ink](https://github.com/navi-crwn/HEL.ink)）に置かれます。ここには登録もパスワードもなく、**HOP ID** で一度サインインすればアプリがあなたを認識します。分割の計画は [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md)（フェーズ D がこのリポジトリ）に文書化されています。

<a id="機能"></a>

## 機能

- **公開 bio ページは `hel.ink/b/{slug}`。各ページに QR コードとクリック計測付き。**
- **`/bio` のエディタでページ・リンクの管理、画像/アバター/アイコン/QR ロゴのアップロード、ライブプレビュー、複製、ページごとの分析に対応。**
- **テーマプリセット（light、dark、minimal、neon など多数）をエディタ・プレビュー・公開ページで共通利用。**
- **ローカルな登録もパスワードも不要。ハブで HOP ID を使って一度サインインするだけ。**
- **クリック分析：国（GeoIP）に加え、デバイス・ブラウザ・ボットの判定。**
- **ブロック内の「Shorten this URL」はメンバー自身のサービストークンで LinkHop の API を呼び出し。LinkHop が止まっていればブロックは元の URL を保持。**
- **URL・コンテンツの安全確認：共有の `domain_blacklist`（hop/safety）を使う `abuse:scan` を毎時実行。**
- **クリックデータの毎日の削除（`privacy:prune`）。**

<a id="自分で動かす"></a>

## 自分で動かす

PHP 8.3、Composer、Node 20+ が必要です。共有パッケージ `hop/auth`、`hop/ui`、`hop/safety` はそれぞれの GitHub リポジトリから取得します（`navi-crwn/hop-auth`、`hop-ui`、`hop-safety`、ブランチ `main`、`composer.json` に `vcs` リポジトリとして登録）。

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**マイグレーション前に設定を。** `.env` を開き（`.env.example` が全キーを説明しており、そのコメントがリファレンスです）、少なくとも次を設定してください：

| キー | 内容 |
|---|---|
| `APP_URL` / `PUBLIC_URL` | このアプリのアドレスと、ページが公開される公開アドレス |
| `HOP_HUB_URL` | ハブのアドレス（HOP ID のサインインはそこへ移動します） |
| `HOP_APP` | ハブでのこのアプリの識別子：`biohop` |
| `HOP_SSO_SECRET` または `HOP_SSO_SECRET_BIOHOP` | ハブと共有する SSO シークレット。32 文字以上 |
| `LINKHOP_URL` | あなたの LinkHop のアドレス（ブロック内の「shorten this URL」を有効化） |
| `DB_CONNECTION`、`DB_*`、`DB_MIGRATIONS_TABLE` | データベース設定。`DB_MIGRATIONS_TABLE=migrations_bio` でこのアプリのマイグレーションログを分離 |

```bash
php artisan migrate      # migrations_bio のみに記録
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**データベース。** BioHop はハブおよび LinkHop と 1 つの MySQL データベースを共有する設計です（各アプリが独自のマイグレーションログを持ちます。ここでは `migrations_bio`）。まずハブのマイグレーションを実行して、`users`、`roles`、`api_tokens`、`domain_blacklist` などのテーブルを用意してください。これは文書化された設計であり、アプリのバージョン間のマイグレーション互換性はこの README では検証されていません。大事にしているデータベースに向ける前にご自身で確認してください。

**テスト**はメモリ上の sqlite で動作します。他アプリ所有のテーブルは `database/migrations/testing/` が作成します（テスト専用）。

```bash
php artisan test
vendor/bin/pint --test
```

## サインイン（HOP ID）

ハブで HOP ID を使ってサインインします。ここに登録もパスワードもありません。サインインとエディタは標準の SSO を使うため、サインイン前に上記の `HOP_HUB_URL`、`HOP_APP=biohop`、SSO シークレットの設定が必要です。`/bio` を開くとハブへ移動して戻ってきます。サインアウトするとここでセッションを終了し、HOP ID のログアウトへリダイレクトします。

公開 bio ページ（`/b/{slug}`）にサインインは不要です。BioHop はハブと一緒に動かす設計で、単体アプリではありません。

<a id="よくある質問"></a>

## よくある質問

**クリックデータはどうなりますか？** クリック計測はページのパフォーマンスを確認するためのもので、`privacy:prune` コマンドが毎日クリックデータを削除します。長期のクリックアーカイブは意図的に保持しません。

**セキュリティスキャンは自動で動きますか？** はい。`abuse:scan` は毎時実行されるようスケジュールされており、hop/safety の共有 `domain_blacklist` に基づいて bio ページとブロックを確認します。これは仕組みであって保証ではありません。自動スキャンがすべてを検出すると約束するものはありません。

**ハブなしで BioHop は使えますか？** いいえ。編集にはハブの HOP ID が必要で、ローカル登録もパスワードもありません。公開 bio ページにサインインは不要ですが、このアプリはハブと一緒に動かす設計であり、公開ルートが決してハブに接続しないことを BioHop は保証できません。

**セルフホストできますか？** できます。MIT ライセンスで、上のセクションがクイックスタートのすべてです。ハブ・LinkHop・データベースは各自で用意します。

<a id="ファミリー"></a>

## HOP ファミリー

BioHop は [hel.ink](https://hel.ink) を支えるセルフホスト可能なオープンソースアプリ群、**HOP** ファミリーの一員です。

| アプリ | 内容 | アドレス | コード |
|---|---|---|---|
| **Hub** | アカウントポータル、HOP ID サインイン | `hel.ink` · `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) |
| **LinkHop** | URL 短縮 | `link.hel.ink` · `hel.ink/l/{slug}` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) |
| **BioHop** | link-in-bio ページ | `bio.hel.ink` · `hel.ink/b/{slug}` | このリポジトリ |
| **PixelHop** | 画像・メディアホスティング | `pixel.hel.ink` · `p.hel.ink` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) |
| **NoteHop** | プライベートなノートとドキュメント | `note.hel.ink` · `n.hel.ink` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) |

<a id="セキュリティ"></a>

## セキュリティ

脆弱性を見つけましたか？ このリポジトリの GitHub security advisories から非公開で報告してください：[github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories)。セキュリティ報告で公開 issue を立てないでください。また、シークレット・トークン・エクスプロイトの詳細を公開場所に投稿しないでください。詳細は [SECURITY.md](SECURITY.md) を参照してください。

<a id="ライセンス"></a>

## ライセンス

[MIT](LICENSE) — HOP ファミリーの他のアプリと同じです。サードパーティのライセンスは [LICENSE](LICENSE) ファイルに記載されています。

---

<p align="center"><sub>BioHop · HOP ファミリーの一員 · <a href="https://hel.ink">hel.ink</a></sub></p>
