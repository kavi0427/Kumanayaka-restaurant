/**
 * Admin Dashboard functionality for Bella Vista Restaurant
 */

class AdminDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.sampleData = {
            reservations: [
                {
                    id: 1,
                    customerName: 'John Smith',
                    email: 'john@example.com',
                    phone: '+1 (555) 123-4567',
                    date: '2025-06-07',
                    time: '19:00',
                    partySize: 4,
                    status: 'confirmed',
                    specialRequests: 'Anniversary dinner',
                    createdAt: '2025-06-06T10:30:00Z'
                },
                {
                    id: 2,
                    customerName: 'Sarah Johnson',
                    email: 'sarah@example.com',
                    phone: '+1 (555) 987-6543',
                    date: '2025-06-07',
                    time: '20:30',
                    partySize: 2,
                    status: 'pending',
                    specialRequests: 'Vegetarian options',
                    createdAt: '2025-06-06T14:15:00Z'
                },
                {
                    id: 3,
                    customerName: 'Michael Brown',
                    email: 'michael@example.com',
                    phone: '+1 (555) 456-7890',
                    date: '2025-06-08',
                    time: '18:00',
                    partySize: 6,
                    status: 'confirmed',
                    specialRequests: 'Birthday celebration',
                    createdAt: '2025-06-06T16:45:00Z'
                }
            ],
            orders: [
                {
                    id: 'ORD-001',
                    customerName: 'Emily Davis',
                    items: ['Grilled Salmon', 'Caesar Salad', 'Tiramisu'],
                    total: 67.50,
                    status: 'preparing',
                    orderTime: '2025-06-06T18:30:00Z',
                    estimatedReady: '2025-06-06T19:15:00Z'
                },
                {
                    id: 'ORD-002',
                    customerName: 'Robert Wilson',
                    items: ['Ribeye Steak', 'Truffle Risotto'],
                    total: 89.00,
                    status: 'ready',
                    orderTime: '2025-06-06T19:00:00Z',
                    estimatedReady: '2025-06-06T19:45:00Z'
                },
                {
                    id: 'ORD-003',
                    customerName: 'Lisa Anderson',
                    items: ['Margherita Pizza', 'Caprese Salad', 'Wine'],
                    total: 45.25,
                    status: 'delivered',
                    orderTime: '2025-06-06T17:45:00Z',
                    estimatedReady: '2025-06-06T18:30:00Z'
                }
            ],
            menuItems: [
                {
                    id: 1,
                    name: 'Grilled Salmon',
                    description: 'Fresh Atlantic salmon with herbs and lemon',
                    price: 28.50,
                    category: 'Main Course',
                    available: true,
                    popularity: 95
                },
                {
                    id: 2,
                    name: 'Ribeye Steak',
                    description: 'Premium aged beef with garlic butter',
                    price: 42.00,
                    category: 'Main Course',
                    available: true,
                    popularity: 88
                },
                {
                    id: 3,
                    name: 'Truffle Risotto',
                    description: 'Creamy arborio rice with black truffle',
                    price: 24.00,
                    category: 'Main Course',
                    available: true,
                    popularity: 82
                },
                {
                    id: 4,
                    name: 'Caesar Salad',
                    description: 'Crisp romaine with parmesan and croutons',
                    price: 16.50,
                    category: 'Appetizer',
                    available: true,
                    popularity: 76
                },
                {
                    id: 5,
                    name: 'Tiramisu',
                    description: 'Classic Italian dessert with coffee and mascarpone',
                    price: 12.00,
                    category: 'Dessert',
                    available: true,
                    popularity: 91
                }
            ],
            customers: [
                {
                    id: 1,
                    name: 'John Smith',
                    email: 'john@example.com',
                    phone: '+1 (555) 123-4567',
                    totalOrders: 12,
                    lastVisit: '2025-06-05',
                    status: 'VIP',
                    joinDate: '2024-03-15'
                },
                {
                    id: 2,
                    name: 'Sarah Johnson',
                    email: 'sarah@example.com',
                    phone: '+1 (555) 987-6543',
                    totalOrders: 8,
                    lastVisit: '2025-06-02',
                    status: 'Regular',
                    joinDate: '2024-07-20'
                },
                {
                    id: 3,
                    name: 'Michael Brown',
                    email: 'michael@example.com',
                    phone: '+1 (555) 456-7890',
                    totalOrders: 5,
                    lastVisit: '2025-05-28',
                    status: 'Regular',
                    joinDate: '2024-11-10'
                }
            ]
        };
    }

    init() {
        this.setupNavigation();
        this.setupEventListeners();
        this.loadDashboardData();
        this.updateCurrentDate();
        this.checkAuthentication();
    }

    async checkAuthentication() {
        try {
            const response = await fetch('/php/admin.php?action=dashboard');
            if (response.status === 401) {
                this.showAdminLogin();
            } else if (response.ok) {
                this.isAuthenticated = true;
            } else {
                throw new Error('Authentication check failed');
            }
        } catch (error) {
            console.error('Authentication check error:', error);
            this.showAdminLogin();
        }
    }

    showAdminLogin() {
        const modal = document.getElementById('adminModal');
        const title = document.getElementById('adminModalTitle');
        const body = document.getElementById('adminModalBody');

        title.textContent = 'Admin Login';
        body.innerHTML = `
            <form id="adminLoginForm" class="admin-form">
                <div class="admin-form-group">
                    <label for="adminUsername">Username</label>
                    <input type="text" id="adminUsername" class="admin-input" required>
                </div>
                <div class="admin-form-group">
                    <label for="adminPassword">Password</label>
                    <input type="password" id="adminPassword" class="admin-input" required>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary">Login</button>
            </form>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--text-secondary));">
                Demo credentials: admin / admin123
            </p>
        `;

        modal.classList.add('show');

        document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('adminUsername').value;
            const password = document.getElementById('adminPassword').value;

            try {
                const response = await fetch('/php/admin.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.isAuthenticated = true;
                    modal.classList.remove('show');
                    this.showNotification('Login successful', 'success');
                    this.loadDashboardData();
                } else {
                    this.showNotification(result.message || 'Invalid credentials', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                this.showNotification('Login failed. Please try again.', 'error');
            }
        });
    }

    setupNavigation() {
        const navLinks = document.querySelectorAll('.admin-nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.switchSection(section);
            });
        });
    }

    setupEventListeners() {
        // Logout button
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                await fetch('/php/admin.php?action=logout', { method: 'POST' });
                this.isAuthenticated = false;
                location.reload();
            } catch (error) {
                console.error('Logout error:', error);
                location.reload();
            }
        });

        // Modal close
        document.querySelector('.admin-modal-close').addEventListener('click', () => {
            document.getElementById('adminModal').classList.remove('show');
        });

        // Filter event listeners
        document.getElementById('reservationFilter')?.addEventListener('change', () => {
            this.loadReservations();
        });

        document.getElementById('orderFilter')?.addEventListener('change', () => {
            this.loadOrders();
        });

        document.getElementById('customerSearch')?.addEventListener('input', (e) => {
            this.searchCustomers(e.target.value);
        });

        // Refresh buttons
        document.getElementById('refreshReservations')?.addEventListener('click', () => {
            this.loadReservations();
        });

        document.getElementById('refreshOrders')?.addEventListener('click', () => {
            this.loadOrders();
        });

        document.getElementById('refreshCustomers')?.addEventListener('click', () => {
            this.loadCustomers();
        });

        // Add menu item button
        document.getElementById('addMenuItem')?.addEventListener('click', () => {
            this.showAddMenuItemModal();
        });
    }

    switchSection(sectionName) {
        // Update navigation
        document.querySelectorAll('.admin-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');

        // Update sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById(sectionName).classList.add('active');

        this.currentSection = sectionName;

        // Load section-specific data
        switch (sectionName) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'reservations':
                this.loadReservations();
                break;
            case 'orders':
                this.loadOrders();
                break;
            case 'menu':
                this.loadMenuItems();
                break;
            case 'customers':
                this.loadCustomers();
                break;
            case 'analytics':
                this.loadAnalytics();
                break;
        }
    }

    updateCurrentDate() {
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            dateElement.textContent = now.toLocaleDateString('en-US', options);
        }
    }

    async loadDashboardData() {
        try {
            const response = await fetch('/php/admin.php?action=dashboard');
            
            if (!response.ok) {
                throw new Error('Failed to load dashboard data');
            }
            
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                
                // Update stats
                document.getElementById('todayReservations').textContent = data.todayReservations || 0;
                document.getElementById('todayOrders').textContent = data.activeOrders || 0;
                document.getElementById('todayRevenue').textContent = `$${(data.todayRevenue || 0).toFixed(2)}`;
                document.getElementById('totalCustomers').textContent = data.totalCustomers || 0;

                // Load recent reservations and popular items
                this.displayRecentReservations(data.recentReservations || []);
                this.displayPopularItems(data.popularItems || []);
            } else {
                throw new Error(result.message || 'Failed to load dashboard data');
            }
        } catch (error) {
            console.error('Dashboard data error:', error);
            this.showNotification('Failed to load dashboard data', 'error');
            
            // Fallback to showing empty state
            document.getElementById('todayReservations').textContent = '0';
            document.getElementById('todayOrders').textContent = '0';
            document.getElementById('todayRevenue').textContent = '$0.00';
            document.getElementById('totalCustomers').textContent = '0';
            
            document.getElementById('recentReservations').innerHTML = '<p>Unable to load recent reservations</p>';
            document.getElementById('popularItems').innerHTML = '<p>Unable to load popular items</p>';
        }
    }

    displayRecentReservations(reservations) {
        const container = document.getElementById('recentReservations');

        if (!reservations || reservations.length === 0) {
            container.innerHTML = '<p style="color: hsl(var(--text-secondary)); text-align: center; padding: 2rem;">No recent reservations</p>';
            return;
        }

        container.innerHTML = reservations.map(reservation => `
            <div class="admin-activity-item">
                <div class="admin-activity-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="admin-activity-content">
                    <div class="admin-activity-title">${reservation.customer_name}</div>
                    <div class="admin-activity-time">
                        ${this.formatDate(reservation.reservation_date)} at ${reservation.reservation_time} - ${reservation.party_size} guests
                    </div>
                </div>
                <span class="admin-status ${reservation.status}">${reservation.status}</span>
            </div>
        `).join('');
    }

    displayPopularItems(items) {
        const container = document.getElementById('popularItems');

        if (!items || items.length === 0) {
            container.innerHTML = '<p style="color: hsl(var(--text-secondary)); text-align: center; padding: 2rem;">No menu data available</p>';
            return;
        }

        container.innerHTML = items.map(item => `
            <div class="admin-popular-item">
                <span class="admin-popular-item-name">${item.name}</span>
                <span class="admin-popular-item-count">${item.popularity_score}% popularity</span>
            </div>
        `).join('');
    }

    async loadReservations() {
        const tbody = document.getElementById('reservationsTableBody');
        const filter = document.getElementById('reservationFilter')?.value || 'all';
        
        try {
            const response = await fetch(`/php/admin.php?action=reservations&filter=${filter}`);
            
            if (!response.ok) {
                throw new Error('Failed to load reservations');
            }
            
            const result = await response.json();
            
            if (result.success) {
                const reservations = result.data;
                
                if (!reservations || reservations.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: hsl(var(--text-secondary));">
                                No reservations found
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = reservations.map(reservation => `
                    <tr>
                        <td>
                            <div style="font-weight: 500;">${this.formatDate(reservation.reservation_date)}</div>
                            <div style="font-size: 0.875rem; color: hsl(var(--text-secondary));">${reservation.reservation_time}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">${reservation.customer_name}</div>
                            <div style="font-size: 0.875rem; color: hsl(var(--text-secondary));">${reservation.email}</div>
                        </td>
                        <td>${reservation.party_size} guests</td>
                        <td>${reservation.phone}</td>
                        <td><span class="admin-status ${reservation.status}">${reservation.status}</span></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="admin-btn admin-btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.updateReservationStatus(${reservation.id}, 'confirmed')">Confirm</button>
                                <button class="admin-btn admin-btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.updateReservationStatus(${reservation.id}, 'cancelled')">Cancel</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                throw new Error(result.message || 'Failed to load reservations');
            }
        } catch (error) {
            console.error('Reservations error:', error);
            this.showNotification('Failed to load reservations', 'error');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: hsl(var(--text-secondary));">
                        Error loading reservations
                    </td>
                </tr>
            `;
        }
    }

    loadOrders() {
        const tbody = document.getElementById('ordersTableBody');
        const filter = document.getElementById('orderFilter')?.value || 'all';
        
        let filteredOrders = this.sampleData.orders;
        
        if (filter !== 'all') {
            filteredOrders = this.sampleData.orders.filter(o => o.status === filter);
        }

        tbody.innerHTML = filteredOrders.map(order => `
            <tr>
                <td style="font-weight: 500;">${order.id}</td>
                <td>${order.customerName}</td>
                <td>
                    <div style="font-size: 0.875rem;">
                        ${order.items.join(', ')}
                    </div>
                </td>
                <td style="font-weight: 500;">$${order.total.toFixed(2)}</td>
                <td><span class="admin-status ${order.status}">${order.status}</span></td>
                <td style="font-size: 0.875rem;">${this.formatTime(order.orderTime)}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        ${this.getOrderActions(order)}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    getOrderActions(order) {
        switch (order.status) {
            case 'pending':
                return `<button class="admin-btn admin-btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.updateOrderStatus('${order.id}', 'preparing')">Start Preparing</button>`;
            case 'preparing':
                return `<button class="admin-btn admin-btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.updateOrderStatus('${order.id}', 'ready')">Mark Ready</button>`;
            case 'ready':
                return `<button class="admin-btn admin-btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.updateOrderStatus('${order.id}', 'delivered')">Mark Delivered</button>`;
            default:
                return '<span style="color: hsl(var(--text-secondary)); font-size: 0.75rem;">No actions</span>';
        }
    }

    loadMenuItems() {
        const container = document.getElementById('menuItemsContainer');
        
        container.innerHTML = this.sampleData.menuItems.map(item => `
            <div class="admin-menu-item">
                <div class="admin-menu-item-image">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="admin-menu-item-content">
                    <div class="admin-menu-item-header">
                        <h4 class="admin-menu-item-name">${item.name}</h4>
                        <span class="admin-menu-item-price">$${item.price.toFixed(2)}</span>
                    </div>
                    <p class="admin-menu-item-description">${item.description}</p>
                    <div style="margin-bottom: 1rem;">
                        <span class="admin-status ${item.available ? 'confirmed' : 'cancelled'}">
                            ${item.available ? 'Available' : 'Unavailable'}
                        </span>
                        <span style="margin-left: 1rem; font-size: 0.875rem; color: hsl(var(--text-secondary));">
                            ${item.popularity}% popularity
                        </span>
                    </div>
                    <div class="admin-menu-item-actions">
                        <button class="admin-btn admin-btn-secondary" onclick="adminDashboard.editMenuItem(${item.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="admin-btn admin-btn-${item.available ? 'danger' : 'success'}" onclick="adminDashboard.toggleMenuItem(${item.id})">
                            <i class="fas fa-${item.available ? 'eye-slash' : 'eye'}"></i> 
                            ${item.available ? 'Disable' : 'Enable'}
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    loadCustomers() {
        const tbody = document.getElementById('customersTableBody');
        
        tbody.innerHTML = this.sampleData.customers.map(customer => `
            <tr>
                <td style="font-weight: 500;">${customer.name}</td>
                <td>${customer.email}</td>
                <td>${customer.phone}</td>
                <td>${customer.totalOrders}</td>
                <td>${this.formatDate(customer.lastVisit)}</td>
                <td><span class="admin-status ${customer.status.toLowerCase()}">${customer.status}</span></td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="admin-btn admin-btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.viewCustomer(${customer.id})">View</button>
                        <button class="admin-btn admin-btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.contactCustomer(${customer.id})">Contact</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    searchCustomers(query) {
        if (!query) {
            this.loadCustomers();
            return;
        }

        const filteredCustomers = this.sampleData.customers.filter(customer =>
            customer.name.toLowerCase().includes(query.toLowerCase()) ||
            customer.email.toLowerCase().includes(query.toLowerCase()) ||
            customer.phone.includes(query)
        );

        const tbody = document.getElementById('customersTableBody');
        tbody.innerHTML = filteredCustomers.map(customer => `
            <tr>
                <td style="font-weight: 500;">${customer.name}</td>
                <td>${customer.email}</td>
                <td>${customer.phone}</td>
                <td>${customer.totalOrders}</td>
                <td>${this.formatDate(customer.lastVisit)}</td>
                <td><span class="admin-status ${customer.status.toLowerCase()}">${customer.status}</span></td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="admin-btn admin-btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.viewCustomer(${customer.id})">View</button>
                        <button class="admin-btn admin-btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="adminDashboard.contactCustomer(${customer.id})">Contact</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    loadAnalytics() {
        // In a real application, this would load actual analytics data
        this.showNotification('Analytics data loaded', 'info');
    }

    // Action methods
    async updateReservationStatus(id, status) {
        try {
            const response = await fetch('/php/admin.php?action=reservations', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, status })
            });

            const result = await response.json();
            
            if (result.success) {
                this.loadReservations();
                this.showNotification(`Reservation ${status}`, 'success');
            } else {
                throw new Error(result.message || 'Failed to update reservation');
            }
        } catch (error) {
            console.error('Update reservation error:', error);
            this.showNotification('Failed to update reservation', 'error');
        }
    }

    async updateOrderStatus(id, status) {
        try {
            const response = await fetch('/php/admin.php?action=orders', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, status })
            });

            const result = await response.json();
            
            if (result.success) {
                this.loadOrders();
                this.showNotification(`Order marked as ${status}`, 'success');
            } else {
                throw new Error(result.message || 'Failed to update order');
            }
        } catch (error) {
            console.error('Update order error:', error);
            this.showNotification('Failed to update order', 'error');
        }
    }

    toggleMenuItem(id) {
        const item = this.sampleData.menuItems.find(i => i.id === id);
        if (item) {
            item.available = !item.available;
            this.loadMenuItems();
            this.showNotification(`Menu item ${item.available ? 'enabled' : 'disabled'}`, 'success');
        }
    }

    editMenuItem(id) {
        const item = this.sampleData.menuItems.find(i => i.id === id);
        if (item) {
            this.showEditMenuItemModal(item);
        }
    }

    viewCustomer(id) {
        const customer = this.sampleData.customers.find(c => c.id === id);
        if (customer) {
            this.showCustomerModal(customer);
        }
    }

    contactCustomer(id) {
        const customer = this.sampleData.customers.find(c => c.id === id);
        if (customer) {
            this.showNotification(`Opening contact for ${customer.name}`, 'info');
        }
    }

    // Modal methods
    showAddMenuItemModal() {
        const modal = document.getElementById('adminModal');
        const title = document.getElementById('adminModalTitle');
        const body = document.getElementById('adminModalBody');

        title.textContent = 'Add Menu Item';
        body.innerHTML = `
            <form id="addMenuItemForm" class="admin-form">
                <div class="admin-form-group">
                    <label for="itemName">Item Name</label>
                    <input type="text" id="itemName" class="admin-input" required>
                </div>
                <div class="admin-form-group">
                    <label for="itemDescription">Description</label>
                    <textarea id="itemDescription" class="admin-textarea" rows="3" required></textarea>
                </div>
                <div class="admin-form-group">
                    <label for="itemPrice">Price</label>
                    <input type="number" id="itemPrice" class="admin-input" step="0.01" required>
                </div>
                <div class="admin-form-group">
                    <label for="itemCategory">Category</label>
                    <select id="itemCategory" class="admin-select" required>
                        <option value="">Select category</option>
                        <option value="Appetizer">Appetizer</option>
                        <option value="Main Course">Main Course</option>
                        <option value="Dessert">Dessert</option>
                        <option value="Beverage">Beverage</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">Add Item</button>
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="document.getElementById('adminModal').classList.remove('show')">Cancel</button>
                </div>
            </form>
        `;

        modal.classList.add('show');

        document.getElementById('addMenuItemForm').addEventListener('submit', (e) => {
            e.preventDefault();
            // Add menu item logic here
            modal.classList.remove('show');
            this.showNotification('Menu item added successfully', 'success');
        });
    }

    showEditMenuItemModal(item) {
        const modal = document.getElementById('adminModal');
        const title = document.getElementById('adminModalTitle');
        const body = document.getElementById('adminModalBody');

        title.textContent = 'Edit Menu Item';
        body.innerHTML = `
            <form id="editMenuItemForm" class="admin-form">
                <div class="admin-form-group">
                    <label for="editItemName">Item Name</label>
                    <input type="text" id="editItemName" class="admin-input" value="${item.name}" required>
                </div>
                <div class="admin-form-group">
                    <label for="editItemDescription">Description</label>
                    <textarea id="editItemDescription" class="admin-textarea" rows="3" required>${item.description}</textarea>
                </div>
                <div class="admin-form-group">
                    <label for="editItemPrice">Price</label>
                    <input type="number" id="editItemPrice" class="admin-input" step="0.01" value="${item.price}" required>
                </div>
                <div class="admin-form-group">
                    <label for="editItemCategory">Category</label>
                    <select id="editItemCategory" class="admin-select" required>
                        <option value="Appetizer" ${item.category === 'Appetizer' ? 'selected' : ''}>Appetizer</option>
                        <option value="Main Course" ${item.category === 'Main Course' ? 'selected' : ''}>Main Course</option>
                        <option value="Dessert" ${item.category === 'Dessert' ? 'selected' : ''}>Dessert</option>
                        <option value="Beverage" ${item.category === 'Beverage' ? 'selected' : ''}>Beverage</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="document.getElementById('adminModal').classList.remove('show')">Cancel</button>
                </div>
            </form>
        `;

        modal.classList.add('show');

        document.getElementById('editMenuItemForm').addEventListener('submit', (e) => {
            e.preventDefault();
            // Save changes logic here
            modal.classList.remove('show');
            this.showNotification('Menu item updated successfully', 'success');
        });
    }

    showCustomerModal(customer) {
        const modal = document.getElementById('adminModal');
        const title = document.getElementById('adminModalTitle');
        const body = document.getElementById('adminModalBody');

        title.textContent = 'Customer Details';
        body.innerHTML = `
            <div class="admin-customer-details">
                <div class="admin-form-group">
                    <label>Name</label>
                    <div class="admin-detail-value">${customer.name}</div>
                </div>
                <div class="admin-form-group">
                    <label>Email</label>
                    <div class="admin-detail-value">${customer.email}</div>
                </div>
                <div class="admin-form-group">
                    <label>Phone</label>
                    <div class="admin-detail-value">${customer.phone}</div>
                </div>
                <div class="admin-form-group">
                    <label>Total Orders</label>
                    <div class="admin-detail-value">${customer.totalOrders}</div>
                </div>
                <div class="admin-form-group">
                    <label>Last Visit</label>
                    <div class="admin-detail-value">${this.formatDate(customer.lastVisit)}</div>
                </div>
                <div class="admin-form-group">
                    <label>Status</label>
                    <div class="admin-detail-value">
                        <span class="admin-status ${customer.status.toLowerCase()}">${customer.status}</span>
                    </div>
                </div>
                <div class="admin-form-group">
                    <label>Member Since</label>
                    <div class="admin-detail-value">${this.formatDate(customer.joinDate)}</div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button class="admin-btn admin-btn-primary" onclick="adminDashboard.contactCustomer(${customer.id})">Contact Customer</button>
                    <button class="admin-btn admin-btn-secondary" onclick="document.getElementById('adminModal').classList.remove('show')">Close</button>
                </div>
            </div>
        `;

        modal.classList.add('show');
    }

    // Utility methods
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('adminNotifications');
        const notification = document.createElement('div');
        notification.className = `admin-notification ${type}`;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        container.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                container.removeChild(notification);
            }, 300);
        }, 3000);
    }

    getNotificationIcon(type) {
        switch (type) {
            case 'success': return 'check-circle';
            case 'error': return 'exclamation-circle';
            case 'warning': return 'exclamation-triangle';
            default: return 'info-circle';
        }
    }
}

// Initialize admin dashboard
const adminDashboard = new AdminDashboard();

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    adminDashboard.init();
});

// Add CSS for customer details
const style = document.createElement('style');
style.textContent = `
    .admin-customer-details .admin-detail-value {
        padding: 0.5rem;
        background: hsl(var(--background));
        border-radius: var(--radius-md);
        border: 1px solid hsl(var(--border));
        color: hsl(var(--text-primary));
        font-weight: 500;
    }
    
    .admin-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
`;
document.head.appendChild(style);