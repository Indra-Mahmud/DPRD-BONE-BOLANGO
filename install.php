<?php

session_start();
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/install.php';

$done = false;
$error = null;

if (installed()) {
    header('Location: index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $pdo = db_server();
        install_schema($pdo);
        $app = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', db_config()['host'], db_config()['port'], db_config()['name']),
            db_config()['user'],
            db_config()['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $count = (int) $app->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            seed_users($app);
            seed_demo($app);
        }
        $upload = app_config()['upload_dir'];
        if (!is_dir($upload)) {
            mkdir($upload, 0777, true);
        }
        if (!is_dir(__DIR__ . '/storage')) {
            mkdir(__DIR__ . '/storage', 0777, true);
        }
        file_put_contents(__DIR__ . '/storage/.installed', date('c'));
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalasi SIMSURAT</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="login-card" style="min-height:100vh">
  <div class="login-box card panel">
    <h3>Instalasi SIMSURAT DPRD</h3>
    <p class="muted">Sistem akan membuat basis data <strong>simsurat_dprd</strong>, tabel, dan data contoh. Pastikan MySQL Laragon berjalan (default: root tanpa kata sandi).</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($done): ?>
      <div class="flash flash-success">Instalasi selesai. Silakan masuk ke sistem.</div>
      <p><a class="btn btn-primary" href="index.php">Buka SIMSURAT</a></p>
    <?php else: ?>
      <form method="post">
        <button class="btn btn-gold" type="submit">Pasang sekarang</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
