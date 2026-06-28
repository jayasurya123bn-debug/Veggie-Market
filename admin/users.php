<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || !isAdmin()) { header('Location: ../index.php'); exit(); }

$msg = '';

// Ban / Unban user
if (isset($_POST['toggle_ban'])) {
    $tid = (int)$_POST['target_id'];
    $new_ban = (int)$_POST['new_ban'];
    if ($tid !== (int)$_SESSION['user_id']) { // prevent self-ban
        $conn->query("UPDATE users SET is_banned=$new_ban WHERE id=$tid");
        $msg = $new_ban ? 'banned' : 'unbanned';
    }
}

// Toggle admin
if (isset($_POST['toggle_admin'])) {
    $tid = (int)$_POST['target_id'];
    $new_admin = (int)$_POST['new_admin'];
    if ($tid !== (int)$_SESSION['user_id']) {
        $conn->query("UPDATE users SET is_admin=$new_admin WHERE id=$tid");
        $msg = $new_admin ? 'promoted' : 'demoted';
    }
}

// Delete user
if (isset($_POST['delete_user'])) {
    $tid = (int)$_POST['target_id'];
    if ($tid !== (int)$_SESSION['user_id']) {
        $conn->query("DELETE FROM cart WHERE user_id=$tid");
        $conn->query("DELETE FROM products WHERE added_by=$tid");
        $conn->query("DELETE FROM users WHERE id=$tid");
        $msg = 'deleted';
    }
}

