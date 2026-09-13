# SIMSURAT DPRD — Sistem Informasi Manajemen Surat
# DPRD Kabupaten Bone Bolango

Aplikasi PHP + MySQL untuk Laragon.

## Instalasi

1. Pastikan Laragon (Apache + MySQL) berjalan.
2. Buka `http://sims_tes.test/install.php` atau `http://localhost/sims_tes/install.php`.
3. Klik **Pasang sekarang**.
4. Masuk dengan akun contoh.

Konfigurasi database ada di `config/database.php` (default Laragon: user `root`, kata sandi kosong).

## Akun contoh

| Peran | Username | Kata sandi |
| --- | --- | --- |
| Administrator | admin | admin123 |
| Petugas Sekretariat | sekretariat | sekre123 |
| Ketua DPRD | ketua | ketua123 |
| Wakil Ketua I / II | wakil1 / wakil2 | wakil123 |
| Sekwan | sekwan | sekwan123 |
| Ketua Komisi I–III | komisi1 / komisi2 / komisi3 | komisi123 |
| Kabag / Kasubag | kabag / kasubag | kabag123 / kasubag123 |
| Anggota Komisi I | anggota1 | anggota123 |

## Alur

1. Sekretariat mencatat surat masuk (fisik dipindai atau digital diunggah). Status: menunggu disposisi.
2. Ketua, Wakil Ketua, atau Sekwan memberi disposisi (unit, instruksi, PIC, batas waktu). Wakil Ketua dapat bertindak sebagai Ketua.
3. Komisi/bagian menerima di kotak disposisi, menetapkan PIC bila perlu, lalu mencatat tindak lanjut.
4. Surat keluar dapat diajukan pemeriksaan ke Pimpinan/Sekwan sebelum dikirim dan diarsipkan.
5. Arsip dicari menurut jenis, perihal, pengirim, tujuan, tanggal, tahun, unit disposisi, dan status.
