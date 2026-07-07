<template>
  <div class="home-page">
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Fast, Reliable Internet <br>for Your Connected Life</h1>
          <p class="hero-subtitle">Choose the perfect plan for your home or business.</p>
          <router-link to="/plans" class="hero-btn">View Plans</router-link>
        </div>
      </div>
    </section>

    <section class="plans-section">
      <div class="container">
        <h2 class="section-title">Our Plans</h2>
        <p class="section-subtitle">Find the speed that fits your needs</p>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading plans...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <p>{{ error }}</p>
          <button @click="fetchPlans" class="retry-btn">Try Again</button>
        </div>

        <!-- Plans Grid -->
        <div v-else class="plans-grid">
          <div
            v-for="plan in displayPlans"
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
              <router-link to="/plans" class="plan-btn">View Details</router-link>
            </div>
          </div>
        </div>

        <div class="view-all-container" v-if="!loading && !error && plans.length > 0">
          <router-link to="/plans" class="view-all-btn">View All Plans →</router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { plansService } from '../services/api'

export default {
  name: 'HomePage',
  data() {
    return {
      loading: false,
      error: null,
      plans: [],
      displayCount: 3
    }
  },
  computed: {
    displayPlans() {
      return this.plans.slice(0, this.displayCount)
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
        console.log('HomePage - Fetched plans:', response)

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

        console.log('HomePage - Mapped plans:', this.plans)
      } catch (error) {
        console.error('HomePage - Error fetching plans:', error)
        this.error = error.response?.data?.message || 'Failed to load plans. Please try again.'
      } finally {
        this.loading = false
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
    }
  }
}
</script>

<style scoped>
.home-page {
  background: #f8f9fa;
}

/* Hero */
.hero {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
  color: #fff;
  padding: 80px 0 70px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  background: rgba(255, 107, 53, 0.06);
  border-radius: 50%;
}

.hero::after {
  content: '';
  position: absolute;
  bottom: -40%;
  left: -10%;
  width: 300px;
  height: 300px;
  background: rgba(255, 107, 53, 0.04);
  border-radius: 50%;
}

.hero-content {
  max-width: 700px;
  margin: 0 auto;
  position: relative;
}

.hero-title {
  font-size: 44px;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
  letter-spacing: -0.5px;
}

.hero-subtitle {
  font-size: 18px;
  color: rgba(255,255,255,0.7);
  margin-bottom: 32px;
}

.hero-btn {
  display: inline-block;
  background: #ff6b35;
  color: #fff;
  padding: 14px 44px;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 700;
  font-size: 18px;
  transition: all 0.3s;
  box-shadow: 0 4px 20px rgba(255, 107, 53, 0.4);
}

.hero-btn:hover {
  background: #e85a2a;
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(255, 107, 53, 0.5);
}

/* Loading State */
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
  font-size: 14px;
}

/* Error State */
.error-state {
  text-align: center;
  padding: 40px 20px;
  background: #fff;
  border-radius: 12px;
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

/* Plans Section */
.plans-section {
  padding: 70px 0 60px;
}

.section-title {
  text-align: center;
  font-size: 38px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.section-subtitle {
  text-align: center;
  font-size: 18px;
  color: #8892a8;
  margin-bottom: 48px;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  align-items: stretch;
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
}

.plan-btn:hover {
  background: #ff6b35;
  transform: scale(1.02);
}

.view-all-container {
  text-align: center;
  margin-top: 40px;
}

.view-all-btn {
  display: inline-block;
  color: #ff6b35;
  font-size: 18px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.view-all-btn:hover {
  color: #e85a2a;
  transform: translateX(4px);
}

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 30px;
  }

  .hero-subtitle {
    font-size: 16px;
  }

  .plans-grid {
    grid-template-columns: 1fr;
    max-width: 400px;
    margin: 0 auto;
  }

  .section-title {
    font-size: 30px;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 26px;
  }

  .hero {
    padding: 50px 0;
  }

  .plan-card {
    padding: 24px 16px;
  }

  .price-amount {
    font-size: 32px;
  }

  .plan-metrics {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
