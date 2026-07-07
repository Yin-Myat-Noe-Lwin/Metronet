import axios from 'axios'
import { config } from '../config/api'

console.log('API Base URL:', config.apiUrl)

// Create axios instance with base URL
const apiClient = axios.create({
  baseURL: config.apiUrl,
  timeout: config.apiTimeout,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('authToken')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    console.log('Making API request to:', config.url)
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    console.log('API response:', response.status, response.config.url)
    return response
  },
  (error) => {
    console.error('API Error:', error.response?.status, error.response?.data)

    const isLoginEndpoint = error.config?.url?.includes('/login')

    if (error.response?.status === 401 && !isLoginEndpoint) {
      localStorage.removeItem('authToken')
      localStorage.removeItem('isLoggedIn')
      localStorage.removeItem('userRole')
      localStorage.removeItem('isAdmin')
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)

// Auth Service
export const authService = {
  async login(email, password) {
    try {
      const response = await apiClient.post('/api/login', { email, password })

      if (response.data.token) {
        localStorage.setItem('authToken', response.data.token)
        localStorage.setItem('isLoggedIn', 'true')
        localStorage.setItem('userEmail', email)

        const userRole = response.data.role !== undefined ? response.data.role : 1
        localStorage.setItem('userRole', String(userRole))

        if (userRole === 0) {
          localStorage.setItem('isAdmin', 'true')
        } else {
          localStorage.setItem('isAdmin', 'false')
        }

        if (response.data.user?.name) {
          localStorage.setItem('userName', response.data.user.name)
        } else if (response.data.customer?.name) {
          localStorage.setItem('userName', response.data.customer.name)
        }
      }
      return response.data
    } catch (error) {
      console.error('Login API error:', error)
      throw error
    }
  },

  async register(userData) {
    try {
      const response = await apiClient.post('/api/register', userData)
      if (response.data.token) {
        localStorage.setItem('authToken', response.data.token)
        localStorage.setItem('isLoggedIn', 'true')
        localStorage.setItem('userData', JSON.stringify(response.data.customer || response.data.user))
        localStorage.setItem('userName', response.data.customer?.name || response.data.user?.name || '')
        localStorage.setItem('userEmail', response.data.customer?.email || response.data.user?.email || '')
        localStorage.setItem('userRole', '1')
        localStorage.setItem('isAdmin', 'false')
      }
      return response.data
    } catch (error) {
      console.error('Register error:', error)
      throw error
    }
  },

  async logout() {
    try {
      await apiClient.post('/api/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      localStorage.removeItem('authToken')
      localStorage.removeItem('isLoggedIn')
      localStorage.removeItem('userData')
      localStorage.removeItem('userName')
      localStorage.removeItem('userEmail')
      localStorage.removeItem('userRole')
      localStorage.removeItem('isAdmin')
    }
  },

  async getUser() {
    const response = await apiClient.get('/api/profile')
    return response.data
  },

  async updateProfile(data) {
    try {
      const response = await apiClient.patch('/api/profile', data)
      return response.data
    } catch (error) {
      console.error('Update profile error:', error)
      throw error
    }
  }
}

// Address Service
export const addressService = {
  async addAddress(data) {
    const response = await apiClient.post('/api/address', data)
    return response.data
  },

  async updateAddress(id, data) {
    const response = await apiClient.patch(`/api/address/${id}`, data)
    return response.data
  },

  async deleteAddress(id) {
    const response = await apiClient.delete(`/api/address/${id}`)
    return response.data
  }
}

export const serviceAreasService = {
  async getServiceAreas() {
    try {
      const response = await apiClient.get('/api/service-areas')
      return response.data
    } catch (error) {
      console.error('Error fetching service areas:', error)
      throw error
    }
  },

  async getAdminServiceAreas() {
    try {
      const response = await apiClient.get('/api/admin/service-areas')
      console.log('Admin service areas response:', response.data)
      return response.data
    } catch (error) {
      console.error('Error fetching admin service areas:', error)
      throw error
    }
  },

  async createServiceArea(data) {
    try {
      const response = await apiClient.post('/api/admin/service-areas', data)
      return response.data
    } catch (error) {
      console.error('Error creating service area:', error)
      throw error
    }
  },

  async updateServiceArea(id, data) {
    try {
      const response = await apiClient.patch(`/api/admin/service-areas/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating service area:', error)
      throw error
    }
  },

  async deleteServiceArea(id) {
    try {
      const response = await apiClient.delete(`/api/admin/service-areas/${id}`)
      return response.data
    } catch (error) {
      console.error('Error deleting service area:', error)
      throw error
    }
  },
}

// ✅ Customer Service - Only View and Delete
export const customerService = {
  async getCustomers() {
    try {
      const response = await apiClient.get(`/api/admin/customers`)
      console.log('Customers response:', response.data)
      return response.data
    } catch (error) {
      console.error('Error fetching customers:', error)
      throw error
    }
  },

  async getCustomer(id) {
    try {
      const response = await apiClient.get(`/api/admin/customers/${id}`)
      console.log('Customer detail response:', response.data)
      return response.data
    } catch (error) {
      console.error('Error fetching customer:', error)
      throw error
    }
  },

  async deleteCustomer(id) {
    try {
      const response = await apiClient.delete(`/api/admin/customers/${id}`)
      return response.data
    } catch (error) {
      console.error('Error deleting customer:', error)
      throw error
    }
  }
}

// Plans Service
export const plansService = {
  async getPlans() {
    try {
      const response = await apiClient.get('/api/plans')
      return response.data
    } catch (error) {
      console.error('Error fetching plans:', error)
      throw error
    }
  },

  async getAdminPlans() {
    try {
      const response = await apiClient.get('/api/admin/plans')
      console.log('Admin plans response:', response.data)
      return response.data
    } catch (error) {
      console.error('Error fetching admin plans:', error)
      throw error
    }
  },

  async createPlan(data) {
    try {
      const response = await apiClient.post('/api/admin/plans', data)
      return response.data
    } catch (error) {
      console.error('Error creating plan:', error)
      throw error
    }
  },

  async updatePlan(id, data) {
    try {
      const response = await apiClient.patch(`/api/admin/plans/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating plan:', error)
      throw error
    }
  },

  async deletePlan(id) {
    try {
      const response = await apiClient.delete(`/api/admin/plans/${id}`)
      return response.data
    } catch (error) {
      console.error('Error deleting plan:', error)
      throw error
    }
  },

  async getPlan(id) {
    const response = await apiClient.get(`/api/plans/${id}`)
    return response.data
  },

  async subscribe(planId, data) {
    const response = await apiClient.post(`/api/subscribe/${planId}`, data)
    return response.data
  },

  async getServiceStatus() {
    const response = await apiClient.post('/api/service-status')
    return response.data
  }
}

// Subscriptions Service
export const subscriptionsService = {
  // async getSubscriptions() {
  //   const response = await apiClient.get('/api/subscriptions')
  //   return response.data
  // },

  async getSubscriptions() {
    const response = await apiClient.get('/api/admin/subscriptions')
    return response.data
  },

  async cancelSubscription(id) {
    const response = await apiClient.delete(`/api/subscriptions/${id}`)
    return response.data
  }
}

// Invoices Service
export const invoicesService = {
  async getInvoices() {
    const response = await apiClient.get('/api/invoices')
    return response.data
  },

  async getAdminInvoices() {
    const response = await apiClient.get('/api/admin/invoices')
    return response.data
  },

  async getInvoice(id) {
    const response = await apiClient.get(`/api/invoices/${id}`)
    return response.data
  },

  async getAdminInvoice(id) {
    const response = await apiClient.get(`/api/admin/invoices/${id}`)
    return response.data
  }
}

// Payment Service
// Payment Service
export const paymentService = {
  async payInvoice(invoiceId, data) {
    const response = await apiClient.post(`/api/pay/${invoiceId}`, data)
    return response.data
  },

  async getPayments() {
    const response = await apiClient.get('/api/payments')
    return response.data
  },

  async getAdminPayments() {
    const response = await apiClient.get('/api/admin/payments')
    return response.data
  }
}
// Notifications Service
export const notificationsService = {
  async getNotifications() {
    const response = await apiClient.get('/api/notifications')
    return response.data
  },

  async markAsRead(id) {
    const response = await apiClient.patch(`/api/notifications/${id}/read`)
    return response.data
  }
}

export const cpeService = {
  async getCPEs() {
    try {
      const response = await apiClient.get('/api/admin/cpes')
      console.log('CPEs response:', response.data)
      return response.data
    } catch (error) {
      console.error('Error fetching CPEs:', error)
      throw error
    }
  },

  async createCPE(data) {
    try {
      const response = await apiClient.post('/api/admin/cpes', data)
      return response.data
    } catch (error) {
      console.error('Error creating CPE:', error)
      throw error
    }
  },

  async updateCPE(id, data) {
    try {
      const response = await apiClient.patch(`/api/admin/cpes/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating CPE:', error)
      throw error
    }
  },

  async deleteCPE(id) {
    try {
      const response = await apiClient.delete(`/api/admin/cpes/${id}`)
      return response.data
    } catch (error) {
      console.error('Error deleting CPE:', error)
      throw error
    }
  }
}

export default apiClient
