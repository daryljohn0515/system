<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Dashboard';
require_once 'includes/header.php';
$db = getDB();

// ── Inventory stats ────────────────────────────────────────────────────────
$totalItems  = $db->query("SELECT COUNT(*) c FROM items WHERE status='active'")->fetch_assoc()['c'];
$totalValue  = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM items WHERE status='active'")->fetch_assoc()['v'];
$lowStock    = $db->query("SELECT COUNT(*) c FROM items WHERE quantity <= reorder_level AND quantity > 0 AND status='active'")->fetch_assoc()['c'];
$outStock    = $db->query("SELECT COUNT(*) c FROM items WHERE quantity = 0 AND status='active'")->fetch_assoc()['c'];
$todayTxn    = $db->query("SELECT COUNT(*) c FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetch_assoc()['c'];

// ── Financial stats ────────────────────────────────────────────────────────
$totalIn      = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM transactions WHERE type='stock_in'")->fetch_assoc()['v'];
$totalOut     = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM transactions WHERE type='stock_out'")->fetch_assoc()['v'];
$thisMonthIn  = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM transactions WHERE type='stock_in'  AND MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW())")->fetch_assoc()['v'];
$thisMonthOut = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM transactions WHERE type='stock_out' AND MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW())")->fetch_assoc()['v'];

// ── Recent transactions ────────────────────────────────────────────────────
$recentTxn = $db->query("SELECT t.*,i.name item_name,i.item_code,u.full_name user_name FROM transactions t LEFT JOIN items i ON t.item_id=i.id LEFT JOIN users u ON t.performed_by=u.id ORDER BY t.transaction_date DESC LIMIT 8");

// ── Low stock items ────────────────────────────────────────────────────────
$lowItems = $db->query("SELECT i.*,c.name cat_name FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.quantity <= i.reorder_level AND i.status='active' ORDER BY i.quantity ASC LIMIT 8");

// ── Category breakdown ─────────────────────────────────────────────────────
$catStats = $db->query("SELECT c.name, COUNT(i.id) total, SUM(i.quantity) qty FROM categories c LEFT JOIN items i ON i.category_id=c.id AND i.status='active' GROUP BY c.id,c.name ORDER BY qty DESC LIMIT 6");

