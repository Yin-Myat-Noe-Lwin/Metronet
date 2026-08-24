<template>
  <div class="admin-plans">
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Plans Management</h1>
        <p class="page-subtitle">Create and manage internet service plans</p>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchPlans" :disabled="loading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
          </svg>
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
        <button class="btn-add" @click="openAddModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add Plan
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
          placeholder="Search plans..."
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
      <p>Loading plans...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchPlans" class="retry-btn">Try Again</button>
    </div>

    <!-- Plans Grid -->
    <div v-else>
      <div v-if="filteredPlans.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
        </div>
        <h3>{{ searchQuery ? 'No results found' : 'No plans yet' }}</h3>
        <p>{{ searchQuery ? 'Try adjusting your search term.' : 'Click "Add Plan" to create your first plan.' }}</p>
        <button v-if="!searchQuery" class="btn-add-empty" @click="openAddModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Create Plan
        </button>
      </div>

      <div v-else class="plans-grid">
        <div v-for="plan in filteredPlans" :key="plan.id" class="plan-card">
          <div class="plan-card-header">
            <div class="plan-name-wrapper">
              <h3>{{ plan.name }}</h3>
              <span class="status-badge" :class="Number(plan.status) === 1 ? 'active' : 'inactive'">
                {{ Number(plan.status) === 1 ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="plan-actions">
              <button class="action-btn edit" @click="editPlan(plan)" title="Edit Plan">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button class="action-btn delete" @click="confirmDelete(plan)" title="Delete Plan">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="plan-price">
            <div class="price-main">
              <span class="price-amount">{{ formatPrice(calculateMonthlyPrice(plan.price, plan.validity_months)) }}</span>
              <span class="price-period">/month</span>
            </div>
            <div class="price-total">
              <span class="price-total-label">Total: </span>
              <span class="price-total-amount">{{ formatPrice(plan.price) }}</span>
              <span class="price-total-period">for {{ plan.validity_months }} month{{ plan.validity_months > 1 ? 's' : '' }}</span>
            </div>
          </div>

          <div class="plan-specs">
            <div class="spec-item">
              <span class="spec-label">Download</span>
              <span class="spec-value">{{ plan.download_speed }} Mbps</span>
            </div>
            <div class="spec-divider"></div>
            <div class="spec-item">
              <span class="spec-label">Upload</span>
              <span class="spec-value">{{ plan.upload_speed }} Mbps</span>
            </div>
          </div>

          <div class="plan-description" v-if="plan.description">
            {{ plan.description }}
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click.self="closeModals">
      <div class="modal modal-form">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Plan' : 'Add New Plan' }}</h2>
          <button class="modal-close" @click="closeModals">×</button>
        </div>
        <form @submit.prevent="savePlan" novalidate>
          <!-- Row 1: Name & Description -->
          <div class="form-row">
            <div class="form-group" :class="{ 'has-error': validationErrors.name }">
              <label>Name <span class="required">*</span></label>
              <input
                type="text"
                v-model="planForm.name"
                name="name"
                class="form-input"
                placeholder="Plan name"
                maxlength="255"
                @input="clearFieldError('name')"
              >
              <small v-if="validationErrors.name" class="error-text">{{ validationErrors.name[0] }}</small>
            </div>
            <div class="form-group" :class="{ 'has-error': validationErrors.description }">
              <label>Description <span class="required">*</span></label>
              <input
                type="text"
                v-model="planForm.description"
                name="description"
                class="form-input"
                placeholder="Brief description"
                maxlength="100"
                @input="clearFieldError('description')"
              >
              <small v-if="validationErrors.description" class="error-text">{{ validationErrors.description[0] }}</small>
            </div>
          </div>

          <!-- Row 2: Price & Validity -->
          <div class="form-row">
            <div class="form-group" :class="{ 'has-error': validationErrors.price }">
              <label>Total Price (MMK) <span class="required">*</span></label>
              <input
                type="number"
                v-model="planForm.price"
                name="price"
                class="form-input"
                placeholder="0 - 999,999"
                min="0"
                max="999999"
                step="0.01"
                @input="clearFieldError('price')"
              >
              <small v-if="validationErrors.price" class="error-text">{{ validationErrors.price[0] }}</small>
              <small class="hint-text" v-if="planForm.price && planForm.validity_months">
                Monthly: {{ formatPrice(Number(planForm.price) / Number(planForm.validity_months)) }}
              </small>
            </div>
            <div class="form-group" :class="{ 'has-error': validationErrors.validity_months }">
              <label>Validity (Months) <span class="required">*</span></label>
              <input
                type="number"
                v-model.number="planForm.validity_months"
                name="validity_months"
                class="form-input"
                placeholder="1 - 12"
                min="1"
                max="12"
                @input="clearFieldError('validity_months')"
              >
              <small v-if="validationErrors.validity_months" class="error-text">{{ validationErrors.validity_months[0] }}</small>
              <small class="hint-text" v-if="planForm.price && planForm.validity_months">
                Total: {{ formatPrice(Number(planForm.price)) }}
              </small>
            </div>
          </div>

          <!-- Row 3: Download & Upload Speeds -->
          <div class="form-row">
            <div class="form-group" :class="{ 'has-error': validationErrors.download_speed }">
              <label>Download (Mbps) <span class="required">*</span></label>
              <input
                type="number"
                v-model.number="planForm.download_speed"
                name="download_speed"
                class="form-input"
                placeholder="1 - 200"
                min="1"
                max="200"
                @input="clearFieldError('download_speed')"
              >
              <small v-if="validationErrors.download_speed" class="error-text">{{ validationErrors.download_speed[0] }}</small>
            </div>
            <div class="form-group" :class="{ 'has-error': validationErrors.upload_speed }">
              <label>Upload (Mbps) <span class="required">*</span></label>
              <input
                type="number"
                v-model.number="planForm.upload_speed"
                name="upload_speed"
                class="form-input"
                placeholder="1 - 200"
                min="1"
                max="200"
                @input="clearFieldError('upload_speed')"
              >
              <small v-if="validationErrors.upload_speed" class="error-text">{{ validationErrors.upload_speed[0] }}</small>
            </div>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : (showEditModal ? 'Update' : 'Add') }}
            </button>
            <button type="button" class="btn-secondary" @click="closeModals">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal modal-delete">
        <div class="delete-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <h3 class="delete-title">Deactivate Plan?</h3>
        <p class="delete-message">
          Are you sure you want to deactivate "<strong>{{ deletePlanName }}</strong>"?
          <br>
          <span class="text-muted">Customers will no longer be able to subscribe to this plan.</span>
        </p>
        <div class="delete-actions">
          <button class="btn-cancel" @click="closeDeleteModal">Cancel</button>
          <button class="btn-confirm-delete" @click="deletePlan" :disabled="isDeleting">
            {{ isDeleting ? 'Deactivating...' : 'Yes, Deactivate' }}
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
import { plansService } from '../../services/api'

