# Perbaikan Halaman /pemerintah — Foto Utuh + Strip Geser HP + Grid Bertingkat

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agar foto 11 perangkat nagori tampil utuh (tanpa potong), halaman `/pemerintah` enak ditelusuri warga di HP (strip geser 2 kartu + cuplikan) dan rapi di layar lebar (grid 2→3→4 kolom).

**Architecture:** Markup kartu tetap dirender JS dari API `internal_api/pemerintah` (tanpa perubahan backend). Perubahan hanya di string template Blade `partials/pemerintah/index.blade.php` + blok CSS kustom `.hv-pm-*` baru di `assets/css/huta-v2.css` (mengikuti pola `.hv-pamong-*` Opsi D yang sudah disetujui).

**Tech Stack:** Laravel Blade (template literal JS + jQuery), CSS kustom, suite uji bash (`check` + grep/python3), verifikasi browser via Playwright MCP (`browser_navigate/resize/evaluate/console_messages`).

**Spec:** Desain disetujui user 2026-10-15. Mockup acuan: `desa/themes/huta-studio/.superpowers/brainstorm/288889-1792047652/content/{hp-layout,hp-scroll,final-desain}.html`. Butir persetujuan: (1) bingkai foto 3:4 `contain` + latar blur; (2) HP <640 px: strip geser Varian 2 (2 kartu utuh + cuplikan ±15%, snap); (3) ≥640 px grid 2 → 3 (≥992) → 4 kolom (≥1200); (4) isi kartu: nama, jabatan kapital emerald, badge semua perangkat ("Hadir di Kantor" emerald / "Belum Rekam Kehadiran" abu, seragam beranda); (5) media sosial hanya platform dengan link terisi; (6) aksesibel: `loading="lazy"` dipertahankan, strip bisa digeser keyboard; (7) rilis: bump versi, pindai tema, bersihkan cache, uji penuh, verifikasi browser, commit + ledger. **Tanpa kirim screenshot ke Telegram** (permintaan user) — screenshot cukup lokal.

## Global Constraints
- Ambang kolom: strip geser hanya <640 px; grid 2 kolom ≥640 px, 3 kolom ≥992 px, 4 kolom ≥1200 px (breakpoint `lg` tema = 1024 px, TIDAK dipakai).
- Bingkai foto: `aspect-ratio: 3/4`, radius 14 px, foto utama `object-fit: contain`, backdrop = foto sama dengan `object-fit: cover; filter: blur(16px) saturate(1.25); transform: scale(1.18)`.
- Peek strip: `flex: 0 0 calc((100% - 10px)/2*0.85)` + `scroll-snap-align: start`; container `scroll-snap-type: x mandatory`.
- Teks Indonesia persis: hint `geser ke kiri →`, penghitung `<n> perangkat`, badge `Hadir di Kantor` / `Belum Rekam Kehadiran`.
- Palet: emerald `#047857`, latar badge `#ecfdf5` border `#a7f3d0`, abu `#f3f4f6`/`#6b7280`/`#e5e7eb`, kartu putih radius 20 px + `box-shadow: 0 8px 22px rgba(2,32,58,.12)` + strip gradien `#047857 → #f59e0b`.
- DIPERTAHANKAN: `id="pemerintah-list"` (dipakai `initPagination`), atribut NIP (`pamong_niap`), logika badge `kehadiran == 1 || hari_libur == false`.
- Versi tema: `2610.2.0 → 2610.3.0` (theme.json + composer.json).
- Jangan cetak token/password ke chat; wajib membaca hasil verifikasi sebelum klaim lulus (verification-before-completion).

## Review Focus
- Nama panjang ("LIDO VICKY PRIANGAN MANALU", "RAINOL SPOKHESMAN SILALAHI") di strip 2 kolom HP → uji tinggi kartu seragam & tidak ada teks meluber (Task 3, assert `scrollHeight` kartu seragam).
- Foto placeholder (`kuser.png`/`wuser.png`, rasio beda dari foto asli) → rasio bingkai wajib 0,75 untuk SEMUA kartu (Task 3, assert rasio per kartu).
- Strip geser + paginasi API (`per_page` 30) → bila nanti >30 perangkat tiap halaman punya strip sendiri; hint tetap tampil di HP (catatan, bukan bug) (Task 3).
- Urutan media query di CSS saling menimpa → urutan 640/992/1200 wajib naik; diuji statis (Task 2) + computed style 3 lebar (Task 3).
- Template literal JS baru rawan salah escape (backtick/`${}`) → uji 0 galat konsol + kartu tetap ter-render 11 (Task 3).

