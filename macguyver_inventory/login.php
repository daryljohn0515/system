<?php
define('BASE_URL', '/macguyver_inventory/');
require_once 'includes/functions.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

// Block direct URL access — must arrive from landing page
$referer     = $_SERVER['HTTP_REFERER'] ?? '';
$fromLanding = strpos($referer, 'landing.php') !== false;
$fromLogin   = strpos($referer, 'login.php')   !== false;
$isPost      = $_SERVER['REQUEST_METHOD'] === 'POST';
if (!$fromLanding && !$fromLogin && !$isPost) {
    header('Location: landing.php');
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    if ($user && $pass) {
        if (login($user, $pass)) { header('Location: dashboard.php'); exit(); }
        else $error = 'Invalid username or password.';
    } else $error = 'Please fill in all fields.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — MacGuyver Inventory System</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&family=Barlow+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --navy:       #1e2d54;
    --navy-mid:   #2d3e6e;
    --navy-light: #3d5080;
    --gold:       #c9a227;
    --gold-light: #e8c347;
    --white:      #ffffff;
    --gray-50:    #f8f9fc;
    --gray-100:   #f0f2f8;
    --gray-200:   #e2e6f0;
    --gray-400:   #9aa5c4;
    --gray-600:   #5a6580;
    --gray-800:   #2c3347;
    --red:        #e74c3c;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Barlow', sans-serif;
    min-height: 100vh;
    display: flex;
    background: var(--navy);
    overflow: hidden;
  }

  /* Left brand panel */
  .panel-left {
    flex: 1;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 64px;
    overflow: hidden;
  }
  .panel-left::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, #111c38 0%, #1e2d54 55%, #2a3f72 100%);
    z-index: 0;
  }
  .panel-left::after {
    content: '';
    position: absolute;
    top: -100px; right: -80px;
    width: 420px; height: 900px;
    background: linear-gradient(170deg, rgba(201,162,39,.18) 0%, rgba(201,162,39,.04) 100%);
    transform: rotate(-18deg);
    border-left: 1px solid rgba(201,162,39,.25);
    z-index: 1;
  }
  .deco-ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(201,162,39,.12);
    z-index: 1;
    pointer-events: none;
  }
  .deco-ring-1 { width: 520px; height: 520px; bottom: -160px; right: -120px; }
  .deco-ring-2 { width: 300px; height: 300px; bottom: -60px;  right: 20px;  border-color: rgba(201,162,39,.2); }
  .deco-ring-3 { width: 160px; height: 160px; top: 60px;      right: 60px;  border-color: rgba(201,162,39,.15); }
  .dot-grid {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(201,162,39,.18) 1px, transparent 1px);
    background-size: 32px 32px;
    z-index: 1;
    mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,.5) 40%, transparent 100%);
  }
  .panel-left-content { position: relative; z-index: 2; max-width: 480px; }

  .brand-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: rgba(201,162,39,.12);
    border: 1px solid rgba(201,162,39,.3);
    border-radius: 100px;
    padding: 6px 16px 6px 8px;
    margin-bottom: 40px;
  }
  .brand-badge img { width: 36px; height: 36px; border-radius: 50%; border: 2px solid var(--gold); object-fit: cover; }
  .brand-badge span { font-family: 'Barlow Condensed', sans-serif; font-size: .78rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold-light); }

  .hero-heading { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(2.4rem,4vw,3.4rem); font-weight: 800; line-height: 1.05; color: #fff; margin-bottom: 20px; letter-spacing: -.5px; }
  .hero-heading .accent { color: var(--gold-light); }
  .hero-sub { font-size: .95rem; color: var(--gray-400); line-height: 1.65; margin-bottom: 48px; max-width: 380px; }

  .feature-list { display: flex; flex-direction: column; gap: 14px; }
  .feature-item { display: flex; align-items: center; gap: 12px; font-size: .875rem; color: rgba(255,255,255,.75); }
  .feature-icon { width: 32px; height: 32px; background: rgba(201,162,39,.15); border: 1px solid rgba(201,162,39,.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: .8rem; flex-shrink: 0; }

  /* Right login panel */
  .panel-right {
    width: 480px;
    min-height: 100vh;
    background: var(--white);
    display: flex; flex-direction: column; justify-content: center;
    padding: 60px 52px;
    position: relative;
    box-shadow: -20px 0 60px rgba(0,0,0,.35);
  }
  .panel-right::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 50%, var(--navy-light) 100%);
  }

  .form-header { margin-bottom: 36px; }
  .form-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; }
  .form-title { font-family: 'Barlow Condensed', sans-serif; font-size: 2rem; font-weight: 800; color: var(--navy); line-height: 1.1; margin-bottom: 8px; }
  .form-subtitle { font-size: .85rem; color: var(--gray-600); }

  .alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; font-size: .875rem; font-weight: 500; margin-bottom: 24px; animation: slideDown .25s ease; }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
  .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
  .alert-danger i { color: var(--red); }

  .field-group { margin-bottom: 20px; }
  .field-label { display: flex; align-items: center; gap: 7px; font-size: .78rem; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--navy-mid); margin-bottom: 8px; }
  .field-label i { color: var(--gold); font-size: .75rem; }

  .input-wrap { position: relative; }
  .input-wrap input {
    width: 100%; padding: 13px 44px 13px 44px;
    border: 1.5px solid var(--gray-200); border-radius: 10px;
    font-family: 'Barlow', sans-serif; font-size: .95rem; color: var(--gray-800);
    background: var(--gray-50); transition: border-color .2s, background .2s, box-shadow .2s; outline: none;
  }
  .input-wrap input::placeholder { color: var(--gray-400); }
  .input-wrap input:focus { border-color: var(--navy-mid); background: #fff; box-shadow: 0 0 0 4px rgba(45,62,110,.08); }
  .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: .85rem; pointer-events: none; transition: color .2s; }
  .input-wrap:focus-within .input-icon { color: var(--navy-mid); }
  .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gray-400); font-size: .85rem; padding: 4px; transition: color .2s; }
  .toggle-pw:hover { color: var(--navy-mid); }

  .btn-signin {
    width: 100%; padding: 14px 20px; margin-top: 8px;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; border: none; border-radius: 10px;
    font-family: 'Barlow Condensed', sans-serif; font-size: 1.05rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
    transition: transform .15s, box-shadow .15s; box-shadow: 0 4px 16px rgba(30,45,84,.3);
    position: relative; overflow: hidden;
  }
  .btn-signin::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, transparent 40%, rgba(201,162,39,.25) 100%); opacity: 0; transition: opacity .25s; }
  .btn-signin:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(30,45,84,.4); }
  .btn-signin:hover::after { opacity: 1; }
  .btn-signin:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(30,45,84,.3); }

  .creds-hint {
    margin-top: 24px; padding: 12px 16px;
    background: var(--gray-50); border: 1px dashed var(--gray-200); border-radius: 8px;
    display: flex; align-items: center; gap: 10px;
    font-size: .78rem; color: var(--gray-600);
  }
  .creds-hint i { color: var(--gold); flex-shrink: 0; }
  .creds-hint code { font-weight: 700; color: var(--navy-mid); }

  .form-footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--gray-100); font-size: .75rem; color: var(--gray-400); text-align: center; line-height: 1.5; }

  @media (max-width: 900px) { .panel-left { display: none; } .panel-right { width: 100%; padding: 48px 32px; } }
  @media (max-width: 480px) { .panel-right { padding: 40px 24px; } }
