<template>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">⚡</span>
        <span class="brand-text">MetroNet Admin</span>
      </div>

      <nav class="sidebar-nav">
        <router-link to="/admin/customers" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
            <path d="M16 3.13a4 4 0 010 7.75"/>
          </svg>
          <span>Customers</span>
        </router-link>

        <router-link to="/admin/plans" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            <path d="M2 17l10 5 10-5"/>
            <path d="M2 12l10 5 10-5"/>
          </svg>
          <span>Plans</span>
        </router-link>

        <router-link to="/admin/subscriptions" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
          <span>Subscriptions</span>
        </router-link>

        <router-link to="/admin/invoices" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
          <span>Invoices</span>
        </router-link>

        <router-link to="/admin/payments" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
          <span>Payments</span>
        </router-link>

        <router-link to="/admin/cpes" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="20" rx="2.18"/>
            <line x1="8" y1="2" x2="8" y2="22"/>
            <line x1="16" y1="2" x2="16" y2="22"/>
            <line x1="2" y1="8" x2="22" y2="8"/>
            <line x1="2" y1="16" x2="22" y2="16"/>
          </svg>
          <span>CPEs</span>
        </router-link>

        <router-link to="/admin/service-areas" class="nav-item" active-class="nav-item--active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            <circle cx="12" cy="9" r="3"/>
          </svg>
          <span>Service Areas</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <router-link to="/" class="nav-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          <span>Back to Site</span>
        </router-link>
      </div>
    </aside>

    <main class="admin-main">
      <div class="admin-content">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script>
export default {
  name: 'AdminLayout',
  computed: {
    pageTitle() {
      const route = this.$route
      const titles = {
        'admin-customers': 'Customers',
        'admin-plans': 'Plans',
        'admin-subscriptions': 'Subscriptions',
        'admin-invoices': 'Invoices',
        'admin-payments': 'Payments',
        'admin-cpes': 'CPEs',
        'admin-service-areas': 'Service Areas'
      }
      return titles[route.name] || 'Admin'
    }
  }
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #f0f2f5;
}

/* Sidebar */
.admin-sidebar {
  width: 260px;
  background: #1a1a2e;
  color: #fff;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 100;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  gap: 12px;
}

.brand-icon {
  font-size: 28px;
  color: #ff6b35;
}

.brand-text {
  font-size: 18px;
  font-weight: 700;
  background: linear-gradient(135deg, #ff6b35, #f7931e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 12px;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.3s;
  margin-bottom: 4px;
  font-size: 14px;
  font-weight: 500;
}

.nav-item:hover {
  background: rgba(255,255,255,0.05);
  color: #fff;
}

.nav-item--active {
  background: rgba(255,107,53,0.15);
  color: #ff6b35;
}

.nav-item svg {
  flex-shrink: 0;
}

.sidebar-footer {
  padding: 16px 12px;
  border-top: 1px solid rgba(255,255,255,0.05);
}

/* Main Content */
.admin-main {
  flex: 1;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.admin-header {
  background: #fff;
  padding: 20px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e8e8e8;
  position: sticky;
  top: 0;
  z-index: 50;
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.admin-user {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: #ff6b35;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: 14px;
}

.user-name {
  font-weight: 500;
  color: #333;
}

.admin-content {
  flex: 1;
  padding: 24px 32px;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-sidebar {
    width: 60px;
  }

  .brand-text,
  .nav-item span {
    display: none;
  }

  .sidebar-brand {
    justify-content: center;
    padding: 16px;
  }

  .nav-item {
    justify-content: center;
    padding: 12px;
  }

  .admin-main {
    margin-left: 60px;
  }

  .admin-header {
    padding: 16px 20px;
  }

  .admin-content {
    padding: 16px 20px;
  }
}
</style>
