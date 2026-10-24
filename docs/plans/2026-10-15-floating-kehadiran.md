# Widget Floating Kehadiran + WhatsApp + Kembali ke Atas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan satu tumpukan tombol floating di semua halaman (kehadiran → WhatsApp → up) dengan panel daftar perangkat: drawer 400 px di desktop, sheet penuh + pegangan geser di HP.

**Architecture:** Satu partial Blade baru (`partials/floating-tools.blade.php`) berisi markup + JavaScript vanilla (IIFE, tanpa jQuery/Alpine) yang membaca API `route('api.pemerintah')`, menghitung urutan & badge lalu merender baris. CSS kustom `.hv-ft-*`/`.hv-keh-*` di akhir `assets/css/huta-v2.css`, perilaku layar lebar/sempit ditentukan media query `max-width:1023px` (selaras `.hv-bottom`). Tombol "Chat Desa" (`partials/wa-floating.blade.php`) dan tombol up lama (`commons/back_to_top.blade.php`) dipensiunkan.

**Tech Stack:** Laravel Blade (template literal JS + `@json`), CSS kustom, suite uji bash statis (`check`/`skip`, pola `/var/www/html/opensid/desa/themes/huta-studio/.superpowers/sdd/2026-10-15-pemerintah-kartu-perangkat/test-task1.sh`), verifikasi browser via skrip Playwright mandiri (chromium `/opt/google/chrome/chrome` wrapper `--no-sandbox`).

**Spec:** `docs/plans/2026-10-15-floating-kehadiran-design.md` (desain disetujui user 2026-10-15). Mockup: `.superpowers/brainstorm/310279-1792079207/content/`.

## Global Constraints
- Breakpoint: **1024 px**. ≥1024 = drawer 400 px kanan + overlay gelap; <1024 = sheet `width:100%` + `touch-action: pan-y`.
- Tombol: 44×44, radius 12 px, shadow `0 4px 16px rgba(2,32,58,.25)`; urutan atas→bawah kehadiran (`var(--hv-primary)`) → WhatsApp (`#25d366`) → up (`var(--hv-text-strong,#0f172a)`, `hidden` sampai `scrollY > 800`).
- Posisi stack: `right:1.5rem; bottom:1.5rem`; di <1024 `bottom:5.5rem` (di atas `.hv-bottom`, mengikuti aturan lama baris 317 huta-v2.css).
- Teks badge persis: `Hadir di Kantor` (`.hv-badge--hadir`) · `Tidak di Kantor` (`.hv-badge--tidak`, oranye `#fff7ed/#c2410c/#fed7aa`) · `Belum Rekam Kehadiran` (`.hv-badge--belum`) · `Libur` (`.hv-badge--libur`, netral `#f8fafc/#475569/#e2e8f0`).
- Logika status: `hari_libur` true → semua `Libur`; selain itu `kehadiran` objek → `jam_keluar` terisi (≠ '' dan tidak diawali `00:00`) = `Tidak di Kantor`, belum keluar = `Hadir di Kantor`; `kehadiran` null → `status_kehadiran.toLowerCase()==='hadir'` = `Hadir di Kantor`, teks asli jika terisi, selainnya `Belum Rekam Kehadiran`. Teks dari `theme_config('kehadiran_text_hadir'/'kehadiran_text_tidak')`.
- Urutan daftar: **Pangulu** (`nama_jabatan` sama dengan `setting('sebutan_pangulu') ?: 'Pangulu'`, huruf kecil semua) → **yang hadir** → sisanya; urutan API dipertahankan di dalam tiap kelompok (sort stabil).
- Data: loop semua halaman API sampai `links.next` habis, **maks 10 halaman**; diambil saat panel pertama dibuka per pemuatan halaman; **tanpa localStorage**.
- Tanpa tombol "Lihat Selengkapnya"; tanpa auto-close mouse-leave; tutup desktop = klik overlay atau ✕ (atau Esc); tutup HP = geser ke kanan dari titik mana pun pada panel (satu-satunya cara, tombol ✕ disembunyikan di <1024).
- **Tanpa dependensi baru** — khususnya tidak memakai Alpine (`x-data`) di widget ini; ikon Font Awesome 5 yang sudah dimuat.
- Konsol browser **0 error** (kegagalan API menampilkan pesan di panel, tanpa `console.error`).
- Dukung mode gelap: varian `html.dark` untuk panel/baris/badge/tombol.
- Aksesibilitas: `aria-label` tiap tombol, `aria-expanded` di tombol kehadiran, panel `role="dialog" aria-modal="true"`, `hidden` saat tertutup, foto `loading="lazy"` + `alt="Foto <nama>"`.
- Versi tema: `2610.3.0 → 2610.4.0` (theme.json + composer.json).
- Repo & jalur: semua commit di repo tema `/var/www/html/opensid/desa/themes/huta-studio` (branch `main`); suite & ledger di `.superpowers/sdd/2026-10-15-floating-kehadiran/` (git-ignored).
- Jangan cetak token/password ke chat; wajib membaca hasil verifikasi sebelum klaim lulus (verification-before-completion).

