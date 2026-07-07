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
                📞 {{ userData.phone_num }}
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
              <div v-if="updateMessage" class="message-container" :class="updateType">
                <span class="message-icon">{{ updateType === 'success' ? '✓' : updateType === 'error' ? '✕' : 'ℹ' }}</span>
                <span class="message-text">{{ updateMessage }}</span>
                <button class="message-close" @click="updateMessage = ''">×</button>
              </div>

              <form @submit.prevent="updateProfile" class="profile-form">
                <h2 class="form-title">Personal Information</h2>

                <div class="form-group">
                  <label for="name">Full Name</label>
                  <input
                    type="text"
                    id="name"
                    v-model="editData.name"
                    required
                    class="form-input"
                    placeholder="Enter your full name"
                  >
                </div>

                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input
                    type="email"
                    id="email"
                    v-model="editData.email"
                    class="form-input"
                    placeholder="Enter your email address"
                    disabled
                  >
                  <small class="form-hint">Email cannot be changed</small>
                </div>

                <div class="form-group">
                  <label for="phone">Phone Number</label>
                  <input
                    type="tel"
                    id="phone"
                    v-model="editData.phone_num"
                    class="form-input"
                    placeholder="Enter your phone number"
                  >
                </div>

                <div class="form-actions">
                  <button type="submit" class="save-btn" :disabled="isSaving">
                    {{ isSaving ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Addresses Tab -->
            <div v-if="activeTab === 'addresses'" class="tab-panel">
              <div class="addresses-header">
                <h2 class="form-title">My Addresses</h2>
                <button @click="showAddressForm = true" class="add-address-btn">+ Add Address</button>
              </div>

              <!-- Address Form -->
              <div v-if="showAddressForm" class="address-form-container">
                <form @submit.prevent="submitAddress" class="address-form">
                  <h3>{{ editingAddress ? 'Edit Address' : 'Add New Address' }}</h3>

                  <div class="form-group">
                    <label for="address">Address <span class="required">*</span></label>
                    <input
                      type="text"
                      id="address"
                      v-model="addressForm.address"
                      required
                      class="form-input"
                      placeholder="Street address"
                    >
                  </div>

                  <div class="form-group">
                    <label for="region">Region <span class="required">*</span></label>
                    <select
                      id="region"
                      v-model="addressForm.region"
                      required
                      class="form-input"
                      @change="onRegionChange"
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
                  </div>

                  <div class="form-group">
                    <label for="city">City <span class="required">*</span></label>
                    <select
                      id="city"
                      v-model="addressForm.city"
                      required
                      class="form-input"
                      :disabled="!addressForm.region"
                      @change="onCityChange"
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
                  </div>

                  <div class="form-group">
                    <label for="township">Township <span class="required">*</span></label>
                    <select
                      id="township"
                      v-model="addressForm.township"
                      required
                      class="form-input"
                      :disabled="!addressForm.city"
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
                  </div>

                  <div class="form-group">
                    <label for="address_type">Address Type <span class="required">*</span></label>
                    <select id="address_type" v-model="addressForm.address_type" required class="form-input">
                      <option value="">Select address type</option>
                      <option value="1">Home</option>
                      <option value="2">Office</option>
                      <option value="3">Other</option>
                    </select>
                  </div>

                  <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="addressForm.is_primary">
                      Set as primary address
                    </label>
                  </div>

                  <div class="address-form-actions">
                    <button type="submit" class="save-btn" :disabled="isSubmittingAddress">
                      {{ isSubmittingAddress ? 'Saving...' : (editingAddress ? 'Update Address' : 'Add Address') }}
                    </button>
                    <button type="button" @click="cancelAddressForm" class="cancel-btn">Cancel</button>
                  </div>
                </form>
              </div>

              <!-- Address List -->
              <div class="address-list">
                <div v-if="addresses.length === 0" class="empty-state">
                  <p>No addresses added yet.</p>
                </div>
                <div
                  v-for="address in addresses"
                  :key="address.id"
                  class="address-card"
                  :class="{ 'address-card--primary': address.is_primary }"
                >
                  <div class="address-card-header">
                    <div class="address-type">
                      <span v-if="address.is_primary" class="primary-badge">Primary</span>
                      <span class="address-type-badge">{{ getAddressTypeLabel(address.address_type) }}</span>
                    </div>
                    <div class="address-actions">
                      <button @click="editAddress(address)" class="edit-btn">Edit</button>
                      <button @click="deleteAddress(address.id)" class="delete-btn">Delete</button>
                    </div>
                  </div>
                  <div class="address-content">
                    <p class="address-line">{{ address.address }}</p>
                    <p class="address-line">{{ address.township }}</p>
                    <p class="address-line">{{ address.city }}, {{ address.region }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
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
      isSaving: false,
      isSubmittingAddress: false,
      activeTab: 'profile',
      tabs: [
        { key: 'profile', label: 'Personal Information' },
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

      // Service Areas - From Database
      regions: [],
      allCities: [],
      allTownships: [],
      filteredCities: [],
      filteredTownships: [],
      isLoadingServiceAreas: false,

      // Addresses
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
      }
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
        console.log('User data:', response)

        const user = response.user || response.customer || response

        this.userData = {
          name: user.name || '',
          email: user.email || '',
          phone_num: user.phone_num || user.phone || '',
          status: user.status || 1
        }

        this.editData = {
          name: this.userData.name,
          email: this.userData.email,
          phone_num: this.userData.phone_num
        }

        localStorage.setItem('userName', this.userData.name)
        localStorage.setItem('userEmail', this.userData.email)
        localStorage.setItem('userPhone', this.userData.phone_num)
        localStorage.setItem('userData', JSON.stringify(this.userData))

        window.dispatchEvent(new CustomEvent('userDataUpdated'))

      } catch (error) {
        console.error('Error loading user data:', error)
        this.error = error.response?.data?.message || 'Failed to load profile data.'
      } finally {
        this.loading = false
      }
    },

    async updateProfile() {
      this.isSaving = true
      this.updateMessage = ''

      try {
        const data = {}

        if (this.editData.name !== this.userData.name) {
          data.name = this.editData.name
        }

        if (this.editData.phone_num !== this.userData.phone_num) {
          data.phone_num = this.editData.phone_num
        }

        if (Object.keys(data).length === 0) {
          this.updateMessage = 'No changes to save'
          this.updateType = 'info'
          this.isSaving = false
          setTimeout(() => {
            this.updateMessage = ''
          }, 3000)
          return
        }

        const response = await authService.updateProfile(data)
        console.log('Update response:', response)

        this.userData.name = this.editData.name
        this.userData.phone_num = this.editData.phone_num

        localStorage.setItem('userName', this.userData.name)
        localStorage.setItem('userPhone', this.userData.phone_num)
        localStorage.setItem('userData', JSON.stringify(this.userData))

        window.dispatchEvent(new CustomEvent('userDataUpdated'))

        this.updateMessage = response.message || 'Profile updated successfully!'
        this.updateType = 'success'
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
        setTimeout(() => {
          this.updateMessage = ''
        }, 5000)
      }
    },

    async loadServiceAreas() {
      this.isLoadingServiceAreas = true

      try {
        console.log('Fetching service areas from database...')

        // Get data from API - this returns actual database data
        const data = await serviceAreasService.getServiceAreas()
        console.log('Service areas data from database:', data)

        // Extract data from response
        const regionData = data.region || []
        const cityData = data.city || []
        const townshipData = data.township || []

        // Set the data from database
        this.regions = regionData
        this.allCities = cityData
        this.allTownships = townshipData

        console.log('✅ Regions from database:', this.regions)
        console.log('✅ Cities from database:', this.allCities)
        console.log('✅ Townships from database:', this.allTownships)

        if (this.regions.length === 0) {
          console.warn('No regions found in database. Check service_areas table.')
        }

      } catch (error) {
        console.error('Error loading service areas:', error)
        // Set empty arrays on error - no fallback
        this.regions = []
        this.allCities = []
        this.allTownships = []
        this.error = 'Failed to load service areas from database.'
      } finally {
        this.isLoadingServiceAreas = false
      }
    },

    onRegionChange() {
      // Show all cities from database when region is selected
      this.filteredCities = [...this.allCities]
      this.addressForm.city = ''
      this.addressForm.township = ''
      this.filteredTownships = []
    },

    onCityChange() {
      // Show all townships from database when city is selected
      this.filteredTownships = [...this.allTownships]
      this.addressForm.township = ''
    },

    async loadAddresses() {
      try {
        const response = await authService.getUser()
        const user = response.user || response.customer || response
        this.addresses = user.addresses || []
        console.log('Addresses loaded:', this.addresses)
      } catch (error) {
        console.error('Error loading addresses:', error)
        this.addresses = []
      }
    },

    getAddressTypeLabel(type) {
      const types = {
        1: 'Home',
        2: 'Office',
        3: 'Other'
      }
      return types[type] || 'Unknown'
    },

    async submitAddress() {
      // Validate required fields
      if (!this.addressForm.address || !this.addressForm.region ||
          !this.addressForm.city || !this.addressForm.township ||
          !this.addressForm.address_type) {
        alert('Please fill in all required fields.')
        return
      }

      if (this.editingAddress) {
        await this.updateAddress()
      } else {
        await this.addAddress()
      }
    },

    async addAddress() {
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

        console.log('Adding address:', data)

        await addressService.addAddress(data)
        await this.loadAddresses()
        this.cancelAddressForm()
        alert('Address added successfully!')
      } catch (error) {
        console.error('Error adding address:', error)
        alert(error.response?.data?.message || 'Failed to add address.')
      } finally {
        this.isSubmittingAddress = false
      }
    },

    async updateAddress() {
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

        console.log('Updating address:', data)

        await addressService.updateAddress(this.editingAddress.id, data)
        await this.loadAddresses()
        this.cancelAddressForm()
        alert('Address updated successfully!')
      } catch (error) {
        console.error('Error updating address:', error)
        alert(error.response?.data?.message || 'Failed to update address.')
      } finally {
        this.isSubmittingAddress = false
      }
    },

    editAddress(address) {
      this.editingAddress = address
      this.addressForm = {
        address: address.address || '',
        region: address.region || '',
        city: address.city || '',
        township: address.township || '',
        address_type: String(address.address_type || ''),
        is_primary: Boolean(address.is_primary)
      }

      // Populate filtered cities and townships
      if (address.region) {
        this.onRegionChange()
        if (address.city) {
          this.onCityChange()
        }
      }

      this.showAddressForm = true
    },

    async deleteAddress(id) {
      if (!confirm('Are you sure you want to delete this address?')) return

      try {
        await addressService.deleteAddress(id)
        this.addresses = this.addresses.filter(a => a.id !== id)
        alert('Address deleted successfully!')
      } catch (error) {
        console.error('Error deleting address:', error)
        alert(error.response?.data?.message || 'Failed to delete address.')
      }
    },

    cancelAddressForm() {
      this.showAddressForm = false
      this.editingAddress = null
      this.isSubmittingAddress = false
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
    }
  }
}
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 40px 0;
}

