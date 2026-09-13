<?php

function user_by_username(string $username): ?array
{
    $st = db()->prepare('
        SELECT *
        FROM users
        WHERE username = ?
            AND is_active = 1
            AND approval_status = "approved"
        LIMIT 1
    ');
    $st->execute([$username]);
    $row = $st->fetch();
    return $row ?: null;
}


function user_by_id(int $id): ?array
{
    $st = db()->prepare('
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
    ');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

//mulai sini

function users_all(): array
{
    return db()->query(
        'SELECT id, name, username, role, unit, is_admin,
                is_active, approval_status, approved_by, approved_at
         FROM users
         ORDER BY name'
    )->fetchAll();
}

function users_by_unit(?string $unit = null): array
{
    if ($unit) {
        $st = db()->prepare(
            'SELECT id, name, unit, role
             FROM users
             WHERE is_active = 1 AND approval_status = "approved" AND unit = ?
             ORDER BY name'
        );
        $st->execute([$unit]);
        return $st->fetchAll();
    }

    return db()->query(
        'SELECT id, name, unit, role
         FROM users
         WHERE is_active = 1 AND approval_status = "approved"
         ORDER BY name'
    )->fetchAll();
}

function pending_user_approvals(): array
{
    $st = db()->query('
        SELECT
            ua.*,
            u.name,
            u.username,
            u.role,
            u.unit,
            r.name AS requester_name
        FROM user_approval ua
        JOIN users u ON u.id = ua.user_id
        LEFT JOIN users r ON r.id = ua.requested_by
        WHERE ua.status = "pending"
        ORDER BY ua.created_at ASC
    ');

    return $st->fetchAll();
}

function approve_user(int $approvalId, int $approverId): void
{
    db()->beginTransaction();

    try {
        $st = db()->prepare('
            SELECT user_id
            FROM user_approval
            WHERE id = ?
              AND status = "pending"
            FOR UPDATE
        ');
        $st->execute([$approvalId]);

        $approval = $st->fetch();

        if (!$approval) {
            throw new RuntimeException('Permintaan persetujuan tidak ditemukan.');
        }

        db()->prepare('
            UPDATE users
            SET
                approval_status = "approved",
                approved_by = ?,
                approved_at = NOW(),
                is_active = 1
            WHERE id = ?
        ')->execute([
            $approverId,
            $approval['user_id'],
        ]);

        db()->prepare('
            UPDATE user_approval
            SET
                status = "approved",
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ?
        ')->execute([
            $approverId,
            $approvalId,
        ]);

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function reject_user(int $approvalId, int $approverId, string $catatan = ''): void
{
    db()->beginTransaction();

    try {
        $st = db()->prepare('
            SELECT user_id
            FROM user_approval
            WHERE id = ?
              AND status = "pending"
            FOR UPDATE
        ');
        $st->execute([$approvalId]);

        $approval = $st->fetch();

        if (!$approval) {
            throw new RuntimeException('Permintaan persetujuan tidak ditemukan.');
        }

        db()->prepare('
            UPDATE users
            SET
                approval_status = "rejected",
                is_active = 0
            WHERE id = ?
        ')->execute([
            $approval['user_id'],
        ]);

        db()->prepare('
            UPDATE user_approval
            SET
                status = "rejected",
                approved_by = ?,
                approved_at = NOW(),
                catatan = ?
            WHERE id = ?
        ')->execute([
            $approverId,
            $catatan,
            $approvalId,
        ]);

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function delete_user_with_password(int $userId, string $password, int $deletedBy): void
{
    if ($userId === $deletedBy) {
        throw new RuntimeException('Admin tidak dapat menghapus akun yang sedang digunakan.');
    }

    $user = user_by_id($userId);

    if (!$user) {
        throw new RuntimeException('Pengguna tidak ditemukan.');
    }

    if (!password_verify($password, $user['password'])) {
        throw new RuntimeException('Kata sandi pengguna tidak benar.');
    }

    db()->beginTransaction();

    try {
        db()->prepare('
            INSERT INTO user_deletion_log
            (
                user_id,
                deleted_by
            )
            VALUES (?, ?)
        ')->execute([
            $userId,
            $deletedBy,
        ]);

        db()->prepare('
            DELETE FROM users
            WHERE id = ?
        ')->execute([
            $userId,
        ]);

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function log_aktivitas(int $suratId, string $jenis, string $keterangan, ?int $userId = null): void
{
    $st = db()->prepare('INSERT INTO aktivitas (surat_id, jenis, keterangan, user_id) VALUES (?,?,?,?)');
    $st->execute([$suratId, $jenis, $keterangan, $userId ?? (current_user()['id'] ?? null)]);
}

function surat_masuk_create(array $data): int
{
    $st = db()->prepare('INSERT INTO surat_masuk
        (nomor_agenda, nomor_surat, tanggal_surat, tanggal_diterima, pengirim, jenis_surat, perihal, tujuan, file_path, bentuk_asal, status, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        $data['nomor_agenda'],
        $data['nomor_surat'],
        $data['tanggal_surat'],
        $data['tanggal_diterima'],
        $data['pengirim'],
        $data['jenis_surat'],
        $data['perihal'],
        $data['tujuan'],
        $data['file_path'],
        $data['bentuk_asal'],
        'menunggu_disposisi',
        $data['created_by'],
    ]);
    $id = (int) db()->lastInsertId();
    log_aktivitas($id, 'pencatatan', 'Surat diterima dan dicatat oleh sekretariat.');
    return $id;
}

function next_agenda(): string
{
    $year = date('Y');
    $st = db()->prepare("SELECT COUNT(*) FROM surat_masuk WHERE YEAR(tanggal_diterima) = ?");
    $st->execute([$year]);
    $n = (int) $st->fetchColumn() + 1;
    return sprintf('SM/%s/%04d', $year, $n);
}

function surat_masuk_filters(array $q): array
{
    $where = ['1=1'];
    $bind = [];
    if (!empty($q['q'])) {
        $where[] = '(nomor_surat LIKE ? OR perihal LIKE ? OR pengirim LIKE ? OR nomor_agenda LIKE ?)';
        $like = '%' . $q['q'] . '%';
        array_push($bind, $like, $like, $like, $like);
    }
    foreach (['jenis_surat', 'pengirim', 'tujuan', 'status'] as $f) {
        if (!empty($q[$f])) {
            $where[] = "$f LIKE ?";
            $bind[] = '%' . $q[$f] . '%';
        }
    }
    if (!empty($q['tahun'])) {
        $where[] = 'YEAR(tanggal_surat) = ?';
        $bind[] = $q['tahun'];
    }
    if (!empty($q['tanggal_dari'])) {
        $where[] = 'tanggal_surat >= ?';
        $bind[] = $q['tanggal_dari'];
    }
    if (!empty($q['tanggal_sampai'])) {
        $where[] = 'tanggal_surat <= ?';
        $bind[] = $q['tanggal_sampai'];
    }
    if (!empty($q['unit_disposisi'])) {
        $where[] = 'id IN (SELECT surat_id FROM disposisi WHERE tujuan_unit = ?)';
        $bind[] = $q['unit_disposisi'];
    }
    return [implode(' AND ', $where), $bind];
}

function surat_masuk_list(array $q, int $offset, int $limit): array
{
    [$where, $bind] = surat_masuk_filters($q);
    $sql = "SELECT * FROM surat_masuk WHERE $where ORDER BY tanggal_diterima DESC, id DESC LIMIT $limit OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($bind);
    return $st->fetchAll();
}

function surat_masuk_count(array $q): int
{
    [$where, $bind] = surat_masuk_filters($q);
    $st = db()->prepare("SELECT COUNT(*) FROM surat_masuk WHERE $where");
    $st->execute($bind);
    return (int) $st->fetchColumn();
}

function surat_masuk_find(int $id): ?array
{
    $st = db()->prepare('SELECT s.*, u.name AS pencatat FROM surat_masuk s LEFT JOIN users u ON u.id = s.created_by WHERE s.id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function surat_update_status(int $id, string $status): void
{
    $st = db()->prepare('UPDATE surat_masuk SET status = ? WHERE id = ?');
    $st->execute([$status, $id]);
}

function disposisi_create(array $data): int
{
    $st = db()->prepare('INSERT INTO disposisi
        (surat_id, parent_id, pemberi_id, jabatan_pemberi, bertindak_sebagai, tujuan_unit, tujuan_user_id, instruksi, penanggung_jawab_id, penanggung_jawab_nama, batas_waktu)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        $data['surat_id'],
        $data['parent_id'] ?? null,
        $data['pemberi_id'],
        $data['jabatan_pemberi'],
        $data['bertindak_sebagai'],
        $data['tujuan_unit'],
        $data['tujuan_user_id'] ?: null,
        $data['instruksi'],
        $data['penanggung_jawab_id'] ?: null,
        $data['penanggung_jawab_nama'],
        $data['batas_waktu'] ?: null,
    ]);
    $id = (int) db()->lastInsertId();
    surat_update_status((int) $data['surat_id'], 'belum_ditindaklanjuti');
    $pj = $data['penanggung_jawab_nama'] ?: $data['tujuan_unit'];
    log_aktivitas(
        (int) $data['surat_id'],
        'disposisi',
        'Disposisi oleh ' . $data['bertindak_sebagai'] . ' kepada ' . $data['tujuan_unit'] . ' (penanggung jawab: ' . $pj . ').'
    );
    return $id;
}

function disposisi_by_surat(int $suratId): array
{
    $st = db()->prepare('SELECT d.*, u.name AS pemberi_nama, t.name AS tujuan_nama
        FROM disposisi d
        JOIN users u ON u.id = d.pemberi_id
        LEFT JOIN users t ON t.id = d.tujuan_user_id
        WHERE d.surat_id = ?
        ORDER BY d.created_at ASC, d.id ASC');
    $st->execute([$suratId]);
    return $st->fetchAll();
}

function tindak_lanjut_create(array $data): int
{
    $st = db()->prepare('INSERT INTO tindak_lanjut (surat_id, disposisi_id, user_id, status, hasil, file_bukti)
        VALUES (?,?,?,?,?,?)');
    $st->execute([
        $data['surat_id'],
        $data['disposisi_id'] ?: null,
        $data['user_id'],
        $data['status'],
        $data['hasil'],
        $data['file_bukti'],
    ]);
    surat_update_status((int) $data['surat_id'], $data['status'] === 'selesai' ? 'selesai' : 'sedang_diproses');
    log_aktivitas(
        (int) $data['surat_id'],
        'tindak_lanjut',
        'Tindak lanjut: ' . status_label($data['status']) . '.'
    );
    return (int) db()->lastInsertId();
}

function tindak_lanjut_by_surat(int $suratId): array
{
    $st = db()->prepare('SELECT t.*, u.name AS petugas FROM tindak_lanjut t JOIN users u ON u.id = t.user_id WHERE t.surat_id = ? ORDER BY t.created_at DESC');
    $st->execute([$suratId]);
    return $st->fetchAll();
}

function aktivitas_by_surat(int $suratId): array
{
    $st = db()->prepare('SELECT a.*, u.name AS petugas FROM aktivitas a LEFT JOIN users u ON u.id = a.user_id WHERE a.surat_id = ? ORDER BY a.created_at ASC');
    $st->execute([$suratId]);
    return $st->fetchAll();
}

function inbox_disposisi(array $user): array
{
    $st = db()->prepare("SELECT s.*, d.id AS disposisi_id, d.instruksi, d.tujuan_unit, d.batas_waktu, d.bertindak_sebagai, d.penanggung_jawab_nama
        FROM disposisi d
        JOIN surat_masuk s ON s.id = d.surat_id
        WHERE d.tujuan_unit = ? OR d.tujuan_user_id = ? OR d.penanggung_jawab_id = ?
        ORDER BY d.created_at DESC");
    $st->execute([$user['unit'], $user['id'], $user['id']]);
    return $st->fetchAll();
}

function dashboard_counts(): array
{
    $pdo = db();
    $row = static function (string $sql) use ($pdo) {
        return (int) $pdo->query($sql)->fetchColumn();
    };
    return [
        'masuk' => $row('SELECT COUNT(*) FROM surat_masuk'),
        'menunggu' => $row("SELECT COUNT(*) FROM surat_masuk WHERE status = 'menunggu_disposisi'"),
        'proses' => $row("SELECT COUNT(*) FROM surat_masuk WHERE status IN ('didisposisi','belum_ditindaklanjuti','sedang_diproses')"),
        'selesai' => $row("SELECT COUNT(*) FROM surat_masuk WHERE status IN ('selesai','diarsipkan')"),
        'keluar' => $row('SELECT COUNT(*) FROM surat_keluar'),
        'keluar_tunggu' => $row("SELECT COUNT(*) FROM surat_keluar WHERE status = 'menunggu_pemeriksaan'"),
    ];
}

function recent_surat(int $limit = 6): array
{
    $st = db()->query("SELECT * FROM surat_masuk ORDER BY id DESC LIMIT $limit");
    return $st->fetchAll();
}

function surat_keluar_create(array $data): int
{
    $st = db()->prepare('INSERT INTO surat_keluar (nomor_surat, tanggal_surat, tujuan, jenis_surat, perihal, file_path, status, created_by)
        VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([
        $data['nomor_surat'],
        $data['tanggal_surat'],
        $data['tujuan'],
        $data['jenis_surat'],
        $data['perihal'],
        $data['file_path'],
        $data['status'],
        $data['created_by'],
    ]);
    return (int) db()->lastInsertId();
}

function surat_keluar_list(array $q, int $offset, int $limit): array
{
    $where = ['1=1'];
    $bind = [];
    if (!empty($q['q'])) {
        $where[] = '(nomor_surat LIKE ? OR perihal LIKE ? OR tujuan LIKE ?)';
        $like = '%' . $q['q'] . '%';
        array_push($bind, $like, $like, $like);
    }
    if (!empty($q['status'])) {
        $where[] = 'status = ?';
        $bind[] = $q['status'];
    }
    if (!empty($q['jenis_surat'])) {
        $where[] = 'jenis_surat = ?';
        $bind[] = $q['jenis_surat'];
    }
    $sql = 'SELECT * FROM surat_keluar WHERE ' . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($bind);
    return $st->fetchAll();
}

function surat_keluar_count(array $q): int
{
    $where = ['1=1'];
    $bind = [];
    if (!empty($q['q'])) {
        $where[] = '(nomor_surat LIKE ? OR perihal LIKE ? OR tujuan LIKE ?)';
        $like = '%' . $q['q'] . '%';
        array_push($bind, $like, $like, $like);
    }
    if (!empty($q['status'])) {
        $where[] = 'status = ?';
        $bind[] = $q['status'];
    }
    $st = db()->prepare('SELECT COUNT(*) FROM surat_keluar WHERE ' . implode(' AND ', $where));
    $st->execute($bind);
    return (int) $st->fetchColumn();
}

function surat_keluar_find(int $id): ?array
{
    $st = db()->prepare('SELECT s.*, u.name AS pembuat FROM surat_keluar s LEFT JOIN users u ON u.id = s.created_by WHERE s.id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function surat_keluar_update(int $id, array $data): void
{
    $st = db()->prepare('UPDATE surat_keluar SET nomor_surat=?, tanggal_surat=?, tujuan=?, jenis_surat=?, perihal=?, file_path=?, status=? WHERE id=?');
    $st->execute([
        $data['nomor_surat'],
        $data['tanggal_surat'],
        $data['tujuan'],
        $data['jenis_surat'],
        $data['perihal'],
        $data['file_path'],
        $data['status'],
        $id,
    ]);
}

function pemeriksaan_create(array $data): void
{
    $st = db()->prepare('INSERT INTO surat_keluar_pemeriksaan (surat_keluar_id, pemeriksa_id, aksi, catatan) VALUES (?,?,?,?)');
    $st->execute([$data['surat_keluar_id'], $data['pemeriksa_id'], $data['aksi'], $data['catatan']]);
    $status = match ($data['aksi']) {
        'setuju' => 'disetujui',
        'revisi' => 'perlu_revisi',
        default => 'draft',
    };
    db()->prepare('UPDATE surat_keluar SET status=?, catatan_pemeriksaan=?, pemeriksa_id=? WHERE id=?')
        ->execute([$status, $data['catatan'], $data['pemeriksa_id'], $data['surat_keluar_id']]);
}

function pemeriksaan_by_surat(int $id): array
{
    $st = db()->prepare('SELECT p.*, u.name AS pemeriksa FROM surat_keluar_pemeriksaan p JOIN users u ON u.id = p.pemeriksa_id WHERE p.surat_keluar_id = ? ORDER BY p.created_at DESC');
    $st->execute([$id]);
    return $st->fetchAll();
}

function user_create(array $data, int $requestedBy): int
{
    $pdo = db();

    $st = $pdo->prepare(
        'INSERT INTO users
        (name, username, password, role, unit, is_active, approval_status)
        VALUES (?, ?, ?, ?, ?, 1, "pending")'
    );

    $st->execute([
        trim($data['name']),
        trim($data['username']),
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['role'],
        $data['unit'],
    ]);

    $userId = (int) $pdo->lastInsertId();

    $st = $pdo->prepare(
        'INSERT INTO user_approval
        (user_id, requested_by, status, created_at)
        VALUES (?, ?, "pending", NOW())'
    );

    $st->execute([
        $userId,
        $requestedBy,
    ]);

    return $userId;
}

function user_approvals_pending(): array
{
    return db()->query(
        'SELECT
            ua.id AS approval_id,
            ua.user_id,
            ua.requested_by,
            ua.created_at,
            u.name,
            u.username,
            u.role,
            u.unit
         FROM user_approval ua
         INNER JOIN users u ON u.id = ua.user_id
         WHERE ua.status = "pending"
         ORDER BY ua.created_at ASC'
    )->fetchAll();
}


function user_approve(int $approvalId, int $approvedBy): void
{
    $pdo = db();

    $pdo->beginTransaction();

    try {
        $st = $pdo->prepare(
            'SELECT user_id
             FROM user_approval
             WHERE id = ? AND status = "pending"
             FOR UPDATE'
        );
        $st->execute([$approvalId]);

        $approval = $st->fetch();

        if (!$approval) {
            throw new RuntimeException('Permintaan pengguna tidak ditemukan.');
        }

        $userId = (int) $approval['user_id'];

        $st = $pdo->prepare(
            'UPDATE users
             SET approval_status = "approved",
                 approved_by = ?,
                 approved_at = NOW(),
                 is_active = 1
             WHERE id = ?'
        );
        $st->execute([$approvedBy, $userId]);

        $st = $pdo->prepare(
            'UPDATE user_approval
             SET status = "approved",
                 approved_by = ?,
                 approved_at = NOW()
             WHERE id = ?'
        );
        $st->execute([$approvedBy, $approvalId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function user_reject(int $approvalId, int $approvedBy, string $catatan = ''): void
{
    $pdo = db();

    $pdo->beginTransaction();

    try {
        $st = $pdo->prepare(
            'SELECT user_id
             FROM user_approval
             WHERE id = ? AND status = "pending"
             FOR UPDATE'
        );
        $st->execute([$approvalId]);

        $approval = $st->fetch();

        if (!$approval) {
            throw new RuntimeException('Permintaan pengguna tidak ditemukan.');
        }

        $userId = (int) $approval['user_id'];

        $st = $pdo->prepare(
            'UPDATE users
             SET approval_status = "rejected",
                 is_active = 0
             WHERE id = ?'
        );
        $st->execute([$userId]);

        $st = $pdo->prepare(
            'UPDATE user_approval
             SET status = "rejected",
                 approved_by = ?,
                 catatan = ?,
                 approved_at = NOW()
             WHERE id = ?'
        );
        $st->execute([
            $approvedBy,
            $catatan,
            $approvalId
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}