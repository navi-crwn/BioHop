#!/usr/bin/env bash
# Copy the shared homepage module from a reviewed HEL.ink checkout.
set -euo pipefail
cd "$(dirname "$0")/.."
source_root="${1:?Pass the HEL.ink checkout path}"
mkdir -p public/hop-home/home
for file in hop-home.css hop-home.js hop-hero3d.js hop-motion.js hop-scenes.css hop-scenes.js home/biohop-light.webp home/biohop-dark.webp; do
  cp "$source_root/public/hop-assets/v3/$file" "public/hop-home/$file"
done
# hop-hero3d.js here also carries a guard (an app with no block map keeps its flat mark); re-apply it after a sync
# until the hub's copy has it: tests/Feature/*Home* check for it.
