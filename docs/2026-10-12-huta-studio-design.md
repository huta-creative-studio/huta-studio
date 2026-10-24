# Huta Studio — Dokumen Desain Tema OpenSID

- Tanggal: 2026-10-12
- Status: Disetujui pengguna (brainstorming + builder-check selesai)
- Situs demo: `sordangbolon-ujungpadang.desa.id` (OpenSID v2610.0.0, PHP 8.4, nginx, VPS 1 core / 2,2 GB)
- Lokasi tema: `/var/www/html/opensid/desa/themes/huta-studio/`

## 1. Intent & Why

Warga desa sulit menemukan informasi transparansi (anggaran, bantuan sosial, pembangunan, dokumen resmi) karena tersembunyi di submenu atau situs berat/berlisensi. **Huta Studio** adalah tema OpenSID reusable multi-desa yang mengangkat informasi terpenting ke beranda, berbahasa Indonesia sederhana, ringan, dan mobile-first — supaya masyarakat desa (segala usia, HP murah, bandwidth seadanya) langsung menemukannya tanpa "mencari".

Tema ini menggantikan kebutuhan akan tema berbayar/terproteksi dengan menjadikan situs ini demo sekaligus laboratorium.

## 2. Goals (V1)

1. **Beranda portal transparansi** dengan strip pengumuman + berita terbaru, lalu **5 pintu kartu besar**:
   - Realisasi Anggaran (sumber: artikel laporan APBDes / halaman pembangunan / GIS — dapat dikonfigurasi)
   - Statistik Penduduk → `/data-statistik` (dan turunannya)
   - Bantuan Sosial → `/data-statistik/bantuan_penduduk` (**agregat saja, tanpa nama/NIK**)
   - Pembangunan → `/pembangunan`
   - Dokumen Publik → `/ppid`
   Tiap pintu: ikon, angka terkini sekilas (null-safe: "data belum diisi"), label **"Diperbarui: &lt;tanggal&gt;"**, tautan "Lihat selengkapnya".
2. **Berita & pengumuman**: feed artikel terbaru + kategori dari modul Artikel inti.
3. **Pencarian global**: kotak cari di header yang mengarah ke pencarian bawaan OpenSID (rute diverifikasi saat implementasi).
4. **Tombol WhatsApp floating** di semua halaman: deep-link `https://wa.me/&lt;nomor&gt;?text=&lt;pesan&gt;`, dikonfigurasi per desa. Tanpa server, tanpa API.
5. **Dashboard pengaturan otomatis** lewat `config.json` tema → menu admin Tema → Pengaturan (sistem bawaan OpenSID; komponen form tersedia: `input-text`, `input-number`, `input-email`, `input-password`, `select`, `textarea`, `color-picker`, `unggah`).
6. **Reusable multi-desa**: semua konten dari basis data & pengaturan OpenSID masing-masing desa; tidak ada konten hardcode satu desa.
7. **Performa**: target Lighthouse mobile ≥ 80 pada beranda; tanpa library berat; CLS minimal.

## 3. Non-goals & Anti-goals

- **Bukan** menyalin/meniru kode Denava (ionCube-encoded, berbayar) — hanya meniru semangat "gampang dipahami".
- **Tidak** memodifikasi inti OpenSID (terproteksi ionCube; file inti tidak boleh disentuh).
- **Bukan** modul baru: tema hanya membungkus modul yang sudah ada (Artikel, Statistik, Pembangunan, PPID, APBDes).
- **Tanpa** backend baru, API, tabel database tambahan.
- **V2 (ditunda)**: galeri/arsip mendalam, grafik statistik interaktif, layanan surat online, arsitektur PWA.
- Anti-goal privasi: jangan pernah menampilkan nama/NIK penerima bantuan sosial — hanya agregat.

## 4. Constraints

