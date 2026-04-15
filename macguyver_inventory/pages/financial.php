<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Financial Management';
require_once '../includes/header.php';
$db = getDB();

// ── Summary stats ──────────────────────────────────────────────────────────
$totalValue    = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM items WHERE status='active'")->fetch_assoc()['v'];
$totalIn       = $db->query("SELECT COALESCE(SUM(t.quantity*t.unit_price),0) v FROM transactions t WHERE t.type='stock_in'")->fetch_assoc()['v'];
$totalOut      = $db->query("SELECT COALESCE(SUM(t.quantity*t.unit_price),0) v FROM transactions t WHERE t.type='stock_out'")->fetch_assoc()['v'];
$lowStockVal   = $db->query("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM items WHERE quantity<=reorder_level AND status='active'")->fetch_assoc()['v'];
$thisMonthIn   = $db->query("SELECT COALESCE(SUM(t.quantity*t.unit_price),0) v FROM transactions t WHERE t.type='stock_in'  AND MONTH(t.transaction_date)=MONTH(NOW()) AND YEAR(t.transaction_date)=YEAR(NOW())")->fetch_assoc()['v'];
$thisMonthOut  = $db->query("SELECT COALESCE(SUM(t.quantity*t.unit_price),0) v FROM transactions t WHERE t.type='stock_out' AND MONTH(t.transaction_date)=MONTH(NOW()) AND YEAR(t.transaction_date)=YEAR(NOW())")->fetch_assoc()['v'];

// ── Category breakdown ─────────────────────────────────────────────────────
$catBreakdown  = $db->query("SELECT c.name, COUNT(i.id) item_count, COALESCE(SUM(i.quantity*i.unit_price),0) val FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.status='active' GROUP BY i.category_id ORDER BY val DESC");

// ── Monthly stock in/out (last 6 months) ───────────────────────────────────
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

// ── Top 10 highest-value items ─────────────────────────────────────────────
$topItems = $db->query("SELECT i.name, i.item_code, i.quantity, i.unit_price, i.unit, (i.quantity*i.unit_price) total_val, c.name cat_name FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.status='active' ORDER BY total_val DESC LIMIT 10");

// ── Recent financial transactions ──────────────────────────────────────────
$recentTxn = $db->query("SELECT t.*, i.name item_name, i.item_code, u.full_name by_name FROM transactions t LEFT JOIN items i ON t.item_id=i.id LEFT JOIN users u ON t.performed_by=u.id WHERE t.unit_price > 0 ORDER BY t.transaction_date DESC LIMIT 12");
?>

<div class="page-header">
  <div>
    <h1>Financial Management</h1>
    <p>Inventory valuation, cost tracking, and financial overview</p>
  </div>
</div>

<!-- ── Summary Cards ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:24px;">

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid var(--navy);">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-boxes"></i> Total Inventory Value</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:var(--navy);"><?= formatCurrency($totalValue) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;">All active items × unit price</div>
  </div>

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid #16a34a;">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-arrow-down"></i> Total Stock In Value</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:#16a34a;"><?= formatCurrency($totalIn) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;">All-time purchases received</div>
  </div>

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid var(--red);">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-arrow-up"></i> Total Stock Out Value</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:var(--red);"><?= formatCurrency($totalOut) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;">All-time items released</div>
  </div>

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid var(--gold);">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i> Low Stock Value at Risk</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:var(--gold);"><?= formatCurrency($lowStockVal) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;">Items at or below reorder level</div>
  </div>

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid #3498db;">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-calendar"></i> This Month In</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:#3498db;"><?= formatCurrency($thisMonthIn) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;"><?= date('F Y') ?></div>
  </div>

  <div class="stat-card" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid #e67e22;">
    <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;"><i class="fas fa-calendar"></i> This Month Out</div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:900;color:#e67e22;"><?= formatCurrency($thisMonthOut) ?></div>
    <div style="font-size:.75rem;color:var(--gray-400);margin-top:4px;"><?= date('F Y') ?></div>
  </div>

</div>

<!-- ── Charts Row ── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

  <!-- Monthly trend chart -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-chart-line" style="color:var(--gold)"></i> Monthly Stock Movement Value</h2>
    </div>
    <div class="card-body">
      <canvas id="monthlyChart" height="100"></canvas>
    </div>
  </div>

  <!-- Category breakdown -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-chart-pie" style="color:var(--gold)"></i> Value by Category</h2>
    </div>
    <div class="card-body">
      <canvas id="catChart" height="180"></canvas>
    </div>
  </div>

</div>

