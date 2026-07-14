<template>
  <header class="navbar">
    <div class="container">
      <template v-if="!isAdmin">
        <router-link to="/" class="logo">
          <span class="logo-icon">⚡</span>
          <span class="logo-text">MetroNet</span>
        </router-link>
      </template>
      <template v-else>
        <router-link to="/admin/customers" class="logo">
          <span class="logo-icon">⚡</span>
          <span class="logo-text">MetroNet</span>
        </router-link>
      </template>

      <nav class="nav">
        <!-- Admin Navigation -->
        <template v-if="isAdmin">

          <div class="admin-user">
            <span class="avatar-initials">{{ userInitials }}</span>
            <span class="user-name">{{ displayName }}</span>
            <a @click="handleLogout" class="logout-link">Logout</a>
          </div>
        </template>

        <!-- Customer Navigation -->
        <template v-else-if="isLoggedIn && !isAdmin">
          <router-link
            to="/"
            class="nav-link"
            active-class="nav-link--active"
            exact-active-class="nav-link--active"
          >
            Home
          </router-link>
          <router-link
            to="/plans"
            class="nav-link"
            active-class="nav-link--active"
          >
            Plans
          </router-link>

          <!-- Notification Bell -->
          <router-link
            to="/notifications"
            class="nav-link nav-notification"
            active-class="nav-link--active"
          >
            <span class="bell-icon" :class="{ 'bell-ring': notificationCount > 0 }">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              </svg>
              <span
                v-if="notificationCount > 0"
                class="notification-badge"
                :class="{ 'badge-pulse': notificationCount > 0 }"
              >
                {{ notificationCount > 99 ? '99+' : notificationCount }}
              </span>
            </span>
            <span class="bell-label">Notifications</span>
          </router-link>

          <!-- User Dropdown -->
          <div
            class="dropdown"
            ref="dropdown"
            v-click-outside="closeDropdown"
          >
            <button
              class="dropdown-toggle nav-link"
              @click.stop="toggleDropdown"
              type="button"
              aria-haspopup="true"
              :aria-expanded="isDropdownOpen"
            >
              <span class="avatar-initials">{{ userInitials }}</span>
              <span class="user-name">{{ displayName }}</span>
              <span class="dropdown-arrow" :class="{ 'dropdown-arrow--open': isDropdownOpen }">▼</span>
            </button>

            <div
              class="dropdown-menu"
              v-show="isDropdownOpen"
              role="menu"
            >
              <router-link to="/profile" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
                My Profile
              </router-link>
              <router-link to="/subscriptions" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                  </svg>
                </span>
                Subscriptions
              </router-link>
              <router-link to="/invoices" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                  </svg>
                </span>
                Invoices
              </router-link>
              <div class="dropdown-divider"></div>
              <a @click="handleLogout" class="dropdown-item logout-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                  </svg>
                </span>
                Logout
              </a>
            </div>
          </div>
        </template>

        <!-- Guest Navigation -->
        <template v-else>
          <router-link
            to="/"
            class="nav-link"
            active-class="nav-link--active"
            exact-active-class="nav-link--active"
          >
            Home
          </router-link>
          <router-link
            to="/plans"
            class="nav-link"
            active-class="nav-link--active"
          >
            Plans
          </router-link>
          <router-link to="/login" class="nav-link">Login</router-link>
          <router-link to="/register" class="nav-link nav-cta">Sign Up</router-link>
        </template>
      </nav>

      <!-- Mobile Menu Toggle -->
      <button class="mobile-toggle" @click="isMobileMenuOpen = !isMobileMenuOpen">
        <span class="hamburger"></span>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" v-show="isMobileMenuOpen">
      <!-- Customer Mobile Menu -->
      <template v-if="isLoggedIn && !isAdmin">
        <router-link to="/" class="mobile-link" @click="isMobileMenuOpen = false">Home</router-link>
        <router-link to="/plans" class="mobile-link" @click="isMobileMenuOpen = false">Plans</router-link>
        <div class="mobile-divider"></div>
        <router-link to="/profile" class="mobile-link" @click="isMobileMenuOpen = false">Profile</router-link>
        <router-link to="/subscriptions" class="mobile-link" @click="isMobileMenuOpen = false">Subscriptions</router-link>
        <router-link to="/invoices" class="mobile-link" @click="isMobileMenuOpen = false">Invoices</router-link>
        <router-link to="/notifications" class="mobile-link" @click="isMobileMenuOpen = false">
          <span>Notifications</span>
        </router-link>
        <div class="mobile-divider"></div>
        <a @click="handleLogout" class="mobile-link logout-link">Logout</a>
      </template>

      <!-- Guest Mobile Menu -->
      <template v-else>
        <router-link to="/" class="mobile-link" @click="isMobileMenuOpen = false">Home</router-link>
        <router-link to="/plans" class="mobile-link" @click="isMobileMenuOpen = false">Plans</router-link>
        <div class="mobile-divider"></div>
        <router-link to="/login" class="mobile-link" @click="isMobileMenuOpen = false">Login</router-link>
        <router-link to="/register" class="mobile-link" @click="isMobileMenuOpen = false">Sign Up</router-link>
      </template>
    </div>

    <!-- Logout Confirmation Modal -->
    <div v-if="showLogoutModal" class="modal-overlay" @click.self="closeLogoutModal">
      <div class="modal-container">
        <div class="modal-content">
          <div class="modal-icon">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </div>
          <h3>Confirm Logout</h3>
          <p>Are you sure you want to sign out of your account?</p>
          <div class="modal-actions">
            <button @click="closeLogoutModal" class="modal-btn modal-btn-secondary">
              Cancel
            </button>
            <button @click="confirmLogout" class="modal-btn modal-btn-primary">
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ Toast Notification -->
    <div v-if="toastMessage" class="toast" :class="toastType">
      <span class="toast-icon">
        <svg v-if="toastType === 'toast-success'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </span>
      {{ toastMessage }}
      <button @click="toastMessage = null" class="toast-close">×</button>
    </div>
  </header>
