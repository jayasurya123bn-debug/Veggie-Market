<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];
$cartAdded = '';

// Add to cart
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])) {
    $pid = (int)$_POST['product_id'];
    $qty = max(1,(int)($_POST['quantity']??1));
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
    $cartAdded = 'yes';
}

// Filters
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = trim($_GET['sort']     ?? 'newest');

$where  = "WHERE 1=1"; $params=[]; $types='';
if ($search)   { $like="%$search%"; $where.=" AND (p.name LIKE ? OR p.description LIKE ?)"; $params[]=$like;$params[]=$like; $types.='ss'; }
if ($category) { $where.=" AND p.category=?"; $params[]=$category; $types.='s'; }

$order = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    'stock'      => 'p.stock DESC',
    default      => 'p.created_at DESC',
};

$stmt = $conn->prepare("SELECT p.*,u.username FROM products p LEFT JOIN users u ON p.added_by=u.id $where ORDER BY $order");
if ($types) $stmt->bind_param($types,...$params);
$stmt->execute();
$products = $stmt->get_result();

// Categories
$cats = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category!='' ORDER BY category");

$emojis=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿',
         'Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'];
$count = $products->num_rows;
$products->data_seek(0);

// Build modal data
$modal_data = [];
$products->data_seek(0);
while($p=$products->fetch_assoc()) $modal_data[]=$p;
$products->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Shop – Veggie Market 🛒</title>
  <meta name="description" content="Browse fresh vegetables, fruits, and herbs. Best quality produce."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:500px;height:500px;background:rgba(34,197,94,0.08);top:-100px;right:-80px;--d:20s;--tx:-40px;--ty:60px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <!-- Top bar -->
  <div class="shop-topbar">
    <div>
      <div class="page-eyebrow">Fresh Produce</div>
      <h1 class="page-title" style="font-size:clamp(24px,3vw,34px);">
        Market Shop 🛒
        <span style="font-size:16px;font-weight:500;color:var(--tx-4);margin-left:10px;"><?=$count?> product<?=$count!=1?'s':''?></span>
      </h1>
    </div>

    <form method="GET" action="shop.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;" id="filterForm">
      <?php if($category):?><input type="hidden" name="category" value="<?=htmlspecialchars($category)?>"/><?php endif;?>
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" id="shopSearch"
          value="<?=htmlspecialchars($search)?>"
          placeholder="Search veggies, fruits..."/>
      </div>
      <select name="sort" class="form-control" style="width:auto;padding:11px 36px 11px 14px;border-radius:var(--r-full);" onchange="this.form.submit()">
        <option value="newest"     <?=$sort==='newest'    ?'selected':''?>>Newest</option>
        <option value="price_asc"  <?=$sort==='price_asc' ?'selected':''?>>Price ↑</option>
        <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Price ↓</option>
        <option value="name"       <?=$sort==='name'      ?'selected':''?>>Name A–Z</option>
        <option value="stock"      <?=$sort==='stock'     ?'selected':''?>>In Stock</option>
      </select>
      <a href="add_product.php" class="btn btn-outline" style="padding:11px 18px;">➕ Add</a>
    </form>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <a href="shop.php<?=$search?'?search='.urlencode($search):''?>"
       class="chip <?=!$category?'active':''?>">🌿 All</a>
    <?php $cats->data_seek(0); while($cat=$cats->fetch_assoc()):
      $c=$cat['category']; $e=$emojis[$c]??'🌱'; ?>
    <a href="shop.php?category=<?=urlencode($c)?><?=$search?'&search='.urlencode($search):''?>"
       class="chip <?=$category===$c?'active':''?>"><?=$e?> <?=htmlspecialchars($c)?></a>
    <?php endwhile; ?>
  </div>

  <!-- Grid -->
  <?php if(count($modal_data)>0): ?>
  <div class="products-grid" id="productsGrid">
    <?php foreach($modal_data as $i=>$p):
      $e   = $emojis[$p['category']]??'🥦';
      $sc  = $p['stock']>10?'ok':($p['stock']>0?'low':'out');
      $stxt= $p['stock']>10?"● {$p['stock']} in stock":($p['stock']>0?"● Only {$p['stock']} left":"● Out of stock");
    ?>
    <div class="product-card reveal" style="transition-delay:<?= min($i*0.05,0.4) ?>s"
         onclick="openModal(<?=$i?>)" id="pcard-<?=$i?>">

      <div class="pc-img">
        <?php if($p['image']&&file_exists('uploads/'.$p['image'])): ?>
          <img src="uploads/<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>" loading="lazy"/>
        <?php else: ?>
          <div class="pc-placeholder"><?=$e?></div>
        <?php endif; ?>

        <?php if($p['stock']<=5&&$p['stock']>0): ?>
          <span class="pc-flag pc-flag-orange">🔥 Low Stock</span>
        <?php elseif($p['stock']>20): ?>
          <span class="pc-flag pc-flag-green">✅ Fresh</span>
        <?php elseif($p['stock']==0): ?>
          <span class="pc-flag" style="background:#555;">❌ Sold Out</span>
        <?php endif; ?>

        <button class="pc-wish" onclick="event.stopPropagation();wishToggle(this)" title="Add to wishlist">🤍</button>
      </div>

      <div class="pc-body">
        <div class="pc-cat"><?=$e?> <?=htmlspecialchars($p['category']?:'General')?></div>
        <div class="pc-name"><?=htmlspecialchars($p['name'])?></div>
        <?php if($p['description']): ?>
          <div class="pc-desc"><?=htmlspecialchars($p['description'])?></div>
        <?php endif; ?>

        <div class="pc-footer">
          <div>
            <div class="pc-price">
              <span class="prc-sym">₹</span>
              <span class="prc-val"><?=number_format($p['price'],2)?></span>
              <span class="prc-unit">/<?=htmlspecialchars($p['unit'])?></span>
            </div>
            <div class="pc-stock stock-<?=$sc?>"><?=$stxt?></div>
          </div>

          <?php if($p['stock']>0): ?>
          <div class="flex-c gap-2" onclick="event.stopPropagation()">
            <div class="qty-spin">
              <button class="qty-btn" onclick="changeQty(<?=$i?>,-1)">−</button>
              <span class="qty-num" id="qty-<?=$i?>">1</span>
              <button class="qty-btn" onclick="changeQty(<?=$i?>,1)">+</button>
            </div>
            <form method="POST" action="shop.php" style="display:inline;">
              <?php if($search):   ?><input type="hidden" name="search"   value="<?=htmlspecialchars($search)?>"/><?php endif;?>
              <?php if($category): ?><input type="hidden" name="category" value="<?=htmlspecialchars($category)?>"/><?php endif;?>
              <input type="hidden" name="product_id" value="<?=$p['id']?>"/>
              <input type="hidden" name="quantity" id="hqty-<?=$i?>" value="1"/>
              <button type="submit" name="add_to_cart" class="atc-btn">🛒</button>
            </form>
          </div>
          <?php else: ?>
            <button class="atc-btn" disabled>Sold Out</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="empty-state">
    <span class="empty-emoji">🔍</span>
    <div class="empty-title">No products found</div>
    <div class="empty-desc"><?=$search?"No results for \"".htmlspecialchars($search)."\"":'The market is empty!'?></div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="shop.php" class="btn btn-ghost" style="padding:12px 22px;">🔄 Clear filters</a>
      <a href="add_product.php" class="btn btn-primary" style="width:auto;padding:12px 24px;">➕ Add Product</a>
      <a href="seed.php" class="btn btn-ghost" style="padding:12px 22px;">🌱 Load Demo Data</a>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- ── Product Modal ── -->
