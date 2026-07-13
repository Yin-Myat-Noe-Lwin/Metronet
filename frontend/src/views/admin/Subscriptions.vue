<template>
  <div class="admin-subscriptions">
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Subscriptions Management</h1>
        <p class="page-subtitle">View and manage all customer subscriptions</p>
      </div>
      <button class="btn-refresh" @click="fetchSubscriptions" :disabled="loading">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 4 23 10 17 10"/>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        {{ loading ? 'Loading...' : 'Refresh' }}
      </button>
    </div>

    <!-- Search & Filters -->
    <div class="filters-bar">
      <div class="search-group">
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by customer, plan or ID..."
          class="search-input"
        >
      </div>
      <div class="filter-group">
        <button
          v-for="filter in filters"
          :key="filter.key"
          class="filter-btn"
          :class="{ active: activeFilter === filter.key }"
          @click="activeFilter = filter.key"
        >
          {{ filter.label }}
          <span class="count" v-if="getFilterCount(filter.key) > 0">
            {{ getFilterCount(filter.key) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading subscriptions...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchSubscriptions" class="retry-btn">Try Again</button>
    </div>

    <!-- Subscriptions Table -->
    <div v-else class="table-wrapper">
      <div v-if="filteredSubscriptions.length === 0" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5">
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
          <line x1="8" y1="21" x2="16" y2="21"/>
          <line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
        <h3>No Subscriptions Found</h3>
        <p>{{ searchQuery ? 'Try adjusting your search.' : 'No subscriptions available.' }}</p>
      </div>

      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Plan</th>
            <th>Price</th>
            <th>Status</th>
            <th>Started</th>
            <th>Next Billing</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sub in filteredSubscriptions" :key="sub.id">
            <td>
              <span class="subscription-id">#{{ String(sub.id).padStart(4, '0') }}</span>
            </td>
            <td>
              <div class="customer-info">
                <span class="customer-name">{{ sub.customer?.name || 'Unknown' }}</span>
                <span class="customer-email">{{ sub.customer?.email || 'N/A' }}</span>
              </div>
            </td>
            <td>
              <div class="plan-info">
                <span class="plan-name">{{ sub.plan?.name || 'N/A' }}</span>
                <span class="plan-speed">{{ sub.plan?.download_speed || 0 }} Mbps</span>
              </div>
            </td>
            <td>
              <span class="plan-price">{{ formatPrice(sub.plan?.price) }}</span>
            </td>
            <td>
              <span class="status-badge" :class="getStatusClass(sub.status)">
                <span class="status-dot"></span>
                {{ getStatusText(sub.status) }}
              </span>
            </td>
            <td>{{ formatDate(sub.created_at) }}</td>
            <td>{{ sub.end_date ? formatDate(sub.end_date) : 'N/A' }}</td>
            <td>
              <div class="action-buttons">
                <button class="action-btn view" @click="viewSubscription(sub.id)" title="View Details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Summary -->
      <div class="table-footer">
        <div class="summary-stats">
          <span class="stat-item">
            <strong>Total:</strong> {{ subscriptions.length }}
          </span>
          <span class="stat-item active">
            <span class="dot green"></span>
            <strong>Active:</strong> {{ activeCount }}
          </span>
          <span class="stat-item pending">
            <span class="dot orange"></span>
            <strong>Pending:</strong> {{ pendingCount }}
          </span>
          <span class="stat-item cancelled">
            <span class="dot red"></span>
            <strong>Cancelled:</strong> {{ cancelledCount }}
          </span>
        </div>
      </div>
    </div>

    <!-- View Subscription Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click.self="closeViewModal">
      <div class="modal modal-view">
        <div class="modal-header">
          <h2>Subscription Details</h2>
          <button class="modal-close" @click="closeViewModal">×</button>
        </div>
        <div v-if="viewingSubscription" class="subscription-detail">
          <!-- Status Bar -->
          <div class="detail-status-bar">
            <span class="status-badge large" :class="getStatusClass(viewingSubscription.status)">
              <span class="status-dot"></span>
              {{ getStatusText(viewingSubscription.status) }}
            </span>
            <span class="detail-id">#{{ String(viewingSubscription.id).padStart(4, '0') }}</span>
          </div>

          <div class="detail-grid">
            <!-- Subscription Info -->
            <div class="detail-section">
              <h4>Subscription Information</h4>
              <div class="detail-item">
                <span class="detail-label">Started</span>
                <span class="detail-value">{{ formatDate(viewingSubscription.created_at) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Next Billing</span>
                <span class="detail-value">{{ viewingSubscription.end_date ? formatDate(viewingSubscription.end_date) : 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Duration</span>
                <span class="detail-value">{{ viewingSubscription.duration_months || 1 }} month(s)</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Auto Renew</span>
                <span class="detail-value">{{ viewingSubscription.auto_renew ? 'Yes' : 'No' }}</span>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="detail-section">
              <h4>Customer Information</h4>
              <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ viewingSubscription.customer?.name || 'Unknown' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ viewingSubscription.customer?.email || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ viewingSubscription.customer?.phone_num || 'N/A' }}</span>
              </div>
            </div>

            <!-- Plan Info -->
            <div class="detail-section">
              <h4>Plan Information</h4>
              <div class="detail-item">
                <span class="detail-label">Plan</span>
                <span class="detail-value plan-name">{{ viewingSubscription.plan?.name || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Price</span>
                <span class="detail-value">{{ formatPrice(viewingSubscription.plan?.price) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Download</span>
                <span class="detail-value">{{ viewingSubscription.plan?.download_speed || 0 }} Mbps</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Upload</span>
                <span class="detail-value">{{ viewingSubscription.plan?.upload_speed || 0 }} Mbps</span>
              </div>
            </div>

            <!-- CPE Devices -->
          <div class="detail-section">
            <h4>CPE Devices</h4>
            <div v-if="viewingSubscription.cpe_assignments && viewingSubscription.cpe_assignments.length > 0" class="cpe-list">
              <div v-for="assignment in viewingSubscription.cpe_assignments" :key="assignment.id" class="cpe-item">
                <span class="cpe-icon">📡</span>
                <span class="cpe-model">{{ assignment.cpe?.serial_number || 'N/A' }}</span>
                <span class="cpe-mac">{{ assignment.cpe?.mac_address || 'N/A' }}</span>
                <span class="cpe-status" :class="assignment.status === 1 ? 'active' : 'inactive'">
                  {{ assignment.status === 1 ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
            <div v-else class="cpe-empty">
              <span>No CPE devices assigned</span>
            </div>
          </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeViewModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="modal-overlay" @click.self="closeCancelModal">
      <div class="modal modal-cancel">
        <div class="cancel-icon-wrapper">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <h3 class="cancel-title">Cancel Subscription?</h3>
        <p class="cancel-message">
          Are you sure you want to cancel <strong>{{ cancelPlanName }}</strong> for <strong>{{ cancelCustomerName }}</strong>?
        </p>
        <p class="cancel-warning">This action cannot be undone.</p>
        <div class="cancel-actions">
          <button class="btn-secondary" @click="closeCancelModal">No, Keep</button>
          <button class="btn-danger" @click="confirmCancel" :disabled="isCancelling">
            {{ isCancelling ? 'Cancelling...' : 'Yes, Cancel' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast.show" class="toast" :class="toast.type">
      <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
      <span class="toast-message">{{ toast.message }}</span>
      <button class="toast-close" @click="toast.show = false">×</button>
    </div>
  </div>
</template>

<script>
import { subscriptionsService } from '../../services/api'

export default {
  name: 'AdminSubscriptions',
  data() {
    return {
      loading: false,
      isCancelling: false,
      error: null,
      searchQuery: '',
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'active', label: 'Active' },
        { key: 'pending', label: 'Pending' },
        { key: 'cancelled', label: 'Cancelled' }
      ],
      subscriptions: [],
      showViewModal: false,
      showCancelModal: false,
      viewingSubscription: null,
      cancelId: null,
      cancelPlanName: '',
      cancelCustomerName: '',
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    filteredSubscriptions() {
      let filtered = [...this.subscriptions]

      if (this.activeFilter === 'active') {
        filtered = filtered.filter(s => s.status === 1)
      } else if (this.activeFilter === 'pending') {
        filtered = filtered.filter(s => s.status === 0)
      } else if (this.activeFilter === 'cancelled') {
        filtered = filtered.filter(s => s.status === 4)
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(s =>
          s.customer?.name?.toLowerCase().includes(query) ||
          s.customer?.email?.toLowerCase().includes(query) ||
          s.plan?.name?.toLowerCase().includes(query) ||
          String(s.id).includes(query)
        )
      }

      return filtered
    },
    activeCount() {
      return this.subscriptions.filter(s => s.status === 1).length
    },
    pendingCount() {
      return this.subscriptions.filter(s => s.status === 0).length
    },
    cancelledCount() {
      return this.subscriptions.filter(s => s.status === 4).length
    }
  },
  mounted() {
    this.fetchSubscriptions()
  },
  methods: {
    async fetchSubscriptions() {
      this.loading = true
      this.error = null

      try {
        const response = await subscriptionsService.getSubscriptions()
        let subsData = []

        if (response.data && Array.isArray(response.data)) {
          subsData = response.data
        } else if (Array.isArray(response)) {
          subsData = response
        }

        this.subscriptions = subsData
      } catch (error) {
        console.error('Error fetching subscriptions:', error)
        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load subscriptions.'
        }
        this.subscriptions = []
      } finally {
        this.loading = false
      }
    },

    getFilterCount(key) {
      if (key === 'all') return this.subscriptions.length
      if (key === 'active') return this.subscriptions.filter(s => s.status === 1).length
      if (key === 'pending') return this.subscriptions.filter(s => s.status === 0).length
      if (key === 'cancelled') return this.subscriptions.filter(s => s.status === 4).length
      return 0
    },

    getStatusText(status) {
      const map = { 0: 'Pending', 1: 'Active', 2: 'Suspended', 3: 'Expired', 4: 'Cancelled' }
      return map[status] || 'Unknown'
    },

    getStatusClass(status) {
      const map = { 0: 'pending', 1: 'active', 2: 'suspended', 3: 'expired', 4: 'cancelled' }
      return map[status] || ''
    },

    viewSubscription(id) {
      const sub = this.subscriptions.find(s => s.id === id)
      if (sub) {
        this.viewingSubscription = sub
        this.showViewModal = true
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingSubscription = null
    },

    confirmCancel(sub) {
      this.cancelId = sub.id
      this.cancelPlanName = sub.plan?.name || 'this plan'
      this.cancelCustomerName = sub.customer?.name || 'this customer'
      this.showCancelModal = true
    },

    closeCancelModal() {
      this.showCancelModal = false
      this.cancelId = null
      this.cancelPlanName = ''
      this.cancelCustomerName = ''
      this.isCancelling = false
    },

    async confirmCancel() {
      if (!this.cancelId) return

      this.isCancelling = true
      try {
        await subscriptionsService.cancelSubscription(this.cancelId)

        // Update local state
        const index = this.subscriptions.findIndex(s => s.id === this.cancelId)
        if (index !== -1) {
          this.subscriptions[index].status = 4
        }

        this.closeCancelModal()
        this.showToast('Subscription cancelled successfully!', 'success')
        await this.fetchSubscriptions()
      } catch (error) {
        console.error('Error cancelling subscription:', error)
        this.showToast(error.response?.data?.message || 'Failed to cancel subscription.', 'error')
        this.closeCancelModal()
      } finally {
        this.isCancelling = false
      }
    },

    formatPrice(price) {
      if (!price) return 'N/A'
      return new Intl.NumberFormat('my-MM', {
        style: 'currency',
        currency: 'MMK',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(price)
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
      this.toast.message = message
      this.toast.type = type
      this.toast.show = true

      setTimeout(() => {
        this.toast.show = false
      }, 4000)
    }
  }
}
</script>

<style scoped>
.admin-subscriptions {
  padding: 20px 0;
  max-width: 1200px;
}

/* ===== PAGE HEADER ===== */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
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
  color: #94a3b8;
  font-size: 15px;
  margin: 0;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-refresh:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ===== FILTERS BAR ===== */
.filters-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  background: #fff;
  padding: 12px 20px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.search-group {
  flex: 1;
  max-width: 360px;
  position: relative;
}

.search-input {
  width: 100%;
  padding: 9px 16px 9px 40px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
  background: #f8fafc;
}

.search-input:focus {
  outline: none;
  border-color: #ff6b35;
  background: #fff;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.filter-group {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 6px 16px;
  border: none;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  background: transparent;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.filter-btn:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.filter-btn.active {
  background: #ff6b35;
  color: #fff;
}

.filter-btn .count {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  border-radius: 50px;
  padding: 0 8px;
  font-size: 11px;
  font-weight: 700;
}

.filter-btn:not(.active) .count {
  background: #f1f5f9;
  color: #94a3b8;
}

/* ===== TABLE ===== */
.table-wrapper {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table thead {
  background: #f8fafc;
}

.admin-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.admin-table td {
  padding: 12px 16px;
  border-top: 1px solid #f1f5f9;
  font-size: 14px;
  color: #0f172a;
  vertical-align: middle;
}

.admin-table tbody tr:hover {
  background: #f8fafc;
}

.actions-col {
  width: 80px;
  text-align: center;
}

/* ===== SUBSCRIPTION ID ===== */
.subscription-id {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
}

/* ===== CUSTOMER INFO ===== */
.customer-info {
  display: flex;
  flex-direction: column;
}

.customer-name {
  font-weight: 500;
  color: #0f172a;
}

.customer-email {
  font-size: 12px;
  color: #94a3b8;
}

/* ===== PLAN INFO ===== */
.plan-info {
  display: flex;
  flex-direction: column;
}

.plan-name {
  font-weight: 500;
  color: #0f172a;
}

.plan-speed {
  font-size: 12px;
  color: #94a3b8;
}

.plan-price {
  font-weight: 600;
  color: #0f172a;
}

/* ===== STATUS BADGE ===== */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-badge.active { background: #dcfce7; color: #16a34a; }
.status-badge.active .status-dot { background: #16a34a; }

.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.pending .status-dot { background: #d97706; }

.status-badge.cancelled { background: #fee2e2; color: #dc2626; }
.status-badge.cancelled .status-dot { background: #dc2626; }

.status-badge.suspended { background: #fef3c7; color: #d97706; }
.status-badge.suspended .status-dot { background: #d97706; }

.status-badge.expired { background: #f1f5f9; color: #94a3b8; }
.status-badge.expired .status-dot { background: #94a3b8; }

.status-badge.large {
  padding: 6px 16px;
  font-size: 14px;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s;
}

.action-btn.view {
  background: #eef2ff;
  color: #4f46e5;
}

.action-btn.view:hover {
  background: #c7d2fe;
}

.action-btn.cancel {
  background: #fef2f2;
  color: #dc2626;
}

.action-btn.cancel:hover {
  background: #fecaca;
}

/* ===== TABLE FOOTER ===== */
.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 12px;
}

.summary-stats {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: #64748b;
}

.stat-item .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.stat-item .dot.green { background: #16a34a; }
.stat-item .dot.orange { background: #d97706; }
.stat-item .dot.red { background: #dc2626; }

.stat-item strong {
  color: #0f172a;
}

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state svg {
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 20px;
  color: #0f172a;
  margin: 0 0 8px;
}

.empty-state p {
  color: #94a3b8;
  margin: 0;
}

/* ===== MODALS ===== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 16px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-view {
  max-width: 700px;
  width: 90%;
  padding: 32px;
}

.modal-cancel {
  max-width: 420px;
  width: 90%;
  padding: 40px 32px;
  text-align: center;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px 8px;
  transition: color 0.3s;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

/* ===== DETAIL MODAL ===== */
.detail-status-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.detail-id {
  font-size: 14px;
  color: #94a3b8;
  font-weight: 500;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-section h4 {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 4px;
  padding-bottom: 6px;
  border-bottom: 1px solid #f1f5f9;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
}

.detail-value.plan-name {
  color: #ff6b35;
}

/* ===== CPE LIST ===== */
.cpe-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cpe-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 13px;
}

.cpe-icon {
  font-size: 16px;
}

.cpe-model {
  font-weight: 500;
  flex: 1;
}

.cpe-mac {
  color: #94a3b8;
  font-size: 12px;
}

.cpe-status {
  padding: 1px 8px;
  border-radius: 50px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.cpe-status.active {
  background: #dcfce7;
  color: #16a34a;
}

.cpe-status.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.cpe-empty {
  color: #94a3b8;
  font-size: 13px;
  padding: 4px 0;
}

/* ===== CANCEL MODAL ===== */
.cancel-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.cancel-icon-wrapper svg {
  background: #fef2f2;
  padding: 12px;
  border-radius: 50%;
}

.cancel-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px;
}

.cancel-message {
  color: #475569;
  font-size: 15px;
  line-height: 1.6;
  margin: 0 0 4px;
}

.cancel-warning {
  color: #dc2626;
  font-size: 14px;
  margin: 0 0 24px;
}

.cancel-actions {
  display: flex;
  gap: 12px;
}

/* ===== BUTTONS ===== */
.btn-secondary {
  padding: 10px 24px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-danger {
  padding: 10px 24px;
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  flex: 1;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ===== LOADING ===== */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #e2e8f0;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #94a3b8;
}

/* ===== ERROR ===== */
.error-state {
  text-align: center;
  padding: 40px 20px;
}

.error-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.error-state p {
  color: #dc2626;
  margin-bottom: 12px;
}

.retry-btn {
  padding: 8px 24px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.3s;
}

.retry-btn:hover {
  background: #e85a2a;
}

/* ===== TOAST ===== */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  padding: 14px 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 3000;
  animation: slideUp 0.3s ease;
  min-width: 280px;
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
  font-size: 18px;
}

.toast-message {
  font-size: 14px;
}

.toast-close {
  background: none;
  border: none;
  color: rgba(255,255,255,0.6);
  font-size: 20px;
  cursor: pointer;
  margin-left: auto;
  padding: 0 4px;
}

.toast-close:hover {
  color: #fff;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .detail-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-group {
    max-width: none;
  }

  .admin-table {
    font-size: 13px;
  }

  .admin-table th,
  .admin-table td {
    padding: 8px 10px;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal-view,
  .modal-cancel {
    padding: 24px;
  }

  .modal-cancel {
    max-width: 95%;
  }

  .cancel-actions {
    flex-direction: column;
  }

  .table-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .summary-stats {
    gap: 12px;
  }

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 22px;
  }

  .admin-table {
    font-size: 12px;
  }

  .admin-table th,
  .admin-table td {
    padding: 6px 8px;
  }

  .modal-view,
  .modal-cancel {
    padding: 16px;
  }
}
</style>
