<template>
  <div class="admin-plan-discounts">
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Plan Discounts Management</h1>
        <p class="page-subtitle">Manage discount rates for different subscription durations</p>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchDiscounts" :disabled="loading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
          </svg>
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
        <button class="btn-primary" @click="openCreateModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Discount
        </button>
      </div>
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
          placeholder="Search by plan name, duration or ID..."
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
      <p>Loading discounts...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchDiscounts" class="retry-btn">Try Again</button>
    </div>

    <!-- Discounts Table -->
    <div v-else class="table-wrapper">
      <div v-if="filteredDiscounts.length === 0" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
        <h3>No Discounts Found</h3>
        <p>{{ searchQuery ? 'Try adjusting your search.' : 'No discounts configured yet.' }}</p>
      </div>

      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Plan</th>
            <th>Duration</th>
            <th>Discount</th>
            <th>Status</th>
            <th>Created</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="discount in filteredDiscounts" :key="discount.id">
            <td>
              <span class="discount-id">#{{ String(discount.id).padStart(4, '0') }}</span>
            </td>
            <td>
              <div class="plan-info">
                <span class="plan-name">{{ discount.plan?.name || 'Unknown' }}</span>
                <span class="plan-speed">{{ discount.plan?.download_speed || 0 }}/{{ discount.plan?.upload_speed || 0 }} Mbps</span>
              </div>
            </td>
            <td>
              <span class="duration-badge">{{ discount.duration_months }} month{{ discount.duration_months > 1 ? 's' : '' }}</span>
            </td>
            <td>
              <span class="discount-percentage" :class="{ 'high-discount': discount.discount_percentage >= 20 }">
                {{ discount.discount_percentage }}%
              </span>
            </td>
            <td>
              <span class="status-badge" :class="discount.is_active ? 'active' : 'inactive'">
                <span class="status-dot"></span>
                {{ discount.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>{{ formatDate(discount.created_at) }}</td>
            <td>
              <div class="action-buttons">
                <!-- Edit -->
                <button class="action-btn edit" @click="openEditModal(discount)" title="Edit Discount">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>

                <!-- Toggle Status -->
                <button
                  class="action-btn toggle"
                  :class="discount.is_active ? 'deactivate' : 'activate'"
                  @click="toggleStatus(discount)"
                  :title="discount.is_active ? 'Deactivate' : 'Activate'"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v4M12 22v-4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M22 12h-4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                  </svg>
                </button>

                <!-- Delete -->
                <button class="action-btn delete" @click="confirmDelete(discount)" title="Delete Discount">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Summary Stats -->
      <div class="table-footer">
        <div class="summary-stats">
          <span class="stat-item"><strong>Total:</strong> {{ discounts.length }}</span>
          <span class="stat-item active"><span class="dot green"></span><strong>Active:</strong> {{ activeCount }}</span>
          <span class="stat-item inactive"><span class="dot gray"></span><strong>Inactive:</strong> {{ inactiveCount }}</span>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showFormModal" class="modal-overlay" @click.self="closeFormModal">
      <div class="modal modal-form">
        <div class="modal-header">
          <h2>{{ isEditing ? 'Edit Discount' : 'Add New Discount' }}</h2>
          <button class="modal-close" @click="closeFormModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveDiscount" class="discount-form">
            <!-- Plan Selection -->
            <div class="form-group">
              <label class="form-label">Plan <span class="required">*</span></label>
              <select v-model="form.plan_id" class="form-select" required>
                <option value="">Select a plan</option>
                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                  {{ plan.name }} - {{ formatPrice(plan.price) }} ({{ plan.download_speed }}/{{ plan.upload_speed }} Mbps)
                </option>
              </select>
            </div>

            <!-- Duration -->
            <div class="form-group">
              <label class="form-label">Duration (Months) <span class="required">*</span></label>
              <select v-model="form.duration_months" class="form-select" required>
                <option value="">Select duration</option>
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
                <option value="24">24 Months</option>
              </select>
            </div>

            <!-- Discount Percentage -->
            <div class="form-group">
              <label class="form-label">Discount Percentage <span class="required">*</span></label>
              <div class="discount-input-group">
                <input
                  type="number"
                  v-model="form.discount_percentage"
                  class="form-input"
                  min="0"
                  max="100"
                  step="0.01"
                  required
                  placeholder="0.00"
                >
                <span class="discount-suffix">%</span>
              </div>
              <span class="form-hint">Enter a value between 0 and 100</span>
            </div>

            <!-- Calculated Price Preview -->
            <div v-if="selectedPlan" class="price-preview">
              <h4>Price Preview</h4>
              <div class="preview-row">
                <span>Original Price:</span>
                <span class="original-price">{{ formatPrice(selectedPlan.price) }}</span>
              </div>
              <div class="preview-row">
                <span>Discount:</span>
                <span class="discount-amount">-{{ form.discount_percentage || 0 }}%</span>
              </div>
              <div class="preview-row total">
                <span>Discounted Price:</span>
                <span class="discounted-price">{{ formatPrice(calculateDiscountedPrice()) }}</span>
              </div>
            </div>

            <!-- Status -->
            <div class="form-group">
              <label class="form-label">Status</label>
              <div class="toggle-wrapper">
                <label class="toggle-label">
                  <input type="checkbox" v-model="form.is_active" class="toggle-input">
                  <span class="toggle-slider"></span>
                  <span class="toggle-text">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="closeFormModal">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update Discount' : 'Add Discount') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal modal-delete">
        <div class="delete-icon-wrapper">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <h3 class="delete-title">Delete Discount?</h3>
        <p class="delete-message">
          Are you sure you want to delete the discount for <strong>{{ deletePlanName }}</strong>
          ({{ deleteDuration }} month{{ deleteDuration > 1 ? 's' : '' }}, {{ deletePercentage }}%)?
        </p>
        <p class="delete-warning">This action cannot be undone.</p>
        <div class="delete-actions">
          <button class="btn-secondary" @click="closeDeleteModal">Cancel</button>
          <button class="btn-danger" @click="confirmDelete" :disabled="isDeleting">
            {{ isDeleting ? 'Deleting...' : 'Yes, Delete' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast.show" class="toast" :class="toast.type">
      <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
      <span class="toast-message">{{ toast.message }}</span>
      <button class="toast-close" @click="toast.show = false">×</button>
    </div>
  </div>
</template>

<script>
import { planDiscountsService, plansService } from '../../services/api'

export default {
  name: 'AdminPlanDiscounts',
  data() {
    return {
      // Loading states
      loading: false,
      isSubmitting: false,
      isDeleting: false,
      error: null,

      // Search and filter
      searchQuery: '',
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'active', label: 'Active' },
        { key: 'inactive', label: 'Inactive' }
      ],

      // Data
      discounts: [],
      plans: [],

      // Form
      showFormModal: false,
      isEditing: false,
      editingId: null,
      form: {
        plan_id: '',
        duration_months: '',
        discount_percentage: 0,
        is_active: true
      },

      // Delete
      showDeleteModal: false,
      deletingId: null,
      deletePlanName: '',
      deleteDuration: '',
      deletePercentage: '',

      // Toast
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },

  computed: {
    filteredDiscounts() {
      let filtered = [...this.discounts]

      if (this.activeFilter === 'active') {
        filtered = filtered.filter(d => d.is_active === 1 || d.is_active === true)
      } else if (this.activeFilter === 'inactive') {
        filtered = filtered.filter(d => d.is_active === 0 || d.is_active === false)
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(d =>
          d.plan?.name?.toLowerCase().includes(query) ||
          String(d.duration_months).includes(query) ||
          String(d.id).includes(query)
        )
      }

      return filtered
    },

    activeCount() {
      return this.discounts.filter(d => d.is_active === 1 || d.is_active === true).length
    },

    inactiveCount() {
      return this.discounts.filter(d => d.is_active === 0 || d.is_active === false).length
    },

    selectedPlan() {
      return this.plans.find(p => p.id === this.form.plan_id)
    }
  },

  mounted() {
    this.fetchDiscounts()
    this.fetchPlans()
  },

  methods: {
    // ===================== DATA FETCHING =====================

    async fetchDiscounts() {
      this.loading = true
      this.error = null

      try {
        const response = await planDiscountsService.getDiscounts()
        this.discounts = response.data || response || []
      } catch (error) {
        console.error('Error fetching discounts:', error)
        this.error = error.response?.data?.message || 'Failed to load discounts.'
        this.discounts = []
      } finally {
        this.loading = false
      }
    },

    async fetchPlans() {
      try {
        const response = await plansService.getPlans()
        this.plans = response.data || response || []
      } catch (error) {
        console.error('Error fetching plans:', error)
      }
    },

    // ===================== FILTER HELPERS =====================

    getFilterCount(key) {
      if (key === 'all') return this.discounts.length
      if (key === 'active') return this.discounts.filter(d => d.is_active === 1 || d.is_active === true).length
      if (key === 'inactive') return this.discounts.filter(d => d.is_active === 0 || d.is_active === false).length
      return 0
    },

    // ===================== FORM MODAL =====================

    openCreateModal() {
      this.isEditing = false
      this.editingId = null
      this.form = {
        plan_id: '',
        duration_months: '',
        discount_percentage: 0,
        is_active: true
      }
      this.showFormModal = true
    },

    openEditModal(discount) {
      this.isEditing = true
      this.editingId = discount.id
      this.form = {
        plan_id: discount.plan_id,
        duration_months: discount.duration_months,
        discount_percentage: parseFloat(discount.discount_percentage),
        is_active: discount.is_active === 1 || discount.is_active === true
      }
      this.showFormModal = true
    },

    closeFormModal() {
      this.showFormModal = false
      this.isEditing = false
      this.editingId = null
      this.isSubmitting = false
    },

    // ===================== SAVE DISCOUNT =====================

    async saveDiscount() {
      if (!this.form.plan_id || !this.form.duration_months) {
        this.showToast('Please select a plan and duration.', 'error')
        return
      }

      this.isSubmitting = true

      try {
        const data = {
          plan_id: this.form.plan_id,
          duration_months: parseInt(this.form.duration_months),
          discount_percentage: parseFloat(this.form.discount_percentage) || 0,
          is_active: this.form.is_active ? 1 : 0
        }

        if (this.isEditing) {
          await planDiscountsService.updateDiscount(this.editingId, data)
          this.showToast('Discount updated successfully!', 'success')
        } else {
          await planDiscountsService.createDiscount(data)
          this.showToast('Discount added successfully!', 'success')
        }

        this.closeFormModal()
        this.fetchDiscounts()
      } catch (error) {
        console.error('Error saving discount:', error)
        const message = error.response?.data?.message || 'Failed to save discount.'
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat().join('\n')
          this.showToast(errors, 'error')
        } else {
          this.showToast(message, 'error')
        }
      } finally {
        this.isSubmitting = false
      }
    },

    // ===================== TOGGLE STATUS =====================

    async toggleStatus(discount) {
      const newStatus = discount.is_active ? 0 : 1
      const action = discount.is_active ? 'deactivate' : 'activate'

      if (!confirm(`Are you sure you want to ${action} this discount?`)) return

      try {
        await planDiscountsService.updateDiscount(discount.id, {
          ...discount,
          is_active: newStatus
        })
        this.showToast(`Discount ${action}d successfully!`, 'success')
        this.fetchDiscounts()
      } catch (error) {
        console.error('Error toggling status:', error)
        this.showToast(error.response?.data?.message || 'Failed to update status.', 'error')
      }
    },

    // ===================== DELETE DISCOUNT =====================

    confirmDelete(discount) {
      this.deletingId = discount.id
      this.deletePlanName = discount.plan?.name || 'Unknown'
      this.deleteDuration = discount.duration_months
      this.deletePercentage = discount.discount_percentage
      this.showDeleteModal = true
    },

    closeDeleteModal() {
      this.showDeleteModal = false
      this.deletingId = null
      this.deletePlanName = ''
      this.deleteDuration = ''
      this.deletePercentage = ''
      this.isDeleting = false
    },

    async confirmDelete() {
      if (!this.deletingId) return

      this.isDeleting = true
      try {
        await planDiscountsService.deleteDiscount(this.deletingId)
        this.closeDeleteModal()
        this.showToast('Discount deleted successfully!', 'success')
        this.fetchDiscounts()
      } catch (error) {
        console.error('Error deleting discount:', error)
        this.showToast(error.response?.data?.message || 'Failed to delete discount.', 'error')
      } finally {
        this.isDeleting = false
      }
    },

    // ===================== UTILITY METHODS =====================

    calculateDiscountedPrice() {
      if (!this.selectedPlan || !this.form.discount_percentage) {
        return this.selectedPlan?.price || 0
      }
      const price = parseFloat(this.selectedPlan.price)
      const discount = parseFloat(this.form.discount_percentage) || 0
      return price - (price * discount / 100)
    },

    formatPrice(price) {
      if (!price && price !== 0) return 'N/A'
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
/* ===== PAGE HEADER ===== */
.admin-plan-discounts {
  padding: 20px 0;
  max-width: 1200px;
}

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

.header-actions {
  display: flex;
  gap: 12px;
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
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
  background: #e85a2a;
  transform: translateY(-1px);
}

.btn-primary:disabled {
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
  background: rgba(255, 255, 255, 0.2);
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
  width: 120px;
  text-align: center;
}

/* ===== DISCOUNT ID ===== */
.discount-id {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
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

/* ===== DURATION BADGE ===== */
.duration-badge {
  display: inline-block;
  padding: 3px 12px;
  background: #eef2ff;
  color: #4f46e5;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 600;
}

/* ===== DISCOUNT PERCENTAGE ===== */
.discount-percentage {
  font-size: 18px;
  font-weight: 700;
  color: #16a34a;
}

.discount-percentage.high-discount {
  color: #dc2626;
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

.status-badge.active {
  background: #dcfce7;
  color: #16a34a;
}
.status-badge.active .status-dot {
  background: #16a34a;
}

.status-badge.inactive {
  background: #f1f5f9;
  color: #94a3b8;
}
.status-badge.inactive .status-dot {
  background: #94a3b8;
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

.action-btn.edit {
  background: #eef2ff;
  color: #4f46e5;
}
.action-btn.edit:hover {
  background: #c7d2fe;
}

.action-btn.toggle {
  background: #fef3c7;
  color: #d97706;
}
.action-btn.toggle:hover {
  background: #fde68a;
}

.action-btn.toggle.activate {
  background: #dcfce7;
  color: #16a34a;
}
.action-btn.toggle.activate:hover {
  background: #bbf7d0;
}

.action-btn.toggle.deactivate {
  background: #fef3c7;
  color: #d97706;
}
.action-btn.toggle.deactivate:hover {
  background: #fde68a;
}

.action-btn.delete {
  background: #fee2e2;
  color: #dc2626;
}
.action-btn.delete:hover {
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

.stat-item .dot.green {
  background: #16a34a;
}
.stat-item .dot.gray {
  background: #94a3b8;
}

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
  to {
    transform: rotate(360deg);
  }
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
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal {
  background: #fff;
  border-radius: 16px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
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

.modal-form {
  max-width: 600px;
  width: 90%;
  padding: 32px;
}

.modal-delete {
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

/* ===== FORM ===== */
.discount-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-weight: 600;
  font-size: 14px;
  color: #0f172a;
}

.required {
  color: #dc2626;
}

.form-select,
.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
  background: #fff;
}

.form-select:focus,
.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.discount-input-group {
  display: flex;
  align-items: center;
  position: relative;
}

.discount-input-group .form-input {
  padding-right: 40px;
}

.discount-suffix {
  position: absolute;
  right: 14px;
  font-size: 16px;
  font-weight: 600;
  color: #94a3b8;
}

.form-hint {
  font-size: 12px;
  color: #94a3b8;
}

/* ===== TOGGLE SWITCH ===== */
.toggle-wrapper {
  display: flex;
  align-items: center;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.toggle-input {
  display: none;
}

.toggle-slider {
  display: inline-block;
  width: 44px;
  height: 24px;
  background: #cbd5e1;
  border-radius: 50px;
  position: relative;
  transition: background 0.3s;
  flex-shrink: 0;
}

.toggle-slider::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: #fff;
  border-radius: 50%;
  transition: transform 0.3s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-input:checked + .toggle-slider {
  background: #ff6b35;
}

.toggle-input:checked + .toggle-slider::after {
  transform: translateX(20px);
}

.toggle-text {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
}

/* ===== PRICE PREVIEW ===== */
.price-preview {
  background: #f8fafc;
  padding: 16px 20px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.price-preview h4 {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 8px;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  font-size: 14px;
  color: #475569;
}

.preview-row.total {
  border-top: 1px solid #e2e8f0;
  margin-top: 4px;
  padding-top: 8px;
  font-weight: 600;
  color: #0f172a;
}

.original-price {
  color: #94a3b8;
  text-decoration: line-through;
}

.discount-amount {
  color: #16a34a;
}

.discounted-price {
  color: #ff6b35;
  font-size: 18px;
}

/* ===== FORM ACTIONS ===== */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
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
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ===== DELETE MODAL ===== */
.delete-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.delete-icon-wrapper svg {
  background: #fef2f2;
  padding: 12px;
  border-radius: 50%;
}

.delete-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px;
}

.delete-message {
  color: #475569;
  font-size: 15px;
  line-height: 1.6;
  margin: 0 0 4px;
}

.delete-warning {
  color: #dc2626;
  font-size: 14px;
  margin: 0 0 24px;
}

.delete-actions {
  display: flex;
  gap: 12px;
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
  color: rgba(255, 255, 255, 0.6);
  font-size: 20px;
  cursor: pointer;
  margin-left: auto;
  padding: 0 4px;
}

.toast-close:hover {
  color: #fff;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
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

  .modal-form,
  .modal-delete {
    padding: 24px;
  }

  .modal-delete {
    max-width: 95%;
  }

  .delete-actions {
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

  .form-actions {
    flex-direction: column;
  }

  .form-actions .btn-primary,
  .form-actions .btn-secondary {
    width: 100%;
    justify-content: center;
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

  .modal-form,
  .modal-delete {
    padding: 16px;
  }

  .actions-col {
    width: 100px;
  }
}
</style>
