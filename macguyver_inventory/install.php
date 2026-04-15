<?php
// MacGuyver Inventory System - Auto Installer
$step = $_POST['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = trim($_POST['db_pass'] ?? '');
    $name = trim($_POST['db_name'] ?? 'macguyver_inventory');

    $conn = @new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        $error = "Cannot connect to MySQL: " . $conn->connect_error;
    } else {
        $conn->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($name);
        $sql = file_get_contents(__DIR__ . '/database.sql');
        // Remove comments and split statements
        $sql = preg_replace('/--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $errors = [];
        foreach ($statements as $stmt) {
            if (!empty($stmt) && !$conn->query($stmt)) {
                if ($conn->errno != 1065) $errors[] = $conn->error;
            }
        }
        // Update config file
        $config = file_get_contents(__DIR__ . '/includes/config.php');
        $config = preg_replace("/define\('DB_HOST'.*?\);/", "define('DB_HOST', '$host');", $config);
        $config = preg_replace("/define\('DB_USER'.*?\);/", "define('DB_USER', '$user');", $config);
        $config = preg_replace("/define\('DB_PASS'.*?\);/", "define('DB_PASS', '$pass');", $config);
        $config = preg_replace("/define\('DB_NAME'.*?\);/", "define('DB_NAME', '$name');", $config);
        file_put_contents(__DIR__ . '/includes/config.php', $config);
        if (empty($errors)) $success = "Installation complete! Database configured successfully.";
        else $error = "Some issues: " . implode('; ', array_slice($errors, 0, 3));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MacGuyver Inventory - Installer</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Barlow',sans-serif; background:linear-gradient(135deg,#1e2d54,#3d5080); min-height:100vh; display:flex; align-items:center; justify-content:center; }
.box { background:#fff; border-radius:16px; padding:40px; width:90%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,.4); }
.logo { text-align:center; margin-bottom:24px; }
.logo img { width:80px; height:80px; border-radius:50%; border:3px solid #c9a227; }
h2 { font-size:1.4rem; color:#1e2d54; margin:10px 0 4px; }
p { color:#888; font-size:.84rem; }
.form-group { margin-bottom:16px; }
label { display:block; font-size:.83rem; font-weight:600; color:#333; margin-bottom:5px; }
input { width:100%; padding:10px 14px; border:1.5px solid #e2e6f0; border-radius:8px; font-family:inherit; font-size:.88rem; }
input:focus { outline:none; border-color:#2d3e6e; }
.btn { width:100%; padding:12px; background:#2d3e6e; color:#fff; border:none; border-radius:8px; font-size:1rem; font-weight:600; cursor:pointer; font-family:inherit; margin-top:8px; }
.btn:hover { background:#3d5080; }
.alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:.86rem; }
.alert-success { background:rgba(46,204,113,.12); color:#27ae60; border:1px solid rgba(46,204,113,.3); }
.alert-danger { background:rgba(231,76,60,.12); color:#c0392b; border:1px solid rgba(231,76,60,.3); }
.steps { display:flex; gap:10px; margin-bottom:24px; }
.step { flex:1; text-align:center; padding:8px; border-radius:8px; font-size:.78rem; font-weight:600; background:#f0f2f8; color:#888; }
.step.active { background:#2d3e6e; color:#fff; }
a.go { display:inline-block; margin-top:12px; color:#2d3e6e; font-weight:600; font-size:.9rem; text-decoration:none; }
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <img src="assets/logo.png" alt="Logo">
    <h2>MacGuyver Inventory</h2>
    <p>System Installer</p>
  </div>

  <div class="steps">
    <div class="step active">1 Configure DB</div>
    <div class="step <?= $success?'active':'' ?>">2 Install</div>
    <div class="step <?= $success?'active':'' ?>">3 Done</div>
  </div>

  <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <p style="margin-bottom:12px">Your inventory system is ready to use!</p>
    <a href="dashboard.php" class="btn">🚀 Go to Inventory System</a>
    <p style="margin-top:16px;font-size:.78rem;color:#aaa;">Login: <strong>admin</strong> / <strong>admin123</strong> — change after first login!</p>
  <?php else: ?>
    <form method="POST">
      <input type="hidden" name="step" value="2">
      <div class="form-group"><label>MySQL Host</label><input name="db_host" value="localhost" required></div>
      <div class="form-group"><label>MySQL Username</label><input name="db_user" value="root" required></div>
      <div class="form-group"><label>MySQL Password</label><input name="db_pass" type="password" placeholder="Leave blank if no password"></div>
      <div class="form-group"><label>Database Name</label><input name="db_name" value="macguyver_inventory" required></div>
      <button type="submit" class="btn">⚙️ Install Now</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
