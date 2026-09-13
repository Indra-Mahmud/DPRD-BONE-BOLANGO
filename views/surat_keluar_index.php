<?php $q = $q ?? []; ob_start(); ?>
<div class="card panel">
  <div class="toolbar">
    <h2>Surat keluar</h2>
    <?php if (can_register()): ?>
      <a class="btn btn-gold" href="<?= e(url('surat-keluar/baru')) ?>">Buat surat keluar</a>
    <?php endif; ?>
  </div>
  <form class="filters" method="get">
    <input type="hidden" name="r" value="surat-keluar">
    <input name="q" value="<?= e($q['q'] ?? '') ?>" placeholder="Nomor, perihal, tujuan">
    <select name="status">
      <option value="">Semua status</option>
      <?php foreach (['draft','menunggu_pemeriksaan','perlu_revisi','disetujui','dikirim','diarsipkan'] as $s): ?>
        <option value="<?= e($s) ?>" <?= (($q['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Saring</button>
  </form>
  <table>
    <thead><tr><th>Nomor</th><th>Tujuan</th><th>Jenis & perihal</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('surat-keluar/detail', ['id' => $r['id']])) ?>"><?= e($r['nomor_surat'] ?: 'Belum bernomor') ?></a>
          <div class="muted"><?= e(format_date($r['tanggal_surat'])) ?></div></td>
        <td><?= e($r['tujuan']) ?></td>
        <td><strong><?= e($r['jenis_surat']) ?></strong><div class="muted"><?= e($r['perihal']) ?></div></td>
        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Belum ada surat keluar.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
