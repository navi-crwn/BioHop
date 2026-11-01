<p align="center">🌐 <a href="README.md">English</a> · <b>简体中文</b> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT 许可证"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests 工作流"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>一个页面汇集你的链接、嵌入内容、社交媒体和二维码 — 发布在 <code>hel.ink/b/{slug}</code>。</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#自行部署">自行部署</a> ·
  <a href="#功能">功能</a> ·
  <a href="#常见问题">常见问题</a> ·
  <a href="#安全">安全</a> ·
  <a href="#许可证">许可证</a>
</p>

---

一条命令安装（它会先检查你的电脑，只问几个问题，还能为你装好 HOP ID 中心）：

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

已克隆仓库时：`./install.sh` · 更新：`./update.sh` · 卸载：`./uninstall.sh`。加上 `--yes` 即接受所有默认值；`--help` 列出全部选项。

## BioHop 是什么？

BioHop 是一个 link-in-bio（链接集合页）应用：你把链接、图片、嵌入内容和社交媒体汇总到一个页面，选一个主题，然后发布到 `hel.ink/b/{slug}` — 并附带可分享的二维码。

它是原来那个应用拆分后的 bio 一半（现在另一半是 [LinkHop](https://github.com/navi-crwn/LinkHop)，URL 缩短服务）。账户由中枢（hub，[HEL.ink](https://github.com/navi-crwn/HEL.ink)）管理：这里没有注册或密码 — 你只需用 **HOP ID** 登录一次，应用就认识你了。拆分计划记录在 [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md)（阶段 D 即本仓库）。

<a id="功能"></a>

## 功能

- **公开 bio 页面位于 `hel.ink/b/{slug}`，每个页面都带二维码和点击统计。**
- **`/bio` 编辑器：管理页面、链接，上传图片/头像/图标/QR 标志，实时预览、复制页面和按页面的分析数据。**
- **主题预设（light、dark、minimal、neon 等多种），编辑器、预览和公开页面共用同一套。**
- **没有本地注册或密码：你在中枢用 HOP ID 登录一次即可。**
- **点击分析：国家（GeoIP）以及设备、浏览器和机器人识别。**
- **区块内的 "Shorten this URL" 通过 LinkHop 的 API 实现，使用成员自己的服务令牌；若 LinkHop 不可用，区块保留原始 URL。**
- **URL/内容安全：`abuse:scan` 配合共享的 `domain_blacklist`（hop/safety），每小时定时运行。**
- **每日清理点击数据（`privacy:prune`）。**

<a id="自行部署"></a>

## 自行部署

你需要 PHP 8.3、Composer 和 Node 20+。共享包 `hop/auth`、`hop/ui` 和 `hop/safety` 来自各自的 GitHub 仓库（`navi-crwn/hop-auth`、`hop-ui`、`hop-safety`，`main` 分支，在 `composer.json` 中列为 `vcs` 仓库）。

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**迁移前先配置。** 打开 `.env`（`.env.example` 记录了所有键；其中的注释就是参考文档），至少设置：

| 键 | 作用 |
|---|---|
| `APP_URL` / `PUBLIC_URL` | 本应用的地址，以及页面对外服务的公开地址 |
| `HOP_HUB_URL` | 你的中枢地址（HOP ID 登录跳转到那里） |
| `HOP_APP` | 本应用在中枢的标识：`biohop` |
| `HOP_SSO_SECRET` 或 `HOP_SSO_SECRET_BIOHOP` | 与中枢共享的 SSO 密钥，32 个字符以上 |
| `LINKHOP_URL` | 你的 LinkHop 地址（启用区块内的 "shorten this URL"） |
| `DB_CONNECTION`、`DB_*`、`DB_MIGRATIONS_TABLE` | 数据库设置；`DB_MIGRATIONS_TABLE=migrations_bio` 把本应用的迁移日志分开存放 |

```bash
php artisan migrate      # 只记录在 migrations_bio
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**数据库。** BioHop 的设计是与中枢和 LinkHop 共用一个 MySQL 数据库（每个应用有自己的迁移日志：这里是 `migrations_bio`）。先运行中枢的迁移，使 `users`、`roles`、`api_tokens`、`domain_blacklist` 等表先存在。这是文档记载的设计；各应用版本之间的迁移兼容性在本 README 中未经验证，因此在把 BioHop 指向你在意的数据库之前请先自行确认。

**测试**在内存中的 sqlite 上运行；其他应用拥有的表由 `database/migrations/testing/` 创建（仅用于测试）。

```bash
php artisan test
vendor/bin/pint --test
```

## 登录（HOP ID）

在中枢用你的 HOP ID 登录 — 这里没有注册或密码。登录和编辑器使用标准 SSO，要求上文的 `HOP_HUB_URL`、`HOP_APP=biohop` 和 SSO 密钥配置在登录前就绪。打开 `/bio` 会先跳到中枢再回来。退出登录会在这里结束会话，然后重定向到 HOP ID 的登出。

公开 bio 页面（`/b/{slug}`）无需登录。BioHop 的设计是与中枢一起运行 — 不是独立应用。

<a id="常见问题"></a>

## 常见问题

**我的点击数据会怎样？** 点击统计是为了让你了解页面的表现，`privacy:prune` 命令每天清理点击数据。这里刻意不保留长期点击存档。

**安全扫描会自动运行吗？** 会。`abuse:scan` 每小时定时运行，依据 hop/safety 的共享 `domain_blacklist` 检查 bio 页面和区块。这些是机制而非保证：任何自动扫描都不承诺能发现所有问题。

**能否在没有中枢的情况下使用 BioHop？** 不能。编辑需要中枢的 HOP ID，而且没有本地注册或密码。公开 bio 页面无需登录，但本应用的设计是与中枢一起运行，BioHop 无法保证公开路由从不访问中枢。

**可以自托管吗？** 可以 — MIT 许可证，上面的内容就是全部快速上手步骤。中枢、LinkHop 和数据库都由你自己提供。

<a id="家族"></a>

## HOP 家族

BioHop 是 [hel.ink](https://hel.ink) 背后可自托管开源应用 **HOP** 家族的一员。

| 应用 | 用途 | 线上地址 | 代码 |
|---|---|---|---|
| **Hub** | 账户门户、HOP ID 登录 | `hel.ink` · `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) |
| **LinkHop** | URL 缩短 | `link.hel.ink` · `hel.ink/l/{slug}` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) |
| **BioHop** | Link-in-bio 页面 | `bio.hel.ink` · `hel.ink/b/{slug}` | 本仓库 |
| **PixelHop** | 图片与媒体托管 | `pixel.hel.ink` · `p.hel.ink` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) |
| **NoteHop** | 私密笔记与文档 | `note.hel.ink` · `n.hel.ink` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) |

<a id="安全"></a>

## 安全

发现漏洞？请通过本仓库的 GitHub security advisories 私下报告：[github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories)。请勿为安全报告开设公开 issue，也不要在公开场合发布密钥、令牌或漏洞利用细节。详见 [SECURITY.md](SECURITY.md)。

<a id="许可证"></a>

## 许可证

[MIT](LICENSE) — 与 HOP 家族的其他应用相同。第三方许可证列在 [LICENSE](LICENSE) 文件中。

---

<p align="center"><sub>BioHop · HOP 家族的一员 · <a href="https://hel.ink">hel.ink</a></sub></p>
