@php
    $nama_desa = ucwords(setting('sebutan_desa')) . ' ' . title_case($desa['nama_desa']);

    $title = preg_replace('/[^A-Za-z0-9- ]/', '', trim(str_replace('-', ' ', get_dynamic_title_page_from_path())));
    $suffix = setting('website_title') . ' ' . ucwords(setting('sebutan_desa')) . ($desa['nama_desa'] ? ' ' . $desa['nama_desa'] : '');
    $desa_title = $title ? $title . ' - ' . $suffix : $suffix;

    // --- Meta description per halaman ---------------------------------------
    // Ringkas, unik per halaman, dan dibatasi ~160 karakter agar tidak terpotong
    // di hasil pencarian. Deskripsi artikel dibuat di blok @if di bawah.
    $lokasi_singkat = trim(
        ucfirst(setting('sebutan_kecamatan')) . ' ' . title_case($desa['nama_kecamatan']) . ', ' .
        ucfirst(setting('sebutan_kabupaten')) . ' ' . title_case($desa['nama_kabupaten'])
    );
    $deskripsi_desa = setting('website_title') ?: 'Situs web resmi ' . $nama_desa;
    $deskripsi_page = $title
        ? trim($title) . ' — ' . $deskripsi_desa . '. ' . $lokasi_singkat . '.'
        : trim(rtrim($deskripsi_desa, '.')) . '. Informasi resmi dan transparansi ' . $nama_desa . ', ' . $lokasi_singkat . '.';
    // normalisasi spasi ganda & batasi panjang
    $deskripsi_page = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($deskripsi_page)), 160, '');

    // --- Canonical URL ------------------------------------------------------
    // Sebelumnya selalu site_url() (beranda) untuk SEMUA halaman, sehingga
    // Google menganggap beranda sebagai versi asli tiap artikel. Kini memakai
    // URL halaman sebenarnya, tanpa query string dan tanpa slash di akhir.
    $canonical = preg_replace('/[?#].*$/', '', current_url());
    $canonical = rtrim($canonical, '/');
    $canonical = $canonical === '' ? site_url() : $canonical;

    // --- Identitas desa untuk structured data -------------------------------
    $desa_website = site_url();
    $desa_logo = !empty($desa['logo']) ? gambar_desa($desa['logo']) : null;
    $desa_alamat = trim((string) ($desa['alamat_kantor'] ?? ''));
    $desa_email = trim((string) ($desa['email_desa'] ?? ''));
    // Kode wilayah Kemendagri untuk areaServed: provinsi(2).kabupaten(2).kecamatan(2).
    // Data mentah: kode_propinsi=12, kode_kabupaten=1208, kode_kecamatan=120830.
    $kp = preg_replace('/\D/', '', (string) ($desa['kode_propinsi'] ?? ''));
    $kk = preg_replace('/\D/', '', (string) ($desa['kode_kabupaten'] ?? ''));
    $kc = preg_replace('/\D/', '', (string) ($desa['kode_kecamatan'] ?? ''));
    $kode_wilayah = $kp !== '' && $kk !== '' && $kc !== ''
        ? $kp . '.' . substr($kk, 2, 2) . '.' . substr($kc, 4, 2)
        : '';
@endphp

<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name='viewport' content='width=device-width, initial-scale=1' />
<meta name='google' content='notranslate' />
<meta name='theme' content='Huta Studio' />
<meta name='designer' content='Huta Studio' />
<meta name='theme:designer' content='Huta Studio' />
<meta name='theme:version' content='{{ $themeVersion }}' />
<meta name="theme-color" content="#efefef">
<meta name='keywords'
    content="{{ $desa_title }} @if (!strpos($desa_title, $nama_desa)) {{ $nama_desa }} @endif {{ ucfirst(setting('sebutan_kecamatan')) }} {{ title_case($desa['nama_kecamatan']) }}, {{ ucfirst(setting('sebutan_kabupaten')) }} {{ title_case($desa['nama_kabupaten']) }}, Provinsi  {{ title_case($desa['nama_propinsi']) }}"
/>
<meta property="og:site_name" content="{{ $nama_desa }}" />
<meta property="og:type" content="article" />
<link rel="canonical" href="{{ $canonical }}" />
<meta name='robots' content='index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' />
<meta name="subject" content="Situs Web Desa">
<meta name="copyright" content="{{ $nama_desa }}">
<meta name="language" content="Indonesia">
@php
    // Verifikasi mesin pencari — hanya dirender bila diisi di Pengaturan Tema.
    // Nilai di-escape; tag tidak pernah dibuat dari input mentah.
    $hvGsv = trim((string) theme_config('google_site_verification'));
    $hvBsv = trim((string) theme_config('bing_site_verification_id'));
