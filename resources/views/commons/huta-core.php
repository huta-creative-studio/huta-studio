<?php
/**
 * huta-core.php — Runtime core Huta Studio (plain PHP, bukan ionCube).
 *
 * Status: STUB/SCAFFOLD. Fungsi bisnis penuh (SKM engine, XLSX, camera token,
 * dispatch WA) belum diimplementasikan. Gate::core() mengembalikan true agar
 * route modul tidak 404, dan fungsi-fungsi helper menyediakan nilai default
 * yang aman agar partial tidak fatal-error. Lengkapi bertahap sesuai CORE-TODO.
 */
defined('BASEPATH') || exit('No direct script access allowed');

/* ------------------------------------------------------------------ */
/* Runtime gate                                                       */
/* ------------------------------------------------------------------ */
class HutaRuntimeGate
{
    public static function theme(): bool { return true; }
}
// Alias untuk Gate::core() yang mengecek class_exists (jika diaktifkan)
class HutaStudioRuntimeGate2610411 { public static function theme(): bool { return true; } }

/* ------------------------------------------------------------------ */
/* Utilitas desa / konteks                                            */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_resolve_desa_data')) {
    function huta_resolve_desa_data(): array
    {
        $desa = [];
        if (function_exists('identitas')) {
            try { $r = identitas(); if (is_array($r)) $desa = $r; } catch (\Throwable $e) {}
        }
        if (!isset($desa['nama_desa']) && function_exists('setting')) {
            try { $desa['nama_desa'] = (string) setting('nama_desa'); } catch (\Throwable $e) {}
        }
        return $desa;
    }
}

if (!function_exists('huta_social_image_dispatch')) {
    function huta_social_image_dispatch(): void { /* stub */ }
}

if (!function_exists('huta_weather_icon_url')) {
    function huta_weather_icon_url($row): string
    {
        if (is_array($row) && !empty($row['image'])) return (string) $row['image'];
        return function_exists('theme_asset') ? theme_asset('images/weather/default.png') : '';
    }
}

/* ------------------------------------------------------------------ */
/* Konteks arsip (cuaca, gempa, indeks, shalat)                       */
/* ------------------------------------------------------------------ */
/* ------------------------------------------------------------------ */
/* Cuaca — fetch BMKG (prakiraan-cuaca) */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_cuaca_fetch')) {
    function huta_cuaca_fetch(array $desa): array
    {
        $out = [
            'ok' => false, 'message' => 'Data cuaca dimuat dari BMKG.',
            'adm4' => '', 'timezone' => '', 'analysis_label' => '',
            'current_label' => 'Cuaca Saat Ini', 'forecast' => [],
            'lokasi' => [],
            '_current_weather' => [], '_current_temp' => '-', '_current_wind' => '-',
            '_today_temp' => '-', '_hum_min' => '-', '_hum_max' => '-',
        ];
        $kode = trim((string) ($desa['kode_desa'] ?? ''));
        if ($kode === '') $kode = trim((string) ($desa['kode_desa_bps'] ?? ''));
        if ($kode === '' && function_exists('huta_db')) {
            $db = huta_db();
            if ($db) {
                try {
                    $row = $db->query('SELECT kode_desa FROM config LIMIT 1')->row();
                    if ($row && !empty($row->kode_desa)) $kode = trim((string) $row->kode_desa);
                } catch (\Throwable $e) {}
            }
        }
        if (strlen($kode) === 10 && ctype_digit($kode)) {
            $adm4 = substr($kode, 0, 2) . '.' . substr($kode, 2, 2) . '.' . substr($kode, 4, 2) . '.' . substr($kode, 6, 4);
        } else {
            $adm4 = '';
        }
        if ($adm4 === '') { $out['message'] = 'Kode wilayah desa tidak tersedia.'; return $out; }
        $out['adm4'] = $adm4;
        $url = 'https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=' . urlencode($adm4);
        $raw = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $raw = (string) curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => ['timeout' => 8]]);
            $raw = (string) @file_get_contents($url, false, $ctx);
        }
        if ($raw === '') { $out['message'] = 'Gagal mengambil data dari BMKG.'; return $out; }
        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['data'][0]['cuaca'])) {
            $out['message'] = 'Data BMKG tidak valid.';
            return $out;
        }
        $lok = $json['lokasi'] ?? [];
        $out['timezone'] = (string) ($lok['timezone'] ?? '');
        $out['lokasi'] = [
            'desa' => (string) ($lok['desa'] ?? ''),
            'kecamatan' => (string) ($lok['kecamatan'] ?? ''),
            'kotkab' => (string) ($lok['kotkab'] ?? ''),
            'provinsi' => (string) ($lok['provinsi'] ?? ''),
        ];
        $days = $json['data'][0]['cuaca'] ?? [];
        $forecast = [];
        $firstSlot = null;
        $temps = [];
        $humMin = 100; $humMax = 0;
        foreach ($days as $daySlots) {
            if (!is_array($daySlots) || empty($daySlots)) continue;
            $rows = [];
            foreach ($daySlots as $slot) {
                if (!is_array($slot)) continue;
                if ($firstSlot === null) $firstSlot = $slot;
                $t = $slot['t'] ?? null;
                if ($t !== null) $temps[] = (float) $t;
                $hu = $slot['hu'] ?? null;
                if ($hu !== null) { $humMin = min($humMin, (float)$hu); $humMax = max($humMax, (float)$hu); }
                $rows[] = [
                    'time' => substr((string)($slot['local_datetime'] ?? ''), 11, 5),
                    'description' => (string) ($slot['weather_desc'] ?? '-'),
                    'temperature' => $t,
                    'humidity' => $hu,
                    'wind_speed' => $slot['ws'] ?? null,
                    'wind_direction' => (string) ($slot['wd'] ?? '-'),
                    'cloud_cover' => $slot['tcc'] ?? null,
                    'visibility' => ($slot['vs_text'] ?? null) ?: (($slot['vs'] ?? null) !== null ? $slot['vs'] . ' m' : '-'),
                    'image' => (string) ($slot['image'] ?? ''),
                ];
            }
            $first = $daySlots[0] ?? [];
            $dateLabel = substr((string)($first['local_datetime'] ?? ''), 0, 10);
            $forecast[] = ['label' => $dateLabel, 'date_label' => $dateLabel, 'rows' => $rows];
        }
        $out['forecast'] = $forecast;
        $out['analysis_label'] = (string) ($firstSlot['analysis_date'] ?? '');
        $out['ok'] = ($firstSlot !== null);
        $out['_current_weather'] = $firstSlot ? [
            'description' => (string) ($firstSlot['weather_desc'] ?? '-'),
            'time' => substr((string)($firstSlot['local_datetime'] ?? ''), 11, 5),
            'wind_direction' => (string) ($firstSlot['wd'] ?? '-'),
            'image' => (string) ($firstSlot['image'] ?? ''),
        ] : [];
        $out['_current_temp'] = $firstSlot ? (($firstSlot['t'] ?? '-') . '°C') : '-';
        $out['_current_wind'] = $firstSlot ? (($firstSlot['ws'] ?? '-') . ' km/jam') : '-';
        $out['_today_temp'] = !empty($temps) ? (min($temps) . '°C - ' . max($temps) . '°C') : '-';
        $out['_hum_min'] = $humMin === 100 ? '-' : $humMin;
        $out['_hum_max'] = $humMax === 0 ? '-' : $humMax;
        $out['message'] = '';
        return $out;
    }
}

