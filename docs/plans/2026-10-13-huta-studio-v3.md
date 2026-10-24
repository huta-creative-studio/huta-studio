# Plan: Huta Studio V3 — susunan beranda gaya langkidi + warna Simalungun + PWA

Repo tema: /var/www/html/opensid/desa/themes/huta-studio (branch main). Base: HEAD saat mulai.
Testing: shell RED→GREEN (curl Host sordangbolon-ujungpadang.desa.id, temp file), seperti V2.
Semua null-safe. Vanilla JS/CSS. dev-switch.sh activate|denava. TDD per task.

## Task 1: Warna Simalungun + font
RED test: grep huta-v2.css — `--hv-primary: #15803d`, `--hv-accent`, html.dark `--hv-bg: #052516`; source_css belum ada Plus Jakarta Sans.
GREEN: token :root + html.dark direwarnai (mapping #15803d/#166534/#f59e0b/#d97706/#052516/#fafaf9/#57534e/#e7e5e4; dark: #052516/#062816/#14532d/#d6d3cd/#f8fafc); navbar kaca hijau rgba(5,37,22,.92) hover amber-300; .hv-btn hijau #15803d; .hv-btn-gold emas+teks #0f172a bold; override kelas primary-* esensi → emerald; ganti Poppins → Plus Jakarta Sans (link font + body font-family).

## Task 2: Loading screen
RED: home HTML punya #hv-loading + css .hv-loading.
GREEN: partials/loading.blade (overlay #072416, pola titik radial, logo/nama desa, fade-out on load maks 1.5s, prefers-reduced-motion = langsung sembunyi), include di template sebelum </body> (hanya sekali).

## Task 3: PWA
RED: /manifest.webmanifest 200 + aplikasi/json; home punya link manifest & meta theme-color; sw.js 200.
GREEN: public/manifest.webmanifest (nama desa, ikon logo desa existing, start_url /, display standalone, theme #052516); public/sw.js (network-first HTML, cache-first aset statis, skipWaiting; JANGAN cache /siteman*, /layanan-mandiri*, /admin*); register di template.

## Task 4: Susun ulang beranda
RED: urutan marker di home: hv-news sebelum hv-stats.
GREEN: geser include berita-home sebelum statistik-home di 3 layout.

## Task 5: Aparatur + badge kehadiran
RED: home punya section id hv-pamong dengan data pamong (nama); class hv-badge-hadirir (khusus badge, muncul via query).
GREEN: queries (pamong aktif + foto; catatan kehadiran hari ini per pamong dari kehadiran_catatan_harian), section grid card (foto, nama, jabatan), badge hanya bila ada record hari ini (warna per status), null-safe, link /pemerintah.

## Task 6: Pembangunan
RED: home punya id hv-pembangunan; teks "Belum ada data pembangunan" (karena 0 baris).
GREEN: latest pembangunan (nama, lokasi/tahun, progress bila ada), null-safe kegiatan, link /pembangunan.

## Task 7: Galeri
RED: home punya id hv-galeri + "Belum ada foto" (0 baris).
GREEN: latest gambar_gallery (thumb, judul), null-safe, link /galeri.

## Task 8: APBDes ringkasan
RED: home punya id hv-apbdes dengan "anggaran"/angka dari keuangan.
GREEN: total anggaran+realisasi tahun berjalan dari keuangan (fallback tahun terakhir), 3 kartu + bar progres, null-safe, link transparansi (/transparansi atau /keuangan — ikut routing tema).

## Task 9: Modal login pilih portal
RED: home punya id login-modal + 2 kartu (siteman, layanan-mandiri); tombol Login punya data-open-login.
GREEN: partials/login-modal.blade (kaca .login-modal-box, overlay, ×, header fingerprint, 2 kartu); JS buka/tutup (Escape, overlay); tombol login navbar & drawer → preventDefault → modal; variabel --lm-* mengikuti token hv + mode gelap.

## Task 10: Acceptance + cut-over
Suite 1..9 PASS; routes /, /pembangunan/, /galeri/, /pemerintah/, /keuangan/ atau transparansi 200; manifest+sw 200; bottom-nav & 1 kolom mobile (meta viewport sudah); mode gelap token benar; theme status=1 id 29; rollback denava utuh.

## Review Focus (final review)
- PWA: jangan cache halaman privat; scope sw root aman untuk subfolder OpenSID.
- Badge kehadiran: query N+1 vs join; zona waktu server UTC vs tanggal WIB.
- APBDes: pembulatan rupiah, format ribuan, tahun ganda.
- Modal: aksesibilitas (role=dialog, focus), konflik dengan siteman modal core.
- Loading: tidak memblokir interaksi bila JS mati (noscript/timeout CSS).
- Warna: kontras teks emas di dark, override primary-* tidak merusak halaman internal.