$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE username LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR email LIKE '%".mysqli_real_escape_string($conn,$search)."%'" : '';
$users  = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM products WHERE added_by=u.id) as product_count FROM users u $where ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Users – Admin Panel</title>
  <link rel="stylesheet" href="../css/style.css"/>
  <style>
    .admin-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:18px 24px;background:var(--glass-bg);border-bottom:1px solid var(--glass-border);backdrop-filter:blur(20px);position:sticky;top:0;z-index:100;}
    .admin-logo{font-size:20px;font-weight:900;color:var(--tx-1);display:flex;align-items:center;gap:10px;}
    .admin-badge{padding:4px 12px;background:rgba(139,92,246,0.15);color:#a78bfa;border-radius:var(--r-full);font-size:12px;font-weight:700;}
    .admin-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
    .admin-nav a{padding:8px 16px;border-radius:var(--r-full);font-size:13px;font-weight:600;color:var(--tx-2);background:rgba(255,255,255,0.05);border:1px solid var(--glass-border);text-decoration:none;transition:all .2s;}
    .admin-nav a:hover,.admin-nav a.active{background:rgba(34,197,94,0.1);color:var(--g400);border-color:rgba(34,197,94,0.2);}
    .admin-nav a.danger:hover{background:rgba(244,63,94,0.1);color:#f87171;border-color:rgba(244,63,94,0.2);}
    .admin-body{max-width:1400px;margin:0 auto;padding:28px 24px;}
    .users-table-wrap{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--r-xl);overflow:hidden;backdrop-filter:blur(20px);}
    .users-header{padding:20px 24px;border-bottom:1px solid var(--glass-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
    .users-header h2{font-size:20px;font-weight:800;color:var(--tx-1);}
  </style>
</head>
<body>
<div class="mesh-bg"><div class="orb" style="width:500px;height:500px;background:rgba(139,92,246,0.07);top:-200px;right:-100px;--d:22s;--tx:-60px;--ty:50px;"></div></div>

<div class="admin-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <a href="../dashboard.php" style="text-decoration:none;"><div class="admin-logo">🥦 <span>VeggieMarket</span> <span class="admin-badge">👑 Admin</span></div></a>
  </div>
  <div class="admin-nav">
    <a href="index.php">📊 Overview</a>
    <a href="users.php" class="active">👥 Users</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="../shop.php">🛒 Live Shop</a>
    <a href="../logout.php" class="danger">🚪 Logout</a>
  </div>
</div>

<div class="admin-body">
  <div style="font-size:26px;font-weight:900;color:var(--tx-1);margin-bottom:4px;">👥 Manage Users</div>
  <div style="color:var(--tx-4);margin-bottom:24px;"><?= $users->num_rows ?> users registered</div>

  <?php if ($msg): ?>
  <div class="alert alert-success" style="margin-bottom:16px;">
    <?php
    $msgs=['banned'=>'🚫 User banned.','unbanned'=>'✅ User unbanned.','promoted'=>'👑 User promoted to admin.','demoted'=>'⬇️ Admin rights removed.','deleted'=>'🗑️ User deleted.'];
    echo $msgs[$msg]??'Done.';?>
  </div>
  <?php endif; ?>

  <div class="users-table-wrap">
    <div class="users-header">
      <h2>All Users</h2>
      <form method="GET" action="users.php" style="display:flex;gap:8px;">
        <div class="search-wrap" style="min-width:240px;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..."/>
        </div>
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
        <?php if($search): ?><a href="users.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap" style="border-radius:0;">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Products</th>
            <th>Joined</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($u = $users->fetch_assoc()):
            $isSelf = $u['id'] == $_SESSION['user_id'];
          ?>
          <tr>
            <td style="color:var(--tx-4);font-size:12px;"><?= str_pad($u['id'],4,'0',STR_PAD_LEFT) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;background:<?= $u['is_admin']?'linear-gradient(135deg,#8b5cf6,#6d28d9)':'linear-gradient(135deg,var(--g500),var(--emerald))' ?>;display:grid;place-items:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                  <?= strtoupper(substr($u['username'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--tx-1);"><?= htmlspecialchars($u['username']) ?></div>
                  <?php if($u['full_name']): ?><div style="font-size:12px;color:var(--tx-4);"><?= htmlspecialchars($u['full_name']) ?></div><?php endif; ?>
                </div>
              </div>
            </td>
            <td style="font-size:13px;color:var(--tx-3);"><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <?php if($u['is_admin']): ?>
                <span class="pill" style="background:rgba(139,92,246,0.15);color:#a78bfa;">👑 Admin</span>
              <?php else: ?>
                <span class="pill pill-green">🌿 User</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;font-weight:700;color:var(--tx-2);"><?= $u['product_count'] ?></td>
            <td style="font-size:12px;color:var(--tx-4);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if($u['is_banned']): ?>
                <span style="color:#f87171;font-size:12px;font-weight:700;">🚫 Banned</span>
              <?php else: ?>
                <span style="color:var(--g400);font-size:12px;font-weight:700;">✅ Active</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$isSelf): ?>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <!-- Ban/Unban -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <input type="hidden" name="new_ban" value="<?= $u['is_banned']?0:1 ?>"/>
                  <button type="submit" name="toggle_ban" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;<?= $u['is_banned']?'color:#86efac;':'color:#fda4af;' ?>">
                    <?= $u['is_banned']?'✅ Unban':'🚫 Ban' ?>
                  </button>
                </form>
                <!-- Admin toggle -->
                <?php if(!$u['is_admin']): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <input type="hidden" name="new_admin" value="1"/>
                  <button type="submit" name="toggle_admin" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;color:#c4b5fd;">👑 Promote</button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <input type="hidden" name="new_admin" value="0"/>
                  <button type="submit" name="toggle_admin" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;color:#fda4af;">⬇️ Demote</button>
                </form>
                <?php endif; ?>
                <!-- Delete -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user? This is permanent!')">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" name="delete_user" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;color:#f43f5e;">🗑️</button>
                </form>
              </div>
              <?php else: ?>
                <span style="font-size:12px;color:var(--tx-4);">(You)</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>const obs=new IntersectionObserver(e=>e.forEach(en=>{if(en.isIntersecting)en.target.classList.add('visible');}),{threshold:0.06});document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));</script>
</body>
</html>
