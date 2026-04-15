<?php
define('BASE_URL', '/macguyver_inventory/');
require_once 'includes/functions.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MacGuyver Enterprises — Inventory System</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&family=Barlow+Condensed:wght@600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --navy:       #1e2d54;
  --navy-mid:   #2d3e6e;
  --navy-light: #3d5080;
  --gold:       #c9a227;
  --gold-light: #e8c347;
  --gold-pale:  #f5e9be;
  --white:      #ffffff;
  --gray-50:    #f8f9fc;
  --gray-100:   #f0f2f8;
  --gray-200:   #e2e6f0;
  --gray-400:   #9aa5c4;
  --gray-600:   #5a6580;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Barlow', sans-serif; background: var(--navy); color: #fff; overflow-x: hidden; }

/* ── NAV ─────────────────────────────── */
.nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 60px;
  background: rgba(30,45,84,.0);
  backdrop-filter: blur(0px);
  transition: background .4s, backdrop-filter .4s, padding .3s;
  border-bottom: 1px solid transparent;
}
.nav.scrolled {
  background: rgba(17,28,56,.92);
  backdrop-filter: blur(12px);
  padding: 12px 60px;
  border-bottom-color: rgba(201,162,39,.2);
}
.nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.nav-brand img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--gold); object-fit: cover; }
.nav-brand-text { display: flex; flex-direction: column; line-height: 1; }
.nav-brand-name { font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 800; color: var(--gold-light); letter-spacing: 1px; }
.nav-brand-sub { font-size: .62rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: .8px; }
.nav-cta {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--gold); color: var(--navy);
  padding: 9px 22px; border-radius: 8px;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: .9rem; font-weight: 800; letter-spacing: .8px; text-transform: uppercase;
  text-decoration: none; transition: background .2s, transform .15s, box-shadow .2s;
  box-shadow: 0 4px 16px rgba(201,162,39,.35);
}
.nav-cta:hover { background: var(--gold-light); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,162,39,.5); }

/* ── HERO ────────────────────────────── */
.hero {
  min-height: 100vh;
  position: relative;
  display: flex; align-items: center;
  padding: 120px 60px 80px;
  overflow: hidden;
}
/* layered background */
.hero::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #0d1830 0%, #1a2748 40%, #243566 70%, #1e2d54 100%);
}
/* dot grid */
.hero::after {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(201,162,39,.18) 1px, transparent 1px);
  background-size: 36px 36px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
}
/* diagonal stripe */
.hero-stripe {
  position: absolute; top: -150px; right: -100px;
  width: 600px; height: 120vh;
  background: linear-gradient(170deg, rgba(201,162,39,.12) 0%, transparent 70%);
  transform: rotate(-12deg);
  border-left: 1px solid rgba(201,162,39,.18);
}
/* floating rings */
.hero-ring { position: absolute; border-radius: 50%; border: 1px solid rgba(201,162,39,.1); pointer-events: none; }
.hero-ring-1 { width: 600px; height: 600px; right: -200px; top: 50%; transform: translateY(-50%); }
.hero-ring-2 { width: 350px; height: 350px; right: -50px; top: 45%; transform: translateY(-50%); border-color: rgba(201,162,39,.16); }
.hero-ring-3 { width: 180px; height: 180px; right: 110px; top: 30%; border-color: rgba(201,162,39,.2); }

.hero-content { position: relative; z-index: 2; max-width: 680px; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(201,162,39,.1); border: 1px solid rgba(201,162,39,.3);
  border-radius: 100px; padding: 6px 16px;
  font-size: .72rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
  color: var(--gold-light); margin-bottom: 32px;
}
.hero-badge i { font-size: .65rem; }

h1.hero-title {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: clamp(3rem, 6vw, 5.2rem);
  font-weight: 900;
  line-height: .97;
  letter-spacing: -1px;
  margin-bottom: 28px;
}
.hero-title .line-gold { color: var(--gold-light); }
.hero-title .line-outline {
  -webkit-text-stroke: 2px rgba(255,255,255,.35);
  color: transparent;
}

.hero-desc {
  font-size: 1.05rem; color: rgba(255,255,255,.65);
  line-height: 1.7; max-width: 500px; margin-bottom: 44px;
}

