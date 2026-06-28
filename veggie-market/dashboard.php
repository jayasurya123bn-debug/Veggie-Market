<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid       = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?: $_SESSION['username'];

// Stats
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$my_products    = $conn->query("SELECT COUNT(*) as c FROM products WHERE added_by=$uid")->fetch_assoc()['c'];
$categories     = $conn->query("SELECT COUNT(DISTINCT category) as c FROM products WHERE category!='' AND category IS NOT NULL")->fetch_assoc()['c'];
$cart_count     = $conn->query("SELECT SUM(quantity) as c FROM cart WHERE user_id=$uid")->fetch_assoc()['c'] ?? 0;

// Greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? '☀️ Good Morning' : ($hour < 17 ? '🌤️ Good Afternoon' : '🌙 Good Evening');

// Recent products
$recent = $conn->query("
    SELECT p.*, u.username FROM products p
    LEFT JOIN users u ON p.added_by=u.id
    ORDER BY p.created_at DESC LIMIT 6");

// Today's picks (random)
$picks = $conn->query("SELECT * FROM products WHERE stock>0 ORDER BY RAND() LIMIT 4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – Veggie Market</title>
  <meta name="description" content="Your Veggie Market dashboard."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:600px;height:600px;background:rgba(34,197,94,0.1);top:-200px;left:-200px;--d:25s;--tx:80px;--ty:50px;"></div>
  <div class="orb" style="width:400px;height:400px;background:rgba(132,204,22,0.07);bottom:-100px;right:-100px;--d:18s;--tx:-50px;--ty:-30px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <!-- Page Header -->
  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Overview</div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-sub">Track your activity and explore the market</p>
    </div>
    <a href="shop.php" class="btn btn-outline gap-2">🛒 Browse Shop</a>
  </div>

  <!-- Welcome Banner -->
  <div class="welcome-banner reveal reveal-d1">
    <div class="wb-emoji">🥦</div>
    <div style="flex:1;">
      <div class="wb-greeting"><?= $greeting ?></div>
      <div class="wb-name"><?= htmlspecialchars($full_name) ?>!</div>
      <div class="wb-sub">Welcome back to your fresh veggie dashboard. What are we shopping today?</div>
      <div class="wb-actions">
        <a href="shop.php"        class="btn btn-primary" style="width:auto;padding:11px 24px;">🛒 Shop Now</a>
        <a href="add_product.php" class="btn btn-ghost"   style="padding:11px 22px;">➕ Add Product</a>
        <a href="seed.php"        class="btn btn-ghost"   style="padding:11px 22px;" title="Load demo products">🌱 Load Demo Data</a>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card reveal reveal-d1" style="--accent:linear-gradient(90deg,var(--g500),var(--emerald));">
      <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);">🥦</div>
      <div class="stat-num" data-count="<?= $total_products ?>">0</div>
      <div class="stat-label">Total Products</div>
      <div class="stat-delta delta-up">↑ In the market</div>
    </div>
    <div class="stat-card reveal reveal-d2" style="--accent:linear-gradient(90deg,var(--orange),#fb923c);">
      <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.12);">📦</div>
      <div class="stat-num" data-count="<?= $my_products ?>">0</div>
      <div class="stat-label">My Products</div>
      <div class="stat-delta delta-up">↑ Listed by you</div>
    </div>
    <div class="stat-card reveal reveal-d3" style="--accent:linear-gradient(90deg,var(--sky),#38bdf8);">
      <div class="stat-icon-wrap" style="background:rgba(14,165,233,0.12);">🏷️</div>
      <div class="stat-num" data-count="<?= $categories ?>">0</div>
      <div class="stat-label">Categories</div>
      <div class="stat-delta delta-up">↑ Variety</div>
    </div>
    <div class="stat-card reveal reveal-d4" style="--accent:linear-gradient(90deg,var(--amber),#fbbf24);">
      <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.12);">🛍️</div>
      <div class="stat-num" data-count="<?= $cart_count ?>">0</div>
      <div class="stat-label">Cart Items</div>
      <div class="stat-delta delta-up"><a href="cart.php" style="color:inherit;text-decoration:none;">→ View cart</a></div>
    </div>
  </div>

  <!-- Quick Actions -->
  <h2 style="font-size:20px;font-weight:800;color:var(--tx-1);margin-bottom:18px;" class="reveal">Quick Actions</h2>
  <div class="actions-grid reveal reveal-d1">
    <a href="shop.php" class="action-card">
      <div class="ac-icon" style="background:linear-gradient(135deg,var(--g500),var(--emerald));">🛒</div>
      <div><div class="ac-title">Browse Shop</div><div class="ac-desc">Explore all fresh produce</div></div>
    </a>
    <a href="add_product.php" class="action-card">
      <div class="ac-icon" style="background:linear-gradient(135deg,var(--lime),var(--emerald));">➕</div>
      <div><div class="ac-title">Add Product</div><div class="ac-desc">List a new item with photos</div></div>
    </a>
    <a href="cart.php" class="action-card">
      <div class="ac-icon" style="background:linear-gradient(135deg,var(--orange),#fb923c);">🛍️</div>
      <div><div class="ac-title">My Cart</div><div class="ac-desc"><?= $cart_count ?> item<?= $cart_count!=1?'s':'' ?> waiting</div></div>
    </a>
    <a href="logout.php" class="action-card" style="border-color:rgba(244,63,94,0.15);">
      <div class="ac-icon" style="background:linear-gradient(135deg,var(--rose),#dc2626);">🚪</div>
      <div><div class="ac-title">Sign Out</div><div class="ac-desc">Securely logout</div></div>
    </a>
  </div>

  <!-- Today's Picks -->
  <?php if ($picks->num_rows > 0): ?>
  <h2 style="font-size:20px;font-weight:800;color:var(--tx-1);margin-bottom:18px;" class="reveal">✨ Today's Picks</h2>
  <div class="products-grid reveal" style="margin-bottom:36px;">
    <?php $emojis=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿','Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'];
    while($p=$picks->fetch_assoc()): $e=$emojis[$p['category']]??'🥦'; ?>
    <div class="product-card" onclick="window.location='shop.php'">
      <div class="pc-img">
        <?php if($p['image']&&file_exists('uploads/'.$p['image'])): ?>
          <img src="uploads/<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>"/>
        <?php else: ?>
          <div class="pc-placeholder"><?=$e?></div>
        <?php endif; ?>
        <span class="pc-flag pc-flag-violet">✨ Pick</span>
      </div>
      <div class="pc-body">
        <div class="pc-cat"><?=$e?> <?=htmlspecialchars($p['category']?:'General')?></div>
        <div class="pc-name"><?=htmlspecialchars($p['name'])?></div>
        <div class="pc-footer">
          <div class="pc-price">
            <span class="prc-sym">₹</span>
            <span class="prc-val"><?=number_format($p['price'],2)?></span>
            <span class="prc-unit">/<?=htmlspecialchars($p['unit'])?></span>
          </div>
          <a href="shop.php" class="atc-btn" onclick="event.stopPropagation()">Shop →</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>

  <!-- Recent Products Table -->
  <div class="flex-between" style="margin-bottom:18px;" class="reveal">
    <h2 style="font-size:20px;font-weight:800;color:var(--tx-1);">Recently Added</h2>
    <a href="shop.php" class="btn btn-ghost btn-sm">View All →</a>
  </div>

  <?php if ($recent->num_rows > 0): ?>
  <div class="table-wrap reveal">
    <table class="data-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Added By</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php while($p=$recent->fetch_assoc()):
          $e=$emojis[$p['category']]??'🥦';
          $sc=$p['stock']>10?'ok':($p['stock']>0?'low':'out');
          $st=$p['stock']>10?"✅ {$p['stock']} in stock":($p['stock']>0?"⚠️ Only {$p['stock']} left":"❌ Out of stock");
        ?>
        <tr>
          <td>
            <div class="flex-c gap-3">
              <div class="tbl-thumb">
                <?php if($p['image']&&file_exists('uploads/'.$p['image'])): ?>
                  <img src="uploads/<?=htmlspecialchars($p['image'])?>" alt=""/>
                <?php else: echo $e; endif; ?>
              </div>
              <span class="font-bold"><?=htmlspecialchars($p['name'])?></span>
            </div>
          </td>
          <td><span class="pill pill-green"><?=$e?> <?=htmlspecialchars($p['category']?:'General')?></span></td>
          <td class="text-green font-bold">₹<?=number_format($p['price'],2)?>/<?=htmlspecialchars($p['unit'])?></td>
          <td><span class="text-sm"><?=$st?></span></td>
          <td class="text-muted text-sm"><?=htmlspecialchars($p['username']??'Unknown')?></td>
          <td><a href="shop.php" class="btn btn-ghost btn-sm">Shop →</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state reveal">
    <span class="empty-emoji">🌱</span>
    <div class="empty-title">No Products Yet</div>
    <div class="empty-desc">Be the first to add fresh produce!</div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="add_product.php" class="btn btn-primary" style="width:auto;padding:13px 28px;">➕ Add First Product</a>
      <a href="seed.php"        class="btn btn-ghost"   style="padding:13px 22px;">🌱 Load Demo Data</a>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- Scroll to top -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Back to top">↑</button>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?=date('Y')?> · Fresh, Local, Delicious</div>
    <div class="footer-links">
      <a href="shop.php">Shop</a>
      <a href="add_product.php">Sell</a>
      <a href="cart.php">Cart</a>
    </div>
  </div>
</footer>

<script>
// Count-up animation
function countUp(el, target) {
  const duration = 1200, start = performance.now();
  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const val = Math.round(progress * target);
    el.textContent = val;
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

// Scroll reveal
const revealObs = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      // Trigger count-up on stat cards
      const num = e.target.querySelector('[data-count]');
      if (num) countUp(num, +num.dataset.count);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// Scroll-to-top
const scrollBtn = document.getElementById('scrollTop');
window.addEventListener('scroll', () => {
  scrollBtn.classList.toggle('visible', window.scrollY > 400);
});
</script>
</body>
</html>