## Review Focus
- **Geser ke kanan sambil daftar digulir vertikal** (`touch-action` salah → scroll macet atau panel ikut meluncur): uji browser HP — gulir list vertikal tidak menggerakkan panel; drag horizontal >80 px menutup panel (Task 3).
- **Nama panjang di baris sempit** ("RAINOL SPOKHESMAN SILALAHI" di drawer 400 px & sheet 390 px) bisa meluber: uji browser — `scrollWidth <= clientWidth` untuk nama & jabatan tiap baris (Task 3).
- **API gagal/jaringan putus** saat panel dibuka: uji browser dengan `page.route` memblokir `internal_api/pemerintah` → muncul `Data kehadiran tidak tersedia`, 0 galat konsol (Task 3).
- **Perangkat >30** (paginasi API terbatas 30/halaman) memotong daftar diam-diam: uji statis loop `links.next` + batas 10 halaman (Task 1) dan uji browser jumlah baris == `meta.total` API (Task 3).
- **Tabrakan dengan bottom-nav & mode gelap**: uji browser HP — nav `display:none` saat panel terbuka dan kembali setelah tertutup, stack `bottom >= 88px`; statis — varian `html.dark` untuk `.hv-keh-panel`/`.hv-ft-btn` (Task 2).

---

### Task 1: Partial `floating-tools.blade.php` + perapian include

**Files:**
- Create: `resources/views/partials/floating-tools.blade.php`
- Modify: `resources/views/template.blade.php` (baris 64: `wa-floating` → `floating-tools`)
- Modify: `resources/views/commons/footer.blade.php` (baris 24: hapus `@include('theme::commons.back_to_top')`)
- Delete: `resources/views/partials/wa-floating.blade.php`, `resources/views/commons/back_to_top.blade.php`
- Test: `.superpowers/sdd/2026-10-15-floating-kehadiran/test-task1.sh` (buat direktori + `test-all.sh` loop `1`)

**Interfaces:**
- Consumes: API `internal_api/pemerintah` (`route('api.pemerintah')`) — atribut: `nama`, `nama_jabatan`, `foto` (URL), `status_kehadiran` (string), `hari_libur` (bool), `kehadiran` (objek `{jam_masuk, jam_keluar}` atau null); `links.next`/`meta.total` untuk paginasi. `theme_config('wa_enable'|'wa_number'|'wa_message'|'wa_label')`, `theme_config('kehadiran_text_hadir'|'kehadiran_text_tidak')`, `setting('sebutan_pangulu')`.
- Produces (dipakai Task 2 & 3): kelas markup `.hv-ft-stack`, `.hv-ft-btn`, `.hv-ft-btn--hadir`, `.hv-ft-btn--wa`, `.hv-ft-btn--up`; `.hv-keh-overlay`, `.hv-keh-panel` (+class `is-open` saat tampil), `.hv-keh-head`, `.hv-keh-close`, `.hv-keh-eyebrow`, `.hv-keh-title`, `.hv-keh-date`, `.hv-keh-list`, `.hv-keh-empty`, `.hv-keh-row`, `.hv-keh-foto`, `.hv-keh-info`, `.hv-keh-nama`, `.hv-keh-jabatan`, `.hv-keh-handle-area`, `.hv-keh-handle`; id `hv-ft-kehadiran`, `hv-ft-up`, `hv-keh-overlay`, `hv-keh-panel`, `hv-keh-close`, `hv-keh-list`, `hv-keh-date`; fungsi global tidak diekspor (IIFE) — perilaku diuji lewat DOM.

