<?php
$error = flash('error');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk · SIMSURAT DPRD</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="login-wrap">
  <section class="hero">
    <div>
      <div class="brand" style="border:0;padding:0;margin:0">
        <div class="crest">DPRD</div>
        <div><h1>SIMSURAT</h1><small>DPRD Kabupaten Bone Bolango</small></div>
      </div>
      <h2>SIMSURAT <br> DPRD BONE BOLANGO</h2>
      <p>Pelacakan surat dari penerimaan di Sekretariat, disposisi Pimpinan atau Sekwan, tindak lanjut komisi dan bagian, hingga arsip digital.</p>
    </div>
    <div class="muted">Sistem Informasi Manajemen Surat · Oleh Mahasiswa magang UNG LisaCantik.</div>
  </section>
  <section class="login-card">
    <div class="login-box">
      <h3>Log In</h3>
      <p class="muted">Gunakan akun sesuai kewenangan Anda.</p>
      <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" action="<?= e(url('login')) ?>">
        <?= csrf_field() ?>
        <label>Nama pengguna</label>
        <input name="username" required autofocus>
        <label style="margin-top:12px">Kata sandi</label>
        <input type="password" name="password" required>
        <button class="btn btn-primary" style="width:100%;margin-top:16px" type="submit">Masuk</button>
      </form>
      <!--
      <div class="accounts">
        Contoh akun: <strong>sekretariat / sekre123</strong>, <strong>ketua / ketua123</strong>,
        <strong>sekwan / sekwan123</strong>, <strong>komisi2 / komisi123</strong>, <strong>admin / admin123</strong>
      </div> -->
    </div>
  </section>
</div>
</body>
</html>
