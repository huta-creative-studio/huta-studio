@extends('theme::layouts.full-content')

@section('content')
    <nav role="navigation" aria-label="navigation" class="breadcrumb">
        <ol>
            <li><a href="{{ site_url() }}">Beranda</a></li>
            <li aria-current="page">
                {{ ucwords(setting('sebutan_pemerintah_desa')) }}
            </li>
        </ol>
    </nav>
    <h1 class="text-h2">
        {{ ucwords(setting('sebutan_pemerintah_desa')) }}
    </h1>

    <div class="hv-pm-gridwrap">
        <div class="hv-pm-grid py-1" id="pemerintah-list" tabindex="0" role="group" aria-label="Daftar perangkat nagori, geser ke kiri"></div>
        <div class="hv-pm-hint">
            <span class="hv-pm-count"></span>
            <span class="hv-pm-arrow" aria-hidden="true">geser ke kiri&nbsp;→</span>
        </div>
    </div>

    @include('theme::commons.pagination')
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            var teksHadir = @json(theme_config('kehadiran_text_hadir') ?: 'Hadir di Kantor');
            var teksTidak = @json(theme_config('kehadiran_text_tidak') ?: 'Tidak di Kantor');

            function loadPemerintah(params = {}) {
                var apiPemerintah = '{{ route('api.pemerintah') }}';

                $('#pagination-container').hide();
                $('#pemerintah-list').html('<p class="text-center">Memuat...</p>');

                $.get(apiPemerintah, params, function(data) {
                    var pemerintah = data.data;
                    var pemerintahList = $('#pemerintah-list');
                    pemerintahList.empty();

                    if (!pemerintah.length) {
                        pemerintahList.html(`<p class="py-2"> ${setting.sebutan_pemerintah_desa} tidak tersedia.</p>`);
                        return;
                    }

                    var mediaSosialPlatforms = JSON.parse(setting.media_sosial_pemerintah_desa);

                    pemerintah.forEach(function(item) {
                        var mediaSosial = '';
                        var mediaSosialPengurus = item.attributes.media_sosial || {};

                        mediaSosialPlatforms.forEach((platform) => {
                            var link = mediaSosialPengurus[platform];
                            if (!link) {
                                return;
                            }
                            mediaSosial += `
                            <a href="${link}" target="_blank" rel="noopener" class="hv-pm-sosmed">
                                <i class="fab fa-lg fa-${platform}"></i>
                            </a>
                        `;
                        });

                        var recKehadiran = item.attributes.kehadiran;
                        var statusTeks = String(item.attributes.status_kehadiran || '');
                        var statusHadir = statusTeks.toLowerCase() === 'hadir';
                        var jamMasuk = recKehadiran && recKehadiran.jam_masuk ? String(recKehadiran.jam_masuk) : '';
                        var jamKeluar = recKehadiran && recKehadiran.jam_keluar ? String(recKehadiran.jam_keluar) : '';
                        var sudahKeluar = jamKeluar !== '' && jamKeluar.indexOf('00:00') !== 0;
                        var diKantor = recKehadiran ? (!sudahKeluar && (jamMasuk !== '' || statusHadir)) : statusHadir;
                        var hadirBadge = recKehadiran ? diKantor : statusHadir;
                        var teksBadge = recKehadiran
                            ? (diKantor ? teksHadir : teksTidak)
                            : (statusTeks !== '' ? statusTeks : 'Belum Rekam Kehadiran');
                        var pemerintahHTML = `
                            <div class="hv-pm-card">
                                <div class="hv-pm-media">
                                    <img class="hv-pm-blur" loading="lazy" decoding="async" src="${item.attributes.foto || ''}" alt="" aria-hidden="true" />
                                    <img class="hv-pm-foto" loading="lazy" decoding="async" src="${item.attributes.foto || ''}" alt="Foto ${item.attributes.nama}" />
                                </div>
                                <div class="hv-pm-info text-sm text-center">
                                    <span class="hv-pm-nama text-h6">${item.attributes.nama}</span>
                                    <span class="hv-pm-jabatan block">${item.attributes.nama_jabatan}</span>
                                    ${
                                        item.attributes.pamong_niap
                                            ? `<span class="block">${item.attributes.sebutan_nip_desa}: ${item.attributes.pamong_niap}</span>`
                                            : ''
                                    }
                                    ${
                                        item.attributes.kehadiran == 1 || item.attributes.hari_libur == false
                                            ? `<span class="hv-badge ${hadirBadge ? 'hv-badge--hadir' : 'hv-badge--belum'}">${teksBadge}</span>`
                                            : ''
                                    }
                                    <div class="hv-pm-sosmed">${mediaSosial}</div>
                                </div>
                            </div>
                        `;

                        pemerintahList.append(pemerintahHTML);
                    });

                    $('.hv-pm-count').text(data.data.length + ' perangkat');

                    initPagination(data);
                });
            }

            $('.pagination').on('click', '.btn-page', function() {
                var params = {};
                var page = $(this).data('page');

                params['page[number]'] = page;

                loadPemerintah(params);
            });

            loadPemerintah();
        });
    </script>
@endpush
