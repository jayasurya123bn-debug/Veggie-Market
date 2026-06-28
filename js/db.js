// ============================================================
// DATABASE SIMULATION (Local Storage) — Veggie Market
// ============================================================

const seedProducts = [
    { id: 1, name: "Fresh Tomatoes", price: 45, unit: "kg", category: "vegetables", image: "https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=800&q=80", stock: 50, featured: 1 },
    { id: 2, name: "Organic Carrots", price: 35, unit: "kg", category: "vegetables", image: "https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=800&q=80", stock: 30, featured: 1 },
    { id: 3, name: "Green Cabbage", price: 25, unit: "piece", category: "vegetables", image: "https://images.unsplash.com/photo-1592351772659-0a6f4460f950?w=800&q=80", stock: 20, featured: 0 },
    { id: 4, name: "Red Onions", price: 60, unit: "kg", category: "vegetables", image: "https://images.unsplash.com/photo-1618512496248-a07fe83aa8cb?w=800&q=80", stock: 100, featured: 0 },
    { id: 5, name: "Sweet Apples", price: 120, unit: "kg", category: "fruits", image: "https://images.unsplash.com/photo-1560806887-1e4cd0b6fd6c?w=800&q=80", stock: 40, featured: 1 },
    { id: 6, name: "Ripe Bananas", price: 50, unit: "dozen", category: "fruits", image: "https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=800&q=80", stock: 60, featured: 0 },
    { id: 7, name: "Fresh Spinach", price: 15, unit: "bunch", category: "vegetables", image: "https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=800&q=80", stock: 15, featured: 1 }
];

const seedUsers = [
    { id: 1, name: "Admin", email: "jayasurya123bn@gmail.com", password: "admin", is_admin: 1, is_banned: 0, created_at: new Date().toISOString() }
];

const DB = {
    init() {
        if (!localStorage.getItem('vm_users')) localStorage.setItem('vm_users', JSON.stringify(seedUsers));
        if (!localStorage.getItem('vm_products')) localStorage.setItem('vm_products', JSON.stringify(seedProducts));
        if (!localStorage.getItem('vm_cart')) localStorage.setItem('vm_cart', JSON.stringify([]));
        if (!localStorage.getItem('vm_orders')) localStorage.setItem('vm_orders', JSON.stringify([]));
        if (!localStorage.getItem('vm_wishlist')) localStorage.setItem('vm_wishlist', JSON.stringify([]));
    },

    // ── DATA ACCESS ──
    get(table) { return JSON.parse(localStorage.getItem(`vm_${table}`)) || []; },
    set(table, data) { localStorage.setItem(`vm_${table}`, JSON.stringify(data)); },
    
    // ── USERS ──
    registerUser(name, email, password) {
        const users = this.get('users');
        if (users.find(u => u.email === email)) return { error: "Email already exists!" };
        const newUser = { id: Date.now(), name, email, password, is_admin: 0, is_banned: 0, created_at: new Date().toISOString() };
        users.push(newUser);
        this.set('users', users);
        return { success: true, user: newUser };
    },
    loginUser(email, password) {
        const users = this.get('users');
        const user = users.find(u => u.email === email && u.password === password);
        if (!user) return { error: "Invalid email or password" };
        if (user.is_banned) return { error: "Your account has been suspended." };
        return { success: true, user };
    },
    updateUser(id, data) {
        let users = this.get('users');
        let index = users.findIndex(u => u.id == id);
        if(index > -1) { users[index] = { ...users[index], ...data }; this.set('users', users); return true; }
        return false;
    },

    // ── PRODUCTS ──
    addProduct(data) {
        const products = this.get('products');
        products.push({ id: Date.now(), ...data });
        this.set('products', products);
    },
    deleteProduct(id) {
        this.set('products', this.get('products').filter(p => p.id != id));
    },

    // ── CART ──
    addToCart(userId, productId) {
        let cart = this.get('cart');
        let item = cart.find(c => c.user_id == userId && c.product_id == productId);
        if (item) item.quantity += 1;
        else cart.push({ id: Date.now(), user_id: userId, product_id: productId, quantity: 1 });
        this.set('cart', cart);
    },
    updateCart(userId, productId, qty) {
        let cart = this.get('cart');
        let index = cart.findIndex(c => c.user_id == userId && c.product_id == productId);
        if (index > -1) {
            if (qty > 0) cart[index].quantity = qty;
            else cart.splice(index, 1);
            this.set('cart', cart);
        }
    },
    clearCart(userId) {
        this.set('cart', this.get('cart').filter(c => c.user_id != userId));
    },

    // ── WISHLIST ──
    toggleWishlist(userId, productId) {
        let wish = this.get('wishlist');
        let index = wish.findIndex(w => w.user_id == userId && w.product_id == productId);
        if (index > -1) wish.splice(index, 1);
        else wish.push({ user_id: userId, product_id: productId });
        this.set('wishlist', wish);
    },
    inWishlist(userId, productId) {
        return this.get('wishlist').some(w => w.user_id == userId && w.product_id == productId);
    },

    // ── ORDERS ──
    createOrder(userId) {
        const cart = this.get('cart').filter(c => c.user_id == userId);
        if (!cart.length) return false;
        
        const products = this.get('products');
        let subtotal = 0;
        let items = [];
        
        cart.forEach(c => {
            const p = products.find(p => p.id == c.product_id);
            if (p) {
                subtotal += p.price * c.quantity;
                items.push({ product_id: p.id, name: p.name, price: p.price, unit: p.unit, image: p.image, quantity: c.quantity });
                // Reduce stock
                p.stock = Math.max(0, p.stock - c.quantity);
            }
        });
        
        this.set('products', products); // save stock changes
        
        const delivery = subtotal > 500 ? 0 : 40;
        const newOrder = {
            id: Date.now(),
            user_id: userId,
            total: subtotal + delivery,
            delivery: delivery,
            status: 'confirmed',
            items: items,
            created_at: new Date().toISOString()
        };
        
        let orders = this.get('orders');
        orders.push(newOrder);
        this.set('orders', orders);
        
        this.clearCart(userId);
        return newOrder.id;
    }
};

// Initialize DB on load
DB.init();
