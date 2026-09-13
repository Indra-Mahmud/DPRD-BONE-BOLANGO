<?php
$page = $page ?? 'dashboard';
$title = $title ?? 'Dasbor';
$user = current_user();
$nav = [
    ['Dashboard', 'dashboard'],
    ['Surat Masuk', 'surat-masuk'],
    ['Kotak Disposisi', 'kotak-disposisi'],
    ['Surat Keluar', 'surat-keluar'],
    ['Arsip', 'arsip'],
];

if (is_admin()) {
    $nav[] = ['Pengguna', 'pengguna'];
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · SIMSURAT DPRD</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Source+Serif+4:wght@500;650;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="crest">DPRD</div>
      <div>
        <h1>SIMSURAT</h1>
        <small>DPRD Bone Bolango</small>
      </div>
    </div>
    <div class="nav">
      <div class="nav-label">Menu utama</div>
      <?php foreach ($nav as [$label, $key]): ?>
        <?php if ($user): ?>
          <a class="<?= $page === $key ? 'active' : '' ?>" href="<?= e(url($key)) ?>"><span class="dot"></span><?= e($label) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <div class="userbox">
      <strong><?= e($user['name'] ?? '') ?></strong>
      <span><?= e($user['jabatan'] ?? '') ?> · <?= e($user['unit'] ?? '') ?></span>
      <div style="margin-top:10px"><a class="btn btn-gold btn-sm" href="<?= e(url('logout')) ?>">Keluar</a></div>
    </div>
  </aside>
  <section class="main">
    <div class="topbar">
      <div class="crumb">Sistem Informasi Manajemen Surat<br><b><?= e($title) ?></b></div>
      <div class="muted"><?= e(app_config()['instansi']) ?></div>
    </div>
    <div class="content">
      <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
      <?= $content ?? '' ?>
    </div>
  </section>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

