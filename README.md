# Huta Studio — Tema OpenSID Portal Transparansi Desa

Tema OpenSID **reusable multi-desa** berfokus keterbukaan informasi: beranda menampilkan strip pengumuman + 5 kartu pintu (Realisasi Anggaran, Statistik Penduduk, Bantuan Sosial, Pembangunan, Dokumen Publik/PPID) + berita terbaru, tombol WhatsApp mengambang, dan pencarian global.

## Persyaratan

- OpenSID >= v2610.0.0 (PHP 8.4 direkomendasikan)
- Modul inti OpenSID terisi: Artikel, Statistik (`/data-statistik`), Pembangunan (`/pembangunan`), PPID (`/ppid`), Bantuan Sosial (`/data-statistik/bantuan_penduduk`)

## Cara Pasang (desa lain)

1. Salin folder tema ini ke `desa/themes/huta-studio/` di instalasi OpenSID desa Anda.
2. Buka admin → **Tampilan → Tema** → pilih **Huta Studio** → **Aktifkan**. (Rollback: aktifkan kembali tema sebelumnya, satu klik.)
3. Buka **Tampilan → Tema → Pengaturan** dan isi:
   - **Kontak WhatsApp**: nomor format `628…` (tanpa `+`), pesan awal, label tombol
   - **Hero Beranda**: judul, subjudul, foto (opsional)
   - **Tampilan**: warna aksen
   - **Pintu Transparansi**: tampil/sembunyikan, URL override, angka & tanggal "Diperbarui" (opsional)
4. Kosongkan angka/tanggal untuk memakai angka otomatis dari data OpenSID; bila data kosong, kartu menampilkan "data belum diisi".

## Catatan Privasi

Kartu **Bantuan Sosial** hanya menampilkan **statistik agregat** (jumlah dari tabel peserta program). Jangan publikasikan nama/NIK penerima bantuan (UU Perlindungan Data Pribadi).

## Struktur

- `config.json` — definisi field pengaturan (dashboard admin Tema → Pengaturan)
- `resources/views/` — template Blade (namespace `theme::`)
  - `partials/portal.blade.php` — blok portal beranda (hanya halaman depan)
  - `partials/wa-floating.blade.php` — tombol WA mengambang
- `assets/css/huta.css` — gaya kustom ringan
- `scripts/sync-aset.sh` — salin aset tema ke folder publik (lihat bagian di bawah)
- `scripts/deploy-pwa.sh` — salin berkas PWA ke web root

## Aset Tema Ada Dua Salinan (penting)

Berkas di `desa/themes/huta-studio/assets/` **tidak dilayani browser secara
langsung**. Saat tema dipasang, OpenSID menyalin folder itu ke
`public/themes/desa-huta-studio/`. Salinan itu **bukan symlink** — mengubah
CSS/JS di folder sumber tidak akan sampai ke pengunjung sampai disalin ulang.
Gejalanya: HTML sudah berubah tetapi tampilan tetap seperti lama.

Setelah mengubah `assets/`, jalankan:

```bash
scripts/sync-aset.sh            # salin + verifikasi md5
scripts/sync-aset.sh --check    # hanya laporkan, tidak menulis
```

## Menu: Dua Perangkap yang Menyembunyikan Item

Tabel `menu` dipakai `menu_tema()` untuk membangun pohon navigasi. Dua aturan
wajib yang kalau dilanggar membuat item **tidak pernah tampil sama sekali**:

1. `parrent` tidak boleh menunjuk ke `id` sendiri. Menu id=1 dengan
   `parrent=1` berarti "anak dari dirinya sendiri" — tidak punya tempat di
   pohon. Isi `0` untuk menu tingkat atas.
2. `urut` harus **unik per induk**. Dua menu tingkat atas dengan `urut=1` yang
   sama akan saling menimpa saat pohon dibangun — salah satunya hilang tanpa
   pesan apa pun. Setelah menambah/memindahkan menu, periksa:

   ```sql
   SELECT parrent, urut, COUNT(*) c, GROUP_CONCAT(id) ids
   FROM menu GROUP BY parrent, urut HAVING c > 1;
   ```

   Hasilnya harus kosong.

## Di Mana Pengaturan Tema Disimpan

