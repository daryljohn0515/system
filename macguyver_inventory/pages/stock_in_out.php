<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Stock In / Out';
require_once '../includes/header.php';
$db = getDB();
$msg = ''; $msgType = 'success';

// Active tab
$tab = $_GET['tab'] ?? 'stock_in';
if (!in_array($tab, ['stock_in','stock_out'])) $tab = 'stock_in';

// Pagination for recent records
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = (int)$_POST['item_id'];
    $qty = (int)$_POST['quantity'];
    $ref = $db->real_escape_string(trim($_POST['reference_no'] ?? ''));
    $remarks = $db->real_escape_string(trim($_POST['remarks'] ?? ''));
    $uid = (int)$_SESSION['user_id'];

    if ($action === 'stock_in') {
        $price = (float)$_POST['unit_price'];
        if ($item_id > 0 && $qty > 0) {
            $item = $db->query("SELECT * FROM items WHERE id=$item_id")->fetch_assoc();
            if ($item) {
                $before = $item['quantity'];
                $after  = $before + $qty;
                $db->query("UPDATE items SET quantity=$after WHERE id=$item_id");
                $code = generateCode('TXN');
                $db->query("INSERT INTO transactions (transaction_code,item_id,type,quantity,quantity_before,quantity_after,unit_price,reference_no,remarks,performed_by) VALUES ('$code',$item_id,'stock_in',$qty,$before,$after,$price,'$ref','$remarks',$uid)");
                logActivity($uid, 'stock_in', "Stock in: {$item['name']} +$qty");
                $msg = "Stock In successful! Added $qty {$item['unit']} of '{$item['name']}'.";
                $tab = 'stock_in';
            }
        } else { $msg = "Please select an item and enter a valid quantity."; $msgType = 'danger'; }
    } elseif ($action === 'stock_out') {
        if ($item_id > 0 && $qty > 0) {
            $item = $db->query("SELECT * FROM items WHERE id=$item_id")->fetch_assoc();
            if ($item) {
                if ($qty > $item['quantity']) {
                    $msg = "Insufficient stock! Available: {$item['quantity']} {$item['unit']}"; $msgType = 'danger';
                } else {
                    $before = $item['quantity'];
                    $after  = $before - $qty;
                    $db->query("UPDATE items SET quantity=$after WHERE id=$item_id");
                    $code = generateCode('TXN');
                    $db->query("INSERT INTO transactions (transaction_code,item_id,type,quantity,quantity_before,quantity_after,unit_price,reference_no,remarks,performed_by) VALUES ('$code',$item_id,'stock_out',$qty,$before,$after,{$item['unit_price']},'$ref','$remarks',$uid)");
                    logActivity($uid, 'stock_out', "Stock out: {$item['name']} -$qty");
                    $msg = "Stock Out recorded! Removed $qty {$item['unit']} of '{$item['name']}'.";
                    $tab = 'stock_out';
                }
            }
        } else { $msg = "Please select an item and enter a valid quantity."; $msgType = 'danger'; }
    }
}

// Data for forms
$items_in  = $db->query("SELECT * FROM items WHERE status='active' ORDER BY name");
$items_out = $db->query("SELECT * FROM items WHERE status='active' AND quantity > 0 ORDER BY name");

// Paginated recent records
$totalIn  = $db->query("SELECT COUNT(*) c FROM transactions WHERE type='stock_in'")->fetch_assoc()['c'];
$totalOut = $db->query("SELECT COUNT(*) c FROM transactions WHERE type='stock_out'")->fetch_assoc()['c'];
$pagesIn  = max(1, ceil($totalIn / $perPage));
$pagesOut = max(1, ceil($totalOut / $perPage));

$recent_in  = $db->query("SELECT t.*,i.name iname,i.unit,s.name sup_name FROM transactions t LEFT JOIN items i ON t.item_id=i.id LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE t.type='stock_in' ORDER BY t.transaction_date DESC LIMIT $perPage OFFSET $offset");
$recent_out = $db->query("SELECT t.*,i.name iname,i.unit FROM transactions t LEFT JOIN items i ON t.item_id=i.id WHERE t.type='stock_out' ORDER BY t.transaction_date DESC LIMIT $perPage OFFSET $offset");
?>

<div class="page-header">
  <div><h1>Stock In / Out</h1><p>Record incoming and outgoing inventory movements</p></div>
</div>

<?php if($msg): ?>
<div class="alert alert-<?= $msgType ?>">
  <i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Tab Navigation -->
<div style="display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid var(--gray-200);">
  <a href="?tab=stock_in&page=1" class="tab-btn <?= $tab==='stock_in'?'tab-active':'' ?>">
    <i class="fas fa-arrow-circle-down" style="color:var(--green)"></i> Stock In
  </a>
  <a href="?tab=stock_out&page=1" class="tab-btn <?= $tab==='stock_out'?'tab-active':'' ?>">
    <i class="fas fa-arrow-circle-up" style="color:var(--red)"></i> Stock Out
  </a>
