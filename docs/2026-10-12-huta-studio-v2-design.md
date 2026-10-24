# Design Doc — Huta Studio V2 (kode sendiri)

- Tanggal: 2026-10-12
- Status: **disetujui** (scope lewat Q&A: gaya langkidi + bottom-nav mobile; Fase A = Denava aktif selama pengembangan)
- Pendahulu: `2026-10-12-huta-studio-design.md` (V1 — semua fitur V1 DIPERTAHANKAN)

## 1. Tujuan
Merestyle Huta Studio agar menyerupai tampilan https://langkidi.desa.id/ (tema Esmo) pada **konsep** — bukan menyalin kode (Esmo = proprietary premium). Seluruh fitur V1 (portal 5 pintu, WA float, pencarian, config.json, null-safe) tetap ada.

## 2. Batasan keras
- **Tanpa** Tailwind CDN, Alpine, jQuery tambahan, atau library carousel. Vanilla JS + CSS.
- Font: **Poppins** via Google Fonts (`display=swap` + preconnect) — sama seperti langkidi.
- VPS 1 core / 2,2 GB: beranda HTML target < 260 KB; Lighthouse mobile ≥ 80 (diukur dari luar VPS).
- Tema induk tetap fork `esensi`; route/data OpenSID tidak diubah; repo tema sendiri (`desa/themes/huta-studio/.git`).
- Selama pengembangan: **Denava yang aktif**; cut-over terakhir.

## 3. Design token
- CSS variables di `:root` (terdefinisi di `huta-v2.css`, dipakai ulang di dark):
  - `--hv-primary` (default `#16a75c`, override dari `theme_config('warna_aksen')`), `--hv-primary-dark`, `--hv-secondary #0f172a`, `--hv-bg #f8fafc`, `--hv-card #ffffff`, `--hv-text #475569`, `--hv-border #e2e8f0`.
- Dark (`html.dark`): `--hv-bg #0f172a`, `--hv-card #1e293b`, `--hv-border #334155`, `--hv-text #cbd5e1`.
- Radius kartu 1rem; bayangan lembut; transisi 0.3s.

## 4. Peta section beranda (atas → bawah)
1. **Navbar** (semua halaman) — `commons/header.blade.php` ditulis ulang: tinggi 56px, transparan di puncak beranda → sticky solid (blur) saat scroll/halaman dalam; kiri: logo 38px + nama desa + kec/kab; kanan: menu desktop (dari `main_menu`), toggle dark, tombol LOGIN (`/siteman`); mobile: drawer geser via `commons/mobile_menu.blade.php` (disesuaikan).
2. **Hero** (hanya beranda) — `partials/hero.blade.php`, tinggi 85vh min 560px: `latar_website`/`foto_hero` config sebagai bg + overlay gradien gelap; konten: sapaan (`hero_greeting`, default "Selamat Datang"), nama desa, **search bar besar** (form GET `/?cari=`), grid **shortcut pintu** (dari config `pintu_*`, termasuk Pidato/Pengumuman?), marquee **running text** berita terbaru di bar bawah hero.
3. **Statistik** (beranda) — `partials/statistik-home.blade.php`: 4 counter animasi (Total, Laki-laki, Perempuan, KK) dari `penduduk_hidup`; "data belum diisi" null-safe; CTA ke `/data-statistik`.
4. **Berita** (beranda) — `partials/berita-home.blade.php`: grid kartu (gambar, judul, tanggal `tgl_indo`, excerpt) dari 6 artikel terbaru + link "Lihat Semua" ke kategori berita; menggantikan strip V1 (data query V1 dipindah ke sini).
5. **Footer** — `commons/footer.blade.php`: 3 kolom (identitas+logo+sosial dari pengaturan OpenSID, alamat kantor, link cepat); **disembunyikan < 1024px** (digantikan bottom-nav).
6. **Bottom-nav mobile** (semua halaman, hanya < 1024px) — `partials/bottom-nav.blade.php`: fixed bar glass 5 item: Beranda `/`, Berita `/artikel/kategori/berita-desa`, **FAB Layanan** (tombol bulat tengah → `/layanan-mandiri/masuk`), Admin `/siteman`, Galeri `/galeri`.
7. **WA float + back-to-top** tetap seperti V1 (di atas bottom-nav: `bottom: 5rem`).

## 5. Dark mode
- Satu mekanisme: class `dark` di `<html>`, disimpan `localStorage['huta_dark']`, fallback `prefers-color-scheme`. Inisialisasi inline di `<head>` (anti flash).
- Semua komponen memakai CSS variables — tanpa patch `!important` massal.
- Toggle: navbar desktop, navbar mobile, (bottom-nav tidak perlu).

## 6. Config.json (penambahan V2)
- `hero_greeting` (teks, default "Selamat Datang")
- `hero_sub` (teks, default motto desa dari `setting('motto_desa')` bila kosong)
- Field V1 lainnya tetap; `latar_website`/`foto_hero` dipakai ulang untuk bg hero.

## 7. Non-goal V3 (tidak dikerjakan sekarang)
Widget sholat, hari nasional, APBDes expandable, lapak marquee, video modal, sinergi program, loading screen Esmo-style, stack slider 3D.

## 8. Acceptance
- [x] Beranda: navbar + hero + shortcut + statistik + berita + footer; semua marker `huta-*` tampil.
- [x] Halaman dalam (`/pembangunan`, `/galeri`, artikel): navbar sticky solid + konten + bottom-nav mobile; TANPA hero/statistik.
- [x] Pencarian `/?cari=` menampilkan hasil tanpa hero portal.
- [x] Dark mode toggle berfungsi & persisten (script + CSS `html.dark` ada).
- [x] WA float tetap; tautan wa.me valid.
- [x] Null-safe: "data belum diisi" bila data kosong; nol error log PHP.
- [x] Rollback: Denava bisa diaktifkan kembali utuh.
- [x] Tidak ada file inti OpenSID yang dimodifikasi.