Nilai pengaturan tema **bukan** di `config.json` (itu hanya definisi field) dan
bukan di tabel `setting_aplikasi`. Nilainya disimpan di kolom `opsi` (JSON) pada
baris tabel `theme` untuk slug tema aktif — `desa-huta-studio` = id 29. Mengubah
nilai lewat dasbor admin menulis ke sana.

Beralih tema (aktif → nonaktif) berarti saklar, verifikasi AdSense, dan
pengaturan SEO ikut mengikuti tema yang aktif.

## PWA (manifest & service worker)

Berkas PWA dilayani dari **root web**: `/manifest.webmanifest` dan `/sw.js`
(dirujuk absolut oleh `resources/views/commons/source_css.blade.php` dan
`template.blade.php`).

Karena mekanisme tema OpenSID hanya menyalin folder `assets/` ke
`public/themes/desa-<slug>/`, berkas PWA disimpan di `public/` dalam repo ini
sebagai sumber, lalu disalin ke web root oleh skrip:

```bash
scripts/deploy-pwa.sh [/path/ke/web-root]   # default: /var/www/html/opensid/public
```

Jalankan skrip ini setiap kali berkas PWA berubah, saat reinstall OpenSID,
atau saat pindah server. `robots.txt` tetap dikelola OpenSID core (bukan bagian tema ini).

## Menerapkan Perubahan (cache berlapis)

Perubahan tema sering "tidak terlihat" karena ada beberapa lapis cache. Urutan
di bawah penting — **jangan** menjalankan `php artisan optimize:clear` di server
produksi (bisa membuat situs tidak bisa dibuka sementara).

```bash
cd /var/www/html/opensid

# 1. pindai tema (hanya perlu bila composer.json berubah) → versi tema ikut DB
sudo -u www-data php artisan opensid:pindai-tema

# 2. bersihkan cache aplikasi + compiled view (pakai skrip aman, jangan optimize:clear)
sudo -u www-data /usr/local/sbin/opensid-clear-cache.sh

# 3. cache fastcgi nginx
find /var/cache/nginx/opensid -type f -delete

# 4. muat ulang PHP & nginx
systemctl reload php8.4-fpm
systemctl reload nginx
```

Tambahan:

- **Setelah mengubah menu** di dasbor admin, `bootstrap/cache/*.php` ikut perlu
  dihapus (`rm -f bootstrap/cache/*.php`) agar daftar menu tidak tertahan.
- **Aset** (CSS/JS) dilayani dengan cache 30 hari, tetapi URL aset memakai
  `?themeVersion=` sehingga naiknya versi tema otomatis mem-bust cache
  pengunjung. Karena itu, menaikkan versi tema tetap wajib setiap kali ada
  perubahan tampilan.

### Memperbarui versi tema

Versi tema yang dirender diambil dari `composer.json` lewat helper
`theme_version()`, lalu disalin ke kolom `theme.versi` oleh perintah
`php artisan opensid:pindai-tema`. Jadi cukup ubah **dua** berkas:

```bash
# composer.json  -> "version": "v2610.4.9"
# theme.json     -> "version": "2610.4.9"
sudo -u www-data php artisan opensid:pindai-tema
```

`theme.json` ikut diperbarui agar sama, tetapi **tidak** memengaruhi versi yang
dirender. Setelah itu, jalankan langkah cache di atas dan cek:

```bash
curl -sk -H 'Host: sordangbolon-ujungpadang.desa.id' https://127.0.0.1/ | grep -o 'themeVersion=[^"&]*'
```

### Menguji tanpa Cloudflare

Situs produksi berada di belakang Cloudflare. Uji **origin** dulu agar yakin
kode-nya benar, baru uji jalur publik:

```bash
# origin (bypass Cloudflare)
curl -sk -H 'Host: sordangbolon-ujungpadang.desa.id' https://127.0.0.1/

# jalur publik
curl -sk https://sordangbolon-ujungpadang.desa.id/
```

Jika origin sudah benar tetapi jalur publik masih lama, itu cache Cloudflare —
tunggu TTL atau purg lewat dashboard. **Jangan** mengira kode gagal hanya
karena jalur publik belum berubah.

## Lisensi

GPL-3.0-or-later — turunan dari Tema Esensi oleh Diki Siswanto & kontributor OpenSID.
