<div class="container">
    @includeWhen($transparansi, 'theme::partials.apbdesa', $transparansi)
</div>

@php
    $social_media = [
        'facebook' => ['color' => 'bg-blue-600', 'icon' => 'fa-facebook-f'],
        'twitter' => ['color' => 'bg-blue-400', 'icon' => 'fa-twitter'],
        'instagram' => ['color' => 'bg-pink-500', 'icon' => 'fa-instagram'],
        'telegram' => ['color' => 'bg-blue-500', 'icon' => 'fa-telegram'],
        'whatsapp' => ['color' => 'bg-green-500', 'icon' => 'fa-whatsapp'],
        'youtube' => ['color' => 'bg-red-500', 'icon' => 'fa-youtube'],
    ];
@endphp

@foreach ($sosmed as $social)
    @if ($social['link'])
        @php
            $social_media[strtolower($social['nama'])]['link'] = $social['link'];
        @endphp
    @endif
@endforeach

<footer class="hv-footer">
    <div class="hv-container hv-footer-grid">
        <div class="hv-footer-col">
            <div class="hv-footer-brand">
                <img src="{{ gambar_desa($desa['logo']) }}" alt="Logo {{ title_case($desa['nama_desa']) }}" class="hv-footer-logo">
                <div>
                    <strong>{{ ucwords(setting('sebutan_desa')) }} {{ title_case($desa['nama_desa']) }}</strong>
                    <small>{{ ucfirst(setting('sebutan_kecamatan_singkat')) }} {{ title_case($desa['nama_kecamatan']) }}, {{ ucfirst(setting('sebutan_kabupaten_singkat')) }} {{ title_case($desa['nama_kabupaten']) }}</small>
                </div>
            </div>
            <p class="hv-footer-desc">Sistem informasi desa untuk transparansi dan pelayanan publik yang lebih baik bagi seluruh warga.</p>
            <ul class="hv-footer-social">
                @foreach ($social_media as $sm)
                    @if ($link = $sm['link'])
                        <li><a href="{{ $link }}" class="{{ $sm['color'] }}" aria-label="{{ ucfirst(array_search($sm, $social_media)) }}"><i class="fab {{ $sm['icon'] }}"></i></a></li>
                    @endif
                @endforeach
            </ul>
        </div>

        <div class="hv-footer-col">
            <h4>Alamat Kantor</h4>
            <ul class="hv-footer-list">
                <li><i class="fas fa-map-marker-alt"></i> {{ $desa['alamat_kantor'] ?? setting('alamat_kantor') ?? '—' }}</li>
                @if (!empty($desa['telepon']))
                    <li><i class="fas fa-phone"></i> {{ $desa['telepon'] }}</li>
                @endif
                @if (!empty($desa['email_desa']))
                    <li><i class="fas fa-envelope"></i> {{ $desa['email_desa'] }}</li>
                @endif
            </ul>
        </div>

        <div class="hv-footer-col">
            <h4>Link Cepat</h4>
            <ul class="hv-footer-list">
                <li><a href="{{ site_url('data-statistik') }}"><i class="fas fa-chart-bar"></i> Statistik Desa</a></li>
                <li><a href="{{ site_url('pembangunan') }}"><i class="fas fa-hard-hat"></i> Pembangunan</a></li>
                <li><a href="{{ site_url('ppid') }}"><i class="fas fa-folder-open"></i> Informasi Publik (PPID)</a></li>
                <li><a href="{{ site_url('galeri') }}"><i class="fas fa-images"></i> Galeri</a></li>
                @if (theme_config('feed_url'))
                <li><a href="{{ theme_config('feed_url') }}" target="_blank" rel="noopener"><i class="fas fa-rss"></i> Feed RSS</a></li>
                @endif
                <li><a href="{{ site_url('siteman') }}"><i class="fas fa-user-shield"></i> Login Admin</a></li>
            </ul>
        </div>
    </div>

    <div class="hv-footer-bottom">
        <div class="hv-container hv-footer-bottom-inner">
            <span>&copy; {{ date('Y') }} {{ $nama_desa }} · Hak cipta situs desa</span>
            <span>
                <a href="https://opensid.my.id" target="_blank" rel="noopener">OpenSID {{ ambilVersi() }}</a>
                · Huta Studio {{ $themeVersion }}
            </span>
        </div>
    </div>
</footer>
