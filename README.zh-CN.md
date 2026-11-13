<p align="center">🌐 <a href="README.md">English</a> · <b>简体中文</b> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT 许可证"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests 工作流"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="BioHop 控制台：本周点击量与上周对比、设置清单，以及每个 bio 页面的卡片" width="100%"></picture></p>

<p align="center">用一个页面放下你的链接、嵌入内容、社交账号和二维码。在托管服务上，页面发布在 <code>hel.ink/b/{slug}</code>。</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">安装</a> ·
  <a href="#features">功能</a> ·
  <a href="#run-your-own">自行部署</a> ·
  <a href="#privacy">隐私</a> ·
  <a href="#faq">常见问题</a> ·
  <a href="#security">安全</a> ·
  <a href="#license">许可证</a>
</p>

---

<a id="install"></a>

## 安装

一条命令。它会先检查你的电脑，问几个问题，也可以替你安装 HOP ID 枢纽：

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

在克隆的仓库里运行：`./install.sh`。更新用 `./update.sh`，卸载用 `./uninstall.sh`。加上 `--yes` 会采用所有默认值，`--help` 列出全部选项。安装程序需要 PHP 8.3 或更高版本、Composer、Node 20 或更高版本，以及约 1 GB 的可用磁盘空间。`--dry-run` 只打印步骤，不执行。

