<template>
  <div class="register-page">
    <div class="container">
      <div class="register-container">
        <div class="register-header">
          <h1>Create Account</h1>
          <p>Join MetroNet today</p>
        </div>

        <!-- Error Message at Top -->
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleRegister">
          <div class="form-group">
             <label class="form-label">
              Full Name <span class="required">*</span>
            </label>
            <input
              type="text"
              v-model="form.name"
              required
              placeholder="e.g., Leona Louisa"
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label class="form-label">
              Email <span class="required">*</span>
            </label>
            <input
              type="email"
              v-model="form.email"
              required
              placeholder="you@example.com"
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label class="form-label">
              Phone Number <span class="required">*</span>
            </label>
            <input
              type="tel"
              v-model="form.phone"
              required
              placeholder="e.g., 09123456789"
              class="form-input"
            >
          </div>

          <div class="form-group">
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
              >
              <button type="button" @click="showPassword = !showPassword" class="password-toggle">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
          </div>

          <div class="form-group">
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
              >
              <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="password-toggle">
                {{ showConfirmPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
          </div>

          <label class="checkbox-label">
            <input type="checkbox" v-model="form.agreeTerms" required>
            <span>I agree to Terms & Privacy</span>
          </label>

          <button type="submit" class="register-btn" :disabled="!isFormValid || isLoading">
            {{ isLoading ? 'Creating...' : 'Create Account' }}
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
      errorMessage: null,
      form: {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',  // Changed from confirmPassword to match Laravel
        agreeTerms: false
      }
    }
  },
  computed: {
    isFormValid() {
      return (
        this.form.name &&
        this.form.email &&
        this.form.phone &&
        this.form.password.length >= 8 &&
        this.form.password_confirmation &&
        this.form.password === this.form.password_confirmation &&
        this.form.agreeTerms
      )
    }
  },
  methods: {
    async handleRegister() {
      // Clear previous error
      this.errorMessage = null

      // Validate password match
      if (this.form.password !== this.form.password_confirmation) {
        this.errorMessage = 'Passwords do not match'
        return
      }

      this.isLoading = true

      try {
        // Send data matching Laravel validation
        const userData = {
          name: this.form.name,
          email: this.form.email,
          phone_num: this.form.phone,
          password: this.form.password,
          password_confirmation: this.form.password_confirmation  // Laravel expects this
        }

        const response = await authService.register(userData)
        console.log('Registration response:', response)

        alert('Account created! Please verify your email.')
        this.$router.push('/login')
      } catch (error) {
        console.error('Registration error:', error)

        // Handle validation errors from Laravel
        if (error.response?.data?.errors) {
          const errors = error.response.data.errors
          const firstError = Object.values(errors)[0]?.[0]
          this.errorMessage = firstError || 'Validation failed'
        } else {
          this.errorMessage = error.response?.data?.message || 'Registration failed. Please try again.'
        }
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  background: #f8f9fa;
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
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.register-header {
  text-align: center;
  margin-bottom: 24px;
}

.register-header h1 {
  font-size: 26px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 4px;
}

.register-header p {
  color: #8892a8;
  font-size: 14px;
}

/* Error Message at Top */
.error-message {
  padding: 12px 16px;
  background: #fdf2f2;
  border: 1px solid #f8d7da;
  border-radius: 8px;
  color: #e74c3c;
  font-size: 14px;
  text-align: center;
  margin-bottom: 16px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 14px;
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
  border-radius: 8px;
  font-size: 15px;
  transition: border-color 0.3s, box-shadow 0.3s;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
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
}

.password-toggle:hover {
  color: #ff6b35;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 13px;
  color: #555;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #ff6b35;
  flex-shrink: 0;
}

.register-btn {
  width: 100%;
  padding: 14px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  margin-top: 4px;
}

.register-btn:hover:not(:disabled) {
  background: #e85a2a;
}

.register-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  }

  .register-header h1 {
    font-size: 22px;
  }

  .form-input {
    font-size: 14px;
    padding: 10px 14px;
  }
}
</style>
