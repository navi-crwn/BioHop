# Changelog

All notable changes to BioHop are listed here, written for the people who use it. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) with one addition: every release has a short title, and every item starts with the page or part of the app it concerns in bold. The /changelog page of the app shows these releases; it is built from this file by scripts/build-changelog.py.

## [Unreleased]

## [1.5.0] - 2026-11-15 - Manage prices and goals without the editor

### Added

- **Manage page.** A new "Manage" entry in the menu opens one page with the products and goal bars of all your bio pages. Change the price, the old price, the stock, sold out, shown or hidden of a product, or the "so far", goal and unit of a goal bar, and save each row without opening the editor. Polls (votes per choice) and forms (number of entries, CSV download) are listed there to read. Effect: a quick price or stock change takes seconds, and the public page follows at once. If the same block was changed in the editor in the meantime, the row says so and saves nothing. Reason: opening the editor to change one number was slow.
- **Dashboard, announcement card.** The card at the top of the dashboard home now shows news from HOP, such as planned maintenance. One card at a time, it can be hidden for a week, and an urgent one stays until it is over. If the news cannot be reached, the dashboard shows no card and opens as fast as before. Reason: one place for news to all HOP apps.
- **Editor, Choose from PixelHop.** The "Choose from PixelHop" button now also appears for the page background, the share picture, a product picture and a block icon (where your PixelHop pictures are switched on). Effect: no need to download a picture and upload it again. Reason: it only worked for some places.
- **Editor, Quick Add image box.** A picture dropped on the image box is uploaded, as the box always said. Reason: the box promised drag and drop, but only clicking worked.
- **Dashboard, colours.** The bottom of the sidebar has three colour samples, Classic, Mist and Sand. Mist and Sand change the accent colour and the soft background and panel colours of your dashboard, and the choice is remembered and shared with the other HOP apps. Classic is the look you already know and does not change. Your public bio pages are never recoloured. Reason: one colour choice for the whole HOP family.
- **Dashboard, sidebar footer.** The footer links are Help, Terms, Privacy, Changelog, GitHub and Announcement, one under the other, followed by "Part of Hop Easy Link". On a phone they scroll together with the menu. Reason: every HOP app now has the same footer.

### Changed

- **Editor, product form.** The labels and hints of the product form are translated in all nine languages. Reason: they were English only.

### Removed

- **Editor, WhatsApp rotator.** The rotator block is gone, together with its notice on the dashboard. A page that still has an old one no longer shows it to visitors, and its owner can delete it from the block list. Reason: nobody uses it.

## [1.4.0] - 2026-11-14 - A calmer editor and dashboard

### Added

- **Editor, adding a block.** A "Back" button at the top of the new-block pop-up (also in the Text and Image pop-ups) returns to the list of block kinds. Effect: a wrong pick no longer means closing the pop-up and opening "Add block" again. Asked for after using the editor.
- **Dashboard, notice.** A card at the top of the dashboard tells members who still have a WhatsApp rotator block that the block is being retired. It can be hidden for a week, and it names the end date only once one is decided. Reason: owners need to know before the block goes away.
- **Editor, Settings tab.** The nine cards are grouped into four sections (Search & sharing, Privacy, Tools, Versions), and the search-engine preview sits next to the title and description on wide screens. A refused setting opens the section it belongs to. Reason: one long list of cards was hard to scan.

### Changed

