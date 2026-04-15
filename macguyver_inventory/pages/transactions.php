<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Transactions';
require_once '../includes/header.php';
$db = getDB();

$search = $db->real_escape_string($_GET['search'] ?? '');
$type = $db->real_escape_string($_GET['type'] ?? '');
$date_from = $db->real_escape_string($_GET['date_from'] ?? '');
$date_to = $db->real_escape_string($_GET['date_to'] ?? '');

$where = "WHERE 1=1";
if ($search) $where .= " AND (i.name LIKE '%$search%' OR t.reference_no LIKE '%$search%')";
if ($type) $where .= " AND t.type='$type'";
if ($date_from) $where .= " AND DATE(t.transaction_date) >= '$date_from'";
if ($date_to) $where .= " AND DATE(t.transaction_date) <= '$date_to'";

$txns = $db->query("SELECT t.*,i.name iname,i.item_code,i.unit,u.full_name uname FROM transactions t LEFT JOIN items i ON t.item_id=i.id LEFT JOIN users u ON t.performed_by=u.id $where ORDER BY t.transaction_date DESC LIMIT 200");
?>
<div class="page-header">
  <div><h1>Transaction History</h1><p>All inventory movement records</p></div>
  <button onclick="printPage()" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
</div>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:12px;">
    <h2><i class="fas fa-exchange-alt" style="color:var(--gold)"></i> All Transactions</h2>
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
      <input type="text" name="search" class="search-input" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="min-width:160px">
      <select name="type" class="form-control" style="width:auto">
        <option value="">All Types</option>
        <option value="stock_in" <?= $type==='stock_in'?'selected':'' ?>>Stock In</option>
        <option value="stock_out" <?= $type==='stock_out'?'selected':'' ?>>Stock Out</option>
        <option value="adjustment" <?= $type==='adjustment'?'selected':'' ?>>Adjustment</option>
        <option value="return" <?= $type==='return'?'selected':'' ?>>Return</option>
      </select>
      <input type="date" name="date_from" class="form-control" style="width:auto" value="<?= htmlspecialchars($date_from) ?>">
      <input type="date" name="date_to" class="form-control" style="width:auto" value="<?= htmlspecialchars($date_to) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
      <a href="transactions.php" class="btn btn-secondary btn-sm">Reset</a>
    </form>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Item</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Reference</th><th>Performed By</th><th>Date & Time</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$txns->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= sanitize($r['iname'] ?? 'N/A') ?></strong><br><small class="item-code"><?= sanitize($r['item_code'] ?? '') ?></small></td>
        <td>
          <?php if($r['type']==='stock_in'): ?><span class="badge badge-success"><i class="fas fa-arrow-down"></i> Stock In</span>
          <?php elseif($r['type']==='stock_out'): ?><span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Stock Out</span>
          <?php elseif($r['type']==='adjustment'): ?><span class="badge badge-warning">Adjustment</span>
          <?php else: ?><span class="badge badge-info">Return</span><?php endif; ?>
        </td>
        <td style="font-weight:700;color:<?= $r['type']==='stock_in'?'var(--green)':'var(--red)' ?>">
          <?= $r['type']==='stock_in'?'+':'-' ?><?= $r['quantity'] ?> <?= sanitize($r['unit'] ?? '') ?>
        </td>
        <td><?= $r['quantity_before'] ?></td>
        <td><?= $r['quantity_after'] ?></td>
        <td><?= sanitize($r['reference_no'] ?? '-') ?><br><small style="color:var(--gray-400)"><?= sanitize(substr($r['remarks'] ?? '', 0, 30)) ?></small></td>
        <td><?= sanitize($r['uname'] ?? 'System') ?></td>
        <td style="font-size:.78rem;color:var(--gray-600);white-space:nowrap"><?= date('M d, Y<br>g:i A', strtotime($r['transaction_date'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
