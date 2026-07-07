<template>
  <div class="subscriptions-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">My Subscriptions</h1>
        <p class="page-subtitle">Manage your active subscriptions and service status</p>
      </div>

      <!-- Service Status -->
      <div class="service-status-card">
        <div class="status-header">
          <h3>Service Status</h3>
          <span class="status-update">Last updated: {{ lastUpdated }}</span>
        </div>
        <div class="status-indicator" :class="serviceStatus">
          <span class="status-dot"></span>
          <span class="status-text">{{ serviceStatusText }}</span>
          <button class="refresh-btn" @click="checkServiceStatus">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="23 4 23 10 17 10"/>
              <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
            </svg>
            Refresh
          </button>
        </div>
      </div>

      <!-- Subscriptions List -->
      <div v-if="subscriptions.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
        </div>
        <h3>No Active Subscriptions</h3>
        <p>You don't have any active subscriptions yet.</p>
        <router-link to="/plans" class="browse-btn">Browse Plans</router-link>
      </div>

      <div v-else class="subscriptions-grid">
        <div
          v-for="subscription in subscriptions"
          :key="subscription.id"
          class="subscription-card"
          :class="subscription.status"
        >
          <div class="card-header">
            <div class="plan-info">
              <h3 class="plan-name">{{ subscription.plan_name }}</h3>
              <span class="plan-speed">{{ subscription.speed }}</span>
            </div>
            <span class="status-badge" :class="subscription.status">
              {{ subscription.status }}
            </span>
          </div>

          <div class="plan-price">
            <span class="price-amount">{{ subscription.price }}</span>
            <span class="price-period">/month</span>
          </div>

          <div class="subscription-details">
            <div class="detail-item">
              <span class="detail-label">Started</span>
              <span class="detail-value">{{ formatDate(subscription.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Next Billing</span>
              <span class="detail-value">{{ formatDate(subscription.next_billing) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Data Usage</span>
              <span class="detail-value">{{ subscription.data_usage || 'N/A' }}</span>
            </div>
          </div>

          <div class="progress-section" v-if="subscription.data_usage">
            <div class="progress-label">
              <span>Data Usage</span>
              <span>{{ subscription.data_usage_percentage || 0 }}%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: (subscription.data_usage_percentage || 0) + '%' }"></div>
            </div>
          </div>

          <div class="subscription-actions">
            <button @click="checkServiceStatus" class="action-btn status-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              Check Status
            </button>
            <button @click="cancelSubscription(subscription.id)" class="action-btn cancel-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SubscriptionsPage',
  data() {
    return {
      serviceStatus: 'active',
      serviceStatusText: 'All services are running normally',
      lastUpdated: '',
      subscriptions: [
        {
          id: 1,
          plan_name: 'Pro',
          speed: '300 Mbps',
          price: '85,000',
          status: 'active',
          created_at: '2025-01-15',
          next_billing: '2025-02-15',
          data_usage: '450 GB / 1 TB',
          data_usage_percentage: 45
        },
        {
          id: 2,
          plan_name: 'Business',
          speed: '500 Mbps',
          price: '120,000',
          status: 'active',
          created_at: '2025-01-20',
          next_billing: '2025-02-20',
          data_usage: '2.1 TB / 5 TB',
          data_usage_percentage: 42
        },
        {
          id: 3,
          plan_name: 'Home',
          speed: '150 Mbps',
          price: '65,000',
          status: 'pending',
          created_at: '2025-02-01',
          next_billing: '2025-03-01',
          data_usage: '200 GB / Unlimited',
          data_usage_percentage: 20
        },
        {
          id: 4,
          plan_name: 'Ultimate',
          speed: '2 Gbps',
          price: '250,000',
          status: 'cancelled',
          created_at: '2024-11-01',
          next_billing: '2025-01-01',
          data_usage: '3.5 TB / 10 TB',
          data_usage_percentage: 35
        }
      ]
    }
  },
  mounted() {
    this.updateTimestamp()
  },
  methods: {
    updateTimestamp() {
      const now = new Date()
      this.lastUpdated = now.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    checkServiceStatus() {
      const statuses = ['active', 'maintenance', 'active', 'active']
      const messages = [
        'All services are running normally',
        'Scheduled maintenance in progress',
        'All services are running normally',
        'All services are running normally'
      ]
      const randomIndex = Math.floor(Math.random() * statuses.length)

      this.serviceStatus = statuses[randomIndex]
      this.serviceStatusText = messages[randomIndex]
      this.updateTimestamp()

      // Reset after 5 seconds
      setTimeout(() => {
        this.serviceStatus = 'active'
        this.serviceStatusText = 'All services are running normally'
        this.updateTimestamp()
      }, 5000)
    },

    cancelSubscription(id) {
      if (!confirm('Are you sure you want to cancel this subscription?')) return

      const subscription = this.subscriptions.find(s => s.id === id)
      if (subscription) {
        subscription.status = 'cancelled'
        alert('Subscription cancelled successfully!')
      }
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.subscriptions-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 40px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.page-subtitle {
  color: #666;
  font-size: 16px;
}

/* Service Status */
.service-status-card {
  background: #fff;
  padding: 20px 24px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  margin-bottom: 32px;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.status-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a2e;
}

.status-update {
  font-size: 12px;
  color: #888;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}

.status-indicator.active .status-dot {
  background: #4caf50;
}

.status-indicator.maintenance .status-dot {
  background: #ffa500;
}

.status-indicator.offline .status-dot {
  background: #e74c3c;
}

.status-text {
  font-weight: 500;
  color: #333;
  flex: 1;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  background: #f0f0f0;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  color: #555;
  cursor: pointer;
  transition: background 0.3s;
}

.refresh-btn:hover {
  background: #e0e0e0;
}

/* Subscriptions Grid */
.subscriptions-grid {
  display: grid;
  gap: 24px;
}

.subscription-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border-left: 4px solid #4caf50;
  transition: box-shadow 0.3s;
}

.subscription-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.subscription-card.cancelled {
  border-left-color: #e74c3c;
  opacity: 0.7;
}

.subscription-card.pending {
  border-left-color: #ffa500;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.plan-info {
  flex: 1;
}

.plan-name {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 4px 0;
}

.plan-speed {
  color: #888;
  font-size: 14px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-badge.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge.cancelled {
  background: #ffebee;
  color: #c62828;
}

.plan-price {
  margin-bottom: 16px;
}

.price-amount {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a2e;
}

.price-period {
  color: #888;
  font-size: 14px;
}

.subscription-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 12px;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a2e;
  margin-top: 2px;
}

/* Progress */
.progress-section {
  margin-bottom: 16px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #555;
  margin-bottom: 4px;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: #e8e8e8;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #ff6b35;
  border-radius: 4px;
  transition: width 0.6s ease;
}

.subscription-actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e8e8e8;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.status-btn {
  background: #e3f2fd;
  color: #1976d2;
}

.status-btn:hover {
  background: #bbdefb;
}

.cancel-btn {
  background: #ffebee;
  color: #c62828;
}

.cancel-btn:hover {
  background: #ffcdd2;
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
  margin-bottom: 20px;
}

.browse-btn {
  display: inline-block;
  padding: 12px 32px;
  background: #ff6b35;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: background 0.3s;
}

.browse-btn:hover {
  background: #e85a2a;
}

/* Responsive */
@media (max-width: 768px) {
  .page-title {
    font-size: 26px;
  }

  .status-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .status-indicator {
    flex-wrap: wrap;
  }

  .subscription-details {
    grid-template-columns: 1fr 1fr;
  }

  .subscription-actions {
    flex-direction: column;
  }

  .action-btn {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .card-header {
    flex-direction: column;
    gap: 8px;
  }

  .subscription-details {
    grid-template-columns: 1fr;
  }
}
</style>
