<template>
  <div class="admin-payments">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Payments Management</h1>
      <p class="page-subtitle">View all customer payments</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by customer, invoice or payment method..."
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
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
        <button class="btn-refresh" @click="fetchPayments">
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
      <p>Loading payments...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchPayments" class="retry-btn">Try Again</button>
    </div>

    <!-- Content -->
    <template v-else>
      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <span class="summary-label">Total Payments</span>
          <span class="summary-value">{{ payments.length }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Total Amount</span>
          <span class="summary-value">{{ totalAmount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Completed</span>
          <span class="summary-value">{{ completedCount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Pending</span>
          <span class="summary-value">{{ pendingCount }}</span>
        </div>
      </div>

      <!-- Table -->
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Invoice</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredPayments.length === 0">
              <td colspan="8" class="empty-row">No payments found</td>
            </tr>
            <tr v-for="payment in filteredPayments" :key="payment.id">
              <td>
                <span class="payment-id">#{{ String(payment.id).padStart(4, '0') }}</span>
              </td>
              <td>
                <div class="customer-info">
                  <span class="customer-name">{{ payment.customer?.name || 'Unknown' }}</span>
                  <span class="customer-email">{{ payment.customer?.email || 'N/A' }}</span>
                </div>
              </td>
              <td>
                <span class="invoice-ref">#{{ String(payment.invoice?.id || payment.invoice_id).padStart(4, '0') }}</span>
              </td>
              <td>
                <span class="amount">{{ formatPrice(payment.amount) }}</span>
              </td>
              <td>
                <span class="payment-method">{{ payment.method || 'N/A' }}</span>
              </td>
              <td>
                <span class="status-badge" :class="payment.status">
                  {{ payment.status }}
                </span>
              </td>
              <td>{{ formatDate(payment.created_at) }}</td>
              <td>
                <button
                  class="action-btn view"
                  @click="viewPayment(payment.id)"
                  title="View Details"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- View Payment Modal -->
    <div class="modal-overlay" v-if="showViewModal" @click.self="closeViewModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Payment Details</h2>
          <button class="modal-close" @click="closeViewModal">✕</button>
        </div>
        <div v-if="viewingPayment" class="payment-detail">
          <div class="detail-header">
            <div>
              <span class="payment-title">Payment #{{ String(viewingPayment.id).padStart(4, '0') }}</span>
              <span class="payment-date">{{ formatDate(viewingPayment.created_at) }}</span>
            </div>
            <span class="status-badge" :class="viewingPayment.status">
              {{ viewingPayment.status }}
            </span>
          </div>

          <div class="detail-grid">
            <div class="detail-section">
              <h4>Customer Information</h4>
              <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ viewingPayment.customer?.name || 'Unknown' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ viewingPayment.customer?.email || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ viewingPayment.customer?.phone_num || 'N/A' }}</span>
              </div>
            </div>

            <div class="detail-section">
              <h4>Payment Information</h4>
              <div class="detail-item">
                <span class="detail-label">Amount</span>
                <span class="detail-value amount">{{ formatPrice(viewingPayment.amount) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Method</span>
                <span class="detail-value">{{ viewingPayment.method || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                  <span class="status-badge" :class="viewingPayment.status">
                    {{ viewingPayment.status }}
                  </span>
                </span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">{{ viewingPayment.transaction_id || 'N/A' }}</span>
              </div>
            </div>

            <div class="detail-section" v-if="viewingPayment.invoice">
              <h4>Invoice Details</h4>
              <div class="detail-item">
                <span class="detail-label">Invoice #</span>
                <span class="detail-value">#{{ String(viewingPayment.invoice?.id || viewingPayment.invoice_id).padStart(4, '0') }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Plan</span>
                <span class="detail-value">{{ viewingPayment.invoice?.subscription?.plan?.name || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Due Date</span>
                <span class="detail-value">{{ formatDate(viewingPayment.invoice?.due_date) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeViewModal">Close</button>
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
import axios from 'axios'

export default {
  name: 'AdminPayments',
  data() {
    return {
      loading: false,
      error: null,
      searchQuery: '',
      statusFilter: 'all',
      searchTimeout: null,
      payments: [],
      showViewModal: false,
      viewingPayment: null,
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    completedCount() {
      return this.payments.filter(p => p.status === 'completed').length
    },
    pendingCount() {
      return this.payments.filter(p => p.status === 'pending').length
    },
    totalAmount() {
      const total = this.payments.reduce((sum, p) => {
        const amount = parseFloat(p.amount) || 0
        return sum + amount
      }, 0)
      return this.formatPrice(total)
    },
    filteredPayments() {
      let filtered = [...this.payments]

      if (this.statusFilter !== 'all') {
        filtered = filtered.filter(p => p.status === this.statusFilter)
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(p =>
          p.customer?.name?.toLowerCase().includes(query) ||
          p.customer?.email?.toLowerCase().includes(query) ||
          p.method?.toLowerCase().includes(query) ||
          p.status?.toLowerCase().includes(query) ||
          String(p.id).includes(query)
        )
      }

      return filtered
    }
  },
  mounted() {
    this.fetchPayments()
  },
  methods: {
    async fetchPayments() {
      this.loading = true
      this.error = null

      try {
        const token = localStorage.getItem('authToken')
        const response = await axios.get('/api/admin/payments', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        console.log('✅ Payments API Response:', response)

        let paymentsData = []
        if (response.data.data && Array.isArray(response.data.data)) {
          paymentsData = response.data.data
        } else if (response.data && Array.isArray(response.data)) {
          paymentsData = response.data
        }

        this.payments = paymentsData
        console.log('✅ Loaded payments:', this.payments.length)

      } catch (error) {
        console.error('❌ Error fetching payments:', error)

        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load payments.'
        }
        this.payments = []
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

    viewPayment(id) {
      this.viewingPayment = this.payments.find(p => p.id === id)
      if (this.viewingPayment) {
        this.showViewModal = true
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingPayment = null
    },

    formatPrice(price) {
      if (!price) return '0 MMK'
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
.admin-payments {
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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.summary-label {
  display: block;
  font-size: 13px;
  color: #8892a8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a2e;
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

.payment-id {
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

.invoice-ref {
  font-weight: 500;
  color: #1976d2;
}

.amount {
  font-weight: 600;
  color: #1a1a2e;
}

.payment-method {
  font-size: 13px;
  color: #555;
}

.status-badge {
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.completed {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge.failed {
  background: #ffebee;
  color: #c62828;
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
  background: #e3f2fd;
  color: #1976d2;
}

.action-btn:hover {
  background: #bbdefb;
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

/* Payment Detail */
.payment-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.payment-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.payment-date {
  font-size: 14px;
  color: #8892a8;
  margin-left: 12px;
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

.detail-value.amount {
  color: #ff6b35;
  font-size: 18px;
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

  .summary-grid {
    grid-template-columns: 1fr 1fr;
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

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