export default {
  name: 'AdminPlans',
  data() {
    return {
      loading: false,
      isSaving: false,
      isDeleting: false,
      error: null,
      searchQuery: '',
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'active', label: 'Active' },
        { key: 'inactive', label: 'Inactive' }
      ],
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      editingId: null,
      deletePlanId: null,
      deletePlanName: '',
      plans: [],
      validationErrors: {},
      planForm: {
        name: '',
        description: '',
        price: '',
        validity_months: 1,
        download_speed: '',
        upload_speed: '',
        status: 1
      },
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    filteredPlans() {
      let filtered = this.plans

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(p =>
          p.name?.toLowerCase().includes(query) ||
          p.description?.toLowerCase().includes(query)
        )
      }

      if (this.activeFilter === 'active') {
        filtered = filtered.filter(p => Number(p.status) === 1)
      } else if (this.activeFilter === 'inactive') {
        filtered = filtered.filter(p => Number(p.status) === 0)
      }

      return filtered
    }
  },
  mounted() {
    this.fetchPlans()
  },
  methods: {
    calculateMonthlyPrice(totalPrice, validityMonths) {
      if (!totalPrice || !validityMonths || validityMonths === 0) {
        return 0
      }
      return Number(totalPrice) / Number(validityMonths)
    },

    async fetchPlans() {
      this.loading = true
      this.error = null

      try {
        const response = await plansService.getAdminPlans()

        let plansData = []
        if (response && response.data && Array.isArray(response.data)) {
          plansData = response.data
        } else if (response && Array.isArray(response)) {
          plansData = response
        }

        const seen = new Set()
        this.plans = plansData
          .filter(plan => {
            const duplicate = seen.has(plan.id)
            seen.add(plan.id)
            return !duplicate
          })
          .map(plan => {
            return {
              ...plan,
              status: Number(plan.status) || 0,
              validity_months: Number(plan.validity_months) || 1
            }
          })

        console.log('Plans loaded:', {
          total: this.plans.length,
          active: this.plans.filter(p => Number(p.status) === 1).length,
          inactive: this.plans.filter(p => Number(p.status) === 0).length,
          all: this.plans.map(p => ({
            id: p.id,
            name: p.name,
            status: p.status,
            price: p.price,
            validity_months: p.validity_months,
            monthly: this.calculateMonthlyPrice(p.price, p.validity_months)
          }))
        })

      } catch (error) {
        console.error('Error fetching plans:', error)
        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load plans.'
        }
        this.plans = []
      } finally {
        this.loading = false
      }
    },

    getFilterCount(key) {
      if (key === 'all') return this.plans.length
      if (key === 'active') return this.plans.filter(p => Number(p.status) === 1).length
      if (key === 'inactive') return this.plans.filter(p => Number(p.status) === 0).length
      return 0
    },

    clearFieldError(field) {
      delete this.validationErrors[field]
      this.validationErrors = { ...this.validationErrors }
    },

    openAddModal() {
      this.validationErrors = {}
      this.planForm = {
        name: '',
        description: '',
        price: '',
        validity_months: 1,
        download_speed: '',
        upload_speed: '',
        status: 1
      }
      this.editingId = null
      this.showAddModal = true
    },

    editPlan(plan) {
      console.log('📝 Editing plan:', plan)
      console.log('📝 Plan validity_months:', plan.validity_months)

      this.validationErrors = {}
      this.editingId = plan.id
      this.planForm = {
        name: plan.name || '',
        description: plan.description || '',
        price: plan.price || '',
        validity_months: Number(plan.validity_months) || 1,
        download_speed: plan.download_speed || '',
        upload_speed: plan.upload_speed || '',
        status: Number(plan.status) || 1
      }

      console.log('📝 Form validity_months after edit:', this.planForm.validity_months)
      this.showEditModal = true
    },

    confirmDelete(plan) {
      this.deletePlanId = plan.id
      this.deletePlanName = plan.name
      this.showDeleteModal = true
    },

    closeDeleteModal() {
      this.showDeleteModal = false
      this.deletePlanId = null
      this.deletePlanName = ''
      this.isDeleting = false
    },

    async deletePlan() {
      if (!this.deletePlanId) return

      this.isDeleting = true
      try {
        await plansService.deletePlan(this.deletePlanId)
        this.showToast('Plan deactivated successfully!', 'success')
        this.closeDeleteModal()
        await this.fetchPlans()
      } catch (error) {
        console.error('Error deleting plan:', error)
        this.showToast(error.response?.data?.message || 'Failed to deactivate plan.', 'error')
        this.closeDeleteModal()
      } finally {
        this.isDeleting = false
      }
    },

    validateField(value, min, max, label, isInteger = true) {
      if (value === '' || value === null || value === undefined) {
        return { valid: false, message: `${label} is required` }
      }

      const num = Number(value)

      if (isNaN(num)) {
        return { valid: false, message: `${label} must be a valid number` }
      }

      if (isInteger && !Number.isInteger(num)) {
        return { valid: false, message: `${label} must be a whole number` }
      }

      if (num < min) {
        return { valid: false, message: `${label} must be at least ${min}` }
      }

      if (num > max) {
        return { valid: false, message: `${label} must not exceed ${max}` }
      }

      return { valid: true, value: num }
    },

    async savePlan() {
      this.validationErrors = {}
      let hasError = false

      // Validate Name
      if (!this.planForm.name || !this.planForm.name.trim()) {
        this.validationErrors.name = ['Name is required']
        hasError = true
      }

      // Validate Description
      if (!this.planForm.description || !this.planForm.description.trim()) {
        this.validationErrors.description = ['Description is required']
        hasError = true
      } else if (this.planForm.description.length > 100) {
        this.validationErrors.description = ['Max 100 characters']
        hasError = true
      }

      // Validate Price
      const priceResult = this.validateField(this.planForm.price, 0, 999999, 'Price', false)
      if (!priceResult.valid) {
        this.validationErrors.price = [priceResult.message]
        hasError = true
      }

      // Validate Validity
      const validityResult = this.validateField(this.planForm.validity_months, 1, 12, 'Validity')
      if (!validityResult.valid) {
        this.validationErrors.validity_months = [validityResult.message]
        hasError = true
      }

      // Validate Download Speed
      const downloadResult = this.validateField(this.planForm.download_speed, 1, 200, 'Download')
      if (!downloadResult.valid) {
        this.validationErrors.download_speed = [downloadResult.message]
        hasError = true
      }

      // Validate Upload Speed
      const uploadResult = this.validateField(this.planForm.upload_speed, 1, 200, 'Upload')
      if (!uploadResult.valid) {
        this.validationErrors.upload_speed = [uploadResult.message]
        hasError = true
      }

      if (hasError) {
        const firstKey = Object.keys(this.validationErrors)[0]
        this.showToast(this.validationErrors[firstKey]?.[0] || 'Please fix errors', 'error')
        const input = document.querySelector(`[name="${firstKey}"]`)
        if (input) { input.focus(); input.scrollIntoView({ behavior: 'smooth', block: 'center' }) }
        return
      }

      this.isSaving = true

      try {
        const data = {
          name: this.planForm.name.trim(),
          description: this.planForm.description.trim(),
          price: Number(this.planForm.price),
          validity_months: Number(this.planForm.validity_months),
          download_speed: Number(this.planForm.download_speed),
          upload_speed: Number(this.planForm.upload_speed),
          status: Number(this.planForm.status) || 1
        }

        // 🔍 DEBUG: Log what's being sent
        console.log('🔍 SENDING UPDATE DATA:')
        console.log('Plan ID:', this.editingId)
        console.log('Form Data:', this.planForm)
        console.log('Data being sent:', data)
        console.log('validity_months value:', data.validity_months)
        console.log('validity_months type:', typeof data.validity_months)
        console.log('========================')

        if (this.showEditModal) {
          const response = await plansService.updatePlan(this.editingId, data)
          console.log('✅ Update response:', response.data)
          console.log('✅ New validity_months from response:', response.data.data?.validity_months)
          this.showToast('Plan updated successfully!', 'success')
        } else {
          await plansService.createPlan(data)
          this.showToast('Plan added successfully!', 'success')
        }

        this.closeModals()
        await this.fetchPlans()

      } catch (error) {
        console.error('❌ Error saving plan:', error)
        console.error('❌ Error response:', error.response?.data)

        if (error.response?.status === 422) {
          if (error.response?.data?.errors && Object.keys(error.response.data.errors).length > 0) {
            this.validationErrors = error.response.data.errors
            const firstKey = Object.keys(this.validationErrors)[0]
            this.showToast(this.validationErrors[firstKey]?.[0] || 'Validation failed', 'error')
            const input = document.querySelector(`[name="${firstKey}"]`)
            if (input) { input.focus(); input.scrollIntoView({ behavior: 'smooth', block: 'center' }) }
          } else if (error.response?.data?.message) {
            this.showToast(error.response.data.message, 'error')
          } else {
            this.showToast('Validation failed. Please check your input.', 'error')
          }
        } else if (error.response?.data?.message) {
          this.showToast(error.response.data.message, 'error')
        } else {
          this.showToast('Failed to save plan. Please try again.', 'error')
        }
      } finally {
        this.isSaving = false
      }
    },

    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.editingId = null
      this.isSaving = false
      this.validationErrors = {}
      this.planForm = {
        name: '',
        description: '',
        price: '',
        validity_months: 1,
        download_speed: '',
        upload_speed: '',
        status: 1
      }
    },

    formatPrice(price) {
      if (!price && price !== 0) return '0 MMK'
      return new Intl.NumberFormat('my-MM', {
        style: 'currency',
        currency: 'MMK',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(price)
    },

    showToast(message, type = 'success') {
      this.toast.message = message
      this.toast.type = type
      this.toast.show = true
      setTimeout(() => { this.toast.show = false }, 4000)
    }
  }
}
</script>

