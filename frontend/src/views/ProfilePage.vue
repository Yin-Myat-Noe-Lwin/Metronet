<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-container">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading profile...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <p>{{ error }}</p>
          <button @click="loadUserData" class="retry-btn">Try Again</button>
        </div>

        <!-- Profile Content -->
        <template v-else>
          <!-- Profile Header -->
          <div class="profile-header">
            <div class="profile-avatar">
              <span class="avatar-text">{{ userInitials }}</span>
            </div>
            <div class="profile-info">
              <h1 class="profile-name">{{ userData.name || 'User' }}</h1>
              <p class="profile-email">{{ userData.email }}</p>
              <p class="profile-phone" v-if="userData.phone_num">
                {{ userData.phone_num }}
              </p>
            </div>
          </div>

          <!-- Profile Tabs -->
          <div class="profile-tabs-wrapper">
            <div class="profile-tabs">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                class="tab-btn"
                :class="{ 'tab-btn--active': activeTab === tab.key }"
                @click="activeTab = tab.key"
              >
                {{ tab.label }}
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Personal Information Tab -->
            <div v-if="activeTab === 'profile'" class="tab-panel">
              <div v-if="updateMessage" class="message" :class="updateType">
                {{ updateMessage }}
                <button class="message-close" @click="updateMessage = ''">×</button>
              </div>

              <div class="profile-card">
                <div class="profile-card-header">
                  <h3>Personal Information</h3>
                  <p class="card-subtitle">Manage your personal details</p>
                </div>

                <div class="profile-display">
                  <!-- Name -->
                  <div class="info-row">
                    <div class="info-icon">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                    </div>
                    <div class="info-content">
                      <div class="info-label">Full Name</div>
                      <div class="info-value">
                        <span v-if="editingField !== 'name'">{{ userData.name || 'Not set' }}</span>
                        <input
                          v-else
                          v-model="editData.name"
                          class="edit-input"
                          placeholder="Enter your full name"
                          autofocus
                          :disabled="isSaving"
                        >
                      </div>
                    </div>
                    <button
                      @click="toggleEdit('name')"
                      class="edit-field-btn"
                      v-if="editingField !== 'name'"
                      :disabled="isSaving"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <div v-else class="edit-actions">
                      <button @click="saveField('name')" class="save-field-btn" :disabled="isSaving">
                        <span v-if="isSaving" class="btn-spinner"></span>
                        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                          <path d="M20 6L9 17l-5-5"/>
                        </svg>
                      </button>
                      <button @click="cancelEdit" class="cancel-field-btn" :disabled="isSaving">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                          <line x1="18" y1="6" x2="6" y2="18"/>
                          <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Email -->
                  <div class="info-row">
                    <div class="info-icon">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                      </svg>
                    </div>
                    <div class="info-content">
                      <div class="info-label">Email Address</div>
                      <div class="info-value">
                        <span v-if="editingField !== 'email'">{{ userData.email || 'Not set' }}</span>
                        <input
                          v-else
                          v-model="editData.email"
                          class="edit-input"
                          placeholder="Enter your email address"
                          type="email"
                          autofocus
                          :disabled="isSaving"
                        >
                      </div>
                    </div>
                    <button
                      @click="toggleEdit('email')"
                      class="edit-field-btn"
                      v-if="editingField !== 'email'"
                      :disabled="isSaving"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <div v-else class="edit-actions">
                      <button @click="saveField('email')" class="save-field-btn" :disabled="isSaving">
                        <span v-if="isSaving" class="btn-spinner"></span>
                        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                          <path d="M20 6L9 17l-5-5"/>
                        </svg>
                      </button>
                      <button @click="cancelEdit" class="cancel-field-btn" :disabled="isSaving">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                          <line x1="18" y1="6" x2="6" y2="18"/>
                          <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Phone -->
                  <div class="info-row">
                    <div class="info-icon">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                      </svg>
                    </div>
                    <div class="info-content">
                      <div class="info-label">Phone Number</div>
                      <div class="info-value">
                        <span v-if="editingField !== 'phone'">{{ userData.phone_num || 'Not set' }}</span>
                        <input
                          v-else
                          v-model="editData.phone_num"
                          class="edit-input"
                          placeholder="Enter your phone number"
                          autofocus
                          :disabled="isSaving"
                        >
                      </div>
                    </div>
                    <button
                      @click="toggleEdit('phone')"
                      class="edit-field-btn"
                      v-if="editingField !== 'phone'"
                      :disabled="isSaving"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <div v-else class="edit-actions">
                      <button @click="saveField('phone')" class="save-field-btn" :disabled="isSaving">
                        <span v-if="isSaving" class="btn-spinner"></span>
                        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                          <path d="M20 6L9 17l-5-5"/>
                        </svg>
                      </button>
                      <button @click="cancelEdit" class="cancel-field-btn" :disabled="isSaving">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                          <line x1="18" y1="6" x2="6" y2="18"/>
                          <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Addresses Tab -->
            <div v-if="activeTab === 'addresses'" class="tab-panel">
              <div class="addresses-header">
                <div>
                  <h3>My Addresses</h3>
                  <p class="card-subtitle">Manage your saved addresses</p>
                </div>
                <span class="address-count">{{ addresses.length }} address{{ addresses.length !== 1 ? 'es' : '' }}</span>
              </div>

              <!-- Address List -->
              <div class="address-list">
                <div v-if="addressesLoading" class="loading-state-small">
                  <div class="small-spinner"></div>
                  <p>Loading addresses...</p>
                </div>
                <div v-else-if="addresses.length === 0" class="empty-state">
                  <p>No addresses added yet.</p>
                  <small>You haven't saved any addresses</small>
                </div>
                <div
                  v-for="address in addresses"
                  :key="address.id"
                  class="address-card"
                  :class="{ 'address-card--primary': address.is_primary }"
                >
                  <div class="address-card-header">
                    <div class="address-badges">
                      <span v-if="address.is_primary" class="primary-badge">Primary</span>
                      <span class="address-type">{{ getAddressTypeLabel(address.address_type) }}</span>
                    </div>
                  </div>
                  <div class="address-content">
                    <p class="address-street">{{ address.address }}</p>
                    <p class="address-location">{{ address.township }}, {{ address.city }}, {{ address.region }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Email Change Confirmation Modal -->
    <div v-if="showEmailModal" class="modal-overlay" @click.self="closeEmailModal">
      <div class="modal-container">
        <div class="modal-content">
          <div class="modal-icon">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <h3>Confirm Email Change</h3>
          <p>
            You are about to change your email from
            <strong>{{ userData.email }}</strong> to
            <strong>{{ editData.email }}</strong>
          </p>
          <p class="modal-hint">
            A verification link will be sent to your new email address.
            You will need to verify it to complete the change.
          </p>
          <div class="modal-actions">
            <button @click="closeEmailModal" class="modal-btn modal-btn-secondary" :disabled="isSaving">
              Cancel
            </button>
            <button @click="confirmEmailChange" class="modal-btn modal-btn-primary" :disabled="isSaving">
              <span v-if="isSaving" class="btn-spinner"></span>
              {{ isSaving ? 'Sending...' : 'Send Verification' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay for Profile Update -->
    <div v-if="isSaving" class="loading-overlay">
      <div class="loading-overlay-content">
        <div class="loading-overlay-spinner"></div>
        <p>Updating your profile...</p>
      </div>
    </div>
  </div>
</template>

<script>
import { authService, addressService } from '../services/api'

export default {
  name: 'ProfilePage',
  data() {
    return {
      loading: true,
      error: null,
      addressesLoading: false,
      editingField: null,
      isSaving: false,
      showEmailModal: false,
      activeTab: 'profile',
      tabs: [
        { key: 'profile', label: 'Profile' },
        { key: 'addresses', label: 'Addresses' }
      ],
      userData: {
        name: '',
        email: '',
        phone_num: '',
        status: 1
      },
      editData: {
        name: '',
        email: '',
        phone_num: ''
      },
      updateMessage: '',
      updateType: 'success',
      addresses: []
    }
  },
  computed: {
    userInitials() {
      let name = this.userData.name || localStorage.getItem('userName') || ''

      if (!name) {
        try {
          const userDataStr = localStorage.getItem('userData')
          if (userDataStr) {
            const userData = JSON.parse(userDataStr)
            name = userData.name || ''
          }
        } catch (e) {
          // Ignore
        }
      }

      if (!name || name.trim() === '') {
        return 'U'
      }

      const nameParts = name.trim().split(' ')
      if (nameParts.length === 1) {
        return nameParts[0].substring(0, 2).toUpperCase()
      }
      return (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
    }
  },
  mounted() {
    this.loadFromLocalStorage()
    this.loadUserData()
    this.loadAddresses()
  },
  methods: {
    loadFromLocalStorage() {
      const storedUserData = localStorage.getItem('userData')
      if (storedUserData) {
        try {
          const userData = JSON.parse(storedUserData)
          this.userData = {
            name: userData.name || '',
            email: userData.email || '',
            phone_num: userData.phone_num || '',
            status: userData.status || 1
          }
          this.editData = {
            name: this.userData.name,
            email: this.userData.email,
            phone_num: this.userData.phone_num
          }
          if (this.userData.name) {
            localStorage.setItem('userName', this.userData.name)
          }
          if (this.userData.email) {
            localStorage.setItem('userEmail', this.userData.email)
          }
        } catch (e) {
          console.error('Error parsing userData:', e)
        }
      }

      if (!this.userData.name) {
        const name = localStorage.getItem('userName')
        if (name) {
          this.userData.name = name
          this.editData.name = name
        }
      }
      if (!this.userData.email) {
        const email = localStorage.getItem('userEmail')
        if (email) {
          this.userData.email = email
          this.editData.email = email
        }
      }

      if (this.userData.name || this.userData.email) {
        this.loading = false
      }
    },

    async loadUserData() {
      if (!this.userData.name && !this.userData.email) {
        this.loading = true
      }
      this.error = null

      try {
        const response = await authService.getUser()
        let user = null

        if (response?.data) {
          user = response.data
        } else if (response?.user) {
          user = response.user
        } else if (response?.customer) {
          user = response.customer
        } else if (response?.id || response?.name || response?.email) {
          user = response
        }

        if (user && user.name) {
          this.userData = {
            name: user.name || this.userData.name || '',
            email: user.email || this.userData.email || '',
            phone_num: user.phone_number || user.phone_num || user.phone || this.userData.phone_num || '',
            status: user.status !== undefined ? user.status : (this.userData.status || 1)
          }

          this.editData = {
            name: this.userData.name,
            email: this.userData.email,
            phone_num: this.userData.phone_num
          }

          const userDataToStore = {
            name: this.userData.name,
            email: this.userData.email,
            phone_num: this.userData.phone_num,
            status: this.userData.status,
            role: user.role !== undefined ? user.role : 1,
            id: user.id || null
          }

          localStorage.setItem('userData', JSON.stringify(userDataToStore))
          localStorage.setItem('userName', this.userData.name)
          localStorage.setItem('userEmail', this.userData.email)
          window.dispatchEvent(new CustomEvent('userDataUpdated'))
        }
      } catch (error) {
        console.error('Error loading user data:', error)
        const storedData = localStorage.getItem('userData')
        if (storedData) {
          try {
            const userData = JSON.parse(storedData)
            if (userData.name || userData.email) {
              if (!this.userData.name) {
                this.userData = {
                  name: userData.name || '',
                  email: userData.email || '',
                  phone_num: userData.phone_num || '',
                  status: userData.status || 1
                }
                this.editData = {
                  name: this.userData.name,
                  email: this.userData.email,
                  phone_num: this.userData.phone_num
                }
              }
            }
          } catch (e) {
            console.error('Error parsing stored data:', e)
          }
        }
        if (!this.userData.name && !this.userData.email) {
          this.error = error.response?.data?.message || error.message || 'Failed to load profile data.'
        }
      } finally {
        this.loading = false
      }
    },

    async loadAddresses() {
      this.addressesLoading = true
      try {
        const response = await addressService.viewAddresses()
        let addresses = response
        if (response?.data) {
          addresses = response.data
        } else if (response?.addresses) {
          addresses = response.addresses
        }
        this.addresses = Array.isArray(addresses) ? addresses : []
      } catch (error) {
        console.error('Error loading addresses:', error)
        this.addresses = []
      } finally {
        this.addressesLoading = false
      }
    },

    getAddressTypeLabel(type) {
      const types = {
        1: 'Home',
        2: 'Office',
        3: 'Business'
      }
      return types[type] || 'Other'
    },

    toggleEdit(field) {
      this.editingField = field
      this.editData = {
        name: this.userData.name,
        email: this.userData.email,
        phone_num: this.userData.phone_num
      }
      this.updateMessage = ''
    },

    cancelEdit() {
      this.editingField = null
      this.editData = {
        name: this.userData.name,
        email: this.userData.email,
        phone_num: this.userData.phone_num
      }
      this.updateMessage = ''
    },

    async saveField(field) {
      if (field === 'email' && this.editData.email !== this.userData.email) {
        if (!this.isValidEmail(this.editData.email)) {
          this.updateMessage = 'Please enter a valid email address'
          this.updateType = 'error'
          setTimeout(() => this.updateMessage = '', 3000)
          return
        }
        this.showEmailModal = true
        return
      }
      await this.performUpdate(field)
    },

    isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    },

    closeEmailModal() {
      this.showEmailModal = false
      this.editingField = null
      this.editData = {
        name: this.userData.name,
        email: this.userData.email,
        phone_num: this.userData.phone_num
      }
    },

    async confirmEmailChange() {
      await this.performUpdate('email')
      this.showEmailModal = false
    },

    async performUpdate(field) {
      this.isSaving = true
      this.updateMessage = ''

      try {
        const data = {}

        if (this.editData.name !== this.userData.name) {
          data.name = this.editData.name
        }
        if (this.editData.email !== this.userData.email) {
          data.email = this.editData.email
        }
        if (this.editData.phone_num !== this.userData.phone_num) {
          data.phone_num = this.editData.phone_num
        }

        if (Object.keys(data).length === 0) {
          this.updateMessage = 'No changes to save'
          this.updateType = 'info'
          this.isSaving = false
          this.editingField = null
          setTimeout(() => this.updateMessage = '', 2000)
          return
        }

        const response = await authService.updateProfile(data)
        console.log('Update response:', response)

        if (data.name) this.userData.name = data.name
        if (data.phone_num) this.userData.phone_num = data.phone_num

        if (data.email) {
          this.updateMessage = response.message || 'Verification email sent! Please check your new email to verify.'
          this.updateType = 'success'
          this.editData.email = this.userData.email
        } else {
          this.updateMessage = response.message || 'Profile updated successfully!'
          this.updateType = 'success'
        }

        localStorage.setItem('userName', this.userData.name)
        localStorage.setItem('userData', JSON.stringify(this.userData))
        window.dispatchEvent(new CustomEvent('userDataUpdated'))

        this.editingField = null
      } catch (error) {
        console.error('Error updating profile:', error)

        if (error.response?.data?.errors) {
          const errors = error.response.data.errors
          const firstError = Object.values(errors)[0]?.[0]
          this.updateMessage = firstError || 'Validation failed'
        } else {
          this.updateMessage = error.response?.data?.message || 'Failed to update profile.'
        }
        this.updateType = 'error'
      } finally {
        this.isSaving = false
        setTimeout(() => this.updateMessage = '', 5000)
      }
    }
  }
}
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #f0f2f6;
  padding: 40px 0;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 20px;
}

.profile-container {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.loading-state, .error-state {
  text-align: center;
  padding: 80px 20px;
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

/* Profile Header */
.profile-header {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 32px 40px;
  background: #1a1a2e;
}

.profile-avatar {
  width: 100px;
  height: 100px;
  background: #ff6b35;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-text {
  font-size: 40px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 1px;
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 28px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 4px;
}

.profile-email {
  color: rgba(255, 255, 255, 0.7);
  font-size: 15px;
  margin-bottom: 4px;
}

.profile-phone {
  color: rgba(255, 255, 255, 0.7);
  font-size: 15px;
}

/* Tabs */
.profile-tabs-wrapper {
  background: #f8f9fa;
  border-bottom: 1px solid #e8ecf1;
  padding: 0 40px;
}

.profile-tabs {
  display: flex;
  gap: 8px;
}

.tab-btn {
  padding: 14px 24px;
  background: none;
  border: none;
  font-size: 15px;
  font-weight: 500;
  color: #8892a8;
  cursor: pointer;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
}

.tab-btn:hover {
  color: #1a1a2e;
}

.tab-btn--active {
  color: #ff6b35;
  border-bottom-color: #ff6b35;
}

/* Tab Content */
.tab-content {
  padding: 32px 40px;
}

/* Messages */
.message {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message.success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.message.error {
  background: #fdf2f2;
  color: #c62828;
  border: 1px solid #f8d7da;
}

.message.info {
  background: #e3f2fd;
  color: #1976d2;
  border: 1px solid #bbdefb;
}

.message-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
}

.message-close:hover {
  opacity: 1;
}

/* Profile Card */
.profile-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e8ecf1;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.profile-card-header {
  padding: 24px 28px;
  border-bottom: 1px solid #f0f2f6;
}

.profile-card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 2px;
}

.card-subtitle {
  font-size: 14px;
  color: #8892a8;
}

.profile-display {
  padding: 0;
}

.info-row {
  display: flex;
  align-items: center;
  padding: 18px 28px;
  border-bottom: 1px solid #f0f2f6;
  gap: 18px;
  transition: all 0.3s ease;
  position: relative;
}

.info-row:hover {
  background: #f8f9fc;
}

.info-row:last-child {
  border-bottom: none;
}

.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background: rgba(255, 107, 53, 0.08);
  border-radius: 12px;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.info-row:hover .info-icon {
  background: rgba(255, 107, 53, 0.15);
  transform: scale(1.05);
}

.info-content {
  flex: 1;
  min-width: 0;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #8892a8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.info-value {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a2e;
  display: flex;
  align-items: center;
}

.info-value span {
  display: inline-block;
  padding: 4px 0;
  background: transparent;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
  position: relative;
}

.info-value span::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, #ff6b35, #f7931e);
  transition: width 0.4s ease;
}

.info-value span:hover::after {
  width: 100%;
}

.info-value span:hover {
  color: #ff6b35;
}

.info-row:hover .info-value span {
  border-bottom-color: rgba(255, 107, 53, 0.2);
}

.info-value .not-set {
  color: #b0b8c8;
  font-style: italic;
}

.edit-input {
  width: 100%;
  max-width: 400px;
  padding: 8px 14px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 500;
  color: #1a1a2e;
  background: #fff;
  outline: none;
  transition: all 0.3s ease;
}

.edit-input:disabled {
  opacity: 0.6;
  background: #f5f5f5;
  cursor: not-allowed;
}

.edit-input:focus {
  border-color: #ff6b35;
  box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.08);
}

.edit-field-btn {
  background: none;
  border: none;
  color: #8892a8;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

.edit-field-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.edit-field-btn:hover:not(:disabled) {
  background: #f0f2f6;
  color: #ff6b35;
}

.edit-actions {
  display: flex;
  gap: 6px;
}

.save-field-btn, .cancel-field-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  min-height: 36px;
  transition: all 0.3s ease;
  position: relative;
}

.save-field-btn:disabled, .cancel-field-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.save-field-btn {
  color: #2ed573;
}

.save-field-btn:hover:not(:disabled) {
  background: #e8f5e9;
  transform: scale(1.05);
}

.cancel-field-btn {
  color: #ff4757;
}

.cancel-field-btn:hover:not(:disabled) {
  background: #fdf2f2;
  transform: scale(1.05);
}

/* Small button spinner */
.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(46, 213, 115, 0.3);
  border-top-color: #2ed573;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

.loading-overlay-content {
  background: #ffffff;
  padding: 40px 48px;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.loading-overlay-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e8ecf1;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

.loading-overlay-content p {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a2e;
  margin: 0;
}

/* Addresses */
.addresses-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.addresses-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
}

.address-count {
  font-size: 14px;
  color: #8892a8;
  font-weight: 500;
  padding: 6px 16px;
  background: #f8f9fa;
  border-radius: 50px;
  border: 1px solid #e8ecf1;
}

/* Loading small */
.loading-state-small {
  text-align: center;
  padding: 60px 20px;
  color: #8892a8;
}

.small-spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #e8ecf1;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #8892a8;
}

