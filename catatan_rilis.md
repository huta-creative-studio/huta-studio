# Catatan Rilis — Huta Studio

## V4.11 (2026-10-22)

Paket perbaikan mode gelap untuk tema Huta Studio. Semua perubahan hanya
berpengaruh saat mode gelap aktif — tampilan terang tidak berubah sama sekali.

1. **Grafik statistik kini gelap dan terbaca.** Sebelumnya grafik Highcharts
   tetap berwarna terang (latar putih, teks abu-abu) meskipun situs sedang
   mode gelap, sehingga Statsitika dan seluruh halaman data statistik
   tampak seperti "sisip halaman terang". Kini latar grafik, teks sumbu,
   legenda, label data, garis bantu, dan tooltip mengikuti warna mode gelap.

2. **Peta otomatis memakai lapisan gelap.** Peta pada halaman lembaga, Peta Nagori,
   dan halaman profil kini berganti ke lapisan gelap (CartoDB DarkMatter)
   begitu mode gelap dinyalakan, dan otomatis kembali ke peta biasa (satelit /
   jalan) ketika mode gelap dimatikan. Tombol "Lapis" tetap berfungsi seperti
   semula dan pilihan pengguna tidak pernah ditimpa diam-diam.

3. **Kontrol peta ikut gelap.** Kotak zoom, tombol "Lapis", catatan hak cipta
   (Leaflet), dan pop-up peta sekarang memakai latar gelap, bukan putih.

4. **Tautan aksen jadi mudah dibaca.** Judul artikel, tautan "Indeks", dan
   tautan aksen lain memakai warna aksen terang di mode gelap. Sebelumnya
   rasionya hanya 2,9:1 (belum layak dibaca), kini 10,7:1.

5. **Badge status IDM menurunkan satu tingkat warna.** Warna Status IDM,
   Skor IDM Saat Ini, Target Status, dan Skor Minimal mengikuti warna yang
   lebih gelap sehingga teks putihnya memenuhi standar aksesibilitas AA
   (4,5:1 ke atas).

6. **Paginasi tabel tidak lagi menyala terang.** Tombol halaman DataTables
   (misalnya "Sebelumnya" / "Selanjutnya") tidak lagi memakai latar putih
   kusam, dan tombol nonaktif tetap terbaca. Penyebabnya: CSS DataTables
   sendiri menulis aturan `html.dark … .paginate_button.disabled { color:#666 }`
   dengan spesifisitas lebih tinggi daripada aturan tema, sehingga harus
   ditimpa secara spesifik.

7. **Uji regresi otomatis (`scripts/test-mode-gelap.py`).** Skrip baru mengukur
   mode gelap pada lima kelompok pemeriksaan: grafik Highcharts, peta Leaflet,
   badge IDM, tautan aksen, dan paginasi DataTables — memverifikasi warna
   terhitung, kontras WCAG, dan piksel yang benar-benar dirender. Sebelum
   perbaikan: **14 pemeriksaan gagal**. Sesudah perbaikan: **semua lulus**.

## V4.10 (2026-10-22)

1. **Pratinjau berbagi kini punya gambar di semua halaman.** Sebelumnya tag gambar pratinjau (`og:image`) hanya dipasang pada halaman artikel. Akibatnya, ketika beranda atau halaman lain (Profil Nagori, Pengaduan, Peta Nagori, dan sejenisnya) dibagikan ke Facebook maupun WhatsApp, yang muncul hanyalah teks tanpa gambar. Kini setiap halaman memakai logo desa sebagai gambar pratinjau — satu-satunya gambar yang selalu tersedia di semua halaman.

2. **Halaman artikel tidak berubah.** Artikel yang punya gambar tetap memakai gambar artikelnya sendiri, dan artikel tanpa gambar tetap memakai logo desa seperti sebelumnya. Yang berbeda hanya halaman non-artikel, yang sebelumnya kosong sama sekali.

3. **Gambar pratinjau dipastikan benar-benar ada.** Sebelum tag-nya dicetak, berkas gambarnya dicek lebih dulu: harus benar-benar ada di server dan tidak lebih dari 8 MB (batas keras crawler media sosial). Bila pemeriksaan gagal, tag gambarnya sengaja tidak dicetak agar pengunjung tidak melihat gambar rusak.