<style scoped>
.admin-plans {
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
  margin: 0 0 4px 0;
}

.page-subtitle {
  color: #94a3b8;
  font-size: 15px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 10px;
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

.btn-add {
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
  transition: background 0.3s;
}

.btn-add:hover {
  background: #e85a2a;
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

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 20px;
  color: #0f172a;
  margin: 0 0 8px;
}

.empty-state p {
  color: #94a3b8;
  margin: 0 0 20px;
}

.btn-add-empty {
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
  transition: background 0.3s;
}

.btn-add-empty:hover {
  background: #e85a2a;
}

/* ===== PLANS GRID ===== */
.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.plan-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  transition: all 0.3s;
}

.plan-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border-color: #cbd5e1;
}

.plan-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.plan-name-wrapper {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.plan-name-wrapper h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.status-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  width: fit-content;
}

.status-badge.active {
  background: #dcfce7;
  color: #16a34a;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.plan-actions {
  display: flex;
  gap: 4px;
}

.action-btn {
  display: flex;
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

.action-btn.delete {
  background: #fef2f2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #fecaca;
}

/* ===== PRICE DISPLAY ===== */
.plan-price {
  margin-bottom: 16px;
}

.price-main {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.price-amount {
  font-size: 28px;
  font-weight: 700;
  color: #ff6b35;
}

.price-period {
  color: #94a3b8;
  font-size: 14px;
}

.price-total {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
  font-size: 13px;
  color: #94a3b8;
  flex-wrap: wrap;
}

.price-total-label {
  color: #94a3b8;
}

.price-total-amount {
  font-weight: 600;
  color: #475569;
}

.price-total-period {
  color: #94a3b8;
}

/* ===== PLAN SPECS ===== */
.plan-specs {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  margin-bottom: 12px;
}

.spec-item {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.spec-label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.spec-value {
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
}

.spec-divider {
  width: 1px;
  height: 30px;
  background: #e2e8f0;
}

.plan-description {
  color: #64748b;
  font-size: 14px;
  line-height: 1.5;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
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
  max-width: 520px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-form {
  padding: 24px 28px;
}

/* ===== DELETE MODAL ===== */
.modal-delete {
  max-width: 420px;
  padding: 40px 32px;
  text-align: center;
}

.delete-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.delete-icon svg {
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
  margin: 0 0 24px;
}

.delete-message strong {
  color: #0f172a;
}

.delete-message .text-muted {
  color: #94a3b8;
  font-size: 14px;
}

.delete-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel {
  padding: 10px 24px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  flex: 1;
}

.btn-cancel:hover {
  background: #e2e8f0;
}

.btn-confirm-delete {
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

.btn-confirm-delete:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-confirm-delete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ===== FORM ===== */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.modal-header h2 {
  font-size: 18px;
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

.form-group {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 10px;
}

.form-group label {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
}

.required {
  color: #dc2626;
}

.hint-text {
  color: #94a3b8;
  font-size: 12px;
  margin-top: 2px;
}

.form-input {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
  width: 100%;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
}

.form-group.has-error .form-input {
  border-color: #dc2626;
}

.error-text {
  color: #dc2626;
  font-size: 11px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 8px;
}

.btn-primary {
  padding: 8px 20px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  flex: 1;
}

.btn-primary:hover:not(:disabled) {
  background: #e85a2a;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 8px 20px;
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

  .plans-grid {
    grid-template-columns: 1fr;
  }

  .modal-delete {
    padding: 32px 24px;
  }

  .delete-actions {
    flex-direction: column;
  }

  .modal {
    padding: 16px;
  }

  .form-row {
    grid-template-columns: 1fr;
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

  .plan-card {
    padding: 16px;
  }

  .plan-specs {
    flex-direction: column;
    gap: 8px;
  }

  .spec-divider {
    display: none;
  }

  .modal {
    padding: 12px;
  }

  .modal-delete {
    padding: 24px 16px;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