.empty-state p {
  font-size: 16px;
  margin-bottom: 4px;
}

.empty-state small {
  font-size: 13px;
  color: #a0a8b8;
}

/* Address card */
.address-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.address-card {
  padding: 18px 24px;
  border: 2px solid #e8ecf1;
  border-radius: 12px;
  transition: all 0.3s ease;
  background: #ffffff;
}

.address-card:hover {
  border-color: #d0d4dc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.address-card--primary {
  border-color: #ff6b35;
  background: #fffaf7;
}

.address-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.address-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.primary-badge {
  display: inline-block;
  padding: 2px 12px;
  background: #ff6b35;
  color: #fff;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
}

.address-type {
  display: inline-block;
  padding: 2px 12px;
  background: #f0f2f6;
  color: #1a1a2e;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 500;
}

.address-content p {
  margin-bottom: 2px;
}

.address-street {
  font-size: 15px;
  font-weight: 500;
  color: #1a1a2e;
}

.address-location {
  font-size: 14px;
  color: #8892a8;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background: #fff;
  border-radius: 20px;
  max-width: 480px;
  width: 90%;
  padding: 40px 36px;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-content {
  text-align: center;
}

.modal-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.modal-icon svg {
  background: rgba(255, 107, 53, 0.08);
  padding: 12px;
  border-radius: 50%;
}

.modal-container h3 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 12px;
}