4. **Uji regresi otomatis (`test-og-image.sh`).** Skrip baru memeriksa delapan halaman (enam halaman non-artikel dan dua artikel): keberadaan `og:image`, `og:image:alt`, `og:image:type`, apakah gambarnya benar-benar bisa diunduh (HTTP 200), apakah halaman artikel memakai gambar artikelnya sendiri, dan apakah tidak ada galat PHP. Sebelum perbaikan: **8 kegagalan**. Sesudah perbaikan: **36 pemeriksaan lulus, 0 gagal** — diuji pada jalur publik maupun ke server langsung.

## V4.9 (2026-10-22)

1. **Menu "Peta Nagori Sordang Bolon" kini tampil di navigasi (perbaikan bug).** Menu tersebut tidak pernah muncul di navigasi mana pun — desktop maupun drawer ponsel — sejak pertama kali dibuat. Penyebabnya dua hal yang bertumpuk: `parrent` menu itu menunjuk ke nomor `id`-nya sendiri (menu "anak dari dirinya sendiri", sehingga tidak punya tempat di pohon navigasi), dan nilai `urut`-nya kembar dengan menu lain di tingkat yang sama sehingga saling menimpa. Keduanya sudah diperbaiki; halaman tujuan `/data-wilayah` tetap utuh dan seluruh halaman lain tidak berubah.

2. **Urutan menu tingkat atas dirapikan.** "Peta Nagori Sordang Bolon" kini menjadi menu pertama (setelah Beranda), disusul Profil Nagori, Demografi, PPID, Lembaga Nagori, Bantuan, dan Pengaduan. Tidak ada menu tingkat atas dengan nomor urut kembar.

3. **Penjaga agar menu tidak hilang diam-diam lagi.** Aturan dan cara pemeriksaan cepatnya dijelaskan di README: `parrent` tidak boleh menunjuk ke `id` sendiri, dan `urut` harus unik per induk.

4. **Verifikasi ulang tujuh saklar bagian beranda.** Ketujuh saklar (Hero, Kabar Terbaru, Statistik, Aparatur, Pembangunan, Galeri, APBDes) diuji langsung: saklar yang dimatikan membuat bagiannya hilang total dari halaman (bukan sekadar disembunyikan), dan bagian kembali muncul saat saklar dinyalakan lagi. Bagian yang datanya masih kosong (Pembangunan, Galeri) tetap disembunyikan otomatis, dan bagian APBDes menampilkan tiga kartu ringkasan anggaran yang terisi.

5. **Metadata sosial & SEO diverifikasi ulang.** Tag `canonical`, `og:title`, `og:description`, `og:url`, `og:type`, `og:image` + `og:image:alt` (halaman artikel), `og:site_name`, `fb:app_id`, `fb:admins`, `google-site-verification`, `google-adsense-account`, GA4, skrip AdSense Auto Ads, dan JSON-LD (`GovernmentOrganization` di beranda, `NewsArticle` di artikel) semuanya hadir dan valid.

6. **Skrip sinkronisasi aset (`scripts/sync-aset.sh`).** Aset tema dilayani dari salinan di `public/themes/desa-huta-studio/`, bukan dari folder sumber — perubahan CSS/JS di sumber tidak akan sampai ke pengunjung tanpa disalin ulang. Skrip baru ini menyalin aset, memverifikasi md5 berkas, dan punya mode `--check` untuk hanya melaporkan perbedaan. Proses manual yang mudah salah langkah sekarang jadi satu perintah.

7. **Dokumentasi deployment diperbarui.** README tidak lagi menyebut `php artisan optimize:clear` sebagai langkah pembersihan cache (perintah itu dilarang di server produksi), menjelaskan asal-usul versi tema (`composer.json` → `opensid:pindai-tema`), dan menambah bagian khusus tentang dua perangkap menu serta lokasi penyimpanan pengaturan tema.

## V4.7 (2026-10-21)

1. **Keterangan privasi pada formulir pengaduan.** Di bawah formulir "Buat Pengaduan" kini tercantum penjelasan singkat bahwa data yang dikirim (nama, kontak, alamat, isi pengaduan) hanya dipakai untuk menindaklanjuti pengaduan tersebut, disertai tautan ke halaman **Kebijakan Privasi**. Formulir ini mengumpulkan data pribadi, sehingga keterangan di titik pengumpulan data memenuhi asas transparansi UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi, sekaligus melengkapi syarat kelayakan Google AdSense untuk situs yang menayangkan iklan ber-cookie.

2. Tautan memakai URL tetap `artikel/2026/01/01/kebijakan-privasi` karena rute artikel pada core OpenSID berbeda antar edisi (tidak tersedia rute bernama `artikel.detail`).

