<template>
  <div class="notifications-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <h1 class="page-title">Notifications</h1>
          <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
        </div>
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="btn-mark-all"
          :disabled="loading"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          Mark all as read
        </button>
      </div>

      <!-- Filters -->
      <div class="filters">
        <button
          v-for="filter in filters"
          :key="filter.key"
          class="filter-btn"
          :class="{ active: activeFilter === filter.key }"
          @click="activeFilter = filter.key"
        >
          {{ filter.label }}
          <span v-if="getFilterCount(filter.key) > 0" class="filter-count">
            {{ getFilterCount(filter.key) }}
          </span>
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading notifications...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredNotifications.length === 0" class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>All caught up!</h3>
        <p>{{ activeFilter === 'all' ? 'You have no notifications.' : 'No notifications in this category.' }}</p>
      </div>

      <!-- Notifications List -->
      <div v-else class="notifications-list">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="notification-item"
          :class="{ unread: !notification.is_read }"
        >
          <div class="notification-icon" :class="getEventTypeClass(notification.event_type)">
            {{ getEventTypeIcon(notification.event_type) }}
          </div>

          <div class="notification-content">
            <div class="notification-top">
              <h4 class="notification-title">{{ notification.title }}</h4>
              <span class="notification-time">{{ timeAgo(notification.created_at) }}</span>
            </div>
            <p class="notification-message">{{ notification.message }}</p>
            <div v-if="!notification.is_read" class="notification-actions">
              <button @click="markAsRead(notification.id)" class="btn-mark-read">
                Mark as read
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { notificationService } from '../services/api'

