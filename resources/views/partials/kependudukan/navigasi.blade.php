<?php defined('BASEPATH')||exit('No direct script access allowed');?>@php
$hutaDaftarStatistik = function_exists('daftar_statistik') ? (array) daftar_statistik() : [];
$hutaUseNativeStatistics = function_exists('huta_feature_enabled') && huta_feature_enabled('stat_navigation_native');
$hutaStatNavigation = $hutaUseNativeStatistics && function_exists('huta_prepare_stat_navigation_adaptive')
    ? huta_prepare_stat_navigation_adaptive($hutaDaftarStatistik, (string) ($slug_aktif ?? ''), $statistik_aktif ?? null)
    : (function_exists('huta_prepare_stat_navigation')
        ? huta_prepare_stat_navigation($hutaDaftarStatistik, (string) ($slug_aktif ?? ''))
        : []);
if (function_exists('huta_extend_stat_navigation_dtsen')) {
    $hutaStatNavigation = huta_extend_stat_navigation_dtsen(
        (array) $hutaStatNavigation,
        $hutaDaftarStatistik,
        (string) ($slug_aktif ?? request()->segment(2) ?? ''),
        $statistik_aktif ?? null
    );
}
$hutaStatIconMap=[
    'statistikPenduduk'=>'users',
    'statistikKeluarga'=>'id-card',
    'statistikBantuan'=>'hand-heart',
    'statistikLainnya'=>'layers',
    'dtsenDesil'=>'activity',
];
@endphp
<nav class="huta-stat-nav" aria-label="Navigasi statistik kependudukan">
    @foreach ($hutaStatNavigation as $group)
        <section class="huta-stat-nav-card {{ $group['is_active'] ? 'active' : '' }}" data-stat-nav-card>
            <button
                type="button"
                class="huta-stat-nav-head"
                data-stat-nav-toggle
                aria-expanded="{{ $group['is_active'] ? 'true' : 'false' }}"
            >
                <span class="huta-stat-nav-icon huta-tone-{{ $group['tone'] }}" aria-hidden="true">
                    {!! function_exists('huta_icon_svg') ? huta_icon_svg($hutaStatIconMap[$group['target']] ?? 'layers','currentColor') : '' !!}
                </span>
                <span class="huta-stat-nav-text">
                    <strong>{{ $group['label'] }}</strong>
                    <small>{{ $group['desc'] }}</small>
                </span>
                <span class="huta-stat-nav-count">{{ $group['count'] }}</span>
                <i class="fa fa-angle-{{ $group['is_active'] ? 'up' : 'down' }} huta-stat-nav-arrow"></i>
            </button>
            <div class="huta-stat-nav-body" style="display:{{ $group['is_active'] ? 'block' : 'none' }};">
                @foreach ($group['items'] as $submenu)
                    <a
                        href="{{ site_url($submenu['url']) }}"
                        class="huta-stat-nav-link {{ !empty($submenu['_huta_is_active']) ? 'active' : '' }}"
                    >
                        <span></span>
                        <b>{{ $submenu['label'] }}</b>
                        <i class="fa fa-angle-right"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</nav>
