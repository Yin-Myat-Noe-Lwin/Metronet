<template>
  <div class="admin-cpes">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">CPE Management</h1>
      <p class="page-subtitle">Manage Customer Premise Equipment (CPEs)</p>
    </div>

    <!-- Search and Actions -->
    <div class="page-actions">
      <div class="search-group">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search by serial number or MAC address..."
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
          <option value="0">Unassigned</option>
          <option value="1">Assigned</option>
          <option value="2">Inactive</option>
        </select>
        <button class="btn-add" @click="openAddModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add CPE
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading CPEs...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="fetchCPEs" class="retry-btn">Try Again</button>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Serial Number</th>
            <th>MAC Address</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredCPEs.length === 0">
            <td colspan="6" class="empty-row">No CPEs found</td>
          </tr>
          <tr v-for="cpe in filteredCPEs" :key="cpe.id">
            <td>
              <span class="cpe-id">#{{ String(cpe.id).padStart(4, '0') }}</span>
            </td>
            <td>
              <span class="serial-number">{{ cpe.serial_number }}</span>
            </td>
            <td>
              <span class="mac-address">{{ cpe.mac_address }}</span>
            </td>
            <td>
              <span class="status-badge" :class="getStatusClass(cpe.status)">
                {{ getStatusLabel(cpe.status) }}
              </span>
            </td>
            <td>{{ formatDate(cpe.created_at) }}</td>
            <td>
              <div class="action-buttons-group">
                <button class="action-btn view" @click="viewCPE(cpe.id)" title="View Details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
                <button class="action-btn edit" @click="editCPE(cpe)" title="Edit CPE">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button
                  class="action-btn delete"
                  @click="deleteCPE(cpe.id)"
                  :disabled="cpe.status === 1"
                  :title="cpe.status === 1 ? 'Cannot delete assigned CPE' : 'Delete CPE'"
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

      <!-- Summary -->
      <div class="table-footer">
        <div class="summary-stats">
          <span class="stat-item">
            <strong>Total:</strong> {{ cpes.length }}
          </span>
          <span class="stat-item">
            <strong>Unassigned:</strong> {{ unassignedCount }}
          </span>
          <span class="stat-item">
            <strong>Assigned:</strong> {{ assignedCount }}
          </span>
          <span class="stat-item">
            <strong>Inactive:</strong> {{ inactiveCount }}
          </span>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" v-if="showAddModal || showEditModal" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit CPE' : 'Add New CPE' }}</h2>
          <button class="modal-close" @click="closeModals">✕</button>
        </div>
        <form @submit.prevent="saveCPE" class="modal-form">
          <div class="form-group">
            <label>Serial Number <span class="required">*</span></label>
            <input
              type="text"
              v-model="cpeForm.serial_number"
              required
              class="form-input"
              placeholder="Enter serial number"
            >
          </div>
          <div class="form-group">
            <label>MAC Address <span class="required">*</span></label>
            <input
              type="text"
              v-model="cpeForm.mac_address"
              required
              class="form-input"
              placeholder="Enter MAC address (e.g., 00:1A:2B:3C:4D:5E)"
            >
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="cpeForm.status" class="form-input">
              <option :value="0">Unassigned</option>
              <option :value="1">Assigned</option>
              <option :value="2">Inactive</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : (showEditModal ? 'Update CPE' : 'Add CPE') }}
            </button>
            <button type="button" class="btn-secondary" @click="closeModals">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View CPE Modal -->
    <div class="modal-overlay" v-if="showViewModal" @click.self="closeViewModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>CPE Details</h2>
          <button class="modal-close" @click="closeViewModal">✕</button>
        </div>
        <div v-if="viewingCPE" class="cpe-detail">
          <div class="detail-header">
            <div>
              <span class="cpe-title">CPE #{{ String(viewingCPE.id).padStart(4, '0') }}</span>
              <span class="cpe-subtitle">{{ viewingCPE.serial_number }}</span>
            </div>
            <span class="status-badge" :class="getStatusClass(viewingCPE.status)">
              {{ getStatusLabel(viewingCPE.status) }}
            </span>
          </div>

          <div class="detail-grid">
            <div class="detail-section">
              <h4>Device Information</h4>
              <div class="detail-item">
                <span class="detail-label">Serial Number</span>
                <span class="detail-value">{{ viewingCPE.serial_number }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">MAC Address</span>
                <span class="detail-value">{{ viewingCPE.mac_address }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                  <span class="status-badge" :class="getStatusClass(viewingCPE.status)">
                    {{ getStatusLabel(viewingCPE.status) }}
                  </span>
                </span>
              </div>
            </div>

            <div class="detail-section">
              <h4>Timestamps</h4>
              <div class="detail-item">
                <span class="detail-label">Created</span>
                <span class="detail-value">{{ formatDate(viewingCPE.created_at) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value">{{ formatDate(viewingCPE.updated_at) }}</span>
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
          <h2>Confirm Delete</h2>
          <button class="modal-close" @click="showDeleteModal = false">✕</button>
        </div>
        <div class="delete-confirm">
          <div class="delete-icon">⚠️</div>
          <p>Are you sure you want to delete this CPE?</p>
          <p class="delete-warning">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-danger" @click="confirmDelete" :disabled="isDeleting">
            {{ isDeleting ? 'Deleting...' : 'Yes, Delete' }}
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
import { cpeService } from '../../services/api'

export default {
  name: 'AdminCPEs',
  data() {
    return {
      loading: false,
      isSaving: false,
      isDeleting: false,
      error: null,
      searchQuery: '',
      statusFilter: 'all',
      searchTimeout: null,
      cpes: [],
      showAddModal: false,
      showEditModal: false,
      showViewModal: false,
      showDeleteModal: false,
      editingId: null,
      deletingId: null,
      viewingCPE: null,
      cpeForm: {
        serial_number: '',
        mac_address: '',
        status: 0
      },
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  computed: {
    filteredCPEs() {
      let filtered = [...this.cpes]

      // Filter by status
      if (this.statusFilter !== 'all') {
        filtered = filtered.filter(cpe => cpe.status === parseInt(this.statusFilter))
      }

      // Filter by search
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(cpe =>
          cpe.serial_number?.toLowerCase().includes(query) ||
          cpe.mac_address?.toLowerCase().includes(query)
        )
      }

      return filtered
    },
    unassignedCount() {
      return this.cpes.filter(c => c.status === 0).length
    },
    assignedCount() {
      return this.cpes.filter(c => c.status === 1).length
    },
    inactiveCount() {
      return this.cpes.filter(c => c.status === 2).length
    }
  },
  mounted() {
    this.fetchCPEs()
  },
  methods: {
    async fetchCPEs() {
      this.loading = true
      this.error = null

      try {
        const response = await cpeService.getCPEs()
        console.log('✅ CPEs API Response:', response)

        let cpesData = []
        if (response.data && Array.isArray(response.data)) {
          cpesData = response.data
        } else if (Array.isArray(response)) {
          cpesData = response
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          cpesData = response.data.data
        }

        this.cpes = cpesData
        console.log('✅ Loaded CPEs:', this.cpes.length)

      } catch (error) {
        console.error('❌ Error fetching CPEs:', error)

        if (error.response?.status === 401) {
          this.error = 'Session expired. Please login again.'
          setTimeout(() => {
            localStorage.removeItem('authToken')
            localStorage.removeItem('isLoggedIn')
            this.$router.push('/login')
          }, 2000)
        } else {
          this.error = error.response?.data?.message || 'Failed to load CPEs.'
        }
        this.cpes = []
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

    getStatusLabel(status) {
      const labels = {
        0: 'Unassigned',
        1: 'Assigned',
        2: 'Inactive'
      }
      return labels[status] || 'Unknown'
    },

    getStatusClass(status) {
      const classes = {
        0: 'unassigned',
        1: 'assigned',
        2: 'inactive'
      }
      return classes[status] || ''
    },

    openAddModal() {
      this.cpeForm = {
        serial_number: '',
        mac_address: '',
        status: 0
      }
      this.editingId = null
      this.showAddModal = true
    },

    editCPE(cpe) {
      this.editingId = cpe.id
      this.cpeForm = {
        serial_number: cpe.serial_number || '',
        mac_address: cpe.mac_address || '',
        status: cpe.status || 0
      }
      this.showEditModal = true
    },

    viewCPE(id) {
      this.viewingCPE = this.cpes.find(c => c.id === id)
      if (this.viewingCPE) {
        this.showViewModal = true
      }
    },

    async saveCPE() {
      // Validate MAC address format
      const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/
      if (!macRegex.test(this.cpeForm.mac_address)) {
        this.showToast('Invalid MAC address format. Use format: 00:1A:2B:3C:4D:5E', 'error')
        return
      }

      this.isSaving = true

      try {
        const data = {
          serial_number: this.cpeForm.serial_number,
          mac_address: this.cpeForm.mac_address,
          status: parseInt(this.cpeForm.status)
        }

        let response
        if (this.showEditModal) {
          // Update existing CPE
          response = await cpeService.updateCPE(this.editingId, data)
          this.showToast('CPE updated successfully!', 'success')
        } else {
          // Create new CPE
          response = await cpeService.createCPE(data)
          this.showToast('CPE added successfully!', 'success')
        }

        console.log('Save response:', response)

        this.closeModals()
        this.fetchCPEs()

      } catch (error) {
        console.error('Error saving CPE:', error)
        const message = error.response?.data?.message || 'Failed to save CPE.'
        this.showToast(message, 'error')
      } finally {
        this.isSaving = false
      }
    },

    deleteCPE(id) {
      const cpe = this.cpes.find(c => c.id === id)
      if (!cpe) return

      if (cpe.status === 1) {
        this.showToast('Cannot delete assigned CPE. Please unassign it first.', 'error')
        return
      }

      this.deletingId = id
      this.showDeleteModal = true
    },

    async confirmDelete() {
      if (!this.deletingId) return

      this.isDeleting = true

      try {
        await cpeService.deleteCPE(this.deletingId)

        this.showDeleteModal = false
        this.showToast('CPE deleted successfully!', 'success')
        this.fetchCPEs()

      } catch (error) {
        console.error('Error deleting CPE:', error)
        this.showToast(error.response?.data?.message || 'Failed to delete CPE.', 'error')
      } finally {
        this.isDeleting = false
        this.deletingId = null
      }
    },

    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.editingId = null
      this.isSaving = false
      this.cpeForm = {
        serial_number: '',
        mac_address: '',
        status: 0
      }
    },

    closeViewModal() {
      this.showViewModal = false
      this.viewingCPE = null
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
.admin-cpes {
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

.cpe-id {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 13px;
}

.serial-number {
  font-weight: 500;
  color: #1a1a2e;
}

.mac-address {
  font-family: monospace;
  color: #555;
  font-size: 13px;
}

.status-badge {
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.unassigned {
  background: #e3f2fd;
  color: #1976d2;
}

.status-badge.assigned {
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

/* CPE Detail */
.cpe-detail {
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

.cpe-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.cpe-subtitle {
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

  .form-actions {
    flex-direction: column;
  }
}
</style>
