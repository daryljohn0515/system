<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Categories';
require_once '../includes/header.php';
$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = $db->real_escape_string(trim($_POST['name']));
        $desc = $db->real_escape_string(trim($_POST['description'] ?? ''));
        if ($action === 'add') {
            $db->query("INSERT INTO categories (name,description) VALUES ('$name','$desc')");
            $msg = "Category '$name' added!";
        } else {
            $id = (int)$_POST['cat_id'];
            $db->query("UPDATE categories SET name='$name',description='$desc' WHERE id=$id");
            $msg = "Category updated!";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['cat_id'];
        $db->query("DELETE FROM categories WHERE id=$id");
        $msg = "Category deleted."; $msgType = 'warning';
    }
}

$cats = $db->query("SELECT c.*,(SELECT COUNT(*) FROM items i WHERE i.category_id=c.id) item_count FROM categories c ORDER BY c.name");
?>
<div class="page-header">
  <div><h1>Categories</h1><p>Manage item categories</p></div>
  <button class="btn btn-gold" onclick="openModal('addCatModal')"><i class="fas fa-plus"></i> Add Category</button>
</div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-check-circle"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-tag" style="color:var(--gold)"></i> All Categories</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Category Name</th><th>Description</th><th>Items</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$cats->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= sanitize($r['name']) ?></strong></td>
        <td><?= sanitize($r['description'] ?? '-') ?></td>
        <td><span class="badge badge-navy"><?= $r['item_count'] ?> items</span></td>
        <td>
          <button class="btn btn-sm btn-primary" onclick='editCat(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
          <?php if(isAdmin() && $r['item_count']==0): ?>
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="cat_id" value="<?= $r['id'] ?>">
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

<!-- Add Modal -->
<div class="modal-overlay" id="addCatModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-plus"></i> Add Category</h3><button class="modal-close" onclick="closeModal('addCatModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Category Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addCatModal')">Cancel</button><button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save</button></div>
  </form></div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editCatModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Category</h3><button class="modal-close" onclick="closeModal('editCatModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="cat_id" id="ecat_id">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Category Name *</label><input type="text" name="name" id="ecat_name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="ecat_desc" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editCatModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button></div>
  </form></div>
</div>
<script>
function editCat(r) {
  document.getElementById('ecat_id').value = r.id;
  document.getElementById('ecat_name').value = r.name;
  document.getElementById('ecat_desc').value = r.description || '';
  openModal('editCatModal');
}
</script>
<?php require_once '../includes/footer.php'; ?>
