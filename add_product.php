<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];
$error = $success = '';
$currentStep = 1;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $unit        = trim($_POST['unit']        ?? 'kg');
    $stock       = (int)($_POST['stock']      ?? 0);
    $category    = trim($_POST['category']    ?? '');

    if (empty($name))     { $error='Product name is required.'; }
    elseif ($price <= 0)  { $error='Please enter a valid price.'; }
    else {
        $img = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) { $error='Only JPG/PNG/GIF/WebP allowed.'; }
            elseif ($_FILES['image']['size']>5*1024*1024)           { $error='Image must be under 5MB.'; }
            elseif ($_FILES['image']['error']!==UPLOAD_ERR_OK)      { $error='Upload failed.'; }
            else {
                $img = uniqid('prod_',true).'.'.$ext;
                if (!is_dir('uploads')) mkdir('uploads',0777,true);
                if (!move_uploaded_file($_FILES['image']['tmp_name'],'uploads/'.$img)) { $error='Could not save image.'; $img=null; }
            }
        }
        if (empty($error)) {
            $stmt=$conn->prepare("INSERT INTO products (name,description,price,unit,stock,category,image,added_by) VALUES(?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssdsissi",$name,$description,$price,$unit,$stock,$category,$img,$uid);
            $success = $stmt->execute() ? "✅ \"$name\" is now live on the market!" : 'DB error: '.$conn->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Product – Veggie Market</title>
  <meta name="description" content="List a new product on Veggie Market."/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<div class="mesh-bg">
  <div class="orb" style="width:450px;height:450px;background:rgba(132,204,22,0.08);top:-80px;left:-80px;--d:22s;--tx:60px;--ty:40px;"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="container">

  <div class="page-hero reveal">
    <div>
      <div class="page-eyebrow">Marketplace</div>
      <h1 class="page-title">Add New Product ➕</h1>
      <p class="page-sub">List your fresh produce on the Veggie Market</p>
    </div>
    <a href="shop.php" class="btn btn-ghost" style="padding:11px 20px;">← Back to Shop</a>
  </div>

  <!-- Wizard Steps -->
  <div class="wizard-steps reveal reveal-d1" style="padding-bottom:48px;">
    <div class="wstep done"   id="ws1"><div class="ws-circle">✓</div><div class="ws-label">Details</div></div>
    <div class="wstep active" id="ws2"><div class="ws-circle">2</div><div class="ws-label">Pricing</div></div>
    <div class="wstep"        id="ws3"><div class="ws-circle">3</div><div class="ws-label">Image</div></div>
  </div>

  <?php if ($error):   ?><div class="alert alert-error reveal">⚠️ <?=htmlspecialchars($error)?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success reveal">
      <?=htmlspecialchars($success)?>
      <a href="shop.php" style="color:inherit;font-weight:700;display:inline-block;margin-left:12px;">View in Shop →</a>
    </div>
  <?php endif; ?>

  <form method="POST" action="add_product.php" enctype="multipart/form-data" id="addForm">

    <div class="wiz-layout">

      <!-- Left: Panels -->
      <div>

        <!-- Panel 1: Details -->
        <div class="wiz-panel active" id="panel1">
          <div class="form-card reveal">
            <div class="form-card-title">📝 Step 1 — Product Details</div>

            <div class="form-group">
              <label class="form-label" for="name">Product Name *</label>
              <div class="input-wrap">
                <span class="input-icon">🏷️</span>
                <input type="text" id="name" name="name" class="form-control"
                  placeholder="e.g. Fresh Broccoli, Red Tomatoes..."
                  value="<?=htmlspecialchars($_POST['name']??'')?>"
                  oninput="syncPreview()" required/>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <select id="category" name="category" class="form-control" onchange="syncPreview()">
                  <option value="">Select...</option>
                  <?php foreach(['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿','Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'] as $c=>$e): ?>
                  <option value="<?=$c?>" <?=($_POST['category']??'')===$c?'selected':''?>><?=$e?> <?=$c?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="unit">Unit</label>
                <select id="unit" name="unit" class="form-control" onchange="syncPreview()">
                  <?php foreach(['kg'=>'Kilogram','g'=>'Gram','piece'=>'Piece','bunch'=>'Bunch','dozen'=>'Dozen','liter'=>'Liter','packet'=>'Packet'] as $v=>$l): ?>
                  <option value="<?=$v?>" <?=($_POST['unit']??'kg')===$v?'selected':''?>><?=$v?> — <?=$l?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" class="form-control"
                placeholder="Describe freshness, origin, uses..."
                oninput="syncPreview()"><?=htmlspecialchars($_POST['description']??'')?></textarea>
            </div>

            <button type="button" class="btn btn-primary" style="margin-top:8px;" onclick="goStep(2)">
              Next: Pricing →
            </button>
          </div>
        </div>

        <!-- Panel 2: Pricing -->
        <div class="wiz-panel" id="panel2">
          <div class="form-card reveal">
            <div class="form-card-title">💰 Step 2 — Pricing & Stock</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="price">Price (₹) *</label>
                <div class="input-wrap">
                  <span class="input-icon">💰</span>
                  <input type="number" id="price" name="price" class="form-control"
                    placeholder="0.00" step="0.01" min="0.01"
                    value="<?=htmlspecialchars($_POST['price']??'')?>"
                    oninput="syncPreview()" required/>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="stock">Stock Qty</label>
                <div class="input-wrap">
                  <span class="input-icon">📦</span>
                  <input type="number" id="stock" name="stock" class="form-control"
                    placeholder="0" min="0"
                    value="<?=htmlspecialchars($_POST['stock']??'0')?>"/>
                </div>
              </div>
            </div>

            <!-- Price calculator hint -->
            <div id="priceHint" style="background:rgba(34,197,94,0.06);border:1px solid var(--border-green);border-radius:var(--r-md);padding:14px 18px;font-size:13px;color:var(--tx-3);margin-bottom:20px;display:none;">
              💡 At <strong id="hPrice">₹0</strong> per <span id="hUnit">kg</span>, that's a great market rate!
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
              <button type="button" class="btn btn-ghost" style="padding:11px 22px;" onclick="goStep(1)">← Back</button>
              <button type="button" class="btn btn-primary" style="flex:1;" onclick="goStep(3)">Next: Upload Image →</button>
            </div>
          </div>
        </div>

        <!-- Panel 3: Image -->
        <div class="wiz-panel" id="panel3">
          <div class="form-card reveal">
            <div class="form-card-title">📸 Step 3 — Product Image</div>

            <div class="img-drop" id="imgDrop">
              <input type="file" name="image" id="imgInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImg(event)"/>
              <div id="dropPrompt">
                <div class="img-drop-icon">📸</div>
                <div class="img-drop-text">
                  <strong>Click to upload</strong> or drag & drop<br>
                  <span style="font-size:12px;">JPG · PNG · GIF · WebP · Max 5MB</span>
                </div>
              </div>
              <img id="imgPreview" class="img-preview" alt="Preview"/>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
              <button type="button" class="btn btn-ghost" style="padding:11px 22px;" onclick="goStep(2)">← Back</button>
              <button type="submit" class="btn btn-primary" style="flex:1;" id="submitBtn">🌿 List on Market</button>
            </div>
          </div>
        </div>

      </div>

      <!-- Right: Live Preview -->
      <div>
        <div class="preview-sticky reveal reveal-d2">
          <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--tx-4);">📋 Live Preview</div>
          <div class="prev-img" id="prevImg">
            <span id="prevEmoji">🥦</span>
            <img id="prevImgEl" src="" alt="" style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"/>
          </div>
          <div class="prev-body">
            <div class="prev-cat" id="prevCat">🌿 Category</div>
            <div class="prev-name" id="prevName">Product Name</div>
            <div class="prev-desc" id="prevDesc">Description will appear here...</div>
            <div class="prev-price">
              ₹<span id="prevPrice">0.00</span>
              <span style="font-size:13px;color:var(--tx-4);">/</span>
              <span id="prevUnit" style="font-size:14px;color:var(--tx-4);">kg</span>
            </div>
            <div class="prev-by">Listed by <strong class="text-green"><?=htmlspecialchars($_SESSION['username']??'')?></strong></div>
          </div>
        </div>
      </div>

    </div>
  </form>

</div>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">🥦 Veggie Market &copy; <?=date('Y')?></div>
    <div class="footer-links"><a href="shop.php">Shop</a><a href="dashboard.php">Dashboard</a></div>
  </div>
</footer>

<script>
const catEmoji = {Vegetables:'🥦',Fruits:'🍎','Leafy Greens':'🥬',Herbs:'🌿',Roots:'🥕',Gourds:'🎃',Citrus:'🍋',Berries:'🍓',Grains:'🌾',Other:'🛒'};
let currentStep = 1;

function goStep(n) {
  document.getElementById('panel'+currentStep).classList.remove('active');
  ['ws1','ws2','ws3'].forEach((id,i) => {
    const el = document.getElementById(id);
    el.classList.remove('active','done');
    if (i+1 < n)  el.classList.add('done');
    if (i+1 === n) el.classList.add('active');
  });
  document.getElementById('panel'+n).classList.add('active');
  currentStep = n;
  window.scrollTo({top:0,behavior:'smooth'});
}

function syncPreview() {
  const name  = document.getElementById('name').value        || 'Product Name';
  const price = document.getElementById('price')?.value      || '0';
  const unit  = document.getElementById('unit')?.value       || 'kg';
  const desc  = document.getElementById('description').value || 'Description will appear here...';
  const cat   = document.getElementById('category').value    || '';
  const e     = catEmoji[cat] || '🥦';

  document.getElementById('prevName').textContent  = name;
  document.getElementById('prevPrice').textContent = parseFloat(price||0).toFixed(2);
  document.getElementById('prevUnit').textContent  = unit;
  document.getElementById('prevDesc').textContent  = desc;
  document.getElementById('prevCat').textContent   = (e+' '+(cat||'Category'));
  document.getElementById('prevEmoji').textContent = e;

  // Price hint
  const hint = document.getElementById('priceHint');
  if (price>0) { hint.style.display='block'; document.getElementById('hPrice').textContent='₹'+parseFloat(price).toFixed(2); document.getElementById('hUnit').textContent=unit; }
  else hint.style.display='none';
}

