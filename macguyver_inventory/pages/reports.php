<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Reports';
require_once '../includes/header.php';
$db = getDB();

$report = $_GET['report'] ?? 'inventory';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');
?>
<div class="page-header">
  <div><h1>Reports</h1><p>Generate and print inventory reports</p></div>
  <button onclick="printPage()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</button>
</div>

<!-- Report Tabs -->
<div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
  <a href="?report=inventory" class="btn <?= $report==='inventory'?'btn-gold':'btn-outline-navy btn-outline' ?>"><i class="fas fa-boxes"></i> Inventory Status</a>
  <a href="?report=low_stock" class="btn <?= $report==='low_stock'?'btn-gold':'btn-outline-navy btn-outline' ?>"><i class="fas fa-exclamation-triangle"></i> Low Stock</a>
  <a href="?report=transactions&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn <?= $report==='transactions'?'btn-gold':'btn-outline-navy btn-outline' ?>"><i class="fas fa-exchange-alt"></i> Transaction Report</a>
  <a href="?report=valuation" class="btn <?= $report==='valuation'?'btn-gold':'btn-outline-navy btn-outline' ?>"><i class="fas fa-peso-sign"></i> Inventory Valuation</a>
</div>

<?php if($report === 'transactions'): ?>
<form method="GET" style="background:#fff;padding:16px 20px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
  <input type="hidden" name="report" value="transactions">
  <label class="form-label" style="margin:0">From:</label>
  <input type="date" name="date_from" class="form-control" style="width:auto" value="<?= $date_from ?>">
  <label class="form-label" style="margin:0">To:</label>
  <input type="date" name="date_to" class="form-control" style="width:auto" value="<?= $date_to ?>">
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Generate</button>
</form>
<?php endif; ?>

<?php if($report === 'inventory'): ?>
<?php
$items = $db->query("SELECT i.*,c.name cat_name,s.name sup_name FROM items i LEFT JOIN categories c ON i.category_id=c.id LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE i.status='active' ORDER BY c.name,i.name");
$total_val = $db->query("SELECT SUM(quantity*unit_price) v FROM items WHERE status='active'")->fetch_assoc()['v'];
?>
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-boxes" style="color:var(--gold)"></i> Inventory Status Report — <?= date('F d, Y') ?></h2>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Item Code</th><th>Item Name</th><th>Category</th><th>Supplier</th><th>Unit</th><th>Qty</th><th>Unit Price</th><th>Total Value</th><th>Status</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$items->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><span class="item-code"><?= sanitize($r['item_code']) ?></span></td>
        <td><?= sanitize($r['name']) ?></td>
        <td><?= sanitize($r['cat_name'] ?? '-') ?></td>
        <td><?= sanitize($r['sup_name'] ?? '-') ?></td>
        <td><?= sanitize($r['unit']) ?></td>
        <td class="<?= $r['quantity']==0?'out-stock':($r['quantity']<=$r['reorder_level']?'low-stock':'') ?>"><?= $r['quantity'] ?></td>
        <td><?= formatCurrency($r['unit_price']) ?></td>
        <td><?= formatCurrency($r['quantity']*$r['unit_price']) ?></td>
        <td><?php if($r['quantity']==0): ?><span class="badge badge-danger">Out of Stock</span>
            <?php elseif($r['quantity']<=$r['reorder_level']): ?><span class="badge badge-warning">Low Stock</span>
            <?php else: ?><span class="badge badge-success">OK</span><?php endif; ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
      <tfoot><tr><td colspan="8" style="text-align:right;font-weight:700;padding:12px 14px">TOTAL INVENTORY VALUE:</td><td colspan="2" style="font-weight:700;color:var(--navy);font-size:1rem;padding:12px 14px"><?= formatCurrency($total_val ?? 0) ?></td></tr></tfoot>
    </table>
  </div>
</div>

<?php elseif($report === 'low_stock'): ?>
<?php $items = $db->query("SELECT i.*,c.name cat_name FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.quantity <= i.reorder_level AND i.status='active' ORDER BY i.quantity ASC"); ?>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-exclamation-triangle" style="color:var(--orange)"></i> Low Stock Report — <?= date('F d, Y') ?></h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Item Code</th><th>Item Name</th><th>Category</th><th>Current Qty</th><th>Reorder Level</th><th>Unit</th><th>Unit Price</th><th>Status</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$items->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><span class="item-code"><?= sanitize($r['item_code']) ?></span></td>
        <td><strong><?= sanitize($r['name']) ?></strong></td>
        <td><?= sanitize($r['cat_name'] ?? '-') ?></td>
        <td class="<?= $r['quantity']==0?'out-stock':'low-stock' ?>"><?= $r['quantity'] ?></td>
        <td><?= $r['reorder_level'] ?></td>
        <td><?= sanitize($r['unit']) ?></td>
        <td><?= formatCurrency($r['unit_price']) ?></td>
        <td><?= $r['quantity']==0?'<span class="badge badge-danger">Out of Stock</span>':'<span class="badge badge-warning">Low Stock</span>' ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if($items->num_rows===0): ?><tr><td colspan="9"><div class="empty-state"><i class="fas fa-check-circle" style="color:var(--green)"></i><p>All items are well stocked!</p></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($report === 'transactions'): ?>
