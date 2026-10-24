<?php defined('BASEPATH')||exit('No direct script access allowed');?>@php
$start_date_raw = huta_plain_text(theme_config('event_mulai', ''), 10);
$end_date_raw = huta_plain_text(theme_config('event_akhir', ''), 10);
$start_date_obj = preg_match('/^\d{2}-\d{2}-\d{4}$/', $start_date_raw) ? DateTime::createFromFormat('!d-m-Y', $start_date_raw) : false;
$end_date_obj = preg_match('/^\d{2}-\d{2}-\d{4}$/', $end_date_raw) ? DateTime::createFromFormat('!d-m-Y', $end_date_raw) : false;
if ($start_date_obj && $start_date_obj->format('d-m-Y') !== $start_date_raw) $start_date_obj = false;
if ($end_date_obj && $end_date_obj->format('d-m-Y') !== $end_date_raw) $end_date_obj = false;
if ($end_date_obj) $end_date_obj->setTime(23, 59, 59);
$current_date = new DateTime();
$hutaEventTopTitle = huta_plain_text(theme_config('event_toptitle', ''), 100);
$hutaEventTitleArab = huta_plain_text(theme_config('event_titlearab', ''), 160);
$hutaEventSubtitle = huta_plain_text(theme_config('event_subtitle', ''), 160);
$hutaEventDeskTitle = huta_plain_text(theme_config('event_desktitle', ''), 240);
@endphp
@if (theme_config('defaultevent') == 'true' && $start_date_obj && $end_date_obj)
    @if ($current_date >= $start_date_obj && $current_date <= $end_date_obj)
        <div class="event-body bg-grey-medium">
            <div class="event-container bg-gradient-hor">
                <div class="event-container-bg bg-pattern"></div>
                <div class="container-page">
                    <div class="event-inner flexcenter">
                        @if (theme_config('posisi_gambar_event') == '0')
                                @foreach ($widgetAktif as $w)
                                    @if (strcasecmp(strip_tags($w['isi']), 'gempa') === 0 && !empty($w['foto']))
                                        <div class="event-image-left">
                                            <img src="{{ base_url(LOKASI_GAMBAR_WIDGET . implode('/', array_map('rawurlencode', explode('/', ltrim(str_replace('\\', '/', (string) $w['foto']), '/'))))) }}" alt="" class="dnv-is-d35b2f4392" decoding="async" fetchpriority="high">
                                        </div>
                                    @endif
                                @endforeach
                        @endif
                        <div class="event-title">
                            @if ($hutaEventTopTitle !== '')
                                <h2 class="dnv-is-f88f9ef834">
                                    {{ $hutaEventTopTitle }}
                                </h2>
                            @endif
                            @if ($hutaEventTitleArab !== '')
                                <div class="event-title-arab color-light">
                                    {{ $hutaEventTitleArab }}
                                </div>
                            @endif
                            @if (!empty($eventdetail['titlestandar']))
                                <h1>{{ $eventdetail['titlestandar'] }}</h1>
                            @endif
                            @if ($hutaEventSubtitle !== '')
                                <h2>{{ $hutaEventSubtitle }}</h2>
                            @endif
                            @if ($hutaEventDeskTitle !== '')
                                <p>{{ $hutaEventDeskTitle }}</p>
                            @endif
                        </div>
                        @if (theme_config('posisi_gambar_event') == '1')
                            @foreach ($widgetAktif as $w)
                                @if (strcasecmp(strip_tags($w['isi']), 'gempa') === 0 && !empty($w['foto']))
                                    <div class="event-image-right">
                                        <img src="{{ base_url(LOKASI_GAMBAR_WIDGET . implode('/', array_map('rawurlencode', explode('/', ltrim(str_replace('\\', '/', (string) $w['foto']), '/'))))) }}" alt="" class="dnv-is-d35b2f4392" decoding="async" fetchpriority="high">
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