if (!function_exists('huta_view_arsip_context')) {
    function huta_view_arsip_context($desa = [], $config = null, $menu = null): array
    {
        $desa = is_array($desa) ? $desa : [];
        $routeReq = (string) ($GLOBALS['HUTA_PUBLIC_ROUTE_REQ'] ?? '');
        $isCuaca = ($routeReq === 'huta-cuaca');
        $isGempa = ($routeReq === 'huta-gempa');
        $hutaCuaca = ($isCuaca && function_exists('huta_cuaca_fetch')) ? huta_cuaca_fetch($desa) : ['ok' => false, 'message' => 'Data cuaca dimuat dari BMKG.', 'forecast' => [], 'adm4' => '', 'timezone' => '', 'analysis_label' => '', 'current_label' => 'Cuaca Saat Ini', '_current_weather' => [], '_current_temp' => '-', '_current_wind' => '-', '_today_temp' => '-', '_hum_min' => '-', '_hum_max' => '-'];
        $ctx = [
            // penduduk
            'hutaPencarianPendudukEnabled' => false,
            'hutaCekPendudukLocked'        => false,
            'hutaCekPendudukVisible'       => false,
            'hutaArsipPenduduk'            => false,
            // indeks desa
            'hutaArsipIndeks'              => false,
            'hutaIndeksEnabled'            => false,
            'hutaIndeksYear'               => (int) date('Y'),
            'hutaIndeksYears'              => [],
            'hutaIndeksCode'               => '',
            'hutaIndeksCodes'              => [],
            'hutaIndeksBoot'               => [],
            'hutaIndeksDesa'               => [],
            // cuaca
            'hutaArsipCuaca'               => $isCuaca,
            'hutaCuacaEnabled'             => true,
            'hutaCuacaData'                => $hutaCuaca,
            'hutaPreviousUrl'              => '',
            'hutaLocation'                 => [
                'desa'      => ($desa['nama_desa'] ?? '') ?: ($hutaCuaca['lokasi']['desa'] ?? ''),
                'kecamatan' => ($desa['nama_kecamatan'] ?? '') ?: ($hutaCuaca['lokasi']['kecamatan'] ?? ''),
                'kabupaten' => ($desa['nama_kabupaten'] ?? '') ?: ($hutaCuaca['lokasi']['kotkab'] ?? ''),
                'provinsi'  => ($desa['nama_provinsi'] ?? '') ?: ($hutaCuaca['lokasi']['provinsi'] ?? ''),
            ],
            'hutaTimezoneLabel'            => $hutaCuaca['timezone'] ?? '',
            'hutaCurrentWeather'           => $hutaCuaca['_current_weather'] ?? [],
            'hutaCurrentTemperature'       => $hutaCuaca['_current_temp'] ?? '-',
            'hutaCurrentWind'              => $hutaCuaca['_current_wind'] ?? '-',
            'hutaTodayTemperature'         => $hutaCuaca['_today_temp'] ?? '-',
            'hutaTodaySummary'             => ['humidity_min' => $hutaCuaca['_hum_min'] ?? '-', 'humidity_max' => $hutaCuaca['_hum_max'] ?? '-'],
            'hutaAir'                      => [],
            'hutaAirClass'                 => 'unknown',
            'hutaAirPm25'                  => '-',
            'hutaAirScale'                 => [],
            // gempa
            'hutaArsipGempa'               => $isGempa,
            'hutaGempaEnabled'             => true,
            'hutaGempaBoot'                => [
                'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json',
                'token'    => '',
                'scope'    => 'autogempa',
                'village'  => trim(ucwords((string)(function_exists('setting') ? setting('sebutan_desa') : 'Desa')) . ' ' . ($desa['nama_desa'] ?? '')),
            ],
            // shalat (dinonaktifkan default — user tidak memilih)
            'hutaArsipShalat'              => false,
            'hutaShalatData'               => [],
            'hutaRequestedShalatCalendar'  => 'masehi',
            'hutaArsipBase'                => function_exists('site_url') ? site_url('arsip') : '/arsip',
        ];
        return $ctx;
    }
}

