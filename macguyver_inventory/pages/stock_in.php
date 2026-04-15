<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Stock In';
require_once '../includes/header.php';
$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $qty = (int)$_POST['quantity'];
    $ref = $db->real_escape_string(trim($_POST['reference_no'] ?? ''));
    $remarks = $db->real_escape_string(trim($_POST['remarks'] ?? ''));
    $price = (float)$_POST['unit_price'];

    if ($item_id > 0 && $qty > 0) {
        $item = $db->query("SELECT * FROM items WHERE id=$item_id")->fetch_assoc();
        if ($item) {
            $before = $item['quantity'];
            $after = $before + $qty;
            $db->query("UPDATE items SET quantity=$after WHERE id=$item_id");
            $code = generateCode('TXN');
            $uid = (int)$_SESSION['user_id'];
            $db->query("INSERT INTO transactions (transaction_code,item_id,type,quantity,quantity_before,quantity_after,unit_price,reference_no,remarks,performed_by) VALUES ('$code',$item_id,'stock_in',$qty,$before,$after,$price,'$ref','$remarks',$uid)");
            logActivity($uid, 'stock_in', "Stock in: {$item['name']} +$qty");
            $msg = "Stock In successful! Added $qty {$item['unit']} of '{$item['name']}'.";
        }
    } else {
        $msg = "Please select an item and enter a valid quantity."; $msgType = 'danger';
    }
}

$items = $db->query("SELECT * FROM items WHERE status='active' ORDER BY name");
$recent = $db->query("SELECT t.*,i.name iname,i.unit FROM transactions t LEFT JOIN items i ON t.item_id=i.id WHERE t.type='stock_in' ORDER BY t.transaction_date DESC LIMIT 10");
?>
<div class="page-header">
  <div><h1>Stock In</h1><p>Record incoming inventory / receiving of items</p></div>
</div>

<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;align-items:start;">
  <div class="card">
    <div class="card-header"><h2><i class="fas fa-arrow-circle-down" style="color:var(--green)"></i> Record Stock In</h2></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Select Item *</label>
          <select name="item_id" class="form-control" required onchange="loadItemInfo(this)">
            <option value="">-- Select Item --</option>
            <?php while($r=$items->fetch_assoc()): ?>
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
    <div class="card-header"><h2><i class="fas fa-history" style="color:var(--gold)"></i> Recent Stock In</h2></div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Code</th><th>Item</th><th>Qty</th><th>Ref No</th><th>Date</th></tr></thead>
        <tbody>
        <?php while($r=$recent->fetch_assoc()): ?>
        <tr>
          <td><span class="item-code"><?= sanitize($r['transaction_code']) ?></span></td>
          <td><?= sanitize($r['iname']) ?></td>
          <td style="color:var(--green);font-weight:600">+<?= $r['quantity'] ?> <?= sanitize($r['unit']) ?></td>
          <td><?= sanitize($r['reference_no'] ?? '-') ?></td>
          <td style="font-size:.78rem;color:var(--gray-600)"><?= date('M d, Y g:i A', strtotime($r['transaction_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
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
</script>
<?php require_once '../includes/footer.php'; ?>
