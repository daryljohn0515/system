<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Inventory Items';
require_once '../includes/header.php';
$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = $db->real_escape_string(trim($_POST['name']));
        $code = $action === 'add' ? $db->real_escape_string(trim($_POST['item_code'])) : '';
        $desc = $db->real_escape_string(trim($_POST['description'] ?? ''));
        $cat  = (int)($_POST['category_id'] ?? 0);
        $sup  = (int)($_POST['supplier_id'] ?? 0);
        $unit = $db->real_escape_string(trim($_POST['unit'] ?? 'pcs'));
        $qty  = (int)($_POST['quantity'] ?? 0);
        $rol  = (int)($_POST['reorder_level'] ?? 5);
        $price= (float)($_POST['unit_price'] ?? 0);
        $loc  = $db->real_escape_string(trim($_POST['location'] ?? ''));
        $status = $db->real_escape_string($_POST['status'] ?? 'active');
        if ($action === 'add') {
            if (empty($code)) $code = generateCode('ITM');
            $db->query("INSERT INTO items (item_code,name,description,category_id,supplier_id,unit,quantity,reorder_level,unit_price,location,status) VALUES ('$code','$name','$desc',$cat,$sup,'$unit',$qty,$rol,$price,'$loc','$status')");
            logActivity($_SESSION['user_id'], 'add_item', "Added item: $name");
            $msg = "Item '$name' added successfully!";
        } else {
            $id = (int)$_POST['item_id'];
            $db->query("UPDATE items SET name='$name',description='$desc',category_id=$cat,supplier_id=$sup,unit='$unit',reorder_level=$rol,unit_price=$price,location='$loc',status='$status' WHERE id=$id");
            logActivity($_SESSION['user_id'], 'edit_item', "Edited item: $name");
            $msg = "Item '$name' updated!";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['item_id'];
        $item = $db->query("SELECT name FROM items WHERE id=$id")->fetch_assoc();
        $db->query("DELETE FROM items WHERE id=$id");
        logActivity($_SESSION['user_id'], 'delete_item', "Deleted item: " . ($item['name'] ?? ''));
        $msg = "Item deleted."; $msgType = 'warning';
    }
}

$filter = $_GET['filter'] ?? '';
$search = $db->real_escape_string($_GET['search'] ?? '');
$where = "WHERE 1=1";
if ($filter === 'low') $where .= " AND i.quantity <= i.reorder_level";
if ($filter === 'out') $where .= " AND i.quantity = 0";
if ($search) $where .= " AND (i.name LIKE '%$search%' OR i.item_code LIKE '%$search%')";
$items = $db->query("SELECT i.*,c.name cat_name,s.name sup_name FROM items i LEFT JOIN categories c ON i.category_id=c.id LEFT JOIN suppliers s ON i.supplier_id=s.id $where ORDER BY i.updated_at DESC");
$cats = $db->query("SELECT * FROM categories ORDER BY name");
$sups = $db->query("SELECT * FROM suppliers ORDER BY name");
?>

<!-- Barcode library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>

<div class="page-header">
  <div><h1>Inventory Items</h1><p>Manage all inventory items and stock levels</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
<?php if(isAdmin() || $_SESSION['role']==='staff'): ?>
    <button class="btn btn-gold" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Item</button>
    <?php endif; ?>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-check-circle"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-boxes" style="color:var(--gold)"></i> All Items (<?= $items->num_rows ?>)</h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <form method="GET" style="display:flex;gap:8px">
        <input type="text" name="search" class="search-input" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>">
        <select name="filter" class="form-control" style="width:auto">
          <option value="">All Items</option>
          <option value="low" <?= $filter==='low'?'selected':'' ?>>Low Stock</option>
          <option value="out" <?= $filter==='out'?'selected':'' ?>>Out of Stock</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <?php if($search||$filter): ?><a href="items.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
      </form>
    </div>
  </div>
  <div class="table-responsive">
    <table id="itemsTable">
      <thead><tr><th>#</th><th>Barcode</th><th>Code</th><th>Name</th><th>Category</th><th>Unit Price</th><th>Qty</th><th>Reorder Lvl</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$items->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td style="text-align:center;">
          <svg class="barcode-svg"
            id="bc-<?= $r['id'] ?>"
            data-code="<?= sanitize($r['item_code']) ?>"
            onclick="showBarcode(<?= json_encode($r) ?>)"
            title="Click to enlarge barcode"
            style="cursor:pointer;display:block;margin:0 auto;">
          </svg>
        </td>
        <td><span class="item-code"><?= sanitize($r['item_code']) ?></span></td>
        <td><strong><?= sanitize($r['name']) ?></strong><br><small style="color:var(--gray-400)"><?= sanitize($r['description'] ?? '') ?></small></td>
        <td><?= sanitize($r['cat_name'] ?? '-') ?></td>
        <td><?= formatCurrency($r['unit_price']) ?></td>
        <td class="<?= $r['quantity']==0?'out-stock':($r['quantity']<=$r['reorder_level']?'low-stock':'') ?>">
          <?= $r['quantity'] ?> <?= sanitize($r['unit']) ?>
        </td>
        <td><?= $r['reorder_level'] ?></td>
        <td><?= sanitize($r['location'] ?? '-') ?></td>
        <td>
          <?php if($r['status']==='active'): ?><span class="badge badge-success">Active</span>
          <?php elseif($r['status']==='inactive'): ?><span class="badge badge-secondary">Inactive</span>
          <?php else: ?><span class="badge badge-danger">Discontinued</span><?php endif; ?>
        </td>
        <td>
          <button class="btn btn-sm btn-primary" onclick='editItem(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
          <?php if(isAdmin()): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="item_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if($items->num_rows===0): ?><tr><td colspan="11"><div class="empty-state"><i class="fas fa-inbox"></i><p>No items found.</p></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── BARCODE MODAL ────────────────────────────── -->
