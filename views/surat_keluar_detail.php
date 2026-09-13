<?php ob_start(); $s = $surat; ?>
<div class="toolbar">
  <h2><?= e($s['perihal']) ?></h2>
  <span class="badge <?= e(status_class($s['status'])) ?>"><?= e(status_label($s['status'])) ?></span>
</div>
<div class="grid split">
  <div class="card panel">
    <table>
      <tr><th>Nomor</th><td><?= e($s['nomor_surat'] ?: '—') ?></td></tr>
      <tr><th>Tanggal</th><td><?= e(format_date($s['tanggal_surat'])) ?></td></tr>
      <tr><th>Tujuan</th><td><?= e($s['tujuan']) ?></td></tr>
      <tr><th>Jenis</th><td><?= e($s['jenis_surat']) ?></td></tr>
      <tr><th>Pembuat</th><td><?= e($s['pembuat'] ?: '—') ?></td></tr>
    </table>
    <?php if ($s['file_path']): ?>
      <p style="margin-top:12px"><a class="btn btn-primary btn-sm" href="<?= e(file_url($s['file_path'])) ?>" target="_blank">Unduh berkas</a></p>
    <?php endif; ?>

    <?php if (is_pimpinan() && $s['status'] === 'menunggu_pemeriksaan'): ?>
      <h3>Pemeriksaan / persetujuan</h3>
      <form method="post" action="<?= e(url('surat-keluar/periksa')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
        <label>Catatan</label>
        <textarea name="catatan"></textarea>
        <div class="row" style="margin-top:10px">
          <button class="btn btn-primary" name="aksi" value="setuju" type="submit">Setujui</button>
          <button class="btn btn-ghost" name="aksi" value="revisi" type="submit">Minta revisi</button>
        </div>
      </form>
    <?php endif; ?>

    <?php if (can_register() && in_array($s['status'], ['disetujui','dikirim'], true)): ?>
      <form method="post" action="<?= e(url('surat-keluar/kirim')) ?>" style="margin-top:16px">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
        <button class="btn btn-gold" type="submit">Tandai terkirim & arsipkan</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card panel">
    <h3>Riwayat pemeriksaan</h3>
    <?php foreach ($pemeriksaan as $p): ?>
      <p><strong><?= e($p['pemeriksa']) ?></strong> · <?= e($p['aksi']) ?><br>
      <span class="muted"><?= e(format_datetime($p['created_at'])) ?></span><br><?= e($p['catatan'] ?: '—') ?></p>
    <?php endforeach; ?>
    <?php if (!$pemeriksaan): ?><p class="muted">Belum ada pemeriksaan.</p><?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
