<?php ob_start(); ?>
<div class="card panel">
  <h2>Pencatatan surat masuk</h2>
  <p class="muted">Isikan data terstruktur. Jika surat fisik, unggah hasil pindaian PDF. Surat fisik tetap disimpan sesuai prosedur kearsipan kantor.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid" action="<?= e(url('surat-masuk/baru')) ?>">
    <?= csrf_field() ?>
    <div>
      <label>Nomor surat</label>
      <input name="nomor_surat" required value="<?= old('nomor_surat') ?>">
    </div>
    <div>
      <label>Bentuk penerimaan</label>
      <select name="bentuk_asal">
        <option value="fisik">Fisik (dipindai)</option>
        <option value="digital">Digital (unggah langsung)</option>
      </select>
    </div>
    <div>
      <label>Tanggal surat</label>
      <input type="date" name="tanggal_surat" required value="<?= old('tanggal_surat', date('Y-m-d')) ?>">
    </div>
    <div>
      <label>Tanggal diterima</label>
      <input type="date" name="tanggal_diterima" required value="<?= old('tanggal_diterima', date('Y-m-d')) ?>">
    </div>
    <div>
      <label>Pengirim</label>
      <input name="pengirim" required value="<?= old('pengirim') ?>">
    </div>
    <div>
      <label>Jenis surat</label>
      <select name="jenis_surat" required>
        <?php foreach (jenis_surat() as $j): ?>
          <option><?= e($j) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="span-2">
      <label>Perihal</label>
      <input name="perihal" required placeholder="Contoh: Undangan Rapat Pembahasan APBD">
    </div>
    <div>
      <label>Tujuan</label>
      <input name="tujuan" placeholder="Contoh: Ketua DPRD">
    </div>
    <div>
      <label>Berkas surat (PDF/JPG)</label>
      <input type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" data-file-name>
      <div class="help" data-file-label>Hasil pindaian atau file digital.</div>
    </div>
    <div class="span-2">
      <button class="btn btn-primary" type="submit">Simpan & menunggu disposisi</button>
      <a class="btn btn-ghost" href="<?= e(url('surat-masuk')) ?>">Batal</a>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
