<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];
$msg = '';
$errors = [];

// Load user data
$user_res = $conn->prepare("SELECT * FROM users WHERE id=?");
$user_res->bind_param("i", $uid); $user_res->execute();
$user = $user_res->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
    $email     = trim($_POST['email'] ?? '');
    $username  = trim(htmlspecialchars($_POST['username'] ?? ''));

    // Check uniqueness
    $chk = $conn->prepare("SELECT id FROM users WHERE (email=? OR username=?) AND id!=?");
    $chk->bind_param("ssi", $email, $username, $uid); $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $errors[] = "That email or username is already taken by another account.";
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, username=? WHERE id=?");
        $upd->bind_param("sssi", $full_name, $email, $username, $uid); $upd->execute();
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $full_name;
        $msg = 'profile_updated';
        // Reload
        $user_res2 = $conn->prepare("SELECT * FROM users WHERE id=?");
        $user_res2->bind_param("i",$uid); $user_res2->execute();
        $user = $user_res2->get_result()->fetch_assoc();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $errors[] = "New passwords do not match.";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid); $upd->execute();
        $msg = 'password_changed';
    }
}

// Stats
$total_orders    = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid")->fetch_assoc()['c'] ?? 0;
$total_spent_res = $conn->query("SELECT SUM(total) as s FROM orders WHERE user_id=$uid");
$total_spent     = $total_spent_res ? ($total_spent_res->fetch_assoc()['s'] ?? 0) : 0;
$my_products     = $conn->query("SELECT COUNT(*) as c FROM products WHERE added_by=$uid")->fetch_assoc()['c'] ?? 0;
$wish_count_res  = $conn->query("SHOW TABLES LIKE 'wishlist'");
$wish_count = 0;
if ($wish_count_res && $wish_count_res->num_rows > 0) {
    $wc = $conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id=$uid");
    $wish_count = $wc ? ($wc->fetch_assoc()['c'] ?? 0) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile – Veggie Market 👤</title>
  <meta name="description" content="Manage your Veggie Market profile and account settings."/>
  <link rel="stylesheet" href="css/style.css"/>
  <style>
    .profile-layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media(max-width:900px){.profile-layout{grid-template-columns:1fr;}}
    .profile-sidebar {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: var(--r-xl);
      padding: 30px 24px;
      backdrop-filter:blur(20px);
      text-align:center;
      position:sticky;top:100px;
    }
    .profile-avatar {
      width:90px;height:90px;border-radius:50%;
      background: linear-gradient(135deg, var(--g500), var(--emerald));
      display:grid;place-items:center;
      font-size:36px;font-weight:900;color:#fff;
      margin:0 auto 16px;
      box-shadow:0 0 0 4px rgba(34,197,94,0.2), 0 8px 32px rgba(34,197,94,0.25);
    }
    .profile-name { font-size:22px; font-weight:800; color:var(--tx-1); margin-bottom:4px; }
    .profile-username { font-size:14px; color:var(--tx-4); margin-bottom:6px; }
    .profile-email { font-size:13px; color:var(--tx-4); margin-bottom:16px; }
    .profile-badge {
      display:inline-flex;align-items:center;gap:6px;
      padding:5px 14px;border-radius:var(--r-full);
      font-size:12px;font-weight:700;
      background:rgba(34,197,94,0.12);color:var(--g400);
      border:1px solid rgba(34,197,94,0.2);margin-bottom:20px;
    }
    .profile-badge.admin { background:rgba(139,92,246,0.12);color:#a78bfa;border-color:rgba(139,92,246,0.2); }
    .profile-stats { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:16px; }
    .ps-item {
      background:rgba(255,255,255,0.04);border-radius:var(--r-md);
      padding:12px 8px; text-align:center;
      border:1px solid var(--glass-border);
    }
    .ps-num { font-size:20px; font-weight:900; color:var(--g400); }
    .ps-lbl { font-size:11px; color:var(--tx-4); font-weight:600; margin-top:2px; }
    .profile-links { display:flex; flex-direction:column; gap:8px; margin-top:20px; }
    .profile-link {
      display:flex;align-items:center;gap:10px;
      padding:11px 14px;border-radius:var(--r-md);
      font-size:14px;font-weight:600;color:var(--tx-2);
      background:rgba(255,255,255,0.04);border:1px solid var(--glass-border);
      text-decoration:none;transition:all .2s;
    }
    .profile-link:hover { background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.25);color:var(--tx-1); }
    .profile-link.danger { color:#fda4af; }
    .profile-link.danger:hover { background:rgba(244,63,94,0.1);border-color:rgba(244,63,94,0.2); }
    .profile-main { display:flex;flex-direction:column;gap:20px; }
    .profile-section {
      background:var(--glass-bg);border:1px solid var(--glass-border);
      border-radius:var(--r-xl);padding:28px;backdrop-filter:blur(20px);
    }
    .ps-title { font-size:18px;font-weight:800;color:var(--tx-1);margin-bottom:20px;
      display:flex;align-items:center;gap:8px; }
    .form-row { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
    @media(max-width:600px){.form-row{grid-template-columns:1fr;}}
    .member-since { font-size:12px;color:var(--tx-4);text-align:center;margin-top:16px; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:500px;height:500px;background:rgba(34,197,94,0.07);top:-150px;left:-200px;--d:24s;--tx:80px;--ty:60px;"></div>
  <div class="orb" style="width:350px;height:350px;background:rgba(139,92,246,0.06);bottom:-50px;right:-80px;--d:16s;--tx:-50px;--ty:-30px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Account</div>
      <h1 class="page-title">My Profile 👤</h1>
      <p class="page-sub">Manage your account settings and preferences</p>
    </div>
    <a href="dashboard.php" class="btn btn-ghost" style="padding:11px 20px;">← Dashboard</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-warn reveal" style="margin-bottom:16px;">
      <?php foreach($errors as $e): ?><div>⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($msg === 'profile_updated'): ?>
    <div class="alert alert-success reveal" style="margin-bottom:16px;">✅ Profile updated successfully!</div>
  <?php elseif ($msg === 'password_changed'): ?>
    <div class="alert alert-success reveal" style="margin-bottom:16px;">🔒 Password changed successfully!</div>
  <?php endif; ?>

  <div class="profile-layout">

    <!-- Sidebar -->
    <div class="profile-sidebar reveal">
      <div class="profile-avatar"><?= strtoupper(substr($user['username']??'U',0,1)) ?></div>
      <div class="profile-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
      <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
      <div class="profile-email">✉️ <?= htmlspecialchars($user['email']) ?></div>
      <?php if($user['is_admin']): ?>
        <span class="profile-badge admin">👑 Admin</span>
      <?php else: ?>
        <span class="profile-badge">🌿 Member</span>
      <?php endif; ?>

      <div class="profile-stats">
        <div class="ps-item">
          <div class="ps-num"><?= $total_orders ?></div>
          <div class="ps-lbl">Orders</div>
        </div>
        <div class="ps-item">
          <div class="ps-num"><?= $my_products ?></div>
          <div class="ps-lbl">Products</div>
        </div>
        <div class="ps-item">
          <div class="ps-num"><?= $wish_count ?></div>
          <div class="ps-lbl">Wishlisted</div>
        </div>
        <div class="ps-item">
          <div class="ps-num">₹<?= number_format($total_spent,0) ?></div>
          <div class="ps-lbl">Spent</div>
        </div>
      </div>

      <div class="profile-links" style="margin-top:20px;">
        <a href="orders.php"   class="profile-link">📦 My Orders</a>
        <a href="wishlist.php" class="profile-link">❤️ Wishlist (<?= $wish_count ?>)</a>
        <a href="cart.php"     class="profile-link">🛍️ My Cart</a>
        <?php if($user['is_admin']): ?>
        <a href="admin/index.php" class="profile-link" style="color:#a78bfa;">👑 Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php"   class="profile-link danger">🚪 Logout</a>
      </div>

      <div class="member-since">
        Member since <?= date('F Y', strtotime($user['created_at'])) ?>
      </div>
    </div>

    <!-- Main -->
    <div class="profile-main">

      <!-- Edit Profile -->
      <div class="profile-section reveal reveal-d1">
        <div class="ps-title">✏️ Edit Profile</div>
        <form method="POST" action="profile.php">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control"
                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                placeholder="Your full name" maxlength="100"/>
            </div>
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control"
                value="<?= htmlspecialchars($user['username']) ?>"
                placeholder="username" required maxlength="50"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control"
              value="<?= htmlspecialchars($user['email']) ?>"
              placeholder="email@example.com" required/>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary" style="width:auto;padding:12px 28px;">
            💾 Save Changes
          </button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="profile-section reveal reveal-d2">
        <div class="ps-title">🔒 Change Password</div>
        <form method="POST" action="profile.php">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required/>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required minlength="6"/>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required/>
            </div>
          </div>
          <button type="submit" name="change_password" class="btn btn-outline" style="width:auto;padding:12px 28px;">
            🔑 Update Password
          </button>
        </form>
      </div>

      <!-- Account Info -->
      <div class="profile-section reveal reveal-d3">
        <div class="ps-title">ℹ️ Account Info</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div style="background:rgba(255,255,255,0.04);border-radius:var(--r-md);padding:14px;border:1px solid var(--glass-border);">
            <div style="font-size:12px;color:var(--tx-4);font-weight:700;margin-bottom:4px;">ACCOUNT TYPE</div>
            <div style="font-size:16px;font-weight:700;color:var(--tx-1);"><?= $user['is_admin']?'👑 Administrator':'🌿 Regular User' ?></div>
          </div>
          <div style="background:rgba(255,255,255,0.04);border-radius:var(--r-md);padding:14px;border:1px solid var(--glass-border);">
            <div style="font-size:12px;color:var(--tx-4);font-weight:700;margin-bottom:4px;">USER ID</div>
            <div style="font-size:16px;font-weight:700;color:var(--tx-1);">#<?= str_pad($uid,6,'0',STR_PAD_LEFT) ?></div>
          </div>
          <div style="background:rgba(255,255,255,0.04);border-radius:var(--r-md);padding:14px;border:1px solid var(--glass-border);">
            <div style="font-size:12px;color:var(--tx-4);font-weight:700;margin-bottom:4px;">JOINED</div>
            <div style="font-size:16px;font-weight:700;color:var(--tx-1);"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
          </div>
          <div style="background:rgba(255,255,255,0.04);border-radius:var(--r-md);padding:14px;border:1px solid var(--glass-border);">
            <div style="font-size:12px;color:var(--tx-4);font-weight:700;margin-bottom:4px;">STATUS</div>
            <div style="font-size:16px;font-weight:700;color:<?= $user['is_banned']?'#f43f5e':'var(--g400)' ?>;">
              <?= $user['is_banned'] ? '🚫 Banned' : '✅ Active' ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /profile-main -->
  </div><!-- /profile-layout -->

</div>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?= date('Y') ?> · Fresh, Local, Delicious</div>
    <div class="footer-links">
      <a href="shop.php">Shop</a>
      <a href="orders.php">Orders</a>
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
