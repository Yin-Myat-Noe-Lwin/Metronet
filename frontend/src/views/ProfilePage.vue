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
              <span class="profile-status" :class="userData.status === 1 ? 'status-active' : 'status-inactive'">
                {{ userData.status === 1 ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>

          <!-- Profile Tabs -->
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

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Personal Information Tab -->
            <div v-if="activeTab === 'profile'" class="tab-panel">
              <div v-if="updateMessage" class="message" :class="updateType">
                {{ updateMessage }}
                <button class="message-close" @click="updateMessage = ''">×</button>
              </div>

              <!-- Profile Info Display -->
              <div class="profile-display">
                <div class="info-row">
                  <div class="info-label">Full Name</div>
                  <div class="info-value">
                    <span v-if="editingField !== 'name'">{{ userData.name || 'Not set' }}</span>
                    <input
                      v-else
                      v-model="editData.name"
                      class="edit-input"
                      placeholder="Enter your full name"
                      autofocus
                    >
                  </div>
                  <button
                    @click="toggleEdit('name')"
                    class="edit-field-btn"
                    v-if="editingField !== 'name'"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <div v-else class="edit-actions">
                    <button @click="saveField('name')" class="save-field-btn" :disabled="isSaving">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                      </svg>
                    </button>
                    <button @click="cancelEdit" class="cancel-field-btn">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="info-row">
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
                    >
                  </div>
                  <button
                    @click="toggleEdit('email')"
                    class="edit-field-btn"
                    v-if="editingField !== 'email'"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <div v-else class="edit-actions">
                    <button @click="saveField('email')" class="save-field-btn" :disabled="isSaving">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                      </svg>
                    </button>
                    <button @click="cancelEdit" class="cancel-field-btn">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">Phone Number</div>
                  <div class="info-value">
                    <span v-if="editingField !== 'phone'">{{ userData.phone_num || 'Not set' }}</span>
                    <input
                      v-else
                      v-model="editData.phone_num"
                      class="edit-input"
                      placeholder="Enter your phone number"
                      autofocus
                    >
                  </div>
                  <button
                    @click="toggleEdit('phone')"
                    class="edit-field-btn"
                    v-if="editingField !== 'phone'"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <div v-else class="edit-actions">
                    <button @click="saveField('phone')" class="save-field-btn" :disabled="isSaving">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                      </svg>
                    </button>
                    <button @click="cancelEdit" class="cancel-field-btn">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">Status</div>
                  <div class="info-value">
                    <span class="status-badge" :class="userData.status === 1 ? 'status-active' : 'status-inactive'">
                      {{ userData.status === 1 ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Addresses Tab -->
            <div v-if="activeTab === 'addresses'" class="tab-panel">
              <div class="addresses-header">
                <h3>My Addresses</h3>
                <button @click="toggleAddressForm" class="add-btn">
                  {{ showAddressForm ? 'Cancel' : '+ Add Address' }}
                </button>
              </div>

              <!-- Address Form -->
              <div v-if="showAddressForm" class="address-form-wrapper">
                <div v-if="loadingServiceAreas" class="loading-service-areas">
                  <div class="small-spinner"></div>
                  <span>Loading service areas...</span>
                </div>
                <form @submit.prevent="submitAddress" class="address-form" v-else>
                  <div class="form-group" :class="{ 'has-error': addressErrors.address }">
                    <label>Address <span class="required">*</span></label>
                    <input
                      v-model="addressForm.address"
                      class="form-input"
                      placeholder="Street address"
                      required
                      :class="{ 'input-error': addressErrors.address }"
                    >
                    <span v-if="addressErrors.address" class="field-error">{{ addressErrors.address }}</span>
                  </div>

                  <div class="form-group" :class="{ 'has-error': addressErrors.region }">
                    <label>Region <span class="required">*</span></label>
                    <select
                      v-model="addressForm.region"
                      class="form-input"
                      required
                      @change="onRegionChange"
                      :class="{ 'input-error': addressErrors.region }"
                    >
                      <option value="">Select Region</option>
                      <option
                        v-for="region in regions"
                        :key="region"
                        :value="region"
                      >
                        {{ region }}
                      </option>
                    </select>
                    <span v-if="addressErrors.region" class="field-error">{{ addressErrors.region }}</span>
                  </div>

                  <div class="form-group" :class="{ 'has-error': addressErrors.city }">
                    <label>City <span class="required">*</span></label>
                    <select
                      v-model="addressForm.city"
                      class="form-input"
                      required
                      :disabled="!addressForm.region"
                      @change="onCityChange"
                      :class="{ 'input-error': addressErrors.city }"
                    >
                      <option value="">Select City</option>
                      <option
                        v-for="city in filteredCities"
                        :key="city"
                        :value="city"
                      >
                        {{ city }}
                      </option>
                    </select>
                    <span v-if="addressErrors.city" class="field-error">{{ addressErrors.city }}</span>
                  </div>

                  <div class="form-group" :class="{ 'has-error': addressErrors.township }">
                    <label>Township <span class="required">*</span></label>
                    <select
                      v-model="addressForm.township"
                      class="form-input"
                      required
                      :disabled="!addressForm.city"
                      :class="{ 'input-error': addressErrors.township }"
                    >
                      <option value="">Select Township</option>
                      <option
                        v-for="township in filteredTownships"
                        :key="township"
                        :value="township"
                      >
                        {{ township }}
                      </option>
                    </select>
                    <span v-if="addressErrors.township" class="field-error">{{ addressErrors.township }}</span>
                  </div>

                  <div class="form-group" :class="{ 'has-error': addressErrors.address_type }">
                    <label>Address Type <span class="required">*</span></label>
                    <select
                      v-model="addressForm.address_type"
                      class="form-input"
                      required
                      :class="{ 'input-error': addressErrors.address_type }"
                    >
                      <option value="">Select type</option>
                      <option value="1">Home</option>
                      <option value="2">Office</option>
                      <option value="3">Business</option>
                    </select>
                    <span v-if="addressErrors.address_type" class="field-error">{{ addressErrors.address_type }}</span>
                  </div>

                  <!-- Primary Address Checkbox - Only show when creating new address -->
                  <div v-if="!editingAddress" class="form-group checkbox-group">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="addressForm.is_primary">
                      Set as primary address
                    </label>
                  </div>

                  <div class="address-form-actions">
                    <button type="submit" class="save-btn" :disabled="isSubmittingAddress">
                      {{ isSubmittingAddress ? 'Saving...' : (editingAddress ? 'Update Address' : 'Save Address') }}
                    </button>
                    <button type="button" @click="cancelAddressForm" class="cancel-btn">Cancel</button>
                  </div>
                </form>
              </div>

              <!-- Address List -->
              <div class="address-list">
                <div v-if="addresses.length === 0" class="empty-state">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#8892a8" stroke-width="1.5">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                  <p>No addresses added yet.</p>
                  <small>Click "Add Address" to add your first address</small>
                </div>
                <div
                  v-for="address in addresses"
                  :key="address.id"
                  class="address-card"
                  :class="{ 'address-card--primary': address.is_primary }"
                >
                  <div class="address-card-header">
                    <div>
                      <span v-if="address.is_primary" class="primary-badge">Primary</span>
                      <span class="address-type">{{ getAddressTypeLabel(address.address_type) }}</span>
                    </div>
                    <div class="address-actions">
                      <button @click="editAddress(address)" class="edit-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit
                      </button>
                      <button @click="deleteAddress(address.id)" class="delete-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M3 6h18"/>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Delete
                      </button>
                    </div>
                  </div>
                  <div class="address-content">
                    <p>{{ address.address }}</p>
                    <p>{{ address.township }}</p>
                    <p>{{ address.city }}, {{ address.region }}</p>
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
            <button @click="closeEmailModal" class="modal-btn modal-btn-secondary">
              Cancel
            </button>
            <button @click="confirmEmailChange" class="modal-btn modal-btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Sending...' : 'Send Verification' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Error Modal -->
    <div v-if="showAddressErrorModal" class="modal-overlay" @click.self="closeAddressErrorModal">
      <div class="modal-container modal-error">
        <div class="modal-content">
          <div class="modal-icon error-modal-icon">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <h3 style="color: #c62828;">{{ addressModalTitle || 'Error' }}</h3>
          <p>{{ addressModalMessage }}</p>
          <div class="modal-actions">
            <button @click="closeAddressErrorModal" class="modal-btn modal-btn-primary" style="background: #ff4757;">
              Got it
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Success Modal -->
    <div v-if="showAddressSuccessModal" class="modal-overlay" @click.self="closeAddressSuccessModal">
      <div class="modal-container modal-success">
        <div class="modal-content">
          <div class="modal-icon success-modal-icon">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#2ed573" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <h3 style="color: #2e7d32;">Success!</h3>
          <p>{{ addressModalMessage }}</p>
          <div class="modal-actions">
            <button @click="closeAddressSuccessModal" class="modal-btn modal-btn-primary" style="background: #2ed573;">
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { authService, addressService, serviceAreasService } from '../services/api'

export default {
  name: 'ProfilePage',
  data() {
    return {
      loading: false,
      error: null,
      editingField: null,
      isSaving: false,
      isSubmittingAddress: false,
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
      addresses: [],
      showAddressForm: false,
      editingAddress: null,
      addressForm: {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: '',
        is_primary: false
      },
      // Address Modal
      showAddressErrorModal: false,
      showAddressSuccessModal: false,
      addressModalTitle: '',
      addressModalMessage: '',
      // Address Errors
      addressFormError: '',
      addressErrors: {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: ''
      },
      // Service Areas
      regions: [],
      allCities: [],
      allTownships: [],
      filteredCities: [],
      filteredTownships: [],
      loadingServiceAreas: false,
      serviceAreasError: null
    }
  },
  computed: {
    userInitials() {
      if (!this.userData.name) return 'U'
      return this.userData.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    }
  },
  mounted() {
    this.loadUserData()
    this.loadAddresses()
    this.loadServiceAreas()
  },
  methods: {
    async loadUserData() {
      this.loading = true
      this.error = null

      try {
        const response = await authService.getUser()
        const user = response.user || response.customer || response

        this.userData = {
          name: user.name || '',
          email: user.email || '',
          phone_num: user.phone_number || user.phone || '',
          status: user.status || 1
        }

        this.editData = {
          name: this.userData.name,
          email: this.userData.email,
          phone_num: this.userData.phone_num
        }

        localStorage.setItem('userName', this.userData.name)
        localStorage.setItem('userEmail', this.userData.email)
        localStorage.setItem('userData', JSON.stringify(this.userData))
        window.dispatchEvent(new CustomEvent('userDataUpdated'))

      } catch (error) {
        console.error('Error loading user data:', error)
        this.error = error.response?.data?.message || 'Failed to load profile data.'
      } finally {
        this.loading = false
      }
    },

    async loadServiceAreas() {
      this.loadingServiceAreas = true
      this.serviceAreasError = null

      try {
        const response = await serviceAreasService.getServiceAreas()
        console.log('Service areas response:', response)

        const data = response.data || response

        this.regions = data.region || []
        this.allCities = data.city || []
        this.allTownships = data.township || []

        this.filteredCities = [...this.allCities]
        this.filteredTownships = [...this.allTownships]

        console.log('Regions loaded:', this.regions.length)
        console.log('Cities loaded:', this.allCities.length)
        console.log('Townships loaded:', this.allTownships.length)

        if (this.regions.length === 0) {
          console.warn('No service areas found in the database.')
          this.serviceAreasError = 'No service areas available. Please contact support.'
        }

      } catch (error) {
        console.error('Error loading service areas:', error)
        this.serviceAreasError = error.response?.data?.message || 'Failed to load service areas.'
        this.regions = []
        this.allCities = []
        this.allTownships = []
        this.filteredCities = []
        this.filteredTownships = []
      } finally {
        this.loadingServiceAreas = false
      }
    },

    onRegionChange() {
      this.filteredCities = [...this.allCities]
      this.addressForm.city = ''
      this.addressForm.township = ''
      this.filteredTownships = []
      this.clearFieldError('region')
    },

    onCityChange() {
      this.filteredTownships = [...this.allTownships]
      this.addressForm.township = ''
      this.clearFieldError('city')
    },

    validateAddressForm() {
      let isValid = true
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: ''
      }
      this.addressFormError = ''

      if (!this.addressForm.address) {
        this.addressErrors.address = 'Address is required'
        isValid = false
      }

      if (!this.addressForm.region) {
        this.addressErrors.region = 'Please select a region'
        isValid = false
      }

      if (!this.addressForm.city) {
        this.addressErrors.city = 'Please select a city'
        isValid = false
      }

      if (!this.addressForm.township) {
        this.addressErrors.township = 'Please select a township'
        isValid = false
      }

      if (!this.addressForm.address_type) {
        this.addressErrors.address_type = 'Please select an address type'
        isValid = false
      }

      return isValid
    },

    clearFieldError(field) {
      if (this.addressErrors[field]) {
        this.addressErrors[field] = ''
      }
    },

    // Address Modal Methods
    showAddressError(message, title = 'Error') {
      this.addressModalTitle = title
      this.addressModalMessage = message
      this.showAddressErrorModal = true
    },

    closeAddressErrorModal() {
      this.showAddressErrorModal = false
    },

    showAddressSuccess(message) {
      this.addressModalMessage = message
      this.showAddressSuccessModal = true
    },

    closeAddressSuccessModal() {
      this.showAddressSuccessModal = false
      this.loadAddresses()
      this.cancelAddressForm()
    },

    toggleAddressForm() {
      if (this.showAddressForm) {
        this.cancelAddressForm()
      } else {
        this.showAddressForm = true
        // Reset form to empty when adding new
        this.editingAddress = null
        this.addressForm = {
          address: '',
          region: '',
          city: '',
          township: '',
          address_type: '',
          is_primary: false
        }
        this.filteredCities = [...this.allCities]
        this.filteredTownships = [...this.allTownships]
        this.addressFormError = ''
        this.addressErrors = {
          address: '',
          region: '',
          city: '',
          township: '',
          address_type: ''
        }
      }
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
    },

    async loadAddresses() {
      try {
        const response = await addressService.viewAddresses()
        console.log('Addresses response:', response)

        const data = response.data || response
        this.addresses = data || []

        console.log('Addresses loaded:', this.addresses.length)
      } catch (error) {
        console.error('Error loading addresses:', error)
        this.addresses = []
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

    async submitAddress() {
      this.addressFormError = ''
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: ''
      }

      if (!this.validateAddressForm()) {
        const firstError = document.querySelector('.has-error')
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
      }

      this.isSubmittingAddress = true

      try {
        const data = {
          address: this.addressForm.address,
          region: this.addressForm.region,
          city: this.addressForm.city,
          township: this.addressForm.township,
          address_type: parseInt(this.addressForm.address_type),
          is_primary: this.addressForm.is_primary ? 1 : 0
        }

        console.log('Submitting address data:', data)

        let response
        if (this.editingAddress) {
          response = await addressService.updateAddress(this.editingAddress.id, data)
        } else {
          response = await addressService.addAddress(data)
        }

        console.log('Address response:', response)

        const action = this.editingAddress ? 'updated' : 'added'
        this.showAddressSuccess(`Address ${action} successfully!`)

      } catch (error) {
        console.error('Error saving address:', error)

        const errorMessage = error.response?.data?.message || 'Failed to save address.'

        if (errorMessage.toLowerCase().includes('invalid') ||
            errorMessage.toLowerCase().includes('combination') ||
            errorMessage.toLowerCase().includes('service area')) {
          this.showAddressError(
            'Invalid service area combination. Please select a valid region, city, and township combination.',
            'Invalid Service Area'
          )
        } else if (error.response?.data?.errors) {
          const errors = error.response.data.errors
          let errorList = ''
          Object.keys(errors).forEach(key => {
            const label = {
              address: 'Address',
              region: 'Region',
              city: 'City',
              township: 'Township',
              address_type: 'Address Type'
            }[key] || key
            errorList += `• ${label}: ${errors[key][0]}\n`
          })
          this.showAddressError(errorList, 'Validation Error')
        } else {
          this.showAddressError(errorMessage, 'Error')
        }
      } finally {
        this.isSubmittingAddress = false
      }
    },

    editAddress(address) {
      console.log('Editing address:', address)

      this.editingAddress = address
      this.showAddressForm = true

      this.addressForm = {
        address: address.address || '',
        region: address.region || '',
        city: address.city || '',
        township: address.township || '',
        address_type: String(address.address_type || ''),
        is_primary: false // Always false when editing
      }

      console.log('Address form set:', this.addressForm)

      this.addressFormError = ''
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: ''
      }

      this.filteredCities = [...this.allCities]
      this.filteredTownships = [...this.allTownships]

      console.log('Filtered cities:', this.filteredCities)
      console.log('Filtered townships:', this.filteredTownships)
    },

    async deleteAddress(id) {
      if (!confirm('Are you sure you want to delete this address?')) return

      try {
        await addressService.deleteAddress(id)
        await this.loadAddresses()
        this.showAddressSuccess('Address deleted successfully!')
      } catch (error) {
        console.error('Error deleting address:', error)
        this.showAddressError(error.response?.data?.message || 'Failed to delete address.', 'Error')
      }
    },

    cancelAddressForm() {
      this.showAddressForm = false
      this.editingAddress = null
      this.addressForm = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: '',
        is_primary: false
      }
      this.filteredCities = []
      this.filteredTownships = []
      this.addressFormError = ''
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: ''
      }
    }
  }
}
</script>

<style scoped>
/* All styles remain the same as previous version */
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
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.loading-service-areas {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 20px;
  color: #8892a8;
  font-size: 14px;
}

.small-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e8ecf1;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.form-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.7;
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

.profile-header {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 32px 40px;
  background: #1a1a2e;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  background: #ff6b35;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-text {
  font-size: 32px;
  font-weight: 700;
  color: #ffffff;
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 24px;
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
  margin-bottom: 8px;
}

.profile-status {
  display: inline-block;
  padding: 4px 16px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.profile-status.status-active {
  background: #4caf50;
  color: #ffffff;
}

.profile-status.status-inactive {
  background: #e74c3c;
  color: #ffffff;
}

.profile-tabs {
  display: flex;
  background: #f8f9fa;
  border-bottom: 2px solid #e8ecf1;
  padding: 0 20px;
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

.tab-content {
  padding: 32px 40px;
}

.message {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
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

.profile-display {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-row {
  display: flex;
  align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid #f0f2f6;
  gap: 16px;
}

.info-row:last-of-type {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #8892a8;
  width: 140px;
  flex-shrink: 0;
  font-size: 14px;
}

.info-value {
  flex: 1;
  color: #1a1a2e;
  font-weight: 500;
  font-size: 15px;
  display: flex;
  align-items: center;
}

.edit-input {
  width: 100%;
  max-width: 400px;
  padding: 6px 12px;
  border: 2px solid #ff6b35;
  border-radius: 6px;
  font-size: 15px;
  font-weight: 500;
  color: #1a1a2e;
  background: #fff;
  outline: none;
  transition: all 0.3s;
}

.edit-input:focus {
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.edit-field-btn {
  background: none;
  border: none;
  color: #8892a8;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.3s;
  display: flex;
  align-items: center;
}

.edit-field-btn:hover {
  background: #f0f2f6;
  color: #1a1a2e;
}

.edit-actions {
  display: flex;
  gap: 4px;
}

.save-field-btn, .cancel-field-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  transition: all 0.3s;
}

.save-field-btn:hover:not(:disabled) {
  background: #e8f5e9;
}

.save-field-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cancel-field-btn:hover {
  background: #fdf2f2;
}

.status-badge {
  display: inline-block;
  padding: 3px 14px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.status-active {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.status-inactive {
  background: #fdf2f2;
  color: #c62828;
}

.addresses-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.addresses-header h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a2e;
}

.add-btn {
  padding: 10px 24px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.add-btn:hover {
  background: #e85a2a;
}

.address-form-wrapper {
  background: #f8f9fa;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid #e8ecf1;
}

.address-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 14px;
}

.required {
  color: #e74c3c;
}

.form-input {
  padding: 12px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 15px;
  transition: border-color 0.3s;
  font-family: inherit;
  background: #ffffff;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08);
}

.form-input.input-error {
  border-color: #e74c3c;
}

.has-error .form-input,
.has-error select.form-input {
  border-color: #e74c3c !important;
}

.has-error .form-input:focus,
.has-error select.form-input:focus {
  border-color: #e74c3c !important;
  box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.08) !important;
}

.field-error {
  color: #e74c3c;
  font-size: 12px;
  font-weight: 500;
  margin-top: 4px;
  display: block;
}

.checkbox-group {
  flex-direction: row;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 400;
  color: #1a1a2e;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #ff6b35;
}

.address-form-actions {
  display: flex;
  gap: 12px;
  margin-top: 4px;
}

.save-btn {
  padding: 12px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.save-btn:hover:not(:disabled) {
  background: #e85a2a;
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-btn {
  padding: 12px 32px;
  background: #e8ecf1;
  color: #666;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.cancel-btn:hover {
  background: #d0d0d0;
}

.address-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.address-card {
  padding: 16px 20px;
  border: 2px solid #e8ecf1;
  border-radius: 12px;
  transition: all 0.3s;
  background: #ffffff;
}

.address-card:hover {
  border-color: #d0d4dc;
}

.address-card--primary {
  border-color: #ff6b35;
  background: #fffaf7;
}

.address-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.primary-badge {
  display: inline-block;
  padding: 2px 14px;
  background: #ff6b35;
  color: #fff;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 600;
  margin-right: 8px;
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

.address-actions {
  display: flex;
  gap: 8px;
}

.edit-btn, .delete-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.edit-btn {
  background: #e3f2fd;
  color: #1976d2;
}

.edit-btn:hover {
  background: #bbdefb;
}

.delete-btn {
  background: #fdf2f2;
  color: #e74c3c;
}

.delete-btn:hover {
  background: #fce4e4;
}

.address-content {
  color: #444;
  font-size: 14px;
}

.address-content p {
  margin-bottom: 2px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #8892a8;
}

.empty-state svg {
  margin-bottom: 12px;
}

.empty-state p {
  font-size: 16px;
  margin-bottom: 4px;
}

.empty-state small {
  font-size: 13px;
  color: #a0a8b8;
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
}

.modal-btn-secondary {
  background: #f0f2f6;
  color: #1a1a2e;
}

.modal-btn-secondary:hover {
  background: #e8ecf1;
}

.modal-btn-primary {
  background: #ff6b35;
  color: #fff;
}

.modal-btn-primary:hover:not(:disabled) {
  background: #e85a2a;
}

.modal-btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-error .modal-icon svg {
  background: rgba(255, 71, 87, 0.1);
}

.modal-success .modal-icon svg {
  background: rgba(46, 213, 115, 0.1);
}

.modal-error .modal-btn-primary {
  background: #ff4757;
}

.modal-error .modal-btn-primary:hover {
  background: #e74c3c;
}

.modal-success .modal-btn-primary {
  background: #2ed573;
}

.modal-success .modal-btn-primary:hover {
  background: #26a65b;
}

.error-modal-icon {
  background: rgba(255, 71, 87, 0.1);
}

.success-modal-icon {
  background: rgba(46, 213, 115, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding: 24px 20px;
  }

  .profile-tabs {
    flex-wrap: wrap;
    padding: 0 12px;
  }

  .tab-btn {
    padding: 12px 16px;
    font-size: 14px;
  }

  .tab-content {
    padding: 20px 16px;
  }

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

  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 14px 0;
  }

  .info-label {
    width: auto;
  }

  .info-value {
    width: 100%;
  }

  .edit-input {
    max-width: 100%;
  }

  .edit-field-btn {
    align-self: flex-start;
  }

  .addresses-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .address-card-header {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }

  .address-actions {
    width: 100%;
  }

  .edit-btn, .delete-btn {
    flex: 1;
    justify-content: center;
  }

  .address-form-actions {
    flex-direction: column;
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
    width: 56px;
    height: 56px;
  }

  .avatar-text {
    font-size: 20px;
  }

  .profile-name {
    font-size: 18px;
  }

  .modal-container {
    padding: 24px 20px;
  }
}
</style>