- OpenSID v2610.0.0; PHP 8.4-FPM; nginx; VPS 1 core / 2,2 GB.
- File tema hanya di: `desa/themes/huta-studio/` + aset publik `public/themes/huta-studio/` (ikuti konvensi tema bawaan — builder memutuskan detail).
- Inti & banyak helper OpenSID ter-ionCube-encode: tema hanya memanggil helper/rute/view yang ada & terverifikasi lewat grep/inspeksi runtime — jangan menebak API.
- Bahasa antarmuka: Indonesia. Format tanggal: WIB/DD MMM YYYY.
- Aksesibilitas: font besar (≥16px body), target sentuh ≥48px, kontras memadai, maksimal 1 klik dari beranda ke isi.
- Theme cache OpenSID: setelah ubah template jalankan `php artisan view:clear` + purge `rm -rf /var/cache/nginx/opensid/*`.

## 5. Keputusan Desain (sudah diputuskan)

| Keputusan | Isi |
|---|---|
| Nama & slug | `Huta Studio`, folder `huta-studio`, prefix key `huta_studio_*` |
| Basis | Fork/adjust dari tema & view publik bawaan OpenSID (semua partial modul tersedia) |
| Pintu beranda | 5 pintu (tabel di Goals); URL default dapat di-override admin |
| "Diperbarui" | Utamakan diturunkan dari data OpenSID (mis. created_at terakhir); fallback: teks yang dapat diedit admin via Pengaturan Tema |
| Bansos | Hanya statistik agregat (mis. jumlah penerima per jenis bantuan) |
| Tombol WA | **Floating di semua halaman** (pojok kanan bawah), dapat dimatikan via settingan; deep-link wa.me; multi-kontak ditunda (V1: satu nomor + teks pesan + label tombol) |
| Dashboard | `config.json` tema → halaman Pengaturan Tema bawaan; tanpa controller admin kustom |
| Identitas per-desa | Logo, nama desa, foto hero, warna aksen dari pengaturan OpenSID + config tema |

### Field `config.json` (V1)

- `wa_enable` (select: Tampilkan/Sembunyikan), `wa_number` (input-text, format `628…`), `wa_message` (textarea), `wa_label` (input-text, mis. "Chat Desa")
- `judul_hero` (input-text), `subjudul_hero` (textarea), `foto_hero` (unggah; fallback: foto/foto_logo dari pengaturan desa)
- `warna_aksen` (color-picker)
- `pintu_*_enable` (select; tampil/sembunyikan tiap pintu)
- `pintu_*_url` (input-text; **override URL untuk kelima pintu**: `pintu_anggaran_url`, `pintu_penduduk_url`, `pintu_bantuan_url`, `pintu_pembangunan_url`, `pintu_dokumen_url`)
- `pintu_*_angka` & `pintu_*_updated` (input-text; **khusus pintu Anggaran**: angka terkini & tanggal "Diperbarui" diisi statis admin di V1)

### Aturan angka sekilas per pintu

| Pintu | Sumber angka | Fallback |
|---|---|---|
| Statistik Penduduk | total penduduk dari data statistik OpenSID (bila helper tersedia) | "data belum diisi" |
| Bantuan Sosial | agregat jumlah penerima per jenis bantuan (bila helper tersedia) | "data belum diisi" |
| Pembangunan | jumlah kegiatan/terbit dari modul pembangunan (bila helper tersedia) | "data belum diisi" |
| Dokumen Publik | jumlah dokumen PPID (bila helper tersedia) | "data belum diisi" |
| Realisasi Anggaran | **statis dari admin** (`pintu_anggaran_angka` + `pintu_anggaran_updated`) — turunan data keuangan penuh ditunda ke V2 | kosong → "data belum diisi" |

## 6. Struktur Tema (usulan)

```
desa/themes/huta-studio/
├── composer.json            # nama, pengembang, versi
├── config.json              # definisi pengaturan dashboard admin
├── README.md                # cara pasang & konfigurasi untuk desa lain
├── assets/thumbnail/preview-1.jpg
├── docs/2026-10-12-huta-studio-design.md
└── resources/views/
    ├── template.blade.php   # layout utama publik
    ├── partials/            # header, footer, floating WA
    ├── widgets/             # pintu transparansi, feed berita, strip pengumuman
    └── commons/
public/themes/huta-studio/   # css/js/gambar (ikuti konvensi tema bawaan; cache-bust)
```