.hero-actions { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
.btn-hero-primary {
  display: inline-flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
  color: var(--navy); padding: 14px 30px; border-radius: 10px;
  font-family: 'Barlow Condensed', sans-serif; font-size: 1.05rem; font-weight: 800;
  letter-spacing: 1px; text-transform: uppercase; text-decoration: none;
  box-shadow: 0 8px 28px rgba(201,162,39,.4);
  transition: transform .2s, box-shadow .2s;
}
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(201,162,39,.55); }
.btn-hero-secondary {
  display: inline-flex; align-items: center; gap: 9px;
  border: 1.5px solid rgba(255,255,255,.25); color: rgba(255,255,255,.8);
  padding: 14px 28px; border-radius: 10px;
  font-family: 'Barlow Condensed', sans-serif; font-size: 1.05rem; font-weight: 700;
  letter-spacing: 1px; text-transform: uppercase; text-decoration: none;
  transition: border-color .2s, color .2s, background .2s;
}
.btn-hero-secondary:hover { border-color: var(--gold); color: var(--gold-light); background: rgba(201,162,39,.06); }

/* Hero visual cards */
.hero-visual {
  position: absolute; right: 60px; top: 50%; transform: translateY(-50%);
  z-index: 2; display: flex; flex-direction: column; gap: 14px;
  width: 280px;
}
.vis-card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(201,162,39,.18);
  backdrop-filter: blur(12px);
  border-radius: 14px; padding: 18px 20px;
  animation: floatCard 4s ease-in-out infinite;
}
.vis-card:nth-child(2) { animation-delay: -1.5s; margin-left: 28px; }
.vis-card:nth-child(3) { animation-delay: -3s; margin-left: 14px; }
@keyframes floatCard { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

.vis-card-label { font-size: .65rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold); margin-bottom: 8px; }
.vis-card-value { font-family: 'Barlow Condensed', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 4px; }
.vis-card-sub { font-size: .72rem; color: var(--gray-400); }
.vis-card-bar { height: 4px; background: rgba(255,255,255,.1); border-radius: 2px; margin-top: 10px; overflow: hidden; }
.vis-card-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-light)); border-radius: 2px; }

/* ── STATS STRIP ─────────────────────── */
.stats-strip {
  position: relative; z-index: 2;
  background: rgba(255,255,255,.04);
  border-top: 1px solid rgba(201,162,39,.15);
  border-bottom: 1px solid rgba(201,162,39,.15);
  padding: 36px 60px;
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 0;
}
.stat-item { text-align: center; padding: 0 20px; border-right: 1px solid rgba(255,255,255,.06); }
.stat-item:last-child { border-right: none; }
.stat-num { font-family: 'Barlow Condensed', sans-serif; font-size: 2.4rem; font-weight: 900; color: var(--gold-light); line-height: 1; margin-bottom: 6px; }
.stat-label { font-size: .78rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

/* ── FEATURES ────────────────────────── */
.features { padding: 100px 60px; background: #0f1c38; position: relative; overflow: hidden; }
.features::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(201,162,39,.07) 1px, transparent 1px);
  background-size: 40px 40px;
}
.section-header { text-align: center; margin-bottom: 64px; position: relative; z-index: 2; }
.section-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--gold); margin-bottom: 12px; }
.section-title { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; color: #fff; line-height: 1.05; margin-bottom: 16px; }
.section-subtitle { font-size: .95rem; color: var(--gray-400); max-width: 500px; margin: 0 auto; line-height: 1.65; }

.features-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 24px; position: relative; z-index: 2;
  max-width: 1100px; margin: 0 auto;
}
.feature-card {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px; padding: 32px 28px;
  transition: border-color .3s, background .3s, transform .3s;
  position: relative; overflow: hidden;
}
.feature-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  opacity: 0; transition: opacity .3s;
}
.feature-card:hover { border-color: rgba(201,162,39,.35); background: rgba(201,162,39,.04); transform: translateY(-4px); }
.feature-card:hover::before { opacity: 1; }
.feature-icon-wrap {
  width: 52px; height: 52px;
  background: rgba(201,162,39,.12);
  border: 1px solid rgba(201,162,39,.25);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: var(--gold);
  margin-bottom: 20px;
}
.feature-card h3 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
.feature-card p { font-size: .85rem; color: var(--gray-400); line-height: 1.65; }