<div class="modal-overlay" id="barcodeModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <h3><i class="fas fa-barcode"></i> Item Barcode</h3>
      <button class="modal-close" onclick="closeModal('barcodeModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" style="padding:24px;text-align:center;">
      <div id="bcItemName" style="font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:800;color:var(--navy);margin-bottom:4px;"></div>
      <div id="bcItemCode" style="font-family:monospace;font-size:.78rem;background:var(--gray-100);border:1px solid var(--gray-200);display:inline-block;padding:3px 12px;border-radius:4px;color:var(--navy);margin-bottom:18px;letter-spacing:.5px;"></div>
      <!-- barcode display -->
      <div style="background:#fff;padding:18px 20px 12px;border-radius:12px;border:2px solid var(--navy);display:inline-block;box-shadow:0 4px 20px rgba(0,0,0,.1);">
        <svg id="bcLarge" style="display:block;max-width:100%;"></svg>
      </div>
      <div style="margin-top:12px;font-size:.78rem;color:var(--gray-600);">
        🔍 Scan with any barcode reader or scanner gun
      </div>
      <div id="bcItemMeta" style="margin-top:8px;font-size:.74rem;color:var(--gray-400);line-height:1.7;"></div>
      <div style="margin-top:20px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
        <button class="btn btn-primary btn-sm" onclick="printBarcode()"><i class="fas fa-print"></i> Print</button>
        <button class="btn btn-gold btn-sm" onclick="downloadBarcode()"><i class="fas fa-download"></i> Download</button>
        <button class="btn btn-secondary btn-sm" onclick="closeModal('barcodeModal')"><i class="fas fa-times"></i> Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-plus"></i> Add New Item</h3>
      <button class="modal-close" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Item Code</label><input type="text" name="item_code" class="form-control" placeholder="Auto-generated if blank"></div>
          <div class="form-group"><label class="form-label">Item Name *</label><input type="text" name="name" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Category</label>
            <select name="category_id" class="form-control"><option value="0">-- Select --</option>
            <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?><option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option><?php endwhile; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-control"><option value="0">-- Select --</option>
            <?php $sups->data_seek(0); while($s=$sups->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option><?php endwhile; ?>
            </select></div>
        </div>
        <div class="form-grid-3">
          <div class="form-group"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" value="pcs"></div>
          <div class="form-group"><label class="form-label">Initial Qty</label><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
          <div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="5" min="0"></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Unit Price (₱)</label><input type="number" name="unit_price" class="form-control" value="0" step="0.01" min="0"></div>
          <div class="form-group"><label class="form-label">Storage Location</label><input type="text" name="location" class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="discontinued">Discontinued</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Item</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-edit"></i> Edit Item</h3>
      <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="item_id" id="edit_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Item Code</label><input type="text" id="edit_code" class="form-control" readonly style="background:var(--gray-100)"></div>
          <div class="form-group"><label class="form-label">Item Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Category</label>
            <select name="category_id" id="edit_cat" class="form-control"><option value="0">-- Select --</option>
            <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?><option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option><?php endwhile; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Supplier</label>
            <select name="supplier_id" id="edit_sup" class="form-control"><option value="0">-- Select --</option>
            <?php $sups->data_seek(0); while($s=$sups->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option><?php endwhile; ?>
            </select></div>
        </div>
        <div class="form-grid-3">
          <div class="form-group"><label class="form-label">Unit</label><input type="text" name="unit" id="edit_unit" class="form-control"></div>
          <div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" id="edit_rol" class="form-control" min="0"></div>
          <div class="form-group"><label class="form-label">Unit Price (₱)</label><input type="number" name="unit_price" id="edit_price" class="form-control" step="0.01" min="0"></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Storage Location</label><input type="text" name="location" id="edit_loc" class="form-control"></div>
          <div class="form-group"><label class="form-label">Status</label>
            <select name="status" id="edit_status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="discontinued">Discontinued</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Item</button>
      </div>
    </form>
  </div>
</div>

<script>
var currentBarcodeItem = null;

function editItem(r) {
  document.getElementById('edit_id').value    = r.id;
  document.getElementById('edit_code').value  = r.item_code;
  document.getElementById('edit_name').value  = r.name;
  document.getElementById('edit_desc').value  = r.description || '';
  document.getElementById('edit_cat').value   = r.category_id || 0;
  document.getElementById('edit_sup').value   = r.supplier_id || 0;
  document.getElementById('edit_unit').value  = r.unit;
  document.getElementById('edit_rol').value   = r.reorder_level;
  document.getElementById('edit_price').value = r.unit_price;
  document.getElementById('edit_loc').value   = r.location || '';
  document.getElementById('edit_status').value = r.status;
  openModal('editModal');
}

/* ── Render all inline barcodes in the table ── */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.barcode-svg').forEach(function(svg) {
    var code = svg.getAttribute('data-code');
    try {
      JsBarcode(svg, code, {
        format:      'CODE128',
        width:       1.4,
        height:      36,
        displayValue: false,
        margin:      4,
        lineColor:   '#1e2d54',
        background:  '#ffffff'
      });
    } catch(e) { svg.style.display = 'none'; }
  });
});

