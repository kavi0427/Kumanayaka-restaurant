/**
 * Authentication functionality for Bella Vista Restaurant
 */

class AuthManager {
    constructor() {
        this.currentUser = null;
        this.init();
    }
    
    init() {
        this.checkAuthStatus();
        this.updateNavigation();
    }
    
    async checkAuthStatus() {
        try {
            const response = await fetch('php/auth.php?action=profile');
            const data = await response.json();
            
            if (data.success && data.user) {
                this.currentUser = data.user;
                this.updateNavigation();
            }
        } catch (error) {
            console.log('Auth check failed:', error);
        }
    }
    
    updateNavigation() {
        const loginLink = document.querySelector('a[href="login.html"]');
        if (!loginLink) return;
        
        const navItem = loginLink.parentElement;
        
        if (this.currentUser) {
            // Replace login link with user menu
            navItem.innerHTML = `
                <div class="user-menu">
                    <span class="user-name">Welcome, ${this.currentUser.first_name}</span>
                    <div class="user-dropdown">
                        <a href="#profile" onclick="authManager.showProfile(); return false;">My Account</a>
                        <a href="#" onclick="authManager.logout()">Logout</a>
                    </div>
                </div>
            `;
        } else {
            // Keep login link
            navItem.innerHTML = '<a href="login.html" class="nav-link">Login</a>';
        }
    }
    
    async logout() {
        try {
            const response = await fetch('php/auth.php?action=logout', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = null;
                this.updateNavigation();
                window.location.href = 'index.html';
            }
        } catch (error) {
            console.error('Logout failed:', error);
        }
    }

    showProfile() {
        this.createProfileModal();
        document.getElementById('profileModal').style.display = 'flex';
        this.loadProfileData();
    }

    createProfileModal() {
        // Remove existing modal if present
        const existingModal = document.getElementById('profileModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalHTML = `
            <div id="profileModal" class="profile-modal">
                <div class="profile-modal-content">
                    <div class="profile-modal-header">
                        <h2>My Profile</h2>
                        <span class="close-modal" onclick="authManager.closeProfile()">&times;</span>
                    </div>
                    <div class="profile-modal-body">
                        <div class="profile-tabs">
                            <button class="profile-tab active" onclick="authManager.showProfileTab('info')">Profile Info</button>
                            <button class="profile-tab" onclick="authManager.showProfileTab('password')">Change Password</button>
                        </div>
                        
                        <div id="profile-info-tab" class="profile-tab-content active">
                            <div class="profile-info-grid">
                                <div class="info-item">
                                    <label>Name:</label>
                                    <span id="profile-name">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Username:</label>
                                    <span id="profile-username">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span id="profile-email">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Phone:</label>
                                    <span id="profile-phone">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Member Since:</label>
                                    <span id="profile-member-since">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Last Login:</label>
                                    <span id="profile-last-login">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="profile-password-tab" class="profile-tab-content">
                            <form id="customerPasswordForm">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" id="customerCurrentPassword" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" id="customerNewPassword" required>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" id="customerConfirmPassword" required>
                                </div>
                                <button type="submit" class="profile-btn">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Add event listener for password form
        document.getElementById('customerPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.changeCustomerPassword();
        });
    }

    closeProfile() {
        const modal = document.getElementById('profileModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showProfileTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.profile-tab').forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.profile-tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(`profile-${tabName}-tab`).classList.add('active');
    }

    loadProfileData() {
        if (this.currentUser) {
            document.getElementById('profile-name').textContent = `${this.currentUser.first_name} ${this.currentUser.last_name}`;
            document.getElementById('profile-username').textContent = this.currentUser.username;
            document.getElementById('profile-email').textContent = this.currentUser.email;
            document.getElementById('profile-phone').textContent = this.currentUser.phone || 'Not provided';
            document.getElementById('profile-member-since').textContent = new Date(this.currentUser.created_at).toLocaleDateString();
            document.getElementById('profile-last-login').textContent = this.currentUser.last_login ? 
                new Date(this.currentUser.last_login).toLocaleDateString() : 'Never';
        }
    }

    async changeCustomerPassword() {
        const currentPassword = document.getElementById('customerCurrentPassword').value;
        const newPassword = document.getElementById('customerNewPassword').value;
        const confirmPassword = document.getElementById('customerConfirmPassword').value;

        if (newPassword !== confirmPassword) {
            alert('New passwords do not match');
            return;
        }

        if (newPassword.length < 6) {
            alert('New password must be at least 6 characters long');
            return;
        }

        try {
            const token = localStorage.getItem('session_token');
            const response = await fetch('php/auth.php?action=change-password', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });

            const data = await response.json();
            if (data.success) {
                alert('Password changed successfully');
                document.getElementById('customerPasswordForm').reset();
            } else {
                alert('Failed to change password: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Change password failed:', error);
            alert('Failed to change password');
        }
    }
    
    isLoggedIn() {
        return this.currentUser !== null;
    }
    
    getUser() {
        return this.currentUser;
    }
}

// Initialize auth manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.authManager = new AuthManager();
});

// Add user menu styles
const userMenuStyles = `
    .user-menu {
        position: relative;
        display: inline-block;
    }
    
    .user-name {
        color: var(--primary-color);
        font-weight: 500;
        cursor: pointer;
        padding: 0.5rem 1rem;
        display: block;
    }
    
    .user-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #e1e1e1;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        min-width: 120px;
        z-index: 1000;
    }
    
    .user-dropdown a {
        display: block;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }
    
    .user-dropdown a:hover {
        background-color: #f8f9fa;
    }
    
    .user-menu:hover .user-dropdown {
        display: block;
    }
    
    @media (max-width: 768px) {
        .user-dropdown {
            position: static;
            display: block !important;
            box-shadow: none;
            border: none;
            background: transparent;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = userMenuStyles;
document.head.appendChild(styleSheet);

// Add profile modal styles
const profileModalStyles = `
    .profile-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
    }

    .profile-modal-content {
        background-color: white;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .profile-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        background: var(--primary-color);
        color: white;
        border-radius: 12px 12px 0 0;
    }

    .profile-modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .close-modal {
        font-size: 2rem;
        cursor: pointer;
        line-height: 1;
        opacity: 0.8;
    }

    .close-modal:hover {
        opacity: 1;
    }

    .profile-modal-body {
        padding: 1.5rem;
    }

    .profile-tabs {
        display: flex;
        margin-bottom: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 4px;
    }

    .profile-tab {
        flex: 1;
        padding: 0.75rem 1rem;
        border: none;
        background: none;
        cursor: pointer;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .profile-tab.active {
        background: var(--primary-color);
        color: white;
    }

    .profile-tab-content {
        display: none;
    }

    .profile-tab-content.active {
        display: block;
    }

    .profile-info-grid {
        display: grid;
        gap: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid var(--primary-color);
    }

    .info-item label {
        font-weight: 600;
        color: var(--primary-color);
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }

    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(194, 24, 91, 0.1);
    }

    .profile-btn {
        background: var(--primary-color);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .profile-btn:hover {
        background: var(--secondary-color);
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .profile-modal-content {
            width: 95%;
            margin: 1rem;
        }
        
        .profile-modal-header,
        .profile-modal-body {
            padding: 1rem;
        }
    }
`;

const profileStyleSheet = document.createElement('style');
profileStyleSheet.textContent = profileModalStyles;
document.head.appendChild(profileStyleSheet);