<div class="modal-backdrop" id="modalBd" onclick="closeModal(event)">
  <div class="modal" id="modal">
    <div class="modal-img" id="modalImg">
      <div class="modal-img-placeholder" id="modalPlaceholder">🥦</div>
      <img id="modalImgEl" src="" alt="" style="display:none;"/>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="modal-cat" id="mCat">Category</div>
      <div class="modal-name" id="mName">Product Name</div>
      <div class="modal-desc" id="mDesc"></div>
      <div class="modal-meta">
        <div class="mm-item"><div class="mm-lbl">Price</div><div class="mm-val text-green" id="mPrice">₹0</div></div>
        <div class="mm-item"><div class="mm-lbl">Stock</div><div class="mm-val" id="mStock">0</div></div>
        <div class="mm-item"><div class="mm-lbl">Unit</div><div class="mm-val" id="mUnit">kg</div></div>
        <div class="mm-item"><div class="mm-lbl">Seller</div><div class="mm-val" id="mSeller">-</div></div>
      </div>
      <div class="modal-actions">
        <div class="qty-spin">
          <button class="qty-btn" onclick="changeModalQty(-1)">−</button>
          <span class="qty-num" id="mQty">1</span>
          <button class="qty-btn" onclick="changeModalQty(1)">+</button>
        </div>
        <form method="POST" action="shop.php" id="modalForm" style="flex:1;">
          <?php if($search):   ?><input type="hidden" name="search"   value="<?=htmlspecialchars($search)?>"/><?php endif;?>
          <?php if($category): ?><input type="hidden" name="category" value="<?=htmlspecialchars($category)?>"/><?php endif;?>
          <input type="hidden" name="product_id" id="mPid" value=""/>
          <input type="hidden" name="quantity"   id="mQtyH" value="1"/>
          <button type="submit" name="add_to_cart" class="btn btn-primary" style="padding:12px 20px;">🛒 Add to Cart</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Toast wrap -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- Scroll to top -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?=date('Y')?> · Fresh, Local, Delicious</div>
    <div class="footer-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="add_product.php">Sell</a>
      <a href="cart.php">Cart</a>
    </div>
  </div>
