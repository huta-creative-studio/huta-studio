#!/bin/bash
# deploy-pwa.sh — salin berkas PWA tema Huta Studio ke web root OpenSID.
#
# manifest.webmanifest & sw.js HARUS dilayani dari root web (/manifest.webmanifest, /sw.js),
# sedangkan mekanisme tema OpenSID hanya menyalin folder assets/ ke public/themes/desa-<slug>/.
# Karena itu berkas ini disimpan di public/ dalam repo tema sebagai sumber (vault) dan
# disalin ke web root oleh skrip ini.
#
# Jalankan setiap kali: (a) berkas PWA berubah, (b) reinstall OpenSID, (c) pindah server.
#
# Pakai: scripts/deploy-pwa.sh [/path/ke/web-root]
#   default web root: /var/www/html/opensid/public
set -euo pipefail

SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/public"
DEST="${1:-/var/www/html/opensid/public}"

if [ ! -f "$SRC/manifest.webmanifest" ] || [ ! -f "$SRC/sw.js" ]; then
  echo "ERROR: sumber tidak lengkap di $SRC" >&2
  exit 1
fi

if [ ! -d "$DEST" ]; then
  echo "ERROR: web root tidak ditemukan: $DEST" >&2
  exit 1
fi

echo "deploy-pwa: $SRC -> $DEST"
install -m 644 "$SRC/manifest.webmanifest" "$DEST/manifest.webmanifest"
install -m 644 "$SRC/sw.js"               "$DEST/sw.js"

# selaraskan kepemilikan dengan web server bila dijalankan sebagai root
if [ "$(id -u)" -eq 0 ]; then
  chown www-data:www-data "$DEST/manifest.webmanifest" "$DEST/sw.js" 2>/dev/null || true
fi

# bersihkan cache halaman nginx bila ada (mencegah berkas lama/stale disajikan).
# Tanpa ini, fastcgi_cache bisa menyajikan salinan lama walau berkas sudah dihapus/diganti.
if [ "$(id -u)" -eq 0 ] && command -v nginx >/dev/null 2>&1; then
  if [ -d /var/cache/nginx/opensid ]; then
    find /var/cache/nginx/opensid -type f -delete 2>/dev/null || true
    echo "deploy-pwa: cache nginx dibersihkan"
  fi
fi

# reload php-fpm agar opcache tidak menyajikan versi lama (opsional, aman bila ada)
if [ "$(id -u)" -eq 0 ] && command -v systemctl >/dev/null 2>&1; then
  systemctl reload php8.4-fpm 2>/dev/null || true
fi

echo "deploy-pwa: OK"
echo "  $DEST/manifest.webmanifest ($(wc -c < "$DEST/manifest.webmanifest") byte)"
echo "  $DEST/sw.js ($(wc -c < "$DEST/sw.js") byte)"
echo "  pastikan browser di-refresh (SW lama bisa menyajikan salinan) jika berkas berubah."
