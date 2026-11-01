#!/usr/bin/env python3
"""Download the fonts the bio editor offers as Latin-subset woff2 files into public/fonts/bio/.

    python3 scripts/build-bio-fonts.py

Why: the public page used to ask Google Fonts on every visit (the visitor's address goes to Google, and text flashes while the
font loads). Now each page inlines a ~1 KB @font-face block and preloads one woff2 from our own domain.
Fonts are SIL Open Font License; the files are committed. Only the Latin subset is kept (English, Indonesian, Spanish, French,
Portuguese, German...). Pages in other scripts fall back to the system font for those glyphs.
Writes: <slug>-<n>.woff2, <slug>.css (font-face rules with relative urls) and manifest.json {slug: {css, preload}}.
"""
import hashlib, json, pathlib, re, urllib.parse, urllib.request

OUT = pathlib.Path(__file__).resolve().parent.parent / "public" / "fonts" / "bio"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
FAMILIES = {  # family -> weights (the same list the public page used to request)
    "DM Sans": "400;500;700", "Inter": "400;500;600;700", "Lato": "400;700", "Lexend": "400;500;600;700", "Manrope": "400;500;600;700",
    "Merriweather": "400;700", "Montserrat": "400;500;600;700", "Nunito": "400;500;600;700", "Open Sans": "400;500;600;700",
    "Oswald": "400;500;600;700", "Playfair Display": "400;500;600;700", "Plus Jakarta Sans": "400;500;600;700", "Poppins": "400;500;600;700",
    "Quicksand": "400;500;600;700", "Raleway": "400;500;600;700", "Roboto": "400;500;700", "Source Sans 3": "400;600;700",
    "Space Grotesk": "400;500;600;700", "Ubuntu": "400;500;700", "Work Sans": "400;500;600;700",
}

def get(url):
    with urllib.request.urlopen(urllib.request.Request(url, headers={"User-Agent": UA}), timeout=30) as r:
        return r.read()

def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for old in list(OUT.glob("*.woff2")) + list(OUT.glob("*.css")) + list(OUT.glob("manifest.json")):  # only this script's own output
        old.unlink()
    manifest, files = {}, {}
    for family, weights in FAMILIES.items():
        slug = family.lower().replace(" ", "-")
        label = family
        if family == "Source Sans 3":  # the editor and old pages call it Source Sans Pro
            slug, label = "source-sans-pro", "Source Sans Pro"
        css = get("https://fonts.googleapis.com/css2?family=%s:wght@%s&display=swap" % (urllib.parse.quote_plus(family), weights)).decode()
        rules, preload = [], None
        for block in re.split(r"(?=/\* [a-z-]+ \*/)", css):
            m = re.match(r"/\* ([a-z-]+) \*/", block)
            if not m or m.group(1) != "latin":
                continue
            url = re.search(r"url\((https://[^)]+\.woff2)\)", block).group(1)
            name = "%s-%s.woff2" % (slug, hashlib.sha1(url.encode()).hexdigest()[:8])
            if name not in files:
                (OUT / name).write_bytes(get(url))
                files[name] = url
            block = block.replace(url, name).replace("/* latin */", "").replace("'%s'" % family, "'%s'" % label).strip()
            rules.append(block)
            if preload is None and "font-weight: 400" in block:
                preload = name
        (OUT / (slug + ".css")).write_text("\n".join(rules) + "\n")
        manifest[slug] = {"family": label, "css": slug + ".css", "preload": preload}
        print(slug, len(rules), "rules")
    # every face in one file, for the editor's live preview (faces only download when the page uses them)
    (OUT / "all.css").write_text("\n".join((OUT / m["css"]).read_text() for m in manifest.values()))
    (OUT / "manifest.json").write_text(json.dumps(manifest, indent=1) + "\n")
    total = sum(p.stat().st_size for p in OUT.glob("*.woff2"))
    print("woff2 files:", len(list(OUT.glob("*.woff2"))), "total KB:", round(total / 1024))

main()
