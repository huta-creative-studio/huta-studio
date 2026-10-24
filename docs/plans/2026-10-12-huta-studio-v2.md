# Huta Studio V2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merestyle Huta Studio agar menyerupai tampilan langkidi.desa.id (konsep Esmo) dengan kode sendiri, di atas fork esensi, tanpa library berat.

**Architecture:** Partial blade baru (`hero`, `statistik-home`, `berita-home`, `bottom-nav`) + tulis ulang `commons/header` & `commons/footer` + satu stylesheet `huta-v2.css` berbasis CSS variables (light/dark). Template `template.blade.php` hanya menambah include & inisialisasi dark-mode. Deteksi beranda: `uri_string() === '' && !request()->query('cari')`.

**Tech Stack:** Blade (fork esensi), vanilla CSS/JS, Poppins (Google Fonts, display=swap). Tanpa Tailwind/Alpine/jQuery baru.

**Spec:** `docs/2026-10-12-huta-studio-v2-design.md`

## Global Constraints
- Repo tema: `/var/www/html/opensid/desa/themes/huta-studio` (git sendiri, branch main). Satu commit per task.
- Selama dev: **Denava aktif**; verifikasi per task via skrip `scripts/dev-switch.sh` (aktifkan huta-studio → curl uji → kembali Denava) + cache clear + purge nginx setiap ganti.
- Tidak menyentuh file inti OpenSID; tidak mencetak secret.
- Semua query DB dibungkus try/catch + null-safe "data belum diisi".
- URL pintu dari config tervalidasi `^https?://|^/` (dipertahankan dari V1).
- PHP 8.4; helper `tgl_indo`, `site_url`, `gambar_desa`, `theme_config`, `theme_version` dipakai apa adanya.

## Review Focus
1. **Beranda vs halaman dalam** — hero/statistik/berita hanya di beranda; halaman dalam navbar solid tanpa hero (uji `/pembangunan` tanpa `huta-hero`).
2. **Pencarian** — `/?cari=x` menampilkan hasil tanpa hero portal.
3. **Null-safe** — statistik & berita tahan data kosong; log tanpa error.
4. **Dark mode** — toggle persisten; `html.dark` di semua komponen (uji jumlah aturan `.dark` di CSS).
5. **Bottom-nav vs WA float** — tidak tumpang tindih di mobile (offset CSS).

---

### Task 1: Fondasi (CSS token + dark init + helper dev-switch)
**Files:**
- Create: `assets/css/huta-v2.css` (token, base, tombol, kartu, utilitas; aturan per komponen menyusul di task berikut)
- Create: `scripts/dev-switch.sh` (`activate` = set huta-studio status 1 + Denava 0; `denava` = sebaliknya; keduanya: artisan cache+view clear + purge nginx)
- Modify: `resources/views/template.blade.php` (inisialisasi dark-mode inline di `<head>`, class body)
- Modify: `resources/views/commons/source_css.blade.php` (link Poppins + `huta-v2.css`)
**Interfaces:**
- Produces: variabel CSS `--hv-*`; fungsi JS global `hvToggleDark()`; skrip `scripts/dev-switch.sh activate|denava`.
- [ ] Step 1 (RED): `scripts/dev-switch.sh activate` lalu uji `curl` → `huta-v2.css` BELUM ada (FAIL diharapkan)
- [ ] Step 2: implement token CSS + include file + dark-init + dev-switch
- [ ] Step 3 (GREEN): `dev-switch.sh activate`; uji: `huta-v2.css` tampil 1×, `hvToggleDark` ada, `view:clear` OK; `dev-switch.sh denava`
- [ ] Step 4: commit `feat(huta-studio): fondasi token CSS + mode gelap + skrip dev-switch`

### Task 2: Navbar modern
**Files:**
- Modify: `resources/views/commons/header.blade.php` (tulis ulang: navbar sticky 56px, logo+nama desa, menu dari `$main_menu` (struktur yang sudah dipakai esensi), toggle dark, tombol LOGIN `/siteman`, tombol hamburger mobile)
- Modify: `assets/css/huta-v2.css` (gaya navbar + drawer)
**Interfaces:**
- Consumes: `hvToggleDark()` (Task 1), `$desa`, `$main_menu` (variabel esensi yang sudah dirender header lama).
- Produces: markup navbar dengan class `hv-nav` (+ `.hv-nav.scrolled` di-handle JS kecil inline).
- [ ] Step 1 (RED): aktifkan; uji `hv-nav` tampil? FAIL
- [ ] Step 2: implement header + CSS
- [ ] Step 3 (GREEN): `hv-nav` ≥1, `hv-dark-toggle` ≥1, LOGIN `/siteman` ada, logo desa tampil; kembali Denava
- [ ] Step 4: commit `feat(huta-studio): navbar modern sticky + drawer + toggle dark`

