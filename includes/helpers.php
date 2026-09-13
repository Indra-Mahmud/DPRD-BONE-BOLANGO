<?php

function app_config(): array
{
    static $config;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    return $config;
}

function db_config(): array
{
    static $config;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/database.php';
    }
    return $config;
}

function installed(): bool
{
    return is_file(dirname(__DIR__) . '/storage/.installed');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path = '', array $query = []): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    $path = ltrim($path, '/');
    $href = ($base === '' ? '' : $base) . '/index.php';
    if ($path === '') {
        return $query ? $href . '?' . http_build_query($query) : $href;
    }
    if (str_contains($path, '.php')) {
        $href = ($base === '' ? '' : $base) . '/' . $path;
        return $query ? $href . '?' . http_build_query($query) : $href;
    }
    $href .= '?r=' . rawurlencode($path);
    if ($query) {
        $href .= '&' . http_build_query($query);
    }
    return $href;
}

function asset(string $path): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    return ($base === '' ? '' : $base) . '/assets/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function status_label(string $status): string
{
    return match ($status) {
        'menunggu_disposisi' => 'Menunggu Disposisi',
        'didisposisi' => 'Sudah Didisposisi',
        'belum_ditindaklanjuti' => 'Belum Ditindaklanjuti',
        'sedang_diproses' => 'Sedang Diproses',
        'selesai' => 'Selesai',
        'draft' => 'Draf',
        'menunggu_pemeriksaan' => 'Menunggu Pemeriksaan',
        'perlu_revisi' => 'Perlu Revisi',
        'disetujui' => 'Disetujui',
        'dikirim' => 'Terkirim',
        'diarsipkan' => 'Diarsipkan',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'menunggu_disposisi', 'menunggu_pemeriksaan', 'belum_ditindaklanjuti' => 'badge-wait',
        'didisposisi', 'sedang_diproses' => 'badge-progress',
        'selesai', 'disetujui', 'dikirim', 'diarsipkan' => 'badge-done',
        'draft', 'perlu_revisi' => 'badge-draft',
        default => 'badge-wait',
    };
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

function format_datetime(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y H:i', $ts) : $date;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && (int)($user['is_admin'] ?? 0) === 1;
}

// function role_label(string $role): string
// {
//     return match ($role) {
//         'ketua' => 'Ketua',
//         'wakil_ketua_1' => 'Wakil Ketua 1',
//         'wakil_ketua_2' => 'Wakil Ketua 2',
//         'sekretaris' => 'Sekretaris',
//         'sekretaris_pribadi' => 'Sekretaris Pribadi',
//         'anggota' => 'Anggota',
//         default => ucfirst(str_replace('_', ' ', $role)),
//     };
// }

//mulai sini

function can(string ...$roles): bool
{
    $user = current_user();

    if (!$user) {
        return false;
    }

    if (is_admin()) {
        return true;
    }

    return in_array($user['role'], $roles, true);
}

function is_pimpinan(): bool
{
    return can('ketua', 'wakil_ketua_1', 'wakil_ketua_2');
}

function can_disposisi(): bool
{
    return is_pimpinan();
}

function can_register(): bool
{
    return is_admin();
}

function roles(): array
{
    return [
        'ketua' => 'Ketua',
        'wakil_ketua_1' => 'Wakil Ketua 1',
        'wakil_ketua_2' => 'Wakil Ketua 2',
        'sekretaris' => 'Sekretaris',
        'sekretaris_pribadi' => 'Sekretaris Pribadi',
        'anggota' => 'Anggota',
    ];
}

function role_label(string $role): string
{
    return roles()[$role] ?? $role;
}

function units(): array
{
    return [
        'Pimpinan',
        'Sekretaris Dewan',
        'Bagian Umum dan Keuangan',
        'Sub Bagian Umum & Rumah Tangga',
        'Jabatan Fungsional Perencanaan Ahli Muda',
        'Bagian Persidangan dan Perundang-undangan – Kelompok Jabatan Fungsional',
        'Badan Kehormatan',
        'Badan Pembentukan Perda',
    ];
}

function acting_as_options(array $user): array
{
    return match ($user['role'] ?? '') {
        'ketua' => ['Ketua DPRD'],

        'wakil_ketua_1' => [
            'Wakil Ketua 1',
            'Ketua DPRD (mewakili)',
        ],

        'wakil_ketua_2' => [
            'Wakil Ketua 2',
            'Ketua DPRD (mewakili)',
        ],

        default => [],
    };
}

function require_login(): void
{
    if (!current_user()) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('login');
    }
}

function require_roles(string ...$roles): void
{
    require_login();

    if (is_admin()) {
        return;
    }

    if (!can(...$roles)) {
        flash('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        redirect('dashboard');
    }
}


function jenis_surat(): array
{
    return [
        'Surat Undangan',
        'Surat Permohonan',
        'Surat Pemberitahuan',
        'Surat Edaran',
        'Surat Keputusan',
        'Surat Tugas',
        'Nota Dinas',
        'Laporan',
        'Surat Keterangan',
        'Lainnya',
    ];
}

function file_url(?string $name): ?string
{
    if (!$name) {
        return null;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    return ($base === '' ? '' : $base) . '/storage/uploads/' . rawurlencode($name);
}

function paginate(int $total, int $page, int $perPage = 10): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
    ];
}