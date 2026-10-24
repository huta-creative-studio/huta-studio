@php
    use Illuminate\Support\Facades\DB;

    $hutaPintu = [
        'anggaran' => [
            'judul' => 'Realisasi Anggaran',
            'ikon' => 'fa-balance-scale',
            'url' => theme_config('pintu_anggaran_url') ?: '',
        ],
        'penduduk' => [
            'judul' => 'Statistik Penduduk',
            'ikon' => 'fa-users',
            'url' => theme_config('pintu_penduduk_url') ?: site_url('data-statistik'),
        ],
        'bantuan' => [
            'judul' => 'Bantuan Sosial',
            'ikon' => 'fa-hand-holding-heart',
            'url' => theme_config('pintu_bantuan_url') ?: site_url('data-statistik/bantuan_penduduk'),
        ],
        'pembangunan' => [
            'judul' => 'Pembangunan',
            'ikon' => 'fa-hard-hat',
            'url' => theme_config('pintu_pembangunan_url') ?: site_url('pembangunan'),
        ],
        'dokumen' => [
            'judul' => 'Dokumen Publik',
            'ikon' => 'fa-folder-open',
            'url' => theme_config('pintu_dokumen_url') ?: site_url('ppid'),
        ],
    ];

    $hutaOtomatis = [
        'penduduk' => ['tabel' => 'penduduk_hidup', 'satuan' => ' jiwa'],
        'bantuan' => ['tabel' => 'program_peserta', 'satuan' => ' penerima'],
        'pembangunan' => ['tabel' => 'pembangunan', 'satuan' => ' kegiatan'],
        'dokumen' => ['tabel' => 'dokumen', 'satuan' => ' dokumen'],
    ];

    foreach ($hutaPintu as $k => &$pintu) {
        $pintu['aktif'] = theme_config("pintu_{$k}_enable") !== '0';
        if ($pintu['url'] !== '' && !preg_match('#^(https?://|/)#', $pintu['url'])) {
            $pintu['url'] = '';
        }
        $angka = theme_config("pintu_{$k}_angka");
        $updated = theme_config("pintu_{$k}_updated");
        if ($angka === '' || $angka === null) {
            $angka = null;
            if (isset($hutaOtomatis[$k])) {
                try {
                    $jumlah = DB::table($hutaOtomatis[$k]['tabel'])->count();
                    $angka = $jumlah > 0
                        ? number_format($jumlah, 0, ',', '.') . $hutaOtomatis[$k]['satuan']
                        : null;
                } catch (\Throwable $e) {
                    $angka = null;
                }
            }
        }
        $pintu['angka'] = $angka ?: 'data belum diisi';
        if ($updated === '' || $updated === null) {
            $updated = null;
            if (isset($hutaOtomatis[$k])) {
                try {
                    $terakhir = DB::table($hutaOtomatis[$k]['tabel'])->max('updated_at')
                        ?: DB::table($hutaOtomatis[$k]['tabel'])->max('created_at');
                    if ($terakhir) {
                        $updated = function_exists('tgl_indo')
                            ? tgl_indo($terakhir)
                            : date('d M Y', strtotime($terakhir));
                    }
                } catch (\Throwable $e) {
                    $updated = null;
                }
            }
        }
        $pintu['updated'] = $updated ?: '—';
    }
    unset($pintu);

    $hutaJudulHero = theme_config('judul_hero') ?: 'Selamat Datang';
    $hutaSubHero = theme_config('subjudul_hero') ?: 'Informasi terbuka untuk warga';
    $hutaFotoHero = theme_config('url_foto_hero') ?: (theme_config('foto_hero') ? base_url(theme_config('foto_hero')) : null);

    $hutaPengumuman = null;
    try {
        $hutaPengumuman = DB::table('artikel')
            ->where('enabled', 1)
            ->orderBy('tgl_upload', 'desc')
            ->select('judul', 'slug', 'tgl_upload')
            ->first();
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil artikel strip pengumuman', ['error' => $e->getMessage()]);
        $hutaPengumuman = null;
    }
@endphp

<section class="container mx-auto lg:px-5 px-3 my-5 space-y-5" aria-label="Portal informasi desa">
    <div class="huta-hero" @if ($hutaFotoHero) style="background-image: linear-gradient(rgba(15,23,42,.65), rgba(15,23,42,.65)), url('{{ $hutaFotoHero }}');" @endif>
        <h1>{{ $hutaJudulHero }}</h1>
        <p>{{ $hutaSubHero }}</p>
    </div>

    @if ($hutaPengumuman)
        <div class="huta-strip">
            <span class="font-semibold">{{ ucwords(setting('sebutan_desa')) }} Terkini:</span>
            <a href="{{ site_url('artikel/' . date('Y/m/d', strtotime($hutaPengumuman->tgl_upload)) . '/' . $hutaPengumuman->slug) }}" class="font-medium hover:underline">
                {{ $hutaPengumuman->judul }}
            </a>
            <span class="huta-updated text-gray-500 text-sm">
                · {{ function_exists('tgl_indo') ? tgl_indo($hutaPengumuman->tgl_upload) : date('d M Y', strtotime($hutaPengumuman->tgl_upload)) }}
            </span>
        </div>
    @endif

    <div>
        <h2 class="huta-judul-bagian">Informasi Untuk Warga</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach ($hutaPintu as $pintu)
                @if ($pintu['aktif'])
                    @if ($pintu['url'])
                        <a class="huta-kartu" href="{{ $pintu['url'] }}">
                    @else
                        <div class="huta-kartu opacity-75">
                    @endif
                        <span class="huta-ikon"><i class="fa {{ $pintu['ikon'] }}"></i></span>
                        <span class="font-semibold text-sm">{{ $pintu['judul'] }}</span>
                        <span class="huta-angka text-sm">{{ $pintu['angka'] }}</span>
                        <span class="huta-updated">Diperbarui: {{ $pintu['updated'] }}</span>
                    @if ($pintu['url'])
                        </a>
                    @else
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</section>
