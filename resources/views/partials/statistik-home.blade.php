@php
    use Illuminate\Support\Facades\DB;

    $hvStats = null;
    try {
        $row = DB::table('penduduk_hidup')
            ->selectRaw('count(*) as total, sum(sex = 1) as l, sum(sex = 2) as p')
            ->first();
        $kk = (int) DB::table('penduduk_hidup')->where('id_kk', '!=', 0)->distinct()->count('id_kk');
        $hvStats = [
            ['label' => 'Total Penduduk', 'nilai' => (int) ($row->total ?? 0), 'ikon' => 'fa-users'],
            ['label' => 'Laki-laki', 'nilai' => (int) ($row->l ?? 0), 'ikon' => 'fa-mars'],
            ['label' => 'Perempuan', 'nilai' => (int) ($row->p ?? 0), 'ikon' => 'fa-venus'],
            ['label' => 'Kepala Keluarga', 'nilai' => $kk, 'ikon' => 'fa-home'],
        ];
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil statistik beranda', ['error' => $e->getMessage()]);
        $hvStats = null;
    }
@endphp

@if ($hvStats && ($hvStats[0]['nilai'] ?? 0) > 0)
<section class="hv-section hv-stats-section" aria-label="Statistik penduduk">
    <div class="hv-container">
        <span class="hv-label">STATISTIK</span>
        <h2 class="hv-title">Data Kependudukan</h2>
        <p class="hv-desc">Ringkasan data kependudukan desa, diperbarui otomatis dari basis data.</p>

        <div class="hv-stats">
            @foreach ($hvStats as $stat)
                <div class="hv-stat hv-card">
                    <span class="hv-stat-icon"><i class="fa {{ $stat['ikon'] }}"></i></span>
                    <span class="hv-stat-num" data-target="{{ $stat['nilai'] }}">0</span>
                    <span class="hv-stat-label">{{ $stat['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="hv-stats-cta">
            <a href="{{ site_url('data-statistik') }}" class="hv-btn hv-ghost">Lihat Statistik Lengkap <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<script>
    (function () {
        var nums = document.querySelectorAll('.hv-stat-num[data-target]');
        if (!nums.length || !('IntersectionObserver' in window)) {
            for (var j = 0; j < nums.length; j++) nums[j].textContent = Number(nums[j].getAttribute('data-target')).toLocaleString('id-ID');
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                io.unobserve(el);
                var target = Number(el.getAttribute('data-target')) || 0;
                var start = null, dur = 1200;
                function step(ts) {
                    if (!start) start = ts;
                    var p = Math.min((ts - start) / dur, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });
        }, { threshold: 0.4 });
        for (var i = 0; i < nums.length; i++) io.observe(nums[i]);
    })();
</script>
@endif