---

### Task 1: Markup kartu & strip di Blade

**Files:**
- Modify: `desa/themes/huta-studio/resources/views/partials/pemerintah/index.blade.php` — wadah grid (baris 16-17), loop `mediaSosial` (baris 43-54 JS), template kartu `pemerintahHTML` (baris 56-80).
- Test: `/var/www/html/opensid/.superpowers/sdd/2026-10-15-pemerintah/test-task1.sh` (pola `check`/`skip` persis `test-task8.sh`; buat direktori + `test-all.sh` loop `1`).

**Interfaces:**
- Consumes: API `internal_api/pemerintah` — atribut: `nama`, `nama_jabatan`, `foto` (URL), `status_kehadiran` (`'hadir'` | teks), `hari_libur` (bool), `kehadiran` (int), `media_sosial` (objek 6 platform, string `''` bila kosong).
- Produces: nama kelas CSS yang didefinisikan Task 2 — `.hv-pm-gridwrap`, `.hv-pm-grid`, `.hv-pm-hint`, `.hv-pm-arrow`, `.hv-pm-count`, `.hv-pm-card`, `.hv-pm-media`, `.hv-pm-blur`, `.hv-pm-foto`, `.hv-pm-info`, `.hv-pm-nama`, `.hv-pm-jabatan`, `.hv-badge`, `.hv-badge--hadir`, `.hv-badge--belum`, `.hv-pm-sosmed`.

- [x] **Step 1: Tulis test-task1.sh** — daftar check (bash, pola `check "deskripsi" "eval"`):
  - wadah: ada `.hv-pm-gridwrap` membungkus `id="pemerintah-list"`; div list memakai `hv-pm-grid` dan **tidak lagi** `grid-cols-1`/`lg:grid-cols-4` (python: cek `hv-pm-grid` ada di baris yang sama `id="pemerintah-list"`; `! grep -q 'lg:grid-cols-4'` file).
  - hint: ada `.hv-pm-hint` dengan `aria-hidden="true"`, teks `geser ke kiri`, dan `.hv-pm-count` yang diisi JS: `grep -q "hv-pm-count" ... && grep -q "perangkat'" ...`.
  - kartu: template punya `.hv-pm-card`, `.hv-pm-media`, `.hv-pm-info`; **tidak lagi** `h-44 w-full object-cover` (`! grep -q 'object-cover'`).
  - foto: dua `<img>` dalam media — `.hv-pm-blur` dengan `alt=""` + `aria-hidden="true"` (python regex, gaya Task 8) dan `.hv-pm-foto` dengan `alt="Foto ${...nama}"`; keduanya mempertahankan `loading="lazy"` (check terpisah, python hitung `loading="lazy"` ≥ 2 di template).
  - teks: `.hv-pm-nama` mempertahankan `text-h6`; `.hv-pm-jabatan` memakai `block`; blok NIP `pamong_niap` tetap ada.
  - badge: ada `hv-badge--hadir` untuk `'hadir'` dengan teks `Hadir di Kantor`, `hv-badge--belum` untuk selainnya; **tidak lagi** `btn btn-primary`/`btn-danger`/`>Hadir<`.
  - sosmed: loop memakai guard `if (!link)` (python: `link` dicek sebelum `+=`); anchor memakai `hv-pm-sosmed` + `rel="noopener"`; **tidak lagi** `bg-blue-600`.
