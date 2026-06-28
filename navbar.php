<?php
// navbar.php — shared navigation partial
// Usage: include 'navbar.php'; at top of any protected page
// Requires: $conn, $_SESSION to be available

$current_page = basename($_SERVER['PHP_SELF']);

// Cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $cc  = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    $cart_count = $cc ? ($cc->fetch_assoc()['total'] ?? 0) : 0;
}
?>
<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="mainNav">
  <div class="navbar-inner">

    <!-- Logo -->
    <a href="dashboard.php" class="logo">
      <div class="logo-icon">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
          <circle cx="14" cy="14" r="13" fill="url(#lg1)"/>
          <text x="14" y="19" text-anchor="middle" font-size="14">🥦</text>
          <defs>
            <linearGradient id="lg1" x1="0" y1="0" x2="28" y2="28">
              <stop offset="0%" stop-color="#22c55e"/>
              <stop offset="100%" stop-color="#10b981"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <span class="logo-text">Veggie<span style="color:var(--green-400)">Market</span></span>
    </a>

    <!-- Desktop Links -->
    <ul class="nav-links" id="navLinks">
      <li>
        <a href="dashboard.php" class="nav-link <?= $current_page==='dashboard.php'?'active':'' ?>" id="navDashboard">
          <span class="nav-icon">⚡</span> Dashboard
        </a>
      </li>
      <li>
        <a href="shop.php" class="nav-link <?= $current_page==='shop.php'?'active':'' ?>" id="navShop">
          <span class="nav-icon">🛒</span> Shop
        </a>
      </li>
      <li>
        <a href="add_product.php" class="nav-link <?= $current_page==='add_product.php'?'active':'' ?>" id="navAdd">
          <span class="nav-icon">➕</span> Add Product
        </a>
      </li>
      <li>
        <a href="cart.php" class="nav-link nav-cart <?= $current_page==='cart.php'?'active':'' ?>" id="navCart">
          <span class="nav-icon">🛍️</span> Cart
          <?php if ($cart_count > 0): ?>
            <span class="cart-badge" id="cartBadge"><?= $cart_count ?></span>
          <?php else: ?>
            <span class="cart-badge hidden" id="cartBadge">0</span>
          <?php endif; ?>
        </a>
      </li>
    </ul>

    <!-- Right Side -->
    <div class="nav-right">
      <div class="nav-user" id="navUserMenu">
        <div class="nav-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></div>
        <span class="nav-username"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
        <span class="nav-chevron">▾</span>
        <div class="nav-dropdown">
          <a href="dashboard.php" class="dropdown-item">⚡ Dashboard</a>
          <a href="profile.php"   class="dropdown-item">👤 My Profile</a>
          <a href="orders.php"    class="dropdown-item">📦 My Orders</a>
          <a href="wishlist.php"  class="dropdown-item">❤️ Wishlist</a>
          <a href="cart.php"      class="dropdown-item">🛍️ My Cart <span style="color:var(--green-400)"><?= $cart_count ?></span></a>
          <?php if (isAdmin()): ?>
          <div class="dropdown-divider"></div>
          <a href="admin/index.php" class="dropdown-item" style="color:#a78bfa;">👑 Admin Panel</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <a href="logout.php"   class="dropdown-item dropdown-logout">🚪 Logout</a>
        </div>
      </div>
      <!-- Mobile Hamburger -->
      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
</nav>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobileDrawer">
  <div class="drawer-header">
    <div class="logo">
      <div class="logo-icon">🥦</div>
      <span class="logo-text">VeggieMarket</span>
    </div>
    <button class="drawer-close" id="drawerClose">✕</button>
  </div>
  <nav class="drawer-nav">
    <a href="dashboard.php" class="drawer-link <?= $current_page==='dashboard.php'?'active':'' ?>">⚡ Dashboard</a>
    <a href="shop.php"      class="drawer-link <?= $current_page==='shop.php'     ?'active':'' ?>">🛒 Shop</a>
    <a href="add_product.php" class="drawer-link <?= $current_page==='add_product.php'?'active':'' ?>">➕ Add Product</a>
    <a href="cart.php"      class="drawer-link <?= $current_page==='cart.php'     ?'active':'' ?>">🛍️ Cart (<?= $cart_count ?>)</a>
    <a href="orders.php"    class="drawer-link <?= $current_page==='orders.php'   ?'active':'' ?>">📦 My Orders</a>
    <a href="wishlist.php"  class="drawer-link <?= $current_page==='wishlist.php' ?'active':'' ?>">❤️ Wishlist</a>
    <a href="profile.php"   class="drawer-link <?= $current_page==='profile.php'  ?'active':'' ?>">👤 Profile</a>
    <?php if (isAdmin()): ?>
    <a href="admin/index.php" class="drawer-link" style="color:#a78bfa;">👑 Admin Panel</a>
    <?php endif; ?>
    <a href="logout.php"    class="drawer-link drawer-logout">🚪 Logout</a>
  </nav>
</div>
<div class="drawer-overlay" id="drawerOverlay"></div>

<script>
(function() {
  // Sticky navbar shrink
  window.addEventListener('scroll', function() {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 40);
  });

  // Hamburger / mobile drawer
  const ham = document.getElementById('hamburger');
  const drawer = document.getElementById('mobileDrawer');
  const overlay = document.getElementById('drawerOverlay');
  const closeBtn = document.getElementById('drawerClose');

  function openDrawer()  { drawer.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow='hidden'; ham.classList.add('active'); }
  function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow=''; ham.classList.remove('active'); }

  ham.addEventListener('click', openDrawer);
  closeBtn.addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);

  // User dropdown
  const userMenu = document.getElementById('navUserMenu');
  if (userMenu) {
    userMenu.addEventListener('click', function(e) {
      e.stopPropagation();
      userMenu.classList.toggle('open');
    });
    document.addEventListener('click', function() { userMenu.classList.remove('open'); });
  }
})();
</script>