.modal-container p {
  font-size: 15px;
  color: #8892a8;
  margin-bottom: 8px;
  line-height: 1.6;
}

.modal-container p strong {
  color: #1a1a2e;
}

.modal-hint {
  font-size: 13px;
  color: #b0b8c8;
  margin-bottom: 24px;
  background: #f8f9fa;
  padding: 12px 16px;
  border-radius: 8px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.modal-btn {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-btn-secondary {
  background: #f0f2f6;
  color: #1a1a2e;
}

.modal-btn-secondary:hover:not(:disabled) {
  background: #e8ecf1;
}

.modal-btn-primary {
  background: #ff6b35;
  color: #fff;
}

.modal-btn-primary:hover:not(:disabled) {
  background: #e85a2a;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding: 24px 20px;
  }

  .profile-avatar {
    width: 80px;
    height: 80px;
  }

  .avatar-text {
    font-size: 32px;
  }

  .profile-name {
    font-size: 22px;
  }

  .profile-tabs-wrapper {
    padding: 0 16px;
    overflow-x: auto;
  }

  .profile-tabs {
    gap: 4px;
  }

  .tab-btn {
    padding: 12px 16px;
    font-size: 14px;
    white-space: nowrap;
  }

  .tab-content {
    padding: 20px 16px;
  }

  .info-row {
    padding: 14px 16px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .info-icon {
    width: 38px;
    height: 38px;
  }

  .info-value {
    font-size: 15px;
  }

  .edit-input {
    max-width: 100%;
  }

  .addresses-header {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }

  .address-card {
    padding: 14px 16px;
  }

  .loading-overlay-content {
    padding: 32px 24px;
  }

  .modal-container {
    padding: 32px 24px;
  }

  .modal-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .profile-avatar {
    width: 64px;
    height: 64px;
  }

  .avatar-text {
    font-size: 24px;
  }

  .profile-name {
    font-size: 20px;
  }

  .container {
    padding: 0 12px;
  }

  .tab-content {
    padding: 16px 12px;
  }

  .profile-card-header {
    padding: 16px;
  }

  .info-row {
    padding: 12px 12px;
    gap: 10px;
  }

  .info-icon {
    width: 34px;
    height: 34px;
  }

  .info-value {
    font-size: 14px;
  }

  .address-count {
    font-size: 13px;
    padding: 4px 12px;
  }

  .loading-overlay-content {
    padding: 24px 16px;
  }

  .loading-overlay-spinner {
    width: 36px;
    height: 36px;
  }

  .modal-container {
    padding: 24px 20px;
  }
}
</style>
