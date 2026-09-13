<?php ob_start(); ?>
<div class="card panel">
  <h2>Catat surat keluar</h2>
  <form method="post" enctype="multipart/form-data" class="form-grid" action="<?= e(url('surat-keluar/baru')) ?>">
    <?= csrf_field() ?>
    <div>
      <label>Nomor surat</label>
      <input name="nomor_surat" placeholder="Dapat diisi setelah disetujui">
    </div>
    <div>
      <label>Tanggal</label>
      <input type="date" name="tanggal_surat" value="<?= e(date('Y-m-d')) ?>">
    </div>
    <div>
      <label>Tujuan</label>
      <input name="tujuan" required>
    </div>
    <div>
      <label>Jenis surat</label>
      <select name="jenis_surat">
        <?php foreach (jenis_surat() as $j): ?><option><?= e($j) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="span-2">
      <label>Perihal</label>
      <input name="perihal" required>
    </div>
    <div>
      <label>Berkas</label>
      <input type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png">
    </div>
    <div>
      <label>Status awal</label>
      <select name="status">
        <option value="draft">Draf</option>
        <option value="menunggu_pemeriksaan">Ajukan pemeriksaan</option>
      </select>
    </div>
    <div class="span-2">
      <button class="btn btn-primary" type="submit">Simpan</button>
      <a class="btn btn-ghost" href="<?= e(url('surat-keluar')) ?>">Batal</a>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
