@php
    use Illuminate\Support\Facades\DB;

    $hvApbdes = null;
    $hvApbdesTahuns = [];
    $hvApbdesTahun = null;
    try {
        // Tahun anggaran yang tersedia di dashboard admin (tabel keuangan).
        $hvApbdesTahuns = DB::table('keuangan')
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->filter()
            ->values()
            ->all();

        if (empty($hvApbdesTahuns)) {
            $hvApbdesTahuns = [(int) date('Y')];
        }

        $hvDiminta = (int) request()->query('tahun', 0);
        $hvApbdesTahun = in_array($hvDiminta, $hvApbdesTahuns, true) ? $hvDiminta : $hvApbdesTahuns[0];

        $hvBaris = DB::table('keuangan as k')
            ->join('keuangan_template as t', 't.uuid', '=', 'k.template_uuid')
            ->where('k.tahun', $hvApbdesTahun)
            ->whereNull('t.parent_uuid')
            ->groupBy('t.uraian')
            ->selectRaw("t.uraian, coalesce(sum(k.anggaran),0) as anggaran, coalesce(sum(k.realisasi),0) as realisasi")
            ->get()
            ->filter(function ($r) {
                return ((float) $r->anggaran) > 0 || ((float) $r->realisasi) > 0;
            });

        if ($hvBaris->isNotEmpty()) {
            $hvApbdes = $hvBaris
                ->sortBy(function ($r) {
                    return array_search(mb_strtolower($r->uraian), ['pendapatan', 'belanja', 'pembiayaan']);
                })
                ->values();
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil APBDes beranda', ['error' => $e->getMessage()]);
        $hvApbdes = null;
    }
@endphp

@if ($hvApbdes && $hvApbdes->count())
<section class="hv-section hv-apbdes-section" id="hv-apbdes" aria-label="Ringkasan APBDes">
    <div class="hv-container">
        <div class="hv-apbdes-head">
            <div class="hv-apbdes-intro">
                <span class="hv-label">APBDES{{ $hvApbdesTahun ? ' ' . $hvApbdesTahun : '' }}</span>
                <h2 class="hv-title">Ringkasan Anggaran Desa</h2>
                <p class="hv-desc">Pendapatan, belanja, dan pembiayaan nagori tahun anggaran {{ $hvApbdesTahun }}. Rincian lengkap tersedia di laman transparansi/footer.</p>
            </div>

            @if (count($hvApbdesTahuns) > 1)
                <form class="hv-apbdes-filter" method="get" action="{{ site_url() }}" aria-label="Pilih tahun anggaran">
                    <label for="apbdes-tahun" class="hv-apbdes-filter-label">Tahun anggaran</label>
                    <select id="apbdes-tahun" name="tahun" class="hv-select" onchange="this.form.submit()">
                        @foreach ($hvApbdesTahuns as $hvTahun)
                            <option value="{{ $hvTahun }}"{{ $hvTahun === $hvApbdesTahun ? ' selected' : '' }}>{{ $hvTahun }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="hv-btn hv-btn-sm">Tampilkan</button></noscript>
                </form>
            @endif
        </div>

            <div class="hv-apbdes-grid">
                @foreach ($hvApbdes as $baris)
                    @php
                        $anggaran = (float) $baris->anggaran;
                        $realisasi = (float) $baris->realisasi;
                        $persen = $anggaran > 0 ? min(100, round($realisasi / $anggaran * 100)) : 0;
                    @endphp
                    <div class="hv-card hv-apbdes-card">
                        <span class="hv-apbdes-name">{{ $baris->uraian }}</span>
                        <span class="hv-apbdes-anggaran">{{ 'Rp ' . number_format($anggaran, 0, ',', '.') }}</span>
                        <div class="hv-apbdes-bar" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                            <span class="hv-apbdes-fill" style="width: {{ $persen }}%"></span>
                        </div>
                        <span class="hv-apbdes-real">Realisasi {{ 'Rp ' . number_format($realisasi, 0, ',', '.') }} ({{ $persen }}%)</span>
                    </div>
                @endforeach
            </div>
    </div>
</section>
@endif
