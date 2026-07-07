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
                <button @click="handleSubscribe(plan)" class="plan-btn" :disabled="subscribing">
                  {{ subscribing && selectedPlanId === plan.id ? 'Subscribing...' : 'Subscribe Now' }}
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </section>

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
import { plansService } from '../services/api'

export default {
  name: 'PlansPage',
  data() {
    return {
      loading: false,
      error: null,
      subscribing: false,
      selectedPlanId: null,
      sortBy: 'popular',
      plans: [],
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
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
        const response = await plansService.getPlans()
        console.log('Fetched plans:', response)

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

        console.log('Mapped plans:', this.plans)
      } catch (error) {
        console.error('Error fetching plans:', error)
        this.error = error.response?.data?.message || 'Failed to load plans. Please try again.'
        this.showToast('Failed to load plans', 'error')
      } finally {
        this.loading = false
      }
    },

    async handleSubscribe(plan) {
      const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'

      if (!isLoggedIn) {
        this.$router.push({
          path: '/login',
          query: { plan: plan.id, return: '/plans' }
        })
        return
      }

      const confirmed = confirm(
        `You are about to subscribe to the ${plan.name} plan for ${this.formatPrice(plan.price)}/month. Continue?`
      )

      if (!confirmed) return

      this.subscribing = true
      this.selectedPlanId = plan.id

      try {
        const response = await plansService.subscribe(plan.id, {
          plan_id: plan.id
        })

        console.log('Subscription response:', response)
        this.showToast(`Successfully subscribed to ${plan.name} plan!`, 'success')

        setTimeout(() => {
          this.$router.push('/subscriptions')
        }, 2000)
      } catch (error) {
        console.error('Subscription error:', error)
        const errorMessage = error.response?.data?.message || 'Failed to subscribe. Please try again.'
        this.showToast(errorMessage, 'error')
      } finally {
        this.subscribing = false
        this.selectedPlanId = null
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
  transition: transform 0.3s, box-shadow 0.3s;
  border: 1px solid #eef0f4;
  display: flex;
  flex-direction: column;
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
  display: inline-block;
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
}

.plan-btn:hover:not(:disabled) {
  background: #ff6b35;
  transform: scale(1.02);
}

.plan-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Comparison Table */
.comparison-section {
  background: #fff;
  padding: 60px 0;
  border-top: 1px solid #eef0f4;
}

.comparison-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a2e;
  text-align: center;
  margin-bottom: 30px;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.comparison-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.comparison-table th,
.comparison-table td {
  padding: 14px 16px;
  text-align: center;
  border-bottom: 1px solid #f0f2f6;
}

.comparison-table th {
  background: #1a1a2e;
  color: #fff;
  font-weight: 600;
}

.comparison-table th:first-child {
  border-radius: 8px 0 0 0;
}

.comparison-table td:first-child {
  text-align: left;
  font-weight: 500;
  color: #1a1a2e;
  background: #fafbfc;
}

.comparison-table tr:hover td {
  background: #f8f9fc;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 1000;
  animation: slideUp 0.3s;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.toast-content {
  padding: 16px 24px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
@media (max-width: 992px) {
  .plans-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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

  .comparison-table {
    font-size: 12px;
  }

  .comparison-table th,
  .comparison-table td {
    padding: 10px 12px;
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

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}
</style>