- **Editor, block pop-up.** The pop-up is split into short sections (Content, Look, Schedule, Advanced). A section with nothing in it for that kind of block is not shown, and a divider or spacer has none. If saving is refused, the section that needs attention opens. Reason: one long form was hard to read, especially for links.
- **Editor, block picker.** There is one "Music" block for Spotify, SoundCloud, Apple Music and YouTube Music links, instead of separate Spotify and SoundCloud blocks, and the app store link, RSS feed and poll blocks are no longer offered. Pages that already have these blocks keep them and can still edit them. Reason: the picker was long and several kinds overlapped.
- **Editor, WhatsApp rotator.** No new rotator block can be made; blocks already on a page keep working and can be edited. Use a normal link block or a LinkHop link instead.
- **Editor, Socials tab.** The position choice is one row of two buttons, and each social icon is one short row. Effect: the list is shorter and easier to arrange.
- **Editor, Design tab.** A panel with little in it keeps a readable width on wide screens instead of stretching into an empty card, and the Footer section is gone (see the public pages item below).
- **Editor, Profile tab.** The picture shape choice is hidden, with a short note, for the Hero and Cutout headers, which do not use it. Your choice is kept if you switch back.
- **Dashboard, sidebar.** "Part of Hop Easy Link" moved to the bottom left of the sidebar. Reason: under the page content it landed in an odd place on long pages such as the editor.
- **Public pages for members.** For a signed-in member, the Back links on the information pages and the report page, the BioHop logo and the Dashboard button now lead to the BioHop dashboard instead of the home page or the HOP ID dashboard. Visitors who are not signed in still go to the home page.
- **Public bio pages.** The "Powered by BioHop" line is always shown, and the editor preview says the same. The switch for hiding it is gone, so pages that had hidden it show it again.

### Fixed

- **Editor, dragging blocks in two tabs.** When the page was changed in another tab, saving the new block order is now refused with a clear message in your language, instead of quietly replacing the other tab's changes. The editor now sends the page version it knows, which the server has been able to check since version 1.3.2.
- **Public pages, language list.** The language list in the header no longer runs off the right edge of a phone screen.

### Removed

- **Editor, link block.** The "Choose from NoteHop" button is gone, so a NoteHop note can no longer be connected to a page from the editor. Effect: fewer buttons in the link form; to write on a page, use the Text block. Links to notes that were added earlier keep working and can still be edited. Reason: the Text block already covers it and the button was rarely needed.

## [1.3.2] - 2026-11-14 - Safer saving of the block order

### Fixed

- **Editor, saving the block order.** Saving the order of your blocks is stricter on the server. It answers the same for a block that belongs to someone else and for one that does not exist, so block numbers of other pages cannot be guessed; it refuses a list that is too long, repeats a block, or carries a position outside the page's block limit; it saves the whole new order in one step or not at all; and it is rate-limited like the other editor actions. Nothing the editor sends is refused by this.
- **Block order everywhere.** Blocks that share the same position are always listed in the same order, in the editor, on the public page and in the API. Reason: before, the database could choose a different order each time.
- **Editor, two tabs.** When a request to save the order carries the page version it was made from, it is refused with "changed in another tab" if the page changed meanwhile, like saving the other fields. The editor sends it from version 1.4.0 on.
- **Editor, social icons.** An empty entry in the social icons list is refused on save and ignored when the page is read, so older data with an empty entry can no longer stop the editor from opening.

## [1.3.1] - 2026-11-12 - Dragging blocks works

### Fixed

- **Editor, dragging blocks.** Dragging a block now moves the block you drag. Before, a drag could move another block, or add an empty "Untitled" block after which no drag and no save worked until a reload. The new order is read from the blocks themselves after the drop, an error can no longer freeze dragging, the dragged block looks like a block, and a drag made while the order is being saved is saved too. The social icons list works the same way. Orders scrambled earlier are not repaired automatically; Settings > Versions can bring back an earlier one.
- **Editor, new blocks.** A block that was just added can be dragged at once, and the preview follows a drag. A move the list cannot apply refreshes the list and says so instead of staying silent; pressing Restore twice restores once; Archive removes the block you archived even if you dragged another one meanwhile.
- **Public pages with a warning or password.** A page with an age or content warning, or a password, opens after one confirmation and stays on the address the visitor came by. Before, the first confirmation was lost and the visitor ended up on the app's own address. The source of a QR or NFC visit is kept.
- **Editor, QR logo.** The QR logo in the Profile tab uploads (it always failed before), and the next save after it is no longer refused as "changed in another tab".
- **Editor, picture uploads.** Uploads of the cover, background, share picture, favicon and images say why a file was refused instead of "Failed to upload".
- **Editor, search fields.** Text typed in "Select existing link", the Add block search and the page password field no longer runs over the search icon.
- **Public pages, hero header.** The hero header fills the width of the page and of the editor preview instead of sitting to the left.

