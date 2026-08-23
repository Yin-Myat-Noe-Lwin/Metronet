<template>
  <div class="reset-page">
    <div class="container">
      <div class="reset-container">
        <!-- Back Button -->
        <router-link to="/login" class="back-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to Login
        </router-link>

        <div class="reset-header">
          <div class="header-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h1>Reset Password</h1>
          <p>Enter a new password for your account</p>
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
        <div v-if="globalError" class="error-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ globalError }}
        </div>

        <form @submit.prevent="handleSubmit" novalidate>
          <!-- Email -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('email') }">
            <label class="form-label">
              Email Address <span class="required">*</span>
            </label>
            <input
              type="email"
              v-model="form.email"
              required
              placeholder="you@example.com"
              class="form-input"
              :class="{ 'input-error': shouldShowError('email') }"
              @input="onInput('email')"
              @blur="onBlur('email')"
              autocomplete="email"
            >
            <span v-if="shouldShowError('email')" class="field-error">{{ emailError }}</span>
          </div>

          <!-- New Password -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('password') }">
            <div class="password-wrapper">
              <label class="form-label">
                New Password <span class="required">*</span>
              </label>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                required
                placeholder="Minimum 8 characters"
                minlength="8"
                class="form-input"
                :class="{ 'input-error': shouldShowError('password') }"
                @input="onInput('password')"
                @blur="onBlur('password')"
              >
              <button type="button" @click="showPassword = !showPassword" class="password-toggle" tabindex="-1">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
            <span v-if="shouldShowError('password')" class="field-error">{{ passwordError }}</span>
          </div>

          <!-- Confirm Password -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('confirm') }">
            <div class="password-wrapper">
              <label class="form-label">
                Confirm New Password <span class="required">*</span>
              </label>
              <input
                :type="showConfirm ? 'text' : 'password'"
                v-model="form.password_confirmation"
                required
                placeholder="Re-enter new password"
                class="form-input"
                :class="{ 'input-error': shouldShowError('confirm') }"
                @input="onInput('confirm')"
                @blur="onBlur('confirm')"
              >
              <button type="button" @click="showConfirm = !showConfirm" class="password-toggle" tabindex="-1">
                {{ showConfirm ? 'Hide' : 'Show' }}
              </button>
            </div>
            <span v-if="shouldShowError('confirm')" class="field-error">{{ confirmError }}</span>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="reset-btn" :disabled="!isFormValid || isLoading">
            <span v-if="isLoading" class="spinner"></span>
            {{ isLoading ? 'Resetting...' : 'Reset Password' }}
          </button>

          <p class="login-link">
            Remember your password? <router-link to="/login">Sign in</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { authService } from '../services/api'

export default {
  name: 'ResetPasswordPage',
  data() {
    return {
      showPassword: false,
      showConfirm: false,
      isLoading: false,
      globalError: null,
      successMessage: null,
      touched: { email: false, password: false, confirm: false },
      form: {
        email: '',
        password: '',
        password_confirmation: '',
        token: '',
      },
    }
  },
  mounted() {
    // Get token from route param
    this.form.token = this.$route.params.token || this.$route.query.token || ''
    // If token not found, show error
    if (!this.form.token) {
      this.globalError = 'Invalid or missing reset token. Please request a new link.'
    }
  },
  computed: {
    emailError() {
      if (!this.touched.email) return null
      if (!this.form.email) return 'Email address is required'
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        return 'Please enter a valid email address'
      }
      return null
    },
    passwordError() {
      if (!this.touched.password) return null
      if (!this.form.password) return 'New password is required'
      if (this.form.password.length < 8) return 'Password must be at least 8 characters'
      return null
    },
    confirmError() {
      if (!this.touched.confirm) return null
      if (!this.form.password_confirmation) return 'Please confirm your password'
      if (this.form.password !== this.form.password_confirmation) {
        return 'Passwords do not match'
      }
      return null
    },
    showEmailError() { return this.emailError !== null },
    showPasswordError() { return this.passwordError !== null },
    showConfirmError() { return this.confirmError !== null },
    isFormValid() {
      return !this.emailError && !this.passwordError && !this.confirmError &&
             this.form.email && this.form.password && this.form.password_confirmation
    },
  },
  methods: {
    // Handle input events - mark field as touched and validate
    onInput(field) {
      this.touched[field] = true
      if (field === 'email') this.validateEmail()
      else if (field === 'password') this.validatePassword()
      else if (field === 'confirm') this.validateConfirm()
    },

    // Handle blur events - validate on leaving field
    onBlur(field) {
      this.touched[field] = true
      if (field === 'email') this.validateEmail()
      else if (field === 'password') this.validatePassword()
      else if (field === 'confirm') this.validateConfirm()
    },

    validateEmail() { this.touched.email = true },
    validatePassword() { this.touched.password = true },
    validateConfirm() { this.touched.confirm = true },

    // Check if we should show error for a field
    shouldShowError(field) {
      const errorMap = {
        email: this.emailError,
        password: this.passwordError,
        confirm: this.confirmError,
      }
      return this.touched[field] && errorMap[field] !== null
    },

    async handleSubmit() {
      this.globalError = null
      this.successMessage = null
      this.touched.email = true
      this.touched.password = true
      this.touched.confirm = true

      if (!this.isFormValid) {
        const firstInvalid = document.querySelector('.input-error')
        if (firstInvalid) firstInvalid.focus()
        return
      }

      if (!this.form.token) {
        this.globalError = 'Reset token is missing. Please request a new reset link.'
        return
      }

      this.isLoading = true
      try {
        const payload = {
          email: this.form.email,
          password: this.form.password,
          password_confirmation: this.form.password_confirmation,
          token: this.form.token,
        }
        const response = await authService.resetPassword(payload)
        this.successMessage = response.message || 'Password reset successfully! Redirecting to login...'
        setTimeout(() => {
          this.$router.push('/login')
        }, 3000)
      } catch (error) {
        console.error('Reset password error:', error)
        this.globalError = error.response?.data?.message || 'Failed to reset password. Please try again.'
      } finally {
        this.isLoading = false
      }
    },
  },
}
</script>