<?php
$df = $db->real_escape_string($date_from);
$dt = $db->real_escape_string($date_to);
$txns = $db->query("SELECT t.*,i.name iname,i.unit,u.full_name uname FROM transactions t LEFT JOIN items i ON t.item_id=i.id LEFT JOIN users u ON t.performed_by=u.id WHERE DATE(t.transaction_date) BETWEEN '$df' AND '$dt' ORDER BY t.transaction_date DESC");
$summary = $db->query("SELECT type,COUNT(*) cnt,SUM(quantity) total_qty FROM transactions WHERE DATE(transaction_date) BETWEEN '$df' AND '$dt' GROUP BY type");
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
<?php while($s=$summary->fetch_assoc()): ?>
<div class="stat-card" style="border-left-color:<?= $s['type']==='stock_in'?'var(--green)':'var(--red)' ?>">
  <div class="stat-icon <?= $s['type']==='stock_in'?'green':'red' ?>"><i class="fas fa-<?= $s['type']==='stock_in'?'arrow-down':'arrow-up' ?>"></i></div>
  <div class="stat-info"><h3><?= $s['cnt'] ?></h3><p><?= ucfirst(str_replace('_',' ',$s['type'])) ?> (<?= $s['total_qty'] ?> units)</p></div>
</div>
<?php endwhile; ?>
</div>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-exchange-alt" style="color:var(--gold)"></i> Transactions: <?= date('M d, Y', strtotime($date_from)) ?> — <?= date('M d, Y', strtotime($date_to)) ?></h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Code</th><th>Item</th><th>Type</th><th>Qty</th><th>Reference</th><th>By</th><th>Date</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$txns->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><span class="item-code"><?= sanitize($r['transaction_code']) ?></span></td>
        <td><?= sanitize($r['iname'] ?? '-') ?></td>
        <td><?php if($r['type']==='stock_in'): ?><span class="badge badge-success">Stock In</span><?php else: ?><span class="badge badge-danger">Stock Out</span><?php endif; ?></td>
        <td style="font-weight:600;color:<?= $r['type']==='stock_in'?'var(--green)':'var(--red)' ?>"><?= ($r['type']==='stock_in'?'+':'-').$r['quantity'].' '.$r['unit'] ?></td>
        <td><?= sanitize($r['reference_no'] ?? '-') ?></td>
        <td><?= sanitize($r['uname'] ?? 'System') ?></td>
        <td style="font-size:.78rem"><?= date('M d, Y g:i A', strtotime($r['transaction_date'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($report === 'valuation'): ?>
<?php
$cats = $db->query("SELECT c.name,COUNT(i.id) item_count,SUM(i.quantity) total_qty,SUM(i.quantity*i.unit_price) total_val FROM categories c LEFT JOIN items i ON i.category_id=c.id AND i.status='active' GROUP BY c.id,c.name ORDER BY total_val DESC");
$grand = $db->query("SELECT SUM(quantity*unit_price) v FROM items WHERE status='active'")->fetch_assoc()['v'];
?>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-peso-sign" style="color:var(--gold)"></i> Inventory Valuation Report — <?= date('F d, Y') ?></h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Category</th><th>Items</th><th>Total Units</th><th>Total Value</th><th>% of Total</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$cats->fetch_assoc()): $pct = $grand>0?round(($r['total_val']/$grand)*100,1):0; ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= sanitize($r['name']) ?></strong></td>
        <td><?= $r['item_count'] ?? 0 ?></td>
        <td><?= $r['total_qty'] ?? 0 ?></td>
        <td><strong><?= formatCurrency($r['total_val'] ?? 0) ?></strong></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="progress" style="flex:1"><div class="progress-bar green" style="width:<?= $pct ?>%"></div></div>
            <span style="font-size:.78rem;min-width:35px"><?= $pct ?>%</span>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
      <tfoot><tr><td colspan="4" style="text-align:right;font-weight:700;padding:12px 14px">GRAND TOTAL:</td><td colspan="2" style="font-weight:700;color:var(--navy);font-size:1.05rem;padding:12px 14px"><?= formatCurrency($grand ?? 0) ?></td></tr></tfoot>
    </table>
  </div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
