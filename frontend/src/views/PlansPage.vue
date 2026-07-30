<template>
  <div class="plans-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Choose Your Perfect Plan</h1>
        <p class="page-subtitle">Compare our internet plans and find the best fit for your needs</p>
      </div>
    </div>

    <section class="plans-section">
      <div class="container">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading plans...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>Unable to Load Plans</h3>
          <p>{{ error }}</p>
          <button @click="fetchPlans" class="retry-btn">Try Again</button>
        </div>

        <!-- Plans Content -->
        <template v-else>
          <!-- Sort Options -->
          <div class="plans-controls">
            <div class="sort-group">
              <label for="sort">Sort by:</label>
              <select id="sort" v-model="sortBy" class="sort-select">
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="speed">Speed</option>
              </select>
            </div>
          </div>

          <!-- Plans Grid -->
          <div class="plans-grid">
            <div
              v-for="plan in filteredAndSortedPlans"
              :key="plan.id"
              class="plan-card"
              :class="{ 'plan-card--loading': subscribing && selectedPlanId === plan.id }"
            >
              <div class="plan-header">
                <h3 class="plan-name">{{ plan.name }}</h3>
                <div class="plan-price">
                  <span class="price-amount">{{ formatPrice(plan.price) }}</span>
                  <span class="price-period">/month</span>
                </div>
                <div class="plan-speed">
                  <span>{{ plan.download_speed }} Mbps</span>
                </div>
              </div>

              <div class="plan-description" v-if="plan.description">
                {{ plan.description }}
              </div>

              <div class="plan-metrics">
                <div class="metric">
                  <span class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                      <polyline points="17 18 23 18 23 12"/>
                    </svg>
                  </span>
                  <span class="metric-label">Download</span>
                  <span class="metric-value">{{ plan.download_speed }} Mbps</span>
                </div>
                <div class="metric">
                  <span class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                      <polyline points="17 6 23 6 23 12"/>
                    </svg>
                  </span>
                  <span class="metric-label">Upload</span>
                  <span class="metric-value">{{ plan.upload_speed }} Mbps</span>
                </div>
              </div>

              <div class="plan-footer">
                <button
                  @click="openSubscribeModal(plan)"
                  class="plan-btn"
                  :disabled="subscribing && selectedPlanId === plan.id"
                  :class="{ 'plan-btn--loading': subscribing && selectedPlanId === plan.id }"
                >
                  <span v-if="subscribing && selectedPlanId === plan.id" class="btn-spinner"></span>
                  {{ subscribing && selectedPlanId === plan.id ? 'Processing...' : 'Subscribe Now' }}
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </section>

    <!-- Installation Address Modal -->
    <div v-if="showAddressModal" class="modal-overlay" @click.self="closeAllModals">
      <div class="modal-container address-modal">
        <div class="modal-header">
          <h3 class="modal-title">Installation Address</h3>
          <button class="modal-close" @click="closeAllModals">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <p class="address-modal-subtitle">Please provide your installation address for this subscription.</p>

          <!-- Loading Service Areas -->
          <div v-if="loadingServiceAreas" class="loading-service-areas">
            <div class="small-spinner"></div>
            <span>Loading service areas...</span>
          </div>

          <div v-else class="address-form">
            <div class="form-group" :class="{ 'has-error': addressErrors.address }">
              <label class="form-label">Street Address <span class="required">*</span></label>
              <input
                v-model="addressForm.address"
                type="text"
                class="form-input"
                placeholder="Enter your street address"
                :class="{ 'input-error': addressErrors.address }"
              />
              <span v-if="addressErrors.address" class="field-error">{{ addressErrors.address }}</span>
            </div>

            <div class="form-row">
              <div class="form-group" :class="{ 'has-error': addressErrors.region }">
                <label class="form-label">Region <span class="required">*</span></label>
                <select
                  v-model="addressForm.region"
                  class="form-input"
                  @change="onRegionChange"
                  :class="{ 'input-error': addressErrors.region }"
                >
                  <option value="">Select Region</option>
                  <option v-for="region in serviceRegions" :key="region" :value="region">
                    {{ region }}
                  </option>
                </select>
                <span v-if="addressErrors.region" class="field-error">{{ addressErrors.region }}</span>
              </div>

              <div class="form-group" :class="{ 'has-error': addressErrors.city }">
                <label class="form-label">City <span class="required">*</span></label>
                <select
                  v-model="addressForm.city"
                  class="form-input"
                  :disabled="!addressForm.region || loadingCities"
                  @change="onCityChange"
                  :class="{ 'input-error': addressErrors.city }"
                >
                  <option value="">Select City</option>
                  <option v-for="city in filteredCities" :key="city" :value="city">
                    {{ city }}
                  </option>
                </select>
                <div v-if="loadingCities" class="loading-indicator">Loading cities...</div>
                <span v-if="addressErrors.city" class="field-error">{{ addressErrors.city }}</span>
              </div>

              <div class="form-group" :class="{ 'has-error': addressErrors.township }">
                <label class="form-label">Township <span class="required">*</span></label>
                <select
                  v-model="addressForm.township"
                  class="form-input"
                  :disabled="!addressForm.city || loadingTownships"
                  :class="{ 'input-error': addressErrors.township }"
                >
                  <option value="">Select Township</option>
                  <option v-for="township in filteredTownships" :key="township" :value="township">
                    {{ township }}
                  </option>
                </select>
                <div v-if="loadingTownships" class="loading-indicator">Loading townships...</div>
                <span v-if="addressErrors.township" class="field-error">{{ addressErrors.township }}</span>
              </div>
            </div>

            <!-- Service Area Availability Indicator -->
            <div v-if="addressForm.region && addressForm.city && addressForm.township" class="service-available">
              <div class="service-available-content">
                <div class="service-available-text">
                  <strong>Service Available!</strong>
                  <span>This location is within our coverage area</span>
                </div>
              </div>
            </div>
          </div>

          <div class="address-actions">
            <button class="modal-btn btn-cancel" @click="closeAllModals">Cancel</button>
            <!-- enable subscribe button only when add the valid address -->
            <button class="modal-btn btn-subscribe" @click="saveAddressAndProceed" :disabled="savingAddress || !isAddressValid">
              <span v-if="savingAddress" class="btn-spinner"></span>
              {{ savingAddress ? 'Saving...' : 'Continue to Subscription' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Subscribe Modal  -->
    <div v-if="showSubscribeModal && selectedPlan" class="modal-overlay" @click.self="closeSubscribeModal">
      <div class="modal-container subscribe-modal">
        <div class="modal-header">
          <div class="modal-header-info">
            <h3 class="modal-title">Subscribe to {{ selectedPlan?.name }}</h3>
            <span class="modal-plan-price">{{ formatPrice(selectedPlan?.price) }}/month</span>
          </div>
          <button class="modal-close" @click="closeSubscribeModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- Installation Address Summary -->
          <div class="address-summary" v-if="savedAddress">
            <div class="address-summary-header">
              <span class="address-summary-label">Installation Address</span>
            </div>
            <p class="address-summary-text">{{ savedAddress.address }}</p>
            <p class="address-summary-location">{{ savedAddress.township }}, {{ savedAddress.city }}, {{ savedAddress.region }}</p>
          </div>

          <div class="plan-summary">
            <div class="summary-item">
              <span class="summary-label">Plan</span>
              <span class="summary-value">{{ selectedPlan?.name }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Speed</span>
              <span class="summary-value">{{ selectedPlan?.download_speed }} Mbps</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Monthly Price</span>
              <span class="summary-value">{{ formatPrice(selectedPlan?.price) }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="duration" class="form-label">Select Duration</label>
            <div class="duration-options">
              <button
                v-for="option in durationOptions"
                :key="option.value"
                class="duration-option"
                :class="{ 'duration-option--active': selectedDuration === option.value }"
                @click="selectedDuration = option.value"
              >
                <span class="duration-months">{{ option.value }} Month{{ option.value > 1 ? 's' : '' }}</span>
                <span class="duration-savings" v-if="option.savings > 0">Save {{ option.savings }}%</span>
                <span class="duration-price">{{ formatPrice(calculateDurationPrice(option.value)) }}</span>
              </button>
            </div>
          </div>

          <div class="total-cost">
            <div class="total-cost-left">
              <span class="total-label">Total Cost</span>
              <span class="total-sub-label" v-if="selectedDuration > 1">You save {{ calculateSavings(selectedDuration) }}%</span>
            </div>
            <span class="total-amount">{{ formatPrice(calculateTotalCost()) }}</span>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-cancel" @click="closeSubscribeModal" :disabled="subscribing">Cancel</button>
          <button class="modal-btn btn-subscribe" @click="confirmSubscription" :disabled="subscribing">
            <span v-if="subscribing" class="btn-spinner"></span>
            {{ subscribing ? 'Processing...' : 'Confirm Subscription' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Already Subscribed Modal -->
    <div v-if="showAlreadySubscribedModal" class="modal-overlay" @click.self="closeAlreadySubscribedModal">
      <div class="modal-container alert-modal">
        <div class="modal-header">
          <h3 class="modal-title">Already Subscribed</h3>
          <button class="modal-close" @click="closeAlreadySubscribedModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="alert-content">
            <p class="alert-text">
              You are already subscribed to this plan or have a pending approval.
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-cancel" @click="closeAlreadySubscribedModal">Close</button>
          <button class="modal-btn btn-subscribe" @click="goToSubscriptions">
            View Subscriptions
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
      <div class="modal-container alert-modal success-modal">
        <div class="modal-header">
          <h3 class="modal-title">Subscription Successful!</h3>
          <button class="modal-close" @click="closeSuccessModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="alert-content">
            <p class="alert-text">
              <strong>{{ successPlanName }}</strong> subscription submitted!
            </p>
            <p class="alert-subtext">
              You'll be notified once approved.
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-subscribe" @click="goToSubscriptions">
            View Subscriptions
          </button>
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
import { plansService, subscriptionsService, addressService, serviceAreasService } from '../services/api'

export default {
  name: 'PlansPage',
  data() {
    return {
      loading: false,
      error: null,
      subscribing: false,
      savingAddress: false,
      selectedPlanId: null,
      sortBy: 'price-low',
      plans: [],
      loadingServiceAreas: false,
      loadingCities: false,
      loadingTownships: false,
      citiesByRegion: {},
      townshipsByCity: {},
      hierarchy: {},

      // Address Modal
      showAddressModal: false,
      pendingPlan: null,
      addressForm: {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: 1 // Installation
      },
      addressErrors: {
        address: '',
        region: '',
        city: '',
        township: ''
      },
      serviceRegions: [],
      filteredCities: [],
      filteredTownships: [],
      savedAddress: null,

      // Subscribe Modal
      showSubscribeModal: false,
      selectedPlan: null,
      selectedDuration: 1,
      durationOptions: [
        { value: 1, savings: 0 },
        { value: 3, savings: 5 },
        { value: 6, savings: 10 },
        { value: 12, savings: 15 }
      ],

      // Already Subscribed Modal
      showAlreadySubscribedModal: false,
      alreadySubscribedPlan: null,

      // Success Modal
      showSuccessModal: false,
      successPlanName: '',

      // Toast
      toast: {
        show: false,
        message: '',
        type: 'success'
      },
      toastTimeout: null
    }
  },
  computed: {
    filteredAndSortedPlans() {
      let filtered = [...this.plans]

      switch(this.sortBy) {
        case 'price-low':
          return filtered.sort((a, b) => a.price - b.price)
        case 'price-high':
          return filtered.sort((a, b) => b.price - a.price)
        case 'speed':
          return filtered.sort((a, b) => b.download_speed - a.download_speed)
        default:
          return filtered
      }
    },
    isAddressValid() {
      return this.addressForm.address &&
              this.addressForm.region &&
              this.addressForm.city &&
              this.addressForm.township
    }
  },
  mounted() {
    this.fetchPlans()
    this.fetchServiceAreas()
  },
  methods: {
    async fetchPlans() {
      this.loading = true
      this.error = null

      try {
        const response = await plansService.getPlans()
        let plansData = response.data || response || []

        this.plans = plansData.map(plan => ({
          id: plan.id,
          name: plan.name || 'Unnamed Plan',
          description: plan.description || '',
          price: parseFloat(plan.price) || 0,
          download_speed: plan.download_speed || 0,
          upload_speed: plan.upload_speed || 0,
          status: plan.status || 0
        }))
      } catch (error) {
        console.error('Error fetching plans:', error)
        this.error = error.response?.data?.message || 'Failed to load plans. Please try again.'
        this.showToast('Failed to load plans', 'error')
      } finally {
        this.loading = false
      }
    },

    async fetchServiceAreas() {
      this.loadingServiceAreas = true
      try {
        // get service areas data
        const response = await serviceAreasService.getServiceAreas()
        const data = response.data || response

        console.log('Service areas response:', data)

        // if data has hierarchy
        if (data.hierarchy) {
          this.serviceRegions = data.regions || []
          this.hierarchy = data.hierarchy || {}

          // Build citiesByRegion
          this.citiesByRegion = {}
          Object.keys(this.hierarchy).forEach(region => {
            this.citiesByRegion[region] = Object.keys(this.hierarchy[region]) || []
          })

          // get townships By City
          this.townshipsByCity = {}
          Object.keys(this.hierarchy).forEach(region => {
            const cities = this.hierarchy[region]
            Object.keys(cities).forEach(city => {
              const key = `${region}_${city}`
              this.townshipsByCity[key] = cities[city] || []
            })
          })

          this.filteredCities = data.cities || []
          this.filteredTownships = data.townships || []
        } else {
          // if data is an array of service areas
          // this.buildMappings(data)
        }

      } catch (error) {
        console.error('Error fetching service areas:', error)
        this.showToast('Failed to load service areas', 'error')
      } finally {
        this.loadingServiceAreas = false
      }
    },

  onRegionChange() {
    // Get cities only for the selected region
    this.filteredCities = this.citiesByRegion[this.addressForm.region] || []

    // Reset city and township
    this.addressForm.city = ''
    this.addressForm.township = ''
    this.filteredTownships = []

    console.log('Region changed:', {
      region: this.addressForm.region,
      cities: this.filteredCities
    })

    this.clearFieldError('region')
  },

  onCityChange() {
    // Get townships only for the selected region + city
    const key = `${this.addressForm.region}_${this.addressForm.city}`
    this.filteredTownships = this.townshipsByCity[key] || []

    // Reset township
    this.addressForm.township = ''

    console.log('City changed:', {
      region: this.addressForm.region,
      city: this.addressForm.city,
      townships: this.filteredTownships
    })

    this.clearFieldError('city')
  },

    clearFieldError(field) {
      if (this.addressErrors[field]) {
        this.addressErrors[field] = ''
      }
    },

    validateAddressForm() {
      let isValid = true
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: ''
      }

      if (!this.addressForm.address || this.addressForm.address.trim() === '') {
        this.addressErrors.address = 'Street address is required'
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

      return isValid
    },

    openSubscribeModal(plan) {
      const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'

      if (!isLoggedIn) {
        this.$router.push({
          path: '/login',
          query: { plan: plan.id, return: '/plans' }
        })
        return
      }

      this.pendingPlan = plan
      this.showAddressModal = true
    },

    async saveAddressAndProceed() {
      if (!this.validateAddressForm()) {
        const firstError = document.querySelector('.has-error')
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
      }

      this.savingAddress = true

      try {
        const addressData = {
          address: this.addressForm.address,
          region: this.addressForm.region,
          city: this.addressForm.city,
          township: this.addressForm.township,
          address_type: 1 // Installation
        }

        const response = await addressService.addAddress(addressData)
        console.log('Address saved:', response)

        this.savedAddress = response.data || response

        // Close address modal and open subscribe modal
        this.showAddressModal = false
        this.selectedPlan = this.pendingPlan
        this.selectedDuration = 1
        this.showSubscribeModal = true

        this.showToast('Installation address saved successfully!', 'success')

      } catch (error) {
        console.error('Error saving address:', error)
        const errorMessage = error.response?.data?.message || 'Failed to save address. Please try again.'
        this.showToast(errorMessage, 'error')
      } finally {
        this.savingAddress = false
      }
    },

    closeAllModals() {
      this.showAddressModal = false
      this.pendingPlan = null
      this.savedAddress = null
      this.addressForm = {
        address: '',
        region: '',
        city: '',
        township: '',
        address_type: 1
      }
      this.addressErrors = {
        address: '',
        region: '',
        city: '',
        township: ''
      }
    },

    closeSubscribeModal() {
      if (this.subscribing) return
      this.showSubscribeModal = false
      this.selectedPlan = null
      this.selectedDuration = 1
      this.savedAddress = null
    },

    closeAlreadySubscribedModal() {
      this.showAlreadySubscribedModal = false
      this.alreadySubscribedPlan = null
    },

    closeSuccessModal() {
      this.showSuccessModal = false
      this.successPlanName = ''
    },

    goToProfile() {
      this.closeAllModals()
      this.$router.push({
        path: '/profile',
        query: { section: 'address' }
      })
    },

    goToSubscriptions() {
      this.showAlreadySubscribedModal = false
      this.showSuccessModal = false
      this.$router.push('/subscriptions')
    },

    async confirmSubscription() {
      if (!this.selectedPlan) return

      this.subscribing = true
      this.selectedPlanId = this.selectedPlan.id

      try {
        const subscriptionData = {
          duration_months: this.selectedDuration
        }

        const response = await subscriptionsService.createSubscription(
          this.selectedPlan.id,
          subscriptionData
        )

        console.log('Subscription response:', response)

        const planName = this.selectedPlan.name
        this.subscribing = false
        this.selectedPlanId = null
        this.closeSubscribeModal()

        this.successPlanName = planName
        this.showSuccessModal = true

      } catch (error) {
        console.error('Subscription error:', error)

        this.subscribing = false
        this.selectedPlanId = null
        this.closeSubscribeModal()

        const errorData = error.response?.data
        const statusCode = error.response?.status

        if (statusCode === 409 || (errorData?.error && errorData.error.includes('Already subscribed'))) {
          this.alreadySubscribedPlan = this.selectedPlan
          this.showAlreadySubscribedModal = true
          return
        }

        if (statusCode === 400 || (errorData?.error && errorData.error.includes('primary installation address'))) {
          this.showToast('Please set a primary installation address.', 'error')
          return
        }

        if (statusCode === 404 || (errorData?.error && errorData.error.includes('Plan not found'))) {
          this.showToast('Plan not found or inactive.', 'error')
          return
        }

        const errorMessage = errorData?.error || errorData?.message || 'Failed to subscribe. Please try again.'
        this.showToast(errorMessage, 'error')
      } finally {
        this.subscribing = false
        this.selectedPlanId = null
      }
    },

    calculateSavings(duration) {
      const savingsMap = {
        1: 0,
        3: 5,
        6: 10,
        12: 15
      }
      return savingsMap[duration] || 0
    },

    calculateDurationPrice(duration) {
      if (!this.selectedPlan) return 0
      const monthlyPrice = this.selectedPlan.price
      const savings = this.calculateSavings(duration)
      const discount = savings / 100
      return monthlyPrice * duration * (1 - discount)
    },

    calculateTotalCost() {
      return this.calculateDurationPrice(this.selectedDuration)
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
      if (this.toastTimeout) {
        clearTimeout(this.toastTimeout)
      }

      this.toast.message = message
      this.toast.type = type
      this.toast.show = true

      this.toastTimeout = setTimeout(() => {
        this.toast.show = false
      }, 4000)
    }
  }
}
</script>

<style scoped>
.plans-page {
  background: #f8f9fa;
  min-height: 100vh;
}

/* Loading State */
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

/* Error State */
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-state h3 {
  color: #1a1a2e;
  margin-bottom: 8px;
}

.error-state p {
  color: #8892a8;
  margin-bottom: 20px;
}

.retry-btn {
  padding: 10px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.retry-btn:hover {
  background: #e85a2a;
}

/* Page Header */
.page-header {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
  color: #fff;
  padding: 60px 0 50px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  background: rgba(255, 107, 53, 0.06);
  border-radius: 50%;
}

.page-header::after {
  content: '';
  position: absolute;
  bottom: -40%;
  left: -10%;
  width: 300px;
  height: 300px;
  background: rgba(255, 107, 53, 0.04);
  border-radius: 50%;
}

.page-title {
  font-size: 44px;
  font-weight: 800;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
  position: relative;
}

.page-subtitle {
  font-size: 18px;
  color: rgba(255,255,255,0.7);
  position: relative;
}

/* Controls */
.plans-controls {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
  margin: 40px 0 30px;
  padding: 20px 24px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.sort-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sort-group label {
  font-weight: 500;
  color: #555;
  font-size: 14px;
}

.sort-select {
  padding: 8px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  background: #fff;
  font-size: 14px;
  color: #1a1a2e;
  cursor: pointer;
  transition: border-color 0.3s, box-shadow 0.3s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}

.sort-select:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

/* Plans Grid */
.plans-section {
  padding: 0 0 60px;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  margin-top: 20px;
}

.plan-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px 24px 28px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: transform 0.3s, box-shadow 0.3s, opacity 0.3s;
  border: 1px solid #eef0f4;
  display: flex;
  flex-direction: column;
  position: relative;
}

.plan-card--loading {
  opacity: 0.7;
  pointer-events: none;
}

.plan-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.08);
}

.plan-header {
  padding-bottom: 16px;
  border-bottom: 1px solid #f0f2f6;
  margin-bottom: 12px;
  text-align: center;
}

.plan-name {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 4px;
}

.plan-price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
  margin: 6px 0 4px;
}

.price-amount {
  font-size: 38px;
  font-weight: 800;
  color: #1a1a2e;
}

.price-period {
  font-size: 14px;
  color: #a0a8b8;
}

.plan-speed {
  display: inline-block;
  padding: 3px 16px;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 600;
  color: #ff6b35;
  background: rgba(255, 107, 53, 0.08);
}

.plan-description {
  color: #666;
  font-size: 14px;
  line-height: 1.5;
  text-align: center;
  margin: 8px 0 16px;
  min-height: 42px;
}

.plan-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
  flex: 1;
}

.metric {
  background: #f8f9fc;
  padding: 12px;
  border-radius: 10px;
  text-align: center;
  transition: background 0.3s;
}

.metric:hover {
  background: #f0f2f6;
}

.metric-icon {
  display: block;
  margin: 0 auto 2px;
  color: #ff6b35;
}

.metric-icon svg {
  width: 18px;
  height: 18px;
}

.metric-label {
  display: block;
  font-size: 11px;
  color: #a0a8b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.metric-value {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: #1a1a2e;
}

.plan-footer {
  margin-top: 12px;
  padding-top: 16px;
  border-top: 1px solid #f0f2f6;
}

.plan-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 12px 0;
  background: #1a1a2e;
  color: #fff;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  text-align: center;
  transition: all 0.3s;
  border: none;
  cursor: pointer;
  position: relative;
}

.plan-btn:hover:not(:disabled) {
  background: #ff6b35;
  transform: scale(1.02);
}

.plan-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.plan-btn--loading {
  background: #ff6b35 !important;
}

.plan-btn--loading:hover {
  transform: none !important;
}

/* Button Spinner */
.btn-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
  padding: 20px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background: #fff;
  border-radius: 16px;
  max-width: 520px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.3s ease;
  overflow: hidden;
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

.modal-container.alert-modal {
  max-width: 450px;
}

.modal-container.success-modal {
  max-width: 450px;
}

.modal-container.address-modal {
  max-width: 520px;
}

.modal-container.subscribe-modal {
  max-width: 560px;
}

.modal-header {
  padding: 24px 24px 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header-info {
  flex: 1;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.modal-plan-price {
  font-size: 16px;
  font-weight: 600;
  color: #ff6b35;
  margin-top: 2px;
  display: inline-block;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #888;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  flex-shrink: 0;
}

.modal-close:hover {
  background: #f5f5f5;
  color: #333;
}

.modal-close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-body {
  padding: 24px;
}

/* Address Modal */
.address-modal-subtitle {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
}

.address-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* Service Available - Badge Style */
.service-available {
  margin-top: 8px;
  padding: 12px 16px;
  background: #ffffff;
  border-radius: 10px;
  border: 2px dashed #4caf50;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.service-available::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.05), rgba(76, 175, 80, 0.02));
  pointer-events: none;
}

.service-available::after {
  content: '✓ AVAILABLE';
  position: absolute;
  top: 8px;
  right: 12px;
  padding: 2px 12px;
  background: #4caf50;
  color: #fff;
  border-radius: 50px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.service-available-content {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 1;
}

.service-available-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.service-available-text strong {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a2e;
  display: block;
}

.service-available-text span {
  font-size: 13px;
  color: #666;
}

.service-available:hover {
  border-color: #66bb6a;
  box-shadow: 0 0 25px rgba(76, 175, 80, 0.12);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #1a1a2e;
}

.required {
  color: #e74c3c;
}

.form-input {
  padding: 10px 14px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
  background: #fff;
  font-family: inherit;
  width: 100%;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08);
}

.form-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.6;
}

.form-input.input-error {
  border-color: #e74c3c;
}

.has-error .form-input {
  border-color: #e74c3c !important;
}

.field-error {
  color: #e74c3c;
  font-size: 12px;
  font-weight: 500;
}

.address-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

/* Address Summary */
.address-summary {
  background: #f0f7ff;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 20px;
  border-left: 4px solid #ff6b35;
}

.address-summary-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.address-summary-icon {
  font-size: 18px;
}

.address-summary-label {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 14px;
}

.address-summary-text {
  font-size: 14px;
  color: #1a1a2e;
  margin: 2px 0;
}

.address-summary-location {
  font-size: 13px;
  color: #666;
  margin: 0;
}

/* Plan Summary */
.plan-summary {
  background: #f8f9fc;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 20px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
}

.summary-item:not(:last-child) {
  border-bottom: 1px solid #eef0f4;
}

.summary-label {
  color: #888;
  font-size: 14px;
}

.summary-value {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 14px;
}

/* Duration Options */
.duration-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-top: 8px;
}

.duration-option {
  padding: 12px 8px;
  border: 2px solid #e8ecf1;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.duration-option:hover {
  border-color: #ff6b35;
  background: rgba(255, 107, 53, 0.04);
}

.duration-option--active {
  border-color: #ff6b35;
  background: rgba(255, 107, 53, 0.08);
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.duration-months {
  font-weight: 700;
  font-size: 15px;
  color: #1a1a2e;
}

.duration-savings {
  font-size: 11px;
  font-weight: 600;
  color: #2e7d32;
  background: #e8f5e9;
  padding: 1px 8px;
  border-radius: 50px;
  display: inline-block;
}

.duration-price {
  font-size: 13px;
  font-weight: 600;
  color: #ff6b35;
}

/* Total Cost */
.total-cost {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 2px solid #f0f2f6;
  margin-top: 8px;
}

.total-cost-left {
  display: flex;
  flex-direction: column;
}

.total-label {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a2e;
}

.total-sub-label {
  font-size: 13px;
  color: #2e7d32;
  font-weight: 500;
}

.total-amount {
  font-size: 28px;
  font-weight: 800;
  color: #ff6b35;
}

/* Modal Footer */
.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  gap: 12px;
  border-top: 1px solid #f0f0f0;
}

.modal-btn {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel {
  background: #f5f5f5;
  color: #555;
}

.btn-cancel:hover:not(:disabled) {
  background: #e8e8e8;
}

.btn-subscribe {
  background: #ff6b35;
  color: #fff;
}

.btn-subscribe:hover:not(:disabled) {
  background: #e85a2a;
  transform: scale(1.02);
}

/* Alert Modal */
.alert-content {
  text-align: center;
  padding: 12px 0;
}

.alert-text {
  font-size: 16px;
  color: #1a1a2e;
  line-height: 1.6;
  margin-bottom: 8px;
}

.alert-text strong {
  color: #ff6b35;
}

.alert-subtext {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.success-icon {
  font-size: 32px;
  margin-right: 12px;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 99999;
  animation: slideUp 0.3s ease;
}

.toast-content {
  padding: 16px 24px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  max-width: 400px;
  min-width: 300px;
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
  flex-shrink: 0;
}

.toast-message {
  font-size: 14px;
  flex: 1;
}

/* Responsive */
@media (max-width: 992px) {
  .plans-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }

  .duration-options {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 32px;
  }

  .plans-controls {
    justify-content: center;
  }

  .plans-grid {
    grid-template-columns: 1fr;
    max-width: 400px;
    margin: 20px auto 0;
  }

  .modal-container {
    max-width: 100%;
    margin: 20px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .duration-options {
    grid-template-columns: repeat(2, 1fr);
  }

  .total-cost {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .modal-footer {
    flex-direction: column;
  }

  .address-actions {
    flex-direction: column;
  }

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }

  .toast-content {
    max-width: 100%;
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 26px;
  }

  .price-amount {
    font-size: 32px;
  }

  .plan-metrics {
    grid-template-columns: 1fr 1fr;
  }

  .modal-body {
    padding: 16px;
  }

  .modal-footer {
    padding: 12px 16px 16px;
  }

  .modal-btn {
    padding: 10px;
    font-size: 14px;
  }

  .duration-options {
    grid-template-columns: 1fr 1fr;
  }

  .duration-option {
    padding: 10px 6px;
  }

  .total-amount {
    font-size: 24px;
  }
}
</style>