## V4.8 (2026-10-21)

1. **Perbaikan menyeluruh mode gelap (penting).** Sebelumnya tabel di mode gelap nyaris tidak terbaca. Penyebabnya dua aturan bawaan tema menargetkan elemen baris (`tr`) dengan kekhususan lebih tinggi daripada aturan sel, sehingga baris genap tetap berlatar abu terang `#f3f4f6`. Hasil pengukuran: rasio kontras **1,36:1** — jauh di bawah ambang WCAG AA (4,5:1). Kini seluruh baris tabel ikut gelap, dan kontras terburuk menjadi **9,53:1** (lolos AA). Diuji pada 3.140 elemen teks di 20 halaman: seluruh halaman tema bersih dari pelanggaran kontras.

2. **Komponen pendukung mode gelap.** Kotak pesan (`.alert`) sebelumnya berwarna teks hijau tua di atas latar gelap — **2,6:1**; kini **10,16:1**. Ikut diperbaiki: lencana status, tombol, kontrol isian (termasuk varian Bootstrap 5 di halaman PPID), remah navigasi, tab/menu samping, label grafik, judul, dan tautan konten. Warna aksen tema (teal) dipakai versi terangnya di mode gelap; mode terang tidak berubah sama sekali.

3. **Tirai pengaman kelas latar.** Kelas latar terang (`.bg-white`, `.bg-light`, `.bg-gray-*`, `.bg-slate-*`, `.card`, `.table-light`) kini otomatis mengikuti warna gelap, sehingga halaman baru yang belum ditangani tidak lagi memunculkan kotak putih menyilaukan.

4. **Bilah gulir & kontrol bawaan browser** ikut mode gelap (`color-scheme: dark`) — daftar pilihan, kalender, dan bilah gulir tidak lagi putih.

5. Tidak ada perubahan pada tata letak, ukuran huruf, atau mode terang. Mode gelap tetap otomatis mengikuti pengaturan sistem pengunjung.

## V4.6 (2026-10-21)

1. **Perbaikan bug pratinjau saat dibagikan (penting).** Tag `og:image` menunjuk ke `/og-image/…`, alamat yang **tidak tersedia** di OpenSID, sehingga Facebook/WhatsApp menerima halaman 404 alih-alih gambar. Akibatnya gambar pratinjau tidak pernah muncul dan proses berbagi ke Facebook menggantung tanpa selesai. Kini `og:image` memakai berkas `sedang_` yang benar-benar ada, mengikuti pola yang sudah dipakai partial lain, dengan cadangan otomatis ke logo desa. Gambar di atas 8 MB (batas crawler Facebook) otomatis dialihkan ke logo desa. `og:image:type` kini sesuai format berkas asli (webp/jpeg/png/gif) — sebelumnya selalu tertulis `image/png`.

2. **Tujuh saklar bagian beranda.** Hero, Kabar Terbaru, Statistik, Aparatur, Pembangunan, Galeri, dan APBDes dapat dinyalakan/dimatikan satu per satu dari Pengaturan Tema. Bagian yang datanya masih kosong otomatis disembunyikan, sehingga beranda tidak pernah menampilkan blok "belum diisi". Urutan bagian kini dikelola satu berkas (`partials/beranda.blade.php`) yang dipakai ketiga layout, jadi tidak lagi bisa berbeda antar layout.

3. **Facebook App ID & Admin ID.** Metadata `fb:app_id` dan `fb:admins` agar Facebook mengenali situs saat tautan dibagikan. Hanya nilai numerik yang diterima; format lain tidak dirender sama sekali.

4. **Google AdSense (Auto Ads).** Kolom Publisher ID dan pemuatan skrip iklan. Google menempatkan iklan sendiri, tampilan tema tidak berubah. Hanya nilai berformat `ca-pub-…` yang dirender.

5. **Verifikasi Google Search Console & Bing Webmaster.** Tag `google-site-verification` dan `msvalidate.01`, hanya dirender bila kolomnya diisi.

6. **Saklar Schema SEO (JSON-LD).** Data terstruktur `NewsArticle` dan `GovernmentOrganization` dapat dimatikan bila tidak diinginkan. Bawaan: aktif.

7. **Judul halaman pengaduan dapat diubah.** Kolom `judul_pengaduan` mengubah judul dan breadcrumb halaman pengaduan warga. Dikosongkan berarti kembali ke "Pengaduan".