@endphp
@if ($hvGsv !== '')
<meta name="google-site-verification" content="{{ $hvGsv }}" />
@endif
@if ($hvBsv !== '')
<meta name="msvalidate.01" content="{{ $hvBsv }}" />
@endif
@php
    /**
     * Facebook App ID / Admin ID.
     *
     * fb:app_id membuat Facebook mengenali situs ini sebagai pemilik tautan,
     * sehingga pratinjau saat dibagikan lebih andal. fb:admins bersifat opsional.
     *
     * Hanya nilai numerik yang diterima. Ini mencegah isi field dipakai
     * menyuntikkan atribut/tag: format salah -> tidak dirender sama sekali.
     */
    $hvFbApp = trim((string) theme_config('facebook_app_id'));
    $hvFbAdm = trim((string) theme_config('facebook_admin_id'));
    $hvFbAppValid = (bool) preg_match('/^[0-9]{5,25}$/', $hvFbApp);
    $hvFbAdmValid = (bool) preg_match('/^[0-9]{5,25}$/', $hvFbAdm);
@endphp
@if ($hvFbAppValid)
<meta property="fb:app_id" content="{{ $hvFbApp }}" />
@endif
@if ($hvFbAdmValid)
<meta property="fb:admins" content="{{ $hvFbAdm }}" />
@endif
@php
    /**
     * Google AdSense.
     *
     * Publisher ID hanya diterima bila benar-benar berbentuk "ca-pub-" diikuti
     * angka. Ini mencegah isi field dipakai menyuntikkan skrip: kalau formatnya
     * tidak cocok, tidak ada apa pun yang dirender.
     */
    $hvAdsenseId = strtolower(trim((string) theme_config('google_adsense_id')));
    $hvAdsenseValid = (bool) preg_match('/^ca-pub-[0-9]{10,20}$/', $hvAdsenseId);

    // Schema SEO (JSON-LD). Default aktif; hanya "0" yang mematikannya.
    $hvSchemaAktif = theme_config('seo_schema_aktif') !== '0';
@endphp
@if ($hvAdsenseValid)
<meta name="google-adsense-account" content="{{ $hvAdsenseId }}" />
@endif
<meta name="revised" content="Sunday, July 18th, 2010, 5:15 pm" />
<meta name="Classification" content="Government">
<meta name="url" content="{{ site_url() }}">
<meta name="identifier-URL" content="{{ site_url() }}">
<meta name="category" content="Desa, Pemerintahan">
<meta name="coverage" content="Worldwide">
<meta name="distribution" content="Global">
<meta name="rating" content="General">
<meta name="revisit-after" content="7 days">
<meta name="revisit-after" content="7" />
<meta name="webcrawlers" content="all" />
<meta name="rating" content="general" />
<meta name="spiders" content="all" />
<link rel="alternate" type="application/rss+xml" title="Feed {{ $nama_desa }}" href="{{ site_url('sitemap') }}" />

@if (isset($single_artikel))
    @php
        // Susun ringkasan dari isi artikel:
        //  1) buang blok pertama bila pendek -> biasanya heading (mis. "1. Pendahuluan")
        //  2) jadikan akhir blok sebagai pemisah spasi, decode entitas HTML
        //  3) rapikan spasi, batasi ~158 karakter
        $html_isi = (string) $single_artikel['isi'];
        if (preg_match('/^\s*(<[^>]+>)*\s*(.*?)\s*(<[^>]+>)*\s*<\/(p|div|h[1-6])>/is', $html_isi, $m)) {
            $blok1 = trim(html_entity_decode(strip_tags($m[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (mb_strlen($blok1) <= 45) {
                $html_isi = substr($html_isi, strlen($m[0]));
            }
        }
        $isi_bersih = preg_replace('/<\/(p|div|h[1-6]|li)>|<br\s*\/?>/i', ' ', $html_isi);
        $isi_bersih = html_entity_decode(strip_tags($isi_bersih), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $isi_bersih = preg_replace('/\s+/', ' ', trim($isi_bersih));
        $desk_artikel = str_replace('"', "'", \Illuminate\Support\Str::limit($isi_bersih, 158, '…'));

        // --- Nilai untuk JSON-LD NewsArticle ---
        $art_judul = trim((string) ($single_artikel['judul'] ?? ''));
        $art_tgl = $single_artikel['tgl_upload'] ?? null;
        $art_date = $art_tgl ? date('c', strtotime($art_tgl)) : null;
        $art_owner = trim((string) ($single_artikel['owner'] ?? ''));
        $art_kategori = trim((string) ($single_artikel['kategori'] ?? ''));
        // Gambar utama (helper AmbilFotoArtikel sudah dipakai tema lain).
        $art_gambar = null;
        if (trim((string) ($single_artikel['gambar'] ?? '')) !== '') {
            $art_gambar = function_exists('AmbilFotoArtikel')
                ? AmbilFotoArtikel($single_artikel['gambar'], 'sedang')
                : base_url(LOKASI_FOTO_ARTIKEL . 'sedang_' . $single_artikel['gambar']);
        }
        $art_url = $canonical;

        // --- Gambar untuk og:image (pratinjau saat dibagikan ke Facebook/WhatsApp) ---
        // Dipakai berkas 'sedang_' yang benar-benar ada, mengikuti pola yang sudah
        // mantap di partial lain (headline/berita-home/article).
        // Berkas > 8 MB ditolak: itu batas keras crawler Facebook, dan mengirim
        // URL yang akan ditolak membuat pratinjau gagal sekaligus membuat proses
        // berbagi menggantung. Bila tidak layak, dipakai logo desa.
        $og_img = null;
        $og_img_type = null;
        if (trim((string) ($single_artikel['gambar'] ?? '')) !== '') {
            $og_path = LOKASI_FOTO_ARTIKEL . 'sedang_' . $single_artikel['gambar'];
            if (is_file($og_path) && filesize($og_path) <= 8 * 1024 * 1024) {
                $og_img = $art_gambar;
                $og_ext = strtolower(pathinfo($og_path, PATHINFO_EXTENSION));
                $og_img_type = [
                    'webp' => 'image/webp',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ][$og_ext] ?? null;
            }
        }
        // Cadangan terakhir: logo desa. Selalu ada, jadi pratinjau tidak pernah kosong.
        if ($og_img === null && function_exists('gambar_desa')) {
            $og_img = gambar_desa($desa['logo'] ?? '');
            $og_img_type = null;
        }
    @endphp
    <title>{{ $single_artikel['judul'] . ' - ' . $nama_desa }}</title>
    <meta name='description' content="{{ $desk_artikel }}" />
    <meta property="og:title" content="{{ $single_artikel['judul'] }}" />
    <meta itemprop="name" content="{{ $single_artikel['judul'] }}" />
    <meta itemprop='description' content="{{ $desk_artikel }}" />
    @if ($og_img)
        <meta property="og:image" content="{{ $og_img }}">
        @if ($og_img_type)
        <meta property="og:image:type" content="{{ $og_img_type }}">
        @endif
        <meta property="og:image:alt" content="{{ $single_artikel['judul'] }}" />
        <meta itemprop="image" content="{{ $og_img }}" />
    @endif
    <meta property='og:description' content="{{ $desk_artikel }}" />
    @php
        // Structured data NewsArticle → memunculkan rich result (judul, gambar,
        // tanggal) di Google Penelusuran & Google Berita.
        $article_ld = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $art_url],
            'headline' => \Illuminate\Support\Str::limit($art_judul, 110, ''),
            'description' => $desk_artikel,
            'image' => $art_gambar ? [$art_gambar] : null,
            'datePublished' => $art_date,
            'dateModified' => $art_date,
            'author' => $art_owner ? ['@type' => 'Person', 'name' => $art_owner] : null,
            'publisher' => array_filter([
                '@type' => 'GovernmentOrganization',
                'name' => $nama_desa,
                'logo' => $desa_logo ? ['@type' => 'ImageObject', 'url' => $desa_logo] : null,
            ]),
            'articleSection' => $art_kategori ?: null,
            'inLanguage' => 'id-ID',
            'url' => $art_url,
        ]);
    @endphp
    @if ($hvSchemaAktif)
    <script type="application/ld+json">{!! json_encode($article_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@else
    <title>{{ $desa_title }}</title>
    <meta name='description' content="{{ $deskripsi_page }}" />
    <meta itemprop="name" content="{{ $desa_title }}" />
    <meta property="og:title" content="{{ $desa_title }}" />
    <meta property='og:description' content="{{ $deskripsi_page }}" />
    @php
        // --- Gambar pratinjau untuk halaman non-artikel ---
        // Tanpa og:image, berbagi beranda atau halaman statis ke Facebook /
        // WhatsApp hanya menghasilkan pratinjau teks tanpa gambar. Logo desa
        // dipakai sebagai cadangan karena itu satu-satunya gambar yang selalu
        // tersedia di setiap halaman — dan sudah jadi pola baku tema ini
        // (dipakai juga sebagai cadangan artikel yang tidak bergambar).
        $hvLogoUrl = null;
        $hvLogoType = null;
        if (!empty($desa['logo']) && function_exists('gambar_desa')) {
            $hvLogoUrl = gambar_desa($desa['logo']);
            // Berkas lokal diturunkan dari URL-nya sendiri supaya tetap benar
            // walau gambar_desa() mengubah struktur foldernya.
            $hvLogoFile = null;
            $hvLogoPath = parse_url((string) $hvLogoUrl, PHP_URL_PATH);
            if ($hvLogoPath) {
                $hvLogoFile = public_path(ltrim($hvLogoPath, '/'));
            }
            if (!is_file((string) $hvLogoFile)) {
                $hvLogoAlt = public_path('desa/logo/' . $desa['logo']);
                $hvLogoFile = is_file($hvLogoAlt) ? $hvLogoAlt : null;
            }
            // Batas 8 MB = batas keras crawler; berkas yang lebih besar ditolak
            // agar pratinjau tidak gagal diam-diam.
            if ($hvLogoFile && filesize($hvLogoFile) <= 8 * 1024 * 1024) {
                $hvLogoType = [
                    'webp' => 'image/webp',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ][strtolower(pathinfo($hvLogoFile, PATHINFO_EXTENSION))] ?? null;
            } else {
                $hvLogoUrl = null;
                $hvLogoType = null;
            }
        }
    @endphp
    @if ($hvLogoUrl)
        <meta property="og:image" content="{{ $hvLogoUrl }}">
        @if ($hvLogoType)
        <meta property="og:image:type" content="{{ $hvLogoType }}">
        @endif
        <meta property="og:image:alt" content="{{ $nama_desa }}" />
        <meta itemprop="image" content="{{ $hvLogoUrl }}" />
    @endif
    @php
        // Structured data GovernmentOrganization → menegaskan situs ini milik
        // pemerintah desa resmi (nama, logo, alamat, wilayah, kontak).
        $org_ld = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'GovernmentOrganization',
            'name' => $nama_desa,
            'alternateName' => 'Pemerintah ' . $nama_desa,
            'url' => $desa_website,
            'logo' => $desa_logo ? ['@type' => 'ImageObject', 'url' => $desa_logo] : null,
            'email' => $desa_email ?: null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $desa_alamat ?: null,
                'addressLocality' => title_case($desa['nama_kecamatan'] ?? ''),
                'addressRegion' => title_case($desa['nama_propinsi'] ?? ''),
                'postalCode' => trim((string) ($desa['kode_pos'] ?? '')) ?: null,
                'addressCountry' => 'ID',
            ]),
            'areaServed' => array_filter([
                '@type' => 'AdministrativeArea',
                'name' => ucfirst(setting('sebutan_kecamatan')) . ' ' . title_case($desa['nama_kecamatan'] ?? ''),
                'identifier' => $kode_wilayah ?: null,
            ]),
        ]);
    @endphp
    @if ($hvSchemaAktif)
    <script type="application/ld+json">{!! json_encode($org_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endif
<meta property='og:url' content="{{ current_url() }}" />
<link rel="shortcut icon" href="{{ favico_desa() }}" />
<noscript>You must have JavaScript enabled in order to use this theme. Please enable JavaScript and then reload this page in order to continue.</noscript>
@if (cek_koneksi_internet())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
@endif
<script>
    var BASE_URL = '{{ base_url() }}';
    var SITE_URL = '{{ site_url() }}';
    var setting = @json(setting());
    var config = @json(identitas());
</script>
@php
    /**
     * Google Analytics 4.
     *
     * ID hanya diterima bila benar-benar berbentuk "G-" diikuti huruf/angka.
     * Ini mencegah isi field dipakai menyuntikkan skrip: kalau formatnya tidak
     * cocok, tidak ada apa pun yang dirender.
     */
    $hvGaId = strtoupper(trim((string) theme_config('google_analytics_id')));
    $hvGaValid = (bool) preg_match('/^G-[A-Z0-9]{4,20}$/', $hvGaId);
@endphp
@if ($hvGaValid)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $hvGaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $hvGaId }}');
    </script>
@endif
@if ($hvAdsenseValid)
    {{-- Auto Ads: Google menempatkan iklan sendiri, tampilan tema tidak diubah. --}}
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $hvAdsenseId }}"
        crossorigin="anonymous"></script>
@endif
