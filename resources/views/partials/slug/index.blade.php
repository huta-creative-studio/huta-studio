<?php defined('BASEPATH')||exit('No direct script access allowed');?>@includeIf('theme::commons.huta-loader')
@php if(function_exists('huta_social_image_dispatch'))huta_social_image_dispatch(); @endphp
@extends('theme::layouts.full-content')
@php extract(huta_view_arsip_context($desa ?? [], $config ?? [], $menu ?? null), EXTR_SKIP); @endphp
@if($hutaArsipIndeks&&$hutaIndeksEnabled)@include('theme::commons.asset_highcharts')@endif
@section('content')
@if($hutaArsipPenduduk)
<section class="huta-cek-standalone" aria-label="Pencarian Penduduk">
@if(!$hutaPencarianPendudukEnabled)<div class="huta-cek-status" role="status"><strong>Pencarian Penduduk Tidak Aktif</strong><span>Layanan Pencarian Penduduk saat ini tidak ditampilkan oleh pengelola website.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div>
@elseif($hutaCekPendudukLocked)<div class="huta-cek-status" role="status"><strong>Pencarian Penduduk Tidak Aktif</strong><span>Layanan Pencarian Penduduk saat ini tidak ditampilkan oleh pengelola website.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div>
@elseif($hutaCekPendudukVisible)@includeIf('theme::partials.home.addons.penduduk')
@else<div class="huta-cek-status" role="status"><strong>Pencarian Penduduk Belum Tersedia</strong><span>Layanan ini belum dapat digunakan saat ini. Silakan kembali beberapa saat lagi.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div>@endif
</section>
@elseif($hutaArsipIndeks)
@if(!$hutaIndeksEnabled)
<section class="huta-cek-standalone" aria-label="Indeks Desa"><div class="huta-cek-status" role="status"><strong>Indeks Desa Tidak Ditampilkan</strong><span>Halaman Indeks Desa saat ini disembunyikan oleh pengelola website.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div></section>
@else
<div class="article-single huta-indeks-page" data-page="indeks-desa"
    data-endpoint="{{ $hutaIndeksBoot['endpoint'] ?? rtrim(site_url('arsip'), '/') . '?req=huta-indeks&year=' . $hutaIndeksYear }}"
    data-token="{{ $hutaIndeksBoot['token'] ?? '' }}"
    data-year="{{ $hutaIndeksYear }}"
    data-code="{{ $hutaIndeksCode }}"
    data-code-candidates="{{ json_encode($hutaIndeksCodes, JSON_UNESCAPED_SLASHES) }}"
    data-base-url="{{ rtrim(site_url('arsip'), '/') . '?req=huta-indeks&year=' }}"
    data-available-years="{{ json_encode($hutaIndeksYears, JSON_UNESCAPED_SLASHES) }}">
    <div class="container-page mb-20">
        <div class="huta-page-card huta-indeks-card">
            <div class="huta-page-title huta-indeks-title">
                <div class="huta-title-icon huta-title-icon-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 3h16v18H4V3Zm2 2v14h12V5H6Zm2 2h8v2H8V7Zm0 4h8v2H8v-2Zm0 4h5v2H8v-2Z"/></svg>
                </div>
                <div class="huta-indeks-title-copy">
                    <h2>Indeks Desa <span id="hutaIndeksHeadingYear">{{ $hutaIndeksYear }}</span></h2>
                    <p id="hutaIndeksSubtitle">Memeriksa ketersediaan dan waktu pembaruan data resmi.</p>
                </div>
                <div class="huta-indeks-year-control huta-no-print">
                    <label for="hutaIndeksYear">Tahun Data</label>
                    <span id="hutaIndeksYearBusy" class="huta-indeks-year-busy" hidden aria-live="polite">Memuat</span>
                    <select id="hutaIndeksYear" aria-label="Pilih tahun Indeks Desa">
                        @foreach($hutaIndeksYears as $yearOption)
                            @php
                                $hutaYearBoot = function_exists('huta_indeks_desa_bootstrap')
                                    ? huta_indeks_desa_bootstrap($hutaIndeksDesa, $yearOption)
                                    : [];
                            @endphp
                            <option
                                value="{{ $yearOption }}"
                                data-endpoint="{{ $hutaYearBoot['endpoint'] ?? (rtrim(site_url('arsip'), '/') . '?req=huta-indeks&year=' . $yearOption) }}"
                                data-token="{{ $hutaYearBoot['token'] ?? '' }}"
                                @selected($yearOption === $hutaIndeksYear)
                            >{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="hutaIndeksAlert" class="huta-alert huta-alert-danger" hidden role="alert"></div>
            <div id="hutaIndeksLoading" class="huta-indeks-loading" role="status" aria-live="polite">
                <div class="huta-skeleton-index"><strong>Memuat Indeks Desa</strong><small>Data sedang dicocokkan dengan kode desa pada OpenSID.</small></div>
            </div>
            <div id="hutaIndeksContent" class="huta-indeks-content" hidden>
                <section class="huta-indeks-metrics" aria-label="Ringkasan Indeks Desa">
                    <article class="huta-indeks-metric huta-indeks-tone-green">
                        <span class="huta-indeks-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H2V3h2v16Zm3-3-1.4-1.4 4.2-4.2 3 3 5.6-6.4 1.5 1.3-7 8-3.1-3.1L7 16Z"/></svg></span>
                        <div><small>Nilai Indeks Desa</small><strong id="hutaIndeksScore">-</strong><em>Skala 0–100 persen</em></div>
                    </article>
                    <article class="huta-indeks-metric huta-indeks-tone-purple">
                        <span class="huta-indeks-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 2h14a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm1 3v4h12V5H6Zm0 7v2h2v-2H6Zm4 0v2h2v-2h-2Zm4 0v2h4v-2h-4Zm-8 4v2h2v-2H6Zm4 0v2h2v-2h-2Zm4 0v2h4v-2h-4Z"/></svg></span>
                        <div><small>Total Skor</small><strong id="hutaIndeksRawScore">-</strong><em id="hutaIndeksScoreMaximum">Dari skor maksimum 635</em></div>
                    </article>
                    <article class="huta-indeks-metric huta-indeks-tone-orange">
                        <span class="huta-indeks-metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3Zm10 0h8v8h-8V3ZM3 13h8v8H3v-8Zm10 0h8v8h-8v-8ZM5 5v4h4V5H5Zm10 0v4h4V5h-4ZM5 15v4h4v-4H5Zm10 0v4h4v-4h-4Z"/></svg></span>
                        <div><small>Subdimensi Terbaca</small><strong id="hutaIndeksSubdimensionCount">0</strong><em>Komponen pembentuk indeks</em></div>
                    </article>
                </section>
                <div class="huta-indeks-topgrid">
                    <section class="huta-panel huta-indeks-identity-panel">
                        <div class="huta-panel-heading">
                            <div><h3>Identitas Wilayah</h3><p>Data wilayah dipadankan otomatis dengan kode desa OpenSID.</p></div>
                        </div>
                        <div class="huta-indeks-identity">
                            <div><span>Tahun Data</span><strong id="hutaIndeksIdentityYear">{{ $hutaIndeksYear }}</strong></div>
                            <div><span>Kode Desa</span><strong id="hutaIndeksCode">{{ $hutaIndeksCode ?: '-' }}</strong></div>
                            <div><span>{{ ucwords(setting('sebutan_desa')) }}</span><strong id="hutaIndeksVillage">-</strong></div>
                            <div><span>{{ ucwords(setting('sebutan_kecamatan')) }}</span><strong id="hutaIndeksDistrict">-</strong></div>
                            <div><span>{{ ucwords(setting('sebutan_kabupaten')) }}</span><strong id="hutaIndeksRegency">-</strong></div>
                            <div><span>Provinsi</span><strong id="hutaIndeksProvince">-</strong></div>
                        </div>
                        <div class="huta-indeks-insight" id="hutaIndeksInsight" hidden>
                            <div><i class="fa fa-arrow-circle-up"></i><span>Kontribusi terkuat</span><strong id="hutaIndeksStrongest">-</strong></div>
                            <div><i class="fa fa-exclamation-circle"></i><span>Perlu perhatian</span><strong id="hutaIndeksWeakest">-</strong></div>
                        </div>
                    </section>
                    <section class="huta-panel huta-indeks-chart-panel">
                        <div class="huta-panel-heading huta-panel-heading-actions">
                            <div><h3>Kontribusi Enam Dimensi</h3><p>Nilai menunjukkan persentase kontribusi terhadap Indeks Desa.</p></div>
                            <div class="huta-chart-actions huta-no-print" role="group" aria-label="Pilihan grafik Indeks Desa">
                                <button type="button" class="active" data-indeks-chart="pie" aria-pressed="true">Pie</button>
                                <button type="button" data-indeks-chart="bar" aria-pressed="false">Batang</button>
                            </div>
                        </div>
                        <div class="huta-chart-wrap"><div id="hutaIndeksChart"></div></div>
                        <div id="hutaIndeksChartFallback" class="huta-indeks-chart-fallback" hidden></div>
                    </section>
                </div>
                <section class="huta-panel huta-indeks-dimensions-panel">
                    <div class="huta-panel-heading">
                        <div><h3>Ringkasan Dimensi</h3><p>Skor setiap dimensi merupakan penjumlahan subdimensi pembentuknya.</p></div>
                    </div>
                    <div id="hutaIndeksDimensions" class="huta-indeks-dimensions"></div>
                </section>
                <section class="huta-panel huta-indeks-subdimension-panel">
                    <div class="huta-panel-heading huta-panel-heading-actions">
                        <div><h3>Rincian Capaian Subdimensi</h3><p>Disajikan seperti indikator SDGs dengan skor dan nilai indeks persentase.</p></div>
                        <div class="huta-table-search huta-no-print">
                            <label for="hutaIndeksSearch">Pencarian</label>
                            <div><input type="search" id="hutaIndeksSearch" placeholder="Cari dimensi atau subdimensi..."><button type="button" id="hutaIndeksReset">Reset</button></div>
                        </div>
                    </div>
                    <div id="hutaIndeksSubdimensions" class="huta-indeks-subdimensions"></div>
                    <div class="huta-table-footer" id="hutaIndeksTableInfo">Menampilkan 0 subdimensi.</div>
                </section>
                <section class="huta-panel huta-indeks-table-panel">
                    <div class="huta-panel-heading">
                        <div><h3>Tabel Indikator Persentase</h3><p>Tampilan ringkas untuk pemeriksaan dan pencetakan data.</p></div>
                    </div>
                    <div class="huta-table-scroll">
                        <table class="huta-data-table huta-indeks-percentage-table" id="hutaIndeksTable">
                            <thead><tr><th class="dnv-is-6f73f3eae6">No.</th><th class="dnv-is-e3bda251e8">Dimensi</th><th>Subdimensi</th><th class="dnv-is-60746827b0">Skor</th><th class="dnv-is-4854a4d713">Indeks</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endif
@elseif($hutaArsipCuaca)
@if(!$hutaCuacaEnabled)
<section class="huta-cek-standalone" aria-label="Cuaca dan Kondisi Udara"><div class="huta-cek-status" role="status"><strong>Halaman Cuaca Tidak Ditampilkan</strong><span>Halaman Cuaca &amp; Kondisi Udara saat ini disembunyikan oleh pengelola website. Widget Cuaca di beranda tetap mengikuti pengaturan Widget OpenSID.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div></section>
@else
<div class="article-single huta-weather-page" data-page="huta-cuaca">
<div class="container-page mb-20">
        <div class="huta-page-card huta-environment-card">
            <div class="huta-page-title huta-environment-title">
                <div class="huta-title-icon huta-title-icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.5 19a4.5 4.5 0 0 1-.7-8.95A6.5 6.5 0 0 1 18.2 8.3 5.5 5.5 0 0 1 18.5 19h-12Zm0-2h12a3.5 3.5 0 1 0-1.36-6.72l-1.16.49-.21-1.24A4.5 4.5 0 0 0 6.9 10.8l-.12 1.13-1.13-.03A2.55 2.55 0 0 0 3 14.45 2.55 2.55 0 0 0 5.55 17H6.5Z"/></svg></div>
                <div class="huta-environment-title-copy"><h2>Cuaca &amp; Kondisi Udara</h2><p>Prakiraan wilayah desa, kondisi saat ini, serta kualitas udara dalam satu halaman.</p></div>
                <div class="huta-page-actions huta-no-print">
                    @if(!empty($hutaCuacaData['ok']))
                        <button class="huta-print-action" type="button" data-huta-print="weather" aria-label="Cetak informasi cuaca"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v4H7V3Zm8 2H9v2h6V5Zm4 4a3 3 0 0 1 3 3v5h-4v4H6v-4H2v-5a3 3 0 0 1 3-3h14Zm-3 10v-5H8v5h8Zm3-4h1v-3a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3h2v-3h12v3h1Z"/></svg><span>Cetak</span></button>
                    @endif
                    @if($hutaPreviousUrl!=='')
                        <a class="huta-page-back" href="{{ $hutaPreviousUrl }}" aria-label="Kembali ke halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.4 5.4 8.8 12l6.6 6.6-1.4 1.4L6 12l8-8 1.4 1.4Z"/></svg><span>Kembali</span></a>
                    @endif
                </div>
            </div>
            @if(empty($hutaCuacaData['ok']))
                <div class="huta-empty-state"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v6h-2V7Zm0 8h2v2h-2v-2Zm1-13a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/></svg><strong>Data cuaca belum tersedia</strong><span>{{ $hutaCuacaData['message'] ?? 'Silakan coba kembali beberapa saat lagi.' }}</span></div>
            @else
                @php extract(huta_view_environment_context((array)$hutaCuacaData), EXTR_SKIP); @endphp
                <section class="huta-environment-metrics" aria-label="Ringkasan cuaca">
                    <article class="huta-environment-metric huta-weather-now">
                        <span class="huta-environment-metric-icon"><img src="{{ huta_weather_icon_url((array)$hutaCurrentWeather) }}" alt="" width="54" height="54" decoding="async"></span>
                        <div><small>{{ $hutaCuacaData['current_label'] ?? 'Cuaca Saat Ini' }}</small><strong>{{ $hutaCurrentTemperature }}</strong><em>{{ $hutaCurrentWeather['description'] ?? 'Belum tersedia' }}{{ !empty($hutaCurrentWeather['time']) ? ' · '.$hutaCurrentWeather['time'].($hutaTimezoneLabel!==''?' '.$hutaTimezoneLabel:'') : '' }}</em></div>
                    </article>
                    <article class="huta-environment-metric"><span class="huta-environment-metric-icon"><svg viewBox="0 0 24 24"><path d="M12 2a4 4 0 0 0-4 4v7.26A6 6 0 1 0 16 13.26V6a4 4 0 0 0-4-4Zm2 12.35.55.3a4 4 0 1 1-5.1 0l.55-.3V6a2 2 0 1 1 4 0v8.35ZM11 7h2v8.17a2 2 0 1 1-2 0V7Z"/></svg></span><div><small>Rentang Suhu Hari Ini</small><strong>{{ $hutaTodayTemperature }}</strong><em>Kelembapan {{ $hutaTodaySummary['humidity_min'] ?? '-' }}–{{ $hutaTodaySummary['humidity_max'] ?? '-' }}%</em></div></article>
                    <article class="huta-environment-metric"><span class="huta-environment-metric-icon"><svg viewBox="0 0 24 24"><path d="M3 8h11a3 3 0 1 0-3-3h2a1 1 0 1 1 1 1H3v2Zm0 4h16a3 3 0 1 1-3 3h2a1 1 0 1 0 1-1H3v-2Zm0 4h8v2H3v-2Z"/></svg></span><div><small>Angin</small><strong>{{ $hutaCurrentWind }}</strong><em>Arah {{ $hutaCurrentWeather['wind_direction'] ?? '-' }}</em></div></article>
                    <article class="huta-environment-metric huta-air-metric huta-air-{{ $hutaAirClass }}"><span class="huta-environment-metric-icon"><svg viewBox="0 0 24 24"><path d="M4 17h16v2H4v-2Zm1-4h14v2H5v-2Zm2-4h10v2H7V9Zm3-4h4v2h-4V5Z"/></svg></span><div><small>Kualitas Udara PM2.5</small><strong>{{ $hutaAirPm25 }}</strong><em class="huta-air-category-text">{{ $hutaAir['category'] ?? 'Belum tersedia' }}</em></div></article>
                </section>
                <div class="huta-environment-topgrid">
                    <section class="huta-panel huta-environment-location-panel">
                        <div class="huta-panel-heading"><div><h3>Informasi Wilayah</h3><p>Lokasi mengikuti identitas desa aktif pada OpenSID.</p></div></div>
                        <div class="huta-environment-identity">
                            <div><span>{{ ucwords(setting('sebutan_desa')) }}</span><strong>{{ $hutaLocation['desa'] ?? '-' }}</strong></div>
                            <div><span>{{ ucwords(setting('sebutan_kecamatan')) }}</span><strong>{{ $hutaLocation['kecamatan'] ?? '-' }}</strong></div>
                            <div><span>{{ ucwords(setting('sebutan_kabupaten')) }}</span><strong>{{ $hutaLocation['kabupaten'] ?? '-' }}</strong></div>
                            <div><span>Provinsi</span><strong>{{ $hutaLocation['provinsi'] ?? '-' }}</strong></div>
                            <div><span>Kode Wilayah</span><strong>{{ $hutaCuacaData['adm4'] ?? '-' }}</strong></div>
                            <div><span>Zona Waktu</span><strong>{{ $hutaTimezoneLabel !== '' ? $hutaTimezoneLabel : str_replace('Asia/','',$hutaCuacaData['timezone'] ?? '-') }}</strong></div>
                        </div>
                        @if(!empty($hutaCuacaData['analysis_label']))
                            <div class="huta-environment-update"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 1-9.95 11H4.1A8 8 0 1 0 6 6.7V10H4V3h7v2H7.5A9.95 9.95 0 0 1 12 2Zm-1 5h2v5.4l3.2 1.85-1 1.73L11 13.55V7Z"/></svg><span>Prakiraan BMKG diperbarui: <strong>{{ $hutaCuacaData['analysis_label'] }}</strong></span></div>
                        @endif
                    </section>
                    <section class="huta-panel huta-air-panel">
                        <div class="huta-panel-heading"><div><h3>Kondisi Udara</h3><p>Informasi kualitas udara untuk wilayah desa.</p></div></div>
                        @if(!empty($hutaAir))
                            <div class="huta-air-status huta-air-{{ $hutaAirClass }}"><span>{{ $hutaAir['category'] ?? 'Belum tersedia' }}</span><strong>PM2.5 {{ $hutaAirPm25 }}</strong><p>{{ $hutaAir['advice'] ?? '' }}</p></div>
                            <div class="huta-air-scale" aria-label="Tingkat kualitas udara">
                                @foreach($hutaAirScale as $hutaAirScaleClass=>$hutaAirScaleLabel)
                                    <div class="huta-air-scale-item huta-air-scale-{{ $hutaAirScaleClass }}{{ $hutaAirClass===$hutaAirScaleClass ? ' is-active' : '' }}"><i aria-hidden="true"></i><span>{{ $hutaAirScaleLabel }}</span></div>
                                @endforeach
                            </div>
                            <div class="huta-air-grid"><div><span>AQI</span><strong>{{ $hutaAir['aqi'] ?? '-' }}</strong></div><div><span>PM10</span><strong>{{ $hutaAir['pm10'] ?? '-' }}</strong><small>µg/m³</small></div><div><span>Ozon (O3)</span><strong>{{ $hutaAir['ozone'] ?? '-' }}</strong><small>µg/m³</small></div><div><span>UV Index</span><strong>{{ $hutaAir['uv'] ?? '-' }}</strong></div><div><span>NO2</span><strong>{{ $hutaAir['no2'] ?? '-' }}</strong><small>µg/m³</small></div><div><span>SO2</span><strong>{{ $hutaAir['so2'] ?? '-' }}</strong><small>µg/m³</small></div></div>
                            <p class="huta-air-note">{{ ($hutaAir['pm25_source'] ?? '') === 'BMKG' ? 'PM2.5 mengikuti data BMKG terdekat; informasi lainnya menyesuaikan wilayah desa.' : 'Kualitas udara merupakan perkiraan wilayah dan dapat berbeda pada titik tertentu.' }}</p>
                        @else
                            <div class="huta-remote-status">Kondisi udara belum tersedia untuk wilayah ini.</div>
                        @endif
                    </section>
                </div>
                @foreach(($hutaCuacaData['forecast'] ?? []) as $hutaForecastDay)
                    <section class="huta-panel huta-weather-table-panel">
                        <div class="huta-panel-heading"><div><h3>{{ $hutaForecastDay['label'] ?? '-' }} · {{ $hutaForecastDay['date_label'] ?? '-' }}</h3><p>Prakiraan cuaca per tiga jam dari BMKG.</p></div></div>
                        <div class="huta-table-scroll">
                            <table class="huta-data-table huta-weather-detail-table">
                                <thead><tr><th>Waktu</th><th>Cuaca</th><th>Suhu</th><th>Kelembapan</th><th>Angin</th><th>Arah</th><th>Awan</th><th>Jarak Pandang</th></tr></thead>
                                <tbody>
                                    @foreach(($hutaForecastDay['rows'] ?? []) as $hutaForecastRow)
                                        <tr>
                                            <td data-label="Waktu"><strong>{{ $hutaForecastRow['time'] ?? '-' }}</strong></td>
                                            <td data-label="Cuaca"><span class="huta-weather-condition"><img src="{{ huta_weather_icon_url((array)$hutaForecastRow) }}" alt="" width="34" height="34" decoding="async"><span>{{ $hutaForecastRow['description'] ?? '-' }}</span></span></td>
                                            <td data-label="Suhu">{{ ($hutaForecastRow['temperature'] ?? null) !== null ? $hutaForecastRow['temperature'].'°C' : '-' }}</td>
                                            <td data-label="Kelembapan">{{ ($hutaForecastRow['humidity'] ?? null) !== null ? $hutaForecastRow['humidity'].'%' : '-' }}</td>
                                            <td data-label="Angin">{{ ($hutaForecastRow['wind_speed'] ?? null) !== null ? $hutaForecastRow['wind_speed'].' km/jam' : '-' }}</td>
                                            <td data-label="Arah">{{ $hutaForecastRow['wind_direction'] ?? '-' }}</td>
                                            <td data-label="Awan">{{ ($hutaForecastRow['cloud_cover'] ?? null) !== null ? $hutaForecastRow['cloud_cover'].'%' : '-' }}</td>
                                            <td data-label="Jarak Pandang">{{ $hutaForecastRow['visibility'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
                <div class="huta-source-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 17h2v-6h-2v6Zm0-8h2V7h-2v2Zm1-7a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/></svg><span>Sumber data: <strong>BMKG</strong> dan layanan kualitas udara.</span></div>
            @endif
        </div>
    </div>
</div>
@endif
@elseif($hutaArsipGempa)
@if(!$hutaGempaEnabled)
<section class="huta-cek-standalone" aria-label="Informasi Gempa"><div class="huta-cek-status" role="status"><strong>Halaman Gempa Tidak Ditampilkan</strong><span>Halaman Informasi Gempa Bumi saat ini disembunyikan oleh pengelola website. Widget Gempa di beranda tetap mengikuti pengaturan Widget OpenSID.</span><a href="{{ site_url() }}">Kembali ke Beranda</a></div></section>
@else
<div class="article-single huta-earthquake-page" data-page="huta-gempa" data-huta-earthquake-page="1" data-endpoint="{{ $hutaGempaBoot['endpoint'] ?? site_url() }}" data-token="{{ $hutaGempaBoot['token'] ?? '' }}" data-scope="{{ $hutaGempaBoot['scope'] ?? '' }}" data-village="{{ $hutaGempaBoot['village'] ?? trim(ucwords(setting('sebutan_desa')).' '.($desa['nama_desa'] ?? '')) }}" data-distance-label="{{ 'Jarak dari '.($hutaGempaBoot['village'] ?? trim(ucwords(setting('sebutan_desa')).' '.($desa['nama_desa'] ?? ''))) }}" data-fallback-image="{{ theme_asset('images/nodata.svg') }}">
    <div class="container-page mb-20">
        <div class="huta-page-card huta-earthquake-card">
            <div class="huta-page-title huta-earthquake-title">
                <div class="huta-title-icon huta-tone-blue" aria-hidden="true"><i class="fas fa-globe-americas"></i></div>
                <div class="huta-earthquake-title-copy"><h2>Informasi Gempa Bumi</h2><p>Data resmi BMKG, ringkasan kejadian terbaru, gempa dirasakan, dan gempa M 5,0+.</p></div>
                <div class="huta-page-actions huta-no-print">
                    <button class="huta-print-action" type="button" data-huta-print="gempa" aria-label="Cetak informasi gempa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v4H7V3Zm8 2H9v2h6V5Zm4 4a3 3 0 0 1 3 3v5h-4v4H6v-4H2v-5a3 3 0 0 1 3-3h14Zm-3 10v-5H8v5h8Zm3-4h1v-3a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3h2v-3h12v3h1Z"/></svg><span>Cetak</span></button>
                    @if($hutaPreviousUrl!=='')<a class="huta-page-back" href="{{ $hutaPreviousUrl }}" aria-label="Kembali ke halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.4 5.4 8.8 12l6.6 6.6-1.4 1.4L6 12l8-8 1.4 1.4Z"/></svg><span>Kembali</span></a>@endif
                </div>
            </div>
            <div class="huta-earthquake-loading" data-quake-loading role="status" aria-live="polite"><div class="huta-earthquake-skeleton"><i></i><i></i><i></i><i></i></div><strong>Memuat data gempa BMKG</strong><span>Mengambil pembaruan resmi BMKG tanpa menahan proses halaman utama.</span></div>
            <div class="huta-empty-state huta-earthquake-error" data-quake-error hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v6h-2V7Zm0 8h2v2h-2v-2Zm1-13a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/></svg><strong>Data gempa belum dapat dimuat</strong><span>Informasi dari BMKG sedang tidak tersedia. Silakan coba kembali beberapa saat lagi.</span></div>
            <div class="huta-earthquake-content" data-quake-content hidden>
                <section class="huta-earthquake-metrics" aria-label="Ringkasan gempa terbaru">
                    <article class="huta-earthquake-metric"><span class="huta-earthquake-metric-icon"><i class="fas fa-bolt" aria-hidden="true"></i></span><div><small>Magnitudo</small><strong data-q-mag>-</strong><em>Gempa terbaru BMKG</em></div></article>
                    <article class="huta-earthquake-metric"><span class="huta-earthquake-metric-icon"><i class="fas fa-arrows-alt-v" aria-hidden="true"></i></span><div><small>Kedalaman</small><strong data-q-depth>-</strong><em>Di bawah permukaan</em></div></article>
                    <article class="huta-earthquake-metric is-location"><span class="huta-earthquake-metric-icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span><div><small>Lokasi</small><strong data-q-region-short>-</strong><em><span data-q-date>-</span> · <span data-q-time>-</span></em></div></article>
                    <article class="huta-earthquake-metric"><span class="huta-earthquake-metric-icon"><i class="fas fa-wave-square" aria-hidden="true"></i></span><div><small>Dirasakan</small><strong data-q-felt-short>-</strong><em>Skala MMI bila tersedia</em></div></article>
                </section>
                <div class="huta-earthquake-topgrid">
                    <section class="huta-panel huta-earthquake-shakemap-panel">
                        <div class="huta-panel-heading"><div><h3>Peta Guncangan BMKG</h3><p>Shakemap resmi untuk kejadian gempa terbaru, bila tersedia.</p></div></div>
                        <a class="huta-earthquake-shakemap" data-q-shakemap-link data-fancybox="huta-gempa" href="" hidden><img data-q-shakemap src="{{ theme_asset('images/nodata.svg') }}" alt="Peta guncangan gempa BMKG" loading="lazy" decoding="async"></a>
                        <div class="huta-remote-status" data-q-shakemap-empty>Peta guncangan belum tersedia untuk kejadian ini.</div>
                    </section>
                    <section class="huta-panel huta-earthquake-detail-panel">
                        <div class="huta-panel-heading"><div><h3>Gempa Bumi Terbaru</h3><p>Parameter kejadian mengikuti data resmi BMKG.</p></div></div>
                        <div class="huta-earthquake-detail">
                            <div><span>Lokasi Episenter</span><strong data-q-region>-</strong></div>
                            <div><span>Koordinat</span><strong data-q-coordinates>-</strong></div>
                            <div class="huta-earthquake-detail-potential"><span class="huta-earthquake-detail-label"><i class="fas fa-water" aria-hidden="true"></i> Potensi</span><strong data-q-potential>-</strong></div>
                            <div><span>Wilayah Dirasakan</span><strong data-q-felt>-</strong></div>
                        </div>
                        <div class="huta-earthquake-local" data-q-local hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 2a5 5 0 0 1 5 5c0 2.9-3 7.5-5 10.1C10 16.5 7 11.9 7 9a5 5 0 0 1 5-5Z"/></svg><div><span>Perkiraan Jarak dari <strong data-q-village>-</strong></span><b data-q-distance>-</b><small data-q-distance-note>Jarak dihitung dari titik acuan desa yang tersedia. Bukan parameter dampak resmi BMKG.</small></div></div>
                    </section>
                </div>
                <section class="huta-panel huta-earthquake-history-panel">
                    <div class="huta-panel-heading huta-panel-heading-actions"><div><h3>Riwayat Gempa</h3><p>Daftar 15 kejadian terbaru yang dipublikasikan BMKG.</p></div><div class="huta-earthquake-tabs huta-no-print" role="tablist" aria-label="Jenis riwayat gempa"><button type="button" class="is-active" data-q-tab="felt" role="tab" aria-selected="true">Gempa Dirasakan</button><button type="button" data-q-tab="m5" role="tab" aria-selected="false">Gempa M 5,0+</button></div></div>
                    <div class="huta-earthquake-tab-panel is-active" data-q-panel="felt" role="tabpanel"><div class="huta-table-scroll"><table class="huta-data-table huta-earthquake-history-table"><thead><tr><th>Waktu</th><th>Magnitudo</th><th>Kedalaman</th><th>Lokasi</th><th>{{ 'Jarak dari '.($hutaGempaBoot['village'] ?? trim(ucwords(setting('sebutan_desa')).' '.($desa['nama_desa'] ?? ''))) }}</th><th>Dirasakan</th></tr></thead><tbody data-q-felt-rows></tbody></table></div><div class="huta-remote-status" data-q-felt-empty hidden>Belum ada daftar gempa dirasakan yang dapat ditampilkan.</div></div>
                    <div class="huta-earthquake-tab-panel" data-q-panel="m5" role="tabpanel" hidden><div class="huta-table-scroll"><table class="huta-data-table huta-earthquake-history-table"><thead><tr><th>Waktu</th><th>Magnitudo</th><th>Kedalaman</th><th>Lokasi</th><th>{{ 'Jarak dari '.($hutaGempaBoot['village'] ?? trim(ucwords(setting('sebutan_desa')).' '.($desa['nama_desa'] ?? ''))) }}</th><th>Potensi</th></tr></thead><tbody data-q-m5-rows></tbody></table></div><div class="huta-remote-status" data-q-m5-empty hidden>Belum ada daftar gempa M 5,0+ yang dapat ditampilkan.</div></div>
                </section>
                <div class="huta-earthquake-info-grid">
                    <section class="huta-panel huta-earthquake-intensity-panel"><div class="huta-panel-heading"><div><h3>Memahami Intensitas Gempa</h3><p>Ringkasan SIG-BMKG untuk membantu membaca tingkat dampak secara umum.</p></div></div><div class="huta-sig-grid"><div><b>I</b><strong>Tidak Dirasakan</strong><span>MMI I–II</span></div><div><b>II</b><strong>Dirasakan</strong><span>MMI III–V</span></div><div><b>III</b><strong>Kerusakan Ringan</strong><span>MMI VI</span></div><div><b>IV</b><strong>Kerusakan Sedang</strong><span>MMI VII–VIII</span></div><div><b>V</b><strong>Kerusakan Berat</strong><span>MMI IX–XII</span></div></div></section>
                    <section class="huta-panel huta-earthquake-safety-panel"><div class="huta-panel-heading"><div><h3>Saat Terjadi Gempa</h3><p>Utamakan keselamatan dan ikuti informasi resmi.</p></div></div><ul><li>Lindungi kepala dan tubuh dari benda yang dapat jatuh.</li><li>Jauhi kaca, lemari, dan benda berat yang berpotensi roboh.</li><li>Setelah guncangan berhenti, keluar dengan tertib bila kondisi memungkinkan.</li><li>Ikuti arahan BMKG dan pemerintah setempat, terutama terkait potensi tsunami.</li></ul></section>
                </div>
                <div class="huta-source-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 17h2v-6h-2v6Zm0-8h2V7h-2v2Zm1-7a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/></svg><span>Sumber data: <strong>BMKG</strong>. Data diperbarui setiap ada peristiwa gempa. <span data-q-cache-note></span></span></div>
            </div>
        </div>
    </div>
</div>
@endif
@elseif($hutaArsipShalat)
<div class="article-single huta-shalat-page" data-page="huta-shalat">
<div class="container-page mb-20">
        <div class="huta-page-card huta-shalat-detail-card">
            <div class="huta-page-title huta-shalat-detail-title">
                <div class="huta-title-icon huta-title-icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12.4 2a8.9 8.9 0 0 0 7.5 13.7A9 9 0 1 1 12.4 2ZM9.2 4.8A7 7 0 1 0 17.7 17 10.9 10.9 0 0 1 9.2 4.8ZM12 9l.8 1.7 1.9.3-1.4 1.3.3 1.9-1.6-.9-1.7.9.4-1.9-1.4-1.3 1.9-.3L12 9Z"/></svg></div>
                <div class="huta-shalat-detail-title-copy"><h2>Jadwal Shalat</h2><p>Jadwal hari ini dan tabel bulanan untuk wilayah {{ $hutaShalatData['location'] ?? 'desa' }}.</p></div>
                <div class="huta-page-actions huta-no-print">@if($hutaPreviousUrl!=='')<a class="huta-page-back" href="{{ $hutaPreviousUrl }}" aria-label="Kembali ke halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.4 5.4 8.8 12l6.6 6.6-1.4 1.4L6 12l8-8 1.4 1.4Z"/></svg><span>Kembali</span></a>@endif</div>
            </div>
            @php extract(huta_view_shalat_page_context((array)$hutaShalatData), EXTR_SKIP); @endphp
            @if(!empty($hutaToday))
                <section class="huta-shalat-today">
                    <div class="huta-shalat-today-head"><div><small>Jadwal Hari Ini</small><h3>{{ $hutaToday['date_label'] ?? '-' }}</h3><p>{{ $hutaToday['hijri'] ?? '-' }} · {{ $hutaToday['location'] ?? ($hutaShalatData['location'] ?? '-') }}</p></div><span class="huta-shalat-today-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 2h2v2h8V2h2v2h1a3 3 0 0 1 3 3v13H2V7a3 3 0 0 1 3-3h1V2Zm13 8H5v8h14v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></div>
                    <div class="huta-shalat-today-grid">
                        @foreach($hutaPrayerMeta as $hutaPrayerKey=>$hutaPrayerInfo)
                            <div class="huta-prayer-tile"><span class="huta-prayer-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $hutaPrayerInfo['icon'] }}"/></svg></span><span class="huta-prayer-label">{{ $hutaPrayerInfo['label'] }}</span><strong>{{ $hutaTodayTimes[$hutaPrayerKey] ?? '--:--' }}</strong></div>
                        @endforeach
                    </div>
                </section>
            @endif
            <section class="huta-panel huta-shalat-month-panel">
                <div class="huta-panel-heading huta-shalat-month-heading"><div><h3>Jadwal Bulanan</h3><p>{{ $hutaShalatData['location'] ?? '-' }}{{ $hutaShalatTimezoneLabel!=='' ? ' · '.$hutaShalatTimezoneLabel : '' }}</p></div>@if(($hutaRequestedShalatCalendar==='hijri'&&!empty($hutaShalatData['hijri_rows']))||($hutaRequestedShalatCalendar!=='hijri'&&!empty($hutaShalatData['rows'])))<button class="huta-print-action huta-no-print" type="button" data-huta-print="shalat" aria-label="Cetak jadwal bulan yang dipilih"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v4H7V3Zm8 2H9v2h6V5Zm4 4a3 3 0 0 1 3 3v5h-4v4H6v-4H2v-5a3 3 0 0 1 3-3h14Zm-3 10v-5H8v5h8Zm3-4h1v-3a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3h2v-3h12v3h1Z"/></svg><span>Cetak Jadwal</span></button>@endif</div>
                @if(!empty($hutaShalatData['rows'])||!empty($hutaShalatData['hijri_rows']))
                    <div class="huta-calendar-switch">
                        <nav class="huta-calendar-tabs huta-no-print" aria-label="Kalender jadwal shalat">
                            <a class="{{ $hutaRequestedShalatCalendar==='hijri' ? '' : 'is-active' }}" href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="masehi" data-huta-shalat-month="{{ $hutaShalatData['month'] ?? '' }}" {{ $hutaRequestedShalatCalendar==='hijri' ? '' : 'aria-current=page' }}>Masehi</a>
                            <a class="{{ $hutaRequestedShalatCalendar==='hijri' ? 'is-active' : '' }}" href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="hijri" data-huta-shalat-hmonth="{{ $hutaShalatData['hijri_month'] ?? '' }}" {{ $hutaRequestedShalatCalendar==='hijri' ? 'aria-current=page' : '' }}>Hijriah</a>
                        </nav>
                        <div class="huta-calendar-panels">
                            @if($hutaRequestedShalatCalendar==='hijri')
                                <div class="huta-calendar-panel huta-calendar-hijri is-active">
                                    <div class="huta-calendar-panel-head">
                                        <div><small>Kalender Hijriah</small><h4>{{ $hutaShalatData['hijri_month_label'] ?? '-' }}</h4></div>
                                        <div class="huta-shalat-month-nav huta-no-print">
                                            @if(!empty($hutaShalatData['hijri_previous']))
                                                <a href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="hijri" data-huta-shalat-hmonth="{{ $hutaShalatData['hijri_previous'] }}" aria-label="Bulan Hijriah sebelumnya"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6-1.4-1.4L6.2 12l7.4 7.4L15 18Z"/></svg><span>Sebelumnya</span></a>
                                            @endif
                                            @if(!empty($hutaShalatData['hijri_next']))
                                                <a href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="hijri" data-huta-shalat-hmonth="{{ $hutaShalatData['hijri_next'] }}" aria-label="Bulan Hijriah berikutnya"><span>Berikutnya</span><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6 1.4-1.4 7.4 7.4-7.4 7.4L9 18Z"/></svg></a>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!empty($hutaShalatData['hijri_rows']))
                                        <div class="huta-print-only huta-shalat-print-title"><h1>Jadwal Shalat {{ $hutaShalatData['hijri_month_label'] ?? '' }}</h1><p>{{ $hutaShalatData['location'] ?? '-' }}{{ $hutaShalatTimezoneLabel!=='' ? ' · '.$hutaShalatTimezoneLabel : '' }}</p></div>
                                        <div class="huta-table-scroll">
                                            <table class="huta-data-table huta-shalat-month-table">
                                                <thead><tr><th>No.</th><th>Tanggal Hijriah</th><th>Imsak</th><th>Subuh</th><th>Terbit</th><th>Dhuha</th><th>Dzuhur</th><th>Ashar</th><th>Maghrib</th><th>Isya</th></tr></thead>
                                                <tbody>
                                                    @foreach($hutaShalatData['hijri_rows'] as $hutaShalatIndex=>$hutaShalatRow)
                                                        <tr class="{{ !empty($hutaShalatRow['today']) ? 'is-today' : '' }}">
                                                            <td>{{ $hutaShalatIndex+1 }}</td>
                                                            <td class="huta-shalat-date"><strong>{{ $hutaShalatRow['hijri'] ?? '-' }}</strong><small>{{ $hutaShalatRow['masehi'] ?? '-' }}</small></td>
                                                            @foreach(['imsak','subuh','terbit','dhuha','dzuhur','ashar','maghrib','isya'] as $hutaPrayerKey)
                                                                <td>{{ $hutaShalatRow['jadwal'][$hutaPrayerKey] ?? '--:--' }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="huta-hijri-note">Tanggal Hijriah merupakan konversi informatif dan dapat berbeda dari penetapan resmi pemerintah.</p>
                                    @else
                                        <div class="huta-empty-state huta-empty-compact"><strong>Jadwal Hijriah belum tersedia</strong><span>Silakan pilih bulan lain atau coba kembali beberapa saat lagi.</span></div>
                                    @endif
                                </div>
                            @else
                                <div class="huta-calendar-panel huta-calendar-masehi is-active">
                                    <div class="huta-calendar-panel-head">
                                        <div><small>Kalender Masehi</small><h4>{{ $hutaShalatData['month_label'] ?? '-' }}</h4></div>
                                        <div class="huta-shalat-month-nav huta-no-print">
                                            @if(!empty($hutaShalatData['previous']))
                                                <a href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="masehi" data-huta-shalat-month="{{ $hutaShalatData['previous'] }}" aria-label="Bulan Masehi sebelumnya"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6-1.4-1.4L6.2 12l7.4 7.4L15 18Z"/></svg><span>Sebelumnya</span></a>
                                            @endif
                                            @if(!empty($hutaShalatData['next']))
                                                <a href="{{ $hutaArsipBase }}?req=huta-shalat" data-huta-shalat-cal="masehi" data-huta-shalat-month="{{ $hutaShalatData['next'] }}" aria-label="Bulan Masehi berikutnya"><span>Berikutnya</span><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6 1.4-1.4 7.4 7.4-7.4 7.4L9 18Z"/></svg></a>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!empty($hutaShalatData['rows']))
                                        <div class="huta-print-only huta-shalat-print-title"><h1>Jadwal Shalat {{ $hutaShalatData['month_label'] ?? '' }}</h1><p>{{ $hutaShalatData['location'] ?? '-' }}{{ $hutaShalatTimezoneLabel!=='' ? ' · '.$hutaShalatTimezoneLabel : '' }}</p></div>
                                        <div class="huta-table-scroll">
                                            <table class="huta-data-table huta-shalat-month-table">
                                                <thead><tr><th>No.</th><th>Tanggal Masehi</th><th>Imsak</th><th>Subuh</th><th>Terbit</th><th>Dhuha</th><th>Dzuhur</th><th>Ashar</th><th>Maghrib</th><th>Isya</th></tr></thead>
                                                <tbody>
                                                    @foreach($hutaShalatData['rows'] as $hutaShalatIndex=>$hutaShalatRow)
                                                        <tr class="{{ !empty($hutaShalatRow['today']) ? 'is-today' : '' }}">
                                                            <td>{{ $hutaShalatIndex+1 }}</td>
                                                            <td class="huta-shalat-date"><strong>{{ $hutaShalatRow['masehi'] ?? '-' }}</strong><small>{{ $hutaShalatRow['hijri'] ?? '-' }}{{ !empty($hutaShalatRow['today']) ? ' · Hari ini' : '' }}</small></td>
                                                            @foreach(['imsak','subuh','terbit','dhuha','dzuhur','ashar','maghrib','isya'] as $hutaPrayerKey)
                                                                <td>{{ $hutaShalatRow['jadwal'][$hutaPrayerKey] ?? '--:--' }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="huta-empty-state huta-empty-compact"><strong>Jadwal Masehi belum tersedia</strong><span>Silakan pilih bulan lain atau coba kembali beberapa saat lagi.</span></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="huta-empty-state huta-empty-compact"><strong>Jadwal bulanan belum tersedia</strong><span>{{ $hutaShalatData['message'] ?? 'Silakan coba kembali beberapa saat lagi.' }}</span></div>
                @endif
            </section>
            <div class="huta-source-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 17h2v-6h-2v6Zm0-8h2V7h-2v2Zm1-7a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/></svg><span>Sumber jadwal: <strong>Kementerian Agama RI</strong>.</span></div>
        </div>
    </div>
</div>
@else
@includeIf('theme::partials.home.addons.artikel')
<div class="arsipstyle">
	<div class="relative-row">
		<div class="container-page mb-20">
			<div class="box-page border-grey-soft bg-white">
				<div class="relative">
					<div class="headingpage border-grey-soft flexleft">
						<div class="headingpage-image border-grey-soft flexcenter">
							<img src="{{ theme_asset('images/icon/arsip.svg') }}" alt="" />
						</div>
						<h2>Arsip Artikel</h2>
					</div>
					<div class="big-screen">
						<div class="righthead flexright">
							@include('theme::partials.artikel.button')
						</div>
					</div>
				</div>
				<div class="box-page-padding">
					<div class="article-inner">
						<div class="flexrow dnv-is-55a41d15a2" id="arsip-list">@for($i=0;$i<8;$i++)<div class="flex-column4 bg-grey-medium huta-arsip-skeleton-card" aria-hidden="true"><i></i><b></b><span></span><span></span><em></em></div>@endfor</div>
						@include('theme::commons.pagination')
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endif
@endsection
@if(!$hutaArsipPenduduk&&!$hutaArsipIndeks&&!$hutaArsipCuaca&&!$hutaArsipGempa&&!$hutaArsipShalat)
@push('script')
<script>
	document.addEventListener("DOMContentLoaded", function(event) {
	var parent = ``;
	let pageSize = 8;
	var routeArsip = `{{ ci_route('internal_api.arsip') }}?page[size]=${pageSize}`;
	let status = '';
	const loadArsip = function(pageNumber, keepScroll) {
		$.ajax({
			url: routeArsip + `&sort=-tgl_upload&page[number]=${pageNumber}`,
			type: "GET",
			beforeSend: function() {
				const arsipList = document.getElementById('arsip-list');
				if(arsipList)arsipList.innerHTML=new Array(9).join('<div class="flex-column4 bg-grey-medium huta-arsip-skeleton-card" aria-hidden="true"><i></i><b></b><span></span><span></span><em></em></div>');
			},
			dataType: 'json',
			data: {
			},
			success: function(data) {
				displayArsip(data);
				initPagination(data);
				try { if (window.lazyload) window.lazyload.run(); } catch(e) {}
                try { window.hutaForceImages && window.hutaForceImages(document.getElementById('arsip-list')); } catch(e) {}
                if (typeof keepScroll === 'number') requestAnimationFrame(function(){requestAnimationFrame(function(){window.scrollTo(0,keepScroll);});});
			}
		});
	}
	const displayArsip = function(dataArsip) {
		const arsipList = document.getElementById('arsip-list');
		arsipList.innerHTML = '';
		if (!dataArsip.data.length) {
			var imgNotFound = '{{ theme_asset('images/nodata.svg') }}';
			arsipList.innerHTML = `<div class="no-found">
					<div class="margin-min10 flexcenter">
						<div class="no-found-title">
							<div class="pd-lr-10">
								<h2>ARTIKEL BELUM TERSEDIA</h2>
								<h3>Maaf, Artikel belum tersedia atau sedang dalam perbaikan.</h3>
							</div>
						</div>
						<div class="no-found-image">
							<div class="pd-lr-10">
								<img src="${imgNotFound}" alt=""/>
							</div>
						</div>
					</div>
				</div>`
			return
		}
		const pathGambar = `{{ LOKASI_FOTO_ARTIKEL }}` + 'sedang_';
		const latarWebsite = `{{ $latar_website ? $latar_website : base_url('assets/front/css/images/latar_website.jpg'); }}`;
        const fullscreenImg = `{{ theme_asset('images/icon/fullscreen.svg') }}`;
		dataArsip.data.forEach(item => {
			const card = document.createElement('div');
			card.className = 'flex-column4 bg-grey-medium';
			const gambarDiv = item.attributes.gambar ? `<div class="latest-image">
						<div class="carouselcustom js-flickity"
							data-flickity='{ "autoPlay": false, "groupCells": true, "groupCells": 1, "wrapAround": true, "cellAlign": "left" }'>
							<div class="carouselcustom-item">
								<div class="image-article dnv-is-8852e69763">
									<img src="/${pathGambar}${item.attributes.gambar}" alt="" loading="lazy">
									<a href="/${pathGambar}${item.attributes.gambar}" data-fancybox="images"
										data-caption="${item.attributes.judul}">
										<div class="image-zoom"><img src="${fullscreenImg}"
												alt="" /></div>
									</a>
								</div>
							</div>
						</div>
					</div>` : `<div class="latest-image">
						<div class="image-article dnv-is-8852e69763">
							<img src="{{ gambar_desa($desa['kantor_desa'], TRUE) }}" alt="" loading="lazy"/>
							<div class="logo-no-image"><img src="{{ gambar_desa($desa['logo']) }}" alt="" /></div>
						</div>
					</div>`;
			card.innerHTML = window.hutaSanitizeHTML(`
				<div class="latest-article relative-hid">
					${gambarDiv}
					<div class="latest-date">${item.attributes.tgl_upload_local} <i class="fa fa-heart"></i>
							${item.attributes.hit}</div>
					<a title="Selengkapnya" href="${item.attributes.url_slug}">
						<h2>${item.attributes.judul}</h2>
					</a>
				</div>
				<div class="latest-box-bottom bg-white border-grey-soft flexcenter dnv-is-152cc2b621">
					<div class="flexcenter">
						<div class="latest-info flexcenter dnv-is-2eda1daa91">
							<a title="Selengkapnya" href="${item.attributes.url_slug}">
								<div class="article-link flexleft">
									<svg viewBox="0 0 128 128">
										<polygon points="37,9 119,9 119,119 37,119 37,127 127,127 127,1 37,1  " />
										<rect height="8" width="8" x="1" y="60" />
										<polygon
											points="46.2,91.2 51.8,96.8 84.7,64 51.8,31.2 46.2,36.8 69.3,60 19,60 19,68 69.3,68  " />
									</svg>
									Selengkapnya
								</div>
							</a>
						</div>
					</div>
				</div>
		`);
			card.onclick = function() {}
			arsipList.appendChild(card);
		});
	}
	$('.pagination').on('click', '.btn-page', function(event) {
		event.preventDefault();
		event.stopPropagation();
		var page = $(this).data('page');
		var keepScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
		loadArsip(page, keepScroll);
	});
	loadArsip(1);
});
</script>
@endpush
@endif
