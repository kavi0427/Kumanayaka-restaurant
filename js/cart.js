/**
 * Shopping Cart functionality for Bella Vista Restaurant
 */

class ShoppingCart {
    constructor() {
        this.items = [];
        this.isOpen = false;
        this.init();
    }

    init() {
        this.loadCartFromStorage();
        this.createCartUI();
        this.attachEventListeners();
        this.updateCartDisplay();
    }

    // Load cart from localStorage
    loadCartFromStorage() {
        const savedCart = localStorage.getItem('bellavistaCart');
        if (savedCart) {
            this.items = JSON.parse(savedCart);
        }
    }

    // Save cart to localStorage
    saveCartToStorage() {
        localStorage.setItem('bellavistaCart', JSON.stringify(this.items));
    }

    // Create cart UI elements
    createCartUI() {
        // Create cart icon in navigation
        const navContainer = document.querySelector('.nav-menu');
        if (navContainer) {
            const cartItem = document.createElement('li');
            cartItem.className = 'nav-item cart-nav-item';
            cartItem.innerHTML = `
                <a href="#" class="nav-link cart-toggle" id="cart-toggle">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            `;
            navContainer.appendChild(cartItem);
        }

        // Create cart sidebar
        const cartSidebar = document.createElement('div');
        cartSidebar.className = 'cart-sidebar';
        cartSidebar.id = 'cart-sidebar';
        cartSidebar.innerHTML = `
            <div class="cart-header">
                <h3>Your Order</h3>
                <button class="cart-close" id="cart-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-items" id="cart-items">
                <!-- Cart items will be inserted here -->
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <strong>Total: $<span id="cart-total">0.00</span></strong>
                </div>
                <button class="btn btn-primary cart-checkout" id="cart-checkout">
                    Proceed to Checkout
                </button>
                <button class="btn btn-secondary cart-clear" id="cart-clear">
                    Clear Cart
                </button>
            </div>
        `;
        document.body.appendChild(cartSidebar);

        // Create cart overlay
        const cartOverlay = document.createElement('div');
        cartOverlay.className = 'cart-overlay';
        cartOverlay.id = 'cart-overlay';
        document.body.appendChild(cartOverlay);
    }

    // Attach event listeners
    attachEventListeners() {
        // Cart toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cart-toggle')) {
                e.preventDefault();
                this.toggleCart();
            }
        });

        // Close cart
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cart-close') || e.target.closest('#cart-overlay')) {
                this.closeCart();
            }
        });

        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn')) {
                e.preventDefault();
                const button = e.target.closest('.add-to-cart-btn');
                const menuItem = button.closest('.menu-item');
                this.addToCart(menuItem);
            }
        });

        // Cart item quantity changes
        document.addEventListener('click', (e) => {
            if (e.target.closest('.cart-item-increase')) {
                const itemId = e.target.closest('.cart-item').dataset.itemId;
                this.updateQuantity(itemId, 1);
            } else if (e.target.closest('.cart-item-decrease')) {
                const itemId = e.target.closest('.cart-item').dataset.itemId;
                this.updateQuantity(itemId, -1);
            } else if (e.target.closest('.cart-item-remove')) {
                const itemId = e.target.closest('.cart-item').dataset.itemId;
                this.removeFromCart(itemId);
            }
        });

        // Clear cart
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cart-clear')) {
                this.clearCart();
            }
        });

        // Checkout
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cart-checkout')) {
                this.checkout();
            }
        });
    }

    // Add item to cart
    addToCart(menuItemElement) {
        const name = menuItemElement.querySelector('h3').textContent;
        const priceText = menuItemElement.querySelector('.price').textContent;
        const price = parseFloat(priceText.replace('$', '').split('-')[0]);
        const description = menuItemElement.querySelector('.description').textContent;
        
        const itemId = this.generateItemId(name);
        const existingItem = this.items.find(item => item.id === itemId);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: itemId,
                name: name,
                price: price,
                description: description,
                quantity: 1
            });
        }

        this.saveCartToStorage();
        this.updateCartDisplay();
        this.showAddedNotification(name);
    }

    // Generate unique item ID
    generateItemId(name) {
        return name.toLowerCase().replace(/[^a-z0-9]/g, '-');
    }

    // Update item quantity
    updateQuantity(itemId, change) {
        const item = this.items.find(item => item.id === itemId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                this.removeFromCart(itemId);
            } else {
                this.saveCartToStorage();
                this.updateCartDisplay();
            }
        }
    }

    // Remove item from cart
    removeFromCart(itemId) {
        this.items = this.items.filter(item => item.id !== itemId);
        this.saveCartToStorage();
        this.updateCartDisplay();
    }

    // Clear entire cart
    clearCart() {
        if (confirm('Are you sure you want to clear your cart?')) {
            this.items = [];
            this.saveCartToStorage();
            this.updateCartDisplay();
        }
    }

    // Toggle cart sidebar
    toggleCart() {
        if (this.isOpen) {
            this.closeCart();
        } else {
            this.openCart();
        }
    }

    // Open cart sidebar
    openCart() {
        document.getElementById('cart-sidebar').classList.add('open');
        document.getElementById('cart-overlay').classList.add('open');
        document.body.classList.add('cart-open');
        this.isOpen = true;
    }

    // Close cart sidebar
    closeCart() {
        document.getElementById('cart-sidebar').classList.remove('open');
        document.getElementById('cart-overlay').classList.remove('open');
        document.body.classList.remove('cart-open');
        this.isOpen = false;
    }

    // Update cart display
    updateCartDisplay() {
        this.updateCartCount();
        this.updateCartItems();
        this.updateCartTotal();
    }

    // Update cart count in navigation
    updateCartCount() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'inline' : 'none';
        }
    }

    // Update cart items display
    updateCartItems() {
        const cartItemsContainer = document.getElementById('cart-items');
        if (!cartItemsContainer) return;

        if (this.items.length === 0) {
            cartItemsContainer.innerHTML = '<div class="cart-empty">Your cart is empty</div>';
            return;
        }

        cartItemsContainer.innerHTML = this.items.map(item => `
            <div class="cart-item" data-item-id="${item.id}">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p class="cart-item-price">$${item.price.toFixed(2)}</p>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-item-decrease">-</button>
                    <span class="cart-item-quantity">${item.quantity}</span>
                    <button class="cart-item-increase">+</button>
                    <button class="cart-item-remove">×</button>
                </div>
                <div class="cart-item-total">
                    $${(item.price * item.quantity).toFixed(2)}
                </div>
            </div>
        `).join('');
    }

    // Update cart total
    updateCartTotal() {
        const cartTotal = document.getElementById('cart-total');
        if (cartTotal) {
            const total = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = total.toFixed(2);
        }
    }

    // Show added to cart notification
    showAddedNotification(itemName) {
        // Remove existing notification
        const existingNotification = document.querySelector('.cart-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create new notification
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${itemName} added to cart!</span>
        `;
        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Checkout process
    checkout() {
        if (this.items.length === 0) {
            alert('Your cart is empty!');
            return;
        }

        // For now, just show an alert with order summary
        const total = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const itemCount = this.items.reduce((sum, item) => sum + item.quantity, 0);
        
        alert(`Order Summary:\n${itemCount} items\nTotal: $${total.toFixed(2)}\n\nThank you for your order! Our team will contact you shortly to confirm your order and arrange pickup or delivery.`);
        
        // Clear cart after checkout
        this.clearCart();
        this.closeCart();
    }

    // Get cart items (for external use)
    getItems() {
        return this.items;
    }

    // Get cart total (for external use)
    getTotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.cart = new ShoppingCart();
});