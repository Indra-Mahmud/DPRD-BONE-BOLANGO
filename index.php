<?php

session_start();

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/models.php';

if (!installed() && !in_array(basename($_SERVER['SCRIPT_NAME']), ['install.php'], true)) {
    header('Location: install.php');
    exit;
}

$route = $_GET['r'] ?? (current_user() ? 'dashboard' : 'login');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function view(string $file, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/views/' . $file . '.php';
}

function id_param(): int
{
    return (int) ($_GET['id'] ?? $_POST['id'] ?? $_POST['surat_id'] ?? 0);
}

try {
    switch ($route) {
        case 'login':
            if (current_user()) {
                redirect('dashboard');
            }
            if ($method === 'POST') {
                verify_csrf();
                $user = user_by_username(trim($_POST['username'] ?? ''));
                if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
                if (
                    $user &&
                    password_verify($_POST['password'] ?? '', $user['password'])
                    ) {
                        if (($user['approval_status'] ?? 'approved') !== 'approved') {
                            flash('error','Akun belum mendapatkan persetujuan Sekretaris Dewan.');
                            redirect('login');
                            }

    $_SESSION['user'] = $user;
    redirect('dashboard');
}
                    $_SESSION['user'] = $user;
                    redirect('dashboard');
                }
                flash('error', 'Nama pengguna atau kata sandi tidak sesuai.');
                redirect('login');
            }
            view('login');
            break;

        case 'logout':
            $_SESSION = [];
            session_destroy();
            header('Location: ' . url('login'));
            exit;

        case 'dashboard':
            require_login();
            view('dashboard', [
                'page' => 'dashboard',
                'title' => 'Dasbor monitoring',
                'counts' => dashboard_counts(),
                'recent' => recent_surat(),
            ]);
            break;

        case 'surat-masuk':
            require_login();
            $q = [
                'q' => $_GET['q'] ?? '',
                'jenis_surat' => $_GET['jenis_surat'] ?? '',
                'status' => $_GET['status'] ?? '',
            ];
            $total = surat_masuk_count($q);
            $meta = paginate($total, (int) ($_GET['page'] ?? 1), 12);
            view('surat_masuk_index', [
                'page' => 'surat-masuk',
                'title' => 'Surat masuk',
                'q' => $q,
                'rows' => surat_masuk_list($q, $meta['offset'], $meta['per_page']),
                'meta' => $meta,
            ]);
            break;

        case 'surat-masuk/baru':
            require_login();
            require_roles('sekretariat', 'admin');
            if ($method === 'POST') {
                verify_csrf();
                try {
                    $file = upload_file($_FILES['berkas'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'masuk');
                    $id = surat_masuk_create([
                        'nomor_agenda' => next_agenda(),
                        'nomor_surat' => trim($_POST['nomor_surat']),
                        'tanggal_surat' => $_POST['tanggal_surat'],
                        'tanggal_diterima' => $_POST['tanggal_diterima'],
                        'pengirim' => trim($_POST['pengirim']),
                        'jenis_surat' => $_POST['jenis_surat'],
                        'perihal' => trim($_POST['perihal']),
                        'tujuan' => trim($_POST['tujuan'] ?? ''),
                        'file_path' => $file,
                        'bentuk_asal' => $_POST['bentuk_asal'] === 'digital' ? 'digital' : 'fisik',
                        'created_by' => current_user()['id'],
                    ]);
                    flash('success', 'Surat tercatat dan berstatus menunggu disposisi.');
                    header('Location: ' . url('surat-masuk/detail', ['id' => $id]));
                    exit;
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect('surat-masuk/baru');
                }
            }
            view('surat_masuk_form', ['page' => 'surat-masuk', 'title' => 'Catat surat masuk']);
            break;

        case 'surat-masuk/detail':
            require_login();
            $surat = surat_masuk_find(id_param());
            if (!$surat) {
                flash('error', 'Surat tidak ditemukan.');
                redirect('surat-masuk');
            }
            view('surat_masuk_detail', [
                'page' => 'surat-masuk',
                'title' => 'Detail surat',
                'surat' => $surat,
                'disposisi' => disposisi_by_surat((int) $surat['id']),
                'tindak' => tindak_lanjut_by_surat((int) $surat['id']),
                'aktivitas' => aktivitas_by_surat((int) $surat['id']),
                'users' => users_by_unit(),
            ]);
            break;

        case 'surat-masuk/disposisi':
            require_login();
            if (!can_disposisi() && !can('admin')) {
                flash('error', 'Hanya Pimpinan DPRD dan Sekwan yang dapat memberi disposisi.');
                redirect('dashboard');
            }
            verify_csrf();
            $suratId = (int) $_POST['surat_id'];
            $pjId = (int) ($_POST['penanggung_jawab_id'] ?? 0);
            $pj = $pjId ? user_by_id($pjId) : null;
            $me = current_user();
            disposisi_create([
                'surat_id' => $suratId,
                'pemberi_id' => $me['id'],
                'jabatan_pemberi' => $me['jabatan'],
                'bertindak_sebagai' => $_POST['bertindak_sebagai'],
                'tujuan_unit' => $_POST['tujuan_unit'],
                'tujuan_user_id' => $pjId ?: null,
                'instruksi' => trim($_POST['instruksi'] ?? ''),
                'penanggung_jawab_id' => $pjId ?: null,
                'penanggung_jawab_nama' => $pj['name'] ?? trim($_POST['penanggung_jawab_nama'] ?? ''),
                'batas_waktu' => $_POST['batas_waktu'] ?? null,
            ]);
            flash('success', 'Disposisi tersimpan. Surat diteruskan kepada pihak yang berkepentingan.');
            header('Location: ' . url('surat-masuk/detail', ['id' => $suratId]));
            exit;

        case 'surat-masuk/teruskan':
            require_login();
            require_roles('ketua_komisi', 'kabag', 'admin');
            verify_csrf();
            $suratId = (int) $_POST['surat_id'];
            $pjId = (int) $_POST['penanggung_jawab_id'];
            $pj = user_by_id($pjId);
            $me = current_user();
            $parent = disposisi_by_surat($suratId);
            $parentId = $parent ? (int) end($parent)['id'] : null;
            disposisi_create([
                'surat_id' => $suratId,
                'parent_id' => $parentId,
                'pemberi_id' => $me['id'],
                'jabatan_pemberi' => $me['jabatan'],
                'bertindak_sebagai' => $me['jabatan'],
                'tujuan_unit' => $_POST['tujuan_unit'],
                'tujuan_user_id' => $pjId,
                'instruksi' => trim($_POST['instruksi'] ?? ''),
                'penanggung_jawab_id' => $pjId,
                'penanggung_jawab_nama' => $pj['name'] ?? '',
                'batas_waktu' => $_POST['batas_waktu'] ?? null,
            ]);
            flash('success', 'Penanggung jawab tindak lanjut telah ditetapkan.');
            header('Location: ' . url('surat-masuk/detail', ['id' => $suratId]));
            exit;

        case 'surat-masuk/tindak-lanjut':
            require_login();
            verify_csrf();
            $suratId = (int) $_POST['surat_id'];
            $bukti = upload_file($_FILES['bukti'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'bukti');
            tindak_lanjut_create([
                'surat_id' => $suratId,
                'disposisi_id' => null,
                'user_id' => current_user()['id'],
                'status' => $_POST['status'] === 'selesai' ? 'selesai' : 'sedang_diproses',
                'hasil' => trim($_POST['hasil']),
                'file_bukti' => $bukti,
            ]);
            flash('success', 'Tindak lanjut tercatat. Status surat diperbarui.');
            header('Location: ' . url('surat-masuk/detail', ['id' => $suratId]));
            exit;

        case 'kotak-disposisi':
            require_login();
            view('kotak_disposisi', [
                'page' => 'kotak-disposisi',
                'title' => 'Kotak disposisi',
                'rows' => inbox_disposisi(current_user()),
            ]);
            break;

        case 'surat-keluar':
            require_login();
            $q = ['q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? ''];
            $total = surat_keluar_count($q);
            $meta = paginate($total, (int) ($_GET['page'] ?? 1), 12);
            view('surat_keluar_index', [
                'page' => 'surat-keluar',
                'title' => 'Surat keluar',
                'q' => $q,
                'rows' => surat_keluar_list($q, $meta['offset'], $meta['per_page']),
            ]);
            break;

        case 'surat-keluar/baru':
            require_login();
            require_roles('sekretariat', 'admin');
            if ($method === 'POST') {
                verify_csrf();
                $file = upload_file($_FILES['berkas'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'keluar');
                $id = surat_keluar_create([
                    'nomor_surat' => trim($_POST['nomor_surat'] ?? '') ?: null,
                    'tanggal_surat' => $_POST['tanggal_surat'] ?: null,
                    'tujuan' => trim($_POST['tujuan']),
                    'jenis_surat' => $_POST['jenis_surat'],
                    'perihal' => trim($_POST['perihal']),
                    'file_path' => $file,
                    'status' => $_POST['status'] === 'menunggu_pemeriksaan' ? 'menunggu_pemeriksaan' : 'draft',
                    'created_by' => current_user()['id'],
                ]);
                flash('success', 'Surat keluar tercatat.');
                header('Location: ' . url('surat-keluar/detail', ['id' => $id]));
                exit;
            }
            view('surat_keluar_form', ['page' => 'surat-keluar', 'title' => 'Buat surat keluar']);
            break;

        case 'surat-keluar/detail':
            require_login();
            $surat = surat_keluar_find(id_param());
            if (!$surat) {
                flash('error', 'Surat tidak ditemukan.');
                redirect('surat-keluar');
            }
            view('surat_keluar_detail', [
                'page' => 'surat-keluar',
                'title' => 'Detail surat keluar',
                'surat' => $surat,
                'pemeriksaan' => pemeriksaan_by_surat((int) $surat['id']),
            ]);
            break;

        case 'surat-keluar/periksa':
            require_login();
            if (!is_pimpinan() && !can('admin')) {
                flash('error', 'Pemeriksaan dilakukan oleh Pimpinan atau Sekwan.');
                redirect('surat-keluar');
            }
            verify_csrf();
            $id = (int) $_POST['id'];
            pemeriksaan_create([
                'surat_keluar_id' => $id,
                'pemeriksa_id' => current_user()['id'],
                'aksi' => $_POST['aksi'] === 'setuju' ? 'setuju' : 'revisi',
                'catatan' => trim($_POST['catatan'] ?? ''),
            ]);
            flash('success', 'Hasil pemeriksaan tersimpan.');
            header('Location: ' . url('surat-keluar/detail', ['id' => $id]));
            exit;

        case 'surat-keluar/kirim':
            require_login();
            require_roles('sekretariat', 'admin');
            verify_csrf();
            $id = (int) $_POST['id'];
            db()->prepare("UPDATE surat_keluar SET status='dikirim', dikirim_at=NOW() WHERE id=?")->execute([$id]);
            flash('success', 'Surat ditandai terkirim dan masuk arsip keluar.');
            header('Location: ' . url('surat-keluar/detail', ['id' => $id]));
            exit;

        case 'arsip':
            require_login();
            $q = [
                'q' => $_GET['q'] ?? '',
                'jenis_surat' => $_GET['jenis_surat'] ?? '',
                'pengirim' => $_GET['pengirim'] ?? '',
                'tujuan' => $_GET['tujuan'] ?? '',
                'tahun' => $_GET['tahun'] ?? '',
                'tanggal_dari' => $_GET['tanggal_dari'] ?? '',
                'tanggal_sampai' => $_GET['tanggal_sampai'] ?? '',
                'unit_disposisi' => $_GET['unit_disposisi'] ?? '',
                'status' => $_GET['status'] ?? '',
            ];
            view('arsip', [
                'page' => 'arsip',
                'title' => 'Arsip digital',
                'q' => $q,
                'rows' => surat_masuk_list($q, 0, 50),
            ]);
            break;

//kjbcvjhebfgeg

        case 'pengguna':
            require_login();

            if (!is_admin() && !can('sekretaris')) {
                flash('error', 'Anda tidak memiliki hak akses ke halaman pengguna.');
                redirect('dashboard');
            }
            // Admin mengajukan pengguna baru
            if ($method === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
                if (!is_admin()) {
                    flash('error', 'Hanya Admin Sistem yang dapat menambahkan pengguna.');
                    redirect('pengguna');
                }
                verify_csrf();
                try {
                    user_create($_POST, (int) current_user()['id']);
                    flash(
                        'success',
                        'Pengguna berhasil diajukan dan menunggu persetujuan Sekwan.'
                    );
                } catch (Throwable $e) {
                    flash(
                        'error',
                        'Gagal menambah pengguna. Username mungkin sudah dipakai.'
                    );
                }
                redirect('pengguna');
            }
            // Sekwan menyetujui pengguna
            if ($method === 'POST' && ($_POST['action'] ?? '') === 'approve_user') {
                if (!can('sekretaris')) {
                    flash('error', 'Hanya Sekwan yang dapat menyetujui pengguna.');
                    redirect('pengguna');
                }
                verify_csrf();
                try {
                    user_approve(
                        (int) $_POST['approval_id'],
                        (int) current_user()['id']
                    );
                    flash('success', 'Pengguna berhasil disetujui.');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }
                redirect('pengguna');
            }
            // Sekwan menolak pengguna
            if ($method === 'POST' && ($_POST['action'] ?? '') === 'reject_user') {
                if (!can('sekretaris')) {
                    flash('error', 'Hanya Sekwan yang dapat menolak pengguna.');
                    redirect('pengguna');
                }
                verify_csrf();
                try {
                    user_reject(
                        (int) $_POST['approval_id'],
                        (int) current_user()['id'],
                        trim($_POST['catatan'] ?? '')
                    );
                    flash('success', 'Pengajuan pengguna ditolak.');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }
                redirect('pengguna');
            }
            view('pengguna', [
                'page' => 'pengguna',
                'title' => 'Pengguna',
                'rows' => users_all(),
                'pending_users' => user_approvals_pending(),
            ]);
            break;
            
//sdbvgeruyru
        case 'pengguna/hapus':
            require_login();
            if (!is_admin()) {
                flash('error', 'Hanya Admin Sistem yang dapat menghapus pengguna.');
                redirect('dashboard');
            }
            if ($method !== 'POST') {
                redirect('pengguna');
            }
            verify_csrf();
            $userId = (int)($_POST['user_id'] ?? 0);
            if (!$userId) {
                flash('error', 'Pengguna tidak valid.');
                redirect('pengguna');
            }
            $target = user_by_id($userId);
            if (!$target) {
                flash('error', 'Pengguna tidak ditemukan.');
                redirect('pengguna');
            }
            view('pengguna_hapus', [
                'page' => 'pengguna',
                'title' => 'Hapus Pengguna',
                'target' => $target,
            ]);
            break;

        case 'pengguna/hapus/proses':
            require_login();
            if (!is_admin()) {
                flash('error', 'Hanya Admin Sistem yang dapat menghapus pengguna.');
                redirect('dashboard');
            }
            verify_csrf();
            $userId = (int)($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            try {
                delete_user_with_password(
                    $userId,
                    $password,
                    (int)current_user()['id']
                );
                flash('success', 'Pengguna berhasil dihapus.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
            redirect('pengguna');
            break;


        default:
            http_response_code(404);
            echo 'Halaman tidak ditemukan.';
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="padding:24px;font-family:monospace">Terjadi kesalahan: ' . e($e->getMessage()) . '</pre>';
}
