<?php defined('BASEPATH')||exit('No direct script access allowed');?>@includeIf('theme::commons.huta-loader')
@php
    $hutaVideoContext = function_exists('huta_view_home_video_context') ? huta_view_home_video_context($vid ?? null) : ['enabled'=>false,'count'=>0,'videos'=>[],'carousel'=>false];
    $hutaVideos = is_array($hutaVideoContext['videos'] ?? null) ? $hutaVideoContext['videos'] : [];
@endphp
@if(!empty($hutaVideos))
<div class="module-gallery">
    <div class="relative-row ptb-10">
        <div class="container-page">
            <div class="fotohome border-grey-soft bg-white-transparent {{ count($hutaVideos) === 1 ? 'huta-video-home-single' : 'huta-video-home-multi' }}">
                <div class="margin-min5">
                    <div class="fotohome-left">
                        <div class="pd-lr-5">
                            <div class="fotohome-label" style="background-image:url({{ gambar_desa($desa['kantor_desa'], true) }});">
                                <div class="fotohome-label2"><img class="yall_lazy" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" data-src="{{ gambar_desa($desa['kantor_desa'], true) }}" alt=""></div>
                                <div class="bottom-gradient"></div><img src="{{ theme_asset('images/latar.png') }}" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="fotohome-right"><div class="pd-lr-5"><div class="margin-min5">
                        <div class="carouselcustom huta-video-track {{ count($hutaVideos) > 1 ? 'js-flickity' : 'huta-video-single-track' }}" @if(count($hutaVideos) > 1) data-flickity='{ "autoPlay": false, "wrapAround": true, "cellAlign": "left" }' @endif>
                            @foreach($hutaVideos as $video)
                            <div class="carouselcustom-item huta-video-item"><div class="mlr-5"><div class="image-fotohome">
                                <iframe src="{{ $video['embed_url'] }}" width="100%" height="100%" frameborder="0" loading="lazy" title="{{ $video['title'] }}" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                <div class="huta-media-fallback"><a href="{{ $video['fallback_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Tonton video di YouTube">Tonton di YouTube</a></div>
                            </div></div></div>
                            @endforeach
                        </div>
                    </div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
