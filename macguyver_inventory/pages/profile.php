<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'My Profile';
require_once '../includes/header.php';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fname = $db->real_escape_string(trim($_POST['full_name']));
        $email = $db->real_escape_string(trim($_POST['email'] ?? ''));
        if (empty($fname)) { $msg = "Full name is required."; $msgType = 'danger'; }
        else {
            $db->query("UPDATE users SET full_name='$fname', email='$email' WHERE id=$uid");
            $_SESSION['full_name'] = $fname;
            $msg = "Profile updated successfully!";
        }
    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $newpass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $userRow  = $db->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();
        if (!password_verify($current, $userRow['password'])) {
            $msg = "Current password is incorrect."; $msgType = 'danger';
        } elseif (strlen($newpass) < 6) {
            $msg = "New password must be at least 6 characters."; $msgType = 'danger';
        } elseif ($newpass !== $confirm) {
            $msg = "New passwords do not match."; $msgType = 'danger';
        } else {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password='$hashed' WHERE id=$uid");
            logActivity($uid, 'change_password', 'User changed their password');
            $msg = "Password changed successfully!";
        }
    }
}

$user = $db->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$activityCount = $db->query("SELECT COUNT(*) c FROM activity_logs WHERE user_id=$uid")->fetch_assoc()['c'];
$lastLogs = $db->query("SELECT * FROM activity_logs WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");
?>

<div class="page-header">
  <div><h1>My Profile</h1><p>Manage your account settings and password</p></div>
</div>

<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;">

  <!-- Profile Card -->
  <div class="card" style="text-align:center;padding:28px 20px;">
    <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;align-items:center;justify-content:center;margin:0 auto 14px;border:3px solid var(--gold);">
      <i class="fas fa-user" style="font-size:2rem;color:#fff;"></i>
    </div>
    <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:1.3rem;color:var(--navy);margin-bottom:4px;"><?= sanitize($user['full_name']) ?></h2>
    <div style="margin-bottom:6px;">
      <?php if($user['role']==='admin'): ?><span class="badge badge-navy">Admin</span>
      <?php elseif($user['role']==='staff'): ?><span class="badge badge-info">Staff</span>
      <?php else: ?><span class="badge badge-secondary">Viewer</span><?php endif; ?>
    </div>
    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:4px;"><i class="fas fa-user-tag" style="margin-right:5px;"></i><?= sanitize($user['username']) ?></p>
    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:4px;"><i class="fas fa-envelope" style="margin-right:5px;"></i><?= sanitize($user['email'] ?? 'No email set') ?></p>
    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:4px;"><i class="fas fa-id-badge" style="margin-right:5px;"></i>User ID: #<?= $uid ?></p>
    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:4px;"><i class="fas fa-calendar-alt" style="margin-right:5px;"></i>Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
    <p style="font-size:.82rem;color:var(--gray-600);"><i class="fas fa-clock" style="margin-right:5px;"></i>Last Login: <?= $user['last_login'] ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'N/A' ?></p>
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--gray-200);">
      <div style="font-size:1.4rem;font-weight:700;color:var(--navy);"><?= number_format($activityCount) ?></div>
      <div style="font-size:.75rem;color:var(--gray-600);">Total Actions Logged</div>
    </div>
  </div>

  <!-- Settings Forms -->
  <div>
    <!-- Update Profile -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h2><i class="fas fa-user-edit" style="color:var(--gold)"></i> Edit Profile</h2></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" value="<?= sanitize($user['email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= sanitize($user['username']) ?>" readonly style="background:var(--gray-100);cursor:not-allowed;">
            <small style="color:var(--gray-400);font-size:.75rem;">Username cannot be changed. Contact an admin if needed.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly style="background:var(--gray-100);cursor:not-allowed;">
          </div>
          <div style="text-align:right;">
            <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><h2><i class="fas fa-lock" style="color:var(--navy)"></i> Change Password</h2></div>
      <div class="card-body">
        <form method="POST" id="passwordForm">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label class="form-label">Current Password *</label>
            <div style="position:relative;">
              <input type="password" name="current_password" id="cur_pass" class="form-control" required style="padding-right:40px;">
              <button type="button" onclick="togglePass('cur_pass','cur_eye')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);"><i class="fas fa-eye" id="cur_eye"></i></button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">New Password * <small style="color:var(--gray-400)">(min. 6 characters)</small></label>
            <div style="position:relative;">
              <input type="password" name="new_password" id="new_pass" class="form-control" required minlength="6" style="padding-right:40px;" oninput="checkStrength(this.value)">
              <button type="button" onclick="togglePass('new_pass','new_eye')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);"><i class="fas fa-eye" id="new_eye"></i></button>
            </div>
            <div id="strengthBar" style="height:4px;border-radius:4px;margin-top:6px;transition:all .3s;background:var(--gray-200);"></div>
            <div id="strengthLabel" style="font-size:.72rem;color:var(--gray-400);margin-top:3px;"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password *</label>
            <div style="position:relative;">
              <input type="password" name="confirm_password" id="conf_pass" class="form-control" required style="padding-right:40px;">
              <button type="button" onclick="togglePass('conf_pass','conf_eye')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);"><i class="fas fa-eye" id="conf_eye"></i></button>
            </div>
          </div>
          <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.8rem;color:var(--gray-600);">
            <i class="fas fa-shield-alt" style="color:var(--navy);margin-right:6px;"></i>
            <strong>Password is hashed (bcrypt)</strong> — your password is stored securely and cannot be read by anyone, including administrators.
          </div>
          <div style="text-align:right;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Recent Activity -->
<div class="card" style="margin-top:24px;">
  <div class="card-header"><h2><i class="fas fa-history" style="color:var(--gold)"></i> My Recent Activity</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Action</th><th>Description</th><th>Date & Time</th></tr></thead>
      <tbody>
      <?php while($la=$lastLogs->fetch_assoc()): ?>
      <tr>
        <td><?php $ac=['login'=>'success','logout'=>'secondary','add_item'=>'info','edit_item'=>'warning','delete_item'=>'danger','stock_in'=>'success','stock_out'=>'danger','change_password'=>'navy']; $c=$ac[$la['action']]??'navy'; ?><span class="badge badge-<?= $c ?>"><?= sanitize(str_replace('_',' ',$la['action'])) ?></span></td>
        <td><?= sanitize($la['description']) ?></td>
        <td style="font-size:.78rem;color:var(--gray-600)"><?= date('M d, Y g:i A', strtotime($la['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function togglePass(inputId, iconId) {
  var input = document.getElementById(inputId);
  var icon  = document.getElementById(iconId);
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fas fa-eye'; }
}
function checkStrength(val) {
  var bar = document.getElementById('strengthBar');
  var lbl = document.getElementById('strengthLabel');
  if (!val) { bar.style.background='var(--gray-200)'; bar.style.width='100%'; lbl.textContent=''; return; }
  var score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  var colors = ['#e74c3c','#e74c3c','#f39c12','#3498db','#2ecc71'];
  var labels = ['Very Weak','Weak','Fair','Strong','Very Strong'];
  bar.style.background = colors[score-1] || '#e74c3c';
  lbl.textContent = 'Password strength: ' + (labels[score-1]||'Very Weak');
  lbl.style.color = colors[score-1] || '#e74c3c';
}
</script>
<?php require_once '../includes/footer.php'; ?>
