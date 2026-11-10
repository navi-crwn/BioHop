#!/usr/bin/env bash
# Copy the shared homepage module from a reviewed HEL.ink checkout. The version is the one the checkout's manifest
# (public/hop-assets/apps.json) names, so this script never copies an older set than the one the hub serves.
set -euo pipefail
cd "$(dirname "$0")/.."
source_root="${1:?Pass the HEL.ink checkout path}"
manifest="$source_root/public/hop-assets/apps.json"
version=$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\(v[0-9][0-9]*\)".*/\1/p' "$manifest" | head -n 1)
[[ -n "$version" ]] || { echo "No version in $manifest" >&2; exit 1; }
src="$source_root/public/hop-assets/$version"
echo "Copying hop-assets $version"
mkdir -p public/hop-home/home
for file in hop-home.css hop-home.js hop-hero3d.js hop-motion.js hop-scenes.css hop-scenes.js hop-bento.css; do
  cp "$src/$file" "public/hop-home/$file"
done
# The apps come from the manifest (every app with "public": true), so a new family member needs no edit here.
apps="$(awk '/"slug":/ { gsub(/[",]/, "", $2); slug = $2 } /"public": *true/ { print slug }' "$manifest")"
[ -n "$apps" ] || { echo "No public apps found in $manifest" >&2; exit 1; }
for app in $apps; do
  for look in light dark; do
    cp "$src/home/$app-$look.webp" "public/hop-home/home/$app-$look.webp"
  done
done
# The app names in their own typefaces (hop-brand.css and the Unbounded licence it points to) and the fonts this app uses.
mkdir -p public/hop-brand/fonts/brand
cp "$src/hop-brand.css" public/hop-brand/hop-brand.css
cp "$src/fonts/LICENSE-unbounded.txt" public/hop-brand/fonts/LICENSE-unbounded.txt
for app in linkhop biohop payhop; do
  cp "$src/fonts/brand/$app-name.woff2" "public/hop-brand/fonts/brand/$app-name.woff2"
done
