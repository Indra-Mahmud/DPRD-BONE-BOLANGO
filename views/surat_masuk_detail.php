<?php ob_start(); $s = $surat; ?>
<div class="toolbar">
  <div>
    <div class="muted"><?= e($s['nomor_agenda']) ?></div>
    <h2 style="font-family:var(--serif);margin:4px 0 0"><?= e($s['perihal']) ?></h2>
  </div>
  <span class="badge <?= e(status_class($s['status'])) ?>"><?= e(status_label($s['status'])) ?></span>
</div>

<div class="grid split">
  <div class="card panel">
    <h3>Data surat</h3>
    <table>
      <tr><th>Nomor surat</th><td><?= e($s['nomor_surat']) ?></td></tr>
      <tr><th>Jenis</th><td><?= e($s['jenis_surat']) ?></td></tr>
      <tr><th>Pengirim</th><td><?= e($s['pengirim']) ?></td></tr>
      <tr><th>Tujuan</th><td><?= e($s['tujuan'] ?: '—') ?></td></tr>
      <tr><th>Tanggal surat</th><td><?= e(format_date($s['tanggal_surat'])) ?></td></tr>
      <tr><th>Diterima</th><td><?= e(format_date($s['tanggal_diterima'])) ?> (<?= e($s['bentuk_asal']) ?>)</td></tr>
      <tr><th>Pencatat</th><td><?= e($s['pencatat'] ?: '—') ?></td></tr>
    </table>

    <h3 style="margin-top:22px">Riwayat disposisi</h3>
    <?php if (!$disposisi): ?><p class="muted">Belum ada disposisi. Status saat ini: menunggu Pimpinan DPRD atau Sekwan.</p><?php endif; ?>
    <?php foreach ($disposisi as $d): ?>
      <div class="card panel" style="box-shadow:none;margin-bottom:10px">
        <strong><?= e($d['bertindak_sebagai']) ?></strong>
        <div class="muted"><?= e($d['pemberi_nama']) ?> · <?= e($d['jabatan_pemberi']) ?> · <?= e(format_datetime($d['created_at'])) ?></div>
        <p>Kepada: <strong><?= e($d['tujuan_unit']) ?></strong><?= $d['tujuan_nama'] ? ' (' . e($d['tujuan_nama']) . ')' : '' ?></p>
        <p>Instruksi: <?= e($d['instruksi'] ?: '—') ?></p>
        <p class="muted">Penanggung jawab: <?= e($d['penanggung_jawab_nama'] ?: '—') ?> · Batas waktu: <?= e(format_date($d['batas_waktu'])) ?></p>
      </div>
    <?php endforeach; ?>

    <?php if (can_disposisi()): ?>
      <h3>Berikan disposisi</h3>
      <form method="post" action="<?= e(url('surat-masuk/disposisi')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="surat_id" value="<?= (int)$s['id'] ?>">
        <div>
          <label>Bertindak sebagai</label>
          <select name="bertindak_sebagai">
            <?php foreach (acting_as_options(current_user()) as $o): ?>
              <option><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Tujuan disposisi</label>
          <select name="tujuan_unit" required>
            <?php foreach (units() as $u): ?><option><?= e($u) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Penanggung jawab (pengguna)</label>
          <select name="penanggung_jawab_id">
            <option value="">— pilih bila sudah ditentukan —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> · <?= e($u['unit']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Batas waktu</label>
          <input type="date" name="batas_waktu">
        </div>
        <div class="span-2">
          <label>Instruksi</label>
          <textarea name="instruksi" placeholder="Contoh: Hadiri rapat dan tindak lanjuti hasil rapat."></textarea>
        </div>
        <div class="span-2"><button class="btn btn-primary" type="submit">Simpan disposisi</button></div>
      </form>
    <?php endif; ?>

    <?php if (can('ketua_komisi','kabag') && $disposisi): ?>
      <h3>Tetapkan penanggung jawab tindak lanjut</h3>
      <p class="muted">Ketua komisi atau Kabag dapat meneruskan surat kepada anggota atau staf tertentu.</p>
      <form method="post" action="<?= e(url('surat-masuk/teruskan')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="surat_id" value="<?= (int)$s['id'] ?>">
        <div>
          <label>Kepada unit</label>
          <select name="tujuan_unit">
            <?php foreach (units() as $u): ?><option <?= $u === current_user()['unit'] ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Penanggung jawab</label>
          <select name="penanggung_jawab_id" required>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> · <?= e($u['unit']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="span-2">
          <label>Instruksi</label>
          <textarea name="instruksi" required></textarea>
        </div>
        <div>
          <label>Batas waktu</label>
          <input type="date" name="batas_waktu">
        </div>
        <div class="span-2"><button class="btn btn-ghost" type="submit">Teruskan</button></div>
      </form>
    <?php endif; ?>

    <?php if (can('ketua_komisi','anggota','kabag','kasubag','sekwan','admin')): ?>
      <h3 style="margin-top:22px">Tindak lanjut</h3>
      <form method="post" enctype="multipart/form-data" action="<?= e(url('surat-masuk/tindak-lanjut')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="surat_id" value="<?= (int)$s['id'] ?>">
        <div>
          <label>Status</label>
          <select name="status">
            <option value="sedang_diproses">Sedang diproses</option>
            <option value="selesai">Selesai</option>
          </select>
        </div>
        <div>
          <label>Bukti / dokumen</label>
          <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div class="span-2">
          <label>Hasil tindak lanjut</label>
          <textarea name="hasil" required></textarea>
        </div>
        <div class="span-2"><button class="btn btn-gold" type="submit">Catat tindak lanjut</button></div>
      </form>
    <?php endif; ?>

    <?php if ($tindak): ?>
      <h3>Catatan hasil</h3>
      <?php foreach ($tindak as $t): ?>
        <p><strong><?= e($t['petugas']) ?></strong> · <?= e(status_label($t['status'])) ?><br>
        <span class="muted"><?= e(format_datetime($t['created_at'])) ?></span><br><?= e($t['hasil']) ?>
        <?php if ($t['file_bukti']): ?> · <a href="<?= e(file_url($t['file_bukti'])) ?>" target="_blank">Bukti</a><?php endif; ?>
        </p>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div>
    <div class="card panel file-preview">
      <h3>Berkas surat</h3>
      <?php if ($s['file_path']): ?>
        <p><a class="btn btn-primary btn-sm" href="<?= e(file_url($s['file_path'])) ?>" target="_blank">Buka berkas</a></p>
        <?php if (str_ends_with(strtolower($s['file_path']), '.pdf')): ?>
          <iframe src="<?= e(file_url($s['file_path'])) ?>" style="width:100%;height:360px;border:0;border-radius:10px"></iframe>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted">Belum ada file pindaian. Surat fisik tetap disimpan di arsip kantor.</p>
      <?php endif; ?>
    </div>
    <div class="card panel" style="margin-top:16px">
      <h3>Jejak proses</h3>
      <div class="timeline">
        <?php foreach ($aktivitas as $a): ?>
          <div class="t-item">
            <strong><?= e(ucfirst(str_replace('_',' ',$a['jenis']))) ?></strong>
            <div class="muted"><?= e($a['petugas'] ?: 'Sistem') ?> · <?= e(format_datetime($a['created_at'])) ?></div>
            <div><?= e($a['keterangan']) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$aktivitas): ?><p class="muted">Belum ada jejak.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';
