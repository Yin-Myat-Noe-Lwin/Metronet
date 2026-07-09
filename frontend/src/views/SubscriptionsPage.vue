<template>
  <div class="subscriptions-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">My Subscriptions</h1>
        <p class="page-subtitle">Manage your active subscriptions and service status</p>
      </div>

      <!-- Service Status -->
      <div class="service-status-card">
        <div class="status-header">
          <div class="status-left">
            <span class="status-icon">📡</span>
            <h3>Service Status</h3>
          </div>
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

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading your subscriptions...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="subscriptions.length === 0" class="empty-state">
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

      <!-- Subscriptions Grid -->
      <div v-else class="subscriptions-grid">
        <div
          v-for="subscription in subscriptions"
          :key="subscription.id"
          class="subscription-card"
          :class="getStatusClass(subscription.status)"
        >
          <div class="card-header">
            <div class="plan-info">
              <h3 class="plan-name">{{ subscription.plan?.name || subscription.plan_name || 'Plan' }}</h3>
              <span class="plan-speed">{{ subscription.plan?.download_speed || subscription.speed || 'N/A' }} Mbps</span>
            </div>
            <span class="status-badge" :class="getStatusClass(subscription.status)">
              {{ getStatusText(subscription.status) }}
            </span>
          </div>

          <div class="plan-price">
            <span class="price-amount">{{ formatPrice(subscription.plan?.price || subscription.price) }}</span>
            <span class="price-period">/month</span>
          </div>

          <div class="subscription-details">
            <div class="detail-item">
              <span class="detail-label">Started</span>
              <span class="detail-value">{{ formatDate(subscription.start_date || subscription.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Next Billing</span>
              <span class="detail-value">{{ formatDate(subscription.end_date || subscription.next_billing) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Duration</span>
              <span class="detail-value">{{ subscription.duration_months || 'N/A' }} months</span>
            </div>
          </div>

          <div class="subscription-actions">
            <button @click="checkStatus(subscription.id)" class="action-btn status-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              Check Status
            </button>
            <button
              v-if="subscription.status === 0 || subscription.status === 1"
              @click="confirmCancel(subscription.id)"
              class="action-btn cancel-btn"
            >
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

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="modal-overlay" @click.self="closeCancelModal">
      <div class="modal-container">
        <div class="modal-header">
          <div class="modal-icon warning">⚠️</div>
          <h3 class="modal-title">Cancel Subscription</h3>
          <button class="modal-close" @click="closeCancelModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="alert-content">
            <p class="alert-text">Are you sure you want to cancel this subscription?</p>
            <p class="alert-subtext">This action cannot be undone.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-cancel" @click="closeCancelModal">Keep It</button>
          <button class="modal-btn btn-danger" @click="cancelSubscription" :disabled="cancelling">
            {{ cancelling ? 'Cancelling...' : 'Yes, Cancel' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Status Modal -->
    <div v-if="showStatusModal" class="modal-overlay" @click.self="closeStatusModal">
      <div class="modal-container">
        <div class="modal-header">
          <div class="modal-icon info">ℹ️</div>
          <h3 class="modal-title">Subscription Status</h3>
          <button class="modal-close" @click="closeStatusModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="status-details">
            <div class="status-item">
              <span class="status-label">Status</span>
              <span class="status-value" :class="statusModalClass">
                {{ statusModalText }}
              </span>
            </div>
            <div class="status-item" v-if="statusModalPlan">
              <span class="status-label">Plan</span>
              <span class="status-value">{{ statusModalPlan }}</span>
            </div>
            <div class="status-item" v-if="statusModalDate">
              <span class="status-label">Next Billing</span>
              <span class="status-value">{{ statusModalDate }}</span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-primary" @click="closeStatusModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
      <div class="modal-container success-modal">
        <div class="modal-header">
          <div class="modal-icon success">✅</div>
          <h3 class="modal-title">Success!</h3>
          <button class="modal-close" @click="closeSuccessModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="alert-content">
            <p class="alert-text">{{ successMessage }}</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-primary" @click="closeSuccessModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal" class="modal-overlay" @click.self="closeErrorModal">
      <div class="modal-container error-modal">
        <div class="modal-header">
          <div class="modal-icon error">❌</div>
          <h3 class="modal-title">Error</h3>
          <button class="modal-close" @click="closeErrorModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="alert-content">
            <p class="alert-text">{{ errorMessage }}</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-primary" @click="closeErrorModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" v-if="toast.show">
      <div class="toast-content" :class="toast.type">
        <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
        <span class="toast-message">{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { subscriptionsService } from '../services/api'

export default {
  name: 'SubscriptionsPage',
  data() {
    return {
      loading: false,
      cancelling: false,
      serviceStatus: 'active',
      serviceStatusText: 'All services are running normally',
      lastUpdated: '',
      subscriptions: [],
      cancelId: null,

      // Cancel Modal
      showCancelModal: false,

      // Status Modal
      showStatusModal: false,
      statusModalText: '',
      statusModalClass: '',
      statusModalPlan: '',
      statusModalDate: '',

      // Success Modal
      showSuccessModal: false,
      successMessage: '',

      // Error Modal
      showErrorModal: false,
      errorMessage: '',

      // Toast
      toast: {
        show: false,
        message: '',
        type: 'success'
      },
      toastTimeout: null
    }
  },
  mounted() {
    this.updateTimestamp()
    this.fetchSubscriptions()
  },
  methods: {
    async fetchSubscriptions() {
      this.loading = true
      try {
        const response = await subscriptionsService.viewSubscriptions()
        console.log('Subscriptions response:', response)

        // Handle different response structures
        this.subscriptions = response.data || response || []

        console.log('Mapped subscriptions:', this.subscriptions)
      } catch (error) {
        console.error('Error fetching subscriptions:', error)
        this.showErrorModalWithMessage('Failed to load subscriptions. Please try again.')
      } finally {
        this.loading = false
      }
    },

    confirmCancel(id) {
      this.cancelId = id
      this.showCancelModal = true
    },

    closeCancelModal() {
      this.showCancelModal = false
      this.cancelId = null
    },

    async cancelSubscription() {
      if (!this.cancelId) return

      this.cancelling = true

      try {
        const response = await subscriptionsService.cancelSubscription(this.cancelId)
        console.log('Cancel response:', response)

        this.closeCancelModal()
        this.showSuccessModalWithMessage('Subscription cancelled successfully!')
        await this.fetchSubscriptions()
      } catch (error) {
        console.error('Error cancelling subscription:', error)
        this.closeCancelModal()

        const errorData = error.response?.data
        const statusCode = error.response?.status

        if (statusCode === 404) {
          this.showErrorModalWithMessage('Subscription not found.')
        } else if (statusCode === 400) {
          this.showErrorModalWithMessage(errorData?.error || 'This subscription cannot be cancelled.')
        } else {
          this.showErrorModalWithMessage(errorData?.error || 'Failed to cancel subscription. Please try again.')
        }
      } finally {
        this.cancelling = false
      }
    },

    checkStatus(id) {
      const subscription = this.subscriptions.find(s => s.id === id)
      if (!subscription) {
        this.showErrorModalWithMessage('Subscription not found.')
        return
      }

      const statusText = this.getStatusText(subscription.status)
      const statusMap = {
        0: '⏳ Pending - Waiting for ISP approval',
        1: '✅ Active - Service is running normally',
        2: '❌ Cancelled - Subscription has been cancelled',
        3: '⏰ Expired - Subscription period has ended',
        4: '🚫 Suspended - Service has been suspended'
      }

      this.statusModalText = statusText
      this.statusModalClass = this.getStatusClass(subscription.status)
      this.statusModalPlan = subscription.plan?.name || subscription.plan_name || 'N/A'
      this.statusModalDate = this.formatDate(subscription.end_date || subscription.next_billing)
      this.showStatusModal = true
    },

    closeStatusModal() {
      this.showStatusModal = false
    },

    showSuccessModalWithMessage(message) {
      this.successMessage = message
      this.showSuccessModal = true
      setTimeout(() => {
        this.closeSuccessModal()
      }, 3000)
    },

    closeSuccessModal() {
      this.showSuccessModal = false
      this.successMessage = ''
    },

    showErrorModalWithMessage(message) {
      this.errorMessage = message
      this.showErrorModal = true
    },

    closeErrorModal() {
      this.showErrorModal = false
      this.errorMessage = ''
    },

    getStatusClass(status) {
      const statusMap = {
        0: 'pending',
        1: 'active',
        2: 'cancelled',
        3: 'expired',
        4: 'suspended'
      }
      return statusMap[status] || ''
    },

    getStatusText(status) {
      const statusMap = {
        0: 'Pending',
        1: 'Active',
        2: 'Cancelled',
        3: 'Expired',
        4: 'Suspended'
      }
      return statusMap[status] || 'Unknown'
    },

    formatPrice(price) {
      if (!price) return 'N/A'
      const cleanPrice = String(price).replace(/[^0-9.]/g, '')
      return `MMK ${parseFloat(cleanPrice).toLocaleString()}`
    },

    formatDate(date) {
      if (!date) return 'N/A'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      } catch (e) {
        return 'N/A'
      }
    },

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

      setTimeout(() => {
        this.serviceStatus = 'active'
        this.serviceStatusText = 'All services are running normally'
        this.updateTimestamp()
      }, 5000)
    },

    showToast(message, type = 'success') {
      if (this.toastTimeout) {
        clearTimeout(this.toastTimeout)
      }

      this.toast.message = message
      this.toast.type = type
      this.toast.show = true

      this.toastTimeout = setTimeout(() => {
        this.toast.show = false
      }, 4000)
    }
  }
}
</script>

<style scoped>
.subscriptions-page {
  min-height: 100vh;
  background: #f0f2f5;
  padding: 40px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Page Header */
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
  color: #6b7280;
  font-size: 16px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #ff6b35;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Service Status */
.service-status-card {
  background: #fff;
  padding: 20px 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  margin-bottom: 32px;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.status-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.status-icon {
  font-size: 20px;
}

.status-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0;
}

.status-update {
  font-size: 12px;
  color: #9ca3af;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-dot {
  width: 10px;
  height: 10px;
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
  background: #22c55e;
}

.status-indicator.maintenance .status-dot {
  background: #f59e0b;
}

.status-indicator.offline .status-dot {
  background: #ef4444;
}

.status-text {
  font-weight: 500;
  color: #374151;
  flex: 1;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.3s;
}

.refresh-btn:hover {
  background: #e5e7eb;
  color: #374151;
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
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border-left: 4px solid #22c55e;
  transition: box-shadow 0.3s;
}

.subscription-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.subscription-card.cancelled {
  border-left-color: #ef4444;
  opacity: 0.7;
}

.subscription-card.pending {
  border-left-color: #f59e0b;
}

.subscription-card.expired {
  border-left-color: #9ca3af;
}

.subscription-card.suspended {
  border-left-color: #ef4444;
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
  color: #9ca3af;
  font-size: 14px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

.status-badge.active {
  background: #dcfce7;
  color: #16a34a;
}

.status-badge.pending {
  background: #fef3c7;
  color: #d97706;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.expired {
  background: #f3f4f6;
  color: #6b7280;
}

.status-badge.suspended {
  background: #fee2e2;
  color: #dc2626;
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
  color: #9ca3af;
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
  font-size: 11px;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a2e;
  margin-top: 2px;
}

.subscription-actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.status-btn {
  background: #eff6ff;
  color: #2563eb;
}

.status-btn:hover {
  background: #dbeafe;
}

.cancel-btn {
  background: #fef2f2;
  color: #dc2626;
}

.cancel-btn:hover {
  background: #fee2e2;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.empty-icon {
  color: #d1d5db;
  margin-bottom: 16px;
}

.empty-state h3 {
  color: #1a1a2e;
  margin-bottom: 8px;
  font-size: 20px;
}

.empty-state p {
  color: #9ca3af;
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

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
  padding: 20px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background: #fff;
  border-radius: 16px;
  max-width: 440px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s ease;
  overflow: hidden;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 20px 24px 16px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #f3f4f6;
}

.modal-icon {
  font-size: 24px;
  margin-right: 12px;
}

.modal-icon.warning {
  color: #f59e0b;
}

.modal-icon.success {
  color: #22c55e;
}

.modal-icon.error {
  color: #ef4444;
}

.modal-icon.info {
  color: #3b82f6;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
  flex: 1;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 24px;
}

.alert-content {
  text-align: center;
}

.alert-text {
  font-size: 16px;
  color: #1a1a2e;
  line-height: 1.6;
  margin: 0;
}

.alert-subtext {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
  margin: 8px 0 0 0;
}

/* Status Details */
.status-details {
  padding: 4px 0;
}

.status-item {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f3f4f6;
}

.status-item:last-child {
  border-bottom: none;
}

.status-label {
  color: #9ca3af;
  font-size: 14px;
}

.status-value {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 14px;
}

.status-value.active {
  color: #16a34a;
}

.status-value.pending {
  color: #d97706;
}

.status-value.cancelled {
  color: #dc2626;
}

.status-value.expired {
  color: #6b7280;
}

.status-value.suspended {
  color: #dc2626;
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  gap: 12px;
  border-top: 1px solid #f3f4f6;
}

.modal-btn {
  flex: 1;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

.btn-danger {
  background: #ef4444;
  color: #fff;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn-primary {
  background: #ff6b35;
  color: #fff;
}

.btn-primary:hover {
  background: #e85a2a;
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 99999;
  animation: slideUp 0.3s ease;
}

.toast-content {
  padding: 14px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  max-width: 400px;
  min-width: 280px;
}

.toast-content.success {
  background: #1a1a2e;
  color: #fff;
}

.toast-content.error {
  background: #ef4444;
  color: #fff;
}

.toast-icon {
  font-weight: 700;
  font-size: 16px;
  flex-shrink: 0;
}

.toast-message {
  font-size: 14px;
  flex: 1;
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

  .modal-container {
    max-width: 100%;
    margin: 16px;
  }

  .modal-footer {
    flex-direction: column;
  }

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }

  .toast-content {
    max-width: 100%;
    min-width: auto;
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

  .status-item {
    flex-direction: column;
    gap: 4px;
  }
}
</style>
