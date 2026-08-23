<template>
  <div class="register-page">
    <div class="container">
      <div class="register-container">
        <!-- Back Button -->
        <router-link to="/" class="back-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to Home
        </router-link>

        <div class="register-header">
          <div class="header-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h1>Create Account</h1>
          <p>Join MetroNet and get connected</p>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="success-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          {{ successMessage }}
        </div>

        <!-- Global Error Message (from server) -->
        <div v-if="globalError" class="error-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ globalError }}
        </div>

        <form @submit.prevent="handleRegister" novalidate>
          <!-- Full Name -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('name') }">
            <label class="form-label">
              Full Name <span class="required">*</span>
            </label>
            <input
              type="text"
              v-model="form.name"
              required
              placeholder="e.g., Leona Louisa"
              class="form-input"
              :class="{ 'input-error': shouldShowError('name') }"
              @input="onInput('name')"
              @blur="onBlur('name')"
            >
            <span v-if="shouldShowError('name')" class="field-error">{{ nameError }}</span>
          </div>

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
            >
            <span v-if="shouldShowError('email')" class="field-error">{{ emailError }}</span>
          </div>

          <!-- Phone -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('phone') }">
            <label class="form-label">
              Phone Number <span class="required">*</span>
            </label>
            <input
              type="tel"
              v-model="form.phone"
              required
              placeholder="e.g., 09123456789"
              class="form-input"
              :class="{ 'input-error': shouldShowError('phone') }"
              @input="onInput('phone')"
              @blur="onBlur('phone')"
            >
            <span v-if="shouldShowError('phone')" class="field-error">{{ phoneError }}</span>
          </div>

          <!-- Password -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('password') }">
            <label class="form-label">
              Password <span class="required">*</span>
            </label>
            <div class="password-wrapper">
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
            <!-- Password Strength Indicator -->
            <div v-if="form.password.length > 0" class="password-strength">
              <div class="strength-bar">
                <div class="strength-fill" :class="passwordStrength.class" :style="{ width: passwordStrength.percentage + '%' }"></div>
              </div>
              <span class="strength-text">{{ passwordStrength.label }}</span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('confirm') }">
            <label class="form-label">
              Confirm Password <span class="required">*</span>
            </label>
            <div class="password-wrapper">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="form.password_confirmation"
                required
                placeholder="Re-enter your password"
                class="form-input"
                :class="{ 'input-error': shouldShowError('confirm') }"
                @input="onInput('confirm')"
                @blur="onBlur('confirm')"
              >
              <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="password-toggle" tabindex="-1">
                {{ showConfirmPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
            <span v-if="shouldShowError('confirm')" class="field-error">{{ confirmError }}</span>
          </div>

          <!-- Terms -->
          <div class="form-group" :class="{ 'has-error': shouldShowError('terms') }">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.agreeTerms" @change="onChangeTerms">
              <span>
                I agree to the
                <router-link to="/terms" class="text-link">Terms of Service</router-link>
                and
                <router-link to="/privacy" class="text-link">Privacy Policy</router-link>
              </span>
            </label>
            <span v-if="shouldShowError('terms')" class="field-error">{{ termsError }}</span>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="register-btn" :disabled="!isFormValid || isLoading">
            <span v-if="isLoading" class="spinner"></span>
            {{ isLoading ? 'Creating Account...' : 'Create Account' }}
          </button>

          <p class="login-link">
            Already have an account? <router-link to="/login">Sign in</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { authService } from '../services/api'

export default {
  name: 'RegisterPage',
  data() {
    return {
      showPassword: false,
      showConfirmPassword: false,
      isLoading: false,
      globalError: null,
      successMessage: null,
      touched: {
        name: false,
        email: false,
        phone: false,
        password: false,
        confirm: false,
        terms: false,
      },
      form: {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        agreeTerms: false,
      },
    }
  },
  computed: {
    // Name validation
    nameError() {
      if (!this.touched.name) return null
      if (!this.form.name) return 'Full name is required'
      if (this.form.name.length < 2) return 'Name must be at least 2 characters'
      return null
    },
    // Email validation
    emailError() {
      if (!this.touched.email) return null
      if (!this.form.email) return 'Email address is required'
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        return 'Please enter a valid email address (e.g., name@domain.com)'
      }
      return null
    },
    // Phone validation
    phoneError() {
      if (!this.touched.phone) return null
      if (!this.form.phone) return 'Phone number is required'
      if (!/^09\d{9}$/.test(this.form.phone)) {
        return 'Phone number must start with 09 and be 11 digits long'
      }
      return null
    },
    // Password validation
    passwordError() {
      if (!this.touched.password) return null
      if (!this.form.password) return 'Password is required'
      if (this.form.password.length < 8) return 'Password must be at least 8 characters'
      return null
    },
    // Confirm password validation
    confirmError() {
      if (!this.touched.confirm) return null
      if (!this.form.password_confirmation) return 'Please confirm your password'
      if (this.form.password !== this.form.password_confirmation) {
        return 'Passwords do not match'
      }
      return null
    },
    // Terms validation
    termsError() {
      if (!this.touched.terms) return null
      if (!this.form.agreeTerms) return 'You must agree to the Terms of Service and Privacy Policy'
      return null
    },
    // Show errors only when touched
    showNameError() { return this.nameError !== null },
    showEmailError() { return this.emailError !== null },
    showPhoneError() { return this.phoneError !== null },
    showPasswordError() { return this.passwordError !== null },
    showConfirmError() { return this.confirmError !== null },
    showTermsError() { return this.termsError !== null },

    // Overall form validity
    isFormValid() {
      return !this.nameError &&
             !this.emailError &&
             !this.phoneError &&
             !this.passwordError &&
             !this.confirmError &&
             !this.termsError &&
             this.form.name &&
             this.form.email &&
             this.form.phone &&
             this.form.password &&
             this.form.password_confirmation &&
             this.form.agreeTerms
    },

    // Password strength indicator
    passwordStrength() {
      const password = this.form.password
      if (!password) return { class: 'weak', label: 'Weak', percentage: 0 }

      let strength = 0
      if (password.length >= 8) strength += 25
      if (password.match(/[a-z]/)) strength += 20
      if (password.match(/[A-Z]/)) strength += 20
      if (password.match(/[0-9]/)) strength += 20
      if (password.match(/[^a-zA-Z0-9]/)) strength += 15

      if (strength <= 30) return { class: 'weak', label: 'Weak', percentage: 30 }
      if (strength <= 60) return { class: 'medium', label: 'Medium', percentage: 60 }
      if (strength <= 80) return { class: 'good', label: 'Good', percentage: 80 }
      return { class: 'strong', label: 'Strong', percentage: 100 }
    },
  },
  methods: {
    // Handle input events - mark field as touched and validate
    onInput(field) {
      this.touched[field] = true
      // Trigger validation
      if (field === 'name') this.validateName()
      else if (field === 'email') this.validateEmail()
      else if (field === 'phone') this.validatePhone()
      else if (field === 'password') this.validatePassword()
      else if (field === 'confirm') this.validateConfirmPassword()
    },

    // Handle blur events - validate on leaving field
    onBlur(field) {
      this.touched[field] = true
      if (field === 'name') this.validateName()
      else if (field === 'email') this.validateEmail()
      else if (field === 'phone') this.validatePhone()
      else if (field === 'password') this.validatePassword()
      else if (field === 'confirm') this.validateConfirmPassword()
    },

    // Handle terms checkbox change
    onChangeTerms() {
      this.touched.terms = true
      this.validateTerms()
    },

    // Individual validation methods
    validateName() { /* touched already set */ },
    validateEmail() { /* touched already set */ },
    validatePhone() { /* touched already set */ },
    validatePassword() { /* touched already set */ },
    validateConfirmPassword() { /* touched already set */ },
    validateTerms() { /* touched already set */ },

    // Check if we should show error for a field
    shouldShowError(field) {
      // Map field names to their error state
      const errorMap = {
        name: this.nameError,
        email: this.emailError,
        phone: this.phoneError,
        password: this.passwordError,
        confirm: this.confirmError,
        terms: this.termsError,
      }

      // Only show error if field is touched AND there's an error
      return this.touched[field] && errorMap[field] !== null
    },

    async handleRegister() {
      // Clear previous server error
      this.globalError = null

      // Mark all fields as touched
      this.touched.name = true
      this.touched.email = true
      this.touched.phone = true
      this.touched.password = true
      this.touched.confirm = true
      this.touched.terms = true

      // If form is invalid, focus the first invalid field and stop
      if (!this.isFormValid) {
        const firstInvalid = document.querySelector('.input-error')
        if (firstInvalid) {
          firstInvalid.focus()
        }
        return
      }

      this.isLoading = true
      try {
        const payload = {
          name: this.form.name,
          email: this.form.email,
          phone_num: this.form.phone,
          password: this.form.password,
          password_confirmation: this.form.password_confirmation,
        }

        const response = await authService.register(payload)
        console.log('Registration success:', response)

        this.successMessage = 'Account created successfully! Please check your email to verify your account.'

        // Redirect to login after a delay
        setTimeout(() => {
          this.$router.push('/login')
        }, 3000)

      } catch (error) {
        console.error('Registration error:', error)

        if (error.response?.data?.errors) {
          // Laravel validation errors
          const fieldErrors = error.response.data.errors
          // Map them to our fields
          for (const [key, messages] of Object.entries(fieldErrors)) {
            let fieldKey = key
            if (key === 'phone_num') fieldKey = 'phone'
            if (this.touched.hasOwnProperty(fieldKey)) {
              if (messages.length > 0) {
                this.globalError = messages[0]
                break
              }
            }
          }
          if (!this.globalError) {
            this.globalError = 'Please fix the errors below.'
          }
        } else {
          this.globalError = error.response?.data?.message || 'Registration failed. Please try again.'
        }
      } finally {
        this.isLoading = false
      }
    },
  },
}
</script>

<style scoped>
.register-page {
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

.register-container {
  max-width: 440px;
  margin: 0 auto;
  background: #fff;
  padding: 40px 36px;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
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

.register-header {
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

.register-header h1 {
  font-size: 28px;
  font-weight: 800;
  color: #1a1a2e;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.register-header p {
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

.password-wrapper {
  position: relative;
}

.password-wrapper .form-input {
  padding-right: 80px;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
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

.password-strength {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 4px;
}

.strength-bar {
  flex: 1;
  height: 3px;
  background: #e8ecf1;
  border-radius: 3px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: 3px;
  transition: all 0.3s ease;
}

.strength-fill.weak {
  background: #e74c3c;
  width: 30%;
}

.strength-fill.medium {
  background: #f39c12;
  width: 60%;
}

.strength-fill.good {
  background: #3498db;
  width: 80%;
}

.strength-fill.strong {
  background: #2ecc71;
  width: 100%;
}

.strength-text {
  font-size: 12px;
  font-weight: 500;
  color: #8892a8;
  min-width: 50px;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  font-size: 13px;
  color: #555;
  padding: 2px 0;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #ff6b35;
  flex-shrink: 0;
  margin-top: 1px;
}

.text-link {
  color: #ff6b35;
  text-decoration: none;
  font-weight: 500;
}

.text-link:hover {
  text-decoration: underline;
}

.register-btn {
  position: relative;
  width: 100%;
  padding: 14px;
  background: #ff6b35;
  color: #fff;
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

.register-btn:hover:not(:disabled) {
  background: #e85a2a;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}

.register-btn:active:not(:disabled) {
  transform: translateY(0);
}

.register-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.login-link {
  text-align: center;
  font-size: 14px;
  color: #8892a8;
  margin: 0;
}

.login-link a {
  color: #ff6b35;
  text-decoration: none;
  font-weight: 600;
}

.login-link a:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .register-container {
    padding: 28px 20px;
    border-radius: 16px;
  }
  .register-header h1 {
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
