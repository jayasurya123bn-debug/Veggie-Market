# 🥦 Veggie Market

> A premium PHP-based online vegetable and fruit shopping market with a stunning dark glassmorphism design.

---

## 📁 Project Structure

```
veggie-market/
├── index.php           ← Login page
├── register.php        ← Registration page
├── dashboard.php       ← User dashboard (stats + quick actions)
├── shop.php            ← Shopping market (browse & add to cart)
├── cart.php            ← Cart with real order saving
├── orders.php          ← Order history with tracking timeline
├── wishlist.php        ← Saved / favourited products
├── profile.php         ← Edit profile, change password, account stats
├── add_product.php     ← Add new product with image upload
├── logout.php          ← Secure session logout
├── db.php              ← Database connection & auto-setup
├── admin/
│   ├── index.php       ← Admin overview dashboard
│   ├── users.php       ← Manage users (ban/unban/promote)
│   ├── products.php    ← Manage products (feature/delete)
│   └── orders.php      ← Manage orders (update status)
├── uploads/            ← Uploaded product images
├── css/
│   └── style.css       ← Premium dark glassmorphism styles
└── README.md           ← This file
```

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 Auth | Secure register & login with `password_hash` |
| 🛒 Shop | Browse products by category with search & sort |
| ➕ Add Products | Upload image, set price/stock/category |
| 📦 Cart | Add items to cart with live badge counter |
| 📊 Dashboard | Stats, quick actions, recent products table |
| 🌙 Dark Mode | Full dark glassmorphism premium UI |
| 📱 Responsive | Mobile-friendly layout |

---

## 🚀 Quick Start

### Requirements
- PHP 7.4 or higher (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB
- A local server (XAMPP, WAMP, Laragon, or MAMP)

### 1. Setup Database
Copy the `veggie-market/` folder into your server's web root:

```
# XAMPP
C:\xampp\htdocs\veggie-market\

# WAMP
C:\wamp64\www\veggie-market\

# Laragon
C:\laragon\www\veggie-market\
```

### 2. Configure Database
Open `db.php` and update your credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // ← your MySQL username
define('DB_PASS', '');          // ← your MySQL password
define('DB_NAME', 'veggie_market');
```

> **Note:** The database and all tables are created **automatically** on first run. No SQL import needed!

### 3. Run the App
Open your browser and navigate to:
```
http://localhost/veggie-market/
```

---

## 🗄️ Database Schema

The app auto-creates these tables:

### `users`
| Column | Type | Description |
|---|---|---|
| id | INT (PK) | Auto-increment |
| username | VARCHAR(50) | Unique username |
| email | VARCHAR(100) | Unique email |
| password | VARCHAR(255) | Bcrypt hashed |
| full_name | VARCHAR(100) | Display name |
| created_at | TIMESTAMP | Registration time |

### `products`
| Column | Type | Description |
|---|---|---|
| id | INT (PK) | Auto-increment |
| name | VARCHAR(100) | Product name |
| description | TEXT | Details |
| price | DECIMAL(10,2) | Price in ₹ |
| unit | VARCHAR(20) | kg/g/piece/etc |
| stock | INT | Available quantity |
| category | VARCHAR(50) | Vegetables/Fruits/etc |
| image | VARCHAR(255) | Filename in uploads/ |
| added_by | INT (FK) | User who added it |
| created_at | TIMESTAMP | Creation time |

### `cart`
| Column | Type | Description |
|---|---|---|
| id | INT (PK) | Auto-increment |
| user_id | INT (FK) | Buyer |
| product_id | INT (FK) | Product added |
| quantity | INT | How many |
| added_at | TIMESTAMP | When added |

---

## 🎨 Design Features

- **Dark Glassmorphism** – `backdrop-filter: blur()` cards with translucent backgrounds
- **Animated Gradient Blobs** – Radial gradient orbs create a living background
- **Micro-animations** – Cards lift on hover, buttons pulse, toast notifications slide in
- **Google Fonts** – `Outfit` for UI text, `Playfair Display` for headings
- **Custom Scrollbar** – Green-tinted scrollbar for consistent theming
- **Cart Toast** – Smooth notification when adding to cart

---

## 📸 Image Uploads

- Images are stored in the `uploads/` folder
- Accepted formats: **JPG, PNG, GIF, WebP**
- Maximum file size: **5MB**
- Files are renamed with `uniqid()` to prevent conflicts
- If no image is uploaded, a category emoji is shown instead

---

## 🔒 Security Notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt)
- All user inputs are sanitized with `htmlspecialchars()` and `trim()`
- Prepared statements (MySQLi) prevent SQL injection
- Session-based authentication on all protected pages
- File upload validation by extension and MIME size

---

## 🛠️ Extending the App

Some ideas to extend functionality:
- [ ] Checkout & order processing
- [ ] Admin panel to manage all products
- [ ] Product reviews & ratings
- [ ] Wishlist / favorites
- [ ] Email notifications (PHPMailer)
- [ ] REST API endpoints

---

## 📄 License

Free to use for personal and educational projects.

---

> Built with ❤️ using PHP, MySQL, HTML, CSS & Vanilla JavaScript
