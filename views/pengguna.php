<?php ob_start(); ?>

<div class="card panel">
    <div class="toolbar">
        <div>
            <h2>Pengguna Sistem</h2>
            <p class="muted">
                Penambahan pengguna harus menunggu persetujuan Sekretaris Dewan.
            </p>
        </div>
    </div>

<?php if (is_admin()): ?>
    <form method="post" action="<?= e(url('pengguna')) ?>" class="form-grid">
        <?= csrf_field() ?>

        <input type="hidden" name="action" value="create_user">

            <div>
                <label>Nama</label>
                <input name="name" required>
            </div>

            <div>
                <label>Username</label>
                <input name="username" required>
            </div>

            <div>
                <label>Kata sandi</label>
                <input name="password" type="password" required>
            </div>

            <div>
                <label>Peran</label>
                <select name="role" required>
                    <?php foreach (roles() as $value => $label): ?>
                        <option value="<?= e($value) ?>">
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="span-2">
                <label>Unit</label>
                <select name="unit" required>
                    <?php foreach (units() as $unit): ?>
                        <option value="<?= e($unit) ?>">
                            <?= e($unit) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="span-2">
                <button class="btn btn-primary" type="submit">
                    Ajukan Pengguna
                </button>
            </div>
        </form>
    <?php endif; ?>

    <?php if (is_admin() && !empty($pending)): ?>
        <h3 style="margin-top:30px">Menunggu Persetujuan Sekwan</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($pending as $r): ?>
                    <tr>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= e(role_label($r['role'])) ?></td>
                        <td><?= e($r['unit']) ?></td>
                        <td>
                            <span class="badge badge-wait">
                                Menunggu Persetujuan
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 style="margin-top:30px">Daftar Pengguna</h3>

        <?php if (is_admin() || can('sekretaris')): ?>
    <div class="card">
        <h2>Pengajuan Pengguna</h2>

        <?php if (empty($pending_users)): ?>
            <p>Belum ada pengajuan pengguna.</p>
        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>NAMA</th>
                            <th>USERNAME</th>
                            <th>PERAN</th>
                            <th>UNIT</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pending_users as $p): ?>
                            <tr>
                                <td><?= e($p['name']) ?></td>
                                <td><?= e($p['username']) ?></td>
                                <td><?= e(role_label($p['role'])) ?></td>
                                <td><?= e($p['unit']) ?></td>
                                <td>
                                    <?php if (can('sekretaris')): ?>

                                        <form method="post"
                                              action="index.php?r=pengguna"
                                              style="display:inline-block">
                                              
                                              <input type="hidden" name="action" value="approve_user">
                                              <input type="hidden" name="approval_id" value="<?= (int) $p['approval_id'] ?>">
                                              <?= csrf_field() ?>

                                            <button type="submit"
                                                    class="btn btn-primary">
                                                Setujui
                                            </button>
                                        </form>

                                        <form method="post"
                                              action="index.php?r=pengguna"
                                              style="display:inline-block">

                                            <input type="hidden"
                                                  name="action"
                                                  value="reject_user">
                                                  <input type="hidden" name="approval_id" value="<?= (int) $p['approval_id'] ?>">
                                                  <input type="text" name="catatan" placeholder="Alasan penolakan">
                                                  <?= csrf_field() ?>


                                            <button type="submit"
                                                    class="btn btn-danger">
                                                Tolak
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        Menunggu persetujuan Sekwan
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <?php if (is_admin()): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['username']) ?></td>
                    <td><?= e(role_label($r['role'])) ?></td>
                    <td><?= e($r['unit']) ?></td>
                    <td>
                        <?php if ($r['approval_status'] === 'approved'): ?>
                            <span class="badge badge-done">Aktif</span>
                        <?php elseif ($r['approval_status'] === 'pending'): ?>
                            <span class="badge badge-wait">Menunggu</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Ditolak</span>
                        <?php endif; ?>
                    </td>

                    <?php if (is_admin()): ?>
                        <td>
                            <?php if (!$r['is_admin']): ?>
                                <form method="post"
                                      action="<?= e(url('pengguna/hapus')) ?>"
                                      onsubmit="return confirm('Hapus pengguna ini? Kata sandi pengguna wajib dimasukkan pada halaman berikutnya.');">

                                    <?= csrf_field() ?>

                                    <input type="hidden"
                                    name="user_id"
                                    value="<?= (int)$r['id'] ?>">

                                    <button class="btn btn-sm btn-ghost" type="submit">
                                        Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/views/layout.php';