</style>
</head>
<body>

<div class="panel-left">
  <div class="dot-grid"></div>
  <div class="deco-ring deco-ring-1"></div>
  <div class="deco-ring deco-ring-2"></div>
  <div class="deco-ring deco-ring-3"></div>
  <div class="panel-left-content">
    <div class="brand-badge">
      <img src="assets/logo.png" alt="MacGuyver Logo">
      <span>MacGuyver Enterprises</span>
    </div>
    <h1 class="hero-heading">Smart Inventory,<br><span class="accent">Zero Guesswork.</span></h1>
    <p class="hero-sub">A complete inventory management solution built for MacGuyver Engineering Services — track stock, manage suppliers, and stay in control.</p>
    <div class="feature-list">
      <div class="feature-item"><div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>Real-time stock tracking &amp; alerts</div>
      <div class="feature-item"><div class="feature-icon"><i class="fas fa-truck"></i></div>Supplier &amp; purchase order management</div>
      <div class="feature-item"><div class="feature-icon"><i class="fas fa-chart-bar"></i></div>Inventory reports &amp; audit logs</div>
      <div class="feature-item"><div class="feature-icon"><i class="fas fa-user-shield"></i></div>Role-based user access control</div>
    </div>
  </div>
</div>

<div class="panel-right">
  <div class="form-header">
    <div class="form-eyebrow">Inventory System</div>
    <h2 class="form-title">Welcome back</h2>
    <p class="form-subtitle">Sign in to access your dashboard</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="field-group">
      <label class="field-label"><i class="fas fa-user"></i> Username</label>
      <div class="input-wrap">
        <input type="text" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        <i class="fas fa-user input-icon"></i>
      </div>
    </div>
    <div class="field-group">
      <label class="field-label"><i class="fas fa-lock"></i> Password</label>
      <div class="input-wrap">
        <input type="password" name="password" id="pw-field" placeholder="Enter your password" required>
        <i class="fas fa-lock input-icon"></i>
        <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Toggle password visibility"><i class="fas fa-eye" id="pw-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn-signin"><i class="fas fa-sign-in-alt"></i> Sign In</button>
  </form>

  <div class="creds-hint">
    <i class="fas fa-circle-info"></i>
    Default credentials &mdash; <code>admin</code> / <code>admin123</code>
  </div>

  <div class="form-footer">
    &copy; <?= date('Y') ?> MacGuyver Enterprises &mdash; Engineering Services<br>
    Inventory Management System
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('pw-field');
  const e = document.getElementById('pw-eye');
  if (f.type === 'password') { f.type = 'text'; e.classList.replace('fa-eye','fa-eye-slash'); }
  else { f.type = 'password'; e.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
</body>
</html>
