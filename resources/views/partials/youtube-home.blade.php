@php
    $yt = theme_config('link_youtube_video', '');
    $urls = array_filter(array_map('trim', explode(',', $yt)));
@endphp

@if (!empty($urls))
<section class="hv-section hv-youtube-section" aria-label="Video YouTube">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Video Desa</h2>
        </div>
        <div class="row g-3">
            @foreach ($urls as $i => $url)
                @php
                    $embed = '';
                    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([A-Za-z0-9_-]{11})~', $url, $m)) {
                        $embed = 'https://www.youtube.com/embed/' . $m[1];
                    } elseif (preg_match('~youtube\.com/shorts/([A-Za-z0-9_-]{11})~', $url, $m)) {
                        $embed = 'https://www.youtube.com/embed/' . $m[1];
                    }
                @endphp
                @if ($embed)
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="hv-youtube-card">
                            <div class="hv-youtube-embed">
                                <iframe src="{{ $embed }}" title="Video {{ $i + 1 }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif