<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit(); }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    if (empty($full_name)||empty($username)||empty($email)||empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $chk->bind_param("ss", $username, $email);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (full_name,username,email,password) VALUES(?,?,?,?)");
            $ins->bind_param("ssss", $full_name, $username, $email, $hash);
            if ($ins->execute()) {
                $success = "🎉 Account created! Welcome to the market, $full_name!";
            } else { $error = 'Something went wrong. Please try again.'; }
            $ins->close();
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register – Veggie Market 🌱</title>
  <meta name="description" content="Create your free Veggie Market account and start shopping for fresh produce."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:500px;height:500px;background:rgba(132,204,22,0.12);top:-100px;right:-100px;--d:20s;--tx:-40px;--ty:60px;"></div>
  <div class="orb" style="width:350px;height:350px;background:rgba(16,185,129,0.1);bottom:0;left:-80px;--d:16s;--tx:50px;--ty:-40px;"></div>
</div>

<div class="auth-page">

  <!-- Hero -->
  <div class="auth-hero">
    <div class="hero-veggies">
      <span class="hero-veggie" style="top:10%;left:15%;--fs:56px;--d:10s;">🌱</span>
      <span class="hero-veggie" style="top:30%;right:10%;--fs:50px;--d:8s;--delay:1s;">🍎</span>
      <span class="hero-veggie" style="top:60%;left:8%;--fs:44px;--d:12s;--delay:2s;">🌽</span>
      <span class="hero-veggie" style="top:75%;right:15%;--fs:40px;--d:9s;--delay:0.5s;">🫐</span>
      <span class="hero-veggie" style="top:45%;left:50%;--fs:48px;--d:11s;--delay:3s;">🍇</span>
      <span class="hero-veggie" style="bottom:20%;left:35%;--fs:36px;--d:7s;--delay:1.5s;">🥝</span>
    </div>

    <div class="hero-badge">🌱 Join 100% Free · No Hidden Fees</div>
    <h1 class="hero-title">Start Your<br><span class="highlight">Fresh Journey</span><br>Today</h1>
    <p class="hero-desc">Register in seconds, browse hundreds of fresh products, and connect with local farmers and veggie lovers.</p>

    <div style="display:flex;flex-direction:column;gap:14px;margin-top:8px;">
      <div style="display:flex;align-items:center;gap:12px;font-size:15px;color:var(--tx-3);">
        <span style="font-size:22px;">✅</span> Browse & buy fresh produce anytime
      </div>
      <div style="display:flex;align-items:center;gap:12px;font-size:15px;color:var(--tx-3);">
        <span style="font-size:22px;">✅</span> List your own products with images
      </div>
      <div style="display:flex;align-items:center;gap:12px;font-size:15px;color:var(--tx-3);">
        <span style="font-size:22px;">✅</span> Track your cart & orders easily
      </div>
    </div>
  </div>

  <!-- Form Panel -->
  <div class="auth-panel">
    <div class="auth-card" style="max-width:460px;">

      <div class="auth-logo-wrap">🌱</div>
      <h1 class="auth-title">Create account</h1>
      <p class="auth-sub">Join the freshest community online</p>

      <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($success) ?>
          <a href="index.php" style="color:inherit;font-weight:700;display:block;margin-top:8px;">→ Sign In Now</a>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" action="register.php" id="regForm" novalidate>

        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <div class="input-wrap">
            <span class="input-icon">✨</span>
            <input type="text" id="full_name" name="full_name" class="form-control"
              placeholder="Your full name"
              value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required/>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="reg_user">Username</label>
            <div class="input-wrap">
              <span class="input-icon">👤</span>
              <input type="text" id="reg_user" name="username" class="form-control"
                placeholder="username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <div class="input-wrap">
              <span class="input-icon">📧</span>
              <input type="email" id="email" name="email" class="form-control"
                placeholder="you@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg_pwd">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="reg_pwd" name="password" class="form-control"
              placeholder="Min. 6 characters" required oninput="checkStrength(this.value)"/>
          </div>
          <div class="pwd-strength"><div class="pwd-bar" id="pwdBar"></div></div>
          <div class="pwd-label" id="pwdLabel">Enter a password</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm">Confirm Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input type="password" id="confirm" name="confirm" class="form-control"
              placeholder="Repeat password" required oninput="checkMatch()"/>
          </div>
          <div id="matchMsg"></div>
        </div>

        <button type="submit" class="btn btn-primary" id="regBtn">🌿 Create My Account</button>

      </form>
      <?php endif; ?>

      <div class="auth-foot">
        Already a member? <a href="index.php">Sign in →</a>
      </div>
    </div>
  </div>

</div>

<script>
function checkStrength(v) {
  const bar = document.getElementById('pwdBar');
  const lbl = document.getElementById('pwdLabel');
  const levels = [
    { re: /^.{0,5}$/,     pct: 15,  col: '#f43f5e', txt: '😬 Too short' },
    { re: /^[a-z]{6,}$/i, pct: 35,  col: '#f97316', txt: '😐 Weak' },
    { re: /^(?=.*[A-Z])(?=.*[a-z]).{6,}$/, pct: 60, col: '#eab308', txt: '🙂 Fair' },
    { re: /^(?=.*\d)(?=.*[A-Za-z]).{6,}$/, pct: 80, col: '#22c55e', txt: '😎 Good' },
    { re: /^(?=.*[!@#$%^&*])(?=.*\d)(?=.*[A-Za-z]).{8,}$/, pct: 100, col: '#10b981', txt: '💪 Strong' },
  ];
  let lvl = levels[0];
  for (const l of levels) { if (l.re.test(v)) lvl = l; }
  bar.style.width = lvl.pct + '%';
  bar.style.background = lvl.col;
  lbl.textContent = v ? lvl.txt : 'Enter a password';
  lbl.style.color = lvl.col;
}

function checkMatch() {
  const p = document.getElementById('reg_pwd').value;
  const c = document.getElementById('confirm').value;
  const el = document.getElementById('matchMsg');
  if (!c) { el.innerHTML = ''; return; }
  el.innerHTML = p === c
    ? '<div class="match-ok">✓ Passwords match</div>'
    : '<div class="match-err">✗ Passwords do not match</div>';
}

document.getElementById('regForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('regBtn');
  btn.innerHTML = '⏳ Creating account...';
  btn.disabled = true;
});
</script>
</body>
</html>
