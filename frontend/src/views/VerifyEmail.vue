<template>
  <div class="verify-page">
    <!-- Loading State -->
    <div v-if="loading" class="verify-container">
      <div class="verify-card loading-card">
        <div class="loader-wrapper">
          <div class="spinner"></div>
        </div>
        <h3>Verifying Your Email</h3>
        <p class="text-muted">Just a moment while we confirm your email address...</p>
        <div class="progress-bar">
          <div class="progress-fill"></div>
        </div>
      </div>
    </div>

    <!-- Success State -->
    <div v-else-if="success" class="verify-container">
      <div class="verify-card success-card">
        <div class="icon-circle success-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="badge">Verified</div>
        <h2 class="verify-title">Email Verified Successfully!</h2>
        <p class="verify-message">Your email has been confirmed. You're all set to access your account and explore our services.</p>
        <div class="verify-actions">
          <router-link to="/login" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
              <polyline points="10 17 15 12 10 7"/>
              <line x1="15" x2="3" y1="12" y2="12"/>
            </svg>
            Get Started
          </router-link>
          <router-link to="/" class="btn-secondary">Return Home</router-link>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="verify-container">
      <div class="verify-card error-card">
        <div class="icon-circle error-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </div>
        <div class="badge error-badge">Failed</div>
        <h2 class="verify-title">Verification Failed</h2>
        <p class="verify-message">{{ message || 'The verification link is invalid or has expired. Please request a new verification email.' }}</p>
        <div class="verify-actions">
          <router-link to="/" class="btn-secondary">Return Home</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'VerifyEmail',
  data() {
    return {
      loading: true,
      success: false,
      message: ''
    }
  },
  async mounted() {
    const token = this.$route.query.token

    if (!token) {
      this.loading = false
      this.success = false
      this.message = 'Invalid verification link. Please check your email for the correct link.'
      return
    }

    try {
      const response = await axios.get(`http://localhost:8080/api/verify-email?token=${token}`)
      this.success = true
      this.message = response.data.message || 'Email verified successfully.'
    } catch (error) {
      this.success = false
      this.message = error.response?.data?.message || 'Verification link is invalid or expired.'
    } finally {
      this.loading = false
    }
  }
}
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  position: relative;
  overflow: hidden;
}

/* Background decorative elements */
.verify-page::before {
  content: '';
  position: absolute;
  top: -30%;
  right: -10%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(255, 107, 53, 0.05) 0%, transparent 70%);
  border-radius: 50%;
}

.verify-page::after {
  content: '';
  position: absolute;
  bottom: -30%;
  left: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 107, 53, 0.03) 0%, transparent 70%);
  border-radius: 50%;
}

.verify-container {
  width: 100%;
  max-width: 460px;
  position: relative;
  z-index: 1;
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.verify-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 48px 40px 40px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(10px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.verify-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.08), 0 12px 32px rgba(0, 0, 0, 0.04);
}

/* Loading State */
.loading-card h3 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
  margin-top: 20px;
}

.loader-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.spinner {
  width: 56px;
  height: 56px;
  border: 4px solid #f0f2f6;
  border-top-color: #ff6b35;
  border-radius: 50%;
  animation: spin 0.8s cubic-bezier(0.6, 0, 0.4, 1) infinite;
}

.progress-bar {
  width: 100%;
  height: 4px;
  background: #f0f2f6;
  border-radius: 4px;
  margin-top: 24px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  width: 60%;
  background: linear-gradient(90deg, #ff6b35, #ff8a5c);
  border-radius: 4px;
  animation: progress 1.5s ease-in-out infinite;
}

.text-muted {
  color: #8892a8;
  font-size: 15px;
  margin: 0;
}

/* Icon Circle */
.icon-circle {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  position: relative;
}

.icon-circle svg {
  width: 44px;
  height: 44px;
}

.success-icon {
  background: linear-gradient(135deg, rgba(46, 213, 115, 0.12), rgba(46, 213, 115, 0.05));
  color: #2ed573;
  animation: pulse 2s ease-in-out infinite;
}

.error-icon {
  background: linear-gradient(135deg, rgba(255, 71, 87, 0.12), rgba(255, 71, 87, 0.05));
  color: #ff4757;
}

/* Badge */
.badge {
  display: inline-block;
  padding: 4px 16px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
  background: rgba(46, 213, 115, 0.1);
  color: #2ed573;
}

.error-badge {
  background: rgba(255, 71, 87, 0.1);
  color: #ff4757;
}

/* Typography */
.verify-title {
  font-size: 28px;
  font-weight: 800;
  color: #1a1a2e;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.verify-message {
  font-size: 15px;
  line-height: 1.7;
  color: #6b7a8f;
  margin-bottom: 32px;
}

.success-card .verify-message {
  color: #5a6b7e;
}

/* Buttons */
.verify-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 16px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
}

.btn-primary svg {
  flex-shrink: 0;
}

.btn-primary:hover {
  background: #e85a2a;
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(255, 107, 53, 0.4);
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-secondary {
  display: inline-block;
  width: 100%;
  padding: 16px 32px;
  background: transparent;
  color: #1a1a2e;
  border: 2px solid #eef0f4;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-secondary:hover {
  background: #f8f9fa;
  border-color: #d0d4dc;
  transform: translateY(-2px);
}

/* Animations */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes progress {
  0% { width: 0%; }
  50% { width: 80%; }
  100% { width: 0%; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(40px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .verify-card {
    padding: 36px 24px 32px;
    border-radius: 20px;
  }

  .verify-title {
    font-size: 24px;
  }

  .icon-circle {
    width: 72px;
    height: 72px;
  }

  .icon-circle svg {
    width: 36px;
    height: 36px;
  }

  .btn-primary,
  .btn-secondary {
    padding: 14px 24px;
    font-size: 15px;
  }
}

@media (max-width: 480px) {
  .verify-page {
    padding: 20px 16px;
  }

  .verify-card {
    padding: 28px 16px 24px;
    border-radius: 16px;
  }

  .verify-title {
    font-size: 20px;
  }

  .verify-message {
    font-size: 14px;
    margin-bottom: 24px;
  }

  .icon-circle {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
  }

  .icon-circle svg {
    width: 32px;
    height: 32px;
  }

  .spinner {
    width: 44px;
    height: 44px;
    border-width: 3px;
  }

  .loading-card h3 {
    font-size: 18px;
  }

  .btn-primary,
  .btn-secondary {
    padding: 12px 20px;
    font-size: 14px;
    border-radius: 12px;
  }

  .badge {
    font-size: 10px;
    padding: 3px 12px;
  }
}
</style>
