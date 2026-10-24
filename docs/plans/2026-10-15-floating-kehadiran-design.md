# Desain: Widget Floating — Kehadiran, WhatsApp, Kembali ke Atas

> Dokumen desain (hasil brainstorming 2026-10-15). Rencana implementasi menyusul di `docs/plans/` setelah desain disetujui.

## Intent & mengapa

Warga yang membuka situs nagori dari halaman mana pun (berita, artikel, galeri) bisa langsung melihat **siapa aparatur yang sudah ada di kantor hari ini** tanpa harus membuka halaman `/pemerintah`. Bersamaan dengan itu, tombol chat WhatsApp dan kembali-ke-atas dirapikan jadi satu tumpukan tombol yang konsisten di semua halaman.

Acuan: panel "Status Kehadiran" desaciater.id (diperiksa langsung 2026-10-15, desktop & 390×844). Mockup rancangan: `.superpowers/brainstorm/310279-1792079207/content/`.

## Goals

1. Tiga tombol floating di **semua halaman**, urutan atas→bawah: **kehadiran** → **WhatsApp** (ikon saja) → **up**.
2. Panel daftar perangkat: foto + nama + jabatan + badge status, **tanpa** tombol "Lihat Selengkapnya".
3. Tanpa error: konsol browser bersih, tanpa dependensi baru.

## Non-goals / anti-goals

- Tidak ada perubahan backend atau API — memakai `internal_api/pemerintah` yang sudah dipakai `/pemerintah`.
- Tidak menambah pustaka/CDN baru (Alpine CDN yang sudah ada tidak boleh jadi syarat kerja widget ini).
- Tidak ada auto-close saat kursor keluar drawer (permintaan user, dibatalkan).
- Tidak ada dua tombol WhatsApp (pill "Chat Desa" lama digantikan) dan tidak ada dua tombol "up".
- Tidak menyimpan status kehadiran lintas hari (localStorage/caching bertanggal dilarang).

## Keputusan yang sudah diputuskan user

1. **Tombol (semua halaman):** urutan atas→bawah = kehadiran (ikon `fa-user-check`), WhatsApp (ikon saja, menggantikan tombol "Chat Desa" ber-teks), up (muncul setelah halaman di-scroll ke bawah).
2. **Desktop (≥1024 px):** klik ikon kehadiran → **drawer 400 px** setinggi viewport menempel di tepi kanan, halaman di belakang menggelap. Tiga tombol floating **tertutup drawer** selama terbuka (tidak digeser). Tutup dengan **klik di luar drawer** atau **tombol ✕**. Daftar digulir di dalam drawer.
3. **HP (<1024 px, saat bottom-nav tampil):** tiga tombol **di atas footer menetap** (`hv-bottom`), urutan sama. Ketuk ikon kehadiran → daftar tampil **layar penuh** dan **bottom-nav menutup**. Ada **pegangan geser** yang menempel di bagian bawah panel dan **tidak ikut tergulir** saat daftar digeser atas-bawah. Satu-satunya cara menutup: **geser ke kanan dari titik mana pun di panel**, dengan animasi geser ke kanan; setelah tertutup, bottom-nav tampil kembali.
4. **Isi panel:** foto, nama, jabatan, badge dari `internal_api/pemerintah` memakai definisi badge yang sudah disepakui: `Hadir di Kantor` / `Tidak di Kantor` / `Belum Rekam Kehadiran` (logika `jam_keluar` + `toLowerCase() === 'hadir'`, teks dari `theme_config('kehadiran_text_hadir'/'kehadiran_text_tidak')`).
5. **Urutan daftar:** **Pangulu paling atas** → **yang sudah hadir** → sisanya sesuai urutan jabatan dari API. Urutan dihitung ulang setiap kali panel dibuka (data harian; pada hari libur tidak ada yang "hadir" sehingga daftar kembali ke urutan jabatan).
6. **Cakupan:** semua halaman.
7. **Hari libur:** panel **selalu menampilkan badge** — saat `hari_libur` bernilai true, semua perangkat mendapat badge khusus **"Libur"** (bukan baris kosong seperti halaman `/pemerintah`).
8. **Warna badge "Tidak di Kantor": oranye** (varian ketiga, bukan abu).

### Keputusan builder untuk badge (mengikuti butir 7–8)

