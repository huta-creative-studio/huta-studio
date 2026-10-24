@php
    use Illuminate\Support\Facades\DB;

    $hvBangunan = collect();
    try {
        $hvBangunan = DB::table('pembangunan')
            ->orderByDesc('tahun_anggaran')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'judul', 'slug', 'tahun_anggaran', 'sumber_dana', 'lokasi', 'foto', 'anggaran']);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil pembangunan beranda', ['error' => $e->getMessage()]);
    }
@endphp

@if ($hvBangunan->count())
<section class="hv-section hv-bangunan-section" id="hv-pembangunan" aria-label="Pembangunan desa">
    <div class="hv-container">
        <span class="hv-label">PEMBANGUNAN</span>
        <h2 class="hv-title">Kegiatan Pembangunan</h2>
        <p class="hv-desc">Progres pembangunan dan kegiatan fisik di nagori, transparan untuk warga.</p>

        <div class="hv-news-grid">
            @foreach ($hvBangunan as $item)
                    <article class="hv-card hv-news-card">
                        <a href="{{ site_url('pembangunan/' . ($item->slug ?: $item->id)) }}" class="hv-news-thumb" aria-label="{{ $item->judul }}">
                            @if ($item->foto && is_file(public_path('desa/upload/pembangunan/' . $item->foto)))
                                <img src="{{ url('desa/upload/pembangunan/' . $item->foto) }}" alt="{{ $item->judul }}" loading="lazy" decoding="async">
                            @else
                                <span class="hv-news-thumb-ph"><i class="fas fa-hard-hat"></i></span>
                            @endif
                        </a>
                        <div class="hv-news-body">
                            @if ($item->tahun_anggaran)
                                <span class="hv-label">TA {{ $item->tahun_anggaran }}</span>
                            @endif
                            <h3 class="hv-news-title"><a href="{{ site_url('pembangunan/' . ($item->slug ?: $item->id)) }}">{{ $item->judul }}</a></h3>
                            @if ($item->sumber_dana)
                                <p class="hv-news-meta"><i class="fas fa-coins"></i> {{ $item->sumber_dana }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="hv-stats-cta">
                <a href="{{ site_url('pembangunan') }}" class="hv-btn hv-ghost">Semua Pembangunan <i class="fas fa-arrow-right"></i></a>
            </div>
    </div>
</section>
@endif
