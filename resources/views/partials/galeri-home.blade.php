@php
    use Illuminate\Support\Facades\DB;

    $hvGaleri = collect();
    try {
        $hvGaleri = DB::table('gambar_gallery')
            ->where('enabled', 1)
            ->orderByDesc('tgl_upload')
            ->limit(8)
            ->get(['gambar', 'nama', 'parrent']);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil galeri beranda', ['error' => $e->getMessage()]);
    }
@endphp

@if ($hvGaleri->count())
<section class="hv-section hv-galeri-section" id="hv-galeri" aria-label="Galeri foto">
    <div class="hv-container">
        <span class="hv-label">GALERI</span>
        <h2 class="hv-title">Galeri Nagori</h2>
        <p class="hv-desc">Dokumentasi kegiatan dan potret kehidupan nagori.</p>

            <div class="hv-galeri-grid">
                @foreach ($hvGaleri as $foto)
                    <a class="hv-card hv-galeri-item" href="{{ $foto->parrent ? site_url('galeri/album/' . $foto->parrent) : site_url('galeri') }}" aria-label="{{ $foto->nama ?: 'Foto galeri' }}">
                        @if ($foto->gambar && is_file(public_path('desa/upload/galeri/' . $foto->gambar)))
                            <img src="{{ url('desa/upload/galeri/' . $foto->gambar) }}" alt="{{ $foto->nama ?: 'Foto galeri' }}" loading="lazy" decoding="async">
                        @else
                            <span class="hv-news-thumb-ph"><i class="fas fa-images"></i></span>
                        @endif
                        @if ($foto->nama)
                            <span class="hv-galeri-cap">{{ \Illuminate\Support\Str::limit($foto->nama, 40) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="hv-stats-cta">
                <a href="{{ site_url('galeri') }}" class="hv-btn hv-ghost">Buka Galeri <i class="fas fa-arrow-right"></i></a>
            </div>
    </div>
</section>
@endif
