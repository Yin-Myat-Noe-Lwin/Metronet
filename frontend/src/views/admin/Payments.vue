<template>
  <div class="admin-payments">
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Payments Management</h1>
        <p class="page-subtitle">View all customer payments</p>
      </div>
      <button class="btn-refresh" @click="fetchPayments" :disabled="loading">
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
          placeholder="Search by customer, invoice or payment method..."
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
        <div v-if="filteredPayments.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
          <h3>No Payments Found</h3>
          <p>{{ searchQuery ? 'Try adjusting your search.' : 'No payments available.' }}</p>
        </div>

        <table v-else class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Invoice</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
              <th class="actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in filteredPayments" :key="payment.id">
              <td>
                <span class="payment-id">#{{ String(payment.id).padStart(4, '0') }}</span>
              </td>
              <td>
                <div class="customer-info">
                  <span class="customer-name">{{ payment.customer?.name || payment.invoice?.subscription?.customer?.name || 'Unknown' }}</span>
                  <span class="customer-email">{{ payment.customer?.email || payment.invoice?.subscription?.customer?.email || 'N/A' }}</span>
                </div>
              </td>
              <td>
                <span class="invoice-ref">#{{ String(payment.invoice?.id || payment.invoice_id).padStart(4, '0') }}</span>
              </td>
              <td>
                <span class="amount">{{ formatPrice(payment.amount) }}</span>
              </td>
              <td>
                <span class="payment-method">{{ getPaymentMethodName(payment.method) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="getStatusClass(payment.status)">
                  <span class="status-dot"></span>
                  {{ getStatusText(payment.status) }}
                </span>
              </td>
              <td>{{ formatDate(payment.created_at) }}</td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" @click="viewPayment(payment.id)" title="View Details">
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
              <strong>Total:</strong> {{ payments.length }}
            </span>
            <span class="stat-item completed">
              <span class="dot green"></span>
              <strong>Completed:</strong> {{ completedCount }}
            </span>
            <span class="stat-item pending">
              <span class="dot orange"></span>
              <strong>Pending:</strong> {{ pendingCount }}
            </span>
            <span class="stat-item failed">
              <span class="dot red"></span>
              <strong>Failed:</strong> {{ failedCount }}
            </span>
          </div>
        </div>
      </div>
    </template>

    <!-- View Payment Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click.self="closeViewModal">
      <div class="modal modal-view">
        <div class="modal-header">
          <h2>Payment Details</h2>
          <button class="modal-close" @click="closeViewModal">×</button>
        </div>
        <div v-if="viewingPayment" class="payment-detail">
          <!-- Status Bar -->
          <div class="detail-status-bar">
            <span class="status-badge large" :class="getStatusClass(viewingPayment.status)">
              <span class="status-dot"></span>
              {{ getStatusText(viewingPayment.status) }}
            </span>
            <span class="detail-id">#{{ String(viewingPayment.id).padStart(4, '0') }}</span>
          </div>

          <div class="detail-grid">
            <!-- Customer Info -->
            <div class="detail-section">
              <h4>Customer Information</h4>
              <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ viewingPayment.customer?.name || viewingPayment.invoice?.subscription?.customer?.name || 'Unknown' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ viewingPayment.customer?.email || viewingPayment.invoice?.subscription?.customer?.email || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ viewingPayment.customer?.phone_num || viewingPayment.invoice?.subscription?.customer?.phone_num || 'N/A' }}</span>
              </div>
            </div>

            <!-- Payment Info -->
            <div class="detail-section">
              <h4>Payment Information</h4>
              <div class="detail-item">
                <span class="detail-label">Amount</span>
                <span class="detail-value amount">{{ formatPrice(viewingPayment.amount) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Method</span>
                <span class="detail-value">{{ getPaymentMethodName(viewingPayment.method) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Transaction Ref</span>
                <span class="detail-value">{{ viewingPayment.transaction_ref || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Paid At</span>
                <span class="detail-value">{{ viewingPayment.paid_at ? formatDate(viewingPayment.paid_at) : 'N/A' }}</span>
              </div>
            </div>

            <!-- Invoice Info -->
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
                <span class="detail-value">{{ viewingPayment.invoice?.due_date ? formatDate(viewingPayment.invoice.due_date) : 'N/A' }}</span>
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
    <div v-if="toast.show" class="toast" :class="toast.type">
      <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
      <span class="toast-message">{{ toast.message }}</span>
      <button class="toast-close" @click="toast.show = false">×</button>
    </div>
  </div>
</template>

<script>
import { paymentService } from '../../services/api'

export default {
  name: 'AdminPayments',
  data() {
    return {
      loading: false,
      error: null,
      searchQuery: '',
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: '1', label: 'Completed' },
        { key: '0', label: 'Pending' },
        { key: '2', label: 'Failed' }
      ],
      payments: [],
      showViewModal: false,
      viewingPayment: null,
      paymentMethods: [],
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    completedCount() {
      return this.payments.filter(p => p.status === 1).length
    },
    pendingCount() {
      return this.payments.filter(p => p.status === 0).length
    },
    failedCount() {
      return this.payments.filter(p => p.status === 2).length
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

      if (this.activeFilter !== 'all') {
        const statusValue = parseInt(this.activeFilter)
        filtered = filtered.filter(p => p.status === statusValue)
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(p =>
          p.customer?.name?.toLowerCase().includes(query) ||
          p.customer?.email?.toLowerCase().includes(query) ||
          p.invoice?.subscription?.customer?.name?.toLowerCase().includes(query) ||
          String(p.id).includes(query) ||
          String(p.invoice_id).includes(query)
        )
      }

      return filtered
    }
  },
  mounted() {
    this.fetchPayments()
    this.fetchPaymentMethods()
  },
  methods: {
    async fetchPayments() {
      this.loading = true
      this.error = null

      try {
        const response = await paymentService.getAdminPayments()
        let paymentsData = []

        if (response.data && Array.isArray(response.data)) {
          paymentsData = response.data
        } else if (Array.isArray(response)) {
          paymentsData = response
        }

        this.payments = paymentsData
      } catch (error) {
        console.error('Error fetching payments:', error)
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

    async fetchPaymentMethods() {
      try {
        const response = await paymentService.getPaymentMethods()
        this.paymentMethods = response.data || response || []
      } catch (error) {
        console.error('Error fetching payment methods:', error)
      }
    },

    getFilterCount(key) {
      if (key === 'all') return this.payments.length
      const statusValue = parseInt(key)
      return this.payments.filter(p => p.status === statusValue).length
    },

    getPaymentMethodName(methodId) {
      if (!methodId) return 'N/A'
      const method = this.paymentMethods.find(m => m.id === methodId)
      return method?.name || 'N/A'
    },

    getStatusText(status) {
      const map = { 0: 'Pending', 1: 'Completed', 2: 'Failed' }
      return map[status] || 'Unknown'
    },

    getStatusClass(status) {
      const map = { 0: 'pending', 1: 'completed', 2: 'failed' }
      return map[status] || ''
    },

    viewPayment(id) {
      const payment = this.payments.find(p => p.id === id)
      if (payment) {
        this.viewingPayment = payment
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
.admin-payments {
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

/* ===== SUMMARY ===== */
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
  border: 1px solid #e2e8f0;
}

.summary-label {
  display: block;
  font-size: 13px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
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
  width: 60px;
  text-align: center;
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

.status-badge.completed { background: #dcfce7; color: #16a34a; }
.status-badge.completed .status-dot { background: #16a34a; }

.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.pending .status-dot { background: #d97706; }

.status-badge.failed { background: #fee2e2; color: #dc2626; }
.status-badge.failed .status-dot { background: #dc2626; }

.status-badge.large {
  padding: 6px 16px;
  font-size: 14px;
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

.detail-value.amount {
  color: #ff6b35;
  font-weight: 700;
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
    grid-template-columns: 1fr;
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

  .modal-view {
    padding: 24px;
  }

  .summary-grid {
    grid-template-columns: 1fr 1fr;
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

  .modal-view {
    padding: 16px;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
