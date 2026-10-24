#!/bin/bash
# sync-aset.sh — salin aset tema Huta Studio ke folder publik yang benar-benar dilayani web.
#
# MEKANISME OPENSID (penting):
#   Berkas di desa/themes/huta-studio/assets/ TIDAK dilayani browser secara langsung.
#   Saat tema dipasang, OpenSID menyalin folder assets/ ke
#   public/themes/desa-<slug>/. Salinan itu BUKAN symlink — jadi mengubah CSS/JS
#   di folder sumber tidak akan sampai ke pengunjung sampai disalin ulang.
#   Gejalanya: "perubahan tidak terlihat", padahal HTML sudah berubah.
#
# Pakai:
#   scripts/sync-aset.sh            # salin lalu verifikasi (md5)
#   scripts/sync-aset.sh --check    # hanya laporkan perbedaan, tidak menulis apa pun
#
# Variabel lingkungan yang bisa dipakai:
#   OPENSiD_APP  (default /var/www/html/opensid)
#   THEME_SRC    (default $OPENSiD_APP/desa/themes/huta-studio)
#   THEME_SLUG   (default desa-huta-studio)

set -euo pipefail

APP="${OPENSiD_APP:-/var/www/html/opensid}"
THEME_SRC="${THEME_SRC:-$APP/desa/themes/huta-studio}"
THEME_SLUG="${THEME_SLUG:-desa-huta-studio}"
THEME_PUB="$APP/public/themes/$THEME_SLUG"
MODE="${1:-sync}"

if [ ! -d "$THEME_SRC/assets" ]; then
  echo "ERROR: folder sumber tidak ditemukan: $THEME_SRC/assets" >&2
  exit 1
fi

if [ "$MODE" != "sync" ] && [ "$MODE" != "--check" ]; then
  echo "Pakai: $0 [sync|--check]" >&2
  exit 2
fi

# Folder yang ikut disalin. `vendor` berisi pustaka pihak ketiga (mis. chart.js).
SUBFOLDERS="css js images vendor"

list_files() {
  # Daftar berkas relatif (tanpa ./ ) di dalam satu subfolder.
  local sub="$1"
  [ -d "$THEME_SRC/assets/$sub" ] || return 0
  ( cd "$THEME_SRC/assets/$sub" && find . -type f | sed 's|^\./||' | sort )
}

total_src=0
total_diff=0
missing_pub=0

echo "Sumber : $THEME_SRC/assets"
echo "Tujuan : $THEME_PUB"
echo "Mode   : $MODE"
echo

for sub in $SUBFOLDERS; do
  while IFS= read -r rel; do
    [ -n "$rel" ] || continue
    total_src=$((total_src + 1))
    src="$THEME_SRC/assets/$sub/$rel"
    pub="$THEME_PUB/$sub/$rel"

    if [ ! -f "$pub" ]; then
      missing_pub=$((missing_pub + 1))
      echo "  HILANG  : $sub/$rel"
      if [ "$MODE" = "sync" ]; then
        mkdir -p "$(dirname "$pub")"
        cp -p "$src" "$pub"
        echo "            -> disalin"
      fi
      continue
    fi

    if ! cmp -s "$src" "$pub"; then
      total_diff=$((total_diff + 1))
      echo "  BEDA    : $sub/$rel"
      if [ "$MODE" = "sync" ]; then
        cp -p "$src" "$pub"
        echo "            -> diperbarui"
      fi
    fi
  done <<< "$(list_files "$sub")"
done

echo
if [ "$MODE" = "sync" ]; then
  # Kepemilikan berkas publik harus tetap www-data agar bisa dibaca web server.
  if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data "$THEME_PUB" 2>/dev/null || true
  fi
  # Berkas penjaga index.html mencegah listing direktori.
  for sub in $SUBFOLDERS; do
    [ -d "$THEME_PUB/$sub" ] && [ ! -f "$THEME_PUB/$sub/index.html" ] \
      && printf '%s' '<!doctype html><title></title>' > "$THEME_PUB/$sub/index.html" || true
  done
fi

echo "Ringkasan: $total_src berkas diperiksa, $total_diff beda, $missing_pub belum ada di tujuan."
if [ "$MODE" = "sync" ]; then
  echo "Selesai. md5 berkas penting:"
  for f in css/huta-v2.css css/huta.css js/huta.js; do
    if [ -f "$THEME_PUB/$f" ]; then
      printf "  %-24s %s\n" "$f" "$(md5sum "$THEME_PUB/$f" | cut -d' ' -f1)"
    fi
  done
  echo
  echo "Ingat: aset dilayani dengan cache 30 hari, tetapi URL aset memakai ?themeVersion="
  echo "yang mengikuti versi tema. Setelah menyalin aset, naikkan versi tema bila"
  echo "perubahan menyentuh tampilan (composer.json -> php artisan opensid:pindai-tema)."
else
  [ "$total_diff" -eq 0 ] && [ "$missing_pub" -eq 0 ] \
    && echo "Aset publik sudah sama dengan sumber." \
    || echo "Ada perbedaan — jalankan ulang tanpa --check untuk menyalin."
fi
