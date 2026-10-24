@php
    $themeVersion = theme_version('huta-studio');
    $nama_desa = ucwords(setting('sebutan_desa')) . ' ' . ucwords($desa['nama_desa']);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('theme::commons.meta')
    @include('theme::commons.source_css')
    @include('theme::commons.source_js')
    <title>@yield('title')</title>
    <script>
        (function () {
            var d = localStorage.getItem('huta_dark');
            var dark = d === '1' || (d === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) document.documentElement.classList.add('dark');
        })();
        function hvToggleDark() {
            var el = document.documentElement;
            var on = el.classList.toggle('dark');
            localStorage.setItem('huta_dark', on ? '1' : '0');
            var icons = document.querySelectorAll('.hv-dark-icon');
            for (var i = 0; i < icons.length; i++) {
                icons[i].className = icons[i].className.replace(/\b(fa-moon|fa-sun)\b/, on ? 'fa-sun' : 'fa-moon');
            }
            // Isi visual yang dibuat JavaScript ikut mode gelap (peta Leaflet).
            if (typeof window.hvTerapkanModeGelap === 'function') {
                window.hvTerapkanModeGelap();
            }
        }
    </script>
    @stack('styles')
</head>

<body class="font-primary bg-gray-100" style="--huta-aksen: {{ theme_config('warna_aksen') ?: '#15803d' }};">
    @include('theme::partials.loading-modern')
    @include('theme::commons.header')

    @yield('layout')

    @include('theme::commons.footer')

    <script src="{{ theme_asset('js/script.min.js') }}&{{ $themeVersion }}"></script>
    <script type="text/javascript">
        function formatRupiah(angka, prefix = 'Rp ') {
            if (angka === null || angka === undefined) return (prefix ?? 'Rp ') + '0,00';
            var number_string = angka.toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix == undefined ? rupiah : (rupiah ? 'Rp ' + rupiah : '') + ',00';
        }
    </script>
    @stack('scripts')
    @include('theme::partials.login-modal')
    @include('theme::partials.bottom-nav')
    @include('theme::partials.floating-tools')
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
}
</script>
</body>

</html>
