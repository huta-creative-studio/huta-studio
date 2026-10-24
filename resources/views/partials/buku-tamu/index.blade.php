@extends('theme::layouts.full-content')

@section('content')
    <nav role="navigation" aria-label="navigation" class="breadcrumb">
        <ol>
            <li><a href="{{ site_url('/') }}">Beranda</a></li>
            <li aria-current="page">Buku Tamu</li>
        </ol>
    </nav>

    <h1 class="text-h2">Buku Tamu</h1>

    @if(setting('status_buku_tamu') == 1)
        <div class="box box-primary my-3">
            <div class="box-header with-border">
                <h3 class="box-title">Tinggalkan Pesan</h3>
            </div>
            <div class="box-body">
                {!! form_open(site_url('buku-tamu')) !!}
                    <div class="form-group">
                        <label for="nama" class="font-bold">Nama</label>
                        <input type="text" name="nama" id="nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="email" class="font-bold">Email</label>
                        <input type="email" name="email" id="email" class="form-control" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="pesan" class="font-bold">Pesan</label>
                        <textarea name="pesan" id="pesan" class="form-control" rows="4" required maxlength="500"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim</button>
                {!! form_close() !!}
            </div>
        </div>
    @else
        <div class="alert alert-info my-3">Buku tamu sedang dinonaktifkan.</div>
    @endif
@endsection