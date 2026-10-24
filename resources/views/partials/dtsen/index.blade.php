<?php defined('BASEPATH')||exit('No direct script access allowed');?>@includeIf('theme::commons.huta-loader')
@extends('theme::layouts.full-content')
@php
$hutaDtsenContext = function_exists('huta_dtsen_statistics_page_context')
    ? huta_dtsen_statistics_page_context()
    : ['available' => false, 'route' => '', 'title' => 'Statistik Desil DTSEN', 'description' => 'Distribusi tingkat kesejahteraan keluarga DTSEN.'];
@endphp
@include('theme::commons.asset_highcharts')
@section('content')
<div class="article-single huta-stat-dashboard huta-dtsen-stat-page"
    data-route="{{ $hutaDtsenContext['route'] ?? '' }}"
    data-available="{{ !empty($hutaDtsenContext['available']) ? 1 : 0 }}">
    <div class="statistikstyle">
        <div class="container-page mb-20 huta-stat-page-container">
            <div class="headingpage border-grey-soft bg-gradient-hor flexleft huta-stat-page-heading">
                <div class="headingpage-image border-grey-soft flexcenter"><span class="huta-heading-context-icon" aria-hidden="true">{!! function_exists('huta_icon_svg') ? huta_icon_svg('layers','currentColor') : '' !!}</span></div>
                <div class="articlehome-head flexleft"><h1>DATA DTSEN</h1></div>
            </div>
            <div class="huta-stat-shell">
                <aside class="huta-stat-sidebar">
                    <div class="huta-stat-sidebar-inner">
                        @include('theme::partials.kependudukan.navigasi')
                    </div>
                </aside>
                <main class="huta-stat-main">
                    <section class="huta-panel huta-dtsen-intro">
                        <header class="huta-panel-head">
                            <div class="huta-panel-title">
                                <span class="huta-title-icon" aria-hidden="true">{!! function_exists('huta_icon_svg') ? huta_icon_svg('layers','currentColor') : '' !!}</span>
                                <div>
                                    <h1>{{ $hutaDtsenContext['title'] ?? 'Statistik Desil DTSEN' }}</h1>
                                    <p>{{ $hutaDtsenContext['description'] ?? '' }}</p>
                                </div>
                            </div>
                        </header>
                    </section>

                    @if(empty($hutaDtsenContext['available']))
                        <section class="huta-panel">
                            <div class="huta-panel-body">
                                <div class="huta-state-muted" role="status">
                                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                                    Statistik Desil DTSEN belum tersedia pada versi OpenSID ini.
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="huta-summary-grid" aria-label="Ringkasan DTSEN">
                            <article class="huta-summary-card huta-tone-blue">
                                <span class="huta-summary-icon"><i class="fa fa-users"></i></span>
                                <div><small>Total Keluarga</small><strong data-dtsen-summary="keluarga">0</strong><em>keluarga</em></div>
                            </article>
                            <article class="huta-summary-card huta-tone-green">
                                <span class="huta-summary-icon"><i class="fa fa-check-square-o"></i></span>
                                <div><small>Terdaftar DTSEN</small><strong data-dtsen-summary="terdaftar">0</strong><em>keluarga</em></div>
                            </article>
                            <article class="huta-summary-card huta-tone-orange">
                                <span class="huta-summary-icon"><i class="fa fa-list-alt"></i></span>
                                <div><small>Belum Terdaftar</small><strong data-dtsen-summary="belum">0</strong><em>keluarga</em></div>
                            </article>
                        </section>

                        <div class="huta-dtsen-status huta-inline-loading" data-dtsen-status="loading" role="status" aria-live="polite">
                            @include('theme::commons.loading')
                            <span>Memuat statistik DTSEN...</span>
                        </div>
                        <div class="huta-dtsen-status huta-state-muted" data-dtsen-status="empty" role="status" style="display:none;">
                            Data statistik desil DTSEN belum tersedia untuk saat ini.
                        </div>
                        <div class="huta-dtsen-status huta-state-error" data-dtsen-status="error" role="alert" style="display:none;">
                            Statistik DTSEN belum dapat dimuat. Silakan muat ulang halaman atau coba kembali beberapa saat lagi.
                        </div>

                        <div class="huta-dtsen-chart-grid" data-dtsen-content style="display:none;">
                            <section class="huta-panel huta-chart-panel" data-huta-panel="dtsen-kemensos">
                                <header class="huta-panel-head">
                                    <div class="huta-panel-title">
                                        <span class="huta-title-icon" aria-hidden="true"><i class="fa fa-bar-chart"></i></span>
                                        <div><h2>Desil Kemensos (SIKNg)</h2><p>Distribusi desil berdasarkan data Kemensos yang tersedia di OpenSID.</p></div>
                                    </div>
                                    <div class="huta-panel-tools">
                                        <label><span>Grafik</span><div class="huta-segmented" role="group" aria-label="Jenis grafik desil Kemensos">
                                            <button type="button" class="active" data-dtsen-chart-target="kemensos" data-dtsen-chart-type="column">Batang</button>
                                            <button type="button" data-dtsen-chart-target="kemensos" data-dtsen-chart-type="pie">Pie</button>
                                        </div></label>
                                        <button type="button" class="huta-icon-btn" data-panel-fullscreen="dtsen-kemensos" title="Fullscreen"><i class="fa fa-expand"></i></button>
                                    </div>
                                </header>
                                <div class="huta-panel-body"><div id="dtsenChartKemensos" class="huta-highchart"></div></div>
                            </section>

                            <section class="huta-panel huta-chart-panel" data-huta-panel="dtsen-analisis">
                                <header class="huta-panel-head">
                                    <div class="huta-panel-title">
                                        <span class="huta-title-icon" aria-hidden="true"><i class="fa fa-line-chart"></i></span>
                                        <div><h2>Desil Analisis Desa</h2><p>Distribusi desil hasil analisis atau pendataan DTSEN desa.</p></div>
                                    </div>
                                    <div class="huta-panel-tools">
                                        <label><span>Grafik</span><div class="huta-segmented" role="group" aria-label="Jenis grafik desil analisis desa">
                                            <button type="button" class="active" data-dtsen-chart-target="analisis" data-dtsen-chart-type="column">Batang</button>
                                            <button type="button" data-dtsen-chart-target="analisis" data-dtsen-chart-type="pie">Pie</button>
                                        </div></label>
                                        <button type="button" class="huta-icon-btn" data-panel-fullscreen="dtsen-analisis" title="Fullscreen"><i class="fa fa-expand"></i></button>
                                    </div>
                                </header>
                                <div class="huta-panel-body"><div id="dtsenChartAnalisis" class="huta-highchart"></div></div>
                            </section>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</div>
@endsection
