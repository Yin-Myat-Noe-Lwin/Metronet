<template>
  <div class="admin-plans">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Plans Management</h1>
      <p class="page-subtitle">Create and manage internet service plans</p>
    </div>

    <!-- Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search plans..."
          class="search-input"
          @input="onSearch"
        >
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      <div class="action-buttons">
        <button class="btn-refresh" @click="fetchPlans">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
          </svg>
          Refresh
        </button>
        <button class="btn-add" @click="openAddModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add Plan
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
    <div v-else class="plans-grid">
      <div v-if="filteredPlans.length === 0" class="empty-state">
        <p>No plans found</p>
      </div>
      <div v-for="plan in filteredPlans" :key="plan.id" class="plan-card">
        <div class="plan-card-header">
          <h3>{{ plan.name }}</h3>
          <div class="plan-actions">
            <button class="action-btn edit" @click="editPlan(plan)">Edit</button>
            <button class="action-btn delete" @click="deletePlan(plan.id)" :disabled="plan.status === 0">Delete</button>
          </div>
        </div>
        <div class="plan-price">
          <span class="price-amount">{{ formatPrice(plan.price) }}</span>
          <span class="price-period">/month</span>
        </div>
        <div class="plan-details">
          <div class="detail-item">
            <span class="label">Download</span>
            <span class="value">{{ plan.download_speed }} Mbps</span>
          </div>
          <div class="detail-item">
            <span class="label">Upload</span>
            <span class="value">{{ plan.upload_speed }} Mbps</span>
          </div>
          <div class="detail-item">
            <span class="label">Status</span>
            <span class="status-badge" :class="plan.status === 1 ? 'active' : 'inactive'">
              {{ plan.status === 1 ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>
        <div class="plan-description" v-if="plan.description">
          {{ plan.description }}
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" v-if="showAddModal || showEditModal" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Plan' : 'Add New Plan' }}</h2>
          <button class="modal-close" @click="closeModals">✕</button>
        </div>
        <form @submit.prevent="savePlan" class="modal-form">
          <div class="form-group">
            <label>Plan Name <span class="required">*</span></label>
            <input type="text" v-model="planForm.name" required class="form-input" placeholder="Enter plan name">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="planForm.description" rows="2" class="form-input" placeholder="Brief description of the plan"></textarea>
          </div>
          <div class="form-group">
            <label>Price (MMK) <span class="required">*</span></label>
            <input type="number" v-model="planForm.price" required class="form-input" placeholder="Enter price">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Download Speed (Mbps) <span class="required">*</span></label>
              <input type="number" v-model="planForm.download_speed" required class="form-input" placeholder="e.g., 100">
            </div>
            <div class="form-group">
              <label>Upload Speed (Mbps) <span class="required">*</span></label>
              <input type="number" v-model="planForm.upload_speed" required class="form-input" placeholder="e.g., 10">
            </div>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="planForm.status" class="form-input">
              <option :value="1">Active</option>
              <option :value="0">Inactive</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : (showEditModal ? 'Update Plan' : 'Add Plan') }}
            </button>
            <button type="button" class="btn-secondary" @click="closeModals">Cancel</button>
          </div>
        </form>
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
import { plansService } from '../../services/api'