.profile-container {
  max-width: 900px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.loading-state {
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

.loading-state p {
  color: #8892a8;
  font-size: 14px;
}

.error-state {
  text-align: center;
  padding: 40px 20px;
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

.profile-header {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 40px;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  color: #fff;
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
  color: #fff;
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 4px;
}

.profile-email {
  color: rgba(255,255,255,0.7);
  margin-bottom: 4px;
}

.profile-phone {
  color: rgba(255,255,255,0.7);
  margin-bottom: 8px;
  font-size: 15px;
}

.profile-status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-active {
  background: #4caf50;
  color: #fff;
}

.status-inactive {
  background: #e74c3c;
  color: #fff;
}

.profile-tabs {
  display: flex;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
  padding: 0 20px;
  gap: 4px;
}

.tab-btn {
  padding: 16px 24px;
  background: none;
  border: none;
  font-size: 15px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
}

.tab-btn:hover {
  color: #ff6b35;
}

.tab-btn--active {
  color: #ff6b35;
  border-bottom-color: #ff6b35;
}

.tab-content {
  padding: 40px;
}

.tab-panel {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message-container {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 8px;
  margin-bottom: 24px;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message-container.success {
  background: #e8f5e9;
  border: 1px solid #c8e6c9;
  color: #2e7d32;
}

.message-container.error {
  background: #fdf2f2;
  border: 1px solid #f8d7da;
  color: #c62828;
}

.message-container.info {
  background: #e3f2fd;
  border: 1px solid #bbdefb;
  color: #1976d2;
}

.message-icon {
  font-size: 18px;
  font-weight: 700;
  flex-shrink: 0;
}

.message-text {
  flex: 1;
  font-size: 14px;
  line-height: 1.5;
}

.message-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: inherit;
  padding: 0 4px;
  opacity: 0.6;
  transition: opacity 0.3s;
}

.message-close:hover {
  opacity: 1;
}

.form-title {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 24px;
}

.profile-form,
.address-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.required {
  color: #e74c3c;
}

.form-hint {
  font-size: 12px;
  color: #8892a8;
}

.form-input {
  padding: 12px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 15px;
  transition: border-color 0.3s, box-shadow 0.3s;
  font-family: inherit;
  width: 100%;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.form-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
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
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #ff6b35;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
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

.addresses-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.add-address-btn {
  padding: 10px 20px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.add-address-btn:hover {
  background: #e85a2a;
}

.address-form-container {
  background: #f8f9fa;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.address-form-container h3 {
  margin-bottom: 16px;
  font-size: 18px;
  color: #1a1a2e;
}

.address-form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.address-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.address-card {
  padding: 20px;
  border: 2px solid #e8ecf1;
  border-radius: 12px;
  transition: border-color 0.3s;
}

.address-card--primary {
  border-color: #ff6b35;
  background: #fffaf7;
}

.address-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.primary-badge {
  display: inline-block;
  padding: 2px 12px;
  background: #ff6b35;
  color: #fff;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  margin-right: 8px;
}

.address-type-badge {
  display: inline-block;
  padding: 2px 12px;
  background: #e8ecf1;
  color: #666;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 500;
}

.address-actions {
  display: flex;
  gap: 8px;
}

.edit-btn {
  padding: 4px 12px;
  background: #e3f2fd;
  color: #1976d2;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.edit-btn:hover {
  background: #bbdefb;
}

.delete-btn {
  padding: 4px 12px;
  background: #fdf2f2;
  color: #e74c3c;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.delete-btn:hover {
  background: #fce4e4;
}

.address-content {
  color: #444;
}

.address-line {
  margin-bottom: 4px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #8892a8;
}

.empty-state p {
  margin-bottom: 12px;
}

@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding: 24px;
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
    padding: 24px 16px;
  }

  .addresses-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .message-container {
    padding: 12px 14px;
  }
}
</style>
