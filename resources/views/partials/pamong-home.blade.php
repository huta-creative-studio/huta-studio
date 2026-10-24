@php
    use Illuminate\Support\Facades\DB;

    $hvPamong = collect();
    $hvHadirToday = collect();
    try {
        $hvPamong = DB::table('tweb_desa_pamong as p')
            ->leftJoin('tweb_penduduk as d', 'd.id', '=', 'p.id_pend')
            ->leftJoin('ref_jabatan as r', 'r.id', '=', 'p.jabatan_id')
            ->where('p.pamong_status', 1)
            ->selectRaw("p.pamong_id, coalesce(nullif(trim(p.pamong_nama), ''), trim(d.nama), 'Perangkat') as nama, coalesce(nullif(trim(r.nama), ''), 'Perangkat Desa') as jabatan, p.foto")
            ->orderBy('p.pamong_id')
            ->get();
        $hvHadirToday = DB::table('kehadiran_perangkat_desa')
            ->where('tanggal', date('Y-m-d'))
            ->get(['pamong_id', 'status_kehadiran', 'jam_masuk', 'jam_keluar'])
            ->keyBy('pamong_id');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Huta Studio: gagal mengambil data aparatur beranda', ['error' => $e->getMessage()]);
    }

    // Teks lencana bisa diubah dari Pengaturan Tema (Huta Studio).
    $hvTeksHadir = theme_config('kehadiran_text_hadir') ?: 'Hadir di Kantor';
    $hvTeksTidak = theme_config('kehadiran_text_tidak') ?: 'Tidak di Kantor';

    // Sebutan wilayah otomatis dari Pengaturan Identitas (mis. "Nagori" / "Desa").
    $hvSebutan = ucwords(setting('sebutan_desa')) ?: 'Desa';
@endphp

@if ($hvPamong->count())
<section class="hv-section hv-pamong-section" id="hv-pamong" aria-label="Aparatur {{ $hvSebutan }}">
    <div class="hv-container">
        <span class="hv-label">APARATUR</span>
        <h2 class="hv-title">Aparatur {{ $hvSebutan }}</h2>
        <p class="hv-desc">Perangkat yang melayani warga. Status kehadiran hari ini tampil otomatis bila sudah direkam.</p>

            <div class="owl-carousel hv-pamong-owl">
                @foreach ($hvPamong as $person)
                    @php
                        $kehadiran = $hvHadirToday->get($person->pamong_id);

                        // "Di kantor" = sudah rekam masuk, belum rekam keluar (atau statusnya hadir).
                        $diKantor = false;
                        if ($kehadiran) {
                            $jamMasuk = trim((string) $kehadiran->jam_masuk);
                            $jamKeluar = trim((string) $kehadiran->jam_keluar);
                            $sudahKeluar = $jamKeluar !== '' && ! str_starts_with($jamKeluar, '00:00');
                            $statusHadir = mb_strtolower(trim((string) $kehadiran->status_kehadiran)) === 'hadir';
                            $diKantor = ! $sudahKeluar && ($jamMasuk !== '' || $statusHadir);
                        }

                        $adaFoto = $person->foto && is_file(public_path('desa/upload/user_pict/' . $person->foto));
                        $fotoUrl = $adaFoto ? url('desa/upload/user_pict/' . $person->foto) : null;
                    @endphp
                    <div class="hv-pamong-card">
                        <div class="hv-pamong-img-wrap">
                            @if ($adaFoto)
                                <img class="hv-pamong-blur" src="{{ $fotoUrl }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <img class="hv-pamong-fg" src="{{ $fotoUrl }}" alt="{{ $person->nama }}" loading="lazy" decoding="async">
                            @else
                                <span class="hv-pamong-initial" aria-hidden="true">{{ mb_substr($person->nama, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="hv-pamong-info">
                            <h3 class="hv-pamong-name">{{ $person->nama }}</h3>
                            <p class="hv-pamong-role">{{ $person->jabatan }}</p>
                            @if ($kehadiran)
                                <span class="hv-badge-kehadiran {{ $diKantor ? 'hv-badge-hadir' : 'hv-badge-lain' }}"
                                    title="Status kehadiran {{ date('d-m-Y') }}">{{ $diKantor ? $hvTeksHadir : $hvTeksTidak }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hv-stats-cta">
                <a href="{{ site_url('pemerintah') }}" class="hv-btn hv-ghost">Profil Pemerintahan <i class="fas fa-arrow-right"></i></a>
            </div>
    </div>

    {{-- Digerakkan sendiri (bukan auto-init generik di script.min.js) karena setelannya
         harus responsif: 1 kartu di HP → 5 kartu di desktop, geser otomatis tiap 4 detik.
         Skrip ini dirender di dalam badan halaman, jadi ia terdaftar SEBELUM handler
         generik `$('.owl-carousel')` pada script.min.js (yang dimuat di akhir body). --}}
    <script>
        (function () {
            function hvInitPamong() {
                var w = window;
                if (!w.jQuery || !w.jQuery.fn || !w.jQuery.fn.owlCarousel) return;
                var $ = w.jQuery;
                var $el = $('.hv-pamong-owl');
                if (!$el.length || $el.data('owl.carousel')) return;
                var jumlah = $el.children('.hv-pamong-card').length;
                $el.owlCarousel({
                    loop: jumlah >= 3,
                    margin: 25,
                    nav: false,
                    dots: true,
                    autoplay: jumlah >= 3,
                    autoplayTimeout: 4000,
                    autoplayHoverPause: true,
                    smartSpeed: 700,
                    responsive: {
                        0: { items: 1, margin: 15 },
                        480: { items: 2.2, margin: 15 },
                        768: { items: 3.5 },
                        1024: { items: 4.5 },
                        1280: { items: 5 }
                    }
                });
                // Klon karusel tetap dimuat biar tidak ada bingkai kosong saat putaran ulang.
                $el.find('img').each(function () { this.setAttribute('loading', 'eager'); });
            }
            if (window.jQuery) {
                hvInitPamong();
            } else if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hvInitPamong);
            } else {
                hvInitPamong();
            }
        })();
    </script>
</section>
@endif
