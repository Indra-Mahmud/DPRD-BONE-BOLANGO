<?php
ob_start();
$c = $counts;
?>
<div class="grid stats">
  <div class="card stat"><div class="k">Surat masuk</div><div class="n"><?= (int)$c['masuk'] ?></div><div class="s">Seluruh arsip digital</div></div>
  <div class="card stat"><div class="k">Menunggu disposisi</div><div class="n"><?= (int)$c['menunggu'] ?></div><div class="s">Perlu Pimpinan / Sekwan</div></div>
  <div class="card stat"><div class="k">Sedang diproses</div><div class="n"><?= (int)$c['proses'] ?></div><div class="s">Disposisi & tindak lanjut</div></div>
  <div class="card stat"><div class="k">Selesai</div><div class="n"><?= (int)$c['selesai'] ?></div><div class="s">Siap diarsipkan</div></div>
</div>

<div class="grid split" style="margin-top:16px">
  <div class="card panel">
    <div class="toolbar">
      <h2>Surat terbaru</h2>
      <a class="btn btn-primary btn-sm" href="<?= e(url('surat-masuk')) ?>">Lihat semua</a>
    </div>
    <table>
      <thead><tr><th>Agenda</th><th>Pengirim</th><th>Perihal</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><a href="<?= e(url('surat-masuk/detail', ['id' => $r['id']])) ?>"><?= e($r['nomor_agenda']) ?></a><div class="muted"><?= e($r['jenis_surat']) ?></div></td>
          <td><?= e($r['pengirim']) ?></td>
          <td><?= e($r['perihal']) ?></td>
          <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="4" class="empty">Belum ada surat.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card panel">
    <h2>Alur SIMSURAT</h2>
    <div class="timeline">
      <div class="t-item"><strong>Penerimaan</strong><div class="muted">Sekretariat menerima surat fisik/digital, memindai, dan mencatat.</div></div>
      <div class="t-item"><strong>Disposisi</strong><div class="muted">Ketua, Wakil Ketua, atau Sekwan menentukan tujuan dan instruksi.</div></div>
      <div class="t-item"><strong>Tindak lanjut</strong><div class="muted">Komisi, Kabag, Kasubag, atau PIC mengerjakan dan mengunggah bukti.</div></div>
      <div class="t-item"><strong>Arsip</strong><div class="muted">Riwayat lengkap tersimpan dan dapat dicari kembali.</div></div>
    </div>
    <p class="muted">Surat keluar: <?= (int)$c['keluar'] ?> berkas · menunggu pemeriksaan <?= (int)$c['keluar_tunggu'] ?></p>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
