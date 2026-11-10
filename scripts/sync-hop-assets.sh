#!/usr/bin/env bash
# Copy the shared homepage module from a reviewed HEL.ink checkout. The version is the one the checkout's manifest
# (public/hop-assets/apps.json) names, so this script never copies an older set than the one the hub serves.
set -euo pipefail
cd "$(dirname "$0")/.."
source_root="${1:?Pass the HEL.ink checkout path}"
version=$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\(v[0-9][0-9]*\)".*/\1/p' "$source_root/public/hop-assets/apps.json" | head -n 1)
[[ -n "$version" ]] || { echo "No version in $source_root/public/hop-assets/apps.json" >&2; exit 1; }
echo "Copying hop-assets $version"
mkdir -p public/hop-home/home
for file in hop-home.css hop-home.js hop-hero3d.js hop-motion.js hop-scenes.css hop-scenes.js home/biohop-light.webp home/biohop-dark.webp; do
  cp "$source_root/public/hop-assets/$version/$file" "public/hop-home/$file"
done
# hop-hero3d.js here also carries a guard (an app with no block map keeps its flat mark); re-apply it after a sync
# until the hub's copy has it: tests/Feature/*Home* check for it.
