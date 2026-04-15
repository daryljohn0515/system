<?php
/**
 * Public Item View — no login required
 * Scanned from QR code: /macguyver_inventory/item_view.php?code=ITM-001
 */
define('BASE_URL', '/macguyver_inventory/');
require_once 'includes/config.php';

$code = trim($_GET['code'] ?? '');
$item = null;
$cat  = null;
$sup  = null;
$txns = [];

if ($code) {
    $db   = getDB();
    $safe = $db->real_escape_string($code);
    $res  = $db->query("
        SELECT i.*, c.name cat_name, c.description cat_desc,
               s.name sup_name, s.contact_person sup_contact, s.phone sup_phone
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN suppliers  s ON i.supplier_id  = s.id
        WHERE i.item_code = '$safe'
        LIMIT 1
    ");
    if ($res && $res->num_rows) {
        $item = $res->fetch_assoc();
        // last 5 transactions
        $tid = (int)$item['id'];
        $tr  = $db->query("
            SELECT t.*, u.full_name performed_by_name
            FROM transactions t
            LEFT JOIN users u ON t.performed_by = u.id
            WHERE t.item_id = $tid
            ORDER BY t.transaction_date DESC
            LIMIT 5
        ");
        while ($row = $tr->fetch_assoc()) $txns[] = $row;
    }
}

// stock status helpers
function stockClass($qty, $reorder) {
    if ($qty == 0)          return ['bg'=>'#fef2f2','color'=>'#e74c3c','label'=>'Out of Stock',   'icon'=>'fa-times-circle'];
    if ($qty <= $reorder)   return ['bg'=>'#fffbeb','color'=>'#d97706','label'=>'Low Stock',       'icon'=>'fa-exclamation-triangle'];
    return                         ['bg'=>'#f0fdf4','color'=>'#16a34a','label'=>'In Stock',         'icon'=>'fa-check-circle'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?= $item ? htmlspecialchars($item['name']) . ' — MacGuyver' : 'Item Not Found — MacGuyver' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --navy:      #1e2d54;
  --navy-mid:  #2d3e6e;
  --gold:      #c9a227;
  --gold-lt:   #e8c347;
  --white:     #ffffff;
  --g50:       #f8f9fc;
  --g100:      #f0f2f8;
  --g200:      #e2e6f0;
  --g400:      #9aa5c4;
  --g600:      #5a6580;
  --g800:      #2c3347;
  --green:     #16a34a;
  --red:       #e74c3c;
  --amber:     #d97706;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Barlow', sans-serif;
  background: var(--g100);
  color: var(--g800);
  min-height: 100vh;
}

/* ── TOP NAV ── */
.topbar {
  background: var(--navy);
  padding: 0 20px;
  height: 52px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
  border-bottom: 2px solid var(--gold);
}
.topbar-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.topbar-brand img { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--gold); }
.topbar-brand-name { font-family: 'Barlow Condensed', sans-serif; font-size: .9rem; font-weight: 800; color: var(--gold-lt); letter-spacing: 1px; text-transform: uppercase; }
.topbar-login { display: flex; align-items: center; gap: 7px; background: var(--gold); color: var(--navy); padding: 7px 16px; border-radius: 7px; font-size: .78rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; text-decoration: none; transition: background .2s; }
.topbar-login:hover { background: var(--gold-lt); }

/* ── PAGE WRAPPER ── */
.page { max-width: 680px; margin: 0 auto; padding: 24px 16px 60px; }

/* ── NOT FOUND ── */
.not-found { text-align: center; padding: 80px 20px; }
.not-found i { font-size: 4rem; color: var(--g400); margin-bottom: 16px; display: block; }
.not-found h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
.not-found p { color: var(--g600); font-size: .9rem; }

/* ── ITEM CARD ── */
.item-card {
  background: var(--white);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,.08);
  margin-bottom: 20px;
}

.item-header {
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
  padding: 20px 22px 18px;
  position: relative;
  overflow: hidden;
}
.item-header::after {
  content: '';
  position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--gold), var(--gold-lt), var(--gold));
}
.item-header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.item-code-badge { font-family: monospace; font-size: .78rem; background: rgba(201,162,39,.15); border: 1px solid rgba(201,162,39,.3); color: var(--gold-lt); padding: 4px 12px; border-radius: 20px; display: inline-block; letter-spacing: .5px; }
.item-scan-badge { font-size: .65rem; background: rgba(255,255,255,.1); color: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.15); padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
.item-name { font-family: 'Barlow Condensed', sans-serif; font-size: 1.55rem; font-weight: 900; color: #fff; line-height: 1.1; margin-bottom: 6px; }
.item-desc { font-size: .82rem; color: rgba(255,255,255,.6); line-height: 1.55; }

/* ── STOCK STATUS BANNER ── */
.stock-banner {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 22px;
  border-bottom: 1px solid var(--g200);
}
.stock-banner-icon { font-size: 1.4rem; }
.stock-qty { font-family: 'Barlow Condensed', sans-serif; font-size: 2rem; font-weight: 900; line-height: 1; }
.stock-unit { font-size: .78rem; color: var(--g600); margin-top: 1px; }
.stock-label { font-size: .7rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 3px 10px; border-radius: 20px; margin-left: auto; }

/* ── DETAIL GRID ── */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.detail-item {
  padding: 14px 20px;
  border-right: 1px solid var(--g100);
  border-bottom: 1px solid var(--g100);
}
.detail-item:nth-child(even) { border-right: none; }
.detail-label { font-size: .68rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--g400); margin-bottom: 4px; display: flex; align-items: center; gap: 5px; }
.detail-label i { font-size: .65rem; }
.detail-value { font-size: .92rem; font-weight: 600; color: var(--g800); }
.detail-value.mono { font-family: monospace; font-size: .82rem; background: var(--g100); padding: 2px 8px; border-radius: 4px; display: inline-block; }

