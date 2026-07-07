<template>
  <div class="admin-invoices">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Invoices Management</h1>
      <p class="page-subtitle">View and manage all customer invoices</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by invoice #, customer or description..."
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
          <option value="paid">Paid</option>
          <option value="pending">Pending</option>
          <option value="overdue">Overdue</option>
        </select>
        <button class="btn-refresh" @click="fetchInvoices">
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
      <p>Loading invoices...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchInvoices" class="retry-btn">Try Again</button>
    </div>

    <!-- Content -->
    <template v-else>
      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <span class="summary-label">Total Invoices</span>
          <span class="summary-value">{{ invoices.length }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Pending</span>
          <span class="summary-value">{{ pendingCount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Paid</span>
          <span class="summary-value">{{ paidCount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Total Amount</span>
          <span class="summary-value">{{ totalAmount }}</span>
        </div>
      </div>

      <!-- Table -->
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th>Due Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredInvoices.length === 0">
              <td colspan="7" class="empty-row">No invoices found</td>
            </tr>
            <tr v-for="invoice in filteredInvoices" :key="invoice.id">
              <td>
                <span class="invoice-id">#{{ String(invoice.id).padStart(4, '0') }}</span>
              </td>
              <td>
                <div class="customer-info">
                  <span class="customer-name">{{ invoice.customer?.name || 'Unknown' }}</span>
                  <span class="customer-email">{{ invoice.customer?.email || 'N/A' }}</span>
                </div>
              </td>
              <td>
                <span class="amount">{{ formatPrice(invoice.amount) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="invoice.status">
                  {{ invoice.status }}
                </span>
              </td>
              <td>{{ formatDate(invoice.created_at) }}</td>
              <td>{{ invoice.due_date ? formatDate(invoice.due_date) : 'N/A' }}</td>
              <td>
                <div class="action-buttons-group">
                  <button class="action-btn view" @click="viewInvoice(invoice.id)" title="View Details">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                  <button v-if="invoice.status === 'pending' || invoice.status === 'overdue'" class="action-btn pay" @click="payInvoice(invoice.id)" title="Mark as Paid">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- View Invoice Modal -->
    <div class="modal-overlay" v-if="showViewModal" @click.self="closeViewModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Invoice Details</h2>
          <button class="modal-close" @click="closeViewModal">✕</button>
        </div>
        <div v-if="viewingInvoice" class="invoice-detail">
          <div class="detail-header">
            <div>
              <span class="invoice-number">Invoice #{{ String(viewingInvoice.id).padStart(4, '0') }}</span>
              <span class="invoice-date">{{ formatDate(viewingInvoice.created_at) }}</span>
            </div>
            <span class="status-badge" :class="viewingInvoice.status">
              {{ viewingInvoice.status }}
            </span>
          </div>

          <div class="detail-grid">
            <div class="detail-section">
              <h4>Customer Information</h4>
              <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ viewingInvoice.customer?.name || 'Unknown' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ viewingInvoice.customer?.email || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ viewingInvoice.customer?.phone_num || 'N/A' }}</span>
              </div>
            </div>

            <div class="detail-section">
              <h4>Invoice Information</h4>
              <div class="detail-item">
                <span class="detail-label">Amount</span>
                <span class="detail-value amount">{{ formatPrice(viewingInvoice.amount) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Due Date</span>
                <span class="detail-value">{{ viewingInvoice.due_date ? formatDate(viewingInvoice.due_date) : 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Description</span>
                <span class="detail-value">{{ viewingInvoice.description || 'Internet Service' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Created</span>
                <span class="detail-value">{{ formatDate(viewingInvoice.created_at) }}</span>
              </div>
            </div>

            <div class="detail-section" v-if="viewingInvoice.subscription">
              <h4>Subscription Details</h4>
              <div class="detail-item">
                <span class="detail-label">Plan</span>
                <span class="detail-value">{{ viewingInvoice.subscription?.plan?.name || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Speed</span>
                <span class="detail-value">{{ viewingInvoice.subscription?.plan?.download_speed || 0 }} Mbps</span>
              </div>
            </div>
          </div>

          <div class="detail-actions" v-if="viewingInvoice.status === 'pending' || viewingInvoice.status === 'overdue'">
            <button class="btn-primary" @click="payInvoice(viewingInvoice.id)">Mark as Paid</button>
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
import { invoicesService } from '../../services/api'

export default {
  name: 'AdminInvoices',
  data() {
    return {
      loading: false,
      error: null,
      searchQuery: '',
      statusFilter: 'all',
      searchTimeout: null,
      invoices: [],
      showViewModal: false,
      viewingInvoice: null,
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    pendingCount() {
      return this.invoices.filter(i => i.status === 'pending' || i.status === 'overdue').length
    },
    paidCount() {
      return this.invoices.filter(i => i.status === 'paid').length
    },
    totalAmount() {
      const total = this.invoices.reduce((sum, inv) => {
        const amount = parseFloat(inv.amount) || 0
        return sum + amount
      }, 0)
      return this.formatPrice(total)
    },
    filteredInvoices() {
      let filtered = [...this.invoices]

      // Filter by status
      if (this.statusFilter !== 'all') {
        filtered = filtered.filter(inv => inv.status === this.statusFilter)
      }

      // Filter by search
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(inv =>
          String(inv.id).includes(query) ||
          inv.customer?.name?.toLowerCase().includes(query) ||
          inv.customer?.email?.toLowerCase().includes(query) ||
          inv.description?.toLowerCase().includes(query)
        )
      }

      return filtered
    }
  },
  mounted() {
    this.fetchInvoices()
  },
  methods: {
    async fetchInvoices() {
      this.loading = true
      this.error = null

      try {
        const response = await invoicesService.getAdminInvoices()
        console.log('✅ Invoices API Response:', response)

        // Handle different response structures
        let invoicesData = []
        if (response.data && Array.isArray(response.data)) {
          invoicesData = response.data
        } else if (Array.isArray(response)) {
          invoicesData = response
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          invoicesData = response.data.data
        }

        this.invoices = invoicesData
        console.log('✅ Loaded invoices:', this.invoices.length)

      } catch (error) {
        console.error('❌ Error fetching invoices:', error)

        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load invoices.'
        }
        this.invoices = []
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

    viewInvoice(id) {
      this.viewingInvoice = this.invoices.find(inv => inv.id === id)
      if (this.viewingInvoice) {
        this.showViewModal = true
      }
    },

    async payInvoice(id) {
      if (!confirm('Are you sure you want to mark this invoice as paid?')) return

      try {
        // Call your payment API endpoint
        // For now, we'll just update the status locally
        const invoice = this.invoices.find(inv => inv.id === id)
        if (invoice) {
          invoice.status = 'paid'
          this.showToast('Invoice marked as paid successfully!', 'success')
          if (this.viewingInvoice && this.viewingInvoice.id === id) {
            this.viewingInvoice.status = 'paid'
          }
        }
      } catch (error) {
        console.error('Error paying invoice:', error)
        this.showToast(error.response?.data?.message || 'Failed to mark invoice as paid.', 'error')
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingInvoice = null
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
.admin-invoices {
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
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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

.invoice-id {
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

.amount {
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

.status-badge.paid {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge.overdue {
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

.action-btn.pay {
  background: #e8f5e9;
  color: #2e7d32;
}

.action-btn.pay:hover {
  background: #c8e6c9;
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

.btn-primary {
  padding: 10px 20px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-primary:hover {
  background: #e85a2a;
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

/* Invoice Detail */
.invoice-detail {
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

.invoice-number {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.invoice-date {
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

.detail-actions {
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
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
    grid-template-columns: 1fr;
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

  .modal {
    padding: 24px;
  }

  .modal-lg {
    max-width: 95%;
  }

  .summary-grid {
    grid-template-columns: 1fr 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
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
