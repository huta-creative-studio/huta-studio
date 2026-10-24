# Huta Studio V4 — Perbaikan & Modernisasi Tampilan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernisasi tampilan Huta Studio V3 menjadi gaya desaciater.id (loading screen fluida, grid 1 kolom, slider satu baris, APBDes filter tahun, link artikel benar, cleanup theme esmo) sambil tetap memakai palette warna simalungun (#15803d hijau + #f59e0b emas + #052516 tinta) dan font Plus Jakarta Sans.

**Architecture:** Setiap tugas di-implementasikan sendiri sebagai fitur/tambahannya dengan RED→GREEN TDD per task. Semua partial/viewsDiedit konsisten di 3 layout (full-content, left-sidebar, right-sidebar). CSS additions hanya di `assets/css/huta-v2.css`. Loading screen dipartial tersendiri. Service worker sudah ada dari V3, diuji kembali.

**Tech Stack:** Laravel Blade + vanilla CSS/JS (tanpa Tailwind/Alpine/jQuery), Google Fonts Poppins → Plus Jakarta Sans, Tailwind kelas bawaan esensi di-override ke emerald/amber, mysql/db_opensid untuk query data.

**Spec:** User requests 6 poin: (1) loading screen modern dari desaciater.id, (2) aparatur badge + slider 1 kolom HP+desktop, (3) APBDes filter tahun dari dashboard, (4) fix link artikel/berita 404, (5) hapus jejak tema esmo, (6) susun ulang seperti desaciater.id tapi warna simalungun.

---

## Global Constraints

- Tema repo: `/var/www/html/opensid/desa/themes/huta-studio` (branch main, detached HEAD 2fa3c6860).
- Setiap partial/include harus konsisten di 3 layout: full-content.blade.php, left-sidebar.blade.php, right-sidebar.blade.php.
- Vanilla CSS/JS hanya — tidak boleh menambah Tailwind CDN/Alpine/jQuery (VPS 1 core/2.2GB RAM).
- Setiap task dimulai dengan menulis test yang gagal (RED), lalu implementasi minimal (GREEN), lalu verifikasi.
- Rollback tetap tersedia: `dev-switch.sh denava` → `dev-switch.sh activate`.
- Tidak mengubah core OpenSID ( hanya menambah file di `resources/views/partials/` dan `assets/css/huta-v2.css`, plus file baru di `public/` jika perlu).
- Font tetap Poppins → Plus Jakarta Sans (sudah ada di V3).
- PWA dari V3 (manifest + sw.js) tetap aktif, diuji kembali setiap task.

---

## Review Focus

1. **XSS di loading screen** — gambar dari DB (`$desa['logo']`) harus di-escape via `{{ }}` Blade, tidak boleh `!!`. Bisa menanamkan JS berbahaya jika nama desa mengandung tag.
2. **Layout shift pada grid 1 kolom** — `<img>` harus memiliki `width` dan `height` statis (atau `aspect-square`) agar foto pamong tidak menggeser layout saat load di HP.
3. **APBDes tahun filter** — dropdown harus submit form atau panggil AJAX ke route yang sama, tanpa refresh penuh halaman jika mungkin; tahun yang dipilih harus valid (ada di tabel `keuangan`), bila tidak ada data → tampil "Tidak ada data untuk tahun X".
4. **Link artikel 404** — pastikan slug di database tidak NULL dan `buat_slug()` menghasilkan string non-kosong; route `/artikel/{slug?}` harus match; uji dengan curl `-o` dan grep.
5. **Modal login fokus** — sudah ada dari V3, tidak disentuh di plan ini kecuali jika ada bug baru.
6. **Warna kontras** — badge kehadiran di dark mode harus kontras (sudah divalidasi di V3, tidak diubah).

---

## Task 1: Loading screen modern (ref desaciater.id)

**Files:**
- Create: `resources/views/partials/loading-modern.blade.php`
- Modify: `resources/views/template.blade.php` — ganti `@include('theme::partials.loading')` dengan `@include('theme::partials.loading-modern')`
- Modify: `assets/css/huta-v2.css` — tambah kelas `.hv-loading-modern`

**Interfaces:**
- Consumes: `--hv-ink-soft` (sudah ada warna `#072416`); `--hv-primary` (hijau `#15803d`), `--hv-accent` (emas `#f59e0b`)
- Produces: partial loading-modern.blade.php yang mengandung overlay fade-in, logo tengah, progress bar horizontal, menghilang setelah `load` event (max 1.5s) dan `prefers-reduced-motion` support