// ── Monthly chart data (last 6 months) ────────────────────────────────────
$monthly = $db->query("
  SELECT DATE_FORMAT(transaction_date,'%b %Y') mo,
         MONTH(transaction_date) mn, YEAR(transaction_date) yr,
         SUM(CASE WHEN type='stock_in'  THEN quantity*unit_price ELSE 0 END) inval,
         SUM(CASE WHEN type='stock_out' THEN quantity*unit_price ELSE 0 END) outval
  FROM transactions
  WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY yr, mn ORDER BY yr ASC, mn ASC
");
$months = []; $inVals = []; $outVals = [];
while ($row = $monthly->fetch_assoc()) {
  $months[]  = $row['mo'];
  $inVals[]  = round((float)$row['inval'], 2);
  $outVals[] = round((float)$row['outval'], 2);
}

// ── Top value items ────────────────────────────────────────────────────────
$topItems = $db->query("SELECT name, item_code, quantity, unit_price, unit, (quantity*unit_price) total_val FROM items WHERE status='active' ORDER BY total_val DESC LIMIT 5");
?>

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <p>Welcome back, <?= sanitize($currentUser['full_name']) ?>! Here's your inventory overview.</p>
  </div>
  <span class="badge badge-navy"><i class="fas fa-circle" style="color:var(--green);font-size:.6rem"></i> System Online</span>
</div>

<!-- ── Inventory Stats ── -->
<div class="stats-grid" style="margin-bottom:10px;">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-boxes"></i></div>
    <div class="stat-info"><h3><?= $totalItems ?></h3><p>Total Active Items</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-peso-sign"></i></div>
    <div class="stat-info"><h3><?= formatCurrency($totalValue) ?></h3><p>Inventory Value</p></div>
  </div>
  <div class="stat-card" style="border-left-color:var(--orange)">
    <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="stat-info"><h3><?= $lowStock ?></h3><p>Low Stock Items</p></div>
  </div>
  <div class="stat-card" style="border-left-color:var(--red)">
    <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
    <div class="stat-info"><h3><?= $outStock ?></h3><p>Out of Stock</p></div>
  </div>
  <div class="stat-card" style="border-left-color:var(--blue)">
    <div class="stat-icon navy"><i class="fas fa-exchange-alt"></i></div>
    <div class="stat-info"><h3><?= $todayTxn ?></h3><p>Today's Transactions</p></div>
  </div>
</div>

<!-- ── Financial Management Section ── -->
<div style="margin:24px 0 12px;display:flex;align-items:center;gap:10px;">
  <i class="fas fa-peso-sign" style="color:var(--gold);font-size:1rem;"></i>
  <span style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;color:var(--navy);letter-spacing:.5px;text-transform:uppercase;">Financial Management</span>
  <div style="flex:1;height:1px;background:var(--gray-200);"></div>
</div>

<!-- Financial Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:22px;">
  <div class="stat-card" style="border-left-color:#16a34a;">
    <div class="stat-icon" style="background:rgba(22,163,74,.12);color:#16a34a;"><i class="fas fa-arrow-circle-down"></i></div>
    <div class="stat-info"><h3 style="color:#16a34a;font-size:1.1rem;"><?= formatCurrency($totalIn) ?></h3><p>Total Stock In Value</p></div>
  </div>
  <div class="stat-card" style="border-left-color:var(--red);">
    <div class="stat-icon" style="background:rgba(231,76,60,.12);color:var(--red);"><i class="fas fa-arrow-circle-up"></i></div>
    <div class="stat-info"><h3 style="color:var(--red);font-size:1.1rem;"><?= formatCurrency($totalOut) ?></h3><p>Total Stock Out Value</p></div>
  </div>
  <div class="stat-card" style="border-left-color:#3498db;">
    <div class="stat-icon" style="background:rgba(52,152,219,.12);color:#3498db;"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-info"><h3 style="color:#3498db;font-size:1.1rem;"><?= formatCurrency($thisMonthIn) ?></h3><p>This Month In</p></div>
  </div>
  <div class="stat-card" style="border-left-color:#e67e22;">
    <div class="stat-icon" style="background:rgba(230,126,34,.12);color:#e67e22;"><i class="fas fa-calendar-times"></i></div>
    <div class="stat-info"><h3 style="color:#e67e22;font-size:1.1rem;"><?= formatCurrency($thisMonthOut) ?></h3><p>This Month Out</p></div>
  </div>
</div>

<!-- Chart + Top Items Row -->
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:22px;margin-bottom:22px;">

  <!-- Monthly chart -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-chart-bar" style="color:var(--gold)"></i> Monthly Stock Movement Value</h2>
    </div>
    <div class="card-body">
      <canvas id="monthlyChart" height="110"></canvas>
    </div>
  </div>

  <!-- Top 5 value items -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-trophy" style="color:var(--gold)"></i> Top Value Items</h2>
      <a href="pages/financial.php" class="btn btn-sm btn-outline-navy btn-outline">Full Report</a>
    </div>
    <div class="card-body" style="padding:12px;">
      <?php $rank=1; while($r=$topItems->fetch_assoc()): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--gray-100);">
        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:900;font-size:1.1rem;color:<?= $rank<=3?'var(--gold)':'var(--gray-400)' ?>;min-width:20px;"><?= $rank++ ?></span>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.82rem;font-weight:700;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($r['name']) ?></div>
          <div style="font-size:.7rem;color:var(--gray-400);"><?= $r['quantity'] ?> <?= sanitize($r['unit']) ?></div>
        </div>
        <span style="font-size:.82rem;font-weight:700;color:var(--navy);white-space:nowrap;"><?= formatCurrency($r['total_val']) ?></span>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

</div>

