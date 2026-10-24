# Huta Studio V1 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun tema OpenSID reusable "Huta Studio" (portal transparansi desa) dengan mem-fork tema resmi GPL **esensi**, tanpa menyentuh file inti OpenSID.

**Architecture:** Fork `storage/app/themes/esensi` → `desa/themes/huta-studio` (namespace `theme::`, helper `theme_config()`/`theme_asset()`/`theme_version()` bawaan core). Beranda portal = partial `portal.blade.php` yang di-inject di layout saat `uri_string() === ''`. Pengaturan per desa sepenuhnya via `config.json` → menu admin Tema → Pengaturan. Data kartu pintu dari tabel OpenSID via `DB::table()` dengan fallback "data belum diisi". Situs live tetap Denava sampai Task 7 (aktivasi + acceptance).

**Tech Stack:** Blade (OpenSID v2610), Tailwind-style classes milik esensi (dipertahankan), CSS kustom minimal `huta.css`, Font Awesome bawaan, MariaDB via `DB::table`.

**Spec:** `/var/www/html/opensid/desa/themes/huta-studio/docs/2026-10-12-huta-studio-design.md`

## Global Constraints

- **Tidak ada file inti OpenSID yang dimodifikasi** — `git status` di `/var/www/html/opensid` harus hanya menampilkan `desa/themes/huta-studio/**`.
- Lisensi turunan: **GPL-3.0-or-later** (ikuti esensi); `composer.json` wajib mempertahankan license.
- Semua tampilan berbahasa Indonesia; tanggal format WIB contoh "5 Okt 2026".
- Setiap kali mengubah view: jalankan `php artisan view:clear` (cwd `/var/www/html/opensid`).
- Akses DB: `sudo mysql db_opensid` (hanya SELECT untuk pengembangan; tanpa INSERT/UPDATE/DELETE di plan ini).
- Commit per task, hanya file tema: `git -C /var/www/html/opensid add desa/themes/huta-studio && git commit -m "..."`.
- Baca opsi tema dari blade dengan `theme_config('<key>')` (terverifikasi dipakai esensi).
- Target performa: Lighthouse mobile ≥ 80 beranda (pengukuran lokal dengan `CHROME_PATH=/tmp/opencode/chrome/chrome-headless-shell/linux-155.0.8059.39/chrome-headless-shell-linux64/chrome-headless-shell npx lighthouse <url> --only-categories=performance --form-factor=mobile --screenEmulation.mobile --chrome-flags="--headless --no-sandbox --disable-gpu"`).
- Rollback selalu tersedia: admin Tema → aktifkan kembali **Denava** (satu klik).

## Review Focus

1. **Aktivasi tema** — hanya satu tema aktif; pengujian live wajib punya jalur rollback Denava (Task 7 menguji rollback sekali penuh).
2. **Deteksi beranda** — portal hanya boleh muncul di `/` (uji: `/` tampil portal, `/artikel` dan `/ppid` tidak).
3. **Tabel kosong** — `pembangunan` berisi 0 baris di situs demo; kartu wajib menampilkan "data belum diisi" tanpa exception (uji curl setelah aktivasi).
4. **Tautan wa.me** — nomor tanpa `+`, pesan ter-encode URL; uji grep href + klik manual.
5. **Aset tema tidak aktif** — selama pengembangan (Denava aktif) perubahan aset Huta tidak boleh mempengaruhi halaman live; uji homepage tetap 200 & tak ada error log per task.

---

### Task 1: Fork & scaffold Huta Studio dari esensi

**Files:**
- Create: salinan penuh `desa/themes/huta-studio/**` (dari `storage/app/themes/esensi/**`)
- Modify: `desa/themes/huta-studio/composer.json`, `desa/themes/huta-studio/resources/views/template.blade.php`, semua file view yang memuat string `esensi`
- Create: `desa/themes/huta-studio/assets/thumbnail/preview-1.jpg` (salin dari esensi, ganti nanti)

**Interfaces:**
- Consumes: struktur resmi esensi (95 blade: `template.blade.php`, `layouts/{full-content,left-sidebar,right-sidebar}.blade.php`, `commons/*`, `partials/*`, `widgets/*`)
- Produces: tema lengkap `theme_version('huta-studio')`; path tema `desa/themes/huta-studio/`; seluruh task berikutnya bekerja di folder ini

