<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, full_name, password, is_admin, is_banned FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['is_banned']) {
                $error = '🚫 Your account has been suspended. Contact admin.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin']  = $user['is_admin'];
                // Redirect admin to admin panel
                if ($user['is_admin']) {
                    header('Location: admin/index.php'); exit();
                }
                header('Location: dashboard.php'); exit();
            } else { $error = 'Incorrect password. Please try again.'; }
        } else { $error = 'No account found with that username or email.'; }
        $stmt->close();
    }
}

// Live stats for hero
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'] ?? 0;
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c']    ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login – Veggie Market 🥦</title>
  <meta name="description" content="Login to Veggie Market – your premium fresh produce online marketplace."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<!-- Mesh background -->
<div class="mesh-bg">
  <div class="orb" style="width:600px;height:600px;background:rgba(34,197,94,0.15);top:-200px;left:-150px;--d:22s;--tx:60px;--ty:40px;"></div>
  <div class="orb" style="width:400px;height:400px;background:rgba(16,185,129,0.1);bottom:-100px;right:-100px;--d:18s;--tx:-50px;--ty:-30px;"></div>
</div>

<div class="auth-page">

  <!-- ── LEFT: Hero Panel ── -->
  <div class="auth-hero">
    <!-- Floating veggies -->
    <div class="hero-veggies">
      <span class="hero-veggie" style="top:8%;left:10%;--fs:60px;--d:9s;--delay:0s;">🥦</span>
      <span class="hero-veggie" style="top:20%;right:8%;--fs:48px;--d:11s;--delay:1s;">🍅</span>
      <span class="hero-veggie" style="top:55%;left:5%;--fs:52px;--d:8s;--delay:2s;">🥕</span>
      <span class="hero-veggie" style="top:72%;right:12%;--fs:44px;--d:13s;--delay:0.5s;">🥬</span>
      <span class="hero-veggie" style="top:40%;left:55%;--fs:56px;--d:10s;--delay:3s;">🍋</span>
      <span class="hero-veggie" style="bottom:15%;left:40%;--fs:38px;--d:7s;--delay:1.5s;">🌿</span>
      <span class="hero-veggie" style="top:85%;left:20%;--fs:46px;--d:12s;--delay:2.5s;">🍓</span>
      <span class="hero-veggie" style="top:5%;left:65%;--fs:34px;--d:6s;--delay:4s;">🌽</span>
    </div>

    <div class="hero-badge">🌿 100% Fresh · Locally Sourced</div>

    <h1 class="hero-title">
      Your Premium<br>
      <span class="highlight">Veggie Market</span><br>
      Awaits You
    </h1>

    <p class="hero-desc">
      Discover farm-fresh vegetables and fruits, add your own products,
      and enjoy the freshest produce delivered to your doorstep.
    </p>

    <div class="hero-stats">
      <div class="hstat">
        <div class="hstat-num"><?= number_format($total_products) ?>+</div>
        <div class="hstat-lbl">Fresh Products</div>
      </div>
      <div class="hstat">
        <div class="hstat-num"><?= number_format($total_users) ?>+</div>
        <div class="hstat-lbl">Happy Members</div>
      </div>
      <div class="hstat">
        <div class="hstat-num">100%</div>
        <div class="hstat-lbl">Organic Quality</div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: Login Form ── -->
  <div class="auth-panel">
    <div class="auth-card">

      <div class="auth-logo-wrap">🥦</div>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-sub">Sign in to your account to continue</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['msg']) && $_GET['msg']==='logged_out'): ?>
        <div class="alert alert-info">👋 You've been signed out. See you soon!</div>
      <?php endif; ?>

      <form method="POST" action="index.php" id="loginForm" novalidate>

        <div class="form-group">
          <label class="form-label" for="username">Username or Email</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" id="username" name="username" class="form-control"
              placeholder="Enter your username or email"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
              autocomplete="username" required/>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="password" name="password" class="form-control"
              placeholder="Your password"
              autocomplete="current-password" required/>
          </div>
        </div>

        <div style="margin-bottom:22px;"></div>

        <button type="submit" class="btn btn-primary" id="loginBtn">
          🚀 Sign In to Market
        </button>

      </form>

      <div class="auth-divider">or</div>

      <div class="auth-foot">
        Don't have an account?
        <a href="register.php">Create one free →</a>
        <div style="margin-top:10px; font-size:12px; color:var(--tx-4);">🌱 Join thousands of fresh produce lovers</div>
      </div>

    </div>
  </div>

</div>

<script>
  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '⏳ Signing in...';
    btn.disabled = true;
  });
</script>
</body>
</html>