- [x] **Step 2: Jalankan → FAIL semua** — `bash /var/www/html/opensid/.superpowers/sdd/2026-10-15-pemerintah/test-task1.sh` (ekspektasi: semua `FAIL`, exit 1).
- [x] **Step 3: Implementasi Blade** — perubahan persis:
  1. Ganti div grid baris 16 menjadi:
     ```html
     <div class="hv-pm-gridwrap">
         <div class="hv-pm-grid py-1" id="pemerintah-list"></div>
         <div class="hv-pm-hint" aria-hidden="true">
             <span class="hv-pm-count"></span>
             <span class="hv-pm-arrow">geser ke kiri&nbsp;→</span>
         </div>
     </div>
     ```
  2. Di callback sukses `$.get` (setelah `initPagination(data);`) tambahkan: `$('.hv-pm-count').text(data.data.length + ' perangkat');`
  3. Ganti isi loop `mediaSosialPlatforms.forEach` → tambah guard `if (!link) { return; }` dan anchor baru: `<a href="${link}" target="_blank" rel="noopener" class="hv-pm-sosmed"><i class="fab fa-lg fa-${platform}"></i></a>` (hapus inline `bg-blue-600 h-8 w-8 rounded-full` dan `style="color:#fff"`).
  4. Ganti template kartu (pertahankan escape & `${}`):
     ```
     <div class="hv-pm-card">
         <div class="hv-pm-media">
             <img class="hv-pm-blur" loading="lazy" src="${item.attributes.foto || ''}" alt="" aria-hidden="true" />
             <img class="hv-pm-foto" loading="lazy" src="${item.attributes.foto || ''}" alt="Foto ${item.attributes.nama}" />
         </div>
         <div class="hv-pm-info text-center">
             <span class="hv-pm-nama text-h6">${item.attributes.nama}</span>
             <span class="hv-pm-jabatan block">${item.attributes.nama_jabatan}</span>
             ${ /* blok NIP dipertahankan apa adanya */ }
             ${ badge: `kehadiran == 1 || hari_libur == false` → `<span class="hv-badge ${hadir ? 'hv-badge--hadir' : 'hv-badge--belum'}">${hadir ? 'Hadir di Kantor' : item.attributes.status_kehadiran}</span>`, selainnya '' }
             <div class="hv-pm-sosmed">${mediaSosial}</div>
         </div>
     </div>
     ```
     (pertahankan `space-y-1`/jarak diatur CSS; logika badge tetap `kehadiran == 1 ? … : hari_libur == false ? … : ''` diubah menjadi kondisi setara dengan penjaga or.)
- [x] **Step 4: Jalankan → PASS** — `bash .../test-task1.sh` (ekspektasi: semua `ok`, exit 0).
- [x] **Step 5: Commit** — `git add desa/themes/huta-studio/resources/views/partials/pemerintah/index.blade.php && git commit -m "feat(pemerintah): markup kartu foto-utuh + strip geser + badge & sosmed palet"` (repo root: `/var/www/html/opensid/desa/themes/huta-studio`).

### Task 2: CSS `.hv-pm-*` di huta-v2.css

**Files:**
- Modify: `desa/themes/huta-studio/assets/css/huta-v2.css` — tambah blok baru di akhir file.
- Test: `/var/www/html/opensid/.superpowers/sdd/2026-10-15-pemerintah/test-task2.sh` + tambah `2` ke loop `test-all.sh`.

**Interfaces:**
- Consumes: nama kelas dari Task 1 (daftar di atas).
- Produces: perilaku visual (diverifikasi Task 3): strip snap+peek <640 px, grid 2/3/4 kolom di 640/992/1200, bingkai 3:4 contain+blur, badge & sosmed palet.

