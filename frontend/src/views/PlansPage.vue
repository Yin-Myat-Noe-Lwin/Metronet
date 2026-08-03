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

        <div v-if="loadingServiceAreas" class="loading-service-areas">
          <div class="small-spinner"></div>
          <span>Loading service areas...</span>
        </div>

        <div v-else class="address-form">
          <!-- DROPDOWNS FIRST (Region, City, Township) -->
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

          <!-- STREET ADDRESS BELOW -->
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

          <!-- Service Available -->
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
          <button class="modal-btn btn-subscribe" @click="continueToSubscription" :disabled="!isAddressValid">
            Continue to Subscription
          </button>
        </div>
      </div>
    </div>
  </div>

    <!-- Subscribe Modal -->
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
          <!-- Address Summary -->
          <div class="address-summary" v-if="addressForm.address">
            <span class="address-summary-label">Installation Address</span>
            <p class="address-summary-text">{{ addressForm.address }}</p>
            <p class="address-summary-location">{{ addressForm.township }}, {{ addressForm.city }}, {{ addressForm.region }}</p>
          </div>

          <!-- Plan Summary -->
          <div class="plan-summary">
            <div class="summary-row">
              <span class="summary-label">Plan</span>
              <span class="summary-value">{{ selectedPlan?.name }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Speed</span>
              <span class="summary-value">{{ selectedPlan?.download_speed }} Mbps</span>
            </div>
          </div>

          <!-- Total Cost -->
          <div class="total-cost">
            <div class="total-cost-left">
              <span class="total-label">Total for {{ selectedDuration }} Month{{ selectedDuration > 1 ? 's' : '' }}</span>
              <span v-if="getDiscountForDuration(selectedDuration) > 0" class="total-savings">
                Save {{ getDiscountForDuration(selectedDuration) }}%
              </span>
            </div>
            <div class="total-amount-wrapper">
              <span
                v-if="getDiscountForDuration(selectedDuration) > 0"
                class="total-original-price"
              >
                {{ formatPrice(selectedPlan?.price * selectedDuration) }}
              </span>
              <span class="total-amount">{{ formatPrice(calculateTotalCost()) }}</span>
              <span v-if="getDiscountForDuration(selectedDuration) > 0" class="total-monthly-savings">
                Only {{ formatPrice(calculateTotalCost() / selectedDuration) }}/month
              </span>
            </div>
          </div>

          <!-- Duration Selection -->
          <div class="form-group">
            <label class="form-label">Select Duration</label>
            <div class="duration-options">
              <button
                v-for="duration in availableDurations"
                :key="duration"
                class="duration-option"
                :class="{
                  'duration-option--active': selectedDuration === duration,
                  'has-discount': getDiscountForDuration(duration) > 0
                }"
                @click="selectedDuration = duration"
              >
                <span class="duration-months">{{ duration }} Month{{ duration > 1 ? 's' : '' }}</span>
                <div class="duration-pricing">
                  <span
                    v-if="getDiscountForDuration(duration) > 0"
                    class="duration-original-price"
                  >
                    {{ formatPrice(selectedPlan?.price * duration) }}
                  </span>
                  <span class="duration-price">{{ formatPrice(calculateDurationPrice(duration)) }}</span>
                </div>
                <span v-if="getDiscountForDuration(duration) > 0" class="duration-savings-badge">
                  -{{ getDiscountForDuration(duration) }}%
                </span>
              </button>
            </div>
          </div>

          <!-- Billing Cycle -->
          <div class="form-group">
            <label class="form-label">Billing Cycle</label>
            <div class="billing-options">
              <button
                v-for="cycle in billingCycles"
                :key="cycle.value"
                class="billing-option"
                :class="{ 'billing-option--active': selectedBillingCycle === cycle.value }"
                @click="selectedBillingCycle = cycle.value"
                :disabled="cycle.value > selectedDuration"
              >
                <span class="billing-label">{{ cycle.value }} Month{{ cycle.value > 1 ? 's' : '' }}</span>
                <span class="billing-desc">{{ cycle.description }}</span>
              </button>
            </div>
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

    <!-- Already Subscribed Modal - UNCHANGED -->
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
            <p class="alert-text">You are already subscribed to this plan or have a pending approval.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn btn-cancel" @click="closeAlreadySubscribedModal">Close</button>
          <button class="modal-btn btn-subscribe" @click="goToSubscriptions">View Subscriptions</button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
  <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
    <div class="modal-container success-modal">
      <div class="modal-header">
        <h3 class="modal-title">Subscription Successful</h3>
        <button class="modal-close" @click="closeSuccessModal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="modal-body success-body">
        <div class="success-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="22 4 12 14.01 9 11.01" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>

        <h4 class="success-plan">{{ successPlanName }}</h4>
        <p class="success-message">Your subscription has been submitted successfully.</p>
        <p class="success-subtext">You'll be notified once approved.</p>
      </div>

      <div class="modal-footer">
        <button class="modal-btn btn-subscribe" @click="goToSubscriptions">View Subscriptions</button>
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
import { plansService, subscriptionsService, serviceAreasService } from '../services/api'