### Changed

- **Editor, upload buttons.** Upload buttons offer the file types they take: SVG only for custom block icons and the QR logo. Choosing an SVG somewhere else says so at once, in nine languages.
- **Theme and language.** The theme and the language are now shared with the other HOP apps, so a choice made in one app is the choice in the others. Public bio pages keep their own theme.
- **Search engines.** The home and information pages name one address per language, list the nine languages, share a picture preview, and the home page describes BioHop as a web application. Signed-in screens ask search engines to stay away. Reason: so each language of a page is found in search.

## [1.3.0] - 2026-11-12 - Receive payments, open apps

### Added

- **Editor and public pages, "Receive payment" block.** The page owner picks a payment method (PayPal.me, Cash App, Venmo, Solana Pay, UPI, EPC SEPA, PromptPay or VietQR), fills in its fields, and can set a fixed amount, let visitors type one, and add a note. The public page shows the payee, method, details and amount as text and draws the QR code in the visitor's browser; nothing is sent anywhere. Reason: owners asked for a simple way to get paid.
- **Public pages, app blocks.** On an iPhone, iPad or Android phone, a block that points to a well-known app (YouTube, WhatsApp, Telegram, TikTok and others) opens the app. On an iPhone without the app, the visitor is taken to the web address after a moment. Reason: links to apps used to open in the browser.

### Changed

- **Editor, payment block.** A payment block now refuses account details that do not have the right shape for their method, also when the request does not come from the editor. Saving other settings of a payment block keeps its stored payment details.

## [1.2.0] - 2026-11-11 - PayHop joins, safer contact files

### Added

- **Whole app, PayHop.** PayHop (pay.hel.ink) joins the family and is named in the FAQ, the home page and the official page in nine languages. "payhop" and "pay" cannot be used as page addresses.
- **Whole app, account check.** When you switch accounts at HOP ID, the old account no longer stays signed in here: the home page, the information pages and every signed-in page confirm with HOP ID every few minutes.
- **Changelog page.** The page can show a history built when the app is updated. Shared links to the information pages now show a preview picture and title.

### Changed

- **Home page.** The boxes are laid out by the shared family grid, the "Part of the HOP family" section and the BioHop logo (a small pixel page with three lines) are updated, and the family tiles sit in one calm row on wide screens.
- **Footer and sign-out.** Every app shows "Part of Hop Easy Link" as the first row of its footer, the footer no longer has a language row (the language menu in the top bar is the one place), and signing out brings you back to BioHop through HOP ID.

### Fixed

- **Public pages, contact card.** The "Save contact" file can no longer be tampered with through a name, number, e-mail or company containing special characters. Reason: before, a line break in a name could add extra fields to the file.
- **Editor, WhatsApp rotator form.** The form is translated in all nine languages.
- **Page addresses.** "filehop" cannot be used as a page address, like the names of the other HOP apps.
- **Changelog page.** The page lists every release again; it had been left out of date.

## [1.1.0] - 2026-11-10 - FileHop joins, link picker fixed

### Added

- **Whole app, FileHop.** FileHop (file.hel.ink) is named wherever BioHop lists its sibling apps, in all nine languages, and "filehop" cannot be used as a page address.
- **Editor, "Choose from NoteHop".** A link block can point to one of your public notes (off by default). Reason: owners wanted to share a note on their page.
- **Editor, "Choose from PixelHop".** A picture already uploaded to PixelHop can be picked for the image block, the carousel, the avatar, the cover and the favicon (off by default).
- **Public bio pages, speed.** Plain public pages can be served from the edge of the network to be faster, only for pages without a password, warning, form or poll, and only for visitors without a session.

### Security