## V4.5 (2026-10-21)
1. **Lazy-load gambar artikel & galeri.** Foto daftar artikel, arsip, galeri, pembangunan, pengaduan, dan aparatur kini dimuat hanya saat mendekati layar (`loading="lazy"` + `decoding="async"`). Halaman awal jauh lebih ringan — pengunjung tidak lagi mengunduh semua foto sekaligus. Gambar utama artikel (hero) tetap dimuat langsung agar tampil tanpa jeda.
2. Konsistensi: seluruh 47 gambar lazy kini juga memakai `decoding="async"`.

## V4.4 (2026-10-21)
1. **Perbaikan bug canonical (penting).** Tag `<link rel="canonical">` sebelumnya selalu menunjuk ke beranda (`site_url()`) di **semua** halaman — termasuk setiap artikel. Ini memberi tahu Google bahwa versi asli setiap artikel adalah beranda, berpotensi menghambat pengindeksan artikel. Kini setiap halaman memakai URL-nya sendiri (`current_url()`), tanpa query string.
2. **JSON-LD `NewsArticle`** pada halaman artikel: `headline`, `description`, `image`, `datePublished`/`dateModified`, `author`, `publisher`, `mainEntityOfPage`, `articleSection`. Memungkinkan rich result (judul, gambar, tanggal) di Google Penelusuran & Berita.
3. **JSON-LD `GovernmentOrganization`** pada beranda: nama, logo, email, alamat kantor, kode wilayah Kemendagri (`areaServed`), dan situs resmi — menegaskan situs ini milik pemerintah desa resmi.
4. Ringkasan deskripsi artikel kini membuang blok heading pembuka (mis. "1. Pendahuluan") dan mendekode entitas HTML (`&ndash;` → `–`).

## V4.3 (2026-10-21)
1. Meta description kini unik dan ringkas per halaman (beranda, arsip, pembangunan, pengaduan, statistik, dll.) — sebelumnya seluruh halaman non-artikel memakai teks boilerplate yang nyaris identik.
2. Meta description artikel: membuang heading bernomor (mis. "1. Pendahuluan") dan menormalkan spasi berlebih; dibatasi ±160 karakter agar tidak terpotong di hasil pencarian.
3. Deskripsi tidak lagi memuat spasi ganda dan panjangnya seragam di bawah 160 karakter.

## V4.2 (2026-10-20)
1. Tautan teks marquee pengumuman diangkat ke minimum 24 px (WCAG 2.5.8); salinan loop tetap `aria-hidden`.

## V4.1 (2026-10-20)
1. Font seragam Plus Jakarta Sans (`.font-primary` tidak lagi memaksa Poppins; Poppins tetap cadangan).
2. Aksesibilitas: target sentuh minimum 44 px untuk kontrol utama (menu, toggle mode gelap, tombol tutup, ikon sosial) dan 24 px untuk tautan daftar padat (WCAG 2.5.8).
3. Aksesibilitas: item klon karusel Owl disembunyikan dari pembaca layar & fokus keyboard; titik navigasi karusel diberi nama "Slide N".
4. `alt` ditambahkan pada gambar widget arsip, komponen peta aparatur, foto pengaduan, dan ilustrasi kosong.

## V4 (2026-10-14)
1. Loading screen modern (fade-in, logo, progress bar, auto-hide).
2. Aparatur: grid satu kolom + foto utuh (aspect-ratio, tanpa terpotong).
3. Ringkasan Anggaran Desa: dapat dipilih per tahun anggaran (dropdown).
4. Perbaikan tautan berita beranda (buat_slug butuh thn/bln/hri/slug).
5. Pembersihan jejak nama tema asal pada aset & metadata.

## V3 (2026-10-13)
1. Palet warna gaya Simalungun (emerald + amber + tinta hijau), font Plus Jakarta Sans.
2. Section beranda: Kabar Terbaru, Statistik, Aparatur, Pembangunan, Galeri, APBDes.
3. Modal login pilih portal (Administrator + Layanan Warga).
4. PWA: manifest + service worker (aset statis, lewati halaman privat).

## V2 (2026-10-12)
1. Hero modern, navbar sticky + drawer, mode gelap menyeluruh.
2. Footer 3 kolom + bottom-nav mobile.
3. Feed berita kartu, statistik counter, footer sosial media.

## V1 (2026-10-12)
1. Kerangka tema Huta Studio.
2. Portal beranda: strip pengumuman + 5 kartu pintu transparansi.
3. Tombol WhatsApp mengambang, kotak pencarian, pengaturan tema via `config.json`.
