@php
    /**
     * Penyusun bagian beranda Huta Studio.
     *
     * Dipakai oleh ketiga layout (full-content, left-sidebar, right-sidebar)
     * supaya urutan dan aturan tampil/sembunyi selalu sama.
     *
     * Dua lapis aturan untuk setiap bagian:
     *   1. Saklar di Pengaturan Tema — dimatikan berarti bagian hilang permanen.
     *   2. Deteksi data kosong — kalau datanya belum ada, bagian hilang otomatis
     *      supaya beranda tidak menampilkan blok "belum diisi" yang mengganggu.
     *
     * Hero tidak punya deteksi kosong: ia bukan data, melainkan kepala halaman,
     * jadi hanya mengikuti saklarnya.
     */
    $tampil = fn (string $key): bool => theme_config($key) !== '0';
@endphp

@if ($tampil('home_hero_enable'))
    @include('theme::partials.hero')
@endif

@if ($tampil('home_berita_enable'))
    @include('theme::partials.berita-home')
@endif

@if ($tampil('home_statistik_enable'))
    @include('theme::partials.statistik-home')
@endif

@if ($tampil('home_pamong_enable'))
    @include('theme::partials.pamong-home')
@endif

@if ($tampil('home_pembangunan_enable'))
    @include('theme::partials.pembangunan-home')
@endif

@if ($tampil('home_galeri_enable'))
    @include('theme::partials.galeri-home')
@endif

@if ($tampil('link_youtube'))
    @include('theme::partials.youtube-home')
@endif

@if ($tampil('home_apbdes_enable'))
    @include('theme::partials.apbdes-home')
@endif