- [x] **Step 1: Tulis test-task1.sh** — check (bash `check "deskripsi" "eval"`, `$F = resources/views/partials/floating-tools.blade.php`):
  - markup: `hv-ft-stack` membungkus tombol kehadiran → wa → up secara berurutan (python: urutan indeks `hv-ft-btn--hadir` < `hv-ft-btn--wa` < `hv-ft-btn--up` di `$F`).
  - tombol kehadiran punya `aria-expanded` dan `aria-controls="hv-keh-panel"`; panel punya `role="dialog"` + `aria-modal="true"`.
  - handle: ada `.hv-keh-handle-area` berisi `.hv-keh-handle` (grep keduanya).
  - JS: ada `route('api.pemerintah')`, loop `links.next`, batas `10` halaman, dan `localStorage` TIDAK dipakai (`! grep -q 'localStorage' $F`).
  - urutan: ada `sebutan_pangulu`, `toLowerCase()` dipakai untuk perbandingan pangulu, dan tiga kunci sort (python: ada `'pangulu'`, `'hadir'` dan `'lain'` sebagai kembalian fungsi kunci urut).
  - badge: keempat teks ada persis (`Hadir di Kantor`, `Tidak di Kantor`, `Belum Rekam Kehadiran`, `Libur`) + keempat kelas (`hv-badge--hadir|tidak|belum|libur`).
  - libur menang: cabang `hari_libur` mengembalikan `Libur` sebelum cabang lain (python: indeks `'libur'` pada fungsi status lebih kecil dari indeks `'kehadiran'`).
  - tutup: handler `click` overlay, klik `hv-keh-close`, `Escape`, dan touch/pointer `pointerdown`/`pointerup` untuk geser; **tidak ada** `mouseleave`/`pointerleave` pada panel (`! grep -Eq 'mouseleave|pointerleave' $F`).
  - up: ada listener `scroll` dengan ambang `800` dan `hidden` di-toggle.
  - kegagalan API: ada `catch` yang mengisi `.hv-keh-empty` dengan `Data kehadiran tidak tersedia` dan tidak memakai `console.error`.
  - wiring: `template.blade.php` memuat `theme::partials.floating-tools` dan TIDAK lagi `wa-floating`; `footer.blade.php` TIDAK lagi `back_to_top`; kedua file lama sudah dihapus.
- [x] **Step 2: Jalankan → FAIL semua** — `bash .superpowers/sdd/2026-10-15-floating-kehadiran/test-task1.sh` (ekspektasi: semua `FAIL`, exit 1).
- [x] **Step 3: Implementasi partial + wiring** — isi `floating-tools.blade.php`:
  1. Bagian `@php`: `$waAktif = theme_config('wa_enable') !== '0'; $waNomor = preg_replace('/[^0-9]/','', (string) theme_config('wa_number')); $waPesan = trim((string) theme_config('wa_message')); $waLabel = theme_config('wa_label') ?: 'Chat Desa'; $waUrl = ($waAktif && $waNomor !== '') ? 'https://wa.me/'.$waNomor.($waPesan !== '' ? '?text='.rawurlencode($waPesan) : '') : null;`
  2. Markup (urutan persis): stack tombol (kehadiran `<button aria-expanded="false" aria-controls="hv-keh-panel" aria-label="Status kehadiran perangkat">` ikon `fas fa-user-check`; wa `<a target="_blank" rel="noopener noreferrer">` ikon `fa fa-whatsapp` + `aria-label="{{ $waLabel }}"`, hanya jika `$waUrl`; up `<button hidden aria-label="Kembali ke atas">` ikon `fa fa-chevron-up`) → overlay `<div class="hv-keh-overlay" hidden>` → `<aside class="hv-keh-panel" role="dialog" aria-modal="true" aria-label="Panel informasi status kehadiran" hidden>` berisi head (tombol ✕ + `PANEL INFORMASI` + `Status <span>Kehadiran</span>` + tanggal), `<div class="hv-keh-list" id="hv-keh-list">`, dan `<div class="hv-keh-handle-area" aria-hidden="true"><span class="hv-keh-handle"></span></div>`.
  3. JS IIFE vanilla: konstanta `API = '{{ route('api.pemerintah') }}'`; `TEKS` dari `@json(theme_config('kehadiran_text_hadir') ?: 'Hadir di Kantor')` dan `@json(theme_config('kehadiran_text_tidak') ?: 'Tidak di Kantor')`; fungsi `status(a)` (aturan Global Constraints), `kunciUrut(a)` → `0` pangulu / `1` hadir / `2` lainnya, `urutkan(list)` sort stabil berbasis `kunciUrut`, `ambilSemua()` loop `page=1..10` dengan `links.next` (berhenti saat tidak ada `next` atau kosong), `render(list)`, `buka()`, `tutup()`, `isMobile()` = `matchMedia('(max-width:1023px)')`.
  4. Perilaku: `buka()` → ambil data bila belum ada → `hidden=false` overlay+panel + class `is-open` + `aria-expanded=true` + (HP) `.hv-bottom` diberi `hidden`; `tutup()` → balik semuanya (kembalikan `.hv-bottom`); listener: klik tombol kehadiran (toggle), klik overlay, klik ✕, `Escape`; gestur: `pointerdown` di panel mencatat `x/y`, `pointerup` menutup bila `dx > 80` dan `|dx| > |dy|` (gerak horizontal), `pointermove` menggerakkan `transform: translateX(dx)` selama geser; tombol up: `scroll` → `hidden = scrollY <= 800`.
  5. Wiring: ganti baris include `wa-floating` di `template.blade.php` jadi `floating-tools`; hapus `@include('theme::commons.back_to_top')` di `footer.blade.php`; hapus kedua file lama.
