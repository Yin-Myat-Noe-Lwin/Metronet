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
        password: ''
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
        this.errorMessage = 'Please check the highlighted fields and provide the correct information.'
        return
      }

      // Start loading
      this.isLoading = true

      try {
        const response = await authService.login(
          this.form.email,
          this.form.password
        )

        console.log('Full Login Response:', JSON.stringify(response, null, 2))

        // Extract user data from response
        let userData = null
        let token = null
        let role = 1

        // Try different response structures
        if (response.user) {
          userData = response.user
          console.log('Found user in response.user')
        } else if (response.customer) {
          userData = response.customer
          console.log('Found user in response.customer')
        } else if (response.data && response.data.user) {
          userData = response.data.user
          console.log('Found user in response.data.user')
        } else if (response.data && response.data.customer) {
          userData = response.data.customer
          console.log('Found user in response.data.customer')
        } else if (response.data) {
          userData = response.data
          console.log('Found user in response.data')
        } else if (response.id || response.name || response.email) {
          userData = response
          console.log('Response itself is user data')
        }

        // Get token
        token = response.token || response.access_token || response.accessToken || ''

        // Get role
        role = response.role !== undefined ? response.role : 1

        // If still no userData, create from email
        if (!userData) {
          console.warn('No user data found, creating from email')
          userData = {
            email: this.form.email,
            name: this.form.email.split('@')[0]
          }
        }

        console.log('Final userData:', userData)
        // Extract ALL user details
        const userId = userData.id ||
                       userData.user_id ||
                       userData.customer_id ||
                       userData.customerId ||
                       null

        const userName = userData.name ||
                         userData.full_name ||
                         userData.username ||
                         userData.display_name ||
                         userData.email?.split('@')[0] ||
                         ''

        const userEmail = userData.email || this.form.email

        const userPhone = userData.phone_num ||
                          userData.phone ||
                          userData.phone_number ||
                          userData.mobile ||
                          userData.contact_number ||
                          ''

        const userRole = userData.role !== undefined ?
                          userData.role :
                          role

        const userStatus = userData.status !== undefined ?
                            userData.status :
                            (userData.is_active !== undefined ? userData.is_active : 1)

        console.log('Extracted user details:', {
          userId,
          userName,
          userEmail,
          userPhone,
          userRole,
          userStatus,
          hasToken: !!token
        })

        // Create user data object
        const userDataToStore = {
          id: userId,
          name: userName,
          email: userEmail,
          phone_num: userPhone,
          role: userRole,
          status: userStatus
        }

        // Save ALL data to localStorage
        // Save token
        if (token) {
          localStorage.setItem('authToken', token)
        }

        // Save user data
        localStorage.setItem('isLoggedIn', 'true')
        localStorage.setItem('userEmail', userEmail)
        localStorage.setItem('userName', userName)
        localStorage.setItem('userRole', String(userRole))
        localStorage.setItem('isAdmin', String(userRole === 0))
        localStorage.setItem('userId', String(userId || ''))
        localStorage.setItem('userData', JSON.stringify(userDataToStore))

        // Save phone
        if (userPhone) {
          localStorage.setItem('userPhone', userPhone)
        }
        // Verify saved data
        console.log('Verified localStorage:')
        console.log('  - userData:', localStorage.getItem('userData'))
        console.log('  - userName:', localStorage.getItem('userName'))
        console.log('  - userEmail:', localStorage.getItem('userEmail'))
        console.log('  - userRole:', localStorage.getItem('userRole'))
        console.log('  - userId:', localStorage.getItem('userId'))
        console.log('  - userPhone:', localStorage.getItem('userPhone'))

        // Dispatch event and redirect
        window.dispatchEvent(new CustomEvent('userDataUpdated'))

        const returnPath = this.$route.query.return || '/'

        // Show success message
        this.successMessage = `Welcome back, ${userName || 'User'}!`

        // Redirect after delay
        setTimeout(() => {
          if (userRole === 0) {
            this.$router.push('/admin/customers')
          } else {
            this.$router.push(returnPath)
          }
        }, 800)

      } catch (error) {
        console.error('Login error:', error)
        console.error('Error details:', error.response?.data || error.message)

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

.form-input::-webkit-credentials-auto-fill-button,
.form-input::-webkit-caps-lock-indicator,
.form-input::-webkit-contacts-auto-fill-button,
.form-input::-webkit-credentials-auto-fill-button {
  display: none !important;
  visibility: hidden;
  pointer-events: none;
}

.form-input::-moz-reveal {
  display: none !important;
}

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

.form-input:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px #fafbfc inset !important;
  -webkit-text-fill-color: #1a1a2e !important;
}

.form-input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
}

@media (max-width: 480px) {
  .login-container {
    padding: 28px 20px;
    border-radius: 16px;
  }

  .login-header h1 {
    font-size: 24px;
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
</style>
