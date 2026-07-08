<template>
  <div class="login-page">
    <div class="container">
      <div class="login-container">
        <!-- Back Button -->
        <router-link to="/" class="back-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to Home
        </router-link>

        <div class="login-header">
          <div class="header-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h1>Welcome Back</h1>
          <p>Sign in to manage your account</p>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="success-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          {{ successMessage }}
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <!-- Email -->
          <div class="form-group" :class="{ 'has-error': errors.email }">
            <label class="form-label">
              Email Address <span class="required">*</span>
            </label>
            <input
              type="email"
              v-model="form.email"
              required
              placeholder="you@example.com"
              class="form-input"
              :class="{ 'input-error': errors.email }"
              @blur="validateField('email')"
              autocomplete="email"
            >
            <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
          </div>

          <!-- Password -->
          <div class="form-group" :class="{ 'has-error': errors.password }">
            <div class="password-wrapper">
              <label class="form-label">
                Password <span class="required">*</span>
              </label>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                required
                placeholder="Enter your password"
                class="form-input"
                :class="{ 'input-error': errors.password }"
                @blur="validateField('password')"
                autocomplete="current-password"
              >
              <button type="button" @click="showPassword = !showPassword" class="password-toggle" tabindex="-1">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
            <span v-if="errors.password" class="field-error">{{ errors.password }}</span>
          </div>

          <!-- Options -->
          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.remember">
              <span>Remember me</span>
            </label>
            <router-link to="/forgot-password" class="forgot-link">Forgot password?</router-link>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="login-btn" :disabled="isLoading">
            <span v-if="isLoading" class="spinner"></span>
            {{ isLoading ? 'Signing in...' : 'Sign In' }}
          </button>

          <p class="register-link">
            Don't have an account? <router-link to="/register">Create one</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { authService } from '../services/api'