<style scoped>
.reset-page {
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
.reset-container {
  max-width: 440px;
  margin: 0 auto;
  background: #ffffff;
  padding: 40px 36px;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
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
.back-link:hover { color: #ff6b35; }
.reset-header {
  text-align: center;
  margin-bottom: 28px;
}
.header-icon { display: flex; justify-content: center; margin-bottom: 12px; }
.header-icon svg {
  background: rgba(255, 107, 53, 0.08);
  padding: 12px;
  border-radius: 50%;
}
.reset-header h1 {
  font-size: 28px;
  font-weight: 800;
  color: #1a1a2e;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}
.reset-header p {
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
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
form { display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 14px; font-weight: 600; color: #1a1a2e; }
.required { color: #e74c3c; font-weight: 700; margin-left: 2px; }

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
.form-input:hover { background: #fff; }

/* ====== FOCUS COLOR = WARNING (Yellow/Amber) ====== */
.form-input:focus {
  outline: none;
  border-color: #f0c27a !important;
  box-shadow: 0 0 0 4px rgba(240, 194, 122, 0.2) !important;
  background: #fffbf5 !important;
}

/* ====== ERROR COLOR = Red/Pink (only shows when there's an error) ====== */
.has-error .form-input {
  border-color: #f0a0a0 !important;
  background: #fff8f8 !important;
}

.has-error .form-input:focus {
  border-color: #e88383 !important;
  box-shadow: 0 0 0 4px rgba(240, 160, 160, 0.2) !important;
}

.input-error {
  border-color: #f0a0a0 !important;
  background: #fff8f8 !important;
}

.input-error:focus {
  border-color: #e88383 !important;
  box-shadow: 0 0 0 4px rgba(240, 160, 160, 0.2) !important;
}

.field-error {
  color: #e74c3c;
  font-size: 12px;
  font-weight: 500;
  margin-top: 2px;
}

.password-wrapper { position: relative; }
.password-wrapper .form-input { padding-right: 80px; }
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
.password-toggle:hover { color: #ff6b35; }

.reset-btn {
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
.reset-btn:hover:not(:disabled) {
  background: #e85a2a;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}
.reset-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.login-link {
  text-align: center;
  font-size: 14px;
  color: #8892a8;
  margin: 0;
}
.login-link a { color: #ff6b35; text-decoration: none; font-weight: 600; }
.login-link a:hover { text-decoration: underline; }

@media (max-width: 480px) {
  .reset-container { padding: 28px 20px; border-radius: 16px; }
  .reset-header h1 { font-size: 24px; }
  .form-input { font-size: 14px; padding: 10px 14px; }
  .password-toggle { font-size: 12px; padding: 4px 8px; }
}
</style>
