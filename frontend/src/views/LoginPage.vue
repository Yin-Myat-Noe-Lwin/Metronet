<template>
  <div class="login-page">
    <div class="container">
      <div class="login-container">
        <div class="login-header">
          <h1>Welcome Back</h1>
          <p>Sign in to manage your account</p>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <div class="form-group">
            <input
              type="email"
              v-model="form.email"
              required
              placeholder="Email Address"
              class="form-input"
              :class="{ 'input-error': errorMessage }"
            >
          </div>

          <div class="form-group">
            <div class="password-wrapper">
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                required
                placeholder="Password"
                class="form-input"
                :class="{ 'input-error': errorMessage }"
              >
              <button type="button" @click="showPassword = !showPassword" class="password-toggle">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
          </div>

          <button type="submit" class="login-btn" :disabled="isLoading">
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
      form: {
        email: '',
        password: '',
        remember: false
      }
    }
  },
  methods: {
    async handleLogin() {
      // Prevent default form submission
      event?.preventDefault?.()

      // Clear previous error
      this.errorMessage = null

      // Validate fields
      if (!this.form.email || !this.form.password) {
        this.errorMessage = 'Please enter email and password'
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

        // ✅ IMPORTANT: Store the role correctly (0 = admin, 1 = user)
        const userRole = response.role !== undefined ? response.role : 1

        // Store user data
        localStorage.setItem('isLoggedIn', 'true')
        localStorage.setItem('authToken', response.token)
        localStorage.setItem('userEmail', this.form.email)
        localStorage.setItem('userRole', String(userRole)) // Store as string

        // Check if admin (role 0)
        const isAdmin = userRole === 0
        localStorage.setItem('isAdmin', String(isAdmin))

        console.log('✅ Stored userRole:', userRole)
        console.log('✅ isAdmin:', isAdmin)

        // Check for plan query
        const planId = this.$route.query.plan
        const returnPath = this.$route.query.return || '/'

        // Redirect based on role (0 = admin, 1 = user)
        if (userRole === 0) {
          console.log('✅ Redirecting to admin customers')
          this.$router.push('/admin/customers')
        } else {
          console.log('✅ Redirecting to:', returnPath)
          this.$router.push(returnPath)
        }
      } catch (error) {
        console.error('❌ Login error:', error)

        // Extract error message
        if (error.response) {
          this.errorMessage = error.response.data?.message ||
                             error.response.data?.error ||
                             'Login failed. Please try again.'
        } else if (error.request) {
          this.errorMessage = 'No response from server. Please check your connection.'
        } else {
          this.errorMessage = error.message || 'Login failed. Please try again.'
        }

        // Reset loading state
        this.isLoading = false
      } finally {
        // Ensure loading is false if not already
        if (this.isLoading) {
          this.isLoading = false
        }
      }
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  padding: 60px 0;
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
  padding: 48px 40px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.login-header {
  text-align: center;
  margin-bottom: 28px;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 4px;
}

.login-header p {
  color: #8892a8;
  font-size: 14px;
}

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
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-input {
  padding: 12px 16px;
  border: 2px solid #e8ecf1;
  border-radius: 8px;
  font-size: 15px;
  transition: border-color 0.3s, box-shadow 0.3s;
  font-family: inherit;
  width: 100%;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
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
  border-radius: 4px;
  transition: color 0.3s;
}

.password-toggle:hover {
  color: #ff6b35;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #666;
  cursor: pointer;
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
}

.forgot-link:hover {
  text-decoration: underline;
}

.login-btn {
  width: 100%;
  padding: 14px;
  background: #ff6b35;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  margin-top: 4px;
}

.login-btn:hover:not(:disabled) {
  background: #e85a2a;
  transform: scale(1.01);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

@media (max-width: 480px) {
  .login-container {
    padding: 32px 24px;
    margin: 0 16px;
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
}
</style>