### Task 3: Hero (sapaan, search, shortcut pintu, running text)
**Files:**
- Create: `resources/views/partials/hero.blade.php`
- Modify: `resources/views/layouts/{full-content,left-sidebar,right-sidebar}.blade.php` (include hero saat `$is_home`; hapus include `portal` lama — shortcut pindah ke hero)
- Modify: `resources/views/template.blade.php` (definisikan `$is_home`)
- Modify: `assets/css/huta-v2.css`
- Modify: `config.json` (tambah `hero_greeting`, `hero_sub`)
**Interfaces:**
- Consumes: config `pintu_*`, `foto_hero`/`latar_website`, query artikel terbaru untuk running text (pindahkan dari portal V1), validasi URL V1.
- Produces: `<header class="hv-hero">` dengan `form GET name="cari"`, grid `.hv-shortcut` (≤6 pintu aktif), `.hv-marquee` (judul 3 artikel terbaru).
- [ ] Step 1 (RED): aktifkan; beranda tanpa `hv-hero`? FAIL
- [ ] Step 2: implement hero + config + layouts
- [ ] Step 3 (GREEN): beranda `hv-hero`≥1 + `hv-shortcut`≥1 + `name="cari"`≥1 + `hv-marquee`≥1; `/pembangunan` `hv-hero`=0; `/?cari=desa` `hv-hero`=0; wa.me tetap OK
- [ ] Step 4: commit `feat(huta-studio): hero modern — sapaan, search, shortcut pintu, running text`

### Task 4: Statistik counter animasi
**Files:**
- Create: `resources/views/partials/statistik-home.blade.php` (include dari `full-content` saat beranda)
- Modify: `assets/css/huta-v2.css`; JS kecil inline (IntersectionObserver → count-up) di partial
**Interfaces:**
- Consumes: DB `penduduk_hidup` (count total/L/P; KK dari `config`/`keluarga` — pakai query aman try/catch, idem V1).
- Produces: `.hv-stats` dengan 4 `.hv-stat-num[data-target]` + label; fallback "data belum diisi".
- [ ] Step 1 (RED): aktifkan; `hv-stats` belum ada? FAIL
- [ ] Step 2: implement
- [ ] Step 3 (GREEN): `hv-stats`≥1, `data-target`≥4, null-safe teruji (angka ≥0); kembali Denava
- [ ] Step 4: commit `feat(huta-studio): section statistik counter animasi null-safe`

### Task 5: Feed berita modern
**Files:**
- Create: `resources/views/partials/berita-home.blade.php` (include beranda)
- Modify: `assets/css/huta-v2.css`; hapus sisa query strip lama dari partial portal bila masih ada
**Interfaces:**
- Consumes: `DB::table('artikel')->where('enabled',1)->orderBy('tgl_upload','desc')` (kolom terverifikasi V1), `gambar_artikel`/path gambar esensi bila tersedia (fallback ikon).
- Produces: `.hv-news` grid ≤6 kartu (gambar, judul, `tgl_indo(tgl_upload)`, excerpt `excerpt()`/substr), link kategori + "Lihat Semua".
- [ ] Step 1 (RED): aktifkan; `hv-news` belum ada? FAIL
- [ ] Step 2: implement
- [ ] Step 3 (GREEN): `hv-news`≥1, `hv-news-card`≥1, tanggal tampil; kembali Denava
- [ ] Step 4: commit `feat(huta-studio): feed berita kartu + tanggal + excerpt`

### Task 6: Footer modern + bottom-nav mobile
**Files:**
- Modify: `resources/views/commons/footer.blade.php` (3 kolom: identitas+sosial, alamat, link cepat; class `hv-footer`; `display:none <1024px`)
- Create: `resources/views/partials/bottom-nav.blade.php`; include di `template.blade.php`
- Modify: `assets/css/huta-v2.css` (bottom-nav fixed glass, FAB tengah, offset WA float `bottom:5.5rem`)
**Interfaces:**
- Consumes: `$desa`, sosial media (struktur footer esensi yang ada), `theme_config('wa_number')` tidak dipakai di bottom-nav.
- Produces: `footer.hv-footer`, `nav.hv-bottom` (5 item, FAB `.hv-fab` → `/layanan-mandiri/masuk`).
- [ ] Step 1 (RED): aktifkan; `hv-footer`/`hv-bottom` belum ada? FAIL
- [ ] Step 2: implement
- [ ] Step 3 (GREEN): `hv-footer`≥1, `hv-bottom`≥1, `hv-fab`≥1 href `/layanan-mandiri/masuk`; kembali Denava
- [ ] Step 4: commit `feat(huta-studio): footer 3 kolom + bottom-nav mobile FAB layanan`

### Task 7: Dark mode menyeluruh
**Files:**
- Modify: `assets/css/huta-v2.css` (pastikan SETiap komponen punya varian `html.dark`)
- Modify: partial bila perlu (ikon toggle moon/sun)
**Interfaces:**
- Consumes: `hvToggleDark()` Task 1; class `hv-nav`, `hv-hero`, `hv-stats`, `hv-news`, `hv-footer`, `hv-bottom` Task 2–6.
- [ ] Step 1 (RED): hitung aturan `html.dark` di CSS — FAIL jika < 15
- [ ] Step 2: lengkapi varian dark semua komponen + ikon toggle
- [ ] Step 3 (GREEN): `html.dark` ≥ 15 aturan; `hv-dark-icon` ada; commit `feat(huta-studio): mode gelap menyeluruh via CSS variables`

### Task 8: Acceptance + cut-over
- [ ] Jalankan seluruh uji Task 1–7 (skrip gabungan `/tmp` atau ulang per perintah)
- [ ] Rollback test: Denava → verifikasi utuh → aktifkan Huta Studio V2 → verifikasi marker + log bersih
- [ ] Perbarui checklist acceptance design doc V2
- [ ] Commit `chore(huta-studio): hasil acceptance V2 + cut-over`; dispatch reviewer segar (diff V1-head..V2-head); perbaiki Critical/Important
