<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Users';
require_once '../includes/header.php';
if (!isAdmin()) { header('Location: ../dashboard.php'); exit(); }
$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $uname = $db->real_escape_string(trim($_POST['username']));
        $fname = $db->real_escape_string(trim($_POST['full_name']));
        $email = $db->real_escape_string(trim($_POST['email'] ?? ''));
        $role  = $db->real_escape_string($_POST['role'] ?? 'staff');
        $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $check = $db->query("SELECT id FROM users WHERE username='$uname'")->num_rows;
        if ($check > 0) { $msg = "Username already exists!"; $msgType = 'danger'; }
        else {
            $db->query("INSERT INTO users (username,password,full_name,email,role) VALUES ('$uname','$pass','$fname','$email','$role')");
            $msg = "User '$uname' created!";
        }
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['user_id'];
        $fname = $db->real_escape_string(trim($_POST['full_name']));
        $email = $db->real_escape_string(trim($_POST['email'] ?? ''));
        $role  = $db->real_escape_string($_POST['role'] ?? 'staff');
        $active= (int)($_POST['is_active'] ?? 1);
        $db->query("UPDATE users SET full_name='$fname',email='$email',role='$role',is_active=$active WHERE id=$id");
        if (!empty($_POST['new_password'])) {
            $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password='$pass' WHERE id=$id");
        }
        $msg = "User updated!";
    } elseif ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        if ($id == $_SESSION['user_id']) { $msg = "You cannot delete your own account!"; $msgType = 'danger'; }
        else { $db->query("DELETE FROM users WHERE id=$id"); $msg = "User deleted."; $msgType = 'warning'; }
    }
}
$users = $db->query("SELECT * FROM users ORDER BY role,full_name");
?>
<div class="page-header">
  <div><h1>User Management</h1><p>Manage system users and access levels</p></div>
  <button class="btn btn-gold" onclick="openModal('addUserModal')"><i class="fas fa-user-plus"></i> Add User</button>
</div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-check-circle"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><h2><i class="fas fa-users" style="color:var(--gold)"></i> All Users</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$users->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= sanitize($r['full_name']) ?></strong></td>
        <td><span class="item-code"><?= sanitize($r['username']) ?></span></td>
        <td><?= sanitize($r['email'] ?? '-') ?></td>
        <td>
          <?php if($r['role']==='admin'): ?><span class="badge badge-navy">Admin</span>
          <?php elseif($r['role']==='staff'): ?><span class="badge badge-info">Staff</span>
          <?php else: ?><span class="badge badge-secondary">Viewer</span><?php endif; ?>
        </td>
        <td><?= $r['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></td>
        <td style="font-size:.78rem;color:var(--gray-600)"><?= $r['last_login'] ? date('M d, Y g:i A', strtotime($r['last_login'])) : 'Never' ?></td>
        <td>
          <button class="btn btn-sm btn-primary" onclick='editUser(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
          <?php if($r['id'] != $_SESSION['user_id']): ?>
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $r['id'] ?>">
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

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-user-plus"></i> Add New User</h3><button class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Username *</label><input type="text" name="username" class="form-control" required></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-control"><option value="staff">Staff</option><option value="admin">Admin</option><option value="viewer">Viewer</option></select>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button><button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Create User</button></div>
  </form></div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal"><div class="modal-header"><h3><i class="fas fa-user-edit"></i> Edit User</h3><button class="modal-close" onclick="closeModal('editUserModal')"><i class="fas fa-times"></i></button></div>
  <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="user_id" id="eu_id">
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="eu_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="eu_email" class="form-control"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" id="eu_role" class="form-control"><option value="staff">Staff</option><option value="admin">Admin</option><option value="viewer">Viewer</option></select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="is_active" id="eu_active" class="form-control"><option value="1">Active</option><option value="0">Inactive</option></select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">New Password <small style="color:var(--gray-400)">(leave blank to keep current)</small></label><input type="password" name="new_password" class="form-control" placeholder="Enter new password or leave blank"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button></div>
  </form></div>
</div>
<script>
function editUser(r) {
  document.getElementById('eu_id').value=r.id;
  document.getElementById('eu_name').value=r.full_name;
  document.getElementById('eu_email').value=r.email||'';
  document.getElementById('eu_role').value=r.role;
  document.getElementById('eu_active').value=r.is_active;
  openModal('editUserModal');
}
</script>
<?php require_once '../includes/footer.php'; ?>
