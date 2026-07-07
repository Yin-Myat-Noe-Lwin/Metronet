// Get API URL 
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
const API_TIMEOUT = parseInt(import.meta.env.VITE_API_TIMEOUT) || 30000

console.log('API URL:', API_URL)
console.log('Environment:', import.meta.env.MODE)
console.log('Timeout:', API_TIMEOUT)

export const config = {
  apiUrl: API_URL,
  apiTimeout: API_TIMEOUT,
  appName: import.meta.env.VITE_APP_NAME || 'MetroNet ISP',
  appVersion: import.meta.env.VITE_APP_VERSION || '1.0.0',
  isDevelopment: import.meta.env.DEV,
  isProduction: import.meta.env.PROD
}

export const endpoints = {
  auth: {
    login: `${API_URL}/api/login`,
    register: `${API_URL}/api/register`,
    logout: `${API_URL}/api/logout`,
    user: `${API_URL}/api/user`
  },
  plans: {
    list: `${API_URL}/api/plans`,
    detail: (id) => `${API_URL}/api/plans/${id}`,
    subscribe: `${API_URL}/api/subscribe`
  },
  subscriptions: {
    list: `${API_URL}/api/subscriptions`,
    cancel: (id) => `${API_URL}/api/subscriptions/${id}`
  }
}

export default config
