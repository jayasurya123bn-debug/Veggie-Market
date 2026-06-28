<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || !isAdmin()) { header('Location: ../index.php'); exit(); }

$msg = '';

// Delete product
if (isset($_POST['delete_product'])) {
    $pid = (int)$_POST['product_id'];
    // Remove from cart first
    $conn->query("DELETE FROM cart WHERE product_id=$pid");
    // Get image to delete
    $pr = $conn->query("SELECT image FROM products WHERE id=$pid")->fetch_assoc();
    if ($pr && $pr['image'] && file_exists('../uploads/'.$pr['image'])) {
        @unlink('../uploads/'.$pr['image']);
    }
    $conn->query("DELETE FROM products WHERE id=$pid");
    $msg = 'deleted';
}

// Toggle featured
if (isset($_POST['toggle_featured'])) {
    $pid = (int)$_POST['product_id'];
    $nf  = (int)$_POST['new_featured'];
    $conn->query("UPDATE products SET is_featured=$nf WHERE id=$pid");
    $msg = $nf ? 'featured' : 'unfeatured';
}

$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$where    = "WHERE 1=1";
if ($search)   $where .= " AND (p.name LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($category) $where .= " AND p.category='".mysqli_real_escape_string($conn,$category)."'";

$products = $conn->query("SELECT p.*, u.username FROM products p LEFT JOIN users u ON p.added_by=u.id $where ORDER BY p.created_at DESC");
$cats_res = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category!='' ORDER BY category");
$emojis=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿','Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Products – Admin Panel</title>
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
    .prod-table-wrap{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--r-xl);overflow:hidden;backdrop-filter:blur(20px);}
    .prod-header{padding:20px 24px;border-bottom:1px solid var(--glass-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
  </style>
</head>
<body>
<div class="mesh-bg"><div class="orb" style="width:500px;height:500px;background:rgba(34,197,94,0.07);top:-200px;left:-100px;--d:22s;--tx:60px;--ty:50px;"></div></div>

<div class="admin-header">
  <a href="../dashboard.php" style="text-decoration:none;"><div class="admin-logo">🥦 <span>VeggieMarket</span> <span class="admin-badge">👑 Admin</span></div></a>
  <div class="admin-nav">
    <a href="index.php">📊 Overview</a>
    <a href="users.php">👥 Users</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="../shop.php">🛒 Live Shop</a>
    <a href="../logout.php" class="danger">🚪 Logout</a>
  </div>
</div>

<div class="admin-body">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div>
      <div style="font-size:26px;font-weight:900;color:var(--tx-1);margin-bottom:4px;">📦 Manage Products</div>
      <div style="color:var(--tx-4);"><?= $products->num_rows ?> products found</div>
    </div>
    <a href="../add_product.php" class="btn btn-primary" style="width:auto;padding:12px 22px;">➕ Add Product</a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success" style="margin-bottom:16px;">
    <?php $msgs=['deleted'=>'🗑️ Product deleted.','featured'=>'⭐ Product featured!','unfeatured'=>'Product unfeatured.'];echo $msgs[$msg]??'Done.'; ?>
  </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="filter-bar" style="margin-bottom:20px;">
    <a href="products.php" class="chip <?= !$category?'active':'' ?>">🌿 All</a>
    <?php $cats_res->data_seek(0); while($cat=$cats_res->fetch_assoc()):
      $c=$cat['category'];$e=$emojis[$c]??'🌱'; ?>
    <a href="products.php?category=<?= urlencode($c) ?><?= $search?'&search='.urlencode($search):'' ?>"
       class="chip <?= $category===$c?'active':'' ?>"><?= $e ?> <?= htmlspecialchars($c) ?></a>
    <?php endwhile; ?>
  </div>

  <div class="prod-table-wrap">
    <div class="prod-header">
      <h2 style="font-size:18px;font-weight:800;color:var(--tx-1);">All Products</h2>
      <form method="GET" action="products.php" style="display:flex;gap:8px;">
        <?php if($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"/><?php endif; ?>
        <div class="search-wrap" style="min-width:220px;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..."/>
        </div>
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
        <?php if($search||$category): ?><a href="products.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
      </form>
    </div>
    <div class="table-wrap" style="border-radius:0;">
      <table class="data-table">
        <thead>
          <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Added By</th><th>Featured</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php while($p=$products->fetch_assoc()):
            $e=$emojis[$p['category']]??'🥦';
            $sc=$p['stock']>10?'ok':($p['stock']>0?'low':'out');
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <?php if($p['image']&&file_exists('../uploads/'.$p['image'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div style="width:40px;height:40px;border-radius:8px;background:rgba(34,197,94,0.1);display:grid;place-items:center;font-size:20px;flex-shrink:0;"><?= $e ?></div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:700;color:var(--tx-1);"><?= htmlspecialchars($p['name']) ?></div>
                  <div style="font-size:11px;color:var(--tx-4);">#<?= $p['id'] ?></div>
                </div>
              </div>
            </td>
            <td><span class="pill pill-green"><?= $e ?> <?= htmlspecialchars($p['category']?:'—') ?></span></td>
            <td class="text-green font-bold">₹<?= number_format($p['price'],2) ?>/<?= htmlspecialchars($p['unit']) ?></td>
            <td>
              <span style="color:<?= $sc==='ok'?'var(--g400)':($sc==='low'?'#fb923c':'#f87171') ?>;font-weight:700;font-size:13px;">
                <?= $p['stock']>0 ? $p['stock'] : '❌ Out' ?>
              </span>
            </td>
            <td style="font-size:13px;color:var(--tx-3);"><?= htmlspecialchars($p['username']??'—') ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
                <input type="hidden" name="new_featured" value="<?= $p['is_featured']?0:1 ?>"/>
                <button type="submit" name="toggle_featured" style="background:none;border:none;cursor:pointer;font-size:18px;" title="<?= $p['is_featured']?'Unfeature':'Feature' ?>">
                  <?= $p['is_featured'] ? '⭐' : '☆' ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="../add_product.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;">✏️ Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product permanently?')">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
                  <button type="submit" name="delete_product" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:12px;color:#f43f5e;">🗑️</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
