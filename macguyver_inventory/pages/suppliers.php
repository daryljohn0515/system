<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Suppliers';
require_once '../includes/header.php';
$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = $db->real_escape_string(trim($_POST['name']));
        $cp   = $db->real_escape_string(trim($_POST['contact_person'] ?? ''));
        $phone= $db->real_escape_string(trim($_POST['phone'] ?? ''));
        $email= $db->real_escape_string(trim($_POST['email'] ?? ''));
        $addr = $db->real_escape_string(trim($_POST['address'] ?? ''));
        if ($action === 'add') {
            $db->query("INSERT INTO suppliers (name,contact_person,phone,email,address) VALUES ('$name','$cp','$phone','$email','$addr')");
            $msg = "Supplier '$name' added!";
        } else {
            $id = (int)$_POST['sup_id'];
            $db->query("UPDATE suppliers SET name='$name',contact_person='$cp',phone='$phone',email='$email',address='$addr' WHERE id=$id");
            $msg = "Supplier updated!";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['sup_id'];
        $db->query("DELETE FROM suppliers WHERE id=$id");
        $msg = "Supplier deleted."; $msgType = 'warning';
    }
}
$sups = $db->query("SELECT s.*,(SELECT COUNT(*) FROM items i WHERE i.supplier_id=s.id) item_count FROM suppliers s ORDER BY s.name");
?>
<div class="page-header">
  <div><h1>Suppliers</h1><p>Manage supplier information</p></div>
  <button class="btn btn-gold" onclick="openModal('addSupModal')"><i class="fas fa-plus"></i> Add Supplier</button>
</div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-check-circle"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-truck" style="color:var(--gold)"></i> All Suppliers</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Supplier Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Address</th><th>Items</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$sups->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= sanitize($r['name']) ?></strong></td>
        <td><?= sanitize($r['contact_person'] ?? '-') ?></td>
        <td><?= sanitize($r['phone'] ?? '-') ?></td>
        <td><?= sanitize($r['email'] ?? '-') ?></td>
        <td><?= sanitize($r['address'] ?? '-') ?></td>
        <td><span class="badge badge-navy"><?= $r['item_count'] ?></span></td>
        <td>
          <button class="btn btn-sm btn-primary" onclick='editSup(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
          <?php if(isAdmin()): ?>
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="sup_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addSupModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-plus"></i> Add Supplier</h3><button class="modal-close" onclick="closeModal('addSupModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Supplier Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addSupModal')">Cancel</button><button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save</button></div>
  </form></div>
</div>

<div class="modal-overlay" id="editSupModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Supplier</h3><button class="modal-close" onclick="closeModal('editSupModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="sup_id" id="esup_id">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Supplier Name *</label><input type="text" name="name" id="esup_name" class="form-control" required></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Contact Person</label><input type="text" name="contact_person" id="esup_cp" class="form-control"></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="esup_phone" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="esup_email" class="form-control"></div>
      <div class="form-group"><label class="form-label">Address</label><textarea name="address" id="esup_addr" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editSupModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button></div>
  </form></div>
</div>
<script>
function editSup(r) {
  document.getElementById('esup_id').value=r.id;
  document.getElementById('esup_name').value=r.name;
  document.getElementById('esup_cp').value=r.contact_person||'';
  document.getElementById('esup_phone').value=r.phone||'';
  document.getElementById('esup_email').value=r.email||'';
  document.getElementById('esup_addr').value=r.address||'';
  openModal('editSupModal');
}
</script>
<?php require_once '../includes/footer.php'; ?>
