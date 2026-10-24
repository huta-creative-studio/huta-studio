@php
    $is_home = uri_string() === '' && !request()->query('cari');
    $nav_items = menu_tema() ?: [];
@endphp

<nav class="hv-nav {{ $is_home ? 'hv-nav-home' : '' }}" id="hv-nav" aria-label="Menu utama">
    <div class="hv-nav-inner">
        <a href="{{ site_url('/') }}" class="hv-nav-brand">
            <img src="{{ gambar_desa($desa['logo']) }}" alt="Logo {{ title_case($desa['nama_desa']) }}" class="hv-nav-logo">
            <span class="hv-nav-names">
                <strong>{{ ucwords(setting('sebutan_desa')) }} {{ title_case($desa['nama_desa']) }}</strong>
                <small>{{ ucfirst(setting('sebutan_kecamatan_singkat')) }} {{ title_case($desa['nama_kecamatan']) }}, {{ ucfirst(setting('sebutan_kabupaten_singkat')) }} {{ title_case($desa['nama_kabupaten']) }}</small>
            </span>
        </a>

        <ul class="hv-nav-menu">
            <li><a href="{{ site_url('/') }}"><i class="fa fa-home"></i><span class="hv-nav-menu-label"> Beranda</span></a></li>
            @foreach ($nav_items as $menu)
                @php $kids = $menu['childrens'] ?? [] @endphp
                <li class="{{ $kids ? 'hv-has-drop' : '' }}">
                    <a href="{{ $kids ? '#' : $menu['link_url'] }}">{!! $menu['nama'] !!} @if ($kids)<i class="fas fa-chevron-down hv-caret"></i>@endif</a>
                    @if ($kids)
                        <ul class="hv-dropdown">
                            @foreach ($kids as $child)
                                @php $gkids = $child['childrens'] ?? [] @endphp
                                <li class="{{ $gkids ? 'hv-has-drop' : '' }}">
                                    <a href="{{ $gkids ? '#' : $child['link_url'] }}">{!! $child['nama'] !!} @if ($gkids)<i class="fas fa-chevron-right hv-caret"></i>@endif</a>
                                    @if ($gkids)
                                        <ul class="hv-dropdown hv-dropdown-sub">
                                            @foreach ($gkids as $grand)
                                                <li><a href="{{ $grand['link_url'] }}">{!! $grand['nama'] !!}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="hv-nav-actions">
            <button type="button" class="hv-dark-toggle" onclick="hvToggleDark()" aria-label="Ganti mode gelap">
                <i class="fas fa-moon hv-dark-icon"></i>
            </button>
            <a href="{{ site_url('siteman') }}" class="hv-btn hv-nav-login" data-open-login>Login</a>
            <button type="button" class="hv-burger" id="hv-burger" aria-label="Buka menu" onclick="document.getElementById('hv-drawer').classList.add('open');document.body.style.overflow='hidden'">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<div class="hv-drawer" id="hv-drawer" aria-hidden="true">
    <div class="hv-drawer-head">
        <strong>Menu</strong>
        <button type="button" class="hv-drawer-close" aria-label="Tutup menu" onclick="document.getElementById('hv-drawer').classList.remove('open');document.body.style.overflow=''">&times;</button>
    </div>
    <form method="get" action="{{ site_url('/') }}" class="hv-drawer-search" role="search">
        <input type="search" name="cari" value="{{ request()->get('cari', '') }}" placeholder="Cari informasi..." aria-label="Cari informasi">
        <button type="submit" class="hv-btn"><i class="fas fa-search"></i></button>
    </form>
    <ul class="hv-drawer-list">
        <li><a href="{{ site_url('/') }}"><i class="fa fa-home"></i> Beranda</a></li>
        @foreach ($nav_items as $menu)
            @php $kids = $menu['childrens'] ?? [] @endphp
            @if ($kids)
                <li>
                    <details>
                        <summary>{!! $menu['nama'] !!}</summary>
                        <ul>
                            @foreach ($kids as $child)
                                <li><a href="{{ $child['link_url'] }}">{!! $child['nama'] !!}</a></li>
                            @endforeach
                        </ul>
                    </details>
                </li>
            @else
                <li><a href="{{ $menu['link_url'] }}">{!! $menu['nama'] !!}</a></li>
            @endif
        @endforeach
        <li class="hv-drawer-sep"></li>
        <li><a href="{{ site_url('siteman') }}" data-open-login><i class="fas fa-user-shield"></i> Login Admin</a></li>
        @if (setting('layanan_mandiri') == 1)
            <li><a href="{{ site_url('layanan-mandiri') }}"><i class="fas fa-envelope-open-text"></i> Layanan Mandiri</a></li>
        @endif
    </ul>
</div>

<script>
    (function () {
        var nav = document.getElementById('hv-nav');
        if (!nav) return;
        var home = nav.classList.contains('hv-nav-home');
        function onScroll() {
            var y = window.scrollY || 0;
            if (!home || y > 40) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
</script>
