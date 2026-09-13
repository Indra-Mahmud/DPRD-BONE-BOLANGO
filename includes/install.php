<?php

function install_schema(PDO $pdo): void
{
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . db_config()['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . db_config()['name'] . '`');

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        jabatan VARCHAR(150) NOT NULL,
        unit VARCHAR(100) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS surat_masuk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nomor_agenda VARCHAR(50) NOT NULL,
        nomor_surat VARCHAR(120) NOT NULL,
        tanggal_surat DATE NOT NULL,
        tanggal_diterima DATE NOT NULL,
        pengirim VARCHAR(200) NOT NULL,
        jenis_surat VARCHAR(100) NOT NULL,
        perihal TEXT NOT NULL,
        tujuan VARCHAR(200) NULL,
        file_path VARCHAR(255) NULL,
        bentuk_asal ENUM("fisik","digital") NOT NULL DEFAULT "fisik",
        status VARCHAR(50) NOT NULL DEFAULT "menunggu_disposisi",
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_jenis (jenis_surat),
        INDEX idx_tgl (tanggal_surat)
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS disposisi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        surat_id INT NOT NULL,
        parent_id INT NULL,
        pemberi_id INT NOT NULL,
        jabatan_pemberi VARCHAR(150) NOT NULL,
        bertindak_sebagai VARCHAR(150) NOT NULL,
        tujuan_unit VARCHAR(100) NOT NULL,
        tujuan_user_id INT NULL,
        instruksi TEXT NULL,
        penanggung_jawab_id INT NULL,
        penanggung_jawab_nama VARCHAR(150) NULL,
        batas_waktu DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_disp_surat FOREIGN KEY (surat_id) REFERENCES surat_masuk(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS tindak_lanjut (
        id INT AUTO_INCREMENT PRIMARY KEY,
        surat_id INT NOT NULL,
        disposisi_id INT NULL,
        user_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        hasil TEXT NULL,
        file_bukti VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_tl_surat FOREIGN KEY (surat_id) REFERENCES surat_masuk(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS aktivitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        surat_id INT NOT NULL,
        jenis VARCHAR(50) NOT NULL,
        keterangan TEXT NOT NULL,
        user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_akt_surat FOREIGN KEY (surat_id) REFERENCES surat_masuk(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS surat_keluar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nomor_surat VARCHAR(120) NULL,
        tanggal_surat DATE NULL,
        tujuan VARCHAR(200) NOT NULL,
        jenis_surat VARCHAR(100) NOT NULL,
        perihal TEXT NOT NULL,
        file_path VARCHAR(255) NULL,
        status VARCHAR(50) NOT NULL DEFAULT "draft",
        catatan_pemeriksaan TEXT NULL,
        pemeriksa_id INT NULL,
        dikirim_at DATETIME NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');

    $pdo->exec('CREATE TABLE IF NOT EXISTS surat_keluar_pemeriksaan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        surat_keluar_id INT NOT NULL,
        pemeriksa_id INT NOT NULL,
        aksi VARCHAR(30) NOT NULL,
        catatan TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_periksa_sk FOREIGN KEY (surat_keluar_id) REFERENCES surat_keluar(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
}

function seed_users(PDO $pdo): void
{
    $users = [
        ['Administrator Sistem', 'admin', 'admin123', 'admin', 'Administrator', 'Sekretariat DPRD'],
        ['Petugas Sekretariat', 'sekretariat', 'sekre123', 'sekretariat', 'Petugas Persuratan', 'Sekretariat DPRD'],
        ['H. Ahmad Ridwan, S.H.', 'ketua', 'ketua123', 'ketua', 'Ketua DPRD', 'Pimpinan DPRD'],
        ['Ir. Sitti Rahma', 'wakil1', 'wakil123', 'wakil_ketua', 'Wakil Ketua I', 'Pimpinan DPRD'],
        ['Drs. Hasanudin Moko', 'wakil2', 'wakil123', 'wakil_ketua', 'Wakil Ketua II', 'Pimpinan DPRD'],
        ['Drs. Yusuf Podungge, M.Si.', 'sekwan', 'sekwan123', 'sekwan', 'Sekretaris DPRD', 'Sekretariat DPRD'],
        ['Ketua Komisi I', 'komisi1', 'komisi123', 'ketua_komisi', 'Ketua Komisi I', 'Komisi I'],
        ['Anggota Komisi I', 'anggota1', 'anggota123', 'anggota', 'Anggota Komisi I', 'Komisi I'],
        ['Ketua Komisi II', 'komisi2', 'komisi123', 'ketua_komisi', 'Ketua Komisi II', 'Komisi II'],
        ['Ketua Komisi III', 'komisi3', 'komisi123', 'ketua_komisi', 'Ketua Komisi III', 'Komisi III'],
        ['Kepala Bagian Umum', 'kabag', 'kabag123', 'kabag', 'Kabag Umum', 'Kabag Umum'],
        ['Kepala Subbagian Tata Usaha', 'kasubag', 'kasubag123', 'kasubag', 'Kasubag Tata Usaha', 'Kasubag Tata Usaha'],
    ];
    $st = $pdo->prepare('INSERT INTO users (name, username, password, role, jabatan, unit) VALUES (?,?,?,?,?,?)');
    foreach ($users as $u) {
        $st->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3], $u[4], $u[5]]);
    }
}

function seed_demo(PDO $pdo): void
{
    $sekre = (int) $pdo->query("SELECT id FROM users WHERE username='sekretariat'")->fetchColumn();
    $ketua = (int) $pdo->query("SELECT id FROM users WHERE username='ketua'")->fetchColumn();
    $komisi2 = (int) $pdo->query("SELECT id FROM users WHERE username='komisi2'")->fetchColumn();
    $kabag = (int) $pdo->query("SELECT id FROM users WHERE username='kabag'")->fetchColumn();

    $surat = [
        ['SM/2026/0001', '005/DISDIK/BB/VIII/2026', '2026-08-18', '2026-08-20', 'Dinas Pendidikan Kabupaten Bone Bolango', 'Surat Undangan', 'Undangan Rapat Pembahasan APBD Bidang Pendidikan', 'Ketua DPRD Kabupaten Bone Bolango', 'digital', 'belum_ditindaklanjuti'],
        ['SM/2026/0002', '12/DINKES/BB/VIII/2026', '2026-08-19', '2026-08-21', 'Dinas Kesehatan Kabupaten Bone Bolango', 'Surat Permohonan', 'Permohonan Dukungan Program Stunting', 'Pimpinan DPRD', 'fisik', 'sedang_diproses'],
        ['SM/2026/0003', '088/SETDA/BB/VIII/2026', '2026-08-22', '2026-08-22', 'Sekretariat Daerah Kabupaten Bone Bolango', 'Surat Pemberitahuan', 'Pemberitahuan Jadwal Rapat Paripurna', 'Sekretaris DPRD', 'digital', 'menunggu_disposisi'],
        ['SM/2026/0004', '21/KOMINFO/BB/VII/2026', '2026-07-30', '2026-08-01', 'Diskominfo Bone Bolango', 'Laporan', 'Laporan Pelaksanaan Website Resmi Pemerintah Daerah', 'Komisi I DPRD', 'fisik', 'selesai'],
        ['SM/2026/0005', '004/BAPPEDA/BB/VIII/2026', '2026-08-15', '2026-08-16', 'Bappeda Bone Bolango', 'Nota Dinas', 'Permintaan Masukan RKPD Tahun 2027', 'Pimpinan DPRD', 'digital', 'menunggu_disposisi'],
    ];
    $ins = $pdo->prepare('INSERT INTO surat_masuk (nomor_agenda, nomor_surat, tanggal_surat, tanggal_diterima, pengirim, jenis_surat, perihal, tujuan, bentuk_asal, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($surat as $s) {
        $ins->execute([...$s, $sekre]);
    }

    $id1 = (int) $pdo->query("SELECT id FROM surat_masuk WHERE nomor_agenda='SM/2026/0001'")->fetchColumn();
    $id2 = (int) $pdo->query("SELECT id FROM surat_masuk WHERE nomor_agenda='SM/2026/0002'")->fetchColumn();
    $id4 = (int) $pdo->query("SELECT id FROM surat_masuk WHERE nomor_agenda='SM/2026/0004'")->fetchColumn();

    $d = $pdo->prepare('INSERT INTO disposisi (surat_id, pemberi_id, jabatan_pemberi, bertindak_sebagai, tujuan_unit, tujuan_user_id, instruksi, penanggung_jawab_id, penanggung_jawab_nama, batas_waktu) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $d->execute([$id1, $ketua, 'Ketua DPRD', 'Ketua DPRD', 'Komisi II', $komisi2, 'Hadiri rapat dan tindak lanjuti hasil rapat pembahasan APBD bidang pendidikan. Laporkan hasil kepada Pimpinan.', $komisi2, 'Ketua Komisi II', '2026-09-05']);
    $d->execute([$id2, $ketua, 'Ketua DPRD', 'Ketua DPRD', 'Komisi II', $komisi2, 'Pelajari permohonan dan berikan rekomendasi kepada Pimpinan.', $komisi2, 'Ketua Komisi II', '2026-09-10']);
    $d->execute([$id4, $ketua, 'Ketua DPRD', 'Ketua DPRD', 'Kabag Umum', $kabag, 'Arsipkan dan siapkan bahan tanggapan bila diperlukan.', $kabag, 'Kabag Umum', '2026-08-10']);

    $pdo->prepare('INSERT INTO tindak_lanjut (surat_id, user_id, status, hasil) VALUES (?,?,?,?)')
        ->execute([$id2, $komisi2, 'sedang_diproses', 'Komisi II sedang menyusun jadwal dengar pendapat dengan Dinas Kesehatan.']);
    $pdo->prepare('INSERT INTO tindak_lanjut (surat_id, user_id, status, hasil) VALUES (?,?,?,?)')
        ->execute([$id4, $kabag, 'selesai', 'Laporan telah diarsipkan dan ringkasan disampaikan kepada Pimpinan.']);

    $pdo->prepare('INSERT INTO aktivitas (surat_id, jenis, keterangan, user_id) VALUES (?,?,?,?)')->execute([$id1, 'pencatatan', 'Surat diterima secara digital dan dicatat.', $sekre]);
    $pdo->prepare('INSERT INTO aktivitas (surat_id, jenis, keterangan, user_id) VALUES (?,?,?,?)')->execute([$id1, 'disposisi', 'Disposisi oleh Ketua DPRD kepada Komisi II.', $ketua]);

    $pdo->prepare('INSERT INTO surat_keluar (nomor_surat, tanggal_surat, tujuan, jenis_surat, perihal, status, created_by) VALUES (?,?,?,?,?,?,?)')
        ->execute(['090/DPRD-BB/VIII/2026', '2026-08-25', 'Bupati Bone Bolango', 'Surat Undangan', 'Undangan Rapat Konsultasi APBD Perubahan', 'dikirim', $sekre]);
    $pdo->prepare('INSERT INTO surat_keluar (nomor_surat, tanggal_surat, tujuan, jenis_surat, perihal, status, created_by) VALUES (?,?,?,?,?,?,?)')
        ->execute([null, '2026-08-27', 'Dinas PUPR Bone Bolango', 'Surat Permohonan', 'Permohonan Data Proyek Infrastruktur 2026', 'menunggu_pemeriksaan', $sekre]);
}

function write_minimal_pdf(string $path, string $title): void
{
    $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);
    $stream = "BT /F1 14 Tf 50 750 Td ($text) Tj ET";
    $len = strlen($stream);
    $pdf = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n4 0 obj<< /Length $len >>stream\n$stream\nendstream endobj\n5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\nxref\n0 6\n0000000000 65535 f \ntrailer<< /Size 6 /Root 1 0 R >>\nstartxref\n0\n%%EOF";
    file_put_contents($path, $pdf);
}