- [x] **Step 4: Jalankan → PASS** — `bash .../test-task1.sh` (semua `ok`, exit 0).
- [x] **Step 5: Commit** — `git add -A && git commit -m "feat(floating): partial tombol kehadiran/wa/up + panel daftar perangkat (vanilla JS)"`.

### Task 2: CSS `.hv-ft-*`/`.hv-keh-*` di huta-v2.css

**Files:**
- Modify: `assets/css/huta-v2.css` (tambah blok baru di akhir file; ubah media query baris 316-317)
- Test: `.superpowers/sdd/2026-10-15-floating-kehadiran/test-task2.sh` + tambah `2` ke `test-all.sh`

**Interfaces:**
- Consumes: nama kelas dari Task 1 (daftar di atas) + variabel tema `--hv-primary`, `--hv-card`, `--hv-text-strong`, `--hv-border`.
- Produces: perilaku visual (diverifikasi Task 3) — stack kanan-bawah, drawer 400 px/sheet penuh, handle, badge 4 varian, varian `html.dark`.

- [x] **Step 1: Tulis test-task2.sh** — check statis: tiap selektor & kelompok properti ada (`grep -q`); `python3` cek: (a) `.hv-ft-stack` punya `position: fixed`, `right: 1.5rem`, `bottom: 1.5rem`; (b) di dalam `@media (max-width: 1023px)` ada `.hv-ft-stack` dengan `bottom: 5.5rem` DAN `.hv-keh-handle-area { display: flex }` DAN `.hv-keh-close { display: none }`; (c) `.hv-keh-panel` punya `width: 400px` di luar media query dan `width: 100%` di `@media (max-width: 1023px)`; (d) `.hv-keh-panel` punya `touch-action: pan-y`, `transition: transform`, dan `.hv-keh-panel.is-open` memakai `translateX(0)`; (e) keempat `.hv-badge--hadir|tidak|belum|libur` ada beserta `html.dark` untuk `--tidak` dan `--libur`; (f) `.hv-ft-btn--up[hidden]` menetapkan `display: none`; (g) `prefers-reduced-motion` mematikan transisi panel/stack; (h) keseimbangan kurung kurawal file = 0.
- [x] **Step 2: Jalankan → FAIL** — `bash .../test-task2.sh`.
- [x] **Step 3: Tambah blok CSS di akhir `huta-v2.css`** (nilai persis):
  ```css
  /* ===== Widget floating: kehadiran + WhatsApp + up ===== */
  .hv-ft-stack { position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1500; display: grid; gap: .75rem; justify-items: end; }
  .hv-ft-btn { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 16px rgba(2, 32, 58, .25); font-size: 1rem; transition: transform .25s ease; }
  .hv-ft-btn:hover { transform: translateY(-2px); }
  .hv-ft-btn--hadir { background: var(--hv-primary); }
  .hv-ft-btn--wa { background: #25d366; }
  .hv-ft-btn--up { background: var(--hv-text-strong, #0f172a); }
  .hv-ft-btn--up[hidden] { display: none; }
  .hv-keh-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px); z-index: 1500; }
  .hv-keh-overlay[hidden] { display: none; }
  .hv-keh-panel { position: fixed; top: 0; right: 0; width: 400px; max-width: 100%; height: 100dvh; z-index: 1510; display: flex; flex-direction: column; background: var(--hv-card); border-left: 1px solid var(--hv-border); box-shadow: -25px 0 50px -12px rgba(0, 0, 0, .25); transform: translateX(100%); transition: transform .5s cubic-bezier(.4, 0, .2, 1); touch-action: pan-y; }
  .hv-keh-panel[hidden] { display: none; }
  .hv-keh-panel.is-open { transform: translateX(0); }
  .hv-keh-head { position: relative; padding: 1.25rem; border-bottom: 1px solid var(--hv-border); }
  .hv-keh-close { position: absolute; left: 1.25rem; top: 1.25rem; width: 36px; height: 36px; border-radius: 10px; border: none; background: var(--hv-border); color: var(--hv-text-strong, #0f172a); cursor: pointer; }
  .hv-keh-eyebrow { margin: 0; text-align: right; font-size: .68rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: #94a3b8; }
  .hv-keh-title { margin: .15rem 0 0; text-align: right; font-size: 1.25rem; font-weight: 800; color: var(--hv-text-strong, #0f172a); }
  .hv-keh-title span { color: var(--hv-primary); }
  .hv-keh-date { margin: .25rem 0 0; text-align: right; font-size: .72rem; font-weight: 600; color: #64748b; }
  .hv-keh-list { flex: 1; overflow-y: auto; padding: 1rem; display: grid; gap: .75rem; align-content: start; }
  .hv-keh-row { display: flex; align-items: center; gap: .75rem; padding: .7rem .8rem; border: 1px solid var(--hv-border); border-radius: 14px; background: var(--hv-card); }
  .hv-keh-foto { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; flex: 0 0 auto; background: var(--hv-border); }
  .hv-keh-info { min-width: 0; flex: 1; }
  .hv-keh-nama { font-size: .82rem; font-weight: 700; color: var(--hv-text-strong, #0f172a); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .hv-keh-jabatan { font-size: .66rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .hv-keh-empty { grid-column: 1 / -1; text-align: center; font-size: .8rem; font-weight: 600; color: #64748b; padding: 2rem 0; }
  .hv-badge--tidak { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
  .hv-badge--libur { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
  .hv-keh-handle-area { display: none; }
  .hv-keh-handle { width: 52px; height: 5px; border-radius: 999px; background: #cbd5e1; display: block; }
  html.dark .hv-keh-overlay { background: rgba(2, 6, 23, .6); }
  html.dark .hv-keh-close { background: #1f2937; color: #e5e7eb; }
  html.dark .hv-badge--tidak { background: rgba(194, 65, 12, .15); color: #fdba74; border-color: rgba(194, 65, 12, .35); }
  html.dark .hv-badge--libur { background: rgba(148, 163, 184, .12); color: #cbd5e1; border-color: rgba(148, 163, 184, .3); }
  @media (max-width: 1023px) {
    .hv-ft-stack { bottom: 5.5rem; }
    .hv-keh-panel { width: 100%; border-left: none; }
    .hv-keh-handle-area { display: flex; justify-content: center; padding: .8rem 0 calc(.8rem + env(safe-area-inset-bottom, 0px)); border-top: 1px solid var(--hv-border); }
    .hv-keh-close { display: none; }
  }
  @media (prefers-reduced-motion: reduce) {
    .hv-ft-btn, .hv-keh-panel { transition: none; }
  }
  ```
  Ubah baris 316-317 (`.huta-wa, #toTop, .back-to-top { bottom: 5.5rem !important; }`) menjadi `.hv-ft-stack { bottom: 5.5rem; }` — pemakaai `!important` tidak perlu karena blok baru didefinisikan setelahnya.
