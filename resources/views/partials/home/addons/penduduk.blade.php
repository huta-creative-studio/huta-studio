<?php defined('BASEPATH')||exit('No direct script access allowed');?>
<div class="huta-cek-page" data-page="cek-penduduk">
  <div class="container-page mb-20">
    <div class="huta-page-card">
      <h1 class="huta-page-title">Cek Data Penduduk</h1>
      <p class="huta-page-subtitle">Masukkan NIK 16 digit untuk melihat data penduduk.</p>
      <form method="GET" action="{{ site_url('cek-penduduk') }}" class="huta-cek-form">
        <label for="nik" class="font-bold">NIK</label>
        <input type="text" name="nik" id="nik" class="form-control" maxlength="16" pattern="[0-9]{16}" required placeholder="16 digit NIK" value="{{ request()->query('nik', '') }}">
        <button type="submit" class="btn btn-primary mt-2">Cari</button>
      </form>

      @php
        $result = $GLOBALS['HUTA_PENDUDUK_RESULT'] ?? null;
      @endphp

      @if(is_array($result))
        @if(!empty($result['ok']))
          <div class="huta-cek-result mt-4">
            <h2 class="huta-page-title" style="font-size:1.1rem;">Hasil Pencarian</h2>
            <table class="table table-striped">
              <tbody>
                <tr><th>Nama</th><td>{{ $result['nama'] }}</td></tr>
                <tr><th>Alamat</th><td>{{ $result['alamat'] }}</td></tr>
                <tr><th>Dusun/RT/RW</th><td>{{ $result['dusun'] }} / {{ $result['rt'] }} / {{ $result['rw'] }}</td></tr>
                <tr><th>Calon Pemilih (17+)</th><td>{{ $result['calon_pemilih'] }}</td></tr>
                <tr><th>DESIL KEMENSOS</th><td>{{ $result['desil'] }}</td></tr>
                <tr><th>Bantuan</th><td>
                  @if(!empty($result['bantuan']))
                    @foreach($result['bantuan'] as $b)
                      {{ $b['program'] }} ({{ $b['no_kartu'] }})<br>
                    @endforeach
                  @else
                    -
                  @endif
                </td></tr>
              </tbody>
            </table>
          </div>
        @else
          <div class="huta-cek-status mt-4" role="status">
            <strong>Tidak Ditemukan</strong>
            <span>{{ $result['message'] ?? 'Data tidak ditemukan.' }}</span>
          </div>
        @endif
      @endif
    </div>
  </div>
</div>