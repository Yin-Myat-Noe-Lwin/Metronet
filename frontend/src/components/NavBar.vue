<template>
  <header class="navbar">
    <div class="container">
      <router-link to="/" class="logo">
        <span class="logo-icon">⚡</span>
        <span class="logo-text">MetroNet</span>
      </router-link>

      <nav class="nav">
        <!-- Show different navigation based on user role -->
        <template v-if="isAdmin">
          <!-- Admin User Avatar -->
          <div class="admin-user">
            <span class="avatar-initials">{{ userInitials }}</span>
            <span class="user-name">{{ displayName }}</span>
            <a @click="handleLogout" class="logout-link">Logout</a>
          </div>
        </template>

        <!-- Regular User Navigation -->
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

          <!-- Account Dropdown -->
          <div class="dropdown" @mouseenter="isDropdownOpen = true" @mouseleave="isDropdownOpen = false">
            <button class="dropdown-toggle nav-link">
              <span class="avatar-initials">{{ userInitials }}</span>
              <span class="user-name">{{ displayName }}</span>
              <span class="dropdown-arrow">▼</span>
            </button>

            <div class="dropdown-menu" v-show="isDropdownOpen">
              <router-link to="/profile" class="dropdown-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
                My Profile
              </router-link>
              <router-link to="/addresses" class="dropdown-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                </span>
                Addresses
              </router-link>
              <router-link to="/subscriptions" class="dropdown-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                  </svg>
                </span>
                Subscriptions
              </router-link>
              <router-link to="/invoices" class="dropdown-item">
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
              <router-link to="/payments" class="dropdown-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                  </svg>
                </span>
                Payments
              </router-link>
              <router-link to="/notifications" class="dropdown-item">
                <span class="dropdown-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                  </svg>
                </span>
                Notifications
                <span class="badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
              </router-link>
              <div class="dropdown-divider"></div>
              <a @click="handleLogout" class="dropdown-item logout-item" style="cursor:pointer;">
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

        <!-- Not Logged In -->
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
      <!-- Admin Mobile Menu -->
      <template v-if="isAdmin">
        <router-link to="/admin/customers" class="mobile-link" @click="isMobileMenuOpen = false">Customers</router-link>
        <router-link to="/admin/plans" class="mobile-link" @click="isMobileMenuOpen = false">Plans</router-link>
        <router-link to="/admin/subscriptions" class="mobile-link" @click="isMobileMenuOpen = false">Subscriptions</router-link>
        <div class="mobile-divider"></div>
        <a @click="handleLogout" class="mobile-link logout-link">Logout</a>
      </template>

      <!-- Regular User Mobile Menu -->
      <template v-else-if="isLoggedIn && !isAdmin">
        <router-link to="/" class="mobile-link" @click="isMobileMenuOpen = false">Home</router-link>
        <router-link to="/plans" class="mobile-link" @click="isMobileMenuOpen = false">Plans</router-link>
        <div class="mobile-divider"></div>
        <router-link to="/profile" class="mobile-link" @click="isMobileMenuOpen = false">Profile</router-link>
        <router-link to="/addresses" class="mobile-link" @click="isMobileMenuOpen = false">Addresses</router-link>
        <router-link to="/subscriptions" class="mobile-link" @click="isMobileMenuOpen = false">Subscriptions</router-link>
        <router-link to="/invoices" class="mobile-link" @click="isMobileMenuOpen = false">Invoices</router-link>
        <router-link to="/payments" class="mobile-link" @click="isMobileMenuOpen = false">Payments</router-link>
        <router-link to="/notifications" class="mobile-link" @click="isMobileMenuOpen = false">
          Notifications
          <span class="badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
        </router-link>
        <div class="mobile-divider"></div>
        <a @click="handleLogout" class="mobile-link logout-link">Logout</a>
      </template>

      <!-- Not Logged In Mobile Menu -->
      <template v-else>
        <router-link to="/" class="mobile-link" @click="isMobileMenuOpen = false">Home</router-link>
        <router-link to="/plans" class="mobile-link" @click="isMobileMenuOpen = false">Plans</router-link>
        <div class="mobile-divider"></div>
        <router-link to="/login" class="mobile-link" @click="isMobileMenuOpen = false">Login</router-link>
        <router-link to="/register" class="mobile-link" @click="isMobileMenuOpen = false">Sign Up</router-link>
      </template>
    </div>
  </header>
</template>

<script>
export default {
  name: 'NavBar',
  data() {
    return {
      isDropdownOpen: false,
      isMobileMenuOpen: false,
      notificationCount: 0,
      isLoggedIn: localStorage.getItem('isLoggedIn') === 'true',
      userName: localStorage.getItem('userName') || '',
      userEmail: localStorage.getItem('userEmail') || '',
      userRole: parseInt(localStorage.getItem('userRole') || '1')
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
    window.addEventListener('userDataUpdated', this.updateUserData)
  },
  beforeUnmount() {
    window.removeEventListener('userDataUpdated', this.updateUserData)
  },
  methods: {
    updateUserData() {
      this.userName = localStorage.getItem('userName') || ''
      this.userEmail = localStorage.getItem('userEmail') || ''
      this.userRole = parseInt(localStorage.getItem('userRole') || '1')
      this.isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'
    },
    handleLogout() {
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
      this.isDropdownOpen = false
      this.isMobileMenuOpen = false

      this.$router.push('/')
    },
    fetchNotificationCount() {
      // You can replace with API call
    }
  },
  watch: {
    '$route'() {
      this.updateUserData()
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

/* Logo - Moved further left */
.logo {
  display: flex;
  align-items: center;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  text-decoration: none;
  flex-shrink: 0;
  margin-right: 30px; /* Space between logo and nav */
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

/* Navigation */
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

/* Admin User */
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

/* Dropdown (Regular User) */
.dropdown {
  position: relative;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 8px;
  background: rgba(255,255,255,0.05);
}

.dropdown-toggle:hover {
  background: rgba(255,255,255,0.1);
}

.dropdown-arrow {
  font-size: 10px;
  transition: transform 0.3s;
  color: rgba(255,255,255,0.5);
}

.dropdown:hover .dropdown-arrow {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: #fff;
  border-radius: 12px;
  min-width: 240px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  padding: 8px 0;
  animation: slideDown 0.2s ease;
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
  cursor: pointer;
}

.logout-item:hover {
  background: #fdf2f2;
}

.logout-item .dropdown-icon {
  color: #e74c3c;
}

.badge {
  background: #e74c3c;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 50px;
  margin-left: auto;
  min-width: 20px;
  text-align: center;
}

/* Mobile Toggle */
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

/* Mobile Menu */
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

.logout-link {
  color: #e74c3c;
}

.logout-link:hover {
  color: #e74c3c !important;
}

/* Responsive */
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

  .badge {
    background: #e74c3c;
    color: #fff;
  }

  .dropdown-icon {
    color: rgba(255,255,255,0.5);
  }

  .admin-user {
    padding: 4px 8px;
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
}
</style>