| Status | Teks badge | Gaya |
|---|---|---|
| `hadir` (di kantor) | Hadir di Kantor | emerald `.hv-badge--hadir` (`#ecfdf5/#047857/#a7f3d0`) |
| sudah keluar / pulang | Tidak di Kantor | **oranye** `.hv-badge--tidak` (`#fff7ed/#c2410c/#fed7aa`) |
| tidak ada rekam | Belum Rekam Kehadiran | abu `.hv-badge--belum` (`#f3f4f6/#6b7280/#e5e7eb`) |
| `hari_libur == true` | Libur | netral slate `.hv-badge--libur` (`#f8fafc/#475569/#e2e8f0`) — teks yang membedakan dari "Belum Rekam" |

Aturan: jika `hari_libur == true` → semua baris badge "Libur" tanpa memandang rekam kehadiran (user: pada hari libur tidak ada yang berstatus hadir). Selain itu pakai logika yang sudah disepakui (jam_keluar + `toLowerCase() === 'hadir'`).

**Divergensi yang disadari:** halaman `/pemerintah` tetap memakai aturan lama (libur → tanpa badge). Perubahan aturan libur ini hanya berlaku di panel floating; menyamakan halaman `/pemerintah` adalah perubahan terpisah yang belum diminta.

## Pilihan teknis (keputusan builder, bisa dikoreksi user)

- **Breakpoint 1024 px** — selaras `.hv-bottom` yang disembunyikan di `@media (min-width: 1024px)`. Jadi "mode HP" = saat bottom-nav tampil.
- **Tanpa Alpine untuk widget ini:** ditulis **vanilla JS** dalam satu partial Blade + CSS kustom (blok baru `.hv-ft-*` / `.hv-keh-*` di `assets/css/huta-v2.css`). Alasan: Alpine dimuat dari CDN unpkg — kalau CDN lambat/mati, widget ikut rusak; permintaan user "tanpa error" diutamakan. Ikon memakai Font Awesome yang sudah dipakai tema.
- **WhatsApp:** link dari `theme_config('wa_enable'/'wa_number'/'wa_message')` seperti `partials/wa-floating.blade.php` sekarang; yang berubah hanya tampilan (ikon saja, label jadi `aria-label`).
- **Tombol up:** dipindah ke tumpukan ini dan **`commons.back_to_top` tidak lagi dipakai** (hindari tombol up dobel). Ambang tampil mengikuti aturan lama (muncul setelah scroll > 800 px).
- **Data:** diambil saat panel pertama dibuka pada suatu pemuatan halaman, lalu ditampilkan; tidak disimpan di localStorage. Kegagalan API menampilkan pesan ramah di dalam panel ("Data kehadiran tidak tersedia"), bukan error konsol.
- **Aksesibilitas:** tombol punya `aria-label` dan `aria-expanded`, panel `role="dialog"` + `aria-modal`, Esc menutup (desktop), foto `loading="lazy"` + `alt="Foto <nama>"`, fokus tidak hilang saat panel tertutup.
- **Mode gelap:** varian `html.dark` mengikuti sistem variabel tema (tema sudah punya ~34 aturan `html.dark`).
- **Rilis:** bump versi tema (2610.3.0 → 2610.4.0 di `theme.json` + `composer.json`), suite uji statis baru, verifikasi browser (desktop 1366 + HP 390, termasuk gestur geser), 0 galat konsol, commit + ledger.

## Jawaban atas builder-check (2026-10-15)

- **Paginasi API:** `internal_api/pemerintah` terbatas 30 baris per halaman. Panel mengambil **semua halaman** (loop `links.next`, maks 10 halaman sebagai pengaman) sehingga tidak pernah memotong daftar diam-diam. *(keputusan builder)*
- **Basis jalur uji & commit:** workspace `.superpowers/sdd/<plan>/` ada di **repo tema** (bukan repo induk), dan commit hanya `git add -A` di repo tema — mengikuti ruling plan `/pemerintah` sebelumnya. *(keputusan lama, diulang)*
- **Perilaku badge saat hari libur** → badge "Libur" untuk semua (jawaban user, butir 7).
- **Warna badge "Tidak di Kantor"** → oranye (jawaban user, butir 8).

## File yang akan disentuh

- Baru: `resources/views/partials/floating-tools.blade.php` (markup + JS).
- Ubah: `assets/css/huta-v2.css` (blok `.hv-ft-*`/`.hv-keh-*`), `resources/views/template.blade.php` (include), `resources/views/commons/footer.blade.php` (hentikan include `back_to_top`), `resources/views/partials/wa-floating.blade.php` (dipensiunkan), `theme.json` + `composer.json` (versi).
- Uji: `.superpowers/sdd/<plan>/` (suite statis pola `check`, sama seperti suite `/pemerintah`).
