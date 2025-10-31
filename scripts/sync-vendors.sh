#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
echo "→ Sync des vendors (FullCalendar)…"

dest_core="$ROOT/assets/vendor/fullcalendar"
dest_day="$ROOT/assets/vendor/fullcalendar-daygrid"
dest_time="$ROOT/assets/vendor/fullcalendar-timegrid"
mkdir -p "$dest_core" "$dest_day" "$dest_time"

copy() { local s="$1" d="$2" l="$3"; [ -f "$s" ] && { cp "$s" "$d"; echo "  ✓ $l → $(basename "$d")"; return 0; }; return 1; }

# JS uniquement (v6)
copy "$ROOT/node_modules/@fullcalendar/core/index.global.min.js" "$dest_core/index.global.min.js" "core v6 js" \
|| copy "$ROOT/node_modules/@fullcalendar/core/index.global.js"      "$dest_core/index.global.js"      "core v6 js (non min)"

copy "$ROOT/node_modules/@fullcalendar/daygrid/index.global.min.js" "$dest_day/index.global.min.js"   "daygrid v6 js" \
|| copy "$ROOT/node_modules/@fullcalendar/daygrid/index.global.js"   "$dest_day/index.global.js"       "daygrid v6 js (non min)"

copy "$ROOT/node_modules/@fullcalendar/timegrid/index.global.min.js" "$dest_time/index.global.min.js"  "timegrid v6 js" \
|| copy "$ROOT/node_modules/@fullcalendar/timegrid/index.global.js"  "$dest_time/index.global.js"      "timegrid v6 js (non min)"

echo "✓ Vendors synchronisés (CSS auto-injecté en v6)."
