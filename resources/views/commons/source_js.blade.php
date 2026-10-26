@if (cek_koneksi_internet())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-providers/1.6.0/leaflet-providers.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mapbox-gl/1.13.0/mapbox-gl.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mapbox-gl-leaflet/0.0.14/leaflet-mapbox-gl.min.js"></script>

@endif
<script src="{{ theme_asset('vendor/OwlCarousel2-2.3.4/owl.carousel.min.js', ['themeVersion' => $themeVersion]) }}"></script>

{{-- Mesin mode gelap: menyelaraskan peta Leaflet (lapisan gelap otomatis).
     Dimuat di sini supaya siap sebelum widget peta di body berjalan. --}}
<script src="{{ theme_asset('js/huta-mode-gelap.js', ['themeVersion' => $themeVersion]) }}"></script>

{{-- Pemuat data halaman Gempa (BMKG) + tombol Cetak halaman cuaca/gempa. --}}
<script src="{{ theme_asset('js/huta-gempa.js', ['themeVersion' => $themeVersion]) }}" defer></script>

@include('core::admin.layouts.components.token')
<script src="{{ asset('js/peta.js') }}"></script>
<script>
    var BASE_URL = '{{ base_url() }}';
    $.extend($.fn.dataTable.defaults, {
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "Semua"]
        ],
        pageLength: 10,
        language: {
            url: "{{ asset('bootstrap/js/dataTables.indonesian.lang') }}",
        }
    });
</script>

@if (!setting('inspect_element'))
    <script src="{{ asset('js/disabled.min.js') }}"></script>
@endif

{{-- Aksesibilitas karusel Owl: item klon disembunyikan dari pembaca layar dan
     alur fokus keyboard (nama aparatur tidak lagi terbaca berkali-kali), serta
     kontrol titik navigasi diberi nama yang bisa dibaca. --}}
<script>
    (function () {
        function hvA11ySweep(root) {
            var scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('.owl-carousel').forEach(function (stage) {
                stage.querySelectorAll('.owl-item.cloned').forEach(function (item) {
                    item.setAttribute('aria-hidden', 'true');
                    if ('inert' in item) {
                        item.inert = true;
                    } else {
                        item.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach(function (f) {
                            f.setAttribute('tabindex', '-1');
                        });
                    }
                });
                var dots = stage.querySelector('.owl-dots');
                if (dots) {
                    if (!dots.getAttribute('aria-label')) dots.setAttribute('aria-label', 'Navigasi slide');
                    dots.querySelectorAll('.owl-dot').forEach(function (d, i) {
                        if (!d.getAttribute('aria-label')) d.setAttribute('aria-label', 'Slide ' + (i + 1));
                    });
                }
            });
        }
        ['initialized.owl.carousel', 'translated.owl.carousel', 'refreshed.owl.carousel'].forEach(function (evt) {
            document.addEventListener(evt, function (e) {
                hvA11ySweep(e.target && e.target.classList && e.target.classList.contains('owl-carousel') ? e.target : document);
            }, true);
        });
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { hvA11ySweep(); });
        } else {
            hvA11ySweep();
        }
        setTimeout(function () { hvA11ySweep(); }, 1500);
    })();
</script>