export default {
  name: 'AdminPlans',
  data() {
    return {
      loading: false,
      isSaving: false,
      error: null,
      searchQuery: '',
      searchTimeout: null,
      showAddModal: false,
      showEditModal: false,
      editingId: null,
      plans: [],
      planForm: {
        name: '',
        description: '',
        price: '',
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
      if (!this.searchQuery) return this.plans
      const query = this.searchQuery.toLowerCase()
      return this.plans.filter(p =>
        p.name?.toLowerCase().includes(query) ||
        p.description?.toLowerCase().includes(query)
      )
    }
  },
  mounted() {
    this.fetchPlans()
  },
  methods: {
    async fetchPlans() {
      this.loading = true
      this.error = null

      try {
        const response = await plansService.getAdminPlans()
        console.log('✅ Plans API Response:', response)

        // ✅ CRITICAL FIX: Extract data correctly
        let plansData = []

        // Check different response structures
        if (response && response.data && Array.isArray(response.data)) {
          // Response: { data: [...] }
          plansData = response.data
        } else if (response && Array.isArray(response)) {
          // Response: [...]
          plansData = response
        } else if (response && response.data && response.data.data && Array.isArray(response.data.data)) {
          // Response: { data: { data: [...] } }
          plansData = response.data.data
        } else if (response && response.data && typeof response.data === 'object') {
          // If data is an object with plans property
          plansData = response.data.plans || []
        }

        // ✅ Remove duplicates based on id
        const seen = new Set()
        const uniquePlans = plansData.filter(plan => {
          const duplicate = seen.has(plan.id)
          seen.add(plan.id)
          return !duplicate
        })

        this.plans = uniquePlans
        console.log('✅ Loaded unique plans:', this.plans.length)

      } catch (error) {
        console.error('❌ Error fetching plans:', error)

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

    onSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        // Client-side filtering handled by computed property
      }, 300)
    },

    openAddModal() {
      this.planForm = {
        name: '',
        description: '',
        price: '',
        download_speed: '',
        upload_speed: '',
        status: 1
      }
      this.editingId = null
      this.showAddModal = true
    },

    editPlan(plan) {
      this.editingId = plan.id
      this.planForm = {
        name: plan.name || '',
        description: plan.description || '',
        price: plan.price || '',
        download_speed: plan.download_speed || '',
        upload_speed: plan.upload_speed || '',
        status: plan.status || 1
      }
      this.showEditModal = true
    },

    async savePlan() {
      // Validate required fields
      if (!this.planForm.name || !this.planForm.price ||
          !this.planForm.download_speed || !this.planForm.upload_speed) {
        this.showToast('Please fill in all required fields.', 'error')
        return
      }

      this.isSaving = true

      try {
        const data = {
          name: this.planForm.name,
          description: this.planForm.description || '',
          price: parseFloat(this.planForm.price),
          download_speed: parseInt(this.planForm.download_speed),
          upload_speed: parseInt(this.planForm.upload_speed),
          status: parseInt(this.planForm.status)
        }

        let response
        if (this.showEditModal) {
          // Update existing plan
          response = await plansService.updatePlan(this.editingId, data)
          this.showToast('Plan updated successfully!', 'success')
        } else {
          // Create new plan
          response = await plansService.createPlan(data)
          this.showToast('Plan added successfully!', 'success')
        }

        console.log('Save response:', response)

        this.closeModals()
        this.fetchPlans()

      } catch (error) {
        console.error('Error saving plan:', error)
        const message = error.response?.data?.message || 'Failed to save plan.'
        this.showToast(message, 'error')
      } finally {
        this.isSaving = false
      }
    },

    async deletePlan(id) {
      const plan = this.plans.find(p => p.id === id)
      if (!plan) return

      if (plan.status === 0) {
        this.showToast('Plan is already inactive.', 'error')
        return
      }

      if (!confirm(`Are you sure you want to deactivate "${plan.name}"?`)) return

      try {
        await plansService.deletePlan(id)
        this.showToast('Plan deactivated successfully!', 'success')
        this.fetchPlans()
      } catch (error) {
        console.error('Error deleting plan:', error)
        this.showToast(error.response?.data?.message || 'Failed to deactivate plan.', 'error')
      }
    },

    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.editingId = null
      this.isSaving = false
      this.planForm = {
        name: '',
        description: '',
        price: '',
        download_speed: '',
        upload_speed: '',
        status: 1
      }
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
.admin-plans {
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

.action-buttons {
  display: flex;
  gap: 10px;
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

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 8px;
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

.btn-add:hover {
  background: #e85a2a;
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

.btn-primary:hover:not(:disabled) {
  background: #e85a2a;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
  color: #8892a8;
}

.plan-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e8ecf1;
  transition: all 0.3s;
}

.plan-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.plan-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.plan-card-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.plan-actions {
  display: flex;
  gap: 4px;
}

.plan-price {
  margin-bottom: 16px;
}

.price-amount {
  font-size: 28px;
  font-weight: 700;
  color: #ff6b35;
}

.price-period {
  color: #8892a8;
  font-size: 14px;
}

.plan-details {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.detail-item .label {
  font-size: 11px;
  color: #8892a8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item .value {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a2e;
  margin-top: 2px;
}

.plan-description {
  color: #666;
  font-size: 14px;
  line-height: 1.5;
  margin-top: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
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

.action-btn {
  padding: 4px 12px;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.action-btn.edit {
  background: #fff3e0;
  color: #e65100;
}

.action-btn.edit:hover {
  background: #ffe0b2;
}

.action-btn.delete {
  background: #ffebee;
  color: #c62828;
}

.action-btn.delete:hover:not(:disabled) {
  background: #ffcdd2;
}

.action-btn.delete:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group label {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.required {
  color: #e74c3c;
}

.form-input {
  padding: 10px 14px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
}

textarea.form-input {
  resize: vertical;
  min-height: 60px;
  font-family: inherit;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
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

  .action-buttons {
    flex-direction: column;
  }

  .plans-grid {
    grid-template-columns: 1fr;
  }

  .plan-details {
    grid-template-columns: 1fr 1fr;
  }

  .modal {
    padding: 24px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 22px;
  }

  .plan-details {
    grid-template-columns: 1fr;
  }

  .modal {
    padding: 20px;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