**Step 1: Write the failing test**
- [ ] Write test `test-loading-screen-modern` that curls home page and checks:
  - `[data-test="hv-loading-modern"]` exists in HTML
  - `.hv-loading-modern` CSS class present
  - tidak ada `.hv-loading` lama yang berganding
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task1.sh`
- Expected: FAIL (test belum ada, atau marker belum ada)

**Step 2: Run test to verify it fails**
- Run the test; confirm it fails because loading-modern partial belum dibuat.

**Step 3: Implement `loading-modern.blade.php`**
- Buat partial dengan desain: fade-in logo 0.5s, progres bar horizontal hijau ke emas, hilang setelah `window.load` (1.5s max), `prefers-reduced-motion = display:none`
- Simpan di `resources/views/partials/loading-modern.blade.php`

**Step 4: Run test to verify it passes**
- Run the test again; confirm it passes.

**Step 5: Commit**
```bash
git add resources/views/partials/loading-modern.blade.php assets/css/huta-v2.css
git commit -m "feat(huta-studio): loading screen modern dari desaciater.id"
```

**Step 6: Update template include**
- Ganti include di 3 layout: full-content, left-sidebar, right-sidebar

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS

---
## Task 2: Aparatur badge & slider 1 kolom (HP + desktop)

**Files:**
- Modify: `resources/views/partials/pamong-home.blade.php` — ganti grid CSS menjadi 1 kolom di semua lebar, tambah `aspect-square` pada img, ubah badge kehadiran agar tidak melayang
- Modify: `assets/css/huta-v2.css` — tambah `.hv-pamong-grid { grid-template-columns: 1fr !important; }` di semua media query, `.hv-pamong-card { aspect-ratio: 1; width: 100%; }`, `.hv-pamong-photo { width: 100%; height: 100%; object-fit: cover; }`

**Interfaces:**
- Consumes: `$hvPamong` collection dari task 1 (sudah ada); `$hvHadirToday` keyBy pamong_id
- Produces: grid 1 kolom hanya, foto tidak potong, badge di bawah nama dengan warna hadir (hijau) / lain (kuning)

**Step 1: Write the failing test**
- [ ] Write test `test-aparatur-grid-1col` that curls home page, checks:
  - `.hv-pamong-grid` has `grid-template-columns: 1fr` (via inspect CSS or HTML class)
  - Setiap `<img hv-pamong-photo>` memiliki `width` dan `height` atribut yang statis
  - badge kehadiran hanya muncul bila ada data hari ini
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task2.sh`
- Expected: FAIL (CSS grid masih multi-column, foto belum punya dimensi statis)

**Step 2: Run test to verify it fails**
- Run the test; confirm failures.

**Step 3: Implement CSS changes in `huta-v2.css`**
- Tambah aturan: `.hv-pamong-grid { grid-template-columns: 1fr !important; }`
- `@media (min-width: ...)` rulesghapus atau di-override jadi `1fr !important`
- Tambah `.hv-pamong-card { aspect-ratio: 1; width: 100%; }`
- Tambah `.hv-pamong-photo { width: 100%; height: 100%; object-fit: cover; }`
- Boleh juga tambah `loading="lazy"` dan `style="width:100%;height:160px;"` di partial

**Step 4: Run test to verify it passes**
- Run the test again; confirm it passes.

**Step 5: Commit**
```bash
git add resources/views/partials/pamong-home.blade.php assets/css/huta-v2.css
git commit -m "feat(huta-studio): aparatur grid 1 kolom + foto aspect-square"
```

**Step 6: Verifikasi di HP + Desktop**
- `dev-switch.sh activate`; curl home; verifikasi 1 kolom foto utuh.

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS

---
## Task 3: APBDes filter tahun dari dashboard

**Files:**
- Modify: `resources/views/partials/apbdes-home.blade.php` — tambah dropdown `<select>` tahun di bagian atas, nilai awal = `$hvApbdesTahun` (max tahun dari DB)
- Modify: logika query di partial — filter berdasarkan `$_GET['tahun']` atau form submit ke route yang sama; reload section dengan hasil filter
- Modify: `assets/css/huta-v2.css` — tambah style untuk select box dan loading state

