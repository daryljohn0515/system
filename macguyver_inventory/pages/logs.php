<?php
define('BASE_URL', '/macguyver_inventory/');
$pageTitle = 'Activity Logs';
require_once '../includes/header.php';
if (!isAdmin()) { header('Location: ../dashboard.php'); exit(); }
$db = getDB();

$search = $db->real_escape_string($_GET['search'] ?? '');
$where = "WHERE 1=1";
if ($search) $where .= " AND (l.action LIKE '%$search%' OR l.description LIKE '%$search%' OR u.full_name LIKE '%$search%')";

$logs = $db->query("SELECT l.*,u.full_name,u.username FROM activity_logs l LEFT JOIN users u ON l.user_id=u.id $where ORDER BY l.created_at DESC LIMIT 200");
?>
<div class="page-header">
  <div><h1>Activity Logs</h1><p>System activity and audit trail</p></div>
  <button onclick="printPage()" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
</div>
<div class="card">
  <div class="card-header" style="gap:12px;flex-wrap:wrap;">
    <h2><i class="fas fa-history" style="color:var(--gold)"></i> Activity Logs</h2>
    <form method="GET" style="display:flex;gap:8px">
      <input type="text" name="search" class="search-input" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      <?php if($search): ?><a href="logs.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>User</th><th>Action</th><th>Description</th><th>IP Address</th><th>Date & Time</th></tr></thead>
      <tbody>
      <?php $i=1; while($r=$logs->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td>
          <strong><?= sanitize($r['full_name'] ?? 'System') ?></strong><br>
          <small class="item-code"><?= sanitize($r['username'] ?? '') ?></small>
        </td>
        <td>
          <?php
          $actionColors = ['login'=>'success','logout'=>'secondary','add_item'=>'info','edit_item'=>'warning','delete_item'=>'danger','stock_in'=>'success','stock_out'=>'danger'];
          $color = $actionColors[$r['action']] ?? 'navy';
          ?>
          <span class="badge badge-<?= $color ?>"><?= sanitize(str_replace('_',' ',$r['action'])) ?></span>
        </td>
        <td><?= sanitize($r['description']) ?></td>
        <td><span class="item-code"><?= sanitize($r['ip_address']) ?></span></td>
        <td style="font-size:.78rem;color:var(--gray-600);white-space:nowrap"><?= date('M d, Y g:i A', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if($logs->num_rows===0): ?><tr><td colspan="6"><div class="empty-state"><i class="fas fa-search"></i><p>No logs found.</p></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