/* ── Show large barcode modal ── */
function showBarcode(item) {
  currentBarcodeItem = item;
  document.getElementById('bcItemName').textContent = item.name;
  document.getElementById('bcItemCode').textContent = item.item_code;
  var meta = [];
  if (item.cat_name) meta.push(item.cat_name);
  if (item.location) meta.push('📍 ' + item.location);
  meta.push('Qty: ' + item.quantity + ' ' + (item.unit || ''));
  if (item.unit_price > 0) meta.push('₱ ' + parseFloat(item.unit_price).toFixed(2));
  document.getElementById('bcItemMeta').textContent = meta.join('  ·  ');

  // render big barcode
  var svg = document.getElementById('bcLarge');
  JsBarcode(svg, item.item_code, {
    format:       'CODE128',
    width:        2.8,
    height:       90,
    displayValue: true,
    fontSize:     14,
    fontOptions:  'bold',
    textMargin:   6,
    lineColor:    '#000000',
    background:   '#ffffff',
    margin:       10
  });
  openModal('barcodeModal');
}

/* ── Print barcode label ── */
function printBarcode() {
  if (!currentBarcodeItem) return;
  var svgEl  = document.getElementById('bcLarge');
  var svgStr = new XMLSerializer().serializeToString(svgEl);
  var svgB64 = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgStr)));
  var price  = parseFloat(currentBarcodeItem.unit_price || 0).toFixed(2);
  var status = currentBarcodeItem.status || 'active';
  var badgeColor = status==='active' ? '#27ae60' : (status==='inactive' ? '#95a5a6' : '#e74c3c');

  var html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
    + '<title>Barcode — ' + currentBarcodeItem.item_code + '</title>'
    + '<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">'
    + '<style>*{box-sizing:border-box;margin:0;padding:0}'
    + 'body{font-family:Barlow,sans-serif;background:#fff;display:flex;justify-content:center;padding:12px;}'
    + '.label{width:80mm;border:1.5px solid #1e2d54;border-radius:8px;overflow:hidden;}'
    + '.lhead{background:#1e2d54;padding:7px 12px;display:flex;align-items:center;gap:8px;}'
    + '.lhead-name{font-family:"Barlow Condensed",sans-serif;font-size:13px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#e8c347;}'
    + '.lhead-sub{font-size:9px;color:rgba(255,255,255,.6);letter-spacing:.5px;}'
    + '.lgold{height:3px;background:linear-gradient(90deg,#c9a227,#e8c347,#c9a227);}'
    + '.lbody{padding:10px 12px;text-align:center;}'
    + '.lbarcode img{width:100%;height:auto;}'
    + '.lname{font-family:"Barlow Condensed",sans-serif;font-size:15px;font-weight:800;color:#1e2d54;margin:6px 0 3px;line-height:1.2;}'
    + '.lmeta{font-size:9px;color:#5a6580;line-height:1.7;}'
    + '.lmeta strong{color:#1e2d54;}'
    + '.lfooter{background:#f8f9fc;border-top:1px solid #e2e6f0;padding:5px 12px;display:flex;justify-content:space-between;align-items:center;}'
    + '.lstatus{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:2px 8px;border-radius:20px;background:' + badgeColor + '22;color:' + badgeColor + ';}'
    + '.lprice{font-size:9px;color:#5a6580;}'
    + '@media print{@page{size:80mm auto;margin:2mm}body{padding:0}}'
    + '</style></head><body><div class="label">'
    + '<div class="lhead"><div><div class="lhead-name">MacGuyver</div><div class="lhead-sub">Engineering Services</div></div></div>'
    + '<div class="lgold"></div>'
    + '<div class="lbody">'
    + '<div class="lbarcode"><img src="' + svgB64 + '"></div>'
    + '<div class="lname">' + currentBarcodeItem.name + '</div>'
    + '<div class="lmeta">';
  if (currentBarcodeItem.cat_name) html += 'Category: <strong>' + currentBarcodeItem.cat_name + '</strong><br>';
  if (currentBarcodeItem.location) html += 'Location: <strong>' + currentBarcodeItem.location + '</strong>';
  html += '</div></div>'
    + '<div class="lfooter"><span class="lstatus">' + status + '</span>'
    + '<span class="lprice">&#8369; ' + price + ' / ' + (currentBarcodeItem.unit || 'unit') + '</span></div>'
    + '</div><script>window.onload=function(){window.print();}<\/script>'
    + '</body></html>';

  var win = window.open('', '_blank', 'width=360,height=520');
  win.document.open();
  win.document.write(html);
  win.document.close();
}