**Interfaces:**
- Consumes: `$hvApbdesTahun` dari query `max(tahun)` di awal partial; `$_GET['tahun']` dari form
- Produces: section APBDes hanya menampilkan data untuk tahun yang dipilih; jika tahun tidak ada data → "Tidak ada data untuk tahun X"

**Step 1: Write the failing test**
- [ ] Write test `test-apbdes-tahun-filter` that curls home page with `?tahun=2024` param, checks:
  - dropdown `<option value="2024">` tersedia
  - section menampilkan angka berbeda jika tahun berbeda (atau "Tidak ada data untuk tahun 2024" jika tidak ada)
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task3.sh`
- Expected: FAIL (filter tahun belum ada)

**Step 2: Run test to verify it fails**
- Run the test; confirm failures.

**Step 3: Implement di `apbdes-home.blade.php`**
- Tambah `<select name="tahun" id="apbdes-tahun" class="hv-select">...</select>` di atas grid
- Setiap `<option>` tahun dari query `SELECT DISTINCT tahun FROM keuangan ORDER BY tahun DESC`
- Nilai awal = `$hvApbdesTahun` (sudah ada dari task V3)
- Jika `$_GET['tahun']` ada dan valid, filter query `where k.tahun, $tahun`
- Jika tidak ada data untuk tahun yang dipilih, tampil `<div class="hv-empty">Tidak ada data untuk tahun {{ $tahun }}.</div>`

**Step 4: Run test to verify it passes**
- Run the test again; confirm it passes.

**Step 5: Commit**
```bash
git add resources/views/partials/apbdes-home.blade.php assets/css/huta-v2.css
git commit -m "feat(huta-studio): APBDes filter tahun"
```

**Step 6: Verifikasi**
- Coba `?tahun=2024` dan `?tahun=2025`, cek output.

**Step 7: Run full suite**

## Task 3: APBDes filter tahun dari dashboard

**Files:**
- Modify: `resources/views/partials/apbdes-home.blade.php` — tambah dropdown `<select>` tahun di bagian atas, nilai awal = `$hvApbdesTahun` (max tahun dari DB)
- Modify: logika query di partial — filter berdasarkan `$_GET['tahun']` atau form submit ke route yang sama; reload section dengan hasil filter
- Modify: `assets/css/huta-v2.css` — tambah style untuk select box dan loading state

**Interfaces:**
- Consumes: `$hvApbdesTahun` dari query `max(tahun)` di awal partial; `$_GET['tahun']` dari form
- Produces: section APBDes hanya menampilkan data untuk tahun yang dipilih; jika tahun tidak ada data → "Tidak ada data untuk tahun X"

**Step 1: Write the failing test**
- [ ] Write test `test-apbdes-tahun-filter` that curls home page with `?tahun=2024` param, checks:
  - dropdown `<option value="2024">` tersedia
  - section menampilkan angka berbeda jika tahun berbeda (atau "Tidak ada data untuk tahun 2024" jika tidak ada)
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task3.sh`
- Expected: FAIL (filter tahun belum ada)

**Step 2: Run test to verify it fails**
- Run the test; confirm failures.

**Step 3: Implement di `apbdes-home.blade.php`**
- Tambah `<select name="tahun" id="apbdes-tahun" class="hv-select">...</select>` di atas grid
- Setiap `<option>` tahun dari query `SELECT DISTINCT tahun FROM keuangan ORDER BY tahun DESC`
- Nilai awal = `$hvApbdesTahun` (sudah ada dari task V3)
- Jika `$_GET['tahun']` ada dan valid, filter query `where k.tahun, $tahun`
- Jika tidak ada data untuk tahun yang dipilih, tampil `<div class="hv-empty">Tidak ada data untuk tahun {{ $tahun }}.</div>`

**Step 4: Run test to verify it passes**
- Run the test again; confirm it passes.

**Step 5: Commit**
```bash
git add resources/views/partials/apbdes-home.blade.php assets/css/huta-v2.css
git commit -m "feat(huta-studio): APBDes filter tahun"
```

**Step 6: Verifikasi**
- Coba `?tahun=2024` dan `?tahun=2025`, cek output.

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS


## Task 4: Fix link artikel/berita 404

**Files:**
- Modify: cek fungsi `buat_slug()` di helper atau filename yang menyediakan slug
- Modify: `resources/views/partials/berita-home.blade.php` — perbaiki href link dari `site_url('artikel/' . buat_slug(...))` ke format yang benar
- Modify: `resources/views/partials/article.blade.php` — pastikan link canonical benar
- Modify: `routes/web.php` — pastikan route `/artikel/{slug?}` terdaftar dengan benar