/* ── SECTION CARD ── */
.section-card {
  background: var(--white);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
  margin-bottom: 20px;
}
.section-head {
  padding: 14px 20px;
  border-bottom: 1px solid var(--g200);
  display: flex; align-items: center; gap: 9px;
}
.section-head h3 { font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 800; color: var(--navy); }
.section-head i { color: var(--gold); font-size: .9rem; }
.section-body { padding: 16px 20px; }

/* ── SUPPLIER CARD ── */
.sup-row { display: flex; gap: 8px; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--g100); }
.sup-row:last-child { border-bottom: none; }
.sup-icon { width: 32px; height: 32px; background: var(--g100); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--g400); font-size: .8rem; flex-shrink: 0; }
.sup-label { font-size: .7rem; color: var(--g400); }
.sup-val { font-size: .88rem; font-weight: 600; color: var(--g800); }

/* ── TRANSACTIONS TABLE ── */
.txn-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.txn-table th { font-size: .68rem; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--g400); padding: 6px 10px; text-align: left; border-bottom: 1px solid var(--g200); }
.txn-table td { padding: 9px 10px; border-bottom: 1px solid var(--g100); vertical-align: middle; }
.txn-table tr:last-child td { border-bottom: none; }
.txn-in  { color: #16a34a; font-weight: 700; }
.txn-out { color: var(--red); font-weight: 700; }
.txn-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.txn-badge.in  { background: rgba(22,163,74,.1);  color: #16a34a; }
.txn-badge.out { background: rgba(231,76,60,.1);  color: var(--red); }
.txn-badge.adj { background: rgba(52,152,219,.1); color: #2980b9; }

/* ── FOOTER ── */
.page-footer { text-align: center; font-size: .72rem; color: var(--g400); padding-top: 12px; }
.page-footer a { color: var(--navy); font-weight: 600; text-decoration: none; }

/* mobile friendly */
@media (max-width: 480px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-item { border-right: none; }
  .item-name { font-size: 1.3rem; }
}
</style>
</head>
<body>

<!-- Top Bar -->
<nav class="topbar">
  <a class="topbar-brand" href="<?= BASE_URL ?>landing.php">
    <img src="<?= BASE_URL ?>assets/logo.png" alt="MacGuyver">
    <span class="topbar-brand-name">MacGuyver</span>
  </a>
  <a class="topbar-login" href="<?= BASE_URL ?>landing.php">
    <i class="fas fa-sign-in-alt"></i> Staff Login
  </a>
</nav>

<div class="page">

<?php if (!$item): ?>
<!-- NOT FOUND -->
<div class="not-found">
  <i class="fas fa-qrcode"></i>
  <h2>Item Not Found</h2>
  <p>
    <?php if ($code): ?>
      No item found with code <strong><?= htmlspecialchars($code) ?></strong>.<br>
      Please check the QR code and try again.
    <?php else: ?>
      No item code was provided. Please scan a valid QR label.
    <?php endif; ?>
  </p>
</div>

<?php else:
  $sc = stockClass($item['quantity'], $item['reorder_level']);
?>

<!-- ITEM CARD -->
<div class="item-card">

  <!-- Header -->
  <div class="item-header">
    <div class="item-header-top">
      <span class="item-code-badge"><i class="fas fa-barcode"></i> <?= htmlspecialchars($item['item_code']) ?></span>
      <span class="item-scan-badge"><i class="fas fa-qrcode"></i> QR Scan</span>
    </div>
    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
    <?php if ($item['description']): ?>
    <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Stock Status Banner -->
  <div class="stock-banner" style="background:<?= $sc['bg'] ?>;">
    <i class="fas <?= $sc['icon'] ?> stock-banner-icon" style="color:<?= $sc['color'] ?>;"></i>
    <div>
      <div class="stock-qty" style="color:<?= $sc['color'] ?>;"><?= $item['quantity'] ?></div>
      <div class="stock-unit"><?= htmlspecialchars($item['unit']) ?> available</div>
    </div>
    <span class="stock-label" style="background:<?= $sc['color'] ?>22;color:<?= $sc['color'] ?>;"><?= $sc['label'] ?></span>
  </div>

  <!-- Detail Grid -->
  <div class="detail-grid">
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-tag"></i> Category</div>
      <div class="detail-value"><?= htmlspecialchars($item['cat_name'] ?? '—') ?></div>
    </div>
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-map-marker-alt"></i> Location</div>
      <div class="detail-value"><?= htmlspecialchars($item['location'] ?? '—') ?></div>
    </div>
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-peso-sign"></i> Unit Price</div>
      <div class="detail-value">₱ <?= number_format($item['unit_price'], 2) ?></div>
    </div>
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-cubes"></i> Unit</div>
      <div class="detail-value"><?= htmlspecialchars($item['unit']) ?></div>
    </div>
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-exclamation-triangle"></i> Reorder Level</div>
      <div class="detail-value"><?= $item['reorder_level'] ?> <?= htmlspecialchars($item['unit']) ?></div>
    </div>
    <div class="detail-item">
      <div class="detail-label"><i class="fas fa-circle"></i> Status</div>
      <div class="detail-value">
        <?php
          $sc2 = ['active'=>['#16a34a','Active'],'inactive'=>['#95a5a6','Inactive'],'discontinued'=>['#e74c3c','Discontinued']];
          $s = $sc2[$item['status']] ?? ['#95a5a6',$item['status']];
        ?>
        <span style="color:<?= $s[0] ?>;font-weight:700;"><?= $s[1] ?></span>
      </div>
    </div>
    <div class="detail-item" style="grid-column:span 2;">
      <div class="detail-label"><i class="fas fa-calendar-alt"></i> Last Updated</div>
      <div class="detail-value"><?= date('F j, Y g:i A', strtotime($item['updated_at'])) ?></div>
    </div>
  </div>

</div><!-- /item-card -->

<!-- Supplier -->
<?php if ($item['sup_name']): ?>
<div class="section-card">
  <div class="section-head"><i class="fas fa-truck"></i><h3>Supplier</h3></div>
  <div class="section-body">
    <div class="sup-row">
      <div class="sup-icon"><i class="fas fa-building"></i></div>
      <div><div class="sup-label">Company</div><div class="sup-val"><?= htmlspecialchars($item['sup_name']) ?></div></div>
    </div>
    <?php if ($item['sup_contact']): ?>
    <div class="sup-row">
      <div class="sup-icon"><i class="fas fa-user"></i></div>
      <div><div class="sup-label">Contact Person</div><div class="sup-val"><?= htmlspecialchars($item['sup_contact']) ?></div></div>
    </div>
    <?php endif; ?>
    <?php if ($item['sup_phone']): ?>
    <div class="sup-row">
      <div class="sup-icon"><i class="fas fa-phone"></i></div>
      <div><div class="sup-label">Phone</div><div class="sup-val"><a href="tel:<?= htmlspecialchars($item['sup_phone']) ?>" style="color:var(--navy);text-decoration:none;"><?= htmlspecialchars($item['sup_phone']) ?></a></div></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Recent Transactions -->
<?php if (!empty($txns)): ?>
<div class="section-card">
  <div class="section-head"><i class="fas fa-history"></i><h3>Recent Transactions</h3></div>
  <div style="overflow-x:auto;">
    <table class="txn-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Qty</th>
          <th>After</th>
          <th>By</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($txns as $t): ?>
        <?php
          $isIn  = in_array($t['type'], ['stock_in','return']);
          $badge = $isIn ? 'in' : ($t['type']==='adjustment' ? 'adj' : 'out');
          $label = str_replace('_',' ', $t['type']);
        ?>
        <tr>
          <td style="color:var(--g600);font-size:.76rem;"><?= date('M j, Y', strtotime($t['transaction_date'])) ?></td>
          <td><span class="txn-badge <?= $badge ?>"><?= $label ?></span></td>
          <td class="<?= $isIn ? 'txn-in' : 'txn-out' ?>"><?= $isIn ? '+' : '-' ?><?= $t['quantity'] ?></td>
          <td style="font-weight:600;"><?= $t['quantity_after'] ?></td>
          <td style="color:var(--g600);font-size:.76rem;"><?= htmlspecialchars($t['performed_by_name'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Footer -->
<div class="page-footer">
  <p>MacGuyver Engineering Services &mdash; Inventory System<br>
  <a href="<?= BASE_URL ?>landing.php">Visit System Portal</a></p>
</div>

</div><!-- /page -->
</body>
</html>