## 7. Cakupan Halaman yang Dirapikan

- **Beranda** (portal) — **didesain bespoke** oleh tema: strip pengumuman, 5 pintu, feed berita.
- **Semua halaman publik lain** (Artikel, Statistik, Pembangunan, PPID, Galeri, Arsip, dll) — dibungkus **shell Huta Studio** (header, footer, navigasi, tipografi, tombol WA floating) dengan markup/modul bawaan dipertahankan. **Tidak ada redesain penuh per halaman modul di V1** — hanya restyle CSS + partial header/footer/pencarian.
- **Sistem widget bawaan OpenSID**: beranda tetap merender area "Widget Tambahan" berisi widget yang diaktifkan admin (`widgetAktif`) di bawah feed berita, agar pengaturan widget admin tetap berfungsi.
- Halaman **layanan mandiri / login admin** — tidak disentuh.

## 8. Kinerja (Performance)

- Tanpa slider otomatis, tanpa library JS berat (jQuery admin hanya di admin); CSS & JS tema diminimalkan.
- Font: sistem atau subset lokal (hindari Google Fonts blocking).
- Gambar: atribut width/height (anti-CLS), `loading="lazy"`, kompresi webp.
- Cache-bust aset dengan query versi (`?v=`).
- Verifikasi: Lighthouse lokal — `CHROME_PATH=/tmp/opencode/chrome/chrome-headless-shell/linux-155.0.8059.39/chrome-headless-shell-linux64/chrome-headless-shell npx lighthouse <url> --only-categories=performance --form-factor=mobile --screenEmulation.mobile --chrome-flags="--headless --no-sandbox --disable-gpu"`.

## 9. Sisa Keputusan untuk Builder (dibiarkan untuk implementasi)

- Lokasi pasti view/t tema bawaan OpenSID untuk dijadikan basis fork.
- Rute & perilaku pencarian publik bawaan OpenSID.
- Helper/rute mana yang tersedia dari blade untuk: total penduduk, agregat bantuan, jumlah/daftar artikel terbaru, URL sumber anggaran — **wajib diverifikasi** (grep + uji curl), bukan ditebak.
- Konvensi penamaan aset & strategi cache-bust tema.
- Detail visual (grid, breakpoint, palet warna default di luar warna aksen) — builder memilih mengikuti gaya bersih/modern.
- Strategi uji: curl smoke-test semua rute + Lighthouse + cek konsol browser.

## 10. Strategi Cut-over (disetujui pengguna 2026-10-12)

- Situs live **tetap Denava** selama pengembangan V1.
- Cut over **sekali jadi** hanya setelah seluruh acceptance criteria V1 lulus uji.
- **Rollback satu klik**: aktifkan kembali Denava dari admin Tema bawaan OpenSID.
- Pengguna **menerima** hilangnya fitur khusus Denava saat cut over (PWA manifest, cuaca, halaman Indeks Desa, smart-insight, AI chat).

## 11. Acceptance Criteria V1

- [x] Tema muncul di admin Tema (tabel theme id 29, slug desa-huta-studio).
- [x] Field config.json (30 key) tersimpan di `opsi` tema & terpakai (bukti: wa_number → tautan wa.me muncul).
- [x] Beranda: hero + strip pengumuman + 5 kartu pintu (null-safe "data belum diisi" teruji utk anggaran & pembangunan=0).
- [x] Tombol WA floating tampil di semua halaman ber-layout tema; tautan wa.me ter-encode benar. Catatan: /ppid dirender inti di luar template tema (di luar kendali tema).
- [x] Kotak pencarian header terpasang (form GET `/?cari=`).
- [x] Data kosong → "data belum diisi"; log tanpa error baru terkait tema.
- [x] Bansos hanya agregat (count `program_peserta`).
- [ ] Lighthouse mobile ≥ 80 — DITUNDA: alat lokal NO_FCP/hang bahkan pada halaman uji statis; sanity: HTML 199KB (Denava 441KB), tanpa library baru.
- [x] Tidak ada file inti OpenSID yang dimodifikasi (repo induk tak tersentuh; tema di repo sendiri).