</template>

<script>
import { notificationService } from '../services/api'

export default {
  name: 'NavBar',
  data() {
    return {
      // UI State
      isDropdownOpen: false,
      isMobileMenuOpen: false,
      showLogoutModal: false,

      // Notification
      notificationCount: 0,
      previousNotificationCount: 0,

      // ✅ Toast
      toastMessage: null,
      toastType: 'toast-success',
      toastTimeout: null,

      // User State
      isLoggedIn: localStorage.getItem('isLoggedIn') === 'true',
      userName: localStorage.getItem('userName') || '',
      userEmail: localStorage.getItem('userEmail') || '',
      userRole: parseInt(localStorage.getItem('userRole') || '1'),

      // Polling
      pollInterval: null
    }
  },
  computed: {
    userInitials() {
      if (!this.userName) return 'U'
      const nameParts = this.userName.trim().split(' ')
      if (nameParts.length === 1) {
        return nameParts[0].substring(0, 2).toUpperCase()
      }
      return (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
    },
    displayName() {
      if (this.userName) {
        return this.userName.length > 20 ? this.userName.substring(0, 18) + '...' : this.userName
      } else if (this.userEmail) {
        return this.userEmail.split('@')[0]
      }
      return 'Account'
    },
    isAdmin() {
      return this.userRole === 0
    }
  },
  mounted() {
    this.fetchNotificationCount()
    this.startPolling()
    this.setupEventListeners()
  },
  beforeUnmount() {
    this.cleanup()
  },
  methods: {
    // ==================== SETUP ====================
    setupEventListeners() {
      window.addEventListener('userDataUpdated', this.updateUserData)
      window.addEventListener('notification-updated', this.handleNotificationUpdate)
      window.addEventListener('show-toast', this.handleShowToast)
      document.addEventListener('click', this.handleClickOutside)
    },

    cleanup() {
      window.removeEventListener('userDataUpdated', this.updateUserData)
      window.removeEventListener('notification-updated', this.handleNotificationUpdate)
      window.removeEventListener('show-toast', this.handleShowToast)
      document.removeEventListener('click', this.handleClickOutside)
      this.stopPolling()
      if (this.toastTimeout) {
        clearTimeout(this.toastTimeout)
      }
    },

    // ==================== USER DATA ====================
    updateUserData() {
      this.userName = localStorage.getItem('userName') || ''
      this.userEmail = localStorage.getItem('userEmail') || ''
      this.userRole = parseInt(localStorage.getItem('userRole') || '1')
      this.isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'

      if (this.isLoggedIn && !this.isAdmin) {
        this.fetchNotificationCount()
        this.startPolling()
      } else {
        this.notificationCount = 0
        this.stopPolling()
      }
    },

    // ==================== NOTIFICATIONS ====================
    handleNotificationUpdate(event) {
      if (event && event.detail) {
        this.updateNotificationCount(event.detail.count)
      } else {
        this.fetchNotificationCount()
      }
    },

    // ✅ Handle show-toast event from notifications page
    handleShowToast(event) {
      if (event && event.detail) {
        this.showToast(event.detail.message, event.detail.type || 'toast-success')
      }
    },

    async fetchNotificationCount() {
      if (!this.isLoggedIn || this.isAdmin) return

      try {
        const response = await notificationService.getNotifications()
        const notifications = response.data || response || []
        const newCount = notifications.filter(n => !n.is_read).length

        this.updateNotificationCount(newCount)

      } catch (error) {
        console.error('Failed to fetch notification count:', error)
      }
    },

    // ✅ Update notification count with toast if new notifications arrive
    updateNotificationCount(newCount) {
      const oldCount = this.notificationCount
      this.notificationCount = newCount

      // ✅ Show toast if new notifications arrived
      if (newCount > oldCount && oldCount > 0) {
        const diff = newCount - oldCount
        const message = diff === 1
          ? '🔔 You have 1 new notification!'
          : `🔔 You have ${diff} new notifications!`
        this.showToast(message, 'toast-success')
      }

      // ✅ Store previous count
      this.previousNotificationCount = oldCount
    },

    startPolling() {
      this.stopPolling()
      if (this.isLoggedIn && !this.isAdmin) {
        this.pollInterval = setInterval(() => {
          this.fetchNotificationCount()
        }, 30000)
      }
    },

    stopPolling() {
      if (this.pollInterval) {
        clearInterval(this.pollInterval)
        this.pollInterval = null
      }
    },

    // ==================== TOAST ====================
    showToast(message, type = 'toast-success') {
      // ✅ Clear existing timeout
      if (this.toastTimeout) {
        clearTimeout(this.toastTimeout)
      }

      this.toastMessage = message
      this.toastType = type

      // ✅ Auto-hide after 4 seconds
      this.toastTimeout = setTimeout(() => {
        this.toastMessage = null
      }, 4000)
    },

    // ==================== DROPDOWN ====================
    toggleDropdown() {
      this.isDropdownOpen = !this.isDropdownOpen
    },

    closeDropdown() {
      this.isDropdownOpen = false
    },

    handleClickOutside(event) {
      const dropdown = this.$refs.dropdown
      if (dropdown && !dropdown.contains(event.target)) {
        this.closeDropdown()
      }
    },

    // ==================== LOGOUT ====================
    handleLogout() {
      this.showLogoutModal = true
      this.closeDropdown()
      this.isMobileMenuOpen = false
    },

    closeLogoutModal() {
      this.showLogoutModal = false
    },

    confirmLogout() {
      this.showLogoutModal = false
      this.stopPolling()

      localStorage.removeItem('isLoggedIn')
      localStorage.removeItem('userEmail')
      localStorage.removeItem('userName')
      localStorage.removeItem('userData')
      localStorage.removeItem('authToken')
      localStorage.removeItem('userRole')
      localStorage.removeItem('isAdmin')

      this.isLoggedIn = false
      this.userName = ''
      this.userEmail = ''
      this.userRole = 1
      this.notificationCount = 0
      this.isDropdownOpen = false
      this.isMobileMenuOpen = false

      this.showToast('Successfully logged out!', 'toast-success')
      setTimeout(() => {
        this.$router.push('/')
      }, 500)
    }
  },
  watch: {
    '$route'() {
      this.updateUserData()
      this.closeDropdown()
    },
    isLoggedIn(val) {
      if (val) {
        this.fetchNotificationCount()
        this.startPolling()
      } else {
        this.notificationCount = 0
        this.stopPolling()
      }
    }
  }
}
</script>

<style scoped>
.navbar {
  background: #1a1a2e;
  color: #fff;
  padding: 12px 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.navbar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  position: relative;
  max-width: 1400px;
  margin: 0 auto;
  padding-left: 0;
  padding-right: 30px;
}

.logo {
  display: flex;
  align-items: center;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  text-decoration: none;
  flex-shrink: 0;
  margin-right: 30px;
}

.logo-icon {
  color: #ff6b35;
  margin-right: 8px;
  font-size: 28px;
}

.logo-text {
  background: linear-gradient(135deg, #ff6b35, #f7931e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.nav {
  display: flex;
  align-items: center;
  gap: 24px;
}

.nav-link {
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.3s;
  padding: 6px 0;
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
}

.nav-link:hover {
  color: #ff6b35;
}

.nav-link--active {
  color: #ff6b35;
}

.nav-link--active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #ff6b35;
}

.nav-cta {
  background: #ff6b35;
  color: #fff !important;
  padding: 8px 24px !important;
  border-radius: 50px;
  font-weight: 600;
  transition: background 0.3s, transform 0.2s;
}

.nav-cta:hover {
  background: #e85a2a;
  color: #fff !important;
  transform: scale(1.02);
}

.user-name {
  font-weight: 500;
  color: #fff;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.admin-user {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 4px 12px;
  border-radius: 8px;
  background: rgba(255,255,255,0.05);
}

.admin-user:hover {
  background: rgba(255,255,255,0.1);
}

.avatar-initials {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: #ff6b35;
  color: #fff;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  flex-shrink: 0;
}

.logout-link {
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  font-size: 14px;
  cursor: pointer;
  transition: color 0.3s;
}

.logout-link:hover {
  color: #ff4444;
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 8px;
  background: rgba(255,255,255,0.05);
  cursor: pointer;
  user-select: none;
  transition: background 0.3s;
}

.dropdown-toggle:hover {
  background: rgba(255,255,255,0.1);
}

.dropdown-arrow {
  font-size: 10px;
  transition: transform 0.3s;
  color: rgba(255,255,255,0.5);
  margin-left: 4px;
}

.dropdown-arrow--open {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #fff;
  border-radius: 12px;
  min-width: 240px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  padding: 8px 0;
  animation: slideDown 0.2s ease;
  z-index: 1001;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  color: #333;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.2s;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.dropdown-item:hover {
  background: #f5f5f5;
}

.dropdown-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  color: #888;
}