<!-- Recent Transactions + Low Stock -->
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:22px;margin-bottom:22px;">

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-exchange-alt" style="color:var(--gold)"></i> Recent Transactions</h2>
      <a href="pages/transactions.php" class="btn btn-sm btn-outline-navy btn-outline">View All</a>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Date</th></tr></thead>
        <tbody>
        <?php while($r=$recentTxn->fetch_assoc()): ?>
        <tr>
          <td>
            <span style="font-size:.83rem;font-weight:600;"><?= sanitize($r['item_name']) ?></span><br>
            <span class="item-code" style="font-size:.7rem;"><?= sanitize($r['item_code']) ?></span>
          </td>
          <td>
            <?php if($r['type']==='stock_in'): ?><span class="badge badge-success">Stock In</span>
            <?php elseif($r['type']==='stock_out'): ?><span class="badge badge-danger">Stock Out</span>
            <?php else: ?><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$r['type'])) ?></span><?php endif; ?>
          </td>
          <td style="font-weight:700;color:<?= $r['type']==='stock_in'?'var(--green)':'var(--red)' ?>">
            <?= $r['type']==='stock_in'?'+':'-' ?><?= $r['quantity'] ?>
          </td>
          <td style="font-size:.75rem;color:var(--gray-600);"><?= date('M d, g:i A', strtotime($r['transaction_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-exclamation-triangle" style="color:var(--orange)"></i> Low Stock Alert</h2>
      <a href="pages/items.php?filter=low" class="btn btn-sm btn-outline-navy btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:12px">
      <?php if($lowItems->num_rows === 0): ?>
        <div class="empty-state"><i class="fas fa-check-circle" style="color:var(--green)"></i><p>All items well stocked!</p></div>
      <?php else: ?>
      <?php while($r=$lowItems->fetch_assoc()):
        $pct = $r['reorder_level'] > 0 ? min(100, round(($r['quantity']/$r['reorder_level'])*100)) : 0;
        $color = $r['quantity']==0 ? 'red' : ($pct<50?'orange':'green');
      ?>
      <div style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span style="font-size:.82rem;font-weight:600"><?= sanitize($r['name']) ?></span>
          <span style="font-size:.76rem" class="<?= $r['quantity']==0?'out-stock':'low-stock' ?>"><?= $r['quantity'] ?> / <?= $r['reorder_level'] ?></span>
        </div>
        <div class="progress"><div class="progress-bar <?= $color ?>" style="width:<?= $pct ?>%"></div></div>
      </div>
      <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Category Breakdown -->
<div class="card">
  <div class="card-header"><h2><i class="fas fa-chart-bar" style="color:var(--gold)"></i> Inventory by Category</h2></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
    <?php while($r=$catStats->fetch_assoc()): ?>
    <div style="background:var(--gray-50);border-radius:10px;padding:16px;border:1px solid var(--gray-200);">
      <div style="font-weight:700;color:var(--navy);margin-bottom:4px"><?= sanitize($r['name']) ?></div>
      <div style="font-size:.82rem;color:var(--gray-600)"><?= $r['total'] ?> items &bull; <?= $r['qty'] ?? 0 ?> units</div>
    </div>
    <?php endwhile; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
var mCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(mCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [
      { label: 'Stock In (₱)',  data: <?= json_encode($inVals) ?>,  backgroundColor:'rgba(22,163,74,.75)',  borderColor:'#16a34a', borderWidth:1.5, borderRadius:4 },
      { label: 'Stock Out (₱)', data: <?= json_encode($outVals) ?>, backgroundColor:'rgba(231,76,60,.75)',  borderColor:'#e74c3c', borderWidth:1.5, borderRadius:4 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend:{ position:'top', labels:{ font:{ size:11 } } } },
    scales: {
      y: { ticks:{ callback:function(v){ return '₱'+v.toLocaleString(); }, font:{size:10} }, grid:{color:'rgba(0,0,0,.05)'} },
      x: { ticks:{ font:{size:10} }, grid:{display:false} }
    }
  }
});
</script>
<?php require_once 'includes/footer.php'; ?>