if (!function_exists('huta_view_environment_context')) {
    function huta_view_environment_context($data = []): array
    {
        return [
            'hutaCurrentWeather'    => $data['current'] ?? [],
            'hutaCurrentTemperature'=> '-',
            'hutaCurrentWind'       => '-',
            'hutaTodayTemperature'  => '-',
            'hutaTodaySummary'      => [],
            'hutaAir'               => [],
            'hutaAirClass'          => 'unknown',
            'hutaAirPm25'           => '-',
            'hutaAirScale'          => [],
            'hutaTimezoneLabel'     => '',
        ];
    }
}

if (!function_exists('huta_view_shalat_page_context')) {
    function huta_view_shalat_page_context($data = []): array
    {
        return [
            'hutaToday'             => [],
            'hutaPrayerMeta'        => [],
            'hutaTodayTimes'        => [],
            'hutaShalatTimezoneLabel'=> '',
        ];
    }
}

if (!function_exists('huta_indeks_desa_available_years')) {
    function huta_indeks_desa_available_years(): array { return []; }
}

if (!function_exists('huta_indeks_desa_bootstrap')) {
    function huta_indeks_desa_bootstrap($desa = null, $year = null): array { return []; }
}

/* ------------------------------------------------------------------ */
/* SKM helpers (stub)                                                 */
/* ------------------------------------------------------------------ */
/* ------------------------------------------------------------------ */
/* SKM — helper DB                                                    */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_db')) {
    function huta_db()
    {
        if (function_exists('get_instance')) {
            $ci = get_instance();
            if (is_object($ci) && isset($ci->db) && is_object($ci->db)) return $ci->db;
        }
        return null;
    }
}

if (!function_exists('huta_table_exists')) {
    function huta_table_exists($db, string $table): bool
    {
        if (!$db) return false;
        try { return $db->table_exists($table); } catch (\Throwable $e) { return false; }
    }
}

if (!function_exists('huta_now')) {
    function huta_now(): string { return date('Y-m-d H:i:s'); }
}

/* ------------------------------------------------------------------ */
/* SKM — schema                                                      */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_skm_ensure_tables')) {
    function huta_skm_ensure_tables(): void
    {
        $db = huta_db();
        if (!$db) return;
        $prefix = $db->dbprefix ?? '';
        $tSurvei = $prefix . 'skm_survei';
        $tQ      = $prefix . 'skm_pertanyaan';
        $tTang   = $prefix . 'skm_tanggapan';
        $tJaw    = $prefix . 'skm_jawaban';

        if (!huta_table_exists($db, $tSurvei)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$tSurvei}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `judul` VARCHAR(200) NOT NULL,
                `objek` VARCHAR(200) DEFAULT NULL,
                `mulai` DATE DEFAULT NULL,
                `mulai_jam` VARCHAR(8) DEFAULT '00:00',
                `selesai` DATE DEFAULT NULL,
                `selesai_jam` VARCHAR(8) DEFAULT '23:59',
                `aktif` TINYINT(1) NOT NULL DEFAULT 0,
                `publikasi` TINYINT(1) NOT NULL DEFAULT 0,
                `publikasi_min` INT NOT NULL DEFAULT 5,
                `arsip` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!huta_table_exists($db, $tQ)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$tQ}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `survei_id` INT UNSIGNED NOT NULL,
                `pertanyaan` VARCHAR(500) NOT NULL,
                `urutan` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `survei_id` (`survei_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!huta_table_exists($db, $tTang)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$tTang}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `survei_id` INT UNSIGNED NOT NULL,
                `token` VARCHAR(64) NOT NULL,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `survei_token` (`survei_id`,`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!huta_table_exists($db, $tJaw)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$tJaw}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tanggapan_id` INT UNSIGNED NOT NULL,
                `pertanyaan_id` INT UNSIGNED NOT NULL,
                `nilai` TINYINT NOT NULL,
                `saran` TEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `tanggapan_id` (`tanggapan_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}

/* ------------------------------------------------------------------ */
/* SKM — dispatch                                                     */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_skm_v2_dispatch')) {
    function huta_skm_v2_dispatch(string $mode, array $args = []): array
    {
        huta_skm_ensure_tables();
        $db = huta_db();
        if (!$db) return ['ok' => false, 'message' => 'Database tidak tersedia.'];

        $prefix = $db->dbprefix ?? '';
        $tSurvei = $prefix . 'skm_survei';
        $tQ      = $prefix . 'skm_pertanyaan';
        $tTang   = $prefix . 'skm_tanggapan';
        $tJaw    = $prefix . 'skm_jawaban';

        /* ---------- public_form ---------- */
        if ($mode === 'public_form') {
            $chosen = isset($args['survei']) ? (int) $args['survei'] : 0;
            $daftar = $db->query("SELECT * FROM `{$tSurvei}` WHERE arsip=0 AND aktif=1 ORDER BY id DESC")->result();
            if (!$daftar) $daftar = [];
            if ($chosen <= 0 && $daftar) $chosen = (int) $daftar[0]->id;
            $survei = null;
            foreach ($daftar as $s) { if ((int)$s->id === $chosen) { $survei = $s; break; } }
            if (!$survei && $daftar) $survei = $daftar[0];

            $pertanyaan = [];
            $token = '';
            $sudah = false;
            if ($survei) {
                $pertanyaan = $db->query("SELECT * FROM `{$tQ}` WHERE survei_id=? ORDER BY urutan ASC, id ASC", [(int)$survei->id])->result();
                if (!$pertanyaan) $pertanyaan = [];
                // token pengunjung (hash IP+UA)
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $token = substr(hash('sha256', $ip . '|' . $ua . '|' . $survei->id), 0, 32);
                // cek sudah menilai
                $row = $db->query("SELECT id FROM `{$tTang}` WHERE survei_id=? AND token=?", [(int)$survei->id, $token])->row();
                if ($row) $sudah = true;
            }
            $receipt = null;
            if (function_exists('get_instance')) {
                $ci = get_instance();
                if (isset($ci->session)) {
                    $r = $ci->session->flashdata('huta_skm2_receipt');
                    if (is_array($r) && !empty($r['survei_id'])) $receipt = $r;
                }
            }
            return [
                'ok'            => ($survei !== null),
                'survei'        => $survei,
                'daftar_survei' => $daftar,
                'pertanyaan'    => $pertanyaan,
                'token'         => $token,
                'sudah_menilai' => $sudah,
                'receipt'       => $receipt,
            ];
        }

        /* ---------- public_results ---------- */
        if ($mode === 'public_results') {
            $input = is_array($args) ? $args : [];
            $pilih = isset($input['pilih_survei']) ? (int) $input['pilih_survei'] : 0;
            $halaman = isset($input['halaman']) ? (int) $input['halaman'] : 1;
            if ($halaman < 1) $halaman = 1;
            $daftar = $db->query("SELECT * FROM `{$tSurvei}` WHERE arsip=0 ORDER BY id DESC")->result();
            if (!$daftar) $daftar = [];
            if ($pilih <= 0 && $daftar) $pilih = (int) $daftar[0]->id;
            $survei = null;
            foreach ($daftar as $s) { if ((int)$s->id === $pilih) { $survei = $s; break; } }
            if (!$survei && $daftar) $survei = $daftar[0];

            $pertanyaan = [];
            $rekap = ['jumlah' => 0, 'rata' => 0, 'mutu' => '-', 'persen' => 0, 'prioritas' => []];
            $jumlah_per_survei = [];
            $jumlah_halaman = 1;

            if ($survei) {
                $pertanyaan = $db->query("SELECT * FROM `{$tQ}` WHERE survei_id=? ORDER BY urutan ASC, id ASC", [(int)$survei->id])->result();
                if (!$pertanyaan) $pertanyaan = [];

                // hitung tanggapan & jawaban
                $tanggapan = $db->query("SELECT id FROM `{$tTang}` WHERE survei_id=?", [(int)$survei->id])->result();
                $jumlah = count($tanggapan ?: []);
                $rekap['jumlah'] = $jumlah;

                // distribusi per pertanyaan
                $totalNilai = 0; $totalJawaban = 0;
                foreach ($pertanyaan as $q) {
                    $q->b1 = $q->b2 = $q->b3 = $q->b4 = 0;
                    $q->rata = 0;
                }
                $jawaban = $db->query("SELECT j.pertanyaan_id, j.nilai FROM `{$tJaw}` j JOIN `{$tTang}` t ON t.id=j.tanggapan_id WHERE t.survei_id=?", [(int)$survei->id])->result();
                foreach ($jawaban as $j) {
                    foreach ($pertanyaan as $q) {
                        if ((int)$q->id === (int)$j->pertanyaan_id) {
                            $n = (int) $j->nilai;
                            if ($n === 1) $q->b1++;
                            elseif ($n === 2) $q->b2++;
                            elseif ($n === 3) $q->b3++;
                            elseif ($n === 4) $q->b4++;
                            $q->rata += $n;
                            $totalNilai += $n;
                            $totalJawaban++;
                        }
                    }
                }
                foreach ($pertanyaan as $q) {
                    $n = $q->b1 + $q->b2 + $q->b3 + $q->b4;
                    if ($n > 0) $q->rata = round($q->rata / $n, 2);
                }
                if ($totalJawaban > 0) {
                    $rata = $totalNilai / $totalJawaban;
                    $rekap['rata'] = round($rata, 2);
                    $rekap['persen'] = (int) round(($rata / 4) * 100);
                }
                // mutu
                $r = $rekap['rata'];
                if ($r >= 3.5) $rekap['mutu'] = 'A';
                elseif ($r >= 3.0) $rekap['mutu'] = 'B';
                elseif ($r >= 2.5) $rekap['mutu'] = 'C';
                elseif ($r > 0) $rekap['mutu'] = 'D';
                else $rekap['mutu'] = '-';

                // prioritas (3 terendah)
                $sorted = $pertanyaan;
                usort($sorted, function($a,$b){ return ($a->rata ?? 0) <=> ($b->rata ?? 0); });
                $pri = [];
                foreach (array_slice($sorted, 0, 3) as $q) {
                    if (($q->rata ?? 0) > 0) $pri[] = $q->pertanyaan;
                }
                $rekap['prioritas'] = $pri;

                // jumlah per survei
                foreach ($daftar as $s) {
                    $c = $db->query("SELECT COUNT(*) c FROM `{$tTang}` WHERE survei_id=?", [(int)$s->id])->row();
                    $jumlah_per_survei[(int)$s->id] = $c ? (int)$c->c : 0;
                }

                // batas publikasi
                $min = (int) ($survei->publikasi_min ?? 5);
                if ($survei->publikasi != 1 || $jumlah < $min) {
                    // hasil belum boleh ditampilkan → kembalikan ok false
                    return [
                        'ok' => false,
                        'survei' => $survei,
                        'rekap' => $rekap,
                        'daftar_survei' => $daftar,
                        'pertanyaan' => $pertanyaan,
                        'jumlah_per_survei' => $jumlah_per_survei,
                        'halaman' => $halaman,
                        'jumlah_halaman' => $jumlah_halaman,
                    ];
                }
            }

            return [
                'ok'                => ($survei !== null),
                'survei'            => $survei,
                'rekap'             => $rekap,
                'daftar_survei'     => $daftar,
                'pertanyaan'        => $pertanyaan,
                'jumlah_per_survei' => $jumlah_per_survei,
                'halaman'           => $halaman,
                'jumlah_halaman'    => $jumlah_halaman,
            ];
        }

        /* ---------- public_submit ---------- */
        if ($mode === 'public_submit') {
            $post = is_array($args) ? $args : [];
            $surveiId = isset($post['survei']) ? (int) $post['survei'] : 0;
            if ($surveiId <= 0) return ['ok' => false, 'message' => 'Survei tidak valid.'];

            $survei = $db->query("SELECT * FROM `{$tSurvei}` WHERE id=? AND aktif=1 AND arsip=0", [$surveiId])->row();
            if (!$survei) return ['ok' => false, 'message' => 'Survei tidak aktif.'];

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $token = substr(hash('sha256', $ip . '|' . $ua . '|' . $surveiId), 0, 32);

            // cek sudah menilai
            $existing = $db->query("SELECT id FROM `{$tTang}` WHERE survei_id=? AND token=?", [$surveiId, $token])->row();
            if ($existing) return ['ok' => false, 'message' => 'Anda sudah memberikan penilaian untuk survei ini.'];

            $jawaban = isset($post['jawaban']) && is_array($post['jawaban']) ? $post['jawaban'] : [];
            $pertanyaan = $db->query("SELECT id FROM `{$tQ}` WHERE survei_id=?", [$surveiId])->result();
            $validIds = [];
            foreach ($pertanyaan as $q) $validIds[] = (int) $q->id;
            if (empty($validIds)) return ['ok' => false, 'message' => 'Survei belum memiliki pertanyaan.'];

            // validasi semua pertanyaan terjawab
            foreach ($validIds as $qid) {
                if (!isset($jawaban[$qid])) return ['ok' => false, 'message' => 'Semua pertanyaan harus dijawab.'];
                $v = (int) $jawaban[$qid];
                if ($v < 1 || $v > 4) return ['ok' => false, 'message' => 'Jawaban tidak valid.'];
            }

            // simpan tanggapan
            $db->query("INSERT INTO `{$tTang}` (survei_id, token, created_at) VALUES (?,?,?)", [$surveiId, $token, huta_now()]);
            $tanggapanId = (int) $db->insert_id();

            $saran = isset($post['saran']) ? trim((string) $post['saran']) : '';
            if (strlen($saran) > 500) $saran = substr($saran, 0, 500);

            foreach ($validIds as $qid) {
                $db->query("INSERT INTO `{$tJaw}` (tanggapan_id, pertanyaan_id, nilai, saran, created_at) VALUES (?,?,?,?,?)",
                    [$tanggapanId, $qid, (int) $jawaban[$qid], $saran, huta_now()]);
                $saran = ''; // saran hanya disimpan di jawaban pertama? simpan sekali
            }
            // simpan saran di jawaban pertama saja (sudah di atas); pastikan tidak duplikat: set saran='' untuk sisanya sudah dilakukan

            // receipt
            $receipt = ['survei_id' => $surveiId, 'baris' => count($validIds)];
            if (function_exists('get_instance')) {
                $ci = get_instance();
                if (isset($ci->session)) $ci->session->set_flashdata('huta_skm2_receipt', $receipt);
            }

            return ['ok' => true, 'receipt' => $receipt];
        }

        /* ---------- admin_list ---------- */
        if ($mode === 'admin_list') {
            $surveiId = isset($args['survei']) ? (int) $args['survei'] : 0;
            $saranPage = isset($args['saran_page']) ? (int) $args['saran_page'] : 1;
            if ($saranPage < 1) $saranPage = 1;

            $daftar = $db->query("SELECT * FROM `{$tSurvei}` WHERE arsip=0 ORDER BY id DESC")->result();
            if (!$daftar) $daftar = [];

            if ($surveiId <= 0 && $daftar) $surveiId = (int) $daftar[0]->id;
            $survei = null;
            foreach ($daftar as $s) { if ((int)$s->id === $surveiId) { $survei = $s; break; } }

            $pertanyaan = [];
            $rekap = ['jumlah' => 0, 'rata' => 0, 'mutu' => '-', 'persen' => 0, 'prioritas' => []];
            $saran = [];
            $saran_page = $saranPage;
            $saran_pages = 1;

            if ($survei) {
                $pertanyaan = $db->query("SELECT * FROM `{$tQ}` WHERE survei_id=? ORDER BY urutan ASC, id ASC", [(int)$survei->id])->result();
                if (!$pertanyaan) $pertanyaan = [];

                $tanggapan = $db->query("SELECT id, created_at FROM `{$tTang}` WHERE survei_id=? ORDER BY id DESC", [(int)$survei->id])->result();
                $jumlah = count($tanggapan ?: []);
                $rekap['jumlah'] = $jumlah;

                $totalNilai = 0; $totalJawaban = 0;
                foreach ($pertanyaan as $q) { $q->b1=$q->b2=$q->b3=$q->b4=0; $q->rata=0; }
                $jawaban = $db->query("SELECT j.pertanyaan_id, j.nilai, j.saran, j.created_at, t.created_at AS tgl FROM `{$tJaw}` j JOIN `{$tTang}` t ON t.id=j.tanggapan_id WHERE t.survei_id=?", [(int)$survei->id])->result();
                foreach ($jawaban as $j) {
                    foreach ($pertanyaan as $q) {
                        if ((int)$q->id === (int)$j->pertanyaan_id) {
                            $n = (int)$j->nilai;
                            if ($n===1) $q->b1++; elseif($n===2) $q->b2++; elseif($n===3) $q->b3++; elseif($n===4) $q->b4++;
                            $q->rata += $n; $totalNilai += $n; $totalJawaban++;
                            if (!empty($j->saran)) $saran[] = ['tanggal' => $j->tgl, 'saran' => $j->saran];
                        }
                    }
                }
                foreach ($pertanyaan as $q) {
                    $n = $q->b1+$q->b2+$q->b3+$q->b4;
                    if ($n > 0) $q->rata = round($q->rata/$n, 2);
                }
                if ($totalJawaban > 0) {
                    $r = $totalNilai/$totalJawaban;
                    $rekap['rata'] = round($r,2);
                    $rekap['persen'] = (int)round(($r/4)*100);
                }
                $r = $rekap['rata'];
                if ($r >= 3.5) $rekap['mutu']='A';
                elseif ($r >= 3.0) $rekap['mutu']='B';
                elseif ($r >= 2.5) $rekap['mutu']='C';
                elseif ($r > 0) $rekap['mutu']='D';
                else $rekap['mutu']='-';

                $sorted = $pertanyaan;
                usort($sorted, function($a,$b){ return ($a->rata ?? 0) <=> ($b->rata ?? 0); });
                $pri = [];
                foreach (array_slice($sorted, 0, 3) as $q) { if (($q->rata ?? 0) > 0) $pri[] = $q->pertanyaan; }
                $rekap['prioritas'] = $pri;

                // paginasi saran (10 per halaman)
                $perPage = 10;
                $totalSaran = count($saran);
                $saran_pages = max(1, (int)ceil($totalSaran / $perPage));
                if ($saranPage > $saran_pages) $saranPage = $saran_pages;
                $saran = array_slice($saran, ($saranPage-1)*$perPage, $perPage);
                $saran_page = $saranPage;

                // survei_terbuka (survei aktif lain)
                $survei_terbuka = [];
                foreach ($daftar as $s) {
                    if ((int)$s->id !== (int)$survei->id && (int)$s->aktif === 1) $survei_terbuka[] = $s;
                }
            }

            $editing = false;
            if ($survei && function_exists('can') && can('u','huta-skm') && !$survei->aktif && !$survei->publikasi && ($rekap['jumlah'] ?? 0) === 0) {
                $editing = true;
            }
            $mode = function_exists('theme_config') ? (string) theme_config('skm_v2_mode','0') : '0';
            $selected = $survei ? (int)$survei->id : 0;
            $otherOpen = null;

            return [
                'ok'              => true,
                'survei'          => $survei,
                'daftar_survei'   => $daftar,
                'pertanyaan'      => $pertanyaan,
                'rekap'           => $rekap,
                'saran'           => $saran,
                'saran_page'      => $saran_page,
                'saran_pages'     => $saran_pages,
                'survei_terbuka'  => $survei_terbuka ?? [],
                'editing'         => $editing,
                'mode'            => $mode,
                'selected'        => $selected,
                'otherOpen'       => $otherOpen,
            ];
        }

        /* ---------- admin mutations ---------- */
        $post = is_array($args) ? $args : [];

        if ($mode === 'admin_survey_create') {
            $judul = trim((string)($post['judul'] ?? ''));
            $objek = trim((string)($post['objek'] ?? ''));
            $mulai = trim((string)($post['mulai'] ?? ''));
            $selesai = trim((string)($post['selesai'] ?? ''));
            if ($judul === '') return ['ok' => false, 'message' => 'Judul survei wajib diisi.'];
            if ($mulai === '') $mulai = date('Y-m-d');
            if ($selesai === '') $selesai = date('Y-m-d', strtotime('+30 days'));
            $db->query("INSERT INTO `{$tSurvei}` (judul, objek, mulai, mulai_jam, selesai, selesai_jam, aktif, publikasi, publikasi_min, arsip, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$judul, $objek, $mulai, $post['mulai_jam'] ?? '00:00', $selesai, $post['selesai_jam'] ?? '23:59', 0, 0, 5, 0, huta_now(), huta_now()]);
            return ['ok' => true, 'message' => 'Survei berhasil dibuat.', 'survei_id' => (int) $db->insert_id()];
        }

        if ($mode === 'admin_survey_edit') {
            $id = (int)($post['survei_id'] ?? 0);
            if ($id <= 0) return ['ok' => false, 'message' => 'Survei tidak valid.'];
            $fields = [];
            $params = [];
            foreach (['judul','objek','mulai','mulai_jam','selesai','selesai_jam'] as $f) {
                if (isset($post[$f])) { $fields[] = "`{$f}`=?"; $params[] = $post[$f]; }
            }
            if ($fields) {
                $params[] = $id;
                $db->query("UPDATE `{$tSurvei}` SET " . implode(',', $fields) . ", updated_at=? WHERE id=?", array_merge($params, [huta_now(), $id]));
            }
            return ['ok' => true, 'message' => 'Survei berhasil diubah.', 'survei_id' => $id];
        }

        if ($mode === 'admin_survey_archive') {
            $id = (int)($post['survei_id'] ?? 0);
            $konf = trim((string)($post['konfirmasi'] ?? ''));
            if ($konf !== 'ARSIPKAN') return ['ok' => false, 'message' => 'Konfirmasi arsip tidak sesuai.'];
            $db->query("UPDATE `{$tSurvei}` SET arsip=1, updated_at=? WHERE id=?", [huta_now(), $id]);
            return ['ok' => true, 'message' => 'Survei berhasil diarsipkan.', 'survei_id' => 0];
        }

        if ($mode === 'admin_question_save') {
            $surveiId = (int)($post['survei_id'] ?? 0);
            $qid = (int)($post['pertanyaan_id'] ?? 0);
            $teks = trim((string)($post['pertanyaan'] ?? ''));
            if ($surveiId <= 0 || $teks === '') return ['ok' => false, 'message' => 'Data pertanyaan tidak valid.'];
            if ($qid > 0) {
                $db->query("UPDATE `{$tQ}` SET pertanyaan=? WHERE id=? AND survei_id=?", [$teks, $qid, $surveiId]);
            } else {
                $urutan = (int) $db->query("SELECT COALESCE(MAX(urutan),0)+1 u FROM `{$tQ}` WHERE survei_id=?", [$surveiId])->row()->u;
                $db->query("INSERT INTO `{$tQ}` (survei_id, pertanyaan, urutan, created_at) VALUES (?,?,?,?)", [$surveiId, $teks, $urutan, huta_now()]);
            }
            return ['ok' => true, 'message' => 'Pertanyaan berhasil disimpan.', 'survei_id' => $surveiId];
        }

        if ($mode === 'admin_question_delete') {
            $surveiId = (int)($post['survei_id'] ?? 0);
            $qid = (int)($post['pertanyaan_id'] ?? 0);
            if ($qid > 0) $db->query("DELETE FROM `{$tQ}` WHERE id=? AND survei_id=?", [$qid, $surveiId]);
            return ['ok' => true, 'message' => 'Pertanyaan berhasil dihapus.', 'survei_id' => $surveiId];
        }

        if ($mode === 'admin_status') {
            $surveiId = (int)($post['survei_id'] ?? 0);
            $field = (string)($post['field'] ?? '');
            $value = (string)($post['value'] ?? '0');
            if (!in_array($field, ['aktif','publikasi'], true)) return ['ok' => false, 'message' => 'Field tidak valid.'];
            if ($field === 'aktif' && $value === '1') {
                // tutup survei lain yang aktif
                $db->query("UPDATE `{$tSurvei}` SET aktif=0 WHERE id<>?", [$surveiId]);
            }
            $db->query("UPDATE `{$tSurvei}` SET `{$field}`=?, updated_at=? WHERE id=?", [(int)$value, huta_now(), $surveiId]);
            return ['ok' => true, 'message' => 'Status berhasil diubah.', 'survei_id' => $surveiId];
        }

        if ($mode === 'admin_minimum') {
            $surveiId = (int)($post['survei_id'] ?? 0);
            $min = (int)($post['publikasi_min'] ?? $post['minimum'] ?? 5);
            if ($min < 1) $min = 1;
            $db->query("UPDATE `{$tSurvei}` SET publikasi_min=?, updated_at=? WHERE id=?", [$min, huta_now(), $surveiId]);
            return ['ok' => true, 'message' => 'Batas publikasi berhasil diubah.', 'survei_id' => $surveiId];
        }

        if ($mode === 'admin_reset') {
            $surveiId = (int)($post['survei_id'] ?? 0);
            $konf = trim((string)($post['konfirmasi'] ?? ''));
            if ($konf !== 'HAPUS SURVEI') return ['ok' => false, 'message' => 'Konfirmasi tidak sesuai.'];
            // hapus jawaban & tanggapan & pertanyaan & survei
            $tang = $db->query("SELECT id FROM `{$tTang}` WHERE survei_id=?", [$surveiId])->result();
            foreach ($tang as $t) $db->query("DELETE FROM `{$tJaw}` WHERE tanggapan_id=?", [(int)$t->id]);
            $db->query("DELETE FROM `{$tTang}` WHERE survei_id=?", [$surveiId]);
            $db->query("DELETE FROM `{$tQ}` WHERE survei_id=?", [$surveiId]);
            $db->query("DELETE FROM `{$tSurvei}` WHERE id=?", [$surveiId]);
            return ['ok' => true, 'message' => 'Survei berhasil dihapus.', 'survei_id' => 0];
        }

        if ($mode === 'admin_export') {
            $surveiId = (int)($post['survei'] ?? $post['survei_id'] ?? 0);
            $survei = $db->query("SELECT * FROM `{$tSurvei}` WHERE id=?", [$surveiId])->row();
            if (!$survei) return ['ok' => false, 'message' => 'Survei tidak ditemukan.'];
            $pertanyaan = $db->query("SELECT * FROM `{$tQ}` WHERE survei_id=? ORDER BY urutan ASC, id ASC", [$surveiId])->result();
            if (!$pertanyaan) $pertanyaan = [];
            $rekap = ['jumlah' => 0];
            $tang = $db->query("SELECT COUNT(*) c FROM `{$tTang}` WHERE survei_id=?", [$surveiId])->row();
            $rekap['jumlah'] = $tang ? (int)$tang->c : 0;
            return ['ok' => true, 'survei' => $survei, 'pertanyaan' => $pertanyaan, 'rekap' => $rekap];
        }

        return ['ok' => false, 'message' => 'Aksi tidak dikenal: ' . $mode];
    }
}

if (!function_exists('huta_skm_v2_date_id')) {
    function huta_skm_v2_date_id($date, bool $full = false): string
    {
        if (empty($date)) return '';
        $ts = is_numeric($date) ? (int) $date : strtotime((string) $date);
        if (!$ts) return (string) $date;
        return $full ? date('d F Y H:i', $ts) : date('d F Y', $ts);
    }
}

if (!function_exists('huta_skm_questions')) {
    function huta_skm_questions(): array { return []; }
}
if (!function_exists('huta_skm_config_id')) {
    function huta_skm_config_id(): int { return 1; }
}
if (!function_exists('huta_skm_key')) {
    function huta_skm_key(): string { return 'huta-studio-local'; }
}
if (!function_exists('huta_skm_v2_tables')) {
    function huta_skm_v2_tables(): bool { return true; }
}
if (!function_exists('huta_skm_v2_min_respondents')) {
    function huta_skm_v2_min_respondents(): int { return 5; }
}

/* ------------------------------------------------------------------ */
/* Kesra helpers (stub)                                               */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_kesra_photo_lookup')) {
    function huta_kesra_photo_lookup($token): array
    {
        return ['ok' => false, 'reason' => 'expired', 'state' => []];
    }
}
if (!function_exists('huta_kesra_photo_prepare_schema')) {
    function huta_kesra_photo_prepare_schema(): bool { return true; }
}

/* ------------------------------------------------------------------ */
/* Schema / asset emitters                                            */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_module_apply_schema')) {
    function huta_module_apply_schema($migration, $template): void
    {
        // Buat tabel SKM (idempotent).
        huta_skm_ensure_tables();
    }
}
if (!function_exists('huta_module_public_asset')) {
    function huta_module_public_asset($screen, $type): string { return ''; }
}
if (!function_exists('huta_module_admin_asset')) {
    function huta_module_admin_asset($screen, $type): string { return ''; }
}

/* ------------------------------------------------------------------ */
/* Gate::schemaMigrate override (no-op, izinkan migrasi)                */
/* ------------------------------------------------------------------ */
// Gate::schemaMigrate didefinisikan di Support/Gate.php; fallback di sini
// agar pemanggilan dari migrasi tidak fatal.
if (!function_exists('huta_schema_migrate')) {
    function huta_schema_migrate($key): bool { return true; }
}