export default {
  name: 'PlansPage',
  data() {
    return {
      loading: false,
      error: null,
      subscribing: false,
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
        address_type: 1
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

      // Subscribe Modal
      showSubscribeModal: false,
      selectedPlan: null,
      selectedDuration: 1,
      selectedBillingCycle: 1,
      billingCycles: [
        { value: 1, label: 'Monthly', description: 'Pay each month' },
        { value: 3, label: 'Quarterly', description: 'Pay every 3 months' },
        { value: 6, label: 'Semi-annual', description: 'Pay every 6 months' },
        { value: 12, label: 'Annual', description: 'Pay once per year' }
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
        case 'price-low': return filtered.sort((a, b) => a.price - b.price)
        case 'price-high': return filtered.sort((a, b) => b.price - a.price)
        case 'speed': return filtered.sort((a, b) => b.download_speed - a.download_speed)
        default: return filtered
      }
    },
    isAddressValid() {
      return this.addressForm.address &&
              this.addressForm.region &&
              this.addressForm.city &&
              this.addressForm.township
    },
    availableDurations() {
      if (this.selectedPlan?.discounts) {
        const durations = [1]
        Object.keys(this.selectedPlan.discounts).forEach(key => {
          const d = parseInt(key)
          if (!durations.includes(d)) durations.push(d)
        })
        return durations.sort((a, b) => a - b)
      }
      return [1]
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
        const data = response.data || response

        if (data.success && data.data) {
          this.plans = data.data.map(p => ({
            ...p,
            price: parseFloat(p.price),
            discounts: p.discounts || {},
            best_discount: parseFloat(p.best_discount) || 0
          }))
        } else if (Array.isArray(data)) {
          this.plans = data.map(p => ({
            ...p,
            price: parseFloat(p.price),
            discounts: p.discounts || {},
            best_discount: parseFloat(p.best_discount) || 0
          }))
        }
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
        const response = await serviceAreasService.getServiceAreas()
        const data = response.data || response

        if (data.hierarchy) {
          this.serviceRegions = data.regions || []
          this.hierarchy = data.hierarchy || {}

          this.citiesByRegion = {}
          Object.keys(this.hierarchy).forEach(region => {
            this.citiesByRegion[region] = Object.keys(this.hierarchy[region]) || []
          })

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
        }
      } catch (error) {
        console.error('Error fetching service areas:', error)
        // this.showToast('Failed to load service areas', 'error')
      } finally {
        this.loadingServiceAreas = false
      }
    },

    onRegionChange() {
      this.filteredCities = this.citiesByRegion[this.addressForm.region] || []
      this.addressForm.city = ''
      this.addressForm.township = ''
      this.filteredTownships = []
      this.clearFieldError('region')
    },

    onCityChange() {
      const key = `${this.addressForm.region}_${this.addressForm.city}`
      this.filteredTownships = this.townshipsByCity[key] || []
      this.addressForm.township = ''
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

    continueToSubscription() {
      if (!this.validateAddressForm()) {
        const firstError = document.querySelector('.has-error')
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
      }

      this.showAddressModal = false
      this.selectedPlan = this.pendingPlan
      this.selectedDuration = 1
      this.selectedBillingCycle = 1
      this.showSubscribeModal = true
    },

    closeAllModals() {
      this.showAddressModal = false
      this.pendingPlan = null
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
      this.selectedBillingCycle = 1
    },

    closeAlreadySubscribedModal() {
      this.showAlreadySubscribedModal = false
      this.alreadySubscribedPlan = null
    },

    closeSuccessModal() {
      this.showSuccessModal = false
      this.successPlanName = ''
    },

    goToSubscriptions() {
      this.showAlreadySubscribedModal = false
      this.showSuccessModal = false
      this.$router.push('/subscriptions')
    },

    // ===== DISCOUNT METHODS - ONLY FROM DATABASE =====

    getDiscountForDuration(duration) {
      if (this.selectedPlan?.discounts && this.selectedPlan.discounts[duration] !== undefined) {
        return parseFloat(this.selectedPlan.discounts[duration])
      }
      return 0
    },

    calculateDurationPrice(duration) {
      if (!this.selectedPlan) return 0
      const monthly = this.selectedPlan.price
      const discount = this.getDiscountForDuration(duration)
      return monthly * duration * (1 - (discount / 100))
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
    },

    async confirmSubscription() {
      if (this.subscribing) return

      this.subscribing = true
      this.selectedPlanId = this.selectedPlan.id

      try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}')
        const customerId = userData.id || localStorage.getItem('userId')

        if (!customerId) {
          this.showToast('Please login to subscribe', 'error')
          this.subscribing = false
          return
        }

        await subscriptionsService.createSubscription(
          this.selectedPlan.id,
          {
            customer_id: parseInt(customerId),
            duration_months: this.selectedDuration,
            billing_cycle: this.selectedBillingCycle,
            address: this.addressForm.address,
            region: this.addressForm.region,
            city: this.addressForm.city,
            township: this.addressForm.township,
            address_type: 1
          }
        )

        this.successPlanName = this.selectedPlan.name
        this.showSuccessModal = true
        this.showSubscribeModal = false
        this.showToast('Subscription submitted successfully!', 'success')
      } catch (error) {
        console.error('Subscription error:', error)
        const errorData = error.response?.data
        const statusCode = error.response?.status

        if (statusCode === 409 || (errorData?.error && errorData.error.includes('Already subscribed'))) {
          this.alreadySubscribedPlan = this.selectedPlan
          this.showAlreadySubscribedModal = true
          return
        }

        const errorMessage = errorData?.error || errorData?.message || 'Failed to subscribe. Please try again.'
        this.showToast(errorMessage, 'error')
      } finally {
        this.subscribing = false
        this.selectedPlanId = null
      }
    }
  }
}
</script>