> [!NOTE]
> BioHop 的账号保存在 HOP ID 枢纽（[HEL.ink](https://github.com/navi-crwn/HEL.ink)）中，没有不依赖枢纽的模式。安装程序会在 BioHop 旁边装一个枢纽（默认做法，约一分钟），也可以连接你已有的枢纽。要一次装好枢纽和多个应用，请使用 HEL.ink 仓库里的 `install-family.sh`。

## BioHop 是什么

BioHop 是一个 link-in-bio 应用。你用链接、图片、嵌入内容和社交图标搭一个页面，选一个主题，然后发布。托管服务 [bio.hel.ink](https://bio.hel.ink) 是控制台，公开页面在 `hel.ink/b/{slug}`。在你自己的服务器上，页面地址是 `{PUBLIC_URL}/b/{slug}`。

BioHop 里没有注册，也没有密码。你在枢纽用 HOP ID 登录一次，应用就认识你了。BioHop 最初是一个应用的 bio 部分，另一部分现在是网址缩短服务 [LinkHop](https://github.com/navi-crwn/LinkHop)。拆分计划见 [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md)（阶段 D 就是这个仓库）。

下面的截图来自本地安装，数据是虚构的示例（一家陶瓷工作室的页面），不包含真实的人或账号。

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="页面编辑器：左边是区块列表和 Quick Add 面板，右边是页面的手机实时预览" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="30 天的页面分析：浏览量、链接点击、点击率、点击趋势、设备、热门链接、热门国家以及访客来源" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="手机上的公开 bio 页面：头像、状态标签、Links 和 Shop 标签栏、三个按钮和一个常见问题" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="同一页面的 Shop 标签：两件商品以两列网格显示，其中一件标为 Sold out" width="32%">
</p>

<a id="features"></a>

## 功能

### 页面与编辑器

- 公开页面位于 `/b/{slug}`，带二维码（`/b/{slug}/qr` 下载 PNG）和可安装的 Web 应用清单。
- 新建页面的六步向导：地址、用途、平台、首批链接、个人资料和颜色，并带实时手机预览。普通表单仍可通过 `?classic=1` 使用。
- 可以从公开的 Linktree 页面导入，生成一个草稿页面。
- `/bio` 的编辑器是一个可正常滚动的页面，旁边是实时预览。你停止输入约一秒后它会自动保存，并且只发送改动的部分。`Ctrl/Cmd+S` 立即保存，`Ctrl/Cmd+K` 打开命令面板。
- 拖动即可排序。选择模式可以一次显示、隐藏、归档或删除多个区块。归档的区块会从页面上消失，但保留它的点击数。
- 版本：每次编辑会话开始前，编辑器会保存一个版本。你也可以给版本命名，并恢复任何一个版本。
- 页面可以复制。默认每个账号有 3 个页面，每个页面有 100 个区块（见下文的设置）。
- 设计：四种页头布局（经典、hero、带封面图的横幅、cutout），选择器里有 17 个主题预设，壁纸可以是纯色、渐变或图片，还有按钮和区块样式、由应用自己提供的字体，以及每个页面的自定义 CSS。
- 界面提供 English、简体中文、Deutsch、Bahasa Indonesia、日本語、Português (Brasil)、Español、Français 和 Русский。

### 区块

添加区块时，BioHop 提供 19 种区块：链接、文本、标题、图片、分隔线、YouTube、音乐（Spotify、SoundCloud、Apple Music 或 YouTube Music）、音频片段、图片轮播、PDF、商品、合集、目标进度条、收款、报名表单、倒计时、名片、地点和 FAQ。页面上已有的五种已停用区块（WhatsApp 轮换、应用商店链接、SoundCloud、RSS 订阅源、投票）仍可使用和编辑，但不能再新增。

- WhatsApp 轮换区块已停用：已有该区块的页面继续保留（最多可轮流使用 10 个号码，按顺序或按权重分配），不能再创建新的。
- 应用区块会把访客带到与其系统对应的应用商店。
- 任何区块都可以设置发布时间和到期时间。
- 链接可以按国家、设备、系统或一天中的时段把访客送到别处，也可以设置自己的 PIN。
- 合集会把它下面的区块分组，可以堆叠显示，也可以排成两列网格。
- YouTube、Spotify 等的播放器和地图，只有在访客点按后才会从对应主机加载。

### 商店

商品区块包含图片、名称、带货币的价格、原价、各有价格和库存的规格、库存数量和售罄开关。它可以根据商店地址自动填入详情。页面有商品时，公开页面会显示 Links 和 Shop 标签栏，商店有自己的地址 `/b/shop/{slug}`。BioHop 不处理付款：商品会在新标签页打开它自己的地址，并计为一次点击。

### 页面设置

密码、内容警告（无、敏感或成人 18+）、不公开列出、隐私模式（完全不计数）、页面未发布时把访客送往的地址、状态标签、悬浮按钮、页脚文字、分享图片、favicon、SEO 标题和描述、可选的外链 UTM 参数，以及页面所有者自己的 Google Analytics、Tag Manager、Facebook 或 TikTok 像素（见[隐私](#privacy)）。

### 表单与 webhook

报名表单区块会收集姓名、电子邮件地址和电话号码，访客必须勾选一个写明该页面的同意框。所有者可以把条目下载为 CSV、删除条目，并设置保留天数（1 到 365 天，默认 90 天）。页面还可以把每条新条目作为签名的 JSON 发送到所有者指定的地址。计划任务会重试失败的投递，编辑器显示投递日志，并提供测试按钮和重发按钮。

### 分析

每个页面：浏览量、链接点击、点击率、点击趋势、设备、国家、热门链接、来源网站、最近点击，以及访客的到达方式（链接本身、二维码、用 `?src=nfc` 的 NFC 标签，或已安装的 Web 应用）。机器人点击不计入，同一访客 10 分钟内的重复点击不计入，同一地址在 10 分钟内对每个区块最多计 20 次点击。点击日志里保存了什么，见[隐私](#privacy)。

### 短链接

区块里的“Shorten this URL”会用成员自己的服务令牌（scope `link:create`，首次使用时创建，保存在 `bio_linkhop_tokens`）调用 LinkHop API。如果连不上 LinkHop，区块保留原始地址，编辑器会提示。指向 hel.ink 或某个 HOP 应用的链接会按输入原样保留。

### 安全与运维

- `abuse:scan` 用 `hop/safety` 的规则和共享的 `domain_blacklist` 检查 bio 页面和区块。它每小时扫描有变动的内容，每天对全部内容扫描一次。Google Safe Browsing、abuse.ch URLhaus 和 Spamhaus DBL 的查询是可选的。
- 访客可以在 `/report-abuse` 举报页面（设置了密钥时，表单由 Cloudflare Turnstile 保护）。
- 管理工作在 Imperium（HOP 控制面板）里进行。BioHop 响应它签名的 `/api/hop/*` 请求，`/admin` 会把管理员送过去。
- 磁盘快满时，上传会被拒绝并返回 507（见 [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)）。上传的图片会缩小为 WebP。SVG 只用于区块的自定义图标和二维码 Logo，存储前会按白名单重建；头像、图片、封面、背景、分享图和网站图标只接受 PNG、JPG、GIF、WebP 或 BMP。
- 订阅源读取器和 webhook 发送器只连接它们检查过的地址，所以域名不会在检查和请求之间解析到另一个地址。
- 内容安全策略不包含任何 CDN、字体或图标主机，库都随应用打包。
- bio.hel.ink 的首页是一个交互式导览，手机里展示官方 HEL.ink 页面。开启“减少动态效果”后，每个场景都显示最终状态。

<a id="run-your-own"></a>

## 自行部署

你需要 PHP 8.3 或更高版本、Composer 和 Node 20 或更高版本。共享包 `hop/auth`、`hop/ui` 和 `hop/safety` 来自各自的 GitHub 仓库（`navi-crwn/hop-auth`、`hop-ui` 和 `hop-safety`，在 `composer.json` 中列为 `vcs` 仓库）。上面的安装程序会替你完成这些。手动安装的步骤：

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

迁移之前先打开 `.env`。`.env.example` 说明了每一个键。至少设置这些：

| 键 | 含义 |
|---|---|
| `APP_URL`, `PUBLIC_URL` | 控制台的地址，以及公开页面所在的地址 |
| `ASSET_URL` | 上传文件和构建文件的提供位置；留空表示使用 `APP_URL` |
| `HOP_HUB_URL` | 你的枢纽地址（HOP ID 登录在那里进行） |
| `HOP_APP` | 本应用在枢纽中的标识：`biohop` |
| `HOP_SSO_SECRET` 或 `HOP_SSO_SECRET_BIOHOP` | 与枢纽共享的 SSO 密钥，32 个字符或更长 |
| `LINKHOP_URL` | 你的 LinkHop，用于“shorten this URL”（`LINKHOP_ENABLED=false` 可关闭） |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | 数据库设置。`DB_MIGRATIONS_TABLE=migrations_bio` 让本应用的迁移记录与枢纽的分开 |
| `TRUSTED_PROXIES` | 在代理之后运行时，填 `cloudflare`、`*` 或代理地址列表 |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | 每个账号的页面数（3）、每个页面的区块数（100），以及成员可占用的最短地址（5） |
| `BIO_ALLOW_CUSTOM_CSS` | 设为 `false` 会拒绝自定义 CSS，并且不再向访客发送已有的 CSS |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | 用于举报表单的 Cloudflare Turnstile。在生产环境中缺少 secret 时，表单会拒绝提交 |

可选的键涉及 GeoIP（`GEOLITE_CITY_DB`、`GEOLITE_ASN_DB`、`IPINFO_API_KEY`、`ABSTRACTAPI_KEY`）、链接信誉查询（`SAFE_BROWSING_KEY`、`URLHAUS_AUTH_KEY`、`SPAMHAUS_DBL`）、上传预留空间（`BIO_UPLOAD_MIN_FREE_MB`、`BIO_UPLOAD_MIN_FREE_PERCENT`），以及首页展示的官方页面（`HOP_OFFICIAL_BIO`、`HOP_OFFICIAL_VIDEO`、`HOP_OFFICIAL_MUSIC`、`HOP_OFFICIAL_CONTACT`）。运营者也可以在 Imperium 中随时修改最短地址、每个账号的页面数、每个页面的区块数和自定义 CSS 开关。

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### 保持运行

BioHop 需要一个队列 worker 和计划任务调度器。在生产环境中，用 systemd 或 supervisor 运行 `php artisan queue:work --tries=3` 和 `php artisan schedule:work`，或者用一行 cron：`* * * * * cd /path/to/BioHop && php artisan schedule:run`。安装程序的 `start.sh` 会在一台机器上同时启动 Web 服务器、worker 和调度器，用于试用。

调度器会运行这些命令：

| 命令 | 时间 | 作用 |
|---|---|---|
| `abuse:scan --since=2h` | 每小时 | 扫描有变动的页面和区块 |
| `abuse:scan --full --purge` | 每天 04:10 | 扫描全部内容，并删除因滥用被停用超过清理期限的内容 |
| `abuse:feeds` | 每天 02:15 | 刷新 `hop/safety` 的威胁情报源 |
| `privacy:prune` | 每天 03:30 | 删除超过 30 天的点击日志、超过保留期的表单条目，以及超过 7 天的 webhook 投递记录 |
| `bio:webhooks:retry` | 每分钟 | 发送到了重试时间的 webhook |

其他命令需要手动运行：`bio:official-page` 根据共享清单填充官方 HEL.ink 页面，`bio:optimize-images` 把旧上传转换为 WebP，`bio:unwrap-family-links` 还原被包成短链接的 HOP 站点链接。这三个命令除非加上 `--apply`，否则只做报告。

### 在 Cloudflare 之后：从边缘节点提供公开页面

公开的 bio 页面在每次请求时生成。只从一个地方提供时，离服务器近就快，远就慢：从源站测得页面在 36 到 175 毫秒内响应，而亚洲访客的请求要经过 Cloudflare 边缘节点到德国，需等待 0.3 到 3.2 秒。因此 BioHop 允许边缘节点把普通页面保留一分钟。没有密码、内容警告、表单和投票的已发布页面，由没有会话、未登录、也未选择语言的访客打开时，带着 `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` 和 `Vary: Cookie` 发送，且不设置 cookie。其他所有响应仍为 `no-cache, private`。访问由页面发出的一个小请求（`POST /b/{slug}/view`，不用 JavaScript 框架）按与点击相同的“每位访客一次”规则计数，因此来自边缘节点的页面也会被统计；由源站自己提供的页面则照旧当场计数。

只有在 Cache Rule 要求时 Cloudflare 才缓存 HTML。请为该区域创建一条：

- **条件**：主机名为 `hel.ink` 或 `bio.hel.ink`（你的 `PUBLIC_URL` 和 `APP_URL`），URI 路径以 `/b/` 开头，且 Cookie 头不包含 `biohop_session`（你的 `SESSION_COOKIE`）、`hop_lang` 或 `remember_web`。
- **动作**：cache eligibility 选 *Eligible for cache*；Edge TTL 选 *Use cache-control header if present, bypass cache if not*；Browser TTL 选 *Respect origin*。

只要有任何 cookie 就绕过缓存的规则同样正确，只是这样一来点过区块（`hel_vid`）或选过语言的访客也会绕过缓存。Cloudflare 不遵守 `Vary: Cookie`，所以把已登录者的页面或翻译后的页面与共享副本分开的，正是规则中的 cookie 条件。

要让改动立刻可见而不是一分钟后，请设置 `CLOUDFLARE_ZONE_ID` 和 `CLOUDFLARE_API_TOKEN`（对该区域拥有 *Zone → Cache Purge* 权限的 API 令牌）。保存页面、发布或取消发布、添加、修改或删除区块时，会把两个主机上该页面、其商店以及 QR、NFC 和已安装应用入口的按 URL 清除任务放入队列（`PurgeEdgeCache`，每次保存一个任务；需要队列 worker）。清除失败只会写入日志，保存从不等待 Cloudflare。两个键都为空时只发送响应头，改动在一分钟结束后可见。

### 数据库

BioHop 的设计是与枢纽和 LinkHop 共用一个数据库。每个应用有自己的迁移记录，这里是 `migrations_bio`。请先运行枢纽的迁移，让其他应用拥有的表（包括 `users`、`roles`、`api_tokens`、`ip_bans`、`domain_blacklist` 和 `abuse_flags`）存在。在 SQLite 上用 `--hub new` 安装时，BioHop 使用枢纽的数据库文件。这一设计有文档记录，但各应用版本之间的兼容性在这里没有检查，所以在把 BioHop 指向你在意的数据库之前，请先测试。

### 更新与卸载

`./update.sh` 先把数据库复制到 `backups/`（SQLite 用 `sqlite3 .backup`，MySQL 用 `mysqldump`；副本只有你能读取）。然后它让站点进入维护模式，拉取新代码，安装软件包，构建前端，执行迁移，并通知队列 worker 重启。如果某一步在迁移之前失败，它会恢复之前的代码。如果在迁移时或之后失败，它会让站点保持维护模式并打印手动步骤。`--no-backup` 跳过复制，`--force` 即使你改动了受跟踪的文件也会更新。`./uninstall.sh` 保留你的数据；`--purge` 会在你输入站点名称后，同时删除 `.env`、`storage/` 和 `backups/`。

### 测试

测试在内存 SQLite 上运行。其他应用拥有的表由 `database/migrations/testing/` 创建，而且只在运行测试套件时创建。CI 在 PHP 8.3 上运行 `composer audit`、Pint 和测试。

```bash
php artisan test
vendor/bin/pint --test
```

## 登录

在枢纽用 HOP ID 登录。打开 `/bio` 会把你送到枢纽再回来，这需要先设置好 `HOP_HUB_URL`、`HOP_APP=biohop` 和 SSO 密钥。退出登录会先结束这里的会话，再结束枢纽的会话。电子邮件验证和账号设置在枢纽处理。`/b/{slug}` 的公开 bio 页面不需要登录，但 BioHop 设计为与枢纽一起运行，不是独立应用。

<a id="privacy"></a>

## 隐私

以下是代码目前的实际行为。

- 点击日志保存时间、区块、国家、设备类型、浏览器、来源网站和访客的到达方式。它保存的是访客 IP 地址的带密钥哈希（用 `APP_KEY` 做 HMAC-SHA256，截短为 40 个字符），不保存地址本身。`privacy:prune` 会在 30 天后删除点击日志。投票和表单条目同样只保存地址的带密钥哈希。
- 为了确定国家，访客的地址会先在本地 GeoIP 数据库中查找（如果你安装了的话），否则会发送给第一个应答的网络地理定位服务（见 [`CREDITS.md`](CREDITS.md)）。这次查询使用的是哈希之前的原始地址。
- 为了让一次浏览每天只计一次，并限制每个地址的点击数，应用会把原始地址放在缓存键里，浏览最长保留 24 小时，点击保留 10 分钟。使用默认的数据库缓存时，这些键就是 `cache` 表中的行。
- 一个随机 cookie `hel_vid` 有效期一年，在点击时设置，只用于点击计数去重。首次访问普通公开页面的读者不会得到会话，也没有 cookie。已登录的成员有包含其地址的会话记录，这与任何 Laravel 数据库会话一样。
- 页面的隐私模式会停止该页面的浏览和点击计数。
- 举报会把举报人的电子邮件地址、理由和举报人的 IP 地址保存在共享的 `abuse_reports` 表中。本仓库不会清理这张表。
- BioHop 自己不添加任何广告或分析脚本。字体、图标和旗帜都由应用提供，所以访客不会连接 Google 或 CDN。
- 页面所有者可以在页面上添加 Google Analytics、Google Tag Manager、Facebook 像素或 TikTok 像素。添加与否由页面所有者决定，并由其负责；BioHop 只提供平台。它们只有在访客于页面上的提示条中表示同意后才会加载，访客的选择会记在该浏览器中。加载后，访客的浏览器会按这些服务自己的隐私政策向它们发送数据。BioHop 不会收到这些数据。
- 访客必须勾选同意框才能提交报名表单。条目为页面所有者存储，所有者设置保留期限，可以导出为 CSV，也可以删除。条目会随页面或账号一起删除。

<a id="faq"></a>

## 常见问题

**不用枢纽可以使用 BioHop 吗？** 不可以。编辑需要枢纽里的 HOP ID，没有本地注册或密码。公开页面不需要登录，但应用设计为与枢纽一起运行。

**我的点击数据会怎样？** 保留 30 天，然后删除。点击日志保存的是地址的带密钥哈希，而不是地址。详情见[隐私](#privacy)。

**安全扫描会自动运行吗？** 会，只要调度器在运行。它使用共享规则和共享黑名单。没有任何自动扫描能发现所有问题。

**可以导出我的页面吗？** 可以。`/settings/export` 以 JSON 返回你的页面和区块，不包含点击日志和表单条目；表单条目有自己的 CSV 下载。

**可以自行托管吗？** 可以，采用 MIT 许可证。你需要自备枢纽、想用短链接的话还需要 LinkHop，以及一个数据库。见[自行部署](#run-your-own)。

<a id="family"></a>

## 自行托管

<!-- self-hosting:start -->
BioHop 所需的一切都在公开仓库中：不需要私有仓库、令牌或 hel.ink 账户。详细说明见家族指南：[docs/self-hosting.md](https://github.com/navi-crwn/HEL.ink/blob/main/docs/self-hosting.md)（英文）。

| 组件 | 对 BioHop 而言 | 作用 |
| --- | --- | --- |
| [HEL.ink](https://github.com/navi-crwn/HEL.ink) (中心，HOP ID) | **必需**：账户保存在那里，使用同一个数据库 | 所有应用的账户与登录 |
| [hop/auth](https://github.com/navi-crwn/hop-auth), [hop/ui](https://github.com/navi-crwn/hop-ui), [hop/safety](https://github.com/navi-crwn/hop-safety) | 由 `composer install` 从公开的 GitHub 安装 | 共享 PHP 代码：登录、界面、链接安全 |
| [Imperium](https://github.com/navi-crwn/Imperium) | 可选，但 BioHop 的管理页面在那里 | 整个家族的管理面板；需要中心 |
| 家族字体与浮动图块 | 无需安装：从 `hel.ink/hop-assets` 加载 | 无法访问 hel.ink 时：使用系统字体，没有浮动图块 |

- **安装顺序**：先装[中心](https://github.com/navi-crwn/HEL.ink)，再装 BioHop。`./install.sh --hub new` 会同时安装两者。
- **最少设置**：`APP_URL`, `PUBLIC_URL`, `HOP_APP=biohop`, `HOP_HUB_URL`, `HOP_SSO_SECRET` (与中心相同), `DB_*` (中心的数据库), `HOP_TRUSTED_HOSTS` (你自己的主机名); 使用 Imperium 时: `HOP_CONTROL_URL`。
- **单独运行或加入家族**：BioHop 不能脱离中心运行。在家族中，它与中心共用数据库和 `HOP_SSO_SECRET`。
- **没有 Imperium 时**：`/admin` 只显示一条说明。账户可在中心自己的管理页面中管理，另可使用 签名的 `/api/hop/*` API 和 artisan 命令。
<!-- self-hosting:end -->

## HOP 家族

BioHop 属于 **HOP** 家族，这是 [hel.ink](https://hel.ink) 背后一组可自托管的开源应用。

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

## 安全

发现了漏洞？请通过 GitHub 安全公告私下报告到本仓库：[github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories)。请不要为安全问题开公开 issue，也不要在公开场合发布密钥、令牌或漏洞利用细节。详情见 [SECURITY.md](SECURITY.md)。

## 更新日志与致谢

变更记录在 [CHANGELOG.md](CHANGELOG.md)。BioHop 使用或附带的第三方软件、字体、图标和服务及其许可证，列在 [CREDITS.md](CREDITS.md)。

<a id="license"></a>

## 许可证

[MIT](LICENSE)，与 HOP 家族的其他应用相同。第三方许可证列在 [CREDITS.md](CREDITS.md)。

---

<p align="center"><sub>BioHop · HOP 家族的一员 · <a href="https://hel.ink">hel.ink</a></sub></p>
