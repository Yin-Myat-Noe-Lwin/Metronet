<template>
  <div class="admin-customers">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Customers Management</h1>
      <p class="page-subtitle">View and manage all registered customers</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by name, email or phone..."
          class="search-input"
          @input="onSearch"
        >
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      <button class="btn-refresh" @click="fetchCustomers">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 4 23 10 17 10"/>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading customers...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchCustomers" class="retry-btn">Try Again</button>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredCustomers.length === 0">
            <td colspan="6" class="empty-row">No customers found</td>
          </tr>
          <tr v-for="customer in filteredCustomers" :key="customer.id">
            <td>
              <span class="customer-id">#{{ String(customer.id).padStart(4, '0') }}</span>
            </td>
            <td>
              <div class="customer-info">
                <span class="customer-name">{{ customer.name }}</span>
                <span class="customer-joined">Joined {{ formatDate(customer.created_at) }}</span>
              </div>
            </td>
            <td class="customer-email">{{ customer.email }}</td>
            <td>{{ customer.phone_num || customer.phone || 'N/A' }}</td>
            <td>
              <span class="status-badge" :class="customer.status === 1 ? 'active' : 'inactive'">
                {{ customer.status === 1 ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="action-buttons-group">
                <button class="action-btn view" @click="viewCustomer(customer.id)" title="View Details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
                <button class="action-btn delete" @click="deleteCustomer(customer.id)" title="Deactivate Customer">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.total > 0">
        <div class="pagination-info">
          Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
          {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
          of {{ pagination.total }} customers
        </div>
        <div class="pagination-controls">
          <button
            class="page-btn"
            :disabled="pagination.current_page === 1"
            @click="changePage(pagination.current_page - 1)"
          >
            Previous
          </button>
          <span class="page-numbers">
            <button
              v-for="page in visiblePages"
              :key="page"
              class="page-number"
              :class="{ 'page-number--active': page === pagination.current_page }"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
          </span>
          <button
            class="page-btn"
            :disabled="pagination.current_page === pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- View Customer Modal -->
    <div class="modal-overlay" v-if="showViewModal" @click.self="closeViewModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Customer Details</h2>
          <button class="modal-close" @click="closeViewModal">✕</button>
        </div>
        <div v-if="viewingCustomer" class="customer-detail">
          <div class="detail-header">
            <div class="detail-avatar">
              <span>{{ getInitials(viewingCustomer.name) }}</span>
            </div>
            <div class="detail-title">
              <h3>{{ viewingCustomer.name }}</h3>
              <p>{{ viewingCustomer.email }}</p>
            </div>
            <span class="status-badge" :class="viewingCustomer.status === 1 ? 'active' : 'inactive'">
              {{ viewingCustomer.status === 1 ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Phone</span>
              <span class="detail-value">{{ viewingCustomer.phone_num || viewingCustomer.phone || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Joined</span>
              <span class="detail-value">{{ formatDate(viewingCustomer.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Last Updated</span>
              <span class="detail-value">{{ formatDate(viewingCustomer.updated_at) }}</span>
            </div>
          </div>

          <div class="detail-section" v-if="viewingCustomer.addresses && viewingCustomer.addresses.length > 0">
            <h4>Addresses</h4>
            <div class="address-list">
              <div v-for="address in viewingCustomer.addresses" :key="address.id" class="address-item">
                <div class="address-info">
                  <span class="address-type">{{ getAddressTypeLabel(address.address_type) }}</span>
                  <span class="address-detail">{{ address.address }}, {{ address.township }}, {{ address.city }}</span>
                </div>
                <span v-if="address.is_primary" class="primary-badge-sm">Primary</span>
              </div>
            </div>
          </div>

          <div class="detail-section" v-if="viewingCustomer.subscriptions && viewingCustomer.subscriptions.length > 0">
            <h4>Subscriptions</h4>
            <div class="subscription-list">
              <div v-for="sub in viewingCustomer.subscriptions" :key="sub.id" class="subscription-item">
                <span class="sub-plan">{{ sub.plan?.name || 'N/A' }}</span>
                <span class="sub-price">{{ sub.plan?.price ? formatPrice(sub.plan.price) : 'N/A' }}</span>
                <span class="sub-status" :class="sub.status">{{ sub.status }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeViewModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" v-if="showDeleteModal" @click.self="showDeleteModal = false">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>Confirm Deactivation</h2>
          <button class="modal-close" @click="showDeleteModal = false">✕</button>
        </div>
        <div class="delete-confirm">
          <div class="delete-icon">⚠️</div>
          <p>Are you sure you want to deactivate <strong>{{ deletingCustomer?.name }}</strong>?</p>
          <p class="delete-warning">This will set the customer status to inactive. They will not be able to log in.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-danger" @click="confirmDelete" :disabled="isDeleting">
            {{ isDeleting ? 'Deactivating...' : 'Yes, Deactivate' }}
          </button>
          <button class="btn-secondary" @click="showDeleteModal = false">Cancel</button>
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
import { customerService } from '../../services/api'

export default {
  name: 'AdminCustomers',
  data() {
    return {
      loading: false,
      error: null,
      isDeleting: false,
      searchQuery: '',
      searchTimeout: null,
      customers: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 20
      },
      showViewModal: false,
      showDeleteModal: false,
      viewingCustomer: null,
      deletingCustomer: null,
      deletingId: null,
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    filteredCustomers() {
      if (!this.searchQuery) return this.customers
      const query = this.searchQuery.toLowerCase()
      return this.customers.filter(c =>
        c.name?.toLowerCase().includes(query) ||
        c.email?.toLowerCase().includes(query) ||
        c.phone_num?.includes(query) ||
        c.phone?.includes(query)
      )
    },
    visiblePages() {
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const pages = []
      const maxVisible = 5

      if (last <= maxVisible) {
        for (let i = 1; i <= last; i++) pages.push(i)
      } else {
        pages.push(1)
        let start = Math.max(2, current - 1)
        let end = Math.min(last - 1, current + 1)
        if (start > 2) pages.push('...')
        for (let i = start; i <= end; i++) pages.push(i)
        if (end < last - 1) pages.push('...')
        pages.push(last)
      }
      return pages
    }
  },
  mounted() {
    this.fetchCustomers()
  },
  methods: {
    async fetchCustomers(page = 1) {
      this.loading = true
      this.error = null

      try {
        const response = await customerService.getCustomers(page)
        console.log('✅ Customers API Response:', response)

        const result = response.data || response

        if (result.data && Array.isArray(result.data)) {
          // ✅ Filter out admin users (role = 0) from the list
          this.customers = result.data.filter(c => c.role !== 0)
          this.pagination = {
            current_page: result.current_page || 1,
            last_page: result.last_page || 1,
            total: result.total || 0,
            per_page: result.per_page || 20
          }
        } else if (Array.isArray(result)) {
          this.customers = result.filter(c => c.role !== 0)
        } else {
          this.customers = []
        }

        console.log('✅ Loaded customers (excluding admins):', this.customers.length)

      } catch (error) {
        console.error('❌ Error fetching customers:', error)

        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load customers.'
        }
        this.customers = []
      } finally {
        this.loading = false
      }
    },

    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) return
      this.fetchCustomers(page)
    },

    onSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        // Client-side filtering handled by computed property
      }, 300)
    },

    viewCustomer(id) {
      this.viewingCustomer = this.customers.find(c => c.id === id)
      if (this.viewingCustomer) {
        this.showViewModal = true
      } else {
        this.fetchCustomerDetail(id)
      }
    },

    async fetchCustomerDetail(id) {
      try {
        const response = await customerService.getCustomer(id)
        const data = response.data || response
        this.viewingCustomer = data
        this.showViewModal = true
      } catch (error) {
        console.error('Error fetching customer detail:', error)
        this.showToast('Failed to load customer details.', 'error')
      }
    },

    deleteCustomer(id) {
      const customer = this.customers.find(c => c.id === id)
      if (customer) {
        this.deletingCustomer = customer
        this.deletingId = id
        this.showDeleteModal = true
      }
    },

    async confirmDelete() {
      if (!this.deletingId) return

      this.isDeleting = true

      try {
        await customerService.deleteCustomer(this.deletingId)

        const index = this.customers.findIndex(c => c.id === this.deletingId)
        if (index !== -1) {
          this.customers[index].status = 0
        }

        this.showDeleteModal = false
        this.showToast('Customer deactivated successfully!', 'success')
        this.fetchCustomers(this.pagination.current_page)

      } catch (error) {
        console.error('Error deleting customer:', error)
        this.showToast(error.response?.data?.message || 'Failed to deactivate customer.', 'error')
      } finally {
        this.isDeleting = false
        this.deletingId = null
        this.deletingCustomer = null
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingCustomer = null
    },

    getInitials(name) {
      if (!name) return 'U'
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },

    getAddressTypeLabel(type) {
      const types = {
        1: 'Home',
        2: 'Office',
        3: 'Other'
      }
      return types[type] || 'Unknown'
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
.admin-customers {
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
  padding: 14px 16px;
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

.customer-id {
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

.customer-joined {
  font-size: 12px;
  color: #8892a8;
}

.customer-email {
  color: #555;
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

.status-badge.inactive {
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

.action-btn.delete {
  background: #ffebee;
  color: #c62828;
}

.action-btn.delete:hover {
  background: #ffcdd2;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  flex-wrap: wrap;
  gap: 12px;
}

.pagination-info {
  font-size: 14px;
  color: #666;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-btn {
  padding: 8px 16px;
  background: #f8f9fa;
  border: 1px solid #e8ecf1;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
}

.page-btn:hover:not(:disabled) {
  background: #ff6b35;
  color: #fff;
  border-color: #ff6b35;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 4px;
}

.page-number {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
}

.page-number:hover {
  background: #f8f9fa;
}

.page-number--active {
  background: #ff6b35;
  color: #fff;
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

/* Customer Detail */
.customer-detail {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.detail-avatar {
  width: 56px;
  height: 56px;
  background: #ff6b35;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 20px;
  font-weight: 700;
}

.detail-title {
  flex: 1;
}

.detail-title h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 2px 0;
}

.detail-title p {
  color: #8892a8;
  margin: 0;
  font-size: 14px;
}

.detail-section {
  margin-top: 4px;
}

.detail-section h4 {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 12px 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
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

/* Address List */
.address-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.address-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 14px;
}

.address-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.address-type {
  font-weight: 600;
  color: #ff6b35;
}

.primary-badge-sm {
  background: #ff6b35;
  color: #fff;
  padding: 1px 8px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
}

/* Subscription List */
.subscription-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.subscription-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 14px;
}

.sub-plan {
  font-weight: 500;
  flex: 1;
}

.sub-price {
  color: #666;
}

.sub-status {
  padding: 1px 8px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.sub-status.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.sub-status.pending {
  background: #fff3e0;
  color: #e65100;
}

.sub-status.cancelled {
  background: #ffebee;
  color: #c62828;
}

/* Delete Confirm */
.delete-confirm {
  text-align: center;
  padding: 8px 0;
}

.delete-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.delete-confirm p {
  color: #444;
  margin-bottom: 8px;
}

.delete-warning {
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
@media (max-width: 768px) {
  .page-actions {
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

  .modal {
    padding: 24px;
  }

  .modal-lg {
    max-width: 95%;
  }

  .pagination {
    flex-direction: column;
    align-items: center;
  }

  .action-buttons-group {
    flex-wrap: wrap;
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