<!-- ── Top Value Items ── -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2><i class="fas fa-trophy" style="color:var(--gold)"></i> Top 10 Highest-Value Items</h2>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Item</th><th>Category</th><th>Qty</th><th>Unit Price</th><th>Total Value</th><th>Share</th></tr></thead>
      <tbody>
      <?php $rank=1; while($r=$topItems->fetch_assoc()):
        $share = $totalValue > 0 ? ($r['total_val'] / $totalValue * 100) : 0;
      ?>
      <tr>
        <td><span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;color:<?= $rank<=3?'var(--gold)':'var(--gray-400)' ?>;font-size:1rem;"><?= $rank++ ?></span></td>
        <td>
          <strong><?= sanitize($r['name']) ?></strong><br>
          <span class="item-code"><?= sanitize($r['item_code']) ?></span>
        </td>
        <td><?= sanitize($r['cat_name'] ?? '—') ?></td>
        <td><?= $r['quantity'] ?> <?= sanitize($r['unit']) ?></td>
        <td><?= formatCurrency($r['unit_price']) ?></td>
        <td><strong style="color:var(--navy);"><?= formatCurrency($r['total_val']) ?></strong></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;">
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px;overflow:hidden;">
              <div style="width:<?= min(100,round($share)) ?>%;height:100%;background:var(--gold);border-radius:4px;"></div>
            </div>
            <span style="font-size:.75rem;color:var(--gray-600);min-width:36px;"><?= number_format($share,1) ?>%</span>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Category Value Table ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-tags" style="color:var(--gold)"></i> Value by Category</h2>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Category</th><th>Items</th><th>Total Value</th><th>%</th></tr></thead>
        <tbody>
        <?php $catBreakdown->data_seek(0); while($r=$catBreakdown->fetch_assoc()):
          $pct = $totalValue > 0 ? ($r['val']/$totalValue*100) : 0;
        ?>
        <tr>
          <td><?= sanitize($r['name'] ?? 'Uncategorized') ?></td>
          <td><?= $r['item_count'] ?></td>
          <td><strong><?= formatCurrency($r['val']) ?></strong></td>
          <td><span style="font-size:.78rem;color:var(--gray-600);"><?= number_format($pct,1) ?>%</span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent financial transactions -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-receipt" style="color:var(--gold)"></i> Recent Transactions</h2>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Value</th></tr></thead>
        <tbody>
        <?php while($r=$recentTxn->fetch_assoc()):
          $isIn = in_array($r['type'],['stock_in','return']);
          $val  = $r['quantity'] * $r['unit_price'];
        ?>
        <tr>
          <td style="font-size:.76rem;color:var(--gray-600);"><?= date('M j', strtotime($r['transaction_date'])) ?></td>
          <td>
            <span style="font-size:.82rem;font-weight:600;"><?= sanitize($r['item_name'] ?? '—') ?></span><br>
            <span class="item-code" style="font-size:.68rem;"><?= sanitize($r['item_code'] ?? '') ?></span>
          </td>
          <td>
            <span class="badge <?= $isIn?'badge-success':'badge-danger' ?>"><?= str_replace('_',' ',$r['type']) ?></span>
          </td>
          <td style="font-weight:700;color:<?= $isIn?'#16a34a':'var(--red)' ?>;">
            <?= $isIn?'+':'-' ?><?= formatCurrency($val) ?>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// Monthly chart
var mCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(mCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [
      { label: 'Stock In (₱)',  data: <?= json_encode($inVals) ?>,  backgroundColor: 'rgba(22,163,74,.75)',  borderColor:'#16a34a', borderWidth:1.5, borderRadius:4 },
      { label: 'Stock Out (₱)', data: <?= json_encode($outVals) ?>, backgroundColor: 'rgba(231,76,60,.75)',  borderColor:'#e74c3c', borderWidth:1.5, borderRadius:4 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend:{ position:'top', labels:{font:{size:11}} } },
    scales: {
      y: { ticks:{ callback:function(v){ return '₱'+v.toLocaleString(); }, font:{size:10} }, grid:{color:'rgba(0,0,0,.05)'} },
      x: { ticks:{ font:{size:10} }, grid:{display:false} }
    }
  }
});

// Category pie chart
<?php
$catBreakdown->data_seek(0);
$catLabels = []; $catData = []; $catColors = ['#1e2d54','#c9a227','#3498db','#16a34a','#e74c3c','#9b59b6','#e67e22','#1abc9c'];
$ci = 0;
while($r = $catBreakdown->fetch_assoc()) {
  $catLabels[] = $r['name'] ?? 'Uncategorized';
  $catData[]   = round((float)$r['val'], 2);
}
?>
var pCtx = document.getElementById('catChart').getContext('2d');
new Chart(pCtx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($catLabels) ?>,
    datasets: [{ data: <?= json_encode($catData) ?>, backgroundColor: <?= json_encode($catColors) ?>, borderWidth:2, borderColor:'#fff' }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position:'bottom', labels:{ font:{size:10}, boxWidth:12 } },
      tooltip: { callbacks:{ label:function(c){ return ' ₱'+c.parsed.toLocaleString(); } } }
    }
  }
});
</script>
<?php require_once '../includes/footer.php'; ?>