.dropdown-divider {
  height: 1px;
  background: #e8e8e8;
  margin: 4px 12px;
}

.logout-item {
  color: #e74c3c;
}

.logout-item:hover {
  background: #fdf2f2;
}

.logout-item .dropdown-icon {
  color: #e74c3c;
}

.mobile-toggle {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  flex-shrink: 0;
}

.hamburger {
  display: block;
  width: 24px;
  height: 2px;
  background: #fff;
  position: relative;
  transition: all 0.3s;
}

.hamburger::before,
.hamburger::after {
  content: '';
  position: absolute;
  width: 24px;
  height: 2px;
  background: #fff;
  transition: all 0.3s;
}

.hamburger::before {
  top: -6px;
}

.hamburger::after {
  bottom: -6px;
}

.mobile-menu {
  display: none;
  background: #1a1a2e;
  padding: 16px 20px 20px;
  border-top: 1px solid rgba(255,255,255,0.05);
  animation: slideDown 0.3s ease;
}

.mobile-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  transition: color 0.3s;
}

.mobile-link:hover {
  color: #ff6b35;
}

.mobile-divider {
  height: 1px;
  background: rgba(255,255,255,0.1);
  margin: 8px 0;
}

.mobile-link.logout-link {
  color: #e74c3c;
}

