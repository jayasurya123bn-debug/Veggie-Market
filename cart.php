<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];
$msg = '';

// Update quantity
if (isset($_POST['update_qty'])) {
    $cid = (int)$_POST['cart_id'];
    $qty = max(1,(int)$_POST['quantity']);
    $conn->query("UPDATE cart SET quantity=$qty WHERE id=$cid AND user_id=$uid");
    $msg = 'updated';
}
// Remove item
if (isset($_POST['remove'])) {
    $cid = (int)$_POST['cart_id'];
    $conn->query("DELETE FROM cart WHERE id=$cid AND user_id=$uid");
    $msg = 'removed';
}
// Checkout — save real order
if (isset($_POST['checkout'])) {
    // Re-load cart items for the order snapshot
    $cart_snap = $conn->query("
        SELECT c.quantity, p.id, p.name, p.price, p.unit, p.category, p.image, p.stock
        FROM cart c JOIN products p ON c.product_id=p.id
        WHERE c.user_id=$uid
    ");
    $snap_rows=[]; $snap_sub=0;
    while($sr=$cart_snap->fetch_assoc()){$snap_rows[]=$sr;$snap_sub+=$sr['price']*$sr['quantity'];}
    $snap_delivery = $snap_sub>500?0:($snap_sub>0?40:0);
    $snap_total    = $snap_sub + $snap_delivery;

    // Ensure orders table exists
    $conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,total DECIMAL(10,2) NOT NULL,delivery DECIMAL(10,2) DEFAULT 0,status ENUM('pending','confirmed','packed','on_the_way','delivered','cancelled') DEFAULT 'confirmed',note TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (user_id) REFERENCES users(id))");
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT,product_name VARCHAR(100) NOT NULL,price DECIMAL(10,2) NOT NULL,quantity INT NOT NULL,unit VARCHAR(20),FOREIGN KEY (order_id) REFERENCES orders(id))");

    if (!empty($snap_rows)) {
        // Insert order
        $ins = $conn->prepare("INSERT INTO orders (user_id,total,delivery,status) VALUES (?,?,?,'confirmed')");
        $ins->bind_param("idd",$uid,$snap_total,$snap_delivery); $ins->execute();
        $oid = $conn->insert_id;
        // Insert order items
        $ii = $conn->prepare("INSERT INTO order_items (order_id,product_id,product_name,price,quantity,unit) VALUES (?,?,?,?,?,?)");
        foreach($snap_rows as $sr){
            $ii->bind_param("iisdis",$oid,$sr['id'],$sr['name'],$sr['price'],$sr['quantity'],$sr['unit']);
            $ii->execute();
        }
        // Reduce stock
        foreach($snap_rows as $sr){
            $nsq = max(0,$sr['stock']-$sr['quantity']);
            $conn->query("UPDATE products SET stock=$nsq WHERE id={$sr['id']}");
        }
    }
    // Clear cart
    $conn->query("DELETE FROM cart WHERE user_id=$uid");
    $msg = 'checkout';
}