</div>

<style>
.tab-btn {
  padding:10px 28px; font-size:.92rem; font-weight:600; color:var(--gray-600);
  text-decoration:none; border-bottom:3px solid transparent; margin-bottom:-2px;
  transition:all .2s; background:none; border-top:none; border-left:none; border-right:none;
  display:inline-flex; align-items:center; gap:8px;
}
.tab-btn:hover { color:var(--navy); }
.tab-active { color:var(--navy); border-bottom:3px solid var(--gold); }
.pagination { display:flex; align-items:center; gap:6px; justify-content:flex-end; padding:14px 16px; flex-wrap:wrap; }
.pag-btn {
  padding:5px 11px; border-radius:6px; border:1px solid var(--gray-200);
  background:#fff; color:var(--navy); font-size:.82rem; font-weight:600;
  text-decoration:none; transition:all .2s;
}
.pag-btn:hover { background:var(--navy); color:#fff; }
.pag-btn.active { background:var(--navy); color:#fff; border-color:var(--navy); }
.pag-btn.disabled { opacity:.4; pointer-events:none; }
</style>

<!-- ===== STOCK IN TAB ===== -->
<?php if($tab === 'stock_in'): ?>
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;align-items:start;">
  <div class="card">
    <div class="card-header"><h2><i class="fas fa-arrow-circle-down" style="color:var(--green)"></i> Record Stock In</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="stock_in">
        <div class="form-group">
          <label class="form-label">Select Item *</label>
          <select name="item_id" class="form-control" required onchange="loadItemInfo(this)">
            <option value="">-- Select Item --</option>
            <?php while($r=$items_in->fetch_assoc()): ?>
            <option value="<?= $r['id'] ?>" data-qty="<?= $r['quantity'] ?>" data-unit="<?= htmlspecialchars($r['unit']) ?>" data-price="<?= $r['unit_price'] ?>">
              <?= sanitize($r['item_code']) ?> - <?= sanitize($r['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div id="itemInfo" style="display:none;background:var(--gray-50);padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid var(--gray-200)">
          <p style="font-size:.83rem;color:var(--gray-600)">Current Stock: <strong id="curStock" style="color:var(--navy)"></strong></p>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity to Add *</label>
          <input type="number" name="quantity" class="form-control" required min="1" placeholder="Enter quantity">
        </div>
        <div class="form-group">
          <label class="form-label">Unit Price (₱)</label>
          <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Reference No. (PO/DR)</label>
          <input type="text" name="reference_no" class="form-control" placeholder="e.g. PO-2024-001">
        </div>
        <div class="form-group">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
        </div>
        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;">
          <i class="fas fa-plus-circle"></i> Record Stock In
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-history" style="color:var(--gold)"></i> Recent Stock In</h2>
      <small style="color:var(--gray-600)"><?= $totalIn ?> total records</small>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Code</th><th>Item</th><th>Supplier</th><th>Qty</th><th>Ref No</th><th>Date</th></tr></thead>
        <tbody>
        <?php while($r=$recent_in->fetch_assoc()): ?>
        <tr>
          <td><span class="item-code"><?= sanitize($r['transaction_code']) ?></span></td>
          <td><?= sanitize($r['iname']) ?></td>
          <td style="font-size:.78rem;color:var(--gray-600)"><?= sanitize($r['sup_name'] ?? '-') ?></td>
          <td style="color:var(--green);font-weight:600">+<?= $r['quantity'] ?> <?= sanitize($r['unit']) ?></td>
          <td><?= sanitize($r['reference_no'] ?? '-') ?></td>
          <td style="font-size:.78rem;color:var(--gray-600)"><?= date('M d, Y g:i A', strtotime($r['transaction_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if($totalIn === 0): ?><tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:24px">No records found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if($pagesIn > 1): ?>
    <div class="pagination">
      <a href="?tab=stock_in&page=1" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-angle-double-left"></i></a>
      <a href="?tab=stock_in&page=<?= max(1,$page-1) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-angle-left"></i></a>
      <?php for($p=max(1,$page-2);$p<=min($pagesIn,$page+2);$p++): ?>
        <a href="?tab=stock_in&page=<?= $p ?>" class="pag-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="?tab=stock_in&page=<?= min($pagesIn,$page+1) ?>" class="pag-btn <?= $page>=$pagesIn?'disabled':'' ?>"><i class="fas fa-angle-right"></i></a>
      <a href="?tab=stock_in&page=<?= $pagesIn ?>" class="pag-btn <?= $page>=$pagesIn?'disabled':'' ?>"><i class="fas fa-angle-double-right"></i></a>
      <span style="font-size:.8rem;color:var(--gray-600);margin-left:6px">Page <?= $page ?> of <?= $pagesIn ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== STOCK OUT TAB ===== -->
<?php elseif($tab === 'stock_out'): ?>
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;align-items:start;">
  <div class="card">
    <div class="card-header"><h2><i class="fas fa-arrow-circle-up" style="color:var(--red)"></i> Record Stock Out</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="stock_out">
        <div class="form-group">
          <label class="form-label">Select Item *</label>
          <select name="item_id" class="form-control" required onchange="loadItemOut(this)">
            <option value="">-- Select Item --</option>
            <?php while($r=$items_out->fetch_assoc()): ?>
            <option value="<?= $r['id'] ?>" data-qty="<?= $r['quantity'] ?>" data-unit="<?= htmlspecialchars($r['unit']) ?>">
              <?= sanitize($r['item_code']) ?> - <?= sanitize($r['name']) ?> (<?= $r['quantity'] ?> available)
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div id="itemInfoOut" style="display:none;background:var(--gray-50);padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid var(--gray-200)">
          <p style="font-size:.83rem;color:var(--gray-600)">Available Stock: <strong id="curStockOut" style="color:var(--red)"></strong></p>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity to Release *</label>
          <input type="number" name="quantity" id="out_qty" class="form-control" required min="1" placeholder="Enter quantity">
        </div>
        <div class="form-group">
          <label class="form-label">Reference No. (Job Order / SI)</label>
          <input type="text" name="reference_no" class="form-control" placeholder="e.g. JO-2024-001">
        </div>
        <div class="form-group">
          <label class="form-label">Remarks / Issued To</label>
          <textarea name="remarks" class="form-control" rows="3" placeholder="e.g. Issued to project team..."></textarea>
        </div>
        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">
          <i class="fas fa-minus-circle"></i> Record Stock Out
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-history" style="color:var(--gold)"></i> Recent Stock Out</h2>
      <small style="color:var(--gray-600)"><?= $totalOut ?> total records</small>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Code</th><th>Item</th><th>Qty</th><th>Ref No</th><th>Date</th></tr></thead>
        <tbody>
        <?php while($r=$recent_out->fetch_assoc()): ?>
        <tr>
          <td><span class="item-code"><?= sanitize($r['transaction_code']) ?></span></td>
          <td><?= sanitize($r['iname']) ?></td>
          <td style="color:var(--red);font-weight:600">-<?= $r['quantity'] ?> <?= sanitize($r['unit']) ?></td>
          <td><?= sanitize($r['reference_no'] ?? '-') ?></td>
          <td style="font-size:.78rem;color:var(--gray-600)"><?= date('M d, Y g:i A', strtotime($r['transaction_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if($totalOut === 0): ?><tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:24px">No records found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if($pagesOut > 1): ?>
    <div class="pagination">
      <a href="?tab=stock_out&page=1" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-angle-double-left"></i></a>
      <a href="?tab=stock_out&page=<?= max(1,$page-1) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-angle-left"></i></a>
      <?php for($p=max(1,$page-2);$p<=min($pagesOut,$page+2);$p++): ?>
        <a href="?tab=stock_out&page=<?= $p ?>" class="pag-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="?tab=stock_out&page=<?= min($pagesOut,$page+1) ?>" class="pag-btn <?= $page>=$pagesOut?'disabled':'' ?>"><i class="fas fa-angle-right"></i></a>
      <a href="?tab=stock_out&page=<?= $pagesOut ?>" class="pag-btn <?= $page>=$pagesOut?'disabled':'' ?>"><i class="fas fa-angle-double-right"></i></a>
      <span style="font-size:.8rem;color:var(--gray-600);margin-left:6px">Page <?= $page ?> of <?= $pagesOut ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<script>
function loadItemInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  const info = document.getElementById('itemInfo');
  if (sel.value) {
    document.getElementById('curStock').textContent = opt.dataset.qty + ' ' + opt.dataset.unit;
    document.getElementById('unit_price').value = opt.dataset.price;
    info.style.display = 'block';
  } else { info.style.display = 'none'; }
}
function loadItemOut(sel) {
  const opt = sel.options[sel.selectedIndex];
  const info = document.getElementById('itemInfoOut');
  const qtyInput = document.getElementById('out_qty');
  if (sel.value) {
    document.getElementById('curStockOut').textContent = opt.dataset.qty + ' ' + opt.dataset.unit;
    qtyInput.max = opt.dataset.qty;
    info.style.display = 'block';
  } else { info.style.display = 'none'; }
}
</script>
<?php require_once '../includes/footer.php'; ?>
