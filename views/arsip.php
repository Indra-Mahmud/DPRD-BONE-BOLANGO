<?php $q = $q ?? []; ob_start(); ?>
<div class="card panel">
  <h2>Arsip digital</h2>
  <p class="muted">Cari berdasarkan jenis, perihal, pengirim, tujuan, tanggal, tahun, unit disposisi, dan status. Jenis surat dan perihal dipisah agar pencarian lebih tepat.</p>
  <form method="get" class="filters" style="grid-template-columns: repeat(4,1fr);">
    <input type="hidden" name="r" value="arsip">
    <input name="q" placeholder="Nomor / perihal / pengirim" value="<?= e($q['q'] ?? '') ?>">
    <select name="jenis_surat">
      <option value="">Jenis surat</option>
      <?php foreach (jenis_surat() as $j): ?>
        <option <?= (($q['jenis_surat'] ?? '') === $j) ? 'selected' : '' ?>><?= e($j) ?></option>
      <?php endforeach; ?>
    </select>
    <input name="pengirim" placeholder="Pengirim" value="<?= e($q['pengirim'] ?? '') ?>">
    <input name="tujuan" placeholder="Tujuan" value="<?= e($q['tujuan'] ?? '') ?>">
    <select name="tahun">
      <option value="">Tahun</option>
      <?php for ($y = (int)date('Y'); $y >= 2022; $y--): ?>
        <option <?= ((string)($q['tahun'] ?? '') === (string)$y) ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <input type="date" name="tanggal_dari" value="<?= e($q['tanggal_dari'] ?? '') ?>">
    <input type="date" name="tanggal_sampai" value="<?= e($q['tanggal_sampai'] ?? '') ?>">
    <select name="unit_disposisi">
      <option value="">Komisi / bagian disposisi</option>
      <?php foreach (units() as $u): ?>
        <option <?= (($q['unit_disposisi'] ?? '') === $u) ? 'selected' : '' ?>><?= e($u) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">Status</option>
      <?php foreach (['menunggu_disposisi','belum_ditindaklanjuti','sedang_diproses','selesai'] as $s): ?>
        <option value="<?= e($s) ?>" <?= (($q['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Cari arsip</button>
  </form>
  <table>
    <thead><tr><th>Agenda</th><th>Nomor</th><th>Jenis</th><th>Perihal</th><th>Pengirim</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('surat-masuk/detail', ['id' => $r['id']])) ?>"><?= e($r['nomor_agenda']) ?></a></td>
        <td><?= e($r['nomor_surat']) ?><div class="muted"><?= e(format_date($r['tanggal_surat'])) ?></div></td>
        <td><?= e($r['jenis_surat']) ?></td>
        <td><?= e($r['perihal']) ?></td>
        <td><?= e($r['pengirim']) ?></td>
        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">Arsip tidak ditemukan.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
