@extends('theme::layouts.full-content')

@section('content')
    <nav role="navigation" aria-label="navigation" class="breadcrumb">
        <ol>
            <li><a href="{{ site_url('/') }}">Beranda</a></li>
            <li aria-current="page">Survei Kepuasan Masyarakat</li>
        </ol>
    </nav>

    <h1 class="text-h2">Survei Kepuasan Masyarakat</h1>

    @if(!empty($receipt))
        <div class="alert alert-success my-3">
            <strong>Terima kasih!</strong> Tanggapan Anda telah tersimpan.
            @if(!empty($receipt['survei_id']))
                (Survei #{{ $receipt['survei_id'] }}, {{ $receipt['baris'] ?? 0 }} jawaban)
            @endif
        </div>
    @endif

    @if(empty($ok))
        <div class="alert alert-warning my-3">
            Survei belum tersedia atau belum diaktifkan. Silakan hubungi pengelola desa.
        </div>
    @else
        {{-- Pilih survei --}}
        @if(!empty($daftar_survei) && count($daftar_survei) > 1)
            <form method="POST" action="{{ site_url('survei-kepuasan') }}" class="mb-4">
                @csrf
                <label for="survei" class="font-bold">Pilih Survei</label>
                <select name="survei" id="survei" class="form-control" onchange="this.form.submit()">
                    @foreach($daftar_survei as $s)
                        <option value="{{ $s->id }}" {{ (int)($survei->id ?? 0) === (int)$s->id ? 'selected' : '' }}>
                            {{ $s->judul ?? ('Survei #' . $s->id) }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        @if(!empty($survei))
            <div class="box box-primary my-3">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $survei->judul ?? 'Survei Kepuasan' }}</h3>
                </div>
                <div class="box-body">
                    @if(!empty($sudah_menilai))
                        <div class="alert alert-info">
                            Anda sudah memberikan penilaian untuk survei ini. Terima kasih!
                        </div>
                    @else
                        <form method="POST" action="{{ site_url('survei-kepuasan/kirim') }}">
                            @csrf
                            <input type="hidden" name="survei" value="{{ $survei->id }}">
                            <input type="hidden" name="token" value="{{ $token ?? '' }}">

                            @foreach($pertanyaan as $idx => $q)
                                <div class="form-group mb-4">
                                    <label class="font-bold">{{ $idx + 1 }}. {{ $q->pertanyaan ?? $q->teks ?? '' }}</label>
                                    <div class="flex gap-4 mt-1">
                                        @foreach([1=>'Buruk',2=>'Cukup',3=>'Puas',4=>'Sangat Puas'] as $val => $label)
                                            <label class="inline-flex items-center gap-1">
                                                <input type="radio" name="jawaban[{{ $q->id ?? $idx }}]" value="{{ $val }}" required>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="form-group">
                                <label for="saran" class="font-bold">Saran / Masukan (opsional)</label>
                                <textarea name="saran" id="saran" class="form-control" rows="3" maxlength="500"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Kirim Penilaian</button>
                            <a href="{{ site_url('survei-kepuasan/hasil') }}" class="btn btn-default ml-2">Lihat Hasil</a>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection