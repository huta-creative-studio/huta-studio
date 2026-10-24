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
        return function_exists('theme_asset') ? theme_asset('images/weather/default.png') : '';
    }
}

/* ------------------------------------------------------------------ */
/* Konteks arsip (cuaca, gempa, indeks, shalat)                       */
/* ------------------------------------------------------------------ */
if (!function_exists('huta_view_arsip_context')) {
    function huta_view_arsip_context($desa = [], $config = null, $menu = null): array
    {
        $desa = is_array($desa) ? $desa : [];
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
            'hutaArsipCuaca'               => true,
            'hutaCuacaEnabled'             => true,
            'hutaCuacaData'                => ['ok' => false, 'message' => 'Data cuaca dimuat dari BMKG.'],
            'hutaPreviousUrl'              => '',
            'hutaLocation'                 => [
                'desa'      => $desa['nama_desa'] ?? '',
                'kecamatan' => $desa['nama_kecamatan'] ?? '',
                'kabupaten' => $desa['nama_kabupaten'] ?? '',
                'provinsi'  => $desa['nama_provinsi'] ?? '',
            ],
            'hutaTimezoneLabel'            => '',
            'hutaCurrentWeather'           => [],
            'hutaCurrentTemperature'       => '-',
            'hutaCurrentWind'              => '-',
            'hutaTodayTemperature'         => '-',
            'hutaTodaySummary'             => [],
            'hutaAir'                      => [],
            'hutaAirClass'                 => 'unknown',
            'hutaAirPm25'                  => '-',
            'hutaAirScale'                 => [],
            // gempa
            'hutaArsipGempa'               => true,
            'hutaGempaEnabled'             => true,
            'hutaGempaBoot'                => [
                'endpoint' => '',
                'token'    => '',
                'scope'    => '',
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
if (!function_exists('huta_skm_v2_dispatch')) {
    function huta_skm_v2_dispatch(string $mode, array $args = []): array
    {
        // Stub: kembalikan struktur minimal agar partial tidak fatal.
        return [
            'ok'            => false,
            'survei'        => null,
            'daftar_survei' => [],
            'pertanyaan'    => [],
            'token'         => '',
            'sudah_menilai' => false,
            'rekap'         => ['jumlah' => 0, 'rata' => 0, 'mutu' => '-', 'persen' => 0, 'prioritas' => []],
            'jumlah_per_survei' => [],
            'halaman'       => 1,
            'jumlah_halaman' => 1,
        ];
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
        // Stub: migrasi aktual ditangani oleh OpenSID Migrator / core.
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
