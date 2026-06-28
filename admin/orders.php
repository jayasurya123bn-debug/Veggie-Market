<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || !isAdmin()) { header('Location: ../index.php'); exit(); }

$msg = '';

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,total DECIMAL(10,2) NOT NULL,delivery DECIMAL(10,2) DEFAULT 0,status ENUM('pending','confirmed','packed','on_the_way','delivered','cancelled') DEFAULT 'confirmed',note TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (user_id) REFERENCES users(id))");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT,product_name VARCHAR(100) NOT NULL,price DECIMAL(10,2) NOT NULL,quantity INT NOT NULL,unit VARCHAR(20),FOREIGN KEY (order_id) REFERENCES orders(id))");

// Update order status
if (isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $allowed = ['pending','confirmed','packed','on_the_way','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $conn->query("UPDATE orders SET status='$status' WHERE id=$oid");
        $msg = 'updated';
    }
}

$filter_status = trim($_GET['status'] ?? '');
$where = $filter_status ? "WHERE o.status='".mysqli_real_escape_string($conn,$filter_status)."'" : '';

$orders = $conn->query("
    SELECT o.*, u.username, u.email,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id=u.id
    LEFT JOIN order_items oi ON o.id=oi.order_id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

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
  <title>Manage Orders – Admin Panel</title>
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
    .order-status-pill{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:var(--r-full);font-size:12px;font-weight:700;}
    .status-select{padding:6px 12px;border-radius:var(--r-full);font-size:12px;font-weight:600;background:rgba(255,255,255,0.07);border:1px solid var(--glass-border);color:var(--tx-1);cursor:pointer;}
  </style>
</head>
<body>
<div class="mesh-bg"><div class="orb" style="width:500px;height:500px;background:rgba(249,115,22,0.07);top:-200px;right:-100px;--d:22s;--tx:-60px;--ty:50px;"></div></div>

<div class="admin-header">
  <a href="../dashboard.php" style="text-decoration:none;"><div class="admin-logo">🥦 <span>VeggieMarket</span> <span class="admin-badge">👑 Admin</span></div></a>
  <div class="admin-nav">
    <a href="index.php">📊 Overview</a>
    <a href="users.php">👥 Users</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php" class="active">🧾 Orders</a>
    <a href="../shop.php">🛒 Live Shop</a>
    <a href="../logout.php" class="danger">🚪 Logout</a>
  </div>
</div>

<div class="admin-body">
  <div style="font-size:26px;font-weight:900;color:var(--tx-1);margin-bottom:4px;">🧾 Manage Orders</div>
  <div style="color:var(--tx-4);margin-bottom:20px;"><?= $orders->num_rows ?> orders found</div>

  <?php if ($msg === 'updated'): ?>
    <div class="alert alert-success" style="margin-bottom:16px;">✅ Order status updated.</div>
  <?php endif; ?>

  <!-- Status filter -->
  <div class="filter-bar" style="margin-bottom:20px;">
    <a href="orders.php" class="chip <?= !$filter_status?'active':'' ?>">📋 All</a>
    <?php foreach($status_meta as $key=>$sm): ?>
    <a href="orders.php?status=<?= $key ?>" class="chip <?= $filter_status===$key?'active':'' ?>">
      <?= $sm['icon'] ?> <?= $sm['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--r-xl);overflow:hidden;backdrop-filter:blur(20px);">
    <div class="table-wrap" style="border-radius:0;">
      <table class="data-table">
        <thead>
          <tr><th>Order ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Update Status</th></tr>
        </thead>
        <tbody>
          <?php if ($orders->num_rows > 0): ?>
          <?php while($o=$orders->fetch_assoc()):
            $sm=$status_meta[$o['status']]??$status_meta['confirmed']; ?>
          <tr>
            <td style="font-weight:700;color:var(--tx-1);">#<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></td>
            <td>
              <div style="font-weight:700;color:var(--tx-1);"><?= htmlspecialchars($o['username']??'Unknown') ?></div>
              <div style="font-size:12px;color:var(--tx-4);"><?= htmlspecialchars($o['email']??'') ?></div>
            </td>
            <td style="text-align:center;color:var(--tx-2);font-weight:700;"><?= $o['item_count'] ?></td>
            <td class="text-green font-bold">₹<?= number_format($o['total'],2) ?></td>
            <td>
              <span class="order-status-pill" style="background:<?= $sm['color'] ?>22;color:<?= $sm['color'] ?>;border:1px solid <?= $sm['color'] ?>44;">
                <?= $sm['icon'] ?> <?= $sm['label'] ?>
              </span>
            </td>
            <td style="font-size:12px;color:var(--tx-4);"><?= date('d M Y h:i A', strtotime($o['created_at'])) ?></td>
            <td>
              <form method="POST" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                <select name="status" class="status-select">
                  <?php foreach($status_meta as $key=>$sm2): ?>
                  <option value="<?= $key ?>" <?= $o['status']===$key?'selected':'' ?>><?= $sm2['icon'] ?> <?= $sm2['label'] ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status" class="btn btn-ghost btn-sm" style="padding:6px 12px;font-size:12px;">Save</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php else: ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--tx-4);">📦 No orders found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
