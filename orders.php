<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];

// Ensure orders table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        total       DECIMAL(10,2) NOT NULL,
        delivery    DECIMAL(10,2) DEFAULT 0,
        status      ENUM('pending','confirmed','packed','on_the_way','delivered','cancelled') DEFAULT 'confirmed',
        note        TEXT,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        order_id    INT NOT NULL,
        product_id  INT,
        product_name VARCHAR(100) NOT NULL,
        price       DECIMAL(10,2) NOT NULL,
        quantity    INT NOT NULL,
        unit        VARCHAR(20),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )
");

// Load orders
$orders_result = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $uid
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$orders = [];
while ($r = $orders_result->fetch_assoc()) $orders[] = $r;

// Load items for each order
$order_items_map = [];
if (!empty($orders)) {
    $ids = implode(',', array_column($orders, 'id'));
    $items_res = $conn->query("SELECT * FROM order_items WHERE order_id IN ($ids) ORDER BY id");
    while ($item = $items_res->fetch_assoc()) $order_items_map[$item['order_id']][] = $item;
}

$status_meta = [
    'pending'    => ['label'=>'Pending',      'color'=>'#f59e0b', 'icon'=>'⏳'],
    'confirmed'  => ['label'=>'Confirmed',    'color'=>'#3b82f6', 'icon'=>'✅'],
    'packed'     => ['label'=>'Packed',       'color'=>'#8b5cf6', 'icon'=>'📦'],
    'on_the_way' => ['label'=>'On The Way',   'color'=>'#f97316', 'icon'=>'🚚'],
    'delivered'  => ['label'=>'Delivered',    'color'=>'#22c55e', 'icon'=>'🎉'],
    'cancelled'  => ['label'=>'Cancelled',    'color'=>'#f43f5e', 'icon'=>'❌'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Orders – Veggie Market 📦</title>
  <meta name="description" content="Track your Veggie Market orders and order history."/>
  <link rel="stylesheet" href="css/style.css"/>
  <style>
    .orders-list { display:flex; flex-direction:column; gap:20px; }
    .order-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: var(--r-xl);
      overflow: hidden;
      backdrop-filter: blur(20px);
      transition: box-shadow .3s, transform .3s;
    }
    .order-card:hover { box-shadow: var(--shadow-lg); transform:translateY(-2px); }
    .order-header {
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
      padding: 18px 22px;
      border-bottom: 1px solid var(--glass-border);
    }
    .order-id { font-size:13px; color:var(--tx-4); font-weight:600; }
    .order-date { font-size:13px; color:var(--tx-4); }
    .order-status {
      display: inline-flex; align-items:center; gap:6px;
      padding: 5px 14px; border-radius: var(--r-full);
      font-size:12px; font-weight:700; letter-spacing:.4px;
    }
    .order-body { padding: 18px 22px; }
    .order-items-list { list-style:none; display:flex; flex-direction:column; gap:10px; }
    .order-item {
      display:flex; align-items:center; gap:14px;
      padding: 12px 14px;
      background: rgba(255,255,255,0.03);
      border-radius: var(--r-md);
      border: 1px solid var(--glass-border);
    }
    .oi-emoji { font-size:24px; width:40px; text-align:center; flex-shrink:0; }
    .oi-name { font-weight:700; color:var(--tx-1); font-size:15px; }
    .oi-meta { font-size:12px; color:var(--tx-4); }
    .oi-price { margin-left:auto; font-weight:800; color:var(--g400); font-size:16px; white-space:nowrap; }
    .order-footer {
      display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
      padding: 14px 22px;
      background: rgba(34,197,94,0.04);
      border-top: 1px solid var(--glass-border);
    }
    .of-total { font-size:18px; font-weight:800; color:var(--tx-1); }
    .of-delivery { font-size:13px; color:var(--tx-4); }
    .timeline {
      display:flex; align-items:center; gap:0; margin: 18px 0 0;
      overflow-x:auto; padding-bottom:4px;
    }
    .tl-step {
      display:flex; flex-direction:column; align-items:center; gap:4px;
      min-width:80px; flex:1; position:relative;
    }
    .tl-step:not(:last-child)::after {
      content:''; position:absolute; top:14px; left:50%; width:100%; height:2px;
      background: rgba(255,255,255,0.1);
      z-index:0;
    }
    .tl-step.done:not(:last-child)::after { background: var(--g500); }
    .tl-dot {
      width:28px; height:28px; border-radius:50%;
      background: rgba(255,255,255,0.08); border:2px solid rgba(255,255,255,0.15);
      display:grid; place-items:center; font-size:13px;
      position:relative; z-index:1; transition: all .3s;
    }
    .tl-step.done .tl-dot { background:var(--g500); border-color:var(--g400); }
    .tl-step.current .tl-dot {
      background:var(--g500); border-color:#fff;
      box-shadow:0 0 0 4px rgba(34,197,94,0.3);
      animation: pulse 2s infinite;
    }
    .tl-label { font-size:10px; color:var(--tx-4); font-weight:600; text-align:center; }
    .tl-step.done .tl-label, .tl-step.current .tl-label { color:var(--tx-2); }
    @keyframes pulse { 0%,100%{box-shadow:0 0 0 4px rgba(34,197,94,0.3)} 50%{box-shadow:0 0 0 8px rgba(34,197,94,0.1)} }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:500px;height:500px;background:rgba(34,197,94,0.07);top:-150px;left:-100px;--d:22s;--tx:60px;--ty:40px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Shopping History</div>
      <h1 class="page-title">My Orders 📦</h1>
      <p class="page-sub"><?= count($orders) ?> order<?= count($orders)!=1?'s':'' ?> placed</p>
    </div>
    <a href="shop.php" class="btn btn-ghost" style="padding:11px 20px;">🛒 Shop More</a>
  </div>

  <?php if (!empty($orders)): ?>
  <div class="orders-list">
    <?php foreach($orders as $i => $order):
      $sm = $status_meta[$order['status']] ?? $status_meta['confirmed'];
      $items = $order_items_map[$order['id']] ?? [];
      $steps = ['confirmed','packed','on_the_way','delivered'];
      $cur_idx = array_search($order['status'], $steps);
    ?>
    <div class="order-card reveal" style="transition-delay:<?= min($i*0.07,0.4) ?>s;">
      <div class="order-header">
        <div>
          <div class="order-id">Order #<?= str_pad($order['id'],6,'0',STR_PAD_LEFT) ?></div>
          <div class="order-date">📅 <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="order-status" style="background:<?= $sm['color'] ?>22;color:<?= $sm['color'] ?>;border:1px solid <?= $sm['color'] ?>44;">
            <?= $sm['icon'] ?> <?= $sm['label'] ?>
          </span>
          <span style="font-size:13px;color:var(--tx-4);"><?= $order['item_count'] ?> item<?= $order['item_count']!=1?'s':'' ?></span>
        </div>
      </div>

      <div class="order-body">
        <?php if ($order['status'] !== 'cancelled'): ?>
        <!-- Progress timeline -->
        <div class="timeline">
          <?php foreach($steps as $si => $step):
            $meta = $status_meta[$step];
            $done = $cur_idx !== false && $si <= $cur_idx;
            $curr = $cur_idx !== false && $si === $cur_idx;
          ?>
          <div class="tl-step <?= $done?'done':'' ?> <?= $curr?'current':'' ?>">
            <div class="tl-dot"><?= $done ? $meta['icon'] : '' ?></div>
            <div class="tl-label"><?= $meta['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Items -->
        <?php if (!empty($items)): ?>
        <ul class="order-items-list" style="margin-top:18px;">
          <?php foreach($items as $item): ?>
          <li class="order-item">
            <div class="oi-emoji">🥦</div>
            <div>
              <div class="oi-name"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="oi-meta">Qty: <?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']??'') ?> · ₹<?= number_format($item['price'],2) ?> each</div>
            </div>
            <div class="oi-price">₹<?= number_format($item['price']*$item['quantity'],2) ?></div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div style="color:var(--tx-4);font-size:14px;padding:12px 0;">Order details recorded at time of checkout.</div>
        <?php endif; ?>
      </div>

      <div class="order-footer">
        <div>
          <div class="of-total">₹<?= number_format($order['total'],2) ?></div>
          <div class="of-delivery">
            <?php if ($order['delivery'] == 0): ?>
              🎉 Free Delivery Included
            <?php else: ?>
              Incl. ₹<?= number_format($order['delivery'],2) ?> delivery
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="shop.php" class="btn btn-ghost btn-sm">🛒 Reorder</a>
          <?php if ($order['status'] === 'delivered'): ?>
            <a href="shop.php" class="btn btn-primary btn-sm" style="width:auto;padding:8px 16px;">⭐ Review</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="empty-state reveal" style="padding:80px 20px;">
    <span class="empty-emoji">📦</span>
    <div class="empty-title">No orders yet</div>
    <div class="empty-desc">Your order history will appear here once you've placed your first order.</div>
    <a href="shop.php" class="btn btn-primary" style="width:auto;padding:14px 32px;">🛒 Start Shopping</a>
  </div>
  <?php endif; ?>

</div>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?= date('Y') ?> · Fresh, Local, Delicious</div>
    <div class="footer-links">
      <a href="shop.php">Shop</a>
      <a href="cart.php">Cart</a>
      <a href="dashboard.php">Dashboard</a>
    </div>
  </div>
</footer>

<script>
const obs = new IntersectionObserver(e => e.forEach(en => { if(en.isIntersecting) en.target.classList.add('visible'); }), {threshold:0.06});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
const stb = document.getElementById('scrollTop');
window.addEventListener('scroll', () => stb.classList.toggle('visible', window.scrollY>400));
</script>
</body>
</html>