- [x] **Step 1: Tulis test-task2.sh** — check statis: tiap selektor/kelompok properti di bawah ada di CSS (`grep -q` per blok); `python3` cek: (a) media query `min-width: 640px`, `992px`, `1200px` muncul BERURUTAN naik dalam file, (b) `aspect-ratio: 3 / 4` ada di `.hv-pm-media`, (c) `object-fit: contain` di `.hv-pm-foto` dan `blur(16px)` di `.hv-pm-blur`, (d) `prefers-reduced-motion` menjaga `.hv-pm-arrow`, (e) keseimbangan kurung kurawal file (selisih `{`/`}` = 0).
- [x] **Step 2: Jalankan → FAIL** — `bash .../test-task2.sh`.
- [x] **Step 3: Tambah blok CSS berikut di akhir `huta-v2.css`** (nilai persis dari desain yang disetujui):
  ```css
  /* ===== Kartu Pemerintah /pemerintah — foto utuh + strip geser HP ===== */
  .hv-pm-gridwrap { position: relative; }
  .hv-pm-grid { display: flex; gap: 10px; overflow-x: auto; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; padding-bottom: 8px; }
  .hv-pm-grid > * { flex: 0 0 calc((100% - 10px) / 2 * 0.85); scroll-snap-align: start; }
  .hv-pm-grid::-webkit-scrollbar { height: 6px; }
  .hv-pm-grid::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 999px; }
  .hv-pm-grid::-webkit-scrollbar-track { background: #e5e7eb; border-radius: 999px; }
  .hv-pm-card { background: #fff; border-radius: 20px; padding: 12px; box-shadow: 0 8px 22px rgba(2, 32, 58, .12); position: relative; overflow: hidden; }
  .hv-pm-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #047857, #f59e0b); }
  .hv-pm-media { position: relative; aspect-ratio: 3 / 4; overflow: hidden; border-radius: 14px; background: #e5e7eb; }
  .hv-pm-blur { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: blur(16px) saturate(1.25); transform: scale(1.18); }
  .hv-pm-foto { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; }
  .hv-pm-info { margin-top: 10px; }
  .hv-pm-nama { font-weight: 800; color: #0f2d47; line-height: 1.25; }
  .hv-pm-jabatan { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; color: #047857; margin-top: 4px; }
  .hv-badge { display: inline-block; text-align: center; font-size: .72rem; font-weight: 700; padding: 5px 10px; border-radius: 999px; margin-top: 9px; line-height: 1.3; }
  .hv-badge--hadir { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
  .hv-badge--belum { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
  .hv-pm-sosmed { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 999px; background: #047857; color: #fff; margin: 6px 3px 0; }
  .hv-pm-sosmed:hover { background: #065f46; }
  .hv-pm-hint { display: flex; justify-content: space-between; align-items: center; font-size: .72rem; font-weight: 700; color: #6b7280; margin-top: 8px; }
  .hv-pm-arrow { color: #047857; animation: hvpmnudge 1.1s ease-in-out infinite; }
  @keyframes hvpmnudge { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(5px); } }
  @media (prefers-reduced-motion: reduce) { .hv-pm-arrow { animation: none; } }
  @media (min-width: 640px) {
    .hv-pm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; overflow: visible; padding-bottom: 0; }
    .hv-pm-grid > * { flex: none; scroll-snap-align: none; }
    .hv-pm-hint { display: none; }
  }
  @media (min-width: 992px) { .hv-pm-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (min-width: 1200px) { .hv-pm-grid { grid-template-columns: repeat(4, 1fr); } }
  ```
- [x] **Step 4: Jalankan → PASS** — `bash .../test-task2.sh`.
- [x] **Step 5: Commit** — `git add assets/css/huta-v2.css && git commit -m "feat(pemerintah): CSS strip geser HP, grid bertingkat 2/3/4, bingkai 3:4, badge palet"`.

### Task 3: Rilis, uji penuh, verifikasi browser, ledger

**Files:**
- Modify: `desa/themes/huta-studio/theme.json:5` (`2610.2.0 → 2610.3.0`), `desa/themes/huta-studio/composer.json:6` (`v2610.2.0 → v2610.3.0`).
- Create: `/var/www/html/opensid/.superpowers/sdd/2026-10-15-pemerintah/progress.md` (ledger).
- Modify: `desa/themes/huta-studio/docs/plans/2026-10-15-pemerintah-kartu-perangkat.md` (centang checklist selesai).

**Interfaces:**
- Consumes: hasil Task 1-2 + suite lama `/var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-all.sh` (loop 1..8, baseline **154 ok / 0 fail**).
- Produces: tema `v2610.3.0` aktif di produksi; bukti verifikasi tersimpan; ledger berisi entri lengkap.