/* ── HOW IT WORKS ────────────────────── */
.how-it-works { padding: 100px 60px; background: linear-gradient(180deg, #111c38 0%, #0f1c38 100%); }
.steps-row {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 0; max-width: 1100px; margin: 0 auto;
  position: relative;
}
.steps-row::before {
  content: '';
  position: absolute; top: 34px; left: 10%; right: 10%; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(201,162,39,.3) 20%, rgba(201,162,39,.3) 80%, transparent);
  z-index: 0;
}
.step { text-align: center; padding: 0 24px; position: relative; z-index: 1; }
.step-num {
  width: 68px; height: 68px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
  border: 2px solid var(--gold);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Barlow Condensed', sans-serif; font-size: 1.6rem; font-weight: 900; color: var(--gold-light);
  margin: 0 auto 20px;
  box-shadow: 0 0 0 8px rgba(201,162,39,.08);
}
.step h4 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
.step p { font-size: .82rem; color: var(--gray-400); line-height: 1.6; }

/* ── CTA SECTION ─────────────────────── */
.cta-section {
  padding: 100px 60px; text-align: center;
  background: linear-gradient(135deg, #0d1830 0%, #1a2748 50%, #111c38 100%);
  position: relative; overflow: hidden;
}
.cta-section::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(201,162,39,.12) 1px, transparent 1px);
  background-size: 32px 32px;
}
.cta-glow {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 600px; height: 300px;
  background: radial-gradient(ellipse, rgba(201,162,39,.12) 0%, transparent 70%);
  pointer-events: none;
}
.cta-section > * { position: relative; z-index: 2; }
.cta-section h2 { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(2rem, 4vw, 3.4rem); font-weight: 900; color: #fff; margin-bottom: 16px; }
.cta-section p { font-size: 1rem; color: var(--gray-400); margin-bottom: 40px; max-width: 460px; margin-left: auto; margin-right: auto; line-height: 1.65; }

/* ── FOOTER ──────────────────────────── */
footer {
  background: #080f1f;
  border-top: 1px solid rgba(201,162,39,.15);
  padding: 32px 60px;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 16px;
}
.footer-brand { display: flex; align-items: center; gap: 10px; }
.footer-brand img { width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid var(--gold); }
.footer-brand span { font-family: 'Barlow Condensed', sans-serif; font-size: .9rem; font-weight: 700; color: var(--gold-light); letter-spacing: .8px; }
footer p { font-size: .75rem; color: var(--gray-400); }

/* responsive */
@media (max-width: 1100px) {
  .hero-visual { display: none; }
  .features-grid { grid-template-columns: repeat(2, 1fr); }
  .steps-row { grid-template-columns: repeat(2, 1fr); gap: 36px; }
  .steps-row::before { display: none; }
}
@media (max-width: 700px) {
  .nav { padding: 16px 24px; }
  .nav.scrolled { padding: 10px 24px; }
  .hero { padding: 100px 24px 60px; }
  .stats-strip { grid-template-columns: repeat(2, 1fr); padding: 32px 24px; }
  .features, .how-it-works, .cta-section { padding: 70px 24px; }
  .features-grid { grid-template-columns: 1fr; }
  .steps-row { grid-template-columns: 1fr; }
  footer { padding: 24px; flex-direction: column; text-align: center; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
  <a class="nav-brand" href="landing.php">
    <img src="assets/logo.png" alt="MacGuyver Logo">
    <div class="nav-brand-text">
      <span class="nav-brand-name">MacGuyver</span>
      <span class="nav-brand-sub">Enterprises</span>
    </div>
  </a>
  <a class="nav-cta" href="login.php"><i class="fas fa-sign-in-alt"></i> Login to System</a>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-stripe"></div>
  <div class="hero-ring hero-ring-1"></div>
  <div class="hero-ring hero-ring-2"></div>
  <div class="hero-ring hero-ring-3"></div>
  <div class="hero-content">
    <div class="hero-badge"><i class="fas fa-shield-alt"></i> MacGuyver Engineering Services</div>
    <h1 class="hero-title">
      Total Control<br>
      <span class="line-gold">of Your</span><br>
      <span class="line-outline">Inventory.</span>
    </h1>
    <p class="hero-desc">
      A purpose-built inventory management system for MacGuyver Engineering Services — real-time stock tracking, Barcode-tagged items, supplier management, and smart reports all in one place.
    </p>
    <div class="hero-actions">
      <a href="login.php" class="btn-hero-primary">
        <i class="fas fa-sign-in-alt"></i> Access the System
      </a>
      <a href="#features" class="btn-hero-secondary">
        <i class="fas fa-arrow-down"></i> See Features
      </a>
    </div>
  </div>

  <!-- floating stat cards -->
  <div class="hero-visual">
    <div class="vis-card">
      <div class="vis-card-label">Total Items Tracked</div>
      <div class="vis-card-value">1,248</div>
      <div class="vis-card-sub">Across all categories</div>
      <div class="vis-card-bar"><div class="vis-card-bar-fill" style="width:78%"></div></div>
    </div>
    <div class="vis-card">
      <div class="vis-card-label">Barcodes Generated</div>
      <div class="vis-card-value">1,248</div>
      <div class="vis-card-sub">Scan-ready labels</div>
      <div class="vis-card-bar"><div class="vis-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="vis-card">
      <div class="vis-card-label">Suppliers Active</div>
      <div class="vis-card-value">34</div>
      <div class="vis-card-sub">Linked &amp; verified</div>
      <div class="vis-card-bar"><div class="vis-card-bar-fill" style="width:60%"></div></div>
    </div>
  </div>
</section>

<!-- STATS STRIP -->
<div class="stats-strip">
  <div class="stat-item">
    <div class="stat-num">100%</div>
    <div class="stat-label">Barcode-tagged items</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">Real-time</div>
    <div class="stat-label">Stock monitoring</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">Multi-role</div>
    <div class="stat-label">Access control</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">Zero</div>
    <div class="stat-label">Guesswork needed</div>
  </div>
</div>

<!-- FEATURES -->
<section class="features" id="features">
  <div class="section-header">
    <div class="section-eyebrow">What's inside</div>
    <h2 class="section-title">Everything you need,<br>nothing you don't.</h2>
    <p class="section-subtitle">Designed specifically for MacGuyver Engineering Services operations.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-barcode"></i></div>
      <h3>Barcode per Item</h3>
      <p>Every inventory item gets a unique barcode. Scan with any barcode reader to instantly pull up item details, stock levels, and location.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-boxes-stacked"></i></div>
      <h3>Live Stock Tracking</h3>
      <p>Real-time quantity updates on every stock-in and stock-out transaction. Low-stock alerts keep you ahead of shortages.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-truck"></i></div>
      <h3>Supplier Management</h3>
      <p>Maintain a full directory of suppliers linked directly to your inventory items and purchase transactions.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-chart-bar"></i></div>
      <h3>Reports & Analytics</h3>
      <p>Generate inventory valuation, stock movement, and transaction reports. Export-ready for management review.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-user-shield"></i></div>
      <h3>Role-based Access</h3>
      <p>Admin, staff, and viewer roles — each with the right level of access. Full audit log for every action taken.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon-wrap"><i class="fas fa-history"></i></div>
      <h3>Activity Audit Log</h3>
      <p>Every add, edit, delete, and transaction is logged with timestamps and user attribution for full accountability.</p>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works">
  <div class="section-header">
    <div class="section-eyebrow">Simple workflow</div>
    <h2 class="section-title">Up and running in minutes.</h2>
    <p class="section-subtitle">From setup to scan — simple and straightforward.</p>
  </div>
  <div class="steps-row">
    <div class="step">
      <div class="step-num">01</div>
      <h4>Log In</h4>
      <p>Sign in with your credentials. Admins can create and manage user accounts from the dashboard.</p>
    </div>
    <div class="step">
      <div class="step-num">02</div>
      <h4>Add Items</h4>
      <p>Create inventory items with name, category, supplier, price, and location. A barcode is generated automatically.</p>
    </div>
    <div class="step">
      <div class="step-num">03</div>
      <h4>Print Barcode Labels</h4>
      <p>Print barcode labels directly from the items page and attach them to physical stock for quick scanning.</p>
    </div>
    <div class="step">
      <div class="step-num">04</div>
      <h4>Track & Report</h4>
      <p>Record stock-in and stock-out transactions. Monitor levels and export reports anytime.</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <h2>Ready to take control<br>of your inventory?</h2>
  <p>Log in to the MacGuyver Inventory System and start managing your stock smarter today.</p>
  <a href="login.php" class="btn-hero-primary" style="font-size:1.1rem;padding:16px 36px;">
    <i class="fas fa-sign-in-alt"></i> Sign In to the System
  </a>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-brand">
    <img src="assets/logo.png" alt="MacGuyver">
    <span>MacGuyver Enterprises</span>
  </div>
  <p>&copy; <?= date('Y') ?> MacGuyver Engineering Services. All rights reserved.</p>
</footer>

<script>
const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
});
</script>
</body>
</html>