export default {
  name: 'LoginPage',
  data() {
    return {
      showPassword: false,
      isLoading: false,
      errorMessage: null,
      successMessage: null,
      errors: {
        email: null,
        password: null
      },
      form: {
        email: '',
        password: '',
        remember: false
      }
    }
  },
  mounted() {
    // Check if user was redirected from verification
    if (this.$route.query.verified === 'true') {
      this.successMessage = 'Email verified successfully! You can now log in.'
    }
  },
  methods: {
    validateField(field) {
      this.errors[field] = null

      switch(field) {
        case 'email':
          if (!this.form.email) {
            this.errors.email = 'Email is required'
          } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
            this.errors.email = 'Please enter a valid email address'
          }
          break

        case 'password':
          if (!this.form.password) {
            this.errors.password = 'Password is required'
          } else if (this.form.password.length < 8) {
            this.errors.password = 'Password must be at least 8 characters'
          }
          break
      }
    },
    validateAll() {
      Object.keys(this.errors).forEach(field => this.validateField(field))
      return !this.errors.email && !this.errors.password
    },
    async handleLogin() {
      // Clear previous messages
      this.errorMessage = null
      this.successMessage = null

      // Validate fields
      if (!this.validateAll()) {
        this.errorMessage = 'Please fix the errors above'
        return
      }

      // Start loading
      this.isLoading = true

      try {
        const response = await authService.login(
          this.form.email,
          this.form.password
        )

        console.log('✅ Login response:', response)

        // Store user data
        const userRole = response.role !== undefined ? response.role : 1

        localStorage.setItem('isLoggedIn', 'true')
        localStorage.setItem('authToken', response.token)
        localStorage.setItem('userEmail', this.form.email)
        localStorage.setItem('userRole', String(userRole))
        localStorage.setItem('isAdmin', String(userRole === 0))

        // Check for plan query
        const planId = this.$route.query.plan
        const returnPath = this.$route.query.return || '/'

        // Redirect based on role
        if (userRole === 0) {
          this.$router.push('/admin/customers')
        } else {
          this.$router.push(returnPath)
        }
      } catch (error) {
        console.error('❌ Login error:', error)

        if (error.response) {
          this.errorMessage = error.response.data?.message ||
                             error.response.data?.error ||
                             'Invalid email or password. Please try again.'
        } else if (error.request) {
          this.errorMessage = 'No response from server. Please check your connection.'
        } else {
          this.errorMessage = error.message || 'Login failed. Please try again.'
        }
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e8ecf1 100%);
  display: flex;
  align-items: center;
  padding: 40px 0;
}

.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.login-container {
  max-width: 440px;
  margin: 0 auto;
  background: #ffffff;
  padding: 40px 36px;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Back Link */
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #8892a8;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 20px;
  transition: color 0.3s;
}

.back-link:hover {
  color: #ff6b35;
}

/* Header */
.login-header {
  text-align: center;
  margin-bottom: 28px;
}

.header-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.header-icon svg {
  background: rgba(255, 107, 53, 0.08);
  padding: 12px;
  border-radius: 50%;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 800;
  color: #1a1a2e;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.login-header p {
  color: #8892a8;
  font-size: 15px;
}

/* Messages */
.success-message {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #f0faf5;
  border: 1px solid #b8e6d0;
  border-radius: 8px;
  color: #1a8a4a;
  font-size: 14px;
  margin-bottom: 16px;
  animation: slideDown 0.3s ease;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #fdf2f2;
  border: 1px solid #f8d7da;
  border-radius: 8px;
  color: #e74c3c;
  font-size: 14px;
  margin-bottom: 16px;
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

/* Form */
form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a2e;
}

.required {
  color: #e74c3c;
  font-weight: 700;
  margin-left: 2px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 10px;
  font-size: 15px;
  transition: all 0.3s;
  font-family: inherit;
  background: #fafbfc;
}

.form-input:hover {
  background: #fff;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.08);
  background: #fff;
}

/* Chrome, Safari, Edge */
.form-input::-webkit-credentials-auto-fill-button,
.form-input::-webkit-caps-lock-indicator,
.form-input::-webkit-contacts-auto-fill-button,
.form-input::-webkit-credentials-auto-fill-button {
  display: none !important;
  visibility: hidden;
  pointer-events: none;
}

/* Firefox */
.form-input::-moz-reveal {
  display: none !important;
}

/* Edge/IE */
.form-input::-ms-reveal,
.form-input::-ms-clear {
  display: none !important;
}

.input-error {
  border-color: #e74c3c !important;
}

.input-error:focus {
  border-color: #e74c3c !important;
  box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.08) !important;
}

.field-error {
  color: #e74c3c;
  font-size: 12px;
  font-weight: 500;
  margin-top: 2px;
}

/* Password */
.password-wrapper {
  position: relative;
}

.password-wrapper .form-input {
  padding-right: 80px;
}

.password-toggle {
  position: absolute;
  right: 12px;
  bottom: 12px;
  background: none;
  border: none;
  color: #8892a8;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 12px;
  transition: color 0.3s;
}

.password-toggle:hover {
  color: #ff6b35;
}

/* Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  margin: 2px 0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #666;
  cursor: pointer;
  font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #ff6b35;
}

.forgot-link {
  color: #ff6b35;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: color 0.3s;
}

.forgot-link:hover {
  color: #e85a2a;
  text-decoration: underline;
}

/* Button */
.login-btn {
  position: relative;
  width: 100%;
  padding: 14px;
  background: #ff6b35;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  margin-top: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.login-btn:hover:not(:disabled) {
  background: #e85a2a;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}

.login-btn:active:not(:disabled) {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Register Link */
.register-link {
  text-align: center;
  font-size: 14px;
  color: #8892a8;
  margin: 0;
}

.register-link a {
  color: #ff6b35;
  text-decoration: none;
  font-weight: 600;
}

.register-link a:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 480px) {
  .login-container {
    padding: 28px 20px;
    border-radius: 16px;
  }

  .login-header h1 {
    font-size: 24px;
  }

  .form-options {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .form-input {
    font-size: 14px;
    padding: 10px 14px;
  }

  .password-toggle {
    font-size: 12px;
    padding: 4px 8px;
  }
}

/* Remove autofill styles */
.form-input:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px #fafbfc inset !important;
  -webkit-text-fill-color: #1a1a2e !important;
}

.form-input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
}
</style>