/* ── Download barcode as PNG ── */
function downloadBarcode() {
  var svgEl  = document.getElementById('bcLarge');
  var svgStr = new XMLSerializer().serializeToString(svgEl);
  var svgBlob = new Blob([svgStr], {type:'image/svg+xml;charset=utf-8'});
  var url  = URL.createObjectURL(svgBlob);
  var img  = new Image();
  img.onload = function() {
    var W = 320, H = 200;
    var oc  = document.createElement('canvas');
    oc.width  = W * 2; oc.height = H * 2;
    var ctx = oc.getContext('2d');
    ctx.scale(2, 2);
    // white bg
    ctx.fillStyle = '#fff'; ctx.fillRect(0,0,W,H);
    // navy header
    ctx.fillStyle = '#1e2d54'; ctx.fillRect(0,0,W,36);
    ctx.fillStyle = '#e8c347';
    ctx.font = 'bold 13px Arial'; ctx.textAlign = 'center';
    ctx.fillText('MacGuyver Enterprises', W/2, 22);
    ctx.fillStyle = 'rgba(255,255,255,.6)';
    ctx.font = '9px Arial';
    ctx.fillText('Engineering Services', W/2, 33);
    // gold bar
    var g = ctx.createLinearGradient(0,36,W,36);
    g.addColorStop(0,'#c9a227'); g.addColorStop(.5,'#e8c347'); g.addColorStop(1,'#c9a227');
    ctx.fillStyle = g; ctx.fillRect(0,36,W,3);
    // barcode
    ctx.drawImage(img, 10, 44, W-20, 100);
    // item name
    ctx.fillStyle = '#1e2d54';
    ctx.font = 'bold 12px Arial'; ctx.textAlign = 'center';
    ctx.fillText(currentBarcodeItem.name.substring(0,38), W/2, 158);
    ctx.fillStyle = '#5a6580'; ctx.font = '9px Arial';
    ctx.fillText(currentBarcodeItem.item_code, W/2, 172);
    // border
    ctx.strokeStyle = '#e2e6f0'; ctx.lineWidth = 1;
    ctx.strokeRect(.5,.5,W-1,H-1);
    URL.revokeObjectURL(url);
    oc.toBlob(function(blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'barcode-' + currentBarcodeItem.item_code + '.png';
      document.body.appendChild(a); a.click();
      document.body.removeChild(a);
    }, 'image/png');
  };
  img.src = url;
}
</script>
<?php require_once '../includes/footer.php'; ?>
