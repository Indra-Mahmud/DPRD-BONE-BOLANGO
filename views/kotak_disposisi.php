<?php ob_start(); ?>
<div class="card panel">
  <h2>Kotak disposisi</h2>
  <p class="muted">Surat yang diteruskan kepada unit atau Anda sebagai penanggung jawab.</p>
  <table>
    <thead><tr><th>Surat</th><th>Dari</th><th>Instruksi</th><th>Batas</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('surat-masuk/detail', ['id' => $r['id']])) ?>"><?= e($r['nomor_agenda']) ?></a>
          <div class="muted"><?= e($r['pengirim']) ?><br><?= e($r['perihal']) ?></div></td>
        <td><?= e($r['bertindak_sebagai']) ?></td>
        <td><?= e($r['instruksi']) ?></td>
        <td><?= e(format_date($r['batas_waktu'])) ?></td>
        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Belum ada disposisi untuk unit Anda.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
