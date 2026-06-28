<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];

// Ensure wishlist table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS wishlist (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        product_id INT NOT NULL,
        added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wish (user_id, product_id),
        FOREIGN KEY (user_id)    REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
");

$msg = '';
// Toggle wishlist via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wish'])) {
    $pid = (int)$_POST['product_id'];
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $check->bind_param("ii", $uid, $pid); $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $d = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?");
        $d->bind_param("ii", $uid, $pid); $d->execute();
        $msg = 'removed';
    } else {
        $a = $conn->prepare("INSERT INTO wishlist (user_id,product_id) VALUES (?,?)");
        $a->bind_param("ii", $uid, $pid); $a->execute();
        $msg = 'added';
    }
}

// Add to cart from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $ex  = $conn->prepare("SELECT id,quantity FROM cart WHERE user_id=? AND product_id=?");
    $ex->bind_param("ii",$uid,$pid); $ex->execute();
    $row = $ex->get_result()->fetch_assoc();
    if ($row) {
        $nq=$row['quantity']+$qty;
        $u=$conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
        $u->bind_param("ii",$nq,$row['id']); $u->execute();
    } else {
        $i=$conn->prepare("INSERT INTO cart(user_id,product_id,quantity) VALUES(?,?,?)");
        $i->bind_param("iii",$uid,$pid,$qty); $i->execute();
    }
    $msg = 'carted';
}

// Load wishlist items
$items_res = $conn->query("
    SELECT p.*, w.id as wid, w.added_at as wished_at
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = $uid
    ORDER BY w.added_at DESC
");
$items = [];
while ($r = $items_res->fetch_assoc()) $items[] = $r;

$emojis=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿',
         'Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Wishlist – Veggie Market ❤️</title>
  <meta name="description" content="Your Veggie Market saved items and wishlist."/>
  <link rel="stylesheet" href="css/style.css"/>
  <style>
    .wish-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }
    .wish-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: var(--r-xl);
      overflow:hidden;
      backdrop-filter:blur(20px);
      transition: transform .3s, box-shadow .3s;
    }
    .wish-card:hover { transform:translateY(-4px); box-shadow: var(--shadow-lg); }
    .wc-img {
      height: 180px; position:relative; overflow:hidden;
      background: rgba(34,197,94,0.06);
    }
    .wc-img img { width:100%; height:100%; object-fit:cover; }
    .wc-placeholder {
      width:100%;height:100%;display:grid;place-items:center;font-size:64px;
    }
    .wc-body { padding: 16px; }
    .wc-cat { font-size:12px; color:var(--g400); font-weight:700; margin-bottom:4px; }
    .wc-name { font-size:18px; font-weight:800; color:var(--tx-1); margin-bottom:4px; }
    .wc-price { font-size:22px; font-weight:900; color:var(--g400); margin-bottom:10px; }
    .wc-price span { font-size:13px; color:var(--tx-4); font-weight:400; }
    .wc-foot { display:flex; gap:8px; margin-top:12px; }
    .wc-foot .btn { flex:1; padding:10px; font-size:13px; }
    .wc-remove {
      width:36px; height:36px; border-radius:50%;
      background:rgba(244,63,94,0.12); border:1px solid rgba(244,63,94,0.2);
      color:#f43f5e; font-size:16px; cursor:pointer;
      display:grid; place-items:center; flex-shrink:0;
      transition: background .2s;
    }
    .wc-remove:hover { background:rgba(244,63,94,0.25); }
    .stock-badge {
      position:absolute; bottom:10px; left:10px;
      padding:4px 10px; border-radius:var(--r-full);
      font-size:11px; font-weight:700;
    }
    .stock-ok   { background:rgba(34,197,94,0.2);color:#4ade80; }
    .stock-low  { background:rgba(249,115,22,0.2);color:#fb923c; }
    .stock-out  { background:rgba(100,100,100,0.3);color:#9ca3af; }
    .wish-date  { font-size:11px; color:var(--tx-4); margin-bottom:10px; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:450px;height:450px;background:rgba(244,63,94,0.06);top:-100px;right:-80px;--d:20s;--tx:-40px;--ty:50px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Saved Items</div>
      <h1 class="page-title">My Wishlist ❤️</h1>
      <p class="page-sub"><?= count($items) ?> item<?= count($items)!=1?'s':'' ?> saved</p>
    </div>
    <a href="shop.php" class="btn btn-ghost" style="padding:11px 20px;">🛒 Browse Shop</a>
  </div>

  <?php if ($msg === 'carted'): ?>
    <div class="alert alert-success reveal">🛒 Added to cart successfully!</div>
  <?php elseif ($msg === 'removed'): ?>
    <div class="alert alert-warn reveal">💔 Removed from wishlist.</div>
  <?php elseif ($msg === 'added'): ?>
    <div class="alert alert-success reveal">❤️ Added to wishlist!</div>
  <?php endif; ?>

  <?php if (!empty($items)): ?>
  <div class="wish-grid">
    <?php foreach($items as $i => $p):
      $e  = $emojis[$p['category']] ?? '🥦';
      $sc = $p['stock']>10 ? 'ok' : ($p['stock']>0 ? 'low' : 'out');
      $st = $p['stock']>10 ? "✅ {$p['stock']} in stock" : ($p['stock']>0 ? "⚠️ Only {$p['stock']} left" : "❌ Out of stock");
    ?>
    <div class="wish-card reveal" style="transition-delay:<?= min($i*0.06,0.4) ?>s;">
      <div class="wc-img">
        <?php if($p['image'] && file_exists('uploads/'.$p['image'])): ?>
          <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"/>
        <?php else: ?>
          <div class="wc-placeholder"><?= $e ?></div>
        <?php endif; ?>
        <span class="stock-badge stock-<?= $sc ?>"><?= $st ?></span>
      </div>
      <div class="wc-body">
        <div class="wc-cat"><?= $e ?> <?= htmlspecialchars($p['category'] ?: 'General') ?></div>
        <div class="wc-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="wish-date">❤️ Saved <?= date('d M Y', strtotime($p['wished_at'])) ?></div>
        <div class="wc-price">
          ₹<?= number_format($p['price'],2) ?>
          <span>/ <?= htmlspecialchars($p['unit']) ?></span>
        </div>
        <div class="wc-foot">
          <?php if($p['stock'] > 0): ?>
          <form method="POST" action="wishlist.php" style="flex:1;">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
            <input type="hidden" name="quantity" value="1"/>
            <button type="submit" name="add_to_cart" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;">🛒 Add to Cart</button>
          </form>
          <?php else: ?>
          <button class="btn btn-ghost" style="flex:1;padding:10px;font-size:13px;" disabled>Sold Out</button>
          <?php endif; ?>
          <form method="POST" action="wishlist.php">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
            <button type="submit" name="toggle_wish" class="wc-remove" title="Remove from wishlist">✕</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="empty-state reveal" style="padding:80px 20px;">
    <span class="empty-emoji">❤️</span>
    <div class="empty-title">Your wishlist is empty</div>
    <div class="empty-desc">Click the 🤍 heart button on any product to save it here!</div>
    <a href="shop.php" class="btn btn-primary" style="width:auto;padding:14px 32px;">🛒 Browse Products</a>
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