</footer>

<script>
const products = <?= json_encode($modal_data, JSON_HEX_TAG) ?>;
const emojis   = <?= json_encode($emojis) ?>;

// Quantity per card
const qtys = {};
function changeQty(i, d) {
  qtys[i] = Math.max(1, (qtys[i]||1) + d);
  document.getElementById('qty-'+i).textContent  = qtys[i];
  document.getElementById('hqty-'+i).value = qtys[i];
}

// Modal
let mQtyVal = 1;
function openModal(i) {
  const p  = products[i];
  const bd = document.getElementById('modalBd');
  const e  = emojis[p.category] || '🥦';

  document.getElementById('mCat').textContent  = e + ' ' + (p.category || 'General');
  document.getElementById('mName').textContent = p.name;
  document.getElementById('mDesc').textContent = p.description || 'No description available.';
  document.getElementById('mPrice').textContent= '₹' + parseFloat(p.price).toFixed(2) + ' / ' + p.unit;
  document.getElementById('mStock').textContent= p.stock > 0 ? p.stock + ' available' : 'Out of stock';
  document.getElementById('mUnit').textContent = p.unit;
  document.getElementById('mSeller').textContent = p.username || 'Market';
  document.getElementById('mPid').value = p.id;
  mQtyVal = 1; document.getElementById('mQty').textContent = 1; document.getElementById('mQtyH').value = 1;

  const imgEl = document.getElementById('modalImgEl');
  const ph    = document.getElementById('modalPlaceholder');
  if (p.image) {
    imgEl.src = 'uploads/' + p.image;
    imgEl.style.display = 'block'; ph.style.display = 'none';
  } else {
    imgEl.style.display = 'none'; ph.style.display = 'grid';
    ph.textContent = e;
  }
  bd.classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeModal(e) {
  if (e && e.target !== document.getElementById('modalBd')) return;
  document.getElementById('modalBd').classList.remove('show');
  document.body.style.overflow = '';
}
function changeModalQty(d) {
  mQtyVal = Math.max(1, mQtyVal + d);
  document.getElementById('mQty').textContent  = mQtyVal;
  document.getElementById('mQtyH').value = mQtyVal;
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });

// Wishlist toggle
function wishToggle(btn) {
  const active = btn.classList.toggle('wished');
  btn.textContent = active ? '❤️' : '🤍';
  showToast(active ? '❤️ Added to wishlist!' : '🤍 Removed from wishlist', 'green');
}

// Toast
function showToast(msg, type='green') {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = `<span class="toast-icon">${type==='green'?'✅':'❌'}</span>${msg}`;
  wrap.appendChild(t);
  requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 3000);
}

<?php if ($cartAdded): ?>
window.addEventListener('load', () => showToast('🛒 Added to cart successfully!'));
<?php endif; ?>

// Auto-search
let st;
document.getElementById('shopSearch').addEventListener('input', function() {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

// Scroll reveal
const obs = new IntersectionObserver(e => e.forEach(en => { if(en.isIntersecting) en.target.classList.add('visible'); }), {threshold:0.08});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

// Scroll-to-top
const stb = document.getElementById('scrollTop');
window.addEventListener('scroll', () => stb.classList.toggle('visible', window.scrollY>400));
</script>
</body>
</html>