- **Behind the scenes, picture and note pickers.** The calls to PixelHop and NoteHop can be signed with a secret of their own, so a leak on one of those servers can no longer be used to sign in here.
- **Links and warnings.** Links to a known phishing kit are recognised and refused, and the shared list of blocked domains is updated.
- **Encrypted note links.** Links to encrypted NoteHop notes are refused everywhere a page or block is saved, not only in address fields.

### Fixed

- **Editor, "Select existing link".** The list now shows your short links; it was always empty, and each search left one more access token behind. When LinkHop cannot be reached, it says so instead of "No links found".
- **Editor, translations.** The link picker and the link library are translated into the nine languages, their buttons work with the keyboard, and the spinner stands still with reduced motion.
- **Pages list, drafts.** A draft no longer links to its public address or QR code, which showed an error page.
- **Uploads.** Uploading a picture that is too large now says so instead of failing with an error.

### Changed

- **Dashboard.** Inputs, selects and buttons share one radius and one height, and controls are 44 pixels tall on touch screens and narrow windows.

## [1.0.0] - 2026-11-04 - BioHop becomes its own app

### Added

- **Whole app.** BioHop, the link-in-bio app of the HOP family, is split out of LinkHop: public pages at hel.ink/b/your-name, an editor at bio.hel.ink, themes, uploads and analytics. People sign in with HOP ID; there are no separate accounts.
- **Editor, blocks.** Many block kinds: links, shop products with variants and stock, collections, sign-up forms with entries you can download, polls, feeds, embeds, audio, PDF, picture carousel, status chip and floating button. Blocks can be scheduled, given a PIN, archived, and sent elsewhere by country, device or time of day.
- **Editor, new page.** "New bio page" is a short guided setup with a live phone, and a public Linktree page can be imported as a draft.
- **Editor, saving.** The editor saves by itself about a second after you stop typing and keeps versions you can restore.
- **Dashboard.** A greeting, clicks this week against last week, a setup checklist, analytics by page, link and source (QR code, NFC tag, installed web app), and a command palette (Ctrl/Cmd+K).
- **Public pages.** Content warnings (sensitive and adult), four header layouts, themes, wallpaper and a share picture. Privacy by default: no outside fonts or scripts, visitor addresses kept only as a coded value for 30 days, and trackers only after consent.
- **Languages and help.** The interface, the READMEs and the documentation site are in nine languages: English, Chinese, German, Indonesian, Japanese, Brazilian Portuguese, Spanish, French and Russian.
- **Hosting.** A one-command installer, updater and uninstaller for self-hosting.

### Changed

- **Editor, layout.** The editor is one page that scrolls normally with the preview beside it; on phones the preview opens as a full-screen sheet. Reason: the old fixed-height box was cramped.
- **Speed.** Public pages load their own small files, pictures are shrunk when uploaded, and a first-time reader of a plain page gets no session and no cookies.
- **Admin.** The admin pages moved to the HOP control panel (Imperium).

### Fixed

- **Public pages, files.** Pictures, icons, audio, PDFs and slides on a public page load again; they live on the app address while the page is on hel.ink.
- **Editor, uploads.** Uploaded background pictures, SVG icons, QR logos and thumbnails now save, and a block's custom icon is kept.
- **Editor, SEO.** Saving the search section no longer fails when an older, longer title is stored.
- **Editor, preview.** The preview scrolls on every browser and on touch.
- **Links.** A link to hel.ink or another HOP app is kept as typed instead of being wrapped in a short link.

### Security

- **Signing out.** Signing out at HOP ID now ends the session here too, even when BioHop was down at that moment, and a deleted account has its sessions and files removed.
- **Links.** Links to encrypted NoteHop notes are refused, and a suspended member is sent to the notice at HOP ID.
- **Feeds and webhooks.** The feed reader and the webhook sender connect only to addresses that were checked.

### Removed

- **Outside hosts.** Fonts, icons and scripts are no longer loaded from outside hosts; everything ships with the app.
