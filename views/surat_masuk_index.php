<?php
$q = $q ?? [];
ob_start();
?>
<div class="card panel">
  <div class="toolbar">
    <h2>Daftar surat masuk</h2>
    <?php if (can_register()): ?>
      <a class="btn btn-gold" href="<?= e(url('surat-masuk/baru')) ?>">Catat surat baru</a>
    <?php endif; ?>
  </div>
  <form class="filters" method="get">
    <input type="hidden" name="r" value="surat-masuk">
    <input name="q" value="<?= e($q['q'] ?? '') ?>" placeholder="Cari nomor, perihal, pengirim">
    <select name="jenis_surat">
      <option value="">Semua jenis</option>
      <?php foreach (jenis_surat() as $j): ?>
        <option <?= (($q['jenis_surat'] ?? '') === $j) ? 'selected' : '' ?>><?= e($j) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">Semua status</option>
      <?php foreach (['menunggu_disposisi','belum_ditindaklanjuti','sedang_diproses','selesai'] as $s): ?>
        <option value="<?= e($s) ?>" <?= (($q['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Saring</button>
  </form>
  <table>
    <thead><tr><th>Agenda</th><th>Nomor / tanggal</th><th>Pengirim</th><th>Jenis & perihal</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('surat-masuk/detail', ['id' => $r['id']])) ?>"><?= e($r['nomor_agenda']) ?></a></td>
        <td><?= e($r['nomor_surat']) ?><div class="muted"><?= e(format_date($r['tanggal_surat'])) ?> · diterima <?= e(format_date($r['tanggal_diterima'])) ?></div></td>
        <td><?= e($r['pengirim']) ?></td>
        <td><strong><?= e($r['jenis_surat']) ?></strong><div class="muted"><?= e($r['perihal']) ?></div></td>
        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada data.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
