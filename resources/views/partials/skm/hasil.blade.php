@extends('theme::layouts.full-content')

@section('content')
    <nav role="navigation" aria-label="navigation" class="breadcrumb">
        <ol>
            <li><a href="{{ site_url('/') }}">Beranda</a></li>
            <li><a href="{{ site_url('survei-kepuasan') }}">Survei Kepuasan</a></li>
            <li aria-current="page">Hasil Survei</li>
        </ol>
    </nav>

    <h1 class="text-h2">Hasil Survei Kepuasan Masyarakat</h1>

    @if(!empty($daftar_survei) && count($daftar_survei) > 1)
        {!! form_open(site_url('survei-kepuasan/hasil'), ['class' => 'mb-4']) !!}
            <label for="pilih_survei" class="font-bold">Pilih Survei</label>
            <select name="pilih_survei" id="pilih_survei" class="form-control" onchange="this.form.submit()">
                @foreach($daftar_survei as $s)
                    <option value="{{ $s->id }}" {{ (int)($survei->id ?? 0) === (int)$s->id ? 'selected' : '' }}>
                        {{ $s->judul ?? ('Survei #' . $s->id) }}
                    </option>
                @endforeach
            </select>
        {!! form_close() !!}
    @endif

    @if(empty($ok) || empty($survei))
        <div class="alert alert-warning my-3">
            Hasil survei belum tersedia. Hasil ditampilkan setelah minimum tanggapan terpenuhi.
        </div>
    @else
        <div class="box box-primary my-3">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $survei->judul ?? 'Survei Kepuasan' }}</h3>
            </div>
            <div class="box-body">
                @if(!empty($rekap))
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="rounded bg-blue-500 text-white p-3">
                            <div class="text-2xl font-bold">{{ $rekap['jumlah'] ?? 0 }}</div>
                            <div class="text-sm">Jumlah Tanggapan</div>
                        </div>
                        <div class="rounded bg-green-500 text-white p-3">
                            <div class="text-2xl font-bold">{{ number_format($rekap['rata'] ?? 0, 2) }}</div>
                            <div class="text-sm">Rata-rata Skor</div>
                        </div>
                        <div class="rounded bg-yellow-500 text-white p-3">
                            <div class="text-2xl font-bold">{{ $rekap['mutu'] ?? '-' }}</div>
                            <div class="text-sm">Mutu Pelayanan</div>
                        </div>
                        <div class="rounded bg-gray-500 text-white p-3">
                            <div class="text-2xl font-bold">{{ $rekap['persen'] ?? 0 }}%</div>
                            <div class="text-sm">Persentase</div>
                        </div>
                    </div>
                @endif

                @if(!empty($pertanyaan) && count($pertanyaan))
                    <h4 class="font-bold mt-4 mb-2">Rincian per Pertanyaan</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered text-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Pertanyaan</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Buruk</th>
                                    <th class="text-center">Cukup</th>
                                    <th class="text-center">Puas</th>
                                    <th class="text-center">Sangat Puas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pertanyaan as $idx => $q)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $q->pertanyaan ?? $q->teks ?? '' }}</td>
                                        <td class="text-center">{{ number_format($q->rata ?? 0, 2) }}</td>
                                        <td class="text-center">{{ $q->b1 ?? 0 }}</td>
                                        <td class="text-center">{{ $q->b2 ?? 0 }}</td>
                                        <td class="text-center">{{ $q->b3 ?? 0 }}</td>
                                        <td class="text-center">{{ $q->b4 ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(!empty($rekap['prioritas']) && count($rekap['prioritas']))
                    <h4 class="font-bold mt-4 mb-2">Prioritas Perbaikan (3 terendah)</h4>
                    <ul class="list-disc pl-5">
                        @foreach($rekap['prioritas'] as $p)
                            <li>{{ $p }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($jumlah_per_survei))
                    <p class="text-sm text-gray-500 mt-4">
                        Total tanggapan per survei: {{ implode(', ', (array)$jumlah_per_survei) }}
                    </p>
                @endif

                <div class="mt-4">
                    <a href="{{ site_url('survei-kepuasan') }}" class="btn btn-default">Kembali ke Survei</a>
                </div>
            </div>
        </div>
    @endif
@endsection