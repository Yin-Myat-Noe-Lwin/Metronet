<template>
  <div class="subscriptions-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">My Subscriptions</h1>
          <p class="page-subtitle">Manage your active subscriptions</p>
        </div>
        <router-link to="/plans" class="btn-primary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          New Subscription
        </router-link>
      </div>

      <!-- Stats -->
      <div class="stats-grid" v-if="!loading && subscriptions.length > 0">
        <div class="stat-card">
          <div class="stat-icon active-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ activeSubscriptions }}</div>
            <div class="stat-label">Active</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon pending-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ pendingSubscriptions }}</div>
            <div class="stat-label">Pending</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cancelled-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ cancelledSubscriptions }}</div>
            <div class="stat-label">Cancelled</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon total-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
              <line x1="8" y1="21" x2="16" y2="21"/>
              <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ subscriptions.length }}</div>
            <div class="stat-label">Total</div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading subscriptions...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="subscriptions.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
        </div>
        <h3>No Subscriptions Yet</h3>
        <p>Browse our plans and subscribe to get started.</p>
        <router-link to="/plans" class="btn-primary">Browse Plans</router-link>
      </div>

      <!-- Subscriptions List -->
      <div v-else class="subscriptions-list">
        <div
          v-for="subscription in subscriptions"
          :key="subscription.id"
          class="subscription-card"
        >
          <div class="card-top">
            <div class="plan-info">
              <div class="plan-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                  <path d="M2 17l10 5 10-5"/>
                  <path d="M2 12l10 5 10-5"/>
                </svg>
              </div>
              <div>
                <h3 class="plan-name">{{ subscription.plan?.name || 'Plan' }}</h3>
                <span class="plan-speed">{{ subscription.plan?.download_speed || 'N/A' }} Mbps</span>
              </div>
            </div>
            <span class="status-badge" :class="getStatusClass(subscription.status)">
              <span class="status-dot"></span>
              {{ getStatusText(subscription.status) }}
            </span>
          </div>

          <div class="card-middle">
            <div class="price">{{ formatPrice(subscription.plan?.price) }}</div>
            <div class="details">
              <div>
                <span class="label">Started</span>
                <span>{{ formatDate(subscription.start_date || subscription.created_at) }}</span>
              </div>
              <div>
                <span class="label">Next Billing</span>
                <span>{{ formatDate(subscription.end_date || subscription.next_billing) }}</span>
              </div>
            </div>
          </div>

          <div class="card-bottom">
            <!-- View Details -->
            <button @click="viewStatus(subscription.id)" class="btn-outline">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              View Details
            </button>

            <!-- ✅ Resubscribe button for cancelled (status 4) -->
            <button
              v-if="subscription.status === 4"
              @click="resubscribe(subscription.plan_id)"
              class="btn-success"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 4v6h-6"/>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
              </svg>
              Resubscribe
            </button>

            <!-- ✅ Cancel button for pending (0) or active (1) -->
            <button
              v-if="canCancel(subscription)"
              @click="confirmCancel(subscription.id)"
              class="btn-danger"
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

    <!-- Cancel Modal -->
    <div v-if="showCancelModal" class="modal-overlay" @click.self="closeCancelModal">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-icon warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <h3>Cancel Subscription</h3>
          <button class="modal-close" @click="closeCancelModal">×</button>
        </div>
        <div class="modal-body">
          <!-- Error Message if cannot cancel -->
          <div v-if="cancelError" class="cancel-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ cancelError }}</span>
          </div>

          <template v-else>
            <p class="cancel-warning">Are you sure you want to cancel this subscription?</p>
            <p class="text-muted">This action cannot be undone.</p>

            <div class="cancel-details" v-if="selectedSubscription">
              <div class="cancel-row">
                <span>Plan</span>
                <span><strong>{{ selectedSubscription.plan?.name || 'N/A' }}</strong></span>
              </div>
              <div class="cancel-row">
                <span>Price</span>
                <span><strong>{{ formatPrice(selectedSubscription.plan?.price) }}</strong></span>
              </div>
              <div class="cancel-row">
                <span>Status</span>
                <span><strong>{{ getStatusText(selectedSubscription.status) }}</strong></span>
              </div>
            </div>
          </template>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeCancelModal">Keep It</button>
          <button
            class="btn-danger"
            @click="cancelSubscription"
            :disabled="cancelling || !!cancelError"
          >
            {{ cancelling ? 'Cancelling...' : 'Yes, Cancel' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Status Modal -->
    <div v-if="showStatusModal" class="modal-overlay" @click.self="closeStatusModal">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="16" x2="12" y2="12"/>
              <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
          </div>
          <h3>Subscription Details</h3>
          <button class="modal-close" @click="closeStatusModal">×</button>
        </div>
        <div class="modal-body">
          <div class="status-item">
            <span class="label">Status</span>
            <span class="status-badge" :class="statusModalClass">
              <span class="status-dot"></span>
              {{ statusModalText }}
            </span>
          </div>
          <div class="status-item">
            <span class="label">Plan</span>
            <span>{{ statusModalPlan }}</span>
          </div>
          <div class="status-item">
            <span class="label">Next Billing</span>
            <span>{{ statusModalDate }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" @click="closeStatusModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast.show" class="toast" :class="toast.type">
      <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
      <span>{{ toast.message }}</span>
      <button @click="toast.show = false">×</button>
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
      subscriptions: [],
      cancelId: null,
      selectedSubscription: null,
      cancelError: null,
      showCancelModal: false,
      showStatusModal: false,
      statusModalText: '',
      statusModalClass: '',
      statusModalPlan: '',
      statusModalDate: '',
      toast: { show: false, message: '', type: 'success' },
      toastTimeout: null
    }
  },
  computed: {
    activeSubscriptions() {
      return this.subscriptions.filter(s => s.status === 1).length
    },
    pendingSubscriptions() {
      return this.subscriptions.filter(s => s.status === 0).length
    },
    cancelledSubscriptions() {
      return this.subscriptions.filter(s => s.status === 2 || s.status === 3 || s.status === 4).length
    }
  },
  mounted() {
    this.fetchSubscriptions()
  },
  methods: {
    async fetchSubscriptions() {
      this.loading = true
      try {
        const response = await subscriptionsService.viewSubscriptions()
        this.subscriptions = response.data || response || []
      } catch (error) {
        this.showToast('Failed to load subscriptions', 'error')
      } finally {
        this.loading = false
      }
    },

    // ✅ Check if subscription can be cancelled (pending or active only)
    canCancel(subscription) {
      return subscription.status === 0 || subscription.status === 1
    },

    // ✅ Confirm cancel with subscription details
    confirmCancel(id) {
      this.cancelId = id
      this.selectedSubscription = this.subscriptions.find(s => s.id === id)
      this.cancelError = null
      this.showCancelModal = true
    },

    closeCancelModal() {
      this.showCancelModal = false
      this.cancelId = null
      this.selectedSubscription = null
      this.cancelError = null
    },

    async cancelSubscription() {
      if (!this.cancelId || this.cancelError) return

      this.cancelling = true
      try {
        await subscriptionsService.cancelSubscription(this.cancelId)
        this.closeCancelModal()
        this.showToast('Subscription cancelled successfully', 'success')
        await this.fetchSubscriptions()
      } catch (error) {
        const message = error.response?.data?.error ||
                       error.response?.data?.message ||
                       'Failed to cancel subscription'
        this.cancelError = message
        this.showToast('❌ ' + message, 'error')
      } finally {
        this.cancelling = false
      }
    },

    // Resubscribe to a cancelled plan
    resubscribe(planId) {
      this.$router.push(`/plans?plan=${planId}`)
    },

    viewStatus(id) {
      const sub = this.subscriptions.find(s => s.id === id)
      if (!sub) return
      this.statusModalText = this.getStatusText(sub.status)
      this.statusModalClass = this.getStatusClass(sub.status)
      this.statusModalPlan = sub.plan?.name || 'N/A'
      this.statusModalDate = this.formatDate(sub.end_date || sub.next_billing)
      this.showStatusModal = true
    },

    closeStatusModal() {
      this.showStatusModal = false
    },

    // ✅ Status mapping - CORRECT
    getStatusClass(status) {
      const map = {
        0: 'pending',
        1: 'active',
        2: 'suspended',
        3: 'expired',
        4: 'cancelled'
      }
      return map[status] || ''
    },

    getStatusText(status) {
      const map = {
        0: 'Pending',
        1: 'Active',
        2: 'Suspended',
        3: 'Expired',
        4: 'Cancelled'
      }
      return map[status] || 'Unknown'
    },

    formatPrice(price) {
      if (!price) return 'Free'
      const num = parseFloat(String(price).replace(/[^0-9.]/g, ''))
      return `MMK ${num.toLocaleString()}`
    },

    formatDate(date) {
      if (!date) return 'N/A'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch {
        return 'N/A'
      }
    },

    showToast(message, type = 'success') {
      if (this.toastTimeout) clearTimeout(this.toastTimeout)
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
* {
  box-sizing: border-box;
}

.subscriptions-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 40px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px;
}

.page-subtitle {
  color: #64748b;
  font-size: 15px;
  margin: 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #e85a2a;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  background: transparent;
  color: #2563eb;
  border: 1px solid #2563eb;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline:hover {
  background: #eff6ff;
}

.btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ✅ New Success Button for Resubscribe */
.btn-success {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  background: #22c55e;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-success:hover {
  background: #16a34a;
  transform: translateY(-1px);
}

.btn-secondary {
  padding: 10px 24px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  background: #fff;
  padding: 16px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon.active-icon { background: #ecfdf5; color: #059669; }
.stat-icon.pending-icon { background: #fffbeb; color: #d97706; }
.stat-icon.cancelled-icon { background: #fef2f2; color: #dc2626; }
.stat-icon.total-icon { background: #eff6ff; color: #2563eb; }

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
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
}

/* Empty */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 16px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 20px;
  color: #0f172a;
  margin: 0 0 8px;
}

.empty-state p {
  color: #94a3b8;
  margin: 0 0 24px;
}

/* Subscriptions List */
.subscriptions-list {
  display: grid;
  gap: 16px;
}

.subscription-card {
  background: #fff;
  border-radius: 10px;
  padding: 20px 24px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}

.subscription-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.plan-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.plan-icon {
  width: 40px;
  height: 40px;
  background: #f1f5f9;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ff6b35;
}

.plan-name {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 2px;
}

.plan-speed {
  font-size: 13px;
  color: #94a3b8;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.status-badge.active { background: #ecfdf5; color: #059669; }
.status-badge.active .status-dot { background: #059669; }

.status-badge.pending { background: #fffbeb; color: #d97706; }
.status-badge.pending .status-dot { background: #d97706; }

.status-badge.cancelled { background: #fef2f2; color: #dc2626; }
.status-badge.cancelled .status-dot { background: #dc2626; }

.status-badge.expired { background: #f1f5f9; color: #94a3b8; }
.status-badge.expired .status-dot { background: #94a3b8; }

.status-badge.suspended { background: #fef2f2; color: #dc2626; }
.status-badge.suspended .status-dot { background: #dc2626; }

.card-middle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  padding: 16px 0;
  border-top: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
}

.price {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.details {
  display: flex;
  gap: 32px;
}

.details > div {
  display: flex;
  flex-direction: column;
}

.details .label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.details span:last-child {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
  margin-top: 2px;
}

.card-bottom {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 12px;
  max-width: 440px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  animation: slideUp 0.25s ease;
  overflow: hidden;
}

@keyframes slideUp {
  from {
    transform: translateY(16px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-icon.warning { background: #fffbeb; color: #d97706; }
.modal-icon.info { background: #eff6ff; color: #2563eb; }

.modal-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  flex: 1;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: #f1f5f9;
}

.modal-body {
  padding: 24px;
}

.modal-body p {
  margin: 0 0 4px;
  font-size: 16px;
  color: #0f172a;
}

.modal-body .text-muted {
  color: #94a3b8;
  font-size: 14px;
}

/* Cancel Modal */
.cancel-warning {
  color: #dc2626;
  font-weight: 600;
  text-align: center;
}

.cancel-error {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fef2f2;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #fecaca;
}

.cancel-error svg {
  flex-shrink: 0;
}

.cancel-details {
  background: #f8fafc;
  border-radius: 8px;
  padding: 12px 16px;
  margin-top: 16px;
}

.cancel-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid #f1f5f9;
}

.cancel-row:last-child {
  border-bottom: none;
}

.cancel-row span:first-child {
  color: #94a3b8;
  font-size: 14px;
}

.cancel-row span:last-child {
  color: #0f172a;
  font-size: 14px;
}

.cancel-row strong {
  font-weight: 600;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.status-item:last-child {
  border-bottom: none;
}

.status-item .label {
  color: #94a3b8;
  font-size: 14px;
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  gap: 12px;
  border-top: 1px solid #f1f5f9;
}

.modal-footer .btn-primary,
.modal-footer .btn-danger,
.modal-footer .btn-secondary {
  flex: 1;
  justify-content: center;
  text-align: center;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 14px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  z-index: 99999;
  min-width: 280px;
  max-width: 420px;
  animation: slideUp 0.3s ease;
}

.toast.success {
  background: #0f172a;
  color: #fff;
}

.toast.error {
  background: #dc2626;
  color: #fff;
}

.toast-icon {
  font-weight: 700;
  font-size: 16px;
  flex-shrink: 0;
}

.toast button {
  background: none;
  border: none;
  color: rgba(255,255,255,0.6);
  font-size: 20px;
  cursor: pointer;
  margin-left: auto;
  padding: 0 4px;
}

.toast button:hover {
  color: #fff;
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-primary {
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .card-middle {
    flex-direction: column;
    align-items: stretch;
  }

  .details {
    justify-content: space-between;
  }

  .card-bottom {
    flex-direction: column;
  }

  .modal {
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
    min-width: auto;
    max-width: none;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-top {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .details {
    flex-direction: column;
    gap: 8px;
  }
}
</style>
