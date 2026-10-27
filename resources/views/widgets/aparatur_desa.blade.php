<div class="box">
    <div class="box-header">
        <h3 class="box-title">
            <i class="fas fa-user mr-1"></i>{{ $judul_widget }}
        </h3>
    </div>
    <div class="box-body">
        <div class="hv-ap-desktop">
    <!-- Desktop: OwlCarousel2 -->
    <div class="owl-carousel hv-pam-carousel">
        @foreach ($aparatur_desa['daftar_perangkat'] as $data)
            <div class="hv-pm-card">
                <div class="hv-pm-media">
                    <img src="{{ $data['foto'] }}" alt="{{ $data['nama'] }}" loading="lazy" decoding="async" class="hv-pm-foto">
                </div>
                @if (getWidgetSetting('aparatur_desa', 'overlay') == true)
                    <div class="hv-pm-info">
                        <div class="hv-pm-nama">{{ $data['nama'] }}</div>
                        <div class="hv-pm-jabatan">{{ $data['jabatan'] }}</div>
                        @if ($data['pamong_niap'])
                            <div class="text-[10px] text-gray-500 mt-1 uppercase font-bold">{{ setting('sebutan_nip_desa') }} : {{ $data['pamong_niap'] }}</div>
                        @endif
                        <div class="hv-pm-hint">
                            @if ($tampilkan_status_kehadiran)
                                @if ($data['kehadiran'] == 1)
                                    @if ($data['status_kehadiran'] == 'hadir')
                                        <span class="hv-badge hv-badge--hadir">Hadir</span>
                                    @else
                                        <span class="hv-badge hv-badge--{{ $data['status_kehadiran'] }}">{{ ucwords($data['status_kehadiran']) }}</span>
                                    @endif
                                @else
                                    <span class="hv-badge hv-badge--belum">Belum Rekam</span>
                                @endif
                            @else
                                <span class="hv-badge hv-badge--libur">Hari Libur</span>
                            @endif
                            <i class="fas fa-arrow-right hv-pm-arrow"></i>
                        </div>
                    </div>
                @else
                    <div class="hv-pm-info">
                        <div class="hv-pm-nama">{{ $data['nama'] }}</div>
                        <div class="hv-pm-jabatan">{{ $data['jabatan'] }}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
<div class="hv-ap-mobile">
    <!-- Mobile: Grid -->
    <div class="hv-pm-gridwrap">
        <div class="hv-pm-grid">
            @foreach ($aparatur_desa['daftar_perangkat'] as $data)
                <div class="hv-pm-card">
                    <div class="hv-pm-media">
                        <img src="{{ $data['foto'] }}" alt="{{ $data['nama'] }}" loading="lazy" decoding="async" class="hv-pm-foto">
                    </div>
                    @if (getWidgetSetting('aparatur_desa', 'overlay') == true)
                        <div class="hv-pm-info">
                            <div class="hv-pm-nama">{{ $data['nama'] }}</div>
                            <div class="hv-pm-jabatan">{{ $data['jabatan'] }}</div>
                            @if ($data['pamong_niap'])
                                <div class="text-[10px] text-gray-500 mt-1 uppercase font-bold">{{ setting('sebutan_nip_desa') }} : {{ $data['pamong_niap'] }}</div>
                            @endif
                            <div class="hv-pm-hint">
                                @if ($tampilkan_status_kehadiran)
                                    @if ($data['kehadiran'] == 1)
                                        @if ($data['status_kehadiran'] == 'hadir')
                                            <span class="hv-badge hv-badge--hadir">Hadir</span>
                                        @else
                                            <span class="hv-badge hv-badge--{{ $data['status_kehadiran'] }}">{{ ucwords($data['status_kehadiran']) }}</span>
                                        @endif
                                    @else
                                        <span class="hv-badge hv-badge--belum">Belum Rekam</span>
                                    @endif
                                @else
                                    <span class="hv-badge hv-badge--libur">Hari Libur</span>
                                @endif
                                <i class="fas fa-arrow-right hv-pm-arrow"></i>
                            </div>
                        </div>
                    @else
                        <div class="hv-pm-info">
                            <div class="hv-pm-nama">{{ $data['nama'] }}</div>
                            <div class="hv-pm-jabatan">{{ $data['jabatan'] }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
    </div>
</div>