- [x] **Step 1: Bump versi** — `sed` kedua file; jalankan `bash /var/www/html/opensid/.superpowers/sdd/2026-10-14-huta-studio-v4/test-task7.sh` → tetap pass (ia membandingkan dengan composer.json, bukan angka patok).
- [x] **Step 2: Deploy** — di `/var/www/html/opensid`: `php artisan opensid:pindai-tema`, lalu `bash desa/themes/huta-studio/scripts/dev-switch.sh activate` (sekalian `cache:clear`, `view:clear`, purge `/var/cache/nginx/opensid/*`), lalu `systemctl restart php8.4-fpm`. Verifikasi: `curl -sk -H 'Host: sordangbolon-ujungpadang.desa.id' https://127.0.0.1/pemerintah` → 200 dan `huta-v2.css?themeVersion=2610.3.0` muncul di HTML.
- [x] **Step 3: Suite penuh** — jalankan suite lama (ekspektasi 154 ok / 0 fail) + `bash .../2026-10-15-pemerintah/test-all.sh` (ekspektasi semua pass). Simpan output ke `task-tests.log` di direktori suite baru. **Baca outputnya** sebelum lanjut.
- [x] **Step 4: Verifikasi browser (Playwright MCP)** — `browser_navigate` ke `https://sordangbolon-ujungpadang.desa.id/pemerintah`, lalu:
  - `browser_resize 390x844` + `browser_evaluate`:
    ```js
    () => {
      const grid = document.querySelector('.hv-pm-grid');
      const cards = [...grid.children];
      const hint = document.querySelector('.hv-pm-hint');
      const imgs = [...document.querySelectorAll('#pemerintah-list img')];
      return {
        jumlah: cards.length,                                              // 11
        rasio: cards.map(c => { const r = c.querySelector('.hv-pm-media').getBoundingClientRect(); return +(r.width / r.height).toFixed(2); }), // semua 0.75
        contain: getComputedStyle(document.querySelector('.hv-pm-foto')).objectFit, // 'contain'
        fotoGagal: imgs.filter(i => i.complete && i.naturalWidth === 0).length,     // 0
        strip: grid.scrollWidth > grid.clientWidth,                        // true
        hintTampil: getComputedStyle(hint).display !== 'none',             // true
        tinggiKartu: [...new Set(cards.map(c => c.getBoundingClientRect().height))].length, // 1
        badgeHadir: document.querySelectorAll('.hv-badge--hadir').length,  // 3
        badgeBelum: document.querySelectorAll('.hv-badge--belum').length,  // 8
        sosmed: document.querySelectorAll('#pemerintah-list .hv-pm-sosmed a').length, // 0
      };
    }
    ```
    lalu uji geser: `grid.scrollLeft = 300` → nilai berubah; uji keyboard: fokus grid (`.focus()`) + `browser_press_key ArrowLeft` → `scrollLeft` berkurang.
  - `browser_resize 768x900` → `getComputedStyle(grid).gridTemplateColumns.split(' ').length === 2`; `1000x900` → 3; `1366x900` → 4 dan `hintTampil === false`.
  - `browser_console_messages` → 0 `error`.
  - `browser_take_screenshot` (mobile 390 & desktop 1366) → simpan ke direktori suite; **tidak dikirim ke Telegram**.
- [x] **Step 5: Ledger** — tambah entri di `progress.md` suite baru: tanggal, ringkasan perubahan, hasil uji (jumlah ok), hasil verifikasi browser (angka persis dari Step 4), jebakan (mis. `lg` = 1024 tidak dipakai; test-task7 membandingkan composer.json), dan catatan: `/pemerintah` tidak di-cache nginx (route non-artikel) jadi langsung terlihat — CSS ter-cache di browser pemakai karena URL membawa `themeVersion`.
- [x] **Step 6: Commit akhir** — `git add -A && git commit -m "release: tema v2610.3.0 — kartu /pemerintah foto utuh + strip geser HP + grid bertingkat"`; pastikan `git status` bersih; catat hash di ledger.

### Final review (setelah Task 3)

Reviewer segar atas `d0c613d..8dc5629`: 0 Critical, 1 Important, 5 Minor.

- **Important (diperbaiki):** strip tidak bisa di-Tab di Firefox/Safari (`div` `overflow-x` hanya focusable di Chromium; verifikasi awal memakai `.focus()` paksa). Perbaikan: `tabindex="0" role="group" aria-label="Daftar perangkat nagori, geser ke kiri"` di div list + `aria-hidden="true"` dipindah dari `.hv-pm-hint` ke `<span class="hv-pm-arrow">` agar penghitung "n perangkat" terbaca pembaca layar. Test baru di `test-task1.sh` (2 check) → RED → GREEN; verifikasi browser: grid tercapai setelah **17 tekan Tab**, `activeElement = pemerintah-list`, count terbaca SR; suite **208 ok / 0 fail**.
- **Minor (ditunda, lihat ledger):** (1) interpolasi `${nama}`/`${teksBadge}`/`href="${link}"` tanpa escape helper — pola lama, XSS stok via data admin; (2) cabang `kehadiran == 1` mati karena API mengembalikan objek/null (kelemahan plan, gate badge efektif `hari_libur == false`); (3) hint tetap tampil saat daftar kosong; (4) blok `.hv-pm-*` belum ada varian `html.dark` (hex keras, tetap terbaca tapi mencolok); (5) logika badge `diKantor`/`sudahKeluar` belum dikunci test perilaku (baru grep string +3 kondisi data hari ini).