// Load cart
$items = $conn->query("
    SELECT c.id as cid, c.quantity, p.id, p.name, p.price, p.unit, p.image, p.category, p.stock
    FROM cart c
    JOIN products p ON c.product_id=p.id
    WHERE c.user_id=$uid
    ORDER BY c.added_at DESC
");

$subtotal = 0; $cart_rows = [];
while($r=$items->fetch_assoc()) { $cart_rows[]=$r; $subtotal+=$r['price']*$r['quantity']; }

$delivery = $subtotal > 500 ? 0 : ($subtotal > 0 ? 40 : 0);
$total    = $subtotal + $delivery;

$emojis=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿','Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Cart – Veggie Market 🛍️</title>
  <meta name="description" content="Your Veggie Market shopping cart."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:400px;height:400px;background:rgba(249,115,22,0.08);top:-80px;right:-60px;--d:18s;--tx:-50px;--ty:40px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Shopping</div>
      <h1 class="page-title">My Cart 🛍️</h1>
      <p class="page-sub"><?= count($cart_rows) ?> item<?= count($cart_rows)!=1?'s':'' ?> in your basket</p>
    </div>
    <a href="shop.php" class="btn btn-ghost" style="padding:11px 20px;">← Continue Shopping</a>
  </div>

  <?php if ($msg==='checkout'): ?>
    <div class="alert alert-success reveal" style="font-size:16px;">
      🎉 <strong>Order placed successfully!</strong> Your fresh veggies are on the way!
    </div>
  <?php elseif($msg==='removed'): ?>
    <div class="alert alert-warn reveal">🗑️ Item removed from cart.</div>
  <?php elseif($msg==='updated'): ?>
    <div class="alert alert-info reveal">✏️ Cart updated.</div>
  <?php endif; ?>

  <?php if (count($cart_rows) > 0): ?>
  <div class="cart-layout">

    <!-- Items -->
    <div class="cart-items">
      <?php foreach($cart_rows as $i=>$r):
        $e = $emojis[$r['category']]??'🥦';
        $lineTotal = $r['price'] * $r['quantity'];
      ?>
      <div class="cart-item reveal" style="transition-delay:<?=$i*0.07?>s;">

        <div class="ci-img">
          <?php if($r['image']&&file_exists('uploads/'.$r['image'])): ?>
            <img src="uploads/<?=htmlspecialchars($r['image'])?>" alt="<?=htmlspecialchars($r['name'])?>"/>
          <?php else: echo $e; endif; ?>
        </div>

        <div class="ci-info">
          <div class="ci-name"><?=htmlspecialchars($r['name'])?></div>
          <div class="ci-unit"><?=$e?> <?=htmlspecialchars($r['category']?:'General')?> · per <?=htmlspecialchars($r['unit'])?></div>
          <div class="ci-price">₹<?=number_format($r['price'],2)?></div>
        </div>

        <div class="ci-right">
          <div class="ci-total">₹<?=number_format($lineTotal,2)?></div>

          <!-- Update qty -->
          <form method="POST" action="cart.php" style="display:flex;align-items:center;gap:6px;">
            <input type="hidden" name="cart_id" value="<?=$r['cid']?>"/>
            <div class="qty-spin">
              <button type="submit" name="update_qty" class="qty-btn"
                onclick="document.getElementById('qty<?=$r['cid']?>').value=Math.max(1,+document.getElementById('qty<?=$r['cid']?>').value-1)"
                formnovalidate>−</button>
              <input type="number" id="qty<?=$r['cid']?>" name="quantity"
                value="<?=$r['quantity']?>" min="1" max="<?=$r['stock']?>"
                style="width:40px;text-align:center;background:transparent;border:none;color:var(--tx-1);font-size:15px;font-weight:700;font-family:inherit;outline:none;"
                onchange="this.form.submit()"/>
              <button type="submit" name="update_qty" class="qty-btn"
                onclick="document.getElementById('qty<?=$r['cid']?>').value=Math.min(<?=$r['stock']?>,+document.getElementById('qty<?=$r['cid']?>').value+1)"
                formnovalidate>+</button>
            </div>
          </form>

          <!-- Remove -->
          <form method="POST" action="cart.php">
            <input type="hidden" name="cart_id" value="<?=$r['cid']?>"/>
            <button type="submit" name="remove" class="ci-remove" title="Remove">✕</button>
          </form>
        </div>

      </div>
      <?php endforeach; ?>

      <!-- Clear cart -->
      <form method="POST" action="cart.php" style="margin-top:8px;">
        <input type="hidden" name="cart_id" value="0"/>
        <button type="submit" name="remove" class="btn btn-danger btn-sm"
          onclick="return confirm('Remove all items?')"
          style="background:transparent;border:1px solid rgba(244,63,94,0.3);color:#fda4af;box-shadow:none;">
          🗑️ Clear Cart
        </button>
      </form>
    </div>

    <!-- Order Summary -->
    <div>
      <div class="order-summary reveal reveal-d2">
        <div class="os-title">📋 Order Summary</div>

        <?php foreach($cart_rows as $r): ?>
        <div class="os-row">
          <span class="os-lbl"><?=htmlspecialchars($r['name'])?> ×<?=$r['quantity']?></span>
          <span class="os-val">₹<?=number_format($r['price']*$r['quantity'],2)?></span>
        </div>
        <?php endforeach; ?>

        <hr class="os-divider"/>

        <div class="os-row">
          <span class="os-lbl">Subtotal</span>
          <span class="os-val">₹<?=number_format($subtotal,2)?></span>
        </div>
        <div class="os-row">
          <span class="os-lbl">Delivery</span>
          <span class="os-val" style="color:<?=$delivery==0?'var(--g400)':'inherit'?>">
            <?=$delivery==0?'🎉 FREE':'₹'.number_format($delivery,2)?>
          </span>
        </div>
        <?php if($delivery>0): ?>
        <div style="font-size:12px;color:var(--tx-4);padding:0 0 10px;text-align:right;">
          Add ₹<?=number_format(500-$subtotal,2)?> more for free delivery!
        </div>
        <?php endif; ?>

        <div class="os-total-row">
          <span class="os-total-lbl">Total</span>
          <span class="os-total-val">₹<?=number_format($total,2)?></span>
        </div>

        <form method="POST" action="cart.php" style="margin-top:20px;">
          <button type="submit" name="checkout" class="btn btn-primary"
            onclick="return confirm('Confirm your order of ₹<?=number_format($total,2)?>?')">
            ✅ Place Order
          </button>
        </form>

        <div style="margin-top:16px;padding:14px;background:rgba(34,197,94,0.06);border-radius:var(--r-md);font-size:12px;color:var(--tx-4);text-align:center;">
          🔒 Secure checkout · 🌿 Fresh guarantee · 🚚 Fast delivery
        </div>
      </div>
    </div>

  </div>

  <?php else: ?>
  <div class="empty-state reveal" style="padding:80px 20px;">
    <span class="empty-emoji">🛍️</span>
    <div class="empty-title">Your cart is empty</div>
    <div class="empty-desc">Looks like you haven't added anything yet. Start shopping!</div>
    <a href="shop.php" class="btn btn-primary" style="width:auto;padding:14px 32px;">🛒 Browse Products</a>
  </div>
  <?php endif; ?>

</div>

<!-- Checkout Success Modal -->
<?php if ($msg==='checkout'): ?>
<div class="modal-backdrop show" id="successModal">
  <div class="modal" style="max-width:480px;text-align:center;">
    <div style="padding:48px 40px;">
      <div style="font-size:72px;margin-bottom:20px;animation:emptyBounce 1s ease infinite;">🎉</div>
      <h2 style="font-size:26px;font-weight:800;color:var(--tx-1);margin-bottom:10px;">Order Placed!</h2>
      <p style="color:var(--tx-4);margin-bottom:28px;">Your fresh produce is confirmed and will be delivered soon!</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="shop.php"      class="btn btn-primary" style="width:auto;padding:13px 26px;">🛒 Shop More</a>
        <a href="dashboard.php" class="btn btn-ghost"   style="padding:13px 22px;">Dashboard</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?=date('Y')?></div>
    <div class="footer-links"><a href="shop.php">Shop</a><a href="dashboard.php">Dashboard</a></div>
  </div>
</footer>

<script>
const obs = new IntersectionObserver(e => e.forEach(en => { if(en.isIntersecting) en.target.classList.add('visible'); }), {threshold:0.1});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
const stb = document.getElementById('scrollTop');
window.addEventListener('scroll', () => stb.classList.toggle('visible', window.scrollY>400));

<?php if($msg==='checkout'): ?>
// Confetti on order success
(function(){
  const colors=['#22c55e','#84cc16','#f97316','#eab308','#10b981'];
  for(let i=0;i<80;i++){
    const p=document.createElement('div');
    p.className='confetti-piece';
    p.style.cssText=`left:${Math.random()*100}vw;background:${colors[Math.floor(Math.random()*colors.length)]};--d:${1+Math.random()*2}s;animation-delay:${Math.random()*0.5}s;border-radius:${Math.random()>.5?'50%':'2px'};width:${8+Math.random()*8}px;height:${8+Math.random()*8}px;`;
    document.body.appendChild(p);
    setTimeout(()=>p.remove(),3000);
  }
  // Auto-close modal
  document.getElementById('successModal')?.addEventListener('click', e => {
    if(e.target===document.getElementById('successModal')) e.target.classList.remove('show');
  });
})();
<?php endif; ?>
</script>
</body>
</html>