function previewImg(event) {
  const file = event.target.files[0]; if(!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('imgPreview');
    const prevEl = document.getElementById('prevImgEl');
    const prompt = document.getElementById('dropPrompt');
    prev.src = e.target.result; prev.style.display = 'block';
    prompt.style.display = 'none';
    prevEl.src = e.target.result; prevEl.style.display = 'block';
    document.getElementById('prevEmoji').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

// Drag & Drop
const drop = document.getElementById('imgDrop');
drop.addEventListener('dragover',  e => { e.preventDefault(); drop.classList.add('drag'); });
drop.addEventListener('dragleave', () => drop.classList.remove('drag'));
drop.addEventListener('drop', e => {
  e.preventDefault(); drop.classList.remove('drag');
  const file = e.dataTransfer.files[0]; if(!file) return;
  const dt = new DataTransfer(); dt.items.add(file);
  document.getElementById('imgInput').files = dt.files;
  previewImg({target:{files:dt.files}});
});

// Submit confetti
document.getElementById('addForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '⏳ Listing product...'; btn.disabled = true;
  fireConfetti();
});

function fireConfetti() {
  const colors = ['#22c55e','#84cc16','#10b981','#f97316','#eab308','#f43f5e'];
  for (let i=0; i<60; i++) {
    const p = document.createElement('div');
    p.className = 'confetti-piece';
    p.style.cssText = `
      left:${Math.random()*100}vw;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      --d:${0.8+Math.random()*1.5}s;
      animation-delay:${Math.random()*0.5}s;
      border-radius:${Math.random()>0.5?'50%':'2px'};
      width:${6+Math.random()*10}px;
      height:${6+Math.random()*10}px;
    `;
    document.body.appendChild(p);
    setTimeout(() => p.remove(), 2500);
  }
}

// Reveal observer
const obs = new IntersectionObserver(e => e.forEach(en => { if(en.isIntersecting) en.target.classList.add('visible'); }), {threshold:0.1});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

const stb = document.getElementById('scrollTop');
window.addEventListener('scroll', () => stb.classList.toggle('visible', window.scrollY>400));

syncPreview();
<?php if ($success): ?> fireConfetti(); <?php endif; ?>
</script>
</body>
</html>
