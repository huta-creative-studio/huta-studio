@php
    use Illuminate\Support\Facades\DB;

    $hvBerita = collect();
    $hvKategoriSlug = null;
    $hvKategoriNama = null;
    try {
        $hvBerita = DB::table('artikel')
            ->leftJoin('kategori', 'kategori.id', '=', 'artikel.id_kategori')
            ->where('artikel.enabled', 1)
            ->orderBy('artikel.tgl_upload', 'desc')
            ->select('artikel.id', 'artikel.judul', 'artikel.slug', 'artikel.tgl_upload', 'artikel.gambar', 'artikel.isi', 'kategori.kategori', 'kategori.slug as kategori_slug')
            ->limit(6)
            ->get();
        $first = $hvBerita->first();
        $hvKategoriSlug = $first->kategori_slug ?? null;
        $hvKategoriNama = $first->kategori ?? null;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil feed berita', ['error' => $e->getMessage()]);
    }
@endphp

@if ($hvBerita->count())
<section class="hv-section hv-news-section" aria-label="Berita terbaru">
    <div class="hv-container">
        <div class="hv-news-head">
            <div>
                <span class="hv-label">BERITA</span>
                <h2 class="hv-title">Kabar Terbaru</h2>
                <p class="hv-desc">Informasi kegiatan dan pengumuman terbaru pemerintah desa.</p>
            </div>
            @if ($hvKategoriSlug)
                <a href="{{ site_url('artikel/kategori/' . $hvKategoriSlug) }}" class="hv-btn hv-ghost hv-news-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            @endif
        </div>

        <div class="hv-news">
            @foreach ($hvBerita as $artikel)
                    @php
                        $gambar = $artikel->gambar && is_file(LOKASI_FOTO_ARTIKEL . 'sedang_' . $artikel->gambar)
                            ? AmbilFotoArtikel($artikel->gambar, 'sedang')
                            : gambar_desa($desa['logo']);
                        $isi = trim(strip_tags($artikel->isi ?? ''));
                        $excerpt = mb_strlen($isi) > 140 ? mb_substr($isi, 0, 140) . '…' : $isi;
                        // buat_slug() butuh thn/bln/hri/slug — samakan dgn core OpenSID (feed & sitemap).
                        $hvTgl = $artikel->tgl_upload ? strtotime($artikel->tgl_upload) : time();
                        $hvSlugData = [
                            'thn' => date('Y', $hvTgl),
                            'bln' => date('m', $hvTgl),
                            'hri' => date('d', $hvTgl),
                            'slug' => (string) $artikel->slug,
                        ];
                    @endphp
                    <a class="hv-news-card hv-card" href="{{ site_url('artikel/' . buat_slug($hvSlugData)) }}">
                        <img src="{{ $gambar }}" alt="{{ $artikel->judul }}" class="hv-news-img" loading="lazy" decoding="async">
                        <div class="hv-news-body">
                            <span class="hv-news-meta">
                                @if ($artikel->kategori_slug)
                                    <span class="hv-news-chip">{{ $artikel->kategori }}</span>
                                @endif
                                <span>{{ function_exists('tgl_indo') ? tgl_indo($artikel->tgl_upload) : date('d M Y', strtotime($artikel->tgl_upload)) }}</span>
                            </span>
                            <h3 class="hv-news-title">{{ $artikel->judul }}</h3>
                            <p class="hv-news-excerpt">{{ $excerpt }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
    </div>
</section>
@endif