.mobile-link.logout-link:hover {
  color: #e74c3c !important;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-container {
  background: #fff;
  border-radius: 20px;
  max-width: 420px;
  width: 90%;
  padding: 40px 36px;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-content {
  text-align: center;
}

.modal-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.modal-icon svg {
  background: rgba(255, 107, 53, 0.08);
  padding: 12px;
  border-radius: 50%;
}

.modal-container h3 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.modal-container p {
  font-size: 15px;
  color: #8892a8;
  margin-bottom: 24px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.modal-btn {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.modal-btn-secondary {
  background: #f0f2f6;
  color: #1a1a2e;
}

.modal-btn-secondary:hover {
  background: #e8ecf1;
}

.modal-btn-primary {
  background: #ff6b35;
  color: #fff;
}

.modal-btn-primary:hover {
  background: #e85a2a;
}

/* ✅ Toast Notification - Enhanced */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  padding: 16px 24px;
  border-radius: 12px;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  z-index: 3000;
  animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  max-width: 400px;
  min-width: 280px;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(40px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.toast-success {
  background: #2ed573;
}

.toast-error {
  background: #ff4757;
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 18px;
}

.toast-close {
  background: none;
  border: none;
  color: rgba(255,255,255,0.7);
  font-size: 24px;
  cursor: pointer;
  padding: 0 4px;
  margin-left: auto;
  transition: color 0.3s;
}

.toast-close:hover {
  color: #fff;
}

/* ===== NOTIFICATION BELL STYLES ===== */
.nav-notification {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  position: relative;
  text-decoration: none;
  color: rgba(255,255,255,0.8);
  transition: color 0.3s;
  border-radius: 8px;
}

.nav-notification:hover {
  color: #ff6b35;
}

.bell-icon {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.bell-ring {
  animation: bellRing 0.5s ease-in-out 3;
}

@keyframes bellRing {
  0% { transform: rotate(0deg); }
  10% { transform: rotate(15deg); }
  20% { transform: rotate(-15deg); }
  30% { transform: rotate(10deg); }
  40% { transform: rotate(-10deg); }
  50% { transform: rotate(5deg); }
  60% { transform: rotate(-5deg); }
  70% { transform: rotate(2deg); }
  80% { transform: rotate(-2deg); }
  90% { transform: rotate(1deg); }
  100% { transform: rotate(0deg); }
}

.notification-badge {
  position: absolute;
  top: -6px;
  right: -8px;
  background: #ff6b35;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #1a1a2e;
  animation: badgePop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.badge-pulse {
  animation: badgePulse 1.5s ease-in-out infinite;
}

@keyframes badgePulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}

@keyframes badgePop {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

.bell-label {
  font-size: 14px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .nav {
    gap: 16px;
  }
}

@media (max-width: 768px) {
  .navbar .container {
    padding: 0 16px;
  }

  .nav {
    display: none;
  }

  .mobile-toggle {
    display: block;
  }

  .mobile-menu {
    display: block;
  }

  .logo {
    margin-right: 16px;
    font-size: 20px;
  }

  .logo-icon {
    font-size: 24px;
  }

  .dropdown-menu {
    position: static;
    box-shadow: none;
    background: rgba(255,255,255,0.05);
    margin-top: 4px;
    padding: 0;
    animation: none;
  }

  .dropdown-item {
    color: rgba(255,255,255,0.8);
    padding: 10px 16px;
  }

  .dropdown-item:hover {
    background: rgba(255,255,255,0.05);
  }

  .dropdown-divider {
    background: rgba(255,255,255,0.1);
  }

  .logout-item {
    color: #e74c3c;
  }

  .dropdown-icon {
    color: rgba(255,255,255,0.5);
  }

  .admin-user {
    padding: 4px 8px;
  }

  .nav-notification {
    padding: 4px 6px;
  }

  .bell-label {
    display: none;
  }

  .notification-badge {
    top: -4px;
    right: -6px;
    font-size: 9px;
    min-width: 16px;
    height: 16px;
  }

  .modal-container {
    padding: 32px 24px;
  }

  .toast {
    bottom: 20px;
    right: 20px;
    left: 20px;
    max-width: none;
  }
}

@media (max-width: 480px) {
  .navbar .container {
    padding: 0 12px;
  }

  .logo {
    font-size: 18px;
    margin-right: 12px;
  }

  .logo-icon {
    font-size: 20px;
    margin-right: 4px;
  }

  .logo-text {
    font-size: 18px;
  }

  .modal-container {
    padding: 24px 20px;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>
