@php
    $waAktif = theme_config('wa_enable') !== '0';
    $waNomor = preg_replace('/[^0-9]/', '', (string) theme_config('wa_number'));
    $waPesan = trim((string) theme_config('wa_message'));
    $waLabel = theme_config('wa_label') ?: 'Chat Desa';
    $waUrl = $waNomor !== ''
        ? 'https://wa.me/' . $waNomor . ($waPesan !== '' ? '?text=' . rawurlencode($waPesan) : '')
        : null;
@endphp

{{-- Widget floating semua halaman: kehadiran (panel daftar perangkat) + WhatsApp + kembali ke atas --}}
<div class="hv-ft-stack" id="hv-ft-stack">
    <button type="button" class="hv-ft-btn hv-ft-btn--hadir" id="hv-ft-kehadiran"
        aria-label="Status kehadiran perangkat" aria-expanded="false" aria-controls="hv-keh-panel">
        <i class="fas fa-user-check" aria-hidden="true"></i>
    </button>
    @if ($waAktif && $waUrl)
        <a class="hv-ft-btn hv-ft-btn--wa" id="hv-ft-wa" href="{{ $waUrl }}" target="_blank"
            rel="noopener noreferrer" aria-label="{{ $waLabel }}">
            <i class="fab fa-whatsapp" aria-hidden="true"></i>
        </a>
    @endif
    <button type="button" class="hv-ft-btn hv-ft-btn--up" id="hv-ft-up" aria-label="Kembali ke atas" hidden>
        <i class="fa fa-chevron-up" aria-hidden="true"></i>
    </button>
</div>

<div class="hv-keh-overlay" id="hv-keh-overlay" hidden></div>

<aside class="hv-keh-panel" id="hv-keh-panel" role="dialog" aria-modal="true"
    aria-label="Panel informasi status kehadiran" tabindex="-1" hidden>
    <div class="hv-keh-head">
        <button type="button" class="hv-keh-close" id="hv-keh-close" aria-label="Tutup panel">
            <i class="fa fa-times" aria-hidden="true"></i>
        </button>
        <p class="hv-keh-eyebrow">Panel Informasi</p>
        <h2 class="hv-keh-title">Status <span>Kehadiran</span></h2>
        <p class="hv-keh-date" id="hv-keh-date"></p>
    </div>
    <div class="hv-keh-list" id="hv-keh-list"></div>
    <div class="hv-keh-handle-area" aria-hidden="true"><span class="hv-keh-handle"></span></div>
</aside>