- [ ] **Step 1: Salin esensi ke lokasi tema**

```bash
cp -a /var/www/html/opensid/storage/app/themes/esensi/. /var/www/html/opensid/desa/themes/huta-studio/
```

- [ ] **Step 2: Ganti identitas**

Ubah `composer.json`: `name` → `themes/huta-studio`, tambah deskripsi "Tema portal transparansi desa", pertahankan `license` GPL-3.0-or-later, `authors` tambah "Huta Studio contributors" sebelum author asal (credit fork), `version` → `v2610.0.0`.

Ganti di seluruh file tema: `theme_version('esensi')` → `theme_version('huta-studio')` (grep dulu: `grep -rn "esensi" desa/themes/huta-studio --include="*.php" --include="*.json" --include="*.js" --include="*.css"`) — hanya ganti identitas tema, JANGAN ganti path aset relatif atau teks sembarang.

- [ ] **Step 3: Verifikasi scaffold**

```bash
grep -rn "theme_version('esensi')" /var/www/html/opensid/desa/themes/huta-studio && echo "SISA — GAGAL" || echo "OK bersih"
find /var/www/html/opensid/desa/themes/huta-studio/resources/views -name "*.blade.php" | wc -l   # ekspektasi: 95
cd /var/www/html/opensid && php artisan view:clear
curl -sk -o /dev/null -w "%{http_code}" -H "Host: sordangbolon-ujungpadang.desa.id" https://127.0.0.1/   # ekspektasi: 200 (Denava masih aktif, tak terpengaruh)
```

- [ ] **Step 4: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): fork tema esensi sebagai kerangka Huta Studio"
```

---

### Task 2: config.json (dashboard pengaturan) + README

**Files:**
- Create: `desa/themes/huta-studio/config.json`
- Create: `desa/themes/huta-studio/README.md`

**Interfaces:**
- Consumes: mekanisme bawaan OpenSID — `config.json` array field {judul,key,value,type,keterangan,options,attributes,readonly}; dibaca dari blade dengan `theme_config('<key>')`
- Produces key (dipakai Task 3–5): `wa_enable`, `wa_number`, `wa_message`, `wa_label`, `judul_hero`, `subjudul_hero`, `foto_hero` (type unggah), `warna_aksen` (color-picker), `pintu_<x>_enable` + `pintu_<x>_url` + `pintu_<x>_angka` + `pintu_<x>_updated` untuk `<x>` ∈ {anggaran, penduduk, bantuan, pembangunan, dokumen}

- [ ] **Step 1: Tulis config.json**

Array JSON berisi field lengkap di atas. Default: `wa_enable=1`, `wa_number=""`, `wa_message="Assalamualaikum, saya warga ingin bertanya soal layanan desa."`, `wa_label="Chat Desa"`, `judul_hero` = "Selamat Datang", `subjudul_hero` = "Informasi terbuka untuk warga", `warna_aksen` = "#0f766e", `pintu_*_enable=1`, `pintu_*_url` = ("", pakai default rute), `pintu_*_angka`="", `pintu_*_updated`="".
Default URL rute (dipakai saat `pintu_*_url` kosong): anggaran="", penduduk="/data-statistik", bantuan="/data-statistik/bantuan_penduduk", pembangunan="/pembangunan", dokumen="/ppid".
`keterangan` berbahasa Indonesia, `attributes.class="select2"` untuk select.

- [ ] **Step 2: Tulis README.md**

Cara pasang di desa lain: salin folder ke `desa/themes/`, unggah thumbnail, aktifkan di admin Tema → Pengaturan, isi WA & pintu, data pintu mengikuti pengisian modul OpenSID masing-masing desa. Catatan privasi bansos agregat. Contoh field config.

- [ ] **Step 3: Verifikasi**

```bash
python3 -m json.tool /var/www/html/opensid/desa/themes/huta-studio/config.json > /dev/null && echo "JSON OK"
python3 -c "import json; ks=[i['key'] for i in json.load(open('/var/www/html/opensid/desa/themes/huta-studio/config.json'))]; print(len(ks),'key'); print('OK' if all(k in ks for k in ['wa_number','warna_aksen','pintu_anggaran_url','pintu_dokumen_updated']) else 'KURANG')"
```

- [ ] **Step 4: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): config.json pengaturan tema + README"
```

