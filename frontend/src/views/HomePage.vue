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

        if (!response || !response.success || !Array.isArray(response.data)) {
          throw new Error(response?.message || 'Invalid response from server')
        }

        const plansData = response.data
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
        this.error = error.message || 'Failed to load plans. Please try again.'
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

/* ─── Refined Plan Cards ─── */
.plan-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 28px 20px 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid #f0f2f6;
}

.plan-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
}

.plan-header {
  text-align: center;
  padding-bottom: 14px;
  border-bottom: 1px solid #f0f2f6;
  margin-bottom: 14px;
}

.plan-name {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 6px 0;
}

.plan-price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
}

.price-amount {
  font-size: 34px;
  font-weight: 800;
  color: #ff6b35;
  transition: color 0.2s;
}

.plan-card:hover .price-amount {
  color: #e85a2a;
}

.price-period {
  font-size: 14px;
  color: #a0a8b8;
}

.plan-footer {
  margin-top: 4px;
}

.plan-btn {
  display: inline-block;
  width: 100%;
  padding: 11px 0;
  background: #1a1a2e;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  transition: all 0.25s ease;
}

.plan-btn:hover {
  background: #ff6b35;
  color: #fff;
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
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
    font-size: 28px;
  }
}
</style>
