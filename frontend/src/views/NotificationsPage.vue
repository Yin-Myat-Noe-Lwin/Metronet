<template>
  <div class="notifications-page">
    <div class="container">
      <div class="page-header">
        <div class="header-left">
          <h1 class="page-title">Notifications</h1>
          <span class="notification-count">{{ unreadCount }} unread</span>
        </div>
        <div class="header-actions">
          <button @click="markAllAsRead" class="mark-all-btn" v-if="unreadCount > 0">
            Mark all as read
          </button>
          <button @click="clearAll" class="clear-btn" v-if="notifications.length > 0">
            Clear all
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters">
        <button
          v-for="filter in filters"
          :key="filter.key"
          class="filter-btn"
          :class="{ 'filter-btn--active': activeFilter === filter.key }"
          @click="activeFilter = filter.key"
        >
          {{ filter.label }}
          <span class="filter-count" v-if="filter.count">{{ filter.count }}</span>
        </button>
      </div>

      <!-- Notifications List -->
      <div v-if="filteredNotifications.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
        </div>
        <h3>No Notifications</h3>
        <p>You're all caught up! No new notifications.</p>
      </div>

      <div v-else class="notifications-list">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'notification-item--unread': !notification.read }"
        >
          <div class="notification-icon" :class="notification.type">
            <span v-if="notification.type === 'info'">ℹ️</span>
            <span v-else-if="notification.type === 'success'">✅</span>
            <span v-else-if="notification.type === 'warning'">⚠️</span>
            <span v-else-if="notification.type === 'error'">❌</span>
            <span v-else>📢</span>
          </div>

          <div class="notification-content">
            <div class="notification-header">
              <h4 class="notification-title">{{ notification.title }}</h4>
              <span class="notification-time">{{ timeAgo(notification.created_at) }}</span>
            </div>
            <p class="notification-message">{{ notification.message }}</p>
            <div class="notification-actions" v-if="!notification.read">
              <button @click="markAsRead(notification.id)" class="mark-read-btn">
                Mark as read
              </button>
            </div>
          </div>

          <button @click="deleteNotification(notification.id)" class="delete-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotificationsPage',
  data() {
    return {
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'unread', label: 'Unread' },
        { key: 'info', label: 'Info' },
        { key: 'success', label: 'Success' },
        { key: 'warning', label: 'Warning' }
      ],
      notifications: [
        {
          id: 1,
          title: 'Payment Successful',
          message: 'Your payment of 85,000 MMK for January 2025 has been processed successfully.',
          type: 'success',
          read: false,
          created_at: '2025-01-30T10:30:00'
        },
        {
          id: 2,
          title: 'Service Maintenance',
          message: 'Scheduled maintenance will occur on February 5th from 2:00 AM to 4:00 AM. Service may be interrupted.',
          type: 'warning',
          read: false,
          created_at: '2025-01-29T15:45:00'
        },
        {
          id: 3,
          title: 'Invoice Reminder',
          message: 'Your invoice #0002 for 120,000 MMK is due on February 10th. Please make payment to avoid service interruption.',
          type: 'info',
          read: false,
          created_at: '2025-01-28T09:15:00'
        },
        {
          id: 4,
          title: 'Welcome to MetroNet!',
          message: 'Welcome aboard! Your internet service has been activated. Enjoy high-speed connectivity!',
          type: 'success',
          read: true,
          created_at: '2025-01-15T14:00:00'
        },
        {
          id: 5,
          title: 'Service Outage Resolved',
          message: 'The service interruption in your area has been resolved. We apologize for the inconvenience.',
          type: 'info',
          read: true,
          created_at: '2025-01-12T11:20:00'
        },
        {
          id: 6,
          title: 'Payment Failed',
          message: 'Your recent payment of 65,000 MMK could not be processed. Please update your payment method.',
          type: 'error',
          read: true,
          created_at: '2025-01-10T16:30:00'
        }
      ]
    }
  },
  computed: {
    unreadCount() {
      return this.notifications.filter(n => !n.read).length
    },
    filteredNotifications() {
      let filtered = this.notifications

      if (this.activeFilter === 'unread') {
        filtered = filtered.filter(n => !n.read)
      } else if (this.activeFilter !== 'all') {
        filtered = filtered.filter(n => n.type === this.activeFilter)
      }

      // Sort by date (newest first)
      return filtered.sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at)
      })
    }
  },
  methods: {
    markAsRead(id) {
      const notification = this.notifications.find(n => n.id === id)
      if (notification) {
        notification.read = true
      }
    },

    markAllAsRead() {
      this.notifications.forEach(n => n.read = true)
    },

    deleteNotification(id) {
      if (!confirm('Are you sure you want to delete this notification?')) return
      this.notifications = this.notifications.filter(n => n.id !== id)
    },

    clearAll() {
      if (!confirm('Are you sure you want to clear all notifications?')) return
      this.notifications = []
    },

    timeAgo(date) {
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
.notifications-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 40px 0;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 32px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.notification-count {
  background: #ff6b35;
  color: #fff;
  padding: 2px 12px;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.mark-all-btn {
  padding: 8px 20px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.3s;
}

.mark-all-btn:hover {
  background: #e85a2a;
}

.clear-btn {
  padding: 8px 20px;
  background: #ffebee;
  color: #c62828;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.3s;
}

.clear-btn:hover {
  background: #ffcdd2;
}

/* Filters */
.filters {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.filter-btn {
  position: relative;
  padding: 8px 16px;
  background: #fff;
  border: 2px solid #e0e0e0;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

.filter-btn--active {
  background: #ff6b35;
  border-color: #ff6b35;
  color: #fff;
}

.filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.3);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 0 6px;
  border-radius: 50px;
  margin-left: 4px;
}

/* Notifications List */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: #fff;
  padding: 16px 20px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: all 0.3s;
  position: relative;
}

.notification-item:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.notification-item--unread {
  background: #f5faff;
  border-left: 4px solid #ff6b35;
}

.notification-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.notification-icon.info {
  background: #e3f2fd;
}

.notification-icon.success {
  background: #e8f5e9;
}

.notification-icon.warning {
  background: #fff3e0;
}

.notification-icon.error {
  background: #ffebee;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 4px;
}

.notification-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0;
}

.notification-time {
  font-size: 12px;
  color: #888;
  white-space: nowrap;
}

.notification-message {
  color: #555;
  font-size: 14px;
  margin: 0 0 8px 0;
  line-height: 1.5;
}

.notification-actions {
  margin-top: 4px;
}

.mark-read-btn {
  background: none;
  border: none;
  color: #ff6b35;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  padding: 0;
}

.mark-read-btn:hover {
  text-decoration: underline;
}

.delete-btn {
  flex-shrink: 0;
  background: none;
  border: none;
  color: #ccc;
  cursor: pointer;
  padding: 4px;
  transition: color 0.3s;
  margin-top: 4px;
}

.delete-btn:hover {
  color: #e74c3c;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.empty-icon {
  color: #ccc;
  margin-bottom: 16px;
}

.empty-state h3 {
  color: #1a1a2e;
  margin-bottom: 8px;
}

.empty-state p {
  color: #888;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 26px;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions button {
    flex: 1;
    text-align: center;
  }

  .notification-item {
    flex-wrap: wrap;
  }

  .notification-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .notification-time {
    white-space: normal;
  }
}

@media (max-width: 480px) {
  .filters {
    gap: 6px;
  }

  .filter-btn {
    font-size: 12px;
    padding: 6px 12px;
  }

  .notification-item {
    padding: 12px 16px;
  }
}
</style>