---

### Task 3: Gaya global (huta.css, header, footer, warna aksen)

**Files:**
- Create: `desa/themes/huta-studio/assets/css/huta.css`
- Modify: `desa/themes/huta-studio/resources/views/commons/source_css.blade.php` (tambahkan link huta.css + cache-bust `?v={{ theme_version('huta-studio') }}`)
- Modify: `desa/themes/huta-studio/resources/views/commons/header.blade.php`, `commons/footer.blade.php` (rapikan struktur/kelas mengikuti desain; pertahankan logika menu & data)
- Modify: `desa/themes/huta-studio/resources/views/template.blade.php` (terapkan CSS variable `--huta-aksen` dari `theme_config('warna_aksen')` di tag `<body>` atau `:root` inline minimal)

**Interfaces:**
- Consumes: `theme_config('warna_aksen')`; aset esensi yang ada (font-awesome via source_css)
- Produces: variabel CSS `--huta-aksen`; kelas utilitas `.huta-btn` (sentuh ≥48px); tampilan header/footer seragam untuk semua task berikutnya

- [ ] **Step 1: Buat huta.css**

CSS ringan (<8 KB): reset ringan, `:root{--huta-aksen: ...}` akan di-override inline, gaya tombol besar `.huta-btn`, kartu `.huta-kartu` (radius, shadow tipis), tipografi (body ≥16px). Tanpa import font eksternal.

- [ ] **Step 2: Sambungkan ke source_css + aksen + header/footer**

`source_css.blade.php`: tambah `<link rel="stylesheet" href="{{ theme_asset('css/huta.css') }}&v={{ theme_version('huta-studio') }}" />` setelah css bawaan. `template.blade.php`: `style="--huta-aksen: {{ theme_config('warna_aksen') ?: '#0f766e' }}"` pada `<body>`. Header/footer: terapkan kelas huta (tanpa mengubah fungsi pencarian/menu yang ada — pencarian header khusus di Task 6).

- [ ] **Step 3: Verifikasi**

```bash
cd /var/www/html/opensid && php artisan view:clear
test -f desa/themes/huta-studio/assets/css/huta.css && grep -q "huta.css" desa/themes/huta-studio/resources/views/commons/source_css.blade.php && echo OK
curl -sk -o /dev/null -w "%{http_code}" -H "Host: sordangbolon-ujungpadang.desa.id" https://127.0.0.1/   # 200, Denava tak terpengaruh
```