- [x] **Step 4: Jalankan → PASS** — `bash .../test-task2.sh`.
- [x] **Step 5: Commit** — `git add assets/css/huta-v2.css && git commit -m "feat(floating): CSS stack tombol, drawer/sheet, handle geser, badge Libur + mode gelap"`.

### Task 3: Rilis, uji penuh, verifikasi browser, ledger

**Files:**
- Modify: `theme.json:5`, `composer.json:6` (`2610.3.0 → 2610.4.0`)
- Create: `.superpowers/sdd/2026-10-15-floating-kehadiran/verify-float.mjs` (skrip Playwright mandiri) + `progress.md` (ledger)
- Modify: `docs/plans/2026-10-15-floating-kehadiran.md` (centang checklist)

**Interfaces:**
- Consumes: hasil Task 1-2 + suite lama `/var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` (baseline 154 ok) + suite `/pemerintah` `.superpowers/sdd/2026-10-15-pemerintah-kartu-perangkat/test-all.sh` (baseline 54 ok).
- Produces: tema `v2610.4.0` aktif; bukti verifikasi; ledger lengkap.

- [x] **Step 1: Bump versi** — `sed` kedua file; jalankan `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task7.sh` → tetap pass (membandingkan theme.json ↔ composer.json).
- [x] **Step 2: Deploy** — di `/var/www/html/opensid`: `php artisan opensid:pindai-tema`, `bash desa/themes/huta-studio/scripts/dev-switch.sh activate`, `cache:clear`, `view:clear`, purge `/var/cache/nginx/opensid/*`, `systemctl restart php8.4-fpm`. Verifikasi: `curl -sk -H 'Host: sordangbolon-ujungpadang.desa.id' https://127.0.0.1/pemerintah` → 200, `hv-ft-stack` ada, `huta-v2.css&themeVersion=v2610.4.0` muncul, dan `huta-wa`/`back_to_top` tidak lagi dirender.
- [x] **Step 3: Suite penuh** — suite lama (154 ok) + suite `/pemerintah` (54 ok) + suite baru (ekspektasi semua pass) disimpan ke `task-tests.log`; **baca outputnya**.
- [x] **Step 4: Verifikasi browser (skrip mandiri `verify-float.mjs`, chromium `/opt/google/chrome/chrome --no-sandbox`)** —
  - **Desktop 1366×900:** `#hv-ft-up` `hidden` saat load → set `scrollTo(0,1200)` + dispatch `scroll` → `hidden=false`; klik `#hv-ft-kehadiran` → panel `is-open`, `aria-expanded=true`, `getComputedStyle(panel).width ≈ 400`, `transform` bergerak ke 0, overlay tampil; jumlah baris == `meta.total` API; urutan: baris 0 = jabatan Pangulu, baris 1..k = badge `hv-badge--hadir`; klik overlay → tertutup (`hidden=true`); buka lagi lalu tekan `Escape` → tertutup; buka lagi lalu klik `#hv-keh-close` → tertutup; nama & jabatan tiap baris `scrollWidth <= clientWidth`; `#hv-ft-wa` href diawali `https://wa.me/`; 0 galat konsol.
  - **HP 390×844:** `.hv-bottom` tampil → klik `#hv-ft-kehadiran` → nav `display:none`, panel `width == 390`, `.hv-keh-handle-area` `display:flex` dan y-posisi tetap (di bawah) ketika `#hv-keh-list` di-scroll (`scrollTop=200`) → bandingkan `getBoundingClientRect().y` handle sebelum/sesudah (selisih <1 px); `getComputedStyle(panel).touchAction === 'pan-y'`; simulasi geser: `pointerdown` di (300,400) → `pointermove` ke (300+120, 405) → `pointerup` → panel tertutup + nav kembali tampil; gulir vertikal: `pointerdown` (300,400) → `pointermove` ke (300,300) → `pointerup` → panel TETAP terbuka; `#hv-keh-close` `display:none`; screenshot mobile & desktop disimpan di direktori suite (tidak dikirim ke Telegram).
  - **API gagal:** `page.route('**/internal_api/pemerintah*', r => r.abort())` → buka panel → `.hv-keh-empty` berisi `Data kehadiran tidak tersedia`, 0 galat konsol.
- [x] **Step 5: Ledger** — entri: tanggal, ringkasan perubahan, jumlah ok suite, angka persis verifikasi browser, jebakan (breakpoint 1024 = `.hv-bottom`; batas 10 halaman API; gestur memakai pointer events sehingga tetap jalan dengan mouse di DevTools), divergensi aturan "Libur" (hanya panel, halaman `/pemerintah` tidak diubah), catatan: `/pemerintah` tidak kena fastcgi_cache, URL CSS membawa `themeVersion`.
- [x] **Step 6: Commit akhir** — `git add -A && git commit -m "release: tema v2610.4.0 — widget floating kehadiran + WhatsApp + up"`; `git status` bersih; catat hash di ledger.
