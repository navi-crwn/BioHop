<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh.md">简体中文</a> · <a href="README.es.md">Español</a> · <b>العربية</b> · <a href="README.pt.md">Português</a> · <a href="README.fr.md">Français</a> · <a href="README.ja.md">日本語</a> · <a href="README.id.md">Bahasa Indonesia</a></p>

<div dir="rtl">

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="رخصة MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="سير عمل الاختبارات"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>صفحة واحدة تجمع روابطك والمحتوى المضمّن وحساباتك الاجتماعية ورمز QR — تُنشر على <code>hel.ink/b/{slug}</code>.</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#شغّلها-بنفسك">شغّلها بنفسك</a> ·
  <a href="#المزايا">المزايا</a> ·
  <a href="#الأسئلة-الشائعة">الأسئلة الشائعة</a> ·
  <a href="#الأمن">الأمن</a> ·
  <a href="#الترخيص">الترخيص</a>
</p>

---

## ما هو BioHop؟

BioHop تطبيق صفحات روابط (link-in-bio): تبني صفحة واحدة تضم روابطك وصورك والمحتوى المضمّن وحساباتك الاجتماعية، وتختار نسقة، ثم تنشرها على `hel.ink/b/{slug}` — مع رمز QR جاهز للمشاركة.

هو النصف الخاص بالصفحات الشخصية من تطبيق كان واحدًا في السابق (أصبح الآن [LinkHop](https://github.com/navi-crwn/LinkHop)، مُختصر الروابط). الحسابات تُدار في المركز (hub، [HEL.ink](https://github.com/navi-crwn/HEL.ink)): لا تسجيل ولا كلمات مرور هنا — تسجّل دخولك مرة واحدة بمعرّف **HOP ID** ويتعرفك التطبيق. خطة هذا الانفصال موثّقة في [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (المرحلة D هي هذا المستودع).

<a id="المزايا"></a>

## المزايا

- **صفحات bio عامة على `hel.ink/b/{slug}`، لكل منها رمز QR وتتبّع للنقرات.**
- **محرّر على `/bio` لإدارة الصفحات والروابط ورفع الصور/الصورة الرمزية/الأيقونات/شعار QR، مع معاينة مباشرة ونسخ الصفحات وتحليلات لكل صفحة.**
- **نسقات جاهزة (light وdark وminimal وneon وغيرها الكثير) مشتركة بين المحرّر ومعاينته والصفحة العامة.**
- **لا تسجيل محلي ولا كلمات مرور: تسجّل دخولك مرة واحدة بمعرّف HOP ID في المركز.**
- **تحليلات النقرات مع البلد (GeoIP) إضافة إلى كشف الجهاز والمتصفح والروبوتات.**
- **"Shorten this URL" داخل العناصر عبر واجهة LinkHop البرمجية برمز الخدمة الخاص بالعضو نفسه؛ وإذا تعطّل LinkHop احتفظ العنصر برابطه الأصلي.**
- **سلامة الروابط والمحتوى: أمر `abuse:scan` مع قائمة `domain_blacklist` المشتركة (hop/safety)، مجدول كل ساعة.**
- **حذف بيانات النقرات يوميًا (`privacy:prune`).**

<a id="شغّلها-بنفسك"></a>

## شغّلها بنفسك

تحتاج PHP 8.3 وComposer وNode 20+. الحزم المشتركة `hop/auth` و`hop/ui` و`hop/safety` تأتي من مستودعاتها على GitHub (`navi-crwn/hop-auth` و`hop-ui` و`hop-safety`، الفرع `main`، مدرجة كمستودعات `vcs` في `composer.json`).

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**الإعداد قبل الترحيل.** افتح `.env` (الملف `.env.example` يوثّق جميع المفاتيح؛ تعليقاته هي المرجع) واضبط على الأقل:

| المفتاح | وظيفته |
|---|---|
| `APP_URL` / `PUBLIC_URL` | عنوان هذا التطبيق والعنوان العام الذي تُقدَّم منه الصفحات |
| `HOP_HUB_URL` | عنوان مركزك (تسجيل الدخول بمعرّف HOP ID ينتقل إليه) |
| `HOP_APP` | معرّف هذا التطبيق في المركز: `biohop` |
| `HOP_SSO_SECRET` أو `HOP_SSO_SECRET_BIOHOP` | سر SSO المشترك مع المركز، 32 حرفًا أو أكثر |
| `LINKHOP_URL` | عنوان LinkHop الخاص بك (يُفعّل "shorten this URL" داخل العناصر) |
| `DB_CONNECTION` و`DB_*` و`DB_MIGRATIONS_TABLE` | إعدادات قاعدة البيانات؛ `DB_MIGRATIONS_TABLE=migrations_bio` يُبقي سجل ترحيلات هذا التطبيق منفصلًا |

```bash
php artisan migrate      # يسجّل في migrations_bio فقط
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**قاعدة البيانات.** صُمّم BioHop ليشارك قاعدة بيانات MySQL واحدة مع المركز وLinkHop (لكل تطبيق سجل ترحيلاته الخاصة: `migrations_bio` هنا). شغّل ترحيلات المركز أولًا حتى توجد جداول مثل `users` و`roles` و`api_tokens` و`domain_blacklist`. هذا تصميم موثّق؛ وتوافق الترحيلات بين إصدارات التطبيقات غير مُتحقق منه في هذا الملف، فتأكد منه قبل توجيه BioHop إلى قاعدة بيانات تهمّك.

**الاختبارات** تعمل على sqlite في الذاكرة؛ والجداول المملوكة للتطبيقات الأخرى ينشئها المجلد `database/migrations/testing/` (للاختبارات فقط).

```bash
php artisan test
vendor/bin/pint --test
```

## تسجيل الدخول (HOP ID)

سجّل دخولك بمعرّف HOP ID في المركز — لا تسجيل ولا كلمات مرور هنا. يستخدم تسجيل الدخول والمحرّر SSO القياسي، الذي يتطلب ضبط `HOP_HUB_URL` و`HOP_APP=biohop` وسر SSO المذكور أعلاه قبل تسجيل الدخول. فتح `/bio` ينقلك إلى المركز ثم يعيدك. تسجيل الخروج ينهي جلستك هنا ثم يعيد توجيهك إلى صفحة خروج HOP ID.

صفحات bio العامة (`/b/{slug}`) لا تتطلب تسجيل دخول. صُمّم BioHop ليعمل جنبًا إلى جنب مع المركز — وليس تطبيقًا مستقلًا.

<a id="الأسئلة-الشائعة"></a>

## الأسئلة الشائعة

**ماذا يحدث لبيانات نقراتي؟** تتبّع النقرات موجود ليُريك أداء صفحتك، ويحذف أمر `privacy:prune` بيانات النقرات يوميًا. لا يُحتفظ عمدًا بأي أرشيف طويل الأمد للنقرات.

**هل يعمل الفحص الأمني تلقائيًا؟** نعم. أمر `abuse:scan` مجدول كل ساعة ويفحص صفحات bio وعناصرها مقابل قائمة `domain_blacklist` المشتركة من hop/safety. هذه آليات لا ضمانات: لا يعدك أي فحص آلي بكشف كل شيء.

**هل يمكنني استخدام BioHop دون المركز؟** لا. التعديل يتطلب معرّف HOP ID في المركز، ولا يوجد تسجيل محلي أو كلمات مرور. صفحات bio العامة لا تتطلب تسجيل دخول، لكن التطبيق مصمّم ليعمل مع المركز، ولا يمكن لـ BioHop ضمان أن المسارات العامة لن تتصل بالمركز أبدًا.

**هل يمكنني استضافته بنفسي؟** نعم — مرخّص برخصة MIT، والقسم أعلاه هو دليل البدء السريع كاملًا. أنت توفّر المركز وLinkHop وقاعدة البيانات بنفسك.

<a id="العائلة"></a>

## عائلة HOP

BioHop جزء من عائلة **HOP**، تطبيقات مفتوحة المصدر قابلة للاستضافة الذاتية خلف [hel.ink](https://hel.ink).

| التطبيق | ماذا يفعل | العنوان | الكود |
|---|---|---|---|
| **Hub** | بوابة الحسابات، دخول HOP ID | `hel.ink` · `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) |
| **LinkHop** | اختصار الروابط | `link.hel.ink` · `hel.ink/l/{slug}` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) |
| **BioHop** | صفحات روابط شخصية | `bio.hel.ink` · `hel.ink/b/{slug}` | هذا المستودع |
| **PixelHop** | استضافة الصور والوسائط | `pixel.hel.ink` · `p.hel.ink` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) |
| **NoteHop** | ملاحظات ومستندات خاصة | `note.hel.ink` · `n.hel.ink` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) |

<a id="الأمن"></a>

## الأمن

هل عثرت على ثغرة؟ أبلغ عنها بسرّية عبر صفحة التنبيهات الأمنية في GitHub لهذا المستودع: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). رجاءً لا تفتح issue عامة للإبلاغات الأمنية، ولا تنشر أسرارًا أو رموزًا أو تفاصيل استغلال في أماكن عامة. راجع [SECURITY.md](SECURITY.md) للتفاصيل.

<a id="الترخيص"></a>

## الترخيص

[MIT](LICENSE) — مثل بقية عائلة HOP. تراخيص الأطراف الخارجية مذكورة في ملف [LICENSE](LICENSE).

---

<p align="center"><sub>BioHop · جزء من عائلة HOP · <a href="https://hel.ink">hel.ink</a></sub></p>

</div>
