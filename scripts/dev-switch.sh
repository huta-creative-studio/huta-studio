#!/bin/bash
# dev-switch.sh — aktifkan tema untuk pengembangan (hanya untuk server ini)
set -e
MODE="${1:?pakai: activate|denava}"
DB=db_opensid
case "$MODE" in
  activate)
    mysql -e "UPDATE theme SET status=1 WHERE slug='desa-huta-studio'; UPDATE theme SET status=0 WHERE slug='desa-denava';" $DB ;;
  denava)
    mysql -e "UPDATE theme SET status=1 WHERE slug='desa-denava'; UPDATE theme SET status=0 WHERE slug='desa-huta-studio';" $DB ;;
  *) echo "pakai: activate|denava"; exit 1 ;;
esac
cd /var/www/html/opensid
php artisan cache:clear >/dev/null
php artisan view:clear >/dev/null
rm -rf /var/cache/nginx/opensid/*
echo "dev-switch: $MODE OK"
