@extends('theme::layouts.full-content')

@section('content')
    <nav role="navigation" aria-label="navigation" class="breadcrumb">
        <ol>
            <li><a href="{{ site_url('/') }}">Beranda</a></li>
            <li aria-current="page">Data Kependudukan</li>
        </ol>
    </nav>

    <h1 class="text-h2">Data Kependudukan</h1>

    <div class="flex flex-col lg:flex-row gap-4 mt-4">
        <aside class="lg:w-64 flex-shrink-0">
            @include('theme::partials.kependudukan.navigasi')
        </aside>
        <main class="flex-1">
            <div class="box box-primary">
                <div class="box-body">
                    <p class="text-gray-600">
                        Pilih kategori data kependudukan pada menu samping untuk menampilkan rincian.
                    </p>
                </div>
            </div>
        </main>
    </div>
@endsection