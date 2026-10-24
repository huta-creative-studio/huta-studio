@php
    use Illuminate\Support\Facades\DB;

    $hutaPintu = [
        'anggaran' => ['judul' => 'Realisasi Anggaran', 'ikon' => 'fa-balance-scale', 'url' => theme_config('pintu_anggaran_url') ?: ''],
        'penduduk' => ['judul' => 'Statistik Penduduk', 'ikon' => 'fa-users', 'url' => theme_config('pintu_penduduk_url') ?: site_url('data-statistik')],
        'bantuan' => ['judul' => 'Bantuan Sosial', 'ikon' => 'fa-hand-holding-heart', 'url' => theme_config('pintu_bantuan_url') ?: site_url('data-statistik/bantuan_penduduk')],
        'pembangunan' => ['judul' => 'Pembangunan', 'ikon' => 'fa-hard-hat', 'url' => theme_config('pintu_pembangunan_url') ?: site_url('pembangunan')],
        'dokumen' => ['judul' => 'Dokumen Publik', 'ikon' => 'fa-folder-open', 'url' => theme_config('pintu_dokumen_url') ?: site_url('ppid')],
    ];
    foreach ($hutaPintu as $k => &$pintu) {
        $pintu['aktif'] = theme_config("pintu_{$k}_enable") !== '0';
        if ($pintu['url'] !== '' && !preg_match('#^(https?://|/)#', $pintu['url'])) {
            $pintu['url'] = '';
        }
    }
    unset($pintu);

    $hutaGreeting = theme_config('hero_greeting') ?: 'Selamat Datang';
    $hutaSub = theme_config('hero_sub') ?: (setting('motto_desa') ?: 'Informasi terbuka untuk warga');
    $hutaFoto = theme_config('url_foto_hero') ?: (theme_config('foto_hero') ? base_url(theme_config('foto_hero')) : (!empty($latar_website) ? $latar_website : null));

    $hutaMarquee = [];
    try {
        $hutaMarquee = DB::table('artikel')
            ->where('enabled', 1)
            ->orderBy('tgl_upload', 'desc')
            ->select('judul', 'slug', 'tgl_upload')
            ->limit(3)
            ->get();
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil artikel marquee hero', ['error' => $e->getMessage()]);
    }
@endphp

<header class="hv-hero" @if ($hutaFoto) style="background-image: linear-gradient(120deg, rgba(15,23,42,0.82) 10%, rgba(15,23,42,0.45) 60%, rgba(15,23,42,0.65)), url('{{ $hutaFoto }}');" @endif>
    <div class="hv-hero-inner">
        <p class="hv-hero-greet">{{ $hutaGreeting }}</p>
        <h1 class="hv-hero-title">{{ ucwords(setting('sebutan_desa')) }} {{ title_case($desa['nama_desa']) }}</h1>
        <p class="hv-hero-sub">{{ $hutaSub }}</p>

        <form method="get" action="{{ site_url('/') }}" class="hv-hero-search" role="search">
            <input type="search" name="cari" value="{{ request()->get('cari', '') }}" placeholder="Cari informasi, layanan, atau dokumen..." aria-label="Cari informasi">
            <button type="submit" class="hv-btn"><i class="fas fa-search"></i> Cari</button>
        </form>

        <div class="hv-shortcut">
            @foreach ($hutaPintu as $pintu)
                @if ($pintu['aktif'])
                    @if ($pintu['url'])
                        <a class="hv-shortcut-item" href="{{ $pintu['url'] }}">
                    @else
                        <div class="hv-shortcut-item hv-shortcut-disabled">
                    @endif
                            <span class="hv-shortcut-icon"><i class="fa {{ $pintu['ikon'] }}"></i></span>
                            <span>{{ $pintu['judul'] }}</span>
                    @if ($pintu['url'])
                        </a>
                    @else
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>

    @if ($hutaMarquee->count())
        <div class="hv-marquee" aria-label="Informasi terkini">
            <span class="hv-marquee-badge">INFO</span>
            <div class="hv-marquee-track">
                @for ($i = 0; $i < 2; $i++)
                    @foreach ($hutaMarquee as $artikel)
                        <a href="{{ site_url('artikel/' . date('Y/m/d', strtotime($artikel->tgl_upload)) . '/' . $artikel->slug) }}" @if ($i === 1) aria-hidden="true" tabindex="-1" @endif>
                            {{ $artikel->judul }} · {{ function_exists('tgl_indo') ? tgl_indo($artikel->tgl_upload) : date('d M Y', strtotime($artikel->tgl_upload)) }}
                        </a>
                    @endforeach
                @endfor
            </div>
        </div>
    @endif
</header>
