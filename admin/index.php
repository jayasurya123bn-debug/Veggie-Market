<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || !isAdmin()) { header('Location: ../index.php'); exit(); }

// Stats
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$total_orders   = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] ?? 0;
$total_revenue  = 0;
$rev_res = $conn->query("SELECT SUM(total) as s FROM orders");
if ($rev_res) $total_revenue = $rev_res->fetch_assoc()['s'] ?? 0;

$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 8");
$recent_orders = null;
$orders_tbl = $conn->query("SHOW TABLES LIKE 'orders'");
if ($orders_tbl && $orders_tbl->num_rows > 0) {
    $recent_orders = $conn->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8");
}

$status_meta = [
    'pending'    => ['label'=>'Pending',    'color'=>'#f59e0b', 'icon'=>'⏳'],
    'confirmed'  => ['label'=>'Confirmed',  'color'=>'#3b82f6', 'icon'=>'✅'],
    'packed'     => ['label'=>'Packed',     'color'=>'#8b5cf6', 'icon'=>'📦'],
    'on_the_way' => ['label'=>'On The Way', 'color'=>'#f97316', 'icon'=>'🚚'],
    'delivered'  => ['label'=>'Delivered',  'color'=>'#22c55e', 'icon'=>'🎉'],
    'cancelled'  => ['label'=>'Cancelled',  'color'=>'#f43f5e', 'icon'=>'❌'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel – Veggie Market 👑</title>
  <meta name="description" content="Veggie Market admin dashboard."/>
  <link rel="stylesheet" href="../css/style.css"/>
  <style>
    .admin-header {
      display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;
      padding:18px 24px;
      background:var(--glass-bg);border-bottom:1px solid var(--glass-border);
      backdrop-filter:blur(20px);
      position:sticky;top:0;z-index:100;
    }
    .admin-logo { font-size:20px;font-weight:900;color:var(--tx-1);display:flex;align-items:center;gap:10px; }
    .admin-badge { padding:4px 12px;background:rgba(139,92,246,0.15);color:#a78bfa;border-radius:var(--r-full);font-size:12px;font-weight:700; }
    .admin-nav { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
    .admin-nav a {
      padding:8px 16px;border-radius:var(--r-full);
      font-size:13px;font-weight:600;color:var(--tx-2);
      background:rgba(255,255,255,0.05);border:1px solid var(--glass-border);
      text-decoration:none;transition:all .2s;
    }
    .admin-nav a:hover,.admin-nav a.active { background:rgba(34,197,94,0.1);color:var(--g400);border-color:rgba(34,197,94,0.2); }
    .admin-nav a.danger:hover { background:rgba(244,63,94,0.1);color:#f87171;border-color:rgba(244,63,94,0.2); }
    .admin-body { max-width:1400px;margin:0 auto;padding:28px 24px; }
    .admin-section { margin-bottom:36px; }
    .admin-section-title {
      font-size:18px;font-weight:800;color:var(--tx-1);margin-bottom:18px;
      display:flex;align-items:center;gap:8px;
    }
    .user-row { display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--glass-border); }
    .user-row:last-child { border-bottom:none; }
    .user-av {
      width:38px;height:38px;border-radius:50%;flex-shrink:0;
      background:linear-gradient(135deg,var(--g500),var(--emerald));
      display:grid;place-items:center;font-size:15px;font-weight:800;color:#fff;
    }
    .user-av.admin-av { background:linear-gradient(135deg,#8b5cf6,#6d28d9); }
    .user-meta { flex:1;min-width:0; }
    .user-name { font-size:14px;font-weight:700;color:var(--tx-1); }
    .user-email { font-size:12px;color:var(--tx-4);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .user-actions { display:flex;gap:6px; }
    .ua-btn {
      padding:5px 12px;border-radius:var(--r-full);font-size:12px;font-weight:600;cursor:pointer;
      border:1px solid var(--glass-border);background:rgba(255,255,255,0.05);color:var(--tx-2);
      transition:all .2s;
    }
    .ua-btn.ban  { border-color:rgba(244,63,94,0.3);color:#fda4af; }
    .ua-btn.ban:hover  { background:rgba(244,63,94,0.12); }
    .ua-btn.unban{ border-color:rgba(34,197,94,0.3);color:#86efac; }
    .ua-btn.unban:hover{ background:rgba(34,197,94,0.12); }
    .order-status-pill {
      display:inline-flex;align-items:center;gap:4px;
      padding:3px 10px;border-radius:var(--r-full);
      font-size:11px;font-weight:700;
    }
    .admin-cards { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px; }
    .admin-card {
      background:var(--glass-bg);border:1px solid var(--glass-border);
      border-radius:var(--r-xl);padding:22px;backdrop-filter:blur(20px);
    }
    .ac-icon { font-size:28px;margin-bottom:8px; }
    .ac-num  { font-size:30px;font-weight:900;color:var(--tx-1); }
    .ac-lbl  { font-size:13px;color:var(--tx-4);font-weight:600; }
    .panel-card {
      background:var(--glass-bg);border:1px solid var(--glass-border);
      border-radius:var(--r-xl);padding:24px;backdrop-filter:blur(20px);
    }
    .two-col { display:grid;grid-template-columns:1fr 1fr;gap:24px; }
    @media(max-width:900px){.two-col{grid-template-columns:1fr;}}
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:500px;height:500px;background:rgba(139,92,246,0.07);top:-200px;right:-100px;--d:22s;--tx:-60px;--ty:50px;"></div>
</div>

<!-- Admin Header -->
<div class="admin-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <a href="../dashboard.php" style="text-decoration:none;">
      <div class="admin-logo">🥦 <span>VeggieMarket</span> <span class="admin-badge">👑 Admin</span></div>
    </a>
  </div>
  <div class="admin-nav">
    <a href="index.php" class="active">📊 Overview</a>
    <a href="users.php">👥 Users</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="../shop.php">🛒 Live Shop</a>
    <a href="../logout.php" class="danger">🚪 Logout</a>
  </div>
</div>

<div class="admin-body">

  <div style="margin-bottom:28px;">
    <div style="font-size:28px;font-weight:900;color:var(--tx-1);margin-bottom:4px;">Admin Dashboard</div>
    <div style="color:var(--tx-4);font-size:15px;">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> · Full control panel</div>
  </div>

  <!-- Stats -->
  <div class="admin-cards">
    <div class="admin-card">
      <div class="ac-icon">👥</div>
      <div class="ac-num"><?= $total_users ?></div>
      <div class="ac-lbl">Total Users</div>
    </div>
    <div class="admin-card">
      <div class="ac-icon">📦</div>
      <div class="ac-num"><?= $total_products ?></div>
      <div class="ac-lbl">Products Listed</div>
    </div>
    <div class="admin-card">
      <div class="ac-icon">🧾</div>
      <div class="ac-num"><?= $total_orders ?></div>
      <div class="ac-lbl">Orders Placed</div>
    </div>
    <div class="admin-card">
      <div class="ac-icon">💰</div>
      <div class="ac-num">₹<?= number_format($total_revenue,0) ?></div>
      <div class="ac-lbl">Total Revenue</div>
    </div>
  </div>

  <div class="two-col">

    <!-- Recent Users -->
    <div class="admin-section">
      <div class="admin-section-title">👥 Recent Users <a href="users.php" style="font-size:13px;font-weight:600;color:var(--g400);text-decoration:none;margin-left:auto;">View All →</a></div>
      <div class="panel-card">
        <?php if ($recent_users && $recent_users->num_rows > 0): ?>
          <?php while($u = $recent_users->fetch_assoc()): ?>
          <div class="user-row">
            <div class="user-av <?= $u['is_admin']?'admin-av':'' ?>"><?= strtoupper(substr($u['username'],0,1)) ?></div>
            <div class="user-meta">
              <div class="user-name">
                <?= htmlspecialchars($u['username']) ?>
                <?php if($u['is_admin']): ?><span style="font-size:10px;background:rgba(139,92,246,0.15);color:#a78bfa;padding:2px 8px;border-radius:20px;margin-left:6px;">Admin</span><?php endif; ?>
                <?php if($u['is_banned']): ?><span style="font-size:10px;background:rgba(244,63,94,0.15);color:#f87171;padding:2px 8px;border-radius:20px;margin-left:4px;">Banned</span><?php endif; ?>
              </div>
              <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
            </div>
            <div style="font-size:11px;color:var(--tx-4);"><?= date('d M', strtotime($u['created_at'])) ?></div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div style="color:var(--tx-4);text-align:center;padding:20px;">No users yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="admin-section">
      <div class="admin-section-title">🧾 Recent Orders <a href="orders.php" style="font-size:13px;font-weight:600;color:var(--g400);text-decoration:none;margin-left:auto;">View All →</a></div>
      <div class="panel-card">
        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
          <?php while($o = $recent_orders->fetch_assoc()):
            $sm = $status_meta[$o['status']] ?? $status_meta['confirmed']; ?>
          <div class="user-row">
            <div>
              <div style="font-size:13px;font-weight:700;color:var(--tx-1);">#<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></div>
              <div style="font-size:11px;color:var(--tx-4);"><?= htmlspecialchars($o['username']??'User') ?></div>
            </div>
            <div style="flex:1;padding-left:12px;">
              <span class="order-status-pill" style="background:<?= $sm['color'] ?>22;color:<?= $sm['color'] ?>;border:1px solid <?= $sm['color'] ?>44;">
                <?= $sm['icon'] ?> <?= $sm['label'] ?>
              </span>
            </div>
            <div style="font-size:14px;font-weight:800;color:var(--g400);">₹<?= number_format($o['total'],2) ?></div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div style="color:var(--tx-4);text-align:center;padding:20px;">No orders placed yet.</div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /two-col -->

  <!-- Quick Actions -->
  <div class="admin-section">
    <div class="admin-section-title">⚡ Quick Actions</div>
    <div class="actions-grid">
      <a href="users.php"    class="action-card"><div class="ac-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">👥</div><div><div class="ac-title">Manage Users</div><div class="ac-desc">Ban, promote, or delete accounts</div></div></a>
      <a href="products.php" class="action-card"><div class="ac-icon" style="background:linear-gradient(135deg,var(--g500),var(--emerald));">📦</div><div><div class="ac-title">Manage Products</div><div class="ac-desc">Edit, feature, or remove products</div></div></a>
      <a href="orders.php"   class="action-card"><div class="ac-icon" style="background:linear-gradient(135deg,var(--orange),#fb923c);">🧾</div><div><div class="ac-title">Manage Orders</div><div class="ac-desc">Update order status</div></div></a>
      <a href="../add_product.php" class="action-card"><div class="ac-icon" style="background:linear-gradient(135deg,var(--sky),#38bdf8);">➕</div><div><div class="ac-title">Add Product</div><div class="ac-desc">List a new product</div></div></a>
    </div>
  </div>

</div>

<script>
const obs = new IntersectionObserver(e => e.forEach(en => { if(en.isIntersecting) en.target.classList.add('visible'); }), {threshold:0.06});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
</script>
</body>
</html>