export default {
  name: 'NotificationsPage',
  data() {
    return {
      loading: false,
      notifications: [],
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'unread', label: 'Unread' },
        { key: '1', label: 'Invoices' },
        { key: '2', label: 'Payments' },
        { key: '3', label: 'Subscription' },
        { key: '4', label: 'Service' }
      ],
      // ✅ Auto-refresh interval
      refreshInterval: null,
      // ✅ Track if page is visible
      isPageVisible: true,
      // ✅ Track previous count for toast
      previousUnreadCount: 0
    }
  },
  computed: {
    unreadCount() {
      return this.notifications.filter(n => !n.is_read).length
    },
    filteredNotifications() {
      let filtered = this.notifications

      if (this.activeFilter === 'unread') {
        filtered = filtered.filter(n => !n.is_read)
      } else if (this.activeFilter !== 'all') {
        filtered = filtered.filter(n => String(n.event_type) === this.activeFilter)
      }

      return filtered
    }
  },
  mounted() {
    this.fetchNotifications()

    // ✅ Listen for real-time updates from navbar
    window.addEventListener('notification-updated', this.handleRealTimeUpdate)

    // ✅ Start auto-refresh polling (every 15 seconds)
    this.startAutoRefresh()

    // ✅ Refresh when tab becomes visible again
    document.addEventListener('visibilitychange', this.handleVisibilityChange)
  },
  beforeUnmount() {
    window.removeEventListener('notification-updated', this.handleRealTimeUpdate)
    document.removeEventListener('visibilitychange', this.handleVisibilityChange)
    this.stopAutoRefresh()
  },
  methods: {
    // ==================== FETCH NOTIFICATIONS ====================
    async fetchNotifications(silent = false) {
      if (!silent) {
        this.loading = true
      }

      try {
        const response = await notificationService.getNotifications()
        const newNotifications = response.data || response || []

        // ✅ Check if there are new notifications
        const oldIds = this.notifications.map(n => n.id)
        const newIds = newNotifications.map(n => n.id)
        const hasNew = newIds.some(id => !oldIds.includes(id))

        // ✅ Store old unread count
        const oldUnreadCount = this.unreadCount

        // ✅ Update notifications
        this.notifications = newNotifications

        // ✅ Check if unread count increased
        const newUnreadCount = this.unreadCount

        // ✅ Show toast if new notifications arrived
        if (hasNew && newUnreadCount > oldUnreadCount && !silent) {
          const diff = newUnreadCount - oldUnreadCount
          const message = diff === 1
            ? '📬 You have 1 new notification!'
            : `📬 You have ${diff} new notifications!`

          // Dispatch toast event
          window.dispatchEvent(new CustomEvent('show-toast', {
            detail: {
              message: message,
              type: 'toast-success'
            }
          }))

          // ✅ Update navbar count
          this.updateNavbarCount()
        } else if (!silent) {
          // ✅ Just update navbar count
          this.updateNavbarCount()
        }

      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        if (!silent) {
          this.loading = false
        }
      }
    },

    // ==================== AUTO REFRESH ====================
    startAutoRefresh() {
      this.stopAutoRefresh()
      this.refreshInterval = setInterval(() => {
        // ✅ Only refresh if page is visible
        if (this.isPageVisible) {
          this.fetchNotifications(true) // Silent refresh
        }
      }, 15000) // Every 15 seconds
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval)
        this.refreshInterval = null
      }
    },

    handleVisibilityChange() {
      this.isPageVisible = !document.hidden
      if (this.isPageVisible) {
        // ✅ Refresh when user comes back to tab
        this.fetchNotifications(true)
      }
    },

    // ==================== EVENT HANDLERS ====================
    handleRealTimeUpdate(event) {
      if (event && event.detail) {
        // ✅ Refresh when navbar updates count
        this.fetchNotifications(true)
      }
    },

    // ==================== FILTERS ====================
    getFilterCount(key) {
      if (key === 'all') return this.notifications.length
      if (key === 'unread') return this.unreadCount
      return this.notifications.filter(n => String(n.event_type) === key).length
    },

    getEventTypeClass(eventType) {
      const map = {
        1: 'invoice',
        2: 'payment',
        3: 'subscription',
        4: 'service'
      }
      return map[eventType] || 'default'
    },

    getEventTypeIcon(eventType) {
      const map = {
        1: '📄',
        2: '💳',
        3: '📋',
        4: '⚡'
      }
      return map[eventType] || '📢'
    },

    // ==================== MARK AS READ ====================
    async markAsRead(id) {
      try {
        await notificationService.markAsRead(id)

        const notification = this.notifications.find(n => n.id === id)
        if (notification) {
          notification.is_read = 1
          notification.read_at = new Date().toISOString()
        }

        // ✅ Update navbar count immediately
        this.updateNavbarCount()

        // ✅ Refresh silently to get latest data
        await this.fetchNotifications(true)

      } catch (error) {
        console.error('Failed to mark as read:', error)
      }
    },

    async markAllAsRead() {
      const unreadIds = this.notifications
        .filter(n => !n.is_read)
        .map(n => n.id)

      if (unreadIds.length === 0) return

      this.loading = true

      try {
        for (const id of unreadIds) {
          await notificationService.markAsRead(id)
        }

        // ✅ Update local state
        this.notifications.forEach(n => {
          if (!n.is_read) {
            n.is_read = 1
            n.read_at = new Date().toISOString()
          }
        })

        // ✅ Update navbar count immediately
        this.updateNavbarCount()

        // ✅ Refresh silently to get latest data
        await this.fetchNotifications(true)

      } catch (error) {
        console.error('Failed to mark all as read:', error)
      } finally {
        this.loading = false
      }
    },

    // ==================== NAVBAR COUNT ====================
    updateNavbarCount() {
      const unreadCount = this.notifications.filter(n => !n.is_read).length

      window.dispatchEvent(new CustomEvent('notification-updated', {
        detail: { count: unreadCount }
      }))
    },

    // ==================== TIME HELPER ====================
    timeAgo(date) {
      if (!date) return 'Just now'

      const now = new Date()
      const diff = now - new Date(date)
      const minutes = Math.floor(diff / 60000)
      const hours = Math.floor(diff / 3600000)
      const days = Math.floor(diff / 86400000)

      if (minutes < 1) return 'Just now'
      if (minutes < 60) return minutes + 'm ago'
      if (hours < 24) return hours + 'h ago'
      if (days < 7) return days + 'd ago'
      if (days < 30) return Math.floor(days / 7) + 'w ago'
      if (days < 365) return Math.floor(days / 30) + 'mo ago'
      return Math.floor(days / 365) + 'y ago'
    }
  }
}
</script>

<style scoped>
/* Your existing styles remain the same */
* {
  box-sizing: border-box;
}

.notifications-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 40px 0;
}

.container {
  max-width: 820px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ==================== HEADER ==================== */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title {
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.unread-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ff6b35;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  min-width: 24px;
  height: 24px;
  padding: 0 8px;
  border-radius: 50px;
}

.btn-mark-all {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-mark-all:hover:not(:disabled) {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-mark-all:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ==================== FILTERS ==================== */

.filters {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.filter-btn {
  padding: 6px 16px;
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.filter-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

.filter-btn.active {
  background: #ff6b35;
  border-color: #ff6b35;
  color: #fff;
}

.filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.2);
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  padding: 0 6px;
  border-radius: 50px;
}

.filter-btn:not(.active) .filter-count {
  background: #f1f5f9;
  color: #94a3b8;
}

/* ==================== LOADING ==================== */

.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.spinner {
  width: 36px;
  height: 36px;
  margin: 0 auto 12px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #ff6b35;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #94a3b8;
  font-size: 14px;
  margin: 0;
}

/* ==================== EMPTY ==================== */

.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
  display: block;
}

.empty-state h3 {
  font-size: 20px;
  color: #0f172a;
  margin: 0 0 4px;
}

.empty-state p {
  color: #94a3b8;
  font-size: 14px;
  margin: 0;
}

/* ==================== NOTIFICATIONS LIST ==================== */

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: #fff;
  padding: 16px 20px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  transition: all 0.3s;
}

.notification-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.notification-item.unread {
  border-left: 4px solid #ff6b35;
  background: #fafcff;
}

.notification-item.unread .notification-title {
  font-weight: 600;
}

/* Notification Icon */
.notification-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  margin-top: 2px;
}

.notification-icon.invoice {
  background: #eef2ff;
}

.notification-icon.payment {
  background: #ecfdf5;
}

.notification-icon.subscription {
  background: #fffbeb;
}

.notification-icon.service {
  background: #f5f3ff;
}

.notification-icon.default {
  background: #f1f5f9;
}

/* Notification Content */
.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 2px;
}

.notification-title {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
  margin: 0;
}

.notification-time {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
  flex-shrink: 0;
  margin-top: 1px;
}

.notification-message {
  font-size: 13px;
  color: #64748b;
  margin: 2px 0 0 0;
  line-height: 1.5;
}

.notification-actions {
  margin-top: 6px;
}

.btn-mark-read {
  background: none;
  border: none;
  color: #ff6b35;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  padding: 0;
  transition: color 0.3s;
}

.btn-mark-read:hover {
  color: #e85a2a;
  text-decoration: underline;
}

/* ==================== RESPONSIVE ==================== */

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-left {
    justify-content: space-between;
  }

  .btn-mark-all {
    justify-content: center;
  }

  .notification-item {
    padding: 14px 16px;
  }

  .notification-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }

  .notification-time {
    white-space: normal;
    font-size: 11px;
  }
}

@media (max-width: 480px) {
  .notifications-page {
    padding: 20px 0;
  }

  .page-title {
    font-size: 22px;
  }

  .filters {
    gap: 4px;
  }

  .filter-btn {
    font-size: 12px;
    padding: 4px 12px;
  }

  .notification-item {
    padding: 12px 14px;
  }

  .notification-icon {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .notification-title {
    font-size: 13px;
  }

  .notification-message {
    font-size: 12px;
  }
}
</style>