<style scoped>
/* ===== PLANS PAGE ===== */
.plans-page {
  background: #f8f9fa;
  min-height: 100vh;
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
@keyframes spin { to { transform: rotate(360deg); } }
.loading-state p { color: #8892a8; font-size: 14px; }

.error-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}
.error-icon { font-size: 48px; margin-bottom: 16px; }
.error-state h3 { color: #1a1a2e; margin-bottom: 8px; }
.error-state p { color: #8892a8; margin-bottom: 20px; }
.retry-btn {
  padding: 10px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
.retry-btn:hover { background: #e85a2a; }

.page-header {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
  color: #fff;
  padding: 60px 0 50px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.page-title { font-size: 44px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.5px; position: relative; }
.page-subtitle { font-size: 18px; color: rgba(255,255,255,0.7); position: relative; }

.plans-controls {
  display: flex;
  justify-content: flex-end;
  margin: 40px 0 30px;
  padding: 20px 24px;
  background: #fff;
  border-radius: 12px;
}
.sort-group { display: flex; align-items: center; gap: 10px; }
.sort-group label { font-weight: 500; color: #555; font-size: 14px; }
.sort-select {
  padding: 8px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  background: #fff;
  font-size: 14px;
  color: #1a1a2e;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}
.sort-select:focus { outline: none; border-color: #ff6b35; }

.plans-section { padding: 0 0 60px; }
.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  margin-top: 20px;
}

/* ===== PLAN CARDS ===== */
.plan-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px 24px 28px;
  border: 1px solid #eef0f4;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.3s;
}
.plan-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }

.plan-header { text-align: center; padding-bottom: 16px; border-bottom: 1px solid #f0f2f6; margin-bottom: 12px; }
.plan-name { font-size: 24px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
.plan-price { display: flex; align-items: baseline; justify-content: center; gap: 4px; margin: 6px 0 4px; }
.price-amount {
  font-size: 34px;
  font-weight: 800;
  color: #ff6b35;
  transition: color 0.2s;
}
.price-period { font-size: 14px; color: #a0a8b8; }
.plan-speed {
  display: inline-block;
  padding: 3px 16px;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 600;
  color: #ff6b35;
  background: rgba(255,107,53,0.08);
}
.plan-description { color: #666; font-size: 14px; text-align: center; margin: 8px 0 16px; min-height: 42px; }
.plan-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; flex: 1; }
.metric { background: #f8f9fc; padding: 12px; border-radius: 10px; text-align: center; }
.metric-icon { display: block; margin: 0 auto 2px; color: #ff6b35; }
.metric-icon svg { width: 18px; height: 18px; }
.metric-label { display: block; font-size: 11px; color: #a0a8b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.metric-value { display: block; font-size: 15px; font-weight: 700; color: #1a1a2e; }

.plan-footer { margin-top: 12px; padding-top: 16px; border-top: 1px solid #f0f2f6; }
.plan-btn {
  width: 100%;
  padding: 12px 0;
  background: #1a1a2e;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s;
}
.plan-btn:hover:not(:disabled) { background: #ff6b35; transform: scale(1.02); }
.plan-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ===== MODAL OVERLAY & CONTAINER ===== */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal-container {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  overflow: hidden;
  width: 100%;
}
.modal-container.address-modal { max-width: 520px; }
.modal-container.subscribe-modal {
  max-width: 720px; /* wider */
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.modal-container.alert-modal { max-width: 450px; }
.modal-container.success-modal {
  max-width: 420px;
}

.success-body {
  text-align: center;
  padding: 32px 24px 24px;
}

.success-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  background: #e8f5e9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-icon svg {
  width: 40px;
  height: 40px;
  color: #2e7d32;
  stroke-width: 2.5;
}

.success-plan {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 8px 0;
}

.success-message {
  font-size: 15px;
  color: #555;
  margin: 0 0 4px 0;
}

.success-subtext {
  font-size: 14px;
  color: #8892a8;
  margin: 0;
}

.modal-container.success-modal .modal-footer {
  justify-content: center;
  padding-top: 4px;
}

.modal-container.success-modal .modal-btn {
  flex: 0 1 auto;
  min-width: 200px;
}

.modal-header {
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
.modal-header-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.modal-title { font-size: 18px; font-weight: 700; color: #1a1a2e; margin: 0; }
.modal-plan-price { font-size: 14px; font-weight: 600; color: #ff6b35; }
.modal-close {
  width: 32px; height: 32px;
  border: none; background: transparent;
  color: #888; cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-close:hover { background: #f5f5f5; color: #333; }

.modal-body {
  padding: 16px 24px 12px;
  overflow-y: auto;
  flex: 0 1 auto;
  max-height: calc(90vh - 120px);
}

/* ===== ADDRESS MODAL ===== */
.address-modal-subtitle { color: #666; font-size: 14px; margin-bottom: 16px; }
.address-form { display: flex; flex-direction: column; gap: 12px; }
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 10px;
}
.form-group { display: flex; flex-direction: column; gap: 3px; }
.form-group label { font-size: 12px; font-weight: 600; color: #1a1a2e; }
.required { color: #e74c3c; }
/* Text inputs – no arrow */
.form-group input {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  width: 100%;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

/* Select dropdowns – with arrow */
.form-group select {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  width: 100%;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}
.form-group select { cursor: pointer; }
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255,107,53,0.15);
}
.form-group select:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.6;
}
.field-error { color: #e74c3c; font-size: 12px; font-weight: 500; }
.input-error { border-color: #e74c3c !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(231,76,60,0.15) !important; }

.service-available {
  background: #e8f5e9;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 13px;
  color: #2e7d32;
  text-align: center;
  font-weight: 500;
}

.address-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #f0f0f0;
}

/* ===== SUBSCRIBE MODAL – WIDE, SHORT, PROFESSIONAL ===== */
.address-summary {
  background: #f0f7ff;
  padding: 10px 14px;
  border-radius: 6px;
  margin-bottom: 12px;
  border-left: 3px solid #ff6b35;
}
.address-summary .address-summary-label {
  font-weight: 600;
  color: #1a1a2e;
  display: block;
  margin-bottom: 2px;
}
.address-summary p {
  margin: 2px 0;
  font-size: 13px;
}
.address-summary small { font-size: 12px; color: #666; }

.plan-summary {
  background: #f8f9fc;
  border-radius: 6px;
  padding: 10px 14px;
  margin-bottom: 12px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 3px 0;
  font-size: 13px;
  color: #555;
}
.summary-row:not(:last-child) { border-bottom: 1px solid #eef0f4; }
.summary-value { font-weight: 600; color: #1a1a2e; }

/* Monthly price with strikethrough */
.original-monthly {
  text-decoration: line-through;
  color: #a0a8b8;
  font-weight: 500;
  margin-right: 6px;
}
.discounted-monthly {
  font-weight: 700;
  color: #ff6b35;
  font-size: 18px;
}
.save-badge {
  background: #2e7d32;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 50px;
  margin-left: 8px;
}

/* Duration cards – compact with strikethrough & badge */
.duration-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-top: 6px;
}
.duration-option {
  padding: 10px 4px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
  position: relative;
}
.duration-option:hover {
  border-color: #ff6b35;
  transform: translateY(-2px);
}
.duration-option--active {
  border-color: #ff6b35;
  background: rgba(255,107,53,0.06);
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
}
.duration-option.has-discount {
  border-color: #2e7d32;
  background: #f0fdf4;
}
.duration-option.has-discount.duration-option--active {
  border-color: #ff6b35;
  background: rgba(255,107,53,0.06);
}
.duration-months {
  font-weight: 700;
  font-size: 15px;
  color: #1a1a2e;
  display: block;
}
.duration-pricing {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0px;
}
.duration-original-price {
  font-size: 12px;
  font-weight: 500;
  color: #a0a8b8;
  text-decoration: line-through;
}
.duration-price {
  font-size: 17px;
  font-weight: 700;
  color: #ff6b35;
}
.duration-savings-badge {
  position: absolute;
  top: -6px;
  right: -4px;
  background: #2e7d32;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 50px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.duration-option--active .duration-savings-badge {
  background: #ff6b35;
}

/* Billing cycle – human‑readable */
.billing-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-top: 6px;
}
.billing-option {
  padding: 8px 4px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
}
.billing-option:hover:not(:disabled) {
  border-color: #ff6b35;
  transform: translateY(-2px);
}
.billing-option:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.billing-option--active {
  border-color: #ff6b35;
  background: rgba(255,107,53,0.06);
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
}
.billing-label {
  font-weight: 700;
  font-size: 13px;
  color: #1a1a2e;
  display: block;
}
.billing-desc {
  font-size: 10px;
  color: #94a3b8;
}

/* Total cost – strikethrough + monthly savings */
.total-cost {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  margin-top: 10px;
  border-top: 2px solid #f0f2f6;
}
.total-cost-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.total-label {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
}
.total-savings {
  font-size: 13px;
  color: #2e7d32;
  font-weight: 600;
}
.total-amount-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0px;
}
.total-original-price {
  font-size: 15px;
  font-weight: 500;
  color: #a0a8b8;
  text-decoration: line-through;
}
.total-amount {
  font-size: 28px;
  font-weight: 800;
  color: #ff6b35;
}
.total-monthly-savings {
  font-size: 14px;
  font-weight: 600;
  color: #2e7d32;
}

.modal-footer {
  padding: 10px 20px 14px;
  display: flex;
  gap: 10px;
  border-top: 1px solid #f0f0f0;
  flex-shrink: 0;
  background: #fff;
}

.modal-btn {
  flex: 1;
  padding: 10px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.modal-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-cancel { background: #f5f5f5; color: #555; }
.btn-cancel:hover:not(:disabled) { background: #e8e8e8; }
.btn-subscribe { background: #ff6b35; color: #fff; }
.btn-subscribe:hover:not(:disabled) { background: #e85a2a; transform: scale(1.02); }

/* ===== TOAST ===== */
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 12px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  z-index: 99999;
  font-size: 14px;
  min-width: 240px;
}
.toast.success { background: #1a1a2e; color: #fff; }
.toast.error { background: #dc2626; color: #fff; }
.toast button {
  background: none; border: none;
  color: rgba(255,255,255,0.6);
  font-size: 18px; cursor: pointer;
  padding: 0 4px;
}
.toast button:hover { color: #fff; }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .page-title { font-size: 28px; }
  .plans-grid { grid-template-columns: 1fr; max-width: 400px; margin: 0 auto; }
  .modal-container { max-width: 100%; margin: 10px; max-height: 95vh; }
  .modal-body { padding: 12px 16px; }
  .modal-footer { flex-direction: column; }
  .address-actions { flex-direction: column; }
  .duration-options { grid-template-columns: repeat(2, 1fr); }
  .billing-options { grid-template-columns: repeat(2, 1fr); }
  .form-row { grid-template-columns: 1fr; }
  .toast { left: 16px; right: 16px; }
  .total-cost { flex-direction: column; align-items: flex-start; gap: 6px; }
  .total-amount-wrapper { align-items: flex-start; width: 100%; }
  .total-amount { font-size: 24px; }
}

@media (max-width: 480px) {
  .page-title { font-size: 24px; }
  .price-amount { font-size: 28px; }
  .plan-card { padding: 20px 16px; }
  .modal-header { padding: 10px 16px; }
  .modal-body { padding: 10px 14px; }
  .modal-footer { padding: 10px 14px 14px; }
  .modal-title { font-size: 16px; }
  .duration-options { gap: 6px; }
  .billing-options { gap: 6px; }
  .duration-option { padding: 6px 4px; }
  .billing-option { padding: 6px 4px; }
  .duration-months { font-size: 12px; }
  .duration-price { font-size: 11px; }
  .billing-label { font-size: 11px; }
  .total-amount { font-size: 22px; }
  .total-original-price { font-size: 12px; }
}
</style>
