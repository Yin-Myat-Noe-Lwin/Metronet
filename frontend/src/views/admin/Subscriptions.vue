<template>
  <div class="admin-subscriptions">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Subscriptions Management</h1>
      <p class="page-subtitle">View and manage all customer subscriptions</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by customer, plan or status..."
          class="search-input"
          @input="onSearch"
        >
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      <div class="filter-group">
        <select v-model="statusFilter" class="filter-select" @change="applyFilters">
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <button class="btn-refresh" @click="fetchSubscriptions">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
      <p>Loading subscriptions...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchSubscriptions" class="retry-btn">Try Again</button>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Plan</th>
            <th>Price</th>
            <th>Status</th>
            <th>Started</th>
            <th>Next Billing</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredSubscriptions.length === 0">
            <td colspan="8" class="empty-row">No subscriptions found</td>
          </tr>
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
              <span class="plan-name">{{ sub.plan?.name || 'N/A' }}</span>
              <span class="plan-speed">{{ sub.plan?.download_speed || 0 }} Mbps</span>
            </td>
            <td>
              <span class="plan-price">{{ formatPrice(sub.plan?.price) }}</span>
            </td>
            <td>
              <span class="status-badge" :class="sub.status">
                {{ sub.status }}
              </span>
            </td>
            <td>{{ formatDate(sub.created_at) }}</td>
            <td>{{ sub.next_billing ? formatDate(sub.next_billing) : 'N/A' }}</td>
            <td>
              <div class="action-buttons-group">
                <button class="action-btn view" @click="viewSubscription(sub.id)" title="View Details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
                <button v-if="sub.status === 'active'" class="action-btn cancel" @click="cancelSubscription(sub.id)" title="Cancel Subscription">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
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
          <span class="stat-item">
            <strong>Active:</strong> {{ activeCount }}
          </span>
          <span class="stat-item">
            <strong>Pending:</strong> {{ pendingCount }}
          </span>
          <span class="stat-item">
            <strong>Cancelled:</strong> {{ cancelledCount }}
          </span>
        </div>
      </div>
    </div>

    <!-- View Subscription Modal -->
    <div class="modal-overlay" v-if="showViewModal" @click.self="closeViewModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Subscription Details</h2>
          <button class="modal-close" @click="closeViewModal">✕</button>
        </div>
        <div v-if="viewingSubscription" class="subscription-detail">
          <div class="detail-grid">
            <div class="detail-section">
              <h4>Subscription Information</h4>
              <div class="detail-item">
                <span class="detail-label">ID</span>
                <span class="detail-value">#{{ String(viewingSubscription.id).padStart(4, '0') }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="status-badge" :class="viewingSubscription.status">
                  {{ viewingSubscription.status }}
                </span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Started</span>
                <span class="detail-value">{{ formatDate(viewingSubscription.created_at) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Next Billing</span>
                <span class="detail-value">{{ viewingSubscription.next_billing ? formatDate(viewingSubscription.next_billing) : 'N/A' }}</span>
              </div>
            </div>

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

            <div class="detail-section">
              <h4>Plan Information</h4>
              <div class="detail-item">
                <span class="detail-label">Plan</span>
                <span class="detail-value">{{ viewingSubscription.plan?.name || 'N/A' }}</span>
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

            <div class="detail-section" v-if="viewingSubscription.cpe_assignments && viewingSubscription.cpe_assignments.length > 0">
              <h4>CPE Devices</h4>
              <div class="cpe-list">
                <div v-for="assignment in viewingSubscription.cpe_assignments" :key="assignment.id" class="cpe-item">
                  <span class="cpe-model">{{ assignment.cpe?.model || 'N/A' }}</span>
                  <span class="cpe-type">{{ assignment.cpe?.type || 'N/A' }}</span>
                  <span class="cpe-status" :class="assignment.status">{{ assignment.status || 'active' }}</span>
                </div>
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
    <div class="modal-overlay" v-if="showCancelModal" @click.self="showCancelModal = false">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>Confirm Cancellation</h2>
          <button class="modal-close" @click="showCancelModal = false">✕</button>
        </div>
        <div class="cancel-confirm">
          <div class="cancel-icon">⚠️</div>
          <p>Are you sure you want to cancel this subscription?</p>
          <p class="cancel-warning">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-danger" @click="confirmCancel" :disabled="isCancelling">
            {{ isCancelling ? 'Cancelling...' : 'Yes, Cancel' }}
          </button>
          <button class="btn-secondary" @click="showCancelModal = false">No, Keep</button>
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
import { subscriptionsService } from '../../services/api'

export default {
  name: 'AdminSubscriptions',
  data() {
    return {
      loading: false,
      isCancelling: false,
      error: null,
      searchQuery: '',
      statusFilter: 'all',
      searchTimeout: null,
      subscriptions: [],
      showViewModal: false,
      showCancelModal: false,
      viewingSubscription: null,
      cancellingId: null,
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

      // Filter by status
      if (this.statusFilter !== 'all') {
        filtered = filtered.filter(s => s.status === this.statusFilter)
      }

      // Filter by search
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(s =>
          s.customer?.name?.toLowerCase().includes(query) ||
          s.customer?.email?.toLowerCase().includes(query) ||
          s.plan?.name?.toLowerCase().includes(query) ||
          s.status?.toLowerCase().includes(query)
        )
      }

      return filtered
    },
    activeCount() {
      return this.subscriptions.filter(s => s.status === 'active').length
    },
    pendingCount() {
      return this.subscriptions.filter(s => s.status === 'pending').length
    },
    cancelledCount() {
      return this.subscriptions.filter(s => s.status === 'cancelled').length
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
        console.log('✅ Subscriptions API Response:', response)

        // Handle different response structures
        let subsData = []
        if (response.data && Array.isArray(response.data)) {
          subsData = response.data
        } else if (Array.isArray(response)) {
          subsData = response
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          subsData = response.data.data
        }

        this.subscriptions = subsData
        console.log('✅ Loaded subscriptions:', this.subscriptions.length)

      } catch (error) {
        console.error('❌ Error fetching subscriptions:', error)

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

    onSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        // Client-side filtering handled by computed property
      }, 300)
    },

    applyFilters() {
      // Filter is handled by computed property
    },

    viewSubscription(id) {
      this.viewingSubscription = this.subscriptions.find(s => s.id === id)
      if (this.viewingSubscription) {
        this.showViewModal = true
      }
    },

    cancelSubscription(id) {
      const sub = this.subscriptions.find(s => s.id === id)
      if (sub && sub.status === 'active') {
        this.cancellingId = id
        this.showCancelModal = true
      }
    },

    async confirmCancel() {
      if (!this.cancellingId) return

      this.isCancelling = true

      try {
        await subscriptionsService.cancelSubscription(this.cancellingId)

        // Update local data
        const index = this.subscriptions.findIndex(s => s.id === this.cancellingId)
        if (index !== -1) {
          this.subscriptions[index].status = 'cancelled'
        }

        this.showCancelModal = false
        this.showToast('Subscription cancelled successfully!', 'success')

        // Refresh the list
        this.fetchSubscriptions()

      } catch (error) {
        console.error('Error cancelling subscription:', error)
        this.showToast(error.response?.data?.message || 'Failed to cancel subscription.', 'error')
      } finally {
        this.isCancelling = false
        this.cancellingId = null
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingSubscription = null
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
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
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

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 4px 0;
}

.page-subtitle {
  color: #8892a8;
  font-size: 15px;
  margin: 0;
}

.page-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-group {
  flex: 1;
  max-width: 400px;
  position: relative;
}

.search-input {
  width: 100%;
  padding: 10px 16px 10px 42px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s, box-shadow 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #8892a8;
}

.filter-group {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filter-select {
  padding: 8px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.3s;
}

.filter-select:focus {
  outline: none;
  border-color: #ff6b35;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #f0f0f0;
  color: #555;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-refresh:hover {
  background: #e0e0e0;
}

.btn-secondary {
  padding: 10px 20px;
  background: #e8ecf1;
  color: #555;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-secondary:hover {
  background: #d0d0d0;
}

.btn-danger {
  padding: 10px 20px;
  background: #e74c3c;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-danger:hover:not(:disabled) {
  background: #c0392b;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e8ecf1;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #8892a8;
}

.error-state {
  text-align: center;
  padding: 40px 20px;
}

.error-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.error-state p {
  color: #e74c3c;
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

.table-wrapper {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th {
  padding: 14px 16px;
  text-align: left;
  background: #f8f9fa;
  color: #555;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.admin-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  color: #333;
}

.admin-table tr:hover td {
  background: #fafafa;
}

.empty-row {
  text-align: center;
  color: #8892a8;
  padding: 40px !important;
}

.subscription-id {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 13px;
}

.customer-info {
  display: flex;
  flex-direction: column;
}

.customer-name {
  font-weight: 500;
  color: #1a1a2e;
}

.customer-email {
  font-size: 12px;
  color: #8892a8;
}

.plan-name {
  font-weight: 500;
  color: #1a1a2e;
  display: block;
}

.plan-speed {
  font-size: 12px;
  color: #8892a8;
}

.plan-price {
  font-weight: 600;
  color: #1a1a2e;
}

.status-badge {
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
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

.action-buttons-group {
  display: flex;
  gap: 4px;
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
  background: #e3f2fd;
  color: #1976d2;
}

.action-btn.view:hover {
  background: #bbdefb;
}

.action-btn.cancel {
  background: #ffebee;
  color: #c62828;
}

.action-btn.cancel:hover {
  background: #ffcdd2;
}

.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  flex-wrap: wrap;
  gap: 12px;
}

.summary-stats {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.stat-item {
  font-size: 14px;
  color: #555;
}

.stat-item strong {
  color: #1a1a2e;
}

/* Modal */
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
  z-index: 2000;
  animation: fadeIn 0.2s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 16px;
  padding: 32px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-lg {
  max-width: 700px;
}

.modal-sm {
  max-width: 420px;
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
  color: #1a1a2e;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #8892a8;
  cursor: pointer;
  padding: 4px 8px;
  transition: color 0.3s;
}

.modal-close:hover {
  color: #1a1a2e;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

/* Subscription Detail */
.subscription-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
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
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 4px 0;
  padding-bottom: 6px;
  border-bottom: 1px solid #f0f0f0;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 12px;
  color: #8892a8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 15px;
  font-weight: 500;
  color: #1a1a2e;
  margin-top: 2px;
}

/* CPE List */
.cpe-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cpe-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 10px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 14px;
}

.cpe-model {
  font-weight: 500;
}

.cpe-type {
  color: #666;
  font-size: 13px;
}

.cpe-status {
  padding: 1px 8px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.cpe-status.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.cpe-status.inactive {
  background: #ffebee;
  color: #c62828;
}

/* Cancel Confirm */
.cancel-confirm {
  text-align: center;
  padding: 8px 0;
}

.cancel-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.cancel-confirm p {
  color: #444;
  margin-bottom: 8px;
}

.cancel-warning {
  color: #e74c3c !important;
  font-size: 14px;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 3000;
  animation: slideUp 0.3s;
}

.toast-content {
  padding: 16px 24px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.toast-content.success {
  background: #1a1a2e;
  color: #fff;
}

.toast-content.error {
  background: #e74c3c;
  color: #fff;
}

.toast-icon {
  font-weight: 700;
  font-size: 18px;
}

.toast-message {
  font-size: 14px;
}

/* Responsive */
@media (max-width: 992px) {
  .detail-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .page-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .search-group {
    max-width: none;
  }

  .filter-group {
    flex-direction: column;
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

  .modal {
    padding: 24px;
  }

  .modal-lg {
    max-width: 95%;
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
  }
}

@media (max-width: 480px) {
  .admin-table {
    font-size: 12px;
  }

  .admin-table th,
  .admin-table td {
    padding: 6px 8px;
  }

  .page-title {
    font-size: 22px;
  }

  .modal {
    padding: 20px;
  }
}
</style>
