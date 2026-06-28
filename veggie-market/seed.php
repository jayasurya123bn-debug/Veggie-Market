<?php
/**
 * seed.php — One-click demo data seeder for Veggie Market
 * Visit this page once to populate the database with 16 sample products.
 * Can be safely run multiple times (checks for duplicates).
 */
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$uid = $_SESSION['user_id'];

$products = [
    ['Fresh Broccoli',          'Crisp, vibrant green broccoli packed with vitamins C and K. Perfect for stir-fries, soups, and roasting.',  29.00, 'kg',    50,  'Vegetables'],
    ['Ripe Tomatoes',           'Juicy, sun-ripened red tomatoes. Great for salads, pasta sauces, and sandwiches.',                          35.00, 'kg',    80,  'Vegetables'],
    ['Sweet Carrots',           'Organically grown sweet carrots with a delightful crunch. Perfect raw or cooked.',                          25.00, 'kg',    60,  'Roots'],
    ['Baby Spinach',            'Tender baby spinach leaves, washed and ready to eat. Rich in iron and antioxidants.',                       45.00, 'bunch', 40,  'Leafy Greens'],
    ['Red Bell Pepper',         'Vibrant red bell peppers, sweet and crispy. Excellent for salads, roasting, and stir-frying.',              55.00, 'kg',    30,  'Vegetables'],
    ['Fresh Ginger',            'Aromatic and spicy fresh ginger root. Essential for curries, teas, and Asian cooking.',                     80.00, 'kg',    25,  'Roots'],
    ['Green Coriander',         'Fresh and fragrant coriander leaves (dhania). Essential herb for Indian, Thai, and Mexican cuisine.',       15.00, 'bunch', 70,  'Herbs'],
    ['Alphonso Mangoes',        'The king of fruits! Creamy, golden Alphonso mangoes with extraordinary sweetness.',                        150.00,'kg',    20,  'Fruits'],
    ['Fresh Strawberries',      'Plump and juicy strawberries picked at peak ripeness. Sweet with just a hint of tartness.',                 120.00,'kg',    15,  'Berries'],
    ['Organic Lemons',          'Bright yellow lemons bursting with fresh citrus flavor. Perfect for lemonade, dressings, and baking.',      60.00, 'kg',    45,  'Citrus'],
    ['Curry Leaves',            'Fresh curry leaves with a distinctive aroma. Staple of South Indian cooking.',                             10.00, 'bunch', 90,  'Herbs'],
    ['Sweet Corn',              'Golden sweet corn on the cob, tender and bursting with natural sweetness.',                                40.00, 'piece', 35,  'Vegetables'],
    ['Bottle Gourd (Lauki)',    'Mild and nutritious bottle gourd. Great for curries, soups, and sabzi.',                                   20.00, 'piece', 55,  'Gourds'],
    ['Pomegranate',             'Ruby-red pomegranate seeds packed with antioxidants. Excellent in salads and juices.',                     90.00, 'piece', 28,  'Fruits'],
    ['Drumstick (Moringa)',     'Fresh drumsticks (moringa pods) – a superfood powerhouse. Ideal for sambar and curries.',                  30.00, 'piece', 40,  'Vegetables'],
    ['Mixed Berries Pack',      'A colorful mix of blueberries, raspberries, and blackberries. Perfect for smoothies and desserts.',        180.00,'packet',12,  'Berries'],
];

$added = 0; $skipped = 0;
foreach ($products as [$name,$desc,$price,$unit,$stock,$cat]) {
    $chk = $conn->prepare("SELECT id FROM products WHERE name=? AND added_by=?");
    $chk->bind_param("si",$name,$uid); $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { $skipped++; $chk->close(); continue; }
    $chk->close();

    $ins = $conn->prepare("INSERT INTO products (name,description,price,unit,stock,category,added_by) VALUES(?,?,?,?,?,?,?)");
    $ins->bind_param("ssdsssi",$name,$desc,$price,$unit,$stock,$cat,$uid);
    if ($ins->execute()) $added++; $ins->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Seeding Data – Veggie Market</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="mesh-bg"></div>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;z-index:1;">
  <div style="text-align:center;max-width:480px;padding:40px 24px;">
    <div style="font-size:80px;margin-bottom:20px;animation:emptyBounce 1.5s ease infinite;">🌱</div>
    <h1 style="font-size:32px;font-weight:800;color:var(--tx-1);margin-bottom:12px;">Demo Data Seeded!</h1>
    <p style="color:var(--tx-4);margin-bottom:24px;">
      Added <strong style="color:var(--g400);"><?=$added?> new products</strong>
      <?php if($skipped): echo "· <span style='color:var(--tx-4);'>$skipped already existed</span>"; endif; ?>
      to the market.
    </p>
    <div style="background:rgba(34,197,94,0.08);border:1px solid var(--border-green);border-radius:var(--r-lg);padding:20px;margin-bottom:28px;text-align:left;">
      <?php foreach($products as [$name,,,$,,$,,$cat]): ?>
        <?php $e=['Vegetables'=>'🥦','Fruits'=>'🍎','Leafy Greens'=>'🥬','Herbs'=>'🌿','Roots'=>'🥕','Gourds'=>'🎃','Citrus'=>'🍋','Berries'=>'🍓','Grains'=>'🌾','Other'=>'🛒'][$cat]??'🌱'; ?>
        <div style="font-size:14px;color:var(--tx-3);padding:4px 0;"><?=$e?> <?=htmlspecialchars($name)?></div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="shop.php"      class="btn btn-primary" style="width:auto;padding:13px 28px;">🛒 Browse Shop</a>
      <a href="dashboard.php" class="btn btn-ghost"   style="padding:13px 22px;">Dashboard</a>
    </div>
  </div>
</div>

<script>
// Confetti celebration
const colors=['#22c55e','#84cc16','#f97316','#10b981','#eab308'];
for(let i=0;i<70;i++){
  const p=document.createElement('div');
  p.className='confetti-piece';
  p.style.cssText=`left:${Math.random()*100}vw;background:${colors[~~(Math.random()*colors.length)]};--d:${1+Math.random()*1.5}s;animation-delay:${Math.random()*0.8}s;border-radius:${Math.random()>.5?'50%':'2px'};width:${7+Math.random()*8}px;height:${7+Math.random()*8}px;`;
  document.body.appendChild(p);
  setTimeout(()=>p.remove(),3000);
}
</script>
</body>
</html>