- [ ] **Step 4: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): gaya global huta.css + warna aksen"
```

---

### Task 4: Blok portal beranda (strip pengumuman + 5 kartu pintu)

**Files:**
- Create: `desa/themes/huta-studio/resources/views/partials/portal.blade.php`
- Modify: `desa/themes/huta-studio/resources/views/layouts/full-content.blade.php`, `layouts/left-sidebar.blade.php`, `layouts/right-sidebar.blade.php` (inject portal di atas `@yield('content')`, hanya saat `uri_string() === ''`)

**Interfaces:**
- Consumes: `theme_config('pintu_*')`, `theme_config('judul_hero')`, `theme_config('subjudul_hero')`, `theme_config('foto_hero')`; `DB::table(...)`; helper `site_url()`, `setting('logo')`/variabel `$desa` (tersedia di layout — verifikasi saat implementasi dengan grep `logo` di commons/header esensi; fallback: gambar dari `foto_hero` config saja)
- Produces: partial `theme::partials.portal`; data kartu: `[$judul, $ikon_fa, $url, $angka_teks, $updated_teks]`

- [ ] **Step 1: Tulis portal.blade.php (logika kartu)**

Untuk tiap pintu, bangun array kartu:
- `anggaran`: url = `theme_config('pintu_anggaran_url')` (kosong → disembunyikan link, teks "tautan belum diatur"), angka = `theme_config('pintu_anggaran_angka')` (kosong → "data belum diisi"), updated = `theme_config('pintu_anggaran_updated')` (kosong → "—"), ikon `fa-balance-scale`
- `penduduk`: url default `/data-statistik`; angka = `DB::table('penduduk_hidup')->count()` dalam try/catch (exception/kosong → "data belum diisi"); updated = `DB::table('penduduk_hidup')->max('updated_at')` format `tgl_indo()` atau config; ikon `fa-users`
- `bantuan`: url default `/data-statistik/bantuan_penduduk`; angka = `DB::table('program_peserta')->count()` try/catch; ikon `fa-hand-holding-heart`
- `pembangunan`: url default `/pembangunan`; angka = `DB::table('pembangunan')->count()` try/catch (0 → "data belum diisi"); ikon `fa-hard-hat`
- `dokumen`: url default `/ppid`; angka = `DB::table('dokumen')->count()` try/catch; ikon `fa-folder-open`

Override `pintu_*_url`/`pintu_*_angka`/`pintu_*_updated` dari config bila terisi. Sembunyikan kartu bila `pintu_*_enable` = "0".
Strip pengumuman: 1 artikel terbaru (`DB::table('artikel')->orderBy('created_at','desc')->limit(1)` try/catch) sebagai tautan judul + tanggal; kosong → sembunyikan strip.
Hero: `judul_hero`, `subjudul_hero`, `foto_hero` (unggah config; kosong → tanpa gambar).

- [ ] **Step 2: Inject di ketiga layout**

Pola di tiap `layouts/*.blade.php` (sebelum `<main>`/`@yield('content')`):

```blade
@if (uri_string() === '')
    @include('theme::partials.portal')
@endif
```

- [ ] **Step 3: Verifikasi (pre-aktivasi)**

```bash
cd /var/www/html/opensid && php artisan view:clear
grep -c "partials.portal" desa/themes/huta-studio/resources/views/layouts/*.blade.php   # ekspektasi: 3
curl -sk -o /dev/null -w "%{http_code}" -H "Host: sordangbolon-ujungpadang.desa.id" https://127.0.0.1/   # 200 (Denava)
```

- [ ] **Step 4: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): portal beranda — strip pengumuman + 5 kartu pintu null-safe"
```

---

### Task 5: Tombol WA floating

**Files:**
- Create: `desa/themes/huta-studio/resources/views/partials/wa-floating.blade.php`
- Modify: `desa/themes/huta-studio/resources/views/template.blade.php` (include sebelum `</body>`)
- Modify: `desa/themes/huta-studio/assets/css/huta.css` (gaya tombol mengambang: fixed kanan-bawah, ≥48px, z-index aman di atas sticky)

**Interfaces:**
- Consumes: `theme_config('wa_enable')`, `theme_config('wa_number')`, `theme_config('wa_message')`, `theme_config('wa_label')`
- Produces: `<a class="huta-wa" href="https://wa.me/{nomor}?text={pesan}">` — monoton `https://wa.me/` (tanpa `+`, spasi, atau `0` di depan sudah tanggung jawab admin; tema hanya `preg_replace('/[^0-9]/','',$nomor)`)

- [ ] **Step 1: Tulis partial wa-floating.blade.php**

Logic: jika `wa_enable` != "1" atau nomor kosong → render kosong. URL: `https://wa.me/` . nomor bersih . (pesan ? `?text=` . urlencode(pesan) : ''). Ikon `fa-whatsapp`, `target="_blank" rel="noopener"`, `aria-label` = label config.

- [ ] **Step 2: Include + CSS**

`template.blade.php`: `@include('theme::partials.wa-floating')` sebelum `</body>`. CSS `.huta-wa` di huta.css.

- [ ] **Step 3: Verifikasi**

```bash
cd /var/www/html/opensid && php artisan view:clear
grep -q "wa-floating" desa/themes/huta-studio/resources/views/template.blade.php && echo OK
```

- [ ] **Step 4: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): tombol WA floating semua halaman"
```

---

### Task 6: Kotak pencarian di header

**Files:**
- Modify: `desa/themes/huta-studio/resources/views/commons/header.blade.php` (form GET ke `/` dengan `name="cari"` — terverifikasi: pencarian publik OpenSID = `/?cari=<q>`)
- Modify: `desa/themes/huta-studio/assets/css/huta.css` (gaya form cari desktop & mobile)

**Interfaces:**
- Consumes: route pencarian bawaan `GET /?cari=`
- Produces: `<form method="get" action="{site_url('/')}" ...><input name="cari" ...>` di header semua halaman

- [ ] **Step 1: Tambahkan form pencarian** di header (desktop: di sekitar menu; mobile: di bawah logo/ menu drawer — minimal: terlihat & berfungsi di keduanya, placeholder "Cari informasi...").
- [ ] **Step 2: Verifikasi**

```bash
cd /var/www/html/opensid && php artisan view:clear
grep -q 'name="cari"' desa/themes/huta-studio/resources/views/commons/header.blade.php && echo OK
```

- [ ] **Step 3: Commit**

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "feat(huta-studio): kotak pencarian header (/?cari=)"
```

---

### Task 7: Aktivasi, acceptance suite, cut-over final

**Files:**
- Modify: `desa/themes/huta-studio/README.md` (catatan hasil uji)
- Modify: `desa/themes/huta-studio/docs/2026-10-12-huta-studio-design.md` (centang acceptance)

**Interfaces:**
- Consumes: Task 1–6; admin Tema bawaan OpenSID (menu Tampilan/Tema → pilih Huta Studio → Aktifkan); rollback = aktifkan Denava
- Produces: Huta Studio tema aktif di situs demo (setelah lulus); bukti hasil uji

- [ ] **Step 1: Backup cepat keadaan**

```bash
tar -czf /root/backups/theme-state-$(date +%F-%H%M).tgz -C /var/www/html/opensid desa/themes/huta-studio 2>/dev/null
sudo mysql db_opensid -e "select slug,status from theme;" > /root/backups/theme-status-before-huta.txt
```

- [ ] **Step 2: Aktifkan Huta Studio** (admin Tema → Huta Studio → Aktifkan; atau via UI admin bila kredensial tersedia — JANGAN UPDATE langsung tabel theme tanpa memahami kolom; prefer UI admin).

- [ ] **Step 3: Acceptance suite** (semua ekspektasi tercantum; catat hasilnya):

```bash
H="Host: sordangbolon-ujungpadang.desa.id"
for p in "" "artikel" "data-statistik/bantuan_penduduk" "pembangunan" "ppid" "galeri"; do
  printf "/%s -> " "$p"; curl -sk --max-time 15 -o /tmp/huta-page.html -w "%{http_code}" -H "$H" "https://127.0.0.1/$p"
  grep -ciE "huta-wa|Error|exception|whoops" /tmp/huta-page.html | tr '\n' ' '; echo
done
curl -sk -H "$H" https://127.0.0.1/ | grep -oE 'href="https://wa.me/[0-9]+\?text=[^"]*"' | head -1   # wa.me benar
curl -sk -H "$H" https://127.0.0.1/ | grep -c 'name="cari"'   # ≥1
curl -sk -H "$H" https://127.0.0.1/ppid | grep -c "huta-kartu\|huta-wa"   # layout aktif di halaman non-home
curl -sk -H "$H" https://127.0.0.1/artikel | grep -c "huta-kartu"   # ekspektasi: 0 (portal hanya di beranda)
curl -sk -H "$H" https://127.0.0.1/ | grep -c "data belum diisi"   # ≥1 (pembangunan=0)
tail -50 /var/www/html/opensid/storage/logs/opensid-$(date +%Y-%m-%d).log | grep -ciE "error|exception"   # ekspektasi: tidak ada error baru terkait tema
```

Lighthouse mobile homepage: jalankan perintah Global Constraints; catat skor (target ≥80; bila <80 — optimasi aset/JS di huta.css & partial, ulangi).

Uji visual manual (Playwright atau browser): homepage desktop & mobile-width 360px — kartu pintu, strip pengumuman, tombol WA, pencarian terlihat.

- [ ] **Step 4: Uji rollback**

Aktifkan kembali **Denava** via admin Tema → homepage 200, tampilan Denava pulih (bukti: grep ciri khas Denava/hapus huta-wa dari homepage). Lalu aktifkan kembali **Huta Studio** (cut-over final).

- [ ] **Step 5: Dokumentasi hasil**

Centang acceptance di design doc yang terpenuhi + skor Lighthouse; commit.

```bash
git -C /var/www/html/opensid add desa/themes/huta-studio
git -C /var/www/html/opensid commit -m "chore(huta-studio): hasil acceptance & cut-over V1"
```
