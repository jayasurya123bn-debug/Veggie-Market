// ============================================================
// APP LOGIC (Auth & UI) — Veggie Market
// ============================================================

const App = {
    user: null,

    init() {
        // Check session
        const session = localStorage.getItem('vm_session');
        if (session) {
            this.user = JSON.parse(session);
        }

        // Redirect logic if unauthorized
        const path = window.location.pathname;
        const page = path.split('/').pop() || 'index.html';
        
        const publicPages = ['index.html', ''];
        if (!this.user && !publicPages.includes(page)) {
            window.location.href = 'index.html';
            return;
        }
        if (this.user && publicPages.includes(page)) {
            window.location.href = 'dashboard.html';
            return;
        }

        // Render Navigation if user is logged in
        if (this.user) {
            this.renderNavbar(page);
        }
    },

    login(email, password) {
        const res = DB.loginUser(email, password);
        if (res.success) {
            localStorage.setItem('vm_session', JSON.stringify(res.user));
            window.location.href = 'dashboard.html';
        } else {
            this.showToast(res.error, 'error');
        }
    },

    register(name, email, password) {
        const res = DB.registerUser(name, email, password);
        if (res.success) {
            localStorage.setItem('vm_session', JSON.stringify(res.user));
            window.location.href = 'dashboard.html';
        } else {
            this.showToast(res.error, 'error');
        }
    },

    logout() {
        localStorage.removeItem('vm_session');
        window.location.href = 'index.html';
    },

    getCartCount() {
        if(!this.user) return 0;
        const cart = DB.get('cart').filter(c => c.user_id == this.user.id);
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    },

    renderNavbar(currentPage) {
        const cartCount = this.getCartCount();
        const isAdmin = this.user.is_admin ? `<a href="admin.html" class="drawer-link ${currentPage==='admin.html'?'active':''}" style="color:#a78bfa;">👑 Admin Panel</a>` : '';
        const adminDropdown = this.user.is_admin ? `<div class="dropdown-divider"></div><a href="admin.html" class="dropdown-item" style="color:#a78bfa;">👑 Admin Panel</a>` : '';

        const navHtml = `
<div class="nav-container reveal">
  <nav class="nav-content">
    <a href="dashboard.html" class="nav-logo">
      <span class="logo-emoji">🥬</span>
      <span class="logo-text">Veggie<span class="logo-accent">Market</span></span>
    </a>
    
    <div class="nav-links">
      <a href="dashboard.html" class="nav-link ${currentPage==='dashboard.html'?'active':''}">Dashboard</a>
      <a href="shop.html"      class="nav-link ${currentPage==='shop.html'?'active':''}">Shop</a>
      <a href="cart.html"      class="nav-link ${currentPage==='cart.html'?'active':''}">Cart <span class="nav-cart-badge">${cartCount}</span></a>
    </div>

    <div class="nav-user" onclick="document.querySelector('.nav-dropdown').classList.toggle('active'); event.stopPropagation();">
      <div class="nav-avatar">${this.user.name.charAt(0).toUpperCase()}</div>
      <span class="nav-username">${this.user.name}</span>
      <span class="nav-chevron">▾</span>
      <div class="nav-dropdown">
        <a href="dashboard.html" class="dropdown-item">⚡ Dashboard</a>
        <a href="profile.html"   class="dropdown-item">👤 My Profile</a>
        <a href="orders.html"    class="dropdown-item">📦 My Orders</a>
        <a href="wishlist.html"  class="dropdown-item">❤️ Wishlist</a>
        <a href="cart.html"      class="dropdown-item">🛍️ My Cart <span style="color:var(--green-400)">${cartCount}</span></a>
        ${adminDropdown}
        <div class="dropdown-divider"></div>
        <a href="#" onclick="App.logout()" class="dropdown-item dropdown-logout">🚪 Logout</a>
      </div>
    </div>
    
    <button class="nav-mobile-btn" onclick="document.querySelector('.nav-drawer').classList.toggle('active')">☰</button>
  </nav>

  <nav class="nav-drawer">
    <div class="drawer-header">
      <span class="drawer-title">Menu</span>
      <button class="drawer-close" onclick="document.querySelector('.nav-drawer').classList.remove('active')">×</button>
    </div>
    <a href="dashboard.html" class="drawer-link ${currentPage==='dashboard.html'?'active':''}">⚡ Dashboard</a>
    <a href="shop.html"      class="drawer-link ${currentPage==='shop.html'?'active':''}">🛒 Shop</a>
    <a href="cart.html"      class="drawer-link ${currentPage==='cart.html'?'active':''}">🛍️ Cart (${cartCount})</a>
    <a href="orders.html"    class="drawer-link ${currentPage==='orders.html'?'active':''}">📦 My Orders</a>
    <a href="wishlist.html"  class="drawer-link ${currentPage==='wishlist.html'?'active':''}">❤️ Wishlist</a>
    <a href="profile.html"   class="drawer-link ${currentPage==='profile.html'?'active':''}">👤 Profile</a>
    ${isAdmin}
    <a href="#" onclick="App.logout()" class="drawer-link drawer-logout">🚪 Logout</a>
  </nav>
</div>`;

        // Insert navbar at the top of the body
        const navWrapper = document.createElement('div');
        navWrapper.innerHTML = navHtml;
        document.body.insertBefore(navWrapper.firstElementChild, document.body.firstChild);
        
        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            const dropdown = document.querySelector('.nav-dropdown');
            if(dropdown) dropdown.classList.remove('active');
        });
    },

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '12px';
        toast.style.background = type === 'success' ? 'rgba(34, 197, 94, 0.9)' : 'rgba(239, 68, 68, 0.9)';
        toast.style.color = '#fff';
        toast.style.fontWeight = '500';
        toast.style.backdropFilter = 'blur(10px)';
        toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.5)';
        toast.style.zIndex = '9999';
        toast.style.animation = 'slideIn 0.3s forwards';
        toast.innerHTML = type === 'success' ? `✅ ${message}` : `❌ ${message}`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Add toast animations to document
const style = document.createElement('style');
style.innerHTML = `
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
`;
document.head.appendChild(style);

// Run init on load
App.init();