**Interfaces:**
- Consumes: `$artikel->slug` dari query artikel; `$artikel->judul` untuk fallback
- Produces: URL `/artikel/{slug}` yang benar dan bisa diakses (HTTP 200), bukan 404

**Step 1: Write the failing test**
- [ ] Write test `test-link-artikel` that curls `/?test=artikel` and checks:
  - Setiap `<a href="...">` di beranda tidak mengandung `//00/00/`
  - Setiap link menghasilkan HTTP 200 saat diklik (atau setidaknya tidak ada 404 untuk link yang valid)
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task4.sh`
- Expected: FAIL (link artikel masih error 404)

**Step 2: Run test to verify it fails**
- Run the test; confirm the 404 issue.

**Step 3: Cek dan perbaiki `buat_slug()` / route**
- Buka file yang menyediakan `buat_slug()` (mungkin di `app/Helpers/` atau `app/`
- Perbaiki agar menghasilkan slug yang valid (non-empty, hanya huruf/angka-kebanyakan)
- Perbaiki route di `routes/web.php` jika perlu: `Route::get('/artikel/{slug}', ...)` atau `Route::get('/artikel/{slug?}', ...)`

**Step 4: Run test to verify it passes**
- Run the test again; confirm links work.

**Step 5: Commit**
```bash
git add ... (file yang diperbaiki)
git commit -m "fix(huta-studio): link artikel/berita benar"
```

**Step 6: Verifikasi**
- Coba berbagai link berita di halaman home, pastikan tidak 404.

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS


## Task 5: Hapus history tema esmo

**Files:**
- Modify: `assets/css/huta-v2.css` — cari dan hapus komentar/* atau deklarasi warna yang merujuk esmo (#16a75c warna lama, atau variabel yang sudah dihapus)
- Modify: `resources/views/commons/source_css.blade.php` — pastikan link font dan CSS hanya pakai Plus Jakarta Sans, tidak ada referensi Poppins atau esmo lagi
- Modify: `config.json` (jika ada) — bersihkan setting warna tema lama
- Modify: `resources/views/template.blade.php` — pastikan tidak ada meta atau komentar "Esmo" di dalam

**Interfaces:**
- Consumes: kodewarna lama `--hv-primary: var(--huta-aksen, #16a75c)` (warna esmo sebelumnya)
- Produces: semuanya hanya pakai warna simalungun baru: `#15803d`, `#f59e0b`, `#052516`; tanpa variabel `--huta-aksen` atau hanya nilainya saja sebagai fallback

**Step 1: Write the failing test**
- [ ] Write test `test-esmo-cleanup` that reads `assets/css/huta-v2.css` and checks:
  - tidak ada deklarasi `--hv-primary: var(--huta-aksen, #16a75c)` atau `#16a75c` sama sekali di CSS
  - tidak ada komentar merujuk "esmo" di file Blade apa pun
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task5.sh`
- Expected: FAIL (masih ada jejak esmo)

**Step 2: Run test to verify it fails**
- Run the test; confirm jejak esmo masih ada.

**Step 3: Implement cleanup**
- Buka `assets/css/huta-v2.css`, hapus atau ubah segala referensi `#16a75c` atau `--huta-aksen` menjadi `#15803d` (hijau simalungun) atau dihapus jika sudah ada warna baru
- Buka `resources/views/commons/source_css.blade.php`, pastikan link font hanyalah Plus Jakarta Sans
- Buka `config.json` jika ada, bersihkan `warna_aksen` atau setting serupa
- Cek `resources/views/template.blade.php` untuk komentar esmo

**Step 4: Run test to verify it passes**
- Run the test again; confirm cleanup done.

**Step 5: Commit**
```bash
git add assets/css/huta-v2.css resources/views/ partials/ *.blade.php config.json 2>/dev/null
git commit -m "fix(huta-studio): hapus jejak tema esmo"
```

**Step 6: Verifikasi**
- `grep -r "16a75c\|esmo" resources/ assets/ --include="*.css" --include="*.blade.php" -l`

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS


## Task 6: Susun ulang seperti desaciater.id (tapi warna simalungun)

**Files:**
- Modify: urutan `@include` di `resources/views/layouts/full-content.blade.php`, `left-sidebar.blade.php`, `right-sidebar.blade.php`
- Urutan harus jadi: navbar → loading-modern → hero → berita-home → statistik-home → pamong-home → pembangunan-home → galeri-home → apbdes-home → footer/bottom-nav
- Modify: `assets/css/huta-v2.css` — pastikan tata letak grid dan posisi sesuai urutan baru (tidak ada overlap, margin benar)

**Interfaces:**
- Consumes: partial-partial yang sudah dibuat di task 1-5 (loading-modern, pamong-home, berita-home, statistik-home, pembangunan-home, galeri-home, apbdes-home)
- Produces: halaman home dengan susunan section sesuai desain desaciater.id (hero di atas, statistik di bawah, aparatur berikutnya, dan seterusnya); warna tetap simalungun

**Step 1: Write the failing test**
- [ ] Write test `test-home-order` that curls home page with `Host: sordangbolon-ujungpadang.desa.id`, checks:
  - Urutan marker HTML: posisi `hv-news` < `hv-stats` < `hv-pamong` < `hv-pembangunan` < `hv-galeri` < `hv-apbdes` (cari urutan melalui `grep -bo` atau parsing DOM)
  - Tidak ada section duplikat dari V2 (misal: tidak ada daftar artikel beranda dua kali)
- [ ] Run test: `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task6.sh`
- Expected: FAIL (urutan masih V2 atau ada duplikat)

**Step 2: Run test to verify it fails**
- Run the test; confirm urutan salah atau duplikat.

**Step 3: Urgenta urutkan include di 3 layout**
- Buka `full-content.blade.php`, `left-sidebar.blade.php`, `right-sidebar.blade.php`
- Urutan `@include` sekarang: 
  1. `@include('theme::partials.navbar')` (sudah ada, belum diubah)
  2. `@include('theme::partials.loading-modern')` (task 1)
  3. `@include('theme::partials.hero')` (sudah ada)
  4. `@include('theme::partials.berita-home')` (task 4 perbaikan, sudah di atas statistik dari task V3)
  5. `@include('theme::partials.statistik-home')` (sudah ada, tapi sekarang di bawah berita)
  6. `@include('theme::partials.pamong-home')` (task 2 grid 1 kolom)
  7. `@include('theme::partials.pembangunan-home')` (task 6)
  8. `@include('theme::partials.galeri-home')` (task 6)
  9. `@include('theme::partials.apbdes-home')` (task 3 filter tahun)
  10. `@include('theme::partials.bottom-nav')` (sudah ada)
- Lakukan perubahan yang sama di `left-sidebar.blade.php` dan `right-sidebar.blade.php`

**Step 4: Run test to verify it passes**
- Run the test again; confirm urutan benar dan tidak ada duplikat.

**Step 5: Commit**
```bash
git add resources/views/layouts/*.blade.php assets/css/huta-v2.css
git commit -m "feat(huta-studio): susun ulang beranda seperti desaciater.id + warna simalungun"
```

**Step 6: Verifikasi**
- `curl -sk -H "Host: sordangbolon-ujungpadang.desa.id" "https://127.0.0.1/" | grep -oE 'class="[^"]*"' | grep -E 'hv-(news|stats|pamong|pembangunan|galeri|apbdes)' | sort | uniq -c`

**Step 7: Run full suite**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` → semua PASS

---

## Task 8: Kartu Aparatur ala desaciater.id (Opsi D) + teks lencana dari Pengaturan Tema

**Tujuan (disetujui user):** bentuk kartu, isi kartu, dan gerak section Aparatur disamakan dengan desaciater.id, memakai warna palet Simalungun (emerald/amber) yang sudah dipakai tema. Foto aparatur **tidak dipotong** → user memilih **Opsi D**: bingkai rasio 3:4, foto `contain`, sisa ruang diisi versi blur dari foto yang sama. Judul tetap "Aparatur {Sebutan Desa}" otomatis dari `setting('sebutan_desa')`. Lencana kehadiran tetap ada, teksnya bisa diubah dari Pengaturan Tema.

**Keputusan yang sudah dikonfirmasi user:**
1. Judul: "Aparatur " + `ucwords(setting('sebutan_desa'))` → sekarang "Aparatur Nagori"; otomatis jadi "Aparatur Desa" bila Sebutan Desa = "Desa".
2. Lencana kehadiran tetap tampil + 2 kolom baru di Pengaturan Tema: **"Text Kehadiran di Kantor"** dan **"Text Ketidakhadiran di Kantor"**.
3. Warna: palet Simalungun (emerald + amber) — tidak ikut hijau desaciater.

**Keputusan saya sendiri (bukan permintaan user — bisa diubah 1 baris bila user mau):**
- Arti "di kantor": ada rekaman kehadiran hari ini **dan** `jam_keluar` masih kosong **dan** (`jam_masuk` terisi atau status = 'hadir') → "kehadiran". Ada rekaman tapi sudah keluar / bukan hadir → "ketidakhadiran". **Tanpa rekaman → lencana tidak tampil** (sama seperti perilaku sekarang; "belum rekam" bukan "tidak hadir").
- Header section (label "APARATUR", judul, deskripsi) dan tombol "Profil Pemerintahan" tetap seperti sekarang; yang disamakan dengan desaciater hanya bentuk/isi kartu + geraknya.
- Aparatur tanpa foto → kartu huruf awal dengan bingkai 3:4 yang sama.

**Interfaces:**
- Consumes: `resources/views/partials/pamong-home.blade.php`, `assets/css/huta-v2.css`, `config.json` (skema Pengaturan Tema), helper `theme_config('key')`, aset `assets/vendor/OwlCarousel2-2.3.4` (sudah dimuat global di `commons/source_css.blade.php` + `commons/source_js.blade.php`).
- Produces: section `#hv-pamong` bergaya kartu desaciater (radius 2rem, bingkai 3:4, bar gradien, kilau, hover terangkat), digerakkan OwlCarousel (autoplay 4 dtk + titik navigasi + loop, responsif 1 → 5 kartu), dengan lencana kehadiran berteks yang bisa diatur.

**Jebakan teknis yang sudah diperiksa:**
- `assets/js/script.min.js` punya auto-init generik: `$('.owl-carousel').each(... $(this).owlCarousel({loop:true, items:data-itemsnumber||1, ...}))` pada `$(document).ready`. Re-init dengan objek **diabaikan** bila `data('owl.carousel')` sudah ada (`owl.carousel.js:1718-1741`). Maka init sendiri harus didaftarkan **sebelum** handler itu → skrip inline di dalam partial (partial dirender di body, `script.min.js` dimuat di akhir body). Markup tetap `class="owl-carousel hv-pamong-owl"` agar CSS dasar owl berlaku tanpa FOUC.
- `owl.theme.default.min.css` **tidak** dimuat tema → `.owl-dots` tidak terstyle → distyle sendiri di `huta-v2.css` (mengikuti mockup yang disetujui).

**Step 1: Write the failing test**
- [ ] Tulis `test-task8.sh` (pola `test-task7.sh`) yang memeriksa config.json (2 key + judul tepat), partial (`setting('sebutan_desa')`, `theme_config('kehadiran_text_hadir'/'kehadiran_text_tidak')`, `owl-carousel hv-pamong-owl`, bingkai 3:4, img blur + img fg, lencana hadir/tidak), CSS (`.hv-pamong-img-wrap` aspect-ratio 3/4, `::before`, `::after`, hover `translateY(-15px)`, `.owl-dots`), versi tema, dan HTML live.

**Step 2: Run test to verify it fails**
- `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task8.sh` → FAIL.

**Step 3: Implementasi**
- `config.json`: sisipkan 2 field `input-text` grup "Aparatur" setelah `warna_aksen`.
- `pamong-home.blade.php`: query kehadiran + `jam_keluar`; tentukan `$diKantor`; markup kartu baru; init owl inline.
- `huta-v2.css`: ganti blok `.hv-pamong-*` (baris ~372-384) dengan gaya kartu desaciater + `.owl-dots` + dark mode via variabel.
- `composer.json` + `theme.json`: 2610.1.0 → 2610.2.0, lalu `php artisan opensid:pindai-tema`.

**Step 4: Run test to verify it passes**
- Jalankan `test-task8.sh` → semua ok.

**Step 5: Terapkan ke live + verifikasi visual**
- `desa/themes/huta-studio/scripts/dev-switch.sh activate`
- Cek `curl` live: jumlah kartu, `owl-loaded`, teks lencana, judul.
- Screenshot desktop/mobile → kirim ke Telegram.

**Step 6: Commit + ledger**
- Commit di repo tema; tambahkan Task 8 ke `progress.md`; jalankan `test-all.sh` → PASS.

