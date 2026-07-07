<template>
  <div class="admin-service-areas">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Service Areas Management</h1>
      <p class="page-subtitle">Manage service areas where internet services are available</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by region, city or township..."
          class="search-input"
          @input="onSearch"
        >
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      <div class="action-buttons">
        <button class="btn-refresh" @click="fetchServiceAreas">
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
          Add Service Area
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading service areas...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchServiceAreas" class="retry-btn">Try Again</button>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Region</th>
            <th>City</th>
            <th>Township</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredAreas.length === 0">
            <td colspan="6" class="empty-row">No service areas found</td>
          </tr>
          <tr v-for="area in filteredAreas" :key="area.id">
            <td>
              <span class="area-id">#{{ String(area.id).padStart(4, '0') }}</span>
            </td>
            <td>{{ area.region }}</td>
            <td>{{ area.city }}</td>
            <td>{{ area.township }}</td>
            <td>
              <span class="status-badge" :class="area.status === 1 ? 'active' : 'inactive'">
                {{ area.status === 1 ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="action-buttons-group">
                <button class="action-btn edit" @click="editArea(area)" title="Edit Service Area">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button
                  class="action-btn delete"
                  @click="deleteArea(area.id)"
                  :disabled="area.status === 0"
                  title="Deactivate Service Area"
                >
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
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" v-if="showAddModal || showEditModal" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Service Area' : 'Add New Service Area' }}</h2>
          <button class="modal-close" @click="closeModals">✕</button>
        </div>
        <form @submit.prevent="saveServiceArea" class="modal-form">
          <div class="form-group">
            <label>Region <span class="required">*</span></label>
            <input type="text" v-model="areaForm.region" required class="form-input" placeholder="Enter region">
          </div>
          <div class="form-group">
            <label>City <span class="required">*</span></label>
            <input type="text" v-model="areaForm.city" required class="form-input" placeholder="Enter city">
          </div>
          <div class="form-group">
            <label>Township <span class="required">*</span></label>
            <input type="text" v-model="areaForm.township" required class="form-input" placeholder="Enter township">
          </div>
          <div class="form-group" v-if="showEditModal">
            <label>Status</label>
            <select v-model="areaForm.status" class="form-input">
              <option :value="1">Active</option>
              <option :value="0">Inactive</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : (showEditModal ? 'Update Service Area' : 'Add Service Area') }}
            </button>
            <button type="button" class="btn-secondary" @click="closeModals">Cancel</button>
          </div>
        </form>
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
          <p>Are you sure you want to deactivate <strong>{{ deletingArea?.region }} - {{ deletingArea?.city }} - {{ deletingArea?.township }}</strong>?</p>
          <p class="delete-warning">This will set the service area status to inactive.</p>
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
import { serviceAreasService } from '../../services/api'

export default {
  name: 'AdminServiceAreas',
  data() {
    return {
      loading: false,
      isSaving: false,
      isDeleting: false,
      searchQuery: '',
      searchTimeout: null,
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      editingId: null,
      deletingId: null,
      deletingArea: null,
      serviceAreas: [],
      areaForm: {
        region: '',
        city: '',
        township: '',
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
    filteredAreas() {
      if (!this.searchQuery) return this.serviceAreas
      const query = this.searchQuery.toLowerCase()
      return this.serviceAreas.filter(a =>
        a.region?.toLowerCase().includes(query) ||
        a.city?.toLowerCase().includes(query) ||
        a.township?.toLowerCase().includes(query)
      )
    }
  },
  mounted() {
    this.fetchServiceAreas()
  },
  methods: {
    async fetchServiceAreas() {
      this.loading = true
      this.error = null

      try {
        const response = await serviceAreasService.getAdminServiceAreas()
        console.log('✅ Service Areas API Response:', response)

        // Handle different response structures
        let areasData = []
        if (response.data && Array.isArray(response.data)) {
          areasData = response.data
        } else if (Array.isArray(response)) {
          areasData = response
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          areasData = response.data.data
        }

        this.serviceAreas = areasData
        console.log('✅ Loaded service areas:', this.serviceAreas.length)

      } catch (error) {
        console.error('❌ Error fetching service areas:', error)

        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load service areas.'
        }
        this.serviceAreas = []
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
      this.areaForm = {
        region: '',
        city: '',
        township: '',
        status: 1
      }
      this.editingId = null
      this.showAddModal = true
    },

    editArea(area) {
      this.editingId = area.id
      this.areaForm = {
        region: area.region || '',
        city: area.city || '',
        township: area.township || '',
        status: area.status || 1
      }
      this.showEditModal = true
    },

    deleteArea(id) {
      const area = this.serviceAreas.find(a => a.id === id)
      if (!area) return

      if (area.status === 0) {
        this.showToast('Service area is already inactive.', 'error')
        return
      }

      this.deletingArea = area
      this.deletingId = id
      this.showDeleteModal = true
    },

    async confirmDelete() {
      if (!this.deletingId) return

      this.isDeleting = true

      try {
        await serviceAreasService.deleteServiceArea(this.deletingId)

        // Update local data
        const index = this.serviceAreas.findIndex(a => a.id === this.deletingId)
        if (index !== -1) {
          this.serviceAreas[index].status = 0
        }

        this.showDeleteModal = false
        this.showToast('Service area deactivated successfully!', 'success')

      } catch (error) {
        console.error('Error deleting service area:', error)
        this.showToast(error.response?.data?.message || 'Failed to deactivate service area.', 'error')
      } finally {
        this.isDeleting = false
        this.deletingId = null
        this.deletingArea = null
      }
    },

    async saveServiceArea() {
      // Validate required fields
      if (!this.areaForm.region || !this.areaForm.city || !this.areaForm.township) {
        this.showToast('Please fill in all required fields.', 'error')
        return
      }

      this.isSaving = true

      try {
        const data = {
          region: this.areaForm.region,
          city: this.areaForm.city,
          township: this.areaForm.township,
          status: this.areaForm.status || 1
        }

        let response
        if (this.showEditModal) {
          // Update existing service area
          response = await serviceAreasService.updateServiceArea(this.editingId, data)
          this.showToast('Service area updated successfully!', 'success')
        } else {
          // Create new service area
          response = await serviceAreasService.createServiceArea(data)
          this.showToast('Service area added successfully!', 'success')
        }

        console.log('Save response:', response)

        this.closeModals()
        this.fetchServiceAreas()

      } catch (error) {
        console.error('Error saving service area:', error)
        const message = error.response?.data?.message || 'Failed to save service area.'
        this.showToast(message, 'error')
      } finally {
        this.isSaving = false
      }
    },

    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.editingId = null
      this.isSaving = false
      this.areaForm = {
        region: '',
        city: '',
        township: '',
        status: 1
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
.admin-service-areas {
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

.area-id {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 13px;
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

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
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

  .action-buttons {
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

  .form-actions {
    flex-direction: column;
  }
}
</style>