<script type="text/javascript">
    (function () {
        'use strict';
        // Jika partial pernah ter-include dua kali, cukup inisialisasi sekali
        var awal = document.getElementById('hv-ft-stack');
        if (!awal || awal.getAttribute('data-init') === '1') return;
        awal.setAttribute('data-init', '1');

        var API = @json(route('api.pemerintah'));
        var MAKS_HALAMAN = 10;
        var teksHadir = @json(theme_config('kehadiran_text_hadir') ?: 'Hadir di Kantor');
        var teksTidak = @json(theme_config('kehadiran_text_tidak') ?: 'Tidak di Kantor');
        var teksBelum = 'Belum Rekam Kehadiran';
        var teksLibur = 'Libur';
        var sebutanPangulu = @json(setting('sebutan_pangulu') ?: 'Pangulu');

        function $(id) {
            return document.getElementById(id);
        }

        var btnKehadiran = $('hv-ft-kehadiran');
        var btnUp = $('hv-ft-up');
        var overlay = $('hv-keh-overlay');
        var panel = $('hv-keh-panel');
        var daftar = $('hv-keh-list');
        var tanggalEl = $('hv-keh-date');
        var navBawah = document.querySelector('.hv-bottom');

        var dataPerangkat = [];
        var dataSiap = false;
        var sedangMemuat = false;

        function esc(teks) {
            return String(teks === null || teks === undefined ? '' : teks)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function status(a) {
            if (a.hari_libur === true || a.hari_libur === 1) return { kelas: 'hv-badge--libur', teks: teksLibur };
            var rec = a.kehadiran;
            if (rec) {
                var jamKeluar = rec.jam_keluar ? String(rec.jam_keluar) : '';
                var sudahKeluar = jamKeluar !== '' && jamKeluar.indexOf('00:00') !== 0;
                if (sudahKeluar) return { kelas: 'hv-badge--tidak', teks: teksTidak };
                return { kelas: 'hv-badge--hadir', teks: teksHadir };
            }
            var statusTeks = String(a.status_kehadiran || '');
            if (statusTeks.toLowerCase() === 'hadir') return { kelas: 'hv-badge--hadir', teks: teksHadir };
            if (statusTeks !== '') return { kelas: 'hv-badge--belum', teks: statusTeks };
            return { kelas: 'hv-badge--belum', teks: teksBelum };
        }

        function kunciUrut(a) {
            // 0 = pangulu, 1 = yang hadir, 2 = kelompok lain; urutan API dipertahankan
            var pangulu = String(a.nama_jabatan || '').trim().toLowerCase() === String(sebutanPangulu).trim().toLowerCase();
            if (pangulu) return 0;
            if (status(a).kelas === 'hv-badge--hadir') return 1;
            var lain = 2;
            return lain;
        }

        function urutkan(list) {
            return list.slice().sort(function (x, y) {
                var ax = x.attributes || x;
                var ay = y.attributes || y;
                return kunciUrut(ax) - kunciUrut(ay);
            });
        }

        function barisHtml(a) {
            var st = status(a);
            var nama = esc(a.nama || '');
            var foto = a.foto
                ? '<img class="hv-keh-foto" loading="lazy" decoding="async" src="' + esc(a.foto) + '" alt="Foto ' + nama + '" />'
                : '<span class="hv-keh-foto" aria-hidden="true"></span>';
            return '<div class="hv-keh-row">'
                + foto
                + '<div class="hv-keh-info">'
                + '<div class="hv-keh-nama">' + nama + '</div>'
                + '<div class="hv-keh-jabatan">' + esc(a.nama_jabatan || '') + '</div>'
                + '</div>'
                + '<span class="hv-badge ' + st.kelas + '">' + esc(st.teks) + '</span>'
                + '</div>';
        }

        function render(list) {
            if (!list.length) {
                daftar.innerHTML = '<p class="hv-keh-empty">Data perangkat tidak tersedia.</p>';
                return;
            }
            var urut = urutkan(list);
            var html = '';
            for (var i = 0; i < urut.length; i++) {
                html += barisHtml(urut[i].attributes || urut[i]);
            }
            daftar.innerHTML = html;
        }

        function ambilSemua() {
            var hasil = [];
            function langkah(url, halaman) {
                return fetch(url, { credentials: 'same-origin' })
                    .then(function (res) {
                        if (!res.ok) throw new Error('gagal-muat');
                        return res.json();
                    })
                    .then(function (body) {
                        hasil = hasil.concat(body.data || []);
                        var next = body.links && body.links.next;
                        if (next && halaman < MAKS_HALAMAN) return langkah(next, halaman + 1);
                        return hasil;
                    });
            }
            return langkah(API, 1);
        }

        function muatData() {
            sedangMemuat = true;
            daftar.innerHTML = '<p class="hv-keh-empty">Memuat data kehadiran...</p>';
            ambilSemua()
                .then(function (list) {
                    sedangMemuat = false;
                    dataPerangkat = list;
                    dataSiap = true;
                    render(dataPerangkat);
                })
                .catch(function () {
                    sedangMemuat = false;
                    dataSiap = false;
                    daftar.innerHTML = '<p class="hv-keh-empty">Data kehadiran tidak tersedia</p>';
                });
        }

        function mobile() {
            return window.matchMedia('(max-width: 1023px)').matches;
        }

        function panelTerbuka() {
            return panel.classList.contains('is-open');
        }

        function buka() {
            panel.style.transform = '';
            panel.style.transition = '';
            if (!dataSiap && !sedangMemuat) muatData();
            if (tanggalEl && !tanggalEl.textContent) {
                try {
                    tanggalEl.textContent = new Date().toLocaleDateString('id-ID', {
                        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                    });
                } catch (e) {
                    tanggalEl.textContent = '';
                }
            }
            overlay.hidden = false;
            panel.hidden = false;
            void panel.offsetWidth;
            panel.classList.add('is-open');
            panel.focus();
            btnKehadiran.setAttribute('aria-expanded', 'true');
            if (navBawah) navBawah.hidden = true;
        }

        function tutup() {
            panel.style.transform = '';
            panel.style.transition = '';
            panel.classList.remove('is-open');
            btnKehadiran.setAttribute('aria-expanded', 'false');
            btnKehadiran.focus();
            if (navBawah) navBawah.hidden = false;
            window.setTimeout(function () {
                if (!panelTerbuka()) {
                    panel.hidden = true;
                    overlay.hidden = true;
                }
            }, 500);
        }

        btnKehadiran.addEventListener('click', function () {
            if (panelTerbuka()) tutup(); else buka();
        });
        overlay.addEventListener('click', tutup);
        $('hv-keh-close').addEventListener('click', tutup);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !mobile() && panelTerbuka()) tutup();
        });

        // Gestur HP: geser ke kanan dari titik mana pun pada panel untuk menutup
        var geserAktif = false;
        var x0 = 0;
        var y0 = 0;
        var geserDx = 0;

        function resetGeser() {
            geserAktif = false;
            panel.style.transition = '';
            panel.style.transform = '';
        }

        panel.addEventListener('pointerdown', function (e) {
            if (!mobile() || !e.isPrimary || (e.pointerType === 'mouse' && e.button !== 0)) return;
            geserAktif = true;
            x0 = e.clientX;
            y0 = e.clientY;
            geserDx = 0;
            panel.style.transition = 'none';
        });

        panel.addEventListener('pointermove', function (e) {
            if (!geserAktif || !e.isPrimary) return;
            var mx = e.clientX - x0;
            var my = e.clientY - y0;
            if (Math.abs(my) > Math.abs(mx) && Math.abs(my) > 8) {
                resetGeser();
                return;
            }
            geserDx = mx;
            panel.style.transform = 'translateX(' + Math.max(0, mx) + 'px)';
        });

        function akhiriGeser(e) {
            if (!geserAktif) return;
            var vertikal = Math.abs((e.clientY || 0) - y0);
            var tutupSekarang = geserDx > 80 && geserDx > vertikal;
            resetGeser();
            if (tutupSekarang) tutup();
        }

        panel.addEventListener('pointerup', akhiriGeser);
        panel.addEventListener('pointercancel', resetGeser);

        function cekScroll() {
            btnUp.hidden = window.scrollY <= 800;
        }
        window.addEventListener('scroll', cekScroll, { passive: true });
        cekScroll();

        btnUp.addEventListener('click', function () {
            window.scrollTo(0, 0);
        });
    })();
</script>
