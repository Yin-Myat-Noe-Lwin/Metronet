<template>
  <div class="invoices-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">My Invoices</h1>
          <p class="page-subtitle">View and manage your billing history</p>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="!loading && invoices.length > 0" class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon total">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
              <line x1="8" y1="21" x2="16" y2="21"/>
              <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ invoices.length }}</div>
            <div class="stat-label">Total Invoices</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon pending">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ pendingCount }}</div>
            <div class="stat-label">Pending</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon overdue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ overdueCount }}</div>
            <div class="stat-label">Overdue</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon paid">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <div>
            <div class="stat-value">{{ paidCount }}</div>
            <div class="stat-label">Paid</div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading your invoices...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="invoices.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
        </div>
        <h3>No Invoices Found</h3>
        <p>You don't have any invoices yet.</p>
      </div>

      <!-- Invoices List -->
      <div v-else>
        <!-- Filters -->
        <div class="filters">
          <div class="filter-group">
            <button
              v-for="filter in filters"
              :key="filter.key"
              class="filter-btn"
              :class="{ active: activeFilter === filter.key }"
              @click="activeFilter = filter.key"
            >
              {{ filter.label }}
              <span class="count" v-if="getFilterCount(filter.key) > 0">
                {{ getFilterCount(filter.key) }}
              </span>
            </button>
          </div>
          <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/>
              <path d="m21 21-4.35-4.35"/>
            </svg>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search invoices..."
            >
          </div>
        </div>

        <!-- List -->
        <div class="invoices-list">
          <div
            v-for="invoice in paginatedInvoices"
            :key="invoice.id"
            class="invoice-card"
          >
            <div class="card-top">
              <div class="invoice-info">
                <span class="invoice-number">#{{ invoice.invoice_number || String(invoice.id).padStart(4, '0') }}</span>
                <span class="invoice-date">{{ formatDate(invoice.created_at) }}</span>
              </div>
              <span class="status-badge" :class="getStatusClass(invoice.status)">
                <span class="status-dot"></span>
                {{ getStatusText(invoice.status) }}
              </span>
            </div>

            <div class="card-middle">
              <div class="detail">
                <span class="label">Amount</span>
                <span class="value amount">{{ formatCurrency(invoice.amount) }}</span>
              </div>
              <div class="divider"></div>
              <div class="detail">
                <span class="label">Due Date</span>
                <span class="value" :class="{ 'text-danger': isOverdue(invoice) }">
                  {{ formatDate(invoice.due_date) }}
                  <span v-if="isOverdue(invoice)" class="overdue-tag">Overdue</span>
                </span>
              </div>
              <div class="divider" v-if="invoice.paid_at"></div>
              <div class="detail" v-if="invoice.paid_at">
                <span class="label">Paid On</span>
                <span class="value">{{ formatDate(invoice.paid_at) }}</span>
              </div>
            </div>

            <div class="card-bottom">
              <button
                v-if="invoice.status === 0 || invoice.status === 2"
                @click="openPaymentModal(invoice)"
                class="btn-pay"
                :disabled="processingPayment"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                  <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                Pay Now
              </button>
              <!-- <button @click="downloadInvoice(invoice)" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download PDF
              </button> -->
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredInvoices.length > itemsPerPage" class="pagination">
          <button
            class="page-btn"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Previous
          </button>
          <span class="page-info">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <button
            class="page-btn"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="modal-overlay" @click.self="handleModalClose">
      <div class="modal payment-modal">
        <div class="modal-header">
          <h3>Pay Invoice</h3>
          <button class="modal-close" @click="handleModalClose">×</button>
        </div>

        <div class="modal-body">
          <!-- Invoice Summary -->
          <div class="invoice-summary">
            <div class="summary-item">
              <span class="summary-label">Invoice #</span>
              <span class="summary-value">{{ selectedInvoice?.invoice_number || '#' + String(selectedInvoice?.id).padStart(4, '0') }}</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-item">
              <span class="summary-label">Amount</span>
              <span class="summary-value amount">{{ formatCurrency(selectedInvoice?.amount) }}</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-item">
              <span class="summary-label">Due Date</span>
              <span class="summary-value">{{ formatDate(selectedInvoice?.due_date) }}</span>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="paymentMethodsLoading" class="payment-loading">
            <div class="spinner-small"></div>
            <p>Loading payment methods...</p>
          </div>

          <!-- Payment Methods -->
          <div v-else>
            <p class="section-label">Choose Payment Method</p>

            <div class="payment-methods-list">
              <div
                v-for="method in paymentMethods"
                :key="method.id"
                class="payment-method-item"
                :class="{ active: selectedMethod === method.id }"
                @click="toggleMethod(method.id)"
              >
                <div class="method-left">
                  <span class="method-icon">{{ getMethodIcon(method) }}</span>
                  <div class="method-info">
                    <span class="method-name">{{ method.name }}</span>
                    <span class="method-desc">{{ method.description || '' }}</span>
                  </div>
                </div>
                <div class="method-check">
                  <span v-if="selectedMethod === method.id">✓</span>
                </div>
              </div>
            </div>

            <!-- Dynamic Fields -->
            <div v-if="selectedMethod && methodFields.length > 0" class="dynamic-fields">
              <p class="fields-label">Payment Details</p>
              <div class="fields-grid">
                <div
                  v-for="field in methodFields"
                  :key="field.key"
                  class="field-group"
                >
                  <label>
                    {{ field.label }}
                    <span v-if="field.required" class="required">*</span>
                  </label>
                  <input
                    :type="field.type"
                    v-model="dynamicFields[field.key]"
                    :placeholder="field.placeholder"
                    class="field-input"
                    :required="field.required"
                  >
                </div>
              </div>
            </div>

            <!-- Special Payment Info -->
            <div v-if="selectedMethod" class="payment-info">
              <div v-if="isCashPayment()" class="info-box cash">
                <span class="info-icon">💰</span>
                <div>
                  <p class="info-title">Cash Payment</p>
                  <p class="info-text">Visit our office to pay in cash</p>
                  <div class="office-details">
                    <p>📍 123 Main Street, Yangon</p>
                    <p>🕐 Mon-Fri 9:00 AM - 5:00 PM</p>
                  </div>
                </div>
              </div>
              <div v-if="isMobilePayment()" class="info-box mobile">
                <span class="info-icon">📱</span>
                <div>
                  <p class="info-title">{{ getSelectedMethod()?.name }}</p>
                  <p class="info-text">Pay using your mobile wallet</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-cancel" @click="handleModalClose">Cancel</button>
          <button
            class="btn-pay-modal"
            @click="confirmPayment"
            :disabled="processingPayment || !selectedMethod"
          >
            {{ processingPayment ? 'Processing...' : 'Pay Now' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
      <div class="modal success-modal">
        <div class="modal-header">
          <div class="modal-icon success-icon">✓</div>
          <h3>Payment Successful</h3>
          <button class="modal-close" @click="closeSuccessModal">×</button>
        </div>
        <div class="modal-body">
          <p>Your payment has been processed successfully.</p>
          <p class="text-muted">Invoice has been marked as paid.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" @click="closeSuccessModal">Done</button>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal" class="modal-overlay" @click.self="closeErrorModal">
      <div class="modal error-modal">
        <div class="modal-header">
          <div class="modal-icon error-icon">✕</div>
          <h3>Payment Failed</h3>
          <button class="modal-close" @click="closeErrorModal">×</button>
        </div>
        <div class="modal-body">
          <p>{{ errorMessage }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" @click="closeErrorModal">Try Again</button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast.show" class="toast" :class="toast.type">
      <span class="toast-icon">{{ toast.type === 'success' ? '✓' : '✕' }}</span>
      <span>{{ toast.message }}</span>
      <button @click="toast.show = false">×</button>
    </div>
  </div>
</template>

<script>
import { invoicesService, paymentService } from '../services/api'

export default {
  name: 'InvoicePage',
  data() {
    return {
      // Loading states
      loading: false,
      processingPayment: false,
      paymentMethodsLoading: false,

      // Data
      invoices: [],
      paymentMethods: [],
      dynamicFields: {},

      // Filters
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: '0', label: 'Pending' },
        { key: '1', label: 'Paid' },
        { key: '2', label: 'Overdue' },
        { key: '3', label: 'Cancelled' }
      ],
      searchQuery: '',

      // Pagination
      currentPage: 1,
      itemsPerPage: 10,

      // Modals
      showPaymentModal: false,
      showSuccessModal: false,
      showErrorModal: false,

      // Selected items
      selectedInvoice: null,
      selectedMethod: null,

      // Error handling
      errorMessage: '',

      // Toast
      toast: { show: false, message: '', type: 'success' },
      toastTimeout: null,

      // Payment tracking
      paymentCompleted: false
    }
  },

  computed: {
    pendingCount() {
      return this.invoices.filter(i => i.status === 0).length
    },
    paidCount() {
      return this.invoices.filter(i => i.status === 1).length
    },
    overdueCount() {
      return this.invoices.filter(i => i.status === 2).length
    },

    filteredInvoices() {
      let filtered = this.invoices

      // Apply status filter
      if (this.activeFilter !== 'all') {
        filtered = filtered.filter(inv => String(inv.status) === this.activeFilter)
      }

      // Apply search filter
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        filtered = filtered.filter(inv =>
          (inv.invoice_number || '').toLowerCase().includes(q) ||
          String(inv.id).includes(q) ||
          (inv.amount && String(inv.amount).includes(q))
        )
      }

      return filtered
    },

    paginatedInvoices() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredInvoices.slice(start, end)
    },

    totalPages() {
      return Math.ceil(this.filteredInvoices.length / this.itemsPerPage)
    },

    methodFields() {
      if (!this.selectedMethod) return []

      const method = this.paymentMethods.find(m => m.id === this.selectedMethod)
      if (!method || !method.fields) return []

      try {
        const fields = typeof method.fields === 'string'
          ? JSON.parse(method.fields)
          : method.fields

        if (typeof fields !== 'object') return []

        return Object.entries(fields).map(([key, value]) => ({
          key,
          required: value === 'required',
          label: this.getFieldLabel(key),
          placeholder: this.getFieldPlaceholder(key),
          type: this.getFieldType(key)
        }))
      } catch (e) {
        console.error('Error parsing fields:', e)
        return []
      }
    }
  },

  mounted() {
    this.fetchInvoices()
  },

  methods: {
    // ==================== INVOICE METHODS ====================

    async fetchInvoices() {
      this.loading = true
      try {
        const response = await invoicesService.getInvoices()
        this.invoices = response.data || response || []
        this.currentPage = 1
      } catch (error) {
        this.showToast('Failed to load invoices', 'error')
      } finally {
        this.loading = false
      }
    },

    async refreshInvoices() {
      await this.fetchInvoices()
      this.showToast('Invoices refreshed', 'success')
    },

    getFilterCount(key) {
      if (key === 'all') return this.invoices.length
      return this.invoices.filter(i => String(i.status) === key).length
    },

    getStatusClass(status) {
      const map = { 0: 'pending', 1: 'paid', 2: 'overdue', 3: 'cancelled' }
      return map[status] || ''
    },

    getStatusText(status) {
      const map = { 0: 'Pending', 1: 'Paid', 2: 'Overdue', 3: 'Cancelled' }
      return map[status] || 'Unknown'
    },

    isOverdue(invoice) {
      if (invoice.status === 1 || invoice.status === 3) return false
      return new Date(invoice.due_date) < new Date()
    },

    async downloadInvoice(invoice) {
      try {
        const response = await invoicesService.downloadInvoice(invoice.id)
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `invoice-${invoice.invoice_number || invoice.id}.pdf`
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        this.showToast('Invoice downloaded!', 'success')
      } catch (error) {
        this.showToast('Failed to download invoice', 'error')
      }
    },

    downloadStatement() {
      this.showToast('Downloading statement...', 'success')
      // Implement actual statement download logic
    },

    // ==================== PAYMENT METHODS ====================

    getMethodIcon(method) {
      if (!method) return '💳'

      if (method.icon) {
        if (method.icon_type === 1) {
          const emoji = this.normalizeEmoji(method.icon)
          if (emoji && emoji.length > 0) return emoji
        }
        if (method.icon_type === 2 || method.icon_type === 3) {
          return '🖼️'
        }
        const icon = this.normalizeEmoji(method.icon)
        if (icon) return icon
      }

      return this.getFallbackIcon(method.name)
    },

    normalizeEmoji(text) {
      if (!text) return null

      try {
        let cleaned = text.replace(/&amp;/g, '&')
          .replace(/&lt;/g, '<')
          .replace(/&gt;/g, '>')
          .replace(/&quot;/g, '"')
          .replace(/&#039;/g, "'")

        const emojiRegex = /[\u{1F000}-\u{1FFFF}]|[\u{2600}-\u{27BF}]|[\u{FE00}-\u{FEFF}]|[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F700}-\u{1F77F}]|[\u{1F780}-\u{1F7FF}]|[\u{1F800}-\u{1F8FF}]|[\u{1F900}-\u{1F9FF}]|[\u{1FA00}-\u{1FA6F}]|[\u{1FA70}-\u{1FAFF}]|[\u{2700}-\u{27BF}]/u

        const matches = cleaned.match(emojiRegex)
        if (matches && matches.length > 0) {
          return matches[0]
        }

        if (cleaned.length === 1 || cleaned.length === 2) {
          return cleaned
        }

        return null
      } catch (e) {
        return null
      }
    },

    getFallbackIcon(name) {
      if (!name) return '💳'

      const nameLower = name.toLowerCase()
      const fallbacks = {
        'credit': '💳',
        'debit': '💳',
        'card': '💳',
        'bank': '🏦',
        'transfer': '🏦',
        'cash': '💰',
        'kbz': '📱',
        'wave': '📱',
        'cb pay': '📱',
        'paypal': '💳',
        'stripe': '💳',
        'mobile': '📱',
        'wallet': '📱',
        'pay': '💳'
      }

      for (const [key, icon] of Object.entries(fallbacks)) {
        if (nameLower.includes(key)) return icon
      }

      return '💳'
    },

    getSelectedMethod() {
      return this.paymentMethods.find(m => m.id === this.selectedMethod)
    },

    isCashPayment() {
      const method = this.getSelectedMethod()
      if (!method) return false
      return (method.name || '').toLowerCase().includes('cash')
    },

    isMobilePayment() {
      const method = this.getSelectedMethod()
      if (!method) return false
      const name = (method.name || '').toLowerCase()
      return ['kbz', 'wave', 'cb pay', 'mobile', 'wallet'].some(m => name.includes(m))
    },

    toggleMethod(methodId) {
      if (this.selectedMethod === methodId) {
        this.selectedMethod = null
        this.resetPaymentFields()
      } else {
        this.selectedMethod = methodId
        this.resetPaymentFields()
      }
    },

    // ==================== FIELD HELPERS ====================

    getFieldLabel(key) {
      const labels = {
        card_number: 'Card Number',
        expiry: 'Expiry Date',
        cvv: 'CVV',
        card_holder: 'Card Holder Name',
        bank_name: 'Bank Name',
        account_number: 'Account Number',
        account_holder: 'Account Holder Name',
        phone_number: 'Phone Number',
        reference: 'Reference',
        email: 'Email Address',
        name: 'Full Name',
        wallet_number: 'Wallet Number'
      }
      return labels[key] || key.replace(/_/g, ' ').toUpperCase()
    },

    getFieldPlaceholder(key) {
      const placeholders = {
        card_number: '1234 5678 9012 3456',
        expiry: 'MM/YY',
        cvv: '***',
        card_holder: 'John Doe',
        bank_name: 'Enter bank name',
        account_number: 'Enter account number',
        account_holder: 'Enter account holder name',
        phone_number: '09xxxxxxxxx',
        reference: 'Enter reference',
        email: 'email@example.com',
        name: 'John Doe',
        wallet_number: 'Enter wallet number'
      }
      return placeholders[key] || `Enter ${this.getFieldLabel(key).toLowerCase()}`
    },

    getFieldType(key) {
      const types = {
        expiry: 'text',
        cvv: 'password',
        card_number: 'text',
        phone_number: 'tel',
        email: 'email'
      }
      return types[key] || 'text'
    },

    resetPaymentFields() {
      this.dynamicFields = {}
    },

    // ==================== PAYMENT MODAL ====================

    async openPaymentModal(invoice) {
      if (invoice.status === 1) {
        this.showToast('Invoice is already paid', 'error')
        return
      }
      if (invoice.status === 3) {
        this.showToast('Invoice has been cancelled', 'error')
        return
      }

      this.paymentMethodsLoading = true
      this.paymentMethods = []
      this.paymentCompleted = false

      try {
        const response = await paymentService.getPaymentMethods()
        this.paymentMethods = response?.data || response || []
        this.paymentMethods = this.paymentMethods.filter(
          m => m.is_active !== false && m.is_active !== 0
        )

        if (this.paymentMethods.length === 0) {
          this.showToast('No payment methods available', 'error')
          this.paymentMethodsLoading = false
          return
        }
      } catch (error) {
        console.error('Failed to load payment methods:', error)
        this.showToast('Failed to load payment methods', 'error')
        this.paymentMethodsLoading = false
        return
      } finally {
        this.paymentMethodsLoading = false
      }

      this.selectedInvoice = invoice
      this.selectedMethod = null
      this.resetPaymentFields()
      this.showPaymentModal = true
    },

    closePaymentModal() {
      this.showPaymentModal = false
      this.selectedInvoice = null
      this.selectedMethod = null
      this.resetPaymentFields()
    },

    handleModalClose() {
      const wasPaymentMade = this.paymentCompleted
      this.closePaymentModal()

      if (!wasPaymentMade) {
        this.fetchInvoices()
      }

      this.paymentCompleted = false
    },

    async confirmPayment() {
      if (!this.selectedInvoice || !this.selectedMethod) return

      const method = this.paymentMethods.find(m => m.id === this.selectedMethod)
      if (method && method.fields) {
        try {
          const fields = typeof method.fields === 'string'
            ? JSON.parse(method.fields)
            : method.fields

          for (const [key, value] of Object.entries(fields)) {
            if (value === 'required' && !this.dynamicFields[key]) {
              this.showToast(`Please fill in ${this.getFieldLabel(key)}`, 'error')
              return
            }
          }
        } catch (e) {
          console.error('Error validating fields:', e)
        }
      }

      const paymentDetails = { ...this.dynamicFields }

      this.processingPayment = true
      try {
        await paymentService.payInvoice(this.selectedInvoice.id, {
          payment_method: this.selectedMethod,
          payment_details: paymentDetails
        })

        this.paymentCompleted = true

        const index = this.invoices.findIndex(i => i.id === this.selectedInvoice.id)
        if (index !== -1) {
          this.invoices[index] = {
            ...this.invoices[index],
            status: 1,
            paid_at: new Date().toISOString()
          }
        }

        this.closePaymentModal()
        this.showSuccessModal = true
        this.showToast('Payment successful!', 'success')
        setTimeout(() => this.closeSuccessModal(), 3000)
      } catch (error) {
        this.closePaymentModal()
        const msg = error.response?.data?.message || error.message || 'Payment failed'
        this.showToast(msg, 'error')
        this.showErrorModalWithMessage(msg)
      } finally {
        this.processingPayment = false
        this.resetPaymentFields()
      }
    },

    // ==================== MODAL HELPERS ====================

    closeSuccessModal() {
      this.showSuccessModal = false
    },

    showErrorModalWithMessage(message) {
      this.errorMessage = message
      this.showErrorModal = true
    },

    closeErrorModal() {
      this.showErrorModal = false
      this.errorMessage = ''
    },

    // ==================== TOAST ====================

    showToast(message, type = 'success') {
      if (this.toastTimeout) clearTimeout(this.toastTimeout)
      this.toast.message = message
      this.toast.type = type
      this.toast.show = true
      this.toastTimeout = setTimeout(() => {
        this.toast.show = false
      }, 4000)
    },

    // ==================== FORMATTERS ====================

    formatCurrency(amount) {
      if (!amount) return 'Free'
      return 'MMK ' + parseFloat(amount).toLocaleString()
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.invoices-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 40px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ==================== HEADER ==================== */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 4px;
}

.page-subtitle {
  color: #8892a8;
  font-size: 15px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.btn-refresh,
.btn-statement {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #fff;
  border: 1px solid #e8ecf1;
  border-radius: 10px;
  font-size: 14px;
  color: #8892a8;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-refresh:hover:not(:disabled),
.btn-statement:hover:not(:disabled) {
  border-color: #ff6b35;
  color: #ff6b35;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ==================== STATS ==================== */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  background: #fff;
  padding: 16px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon.total { background: #eef2ff; color: #6366f1; }
.stat-icon.pending { background: #fffbeb; color: #f59e0b; }
.stat-icon.overdue { background: #fef2f2; color: #ef4444; }
.stat-icon.paid { background: #ecfdf5; color: #22c55e; }

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
}

/* ==================== LOADING ==================== */

.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #ff6b35;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #94a3b8;
}

/* ==================== EMPTY ==================== */

.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 16px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 20px;
  color: #0f172a;
  margin: 0 0 8px;
}

.empty-state p {
  color: #94a3b8;
  margin: 0;
}

/* ==================== FILTERS ==================== */

.filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.filter-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 8px 20px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

.filter-btn.active {
  background: #ff6b35;
  border-color: #ff6b35;
  color: #fff;
}

.filter-btn .count {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  border-radius: 50px;
  padding: 0 8px;
  font-size: 11px;
  margin-left: 4px;
  color: #fff;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 280px;
}

.search-box input {
  width: 100%;
  padding: 8px 16px 8px 36px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.3s;
}

.search-box input:focus {
  outline: none;
  border-color: #ff6b35;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

/* ==================== INVOICES LIST ==================== */

.invoices-list {
  display: grid;
  gap: 16px;
}

.invoice-card {
  background: #fff;
  border-radius: 10px;
  padding: 20px 24px;
  border: 1px solid #e2e8f0;
  transition: all 0.3s;
}

.invoice-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}

.invoice-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.invoice-number {
  font-weight: 600;
  color: #0f172a;
  font-size: 16px;
}

.invoice-date {
  color: #94a3b8;
  font-size: 14px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-badge.pending { background: #fffbeb; color: #d97706; }
.status-badge.pending .status-dot { background: #d97706; }

.status-badge.paid { background: #ecfdf5; color: #059669; }
.status-badge.paid .status-dot { background: #059669; }

.status-badge.overdue { background: #fef2f2; color: #dc2626; }
.status-badge.overdue .status-dot { background: #dc2626; }

.status-badge.cancelled { background: #f1f3f5; color: #94a3b8; }
.status-badge.cancelled .status-dot { background: #94a3b8; }

.card-middle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  padding: 16px 0;
  border-top: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
}

.detail {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.detail .label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail .value {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
  margin-top: 2px;
}

.detail .value.amount {
  font-weight: 700;
}

.detail .value.text-danger {
  color: #dc2626;
}

.divider {
  width: 1px;
  height: 30px;
  background: #e2e8f0;
}

.overdue-tag {
  font-size: 10px;
  background: #fef2f2;
  color: #dc2626;
  padding: 1px 8px;
  border-radius: 4px;
  font-weight: 600;
  text-transform: uppercase;
  margin-left: 8px;
}

.card-bottom {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.btn-pay,
.btn-download {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-pay {
  background: #ff6b35;
  color: #fff;
}

.btn-pay:hover:not(:disabled) {
  background: #e85a2a;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-download {
  background: #f1f5f9;
  color: #475569;
}

.btn-download:hover {
  background: #e2e8f0;
}

/* ==================== PAGINATION ==================== */

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.page-btn {
  padding: 8px 20px;
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s;
}

.page-btn:hover:not(:disabled) {
  border-color: #ff6b35;
  color: #ff6b35;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  color: #94a3b8;
  font-size: 14px;
}

/* ==================== MODALS ==================== */

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
  animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 16px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 24px 64px rgba(0,0,0,0.2);
  animation: slideUp 0.3s ease;
  position: relative;
  z-index: 10001;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.payment-modal {
  max-width: 480px;
  width: 100%;
}

.success-modal,
.error-modal {
  max-width: 380px;
  width: 100%;
}

.modal-header {
  padding: 18px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 10;
  border-radius: 16px 16px 0 0;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.modal-close:hover {
  background: #f1f5f9;
}

.modal-body {
  padding: 20px 24px;
}

.modal-body .text-muted {
  color: #94a3b8;
  font-size: 14px;
}

.modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
}

.success-icon {
  background: #ecfdf5;
  color: #059669;
}

.error-icon {
  background: #fef2f2;
  color: #dc2626;
}

/* ==================== PAYMENT MODAL CONTENT ==================== */

.invoice-summary {
  display: flex;
  justify-content: space-around;
  align-items: center;
  background: #f8fafc;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 20px;
  border: 1px solid #eef2f6;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.summary-item .summary-label {
  font-size: 10px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  font-weight: 600;
}

.summary-item .summary-value {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin-top: 2px;
}

.summary-item .summary-value.amount {
  color: #ff6b35;
  font-weight: 700;
}

.summary-divider {
  width: 1px;
  height: 30px;
  background: #e2e8f0;
}

.section-label {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 12px;
}

.payment-methods-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.payment-method-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.payment-method-item:hover {
  border-color: #cbd5e1;
  background: #fafbfc;
}

.payment-method-item.active {
  border-color: #ff6b35;
  background: #fff8f5;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08);
}

.method-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.method-icon {
  font-size: 22px;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 8px;
  flex-shrink: 0;
}

.method-info {
  display: flex;
  flex-direction: column;
}

.method-name {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}

.method-desc {
  font-size: 12px;
  color: #94a3b8;
}

.method-check {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #e2e8f0;
  color: transparent;
  transition: all 0.2s ease;
  flex-shrink: 0;
  font-size: 14px;
  font-weight: 700;
}

.payment-method-item.active .method-check {
  background: #ff6b35;
  border-color: #ff6b35;
  color: #fff;
}

.dynamic-fields {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.fields-label {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 10px;
}

.fields-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.field-group label {
  font-size: 12px;
  font-weight: 500;
  color: #475569;
}

.field-group .required {
  color: #ef4444;
  margin-left: 2px;
}

.field-input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  transition: border-color 0.3s;
  background: #fff;
  width: 100%;
}

.field-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08);
}

.payment-info {
  margin-top: 12px;
}

.info-box {
  display: flex;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 10px;
  align-items: flex-start;
}

.info-box.cash {
  background: #fffbeb;
  border: 1px solid #fef3c7;
}

.info-box.mobile {
  background: #f5f3ff;
  border: 1px solid #ede9fe;
}

.info-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.info-title {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 2px;
}

.info-text {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.office-details {
  margin-top: 6px;
  padding: 8px 12px;
  background: #fff;
  border-radius: 6px;
}

.office-details p {
  font-size: 12px;
  color: #0f172a;
  margin: 2px 0;
}

.payment-loading {
  text-align: center;
  padding: 24px;
}

.spinner-small {
  width: 32px;
  height: 32px;
  margin: 0 auto 8px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #ff6b35;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.payment-loading p {
  color: #94a3b8;
  font-size: 14px;
  margin: 0;
}

/* ==================== MODAL FOOTER ==================== */

.modal-footer {
  padding: 16px 24px 20px;
  display: flex;
  gap: 12px;
  border-top: 1px solid #f1f5f9;
  position: sticky;
  bottom: 0;
  background: #fff;
  border-radius: 0 0 16px 16px;
}

.modal-footer button {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
}

.modal-footer button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel {
  background: #f1f5f9;
  color: #64748b;
  flex: 0.5;
}

.btn-cancel:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-pay-modal {
  background: #ff6b35;
  color: #fff;
  flex: 1;
}

.btn-pay-modal:hover:not(:disabled) {
  background: #e85a2a;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.btn-primary {
  background: #ff6b35;
  color: #fff;
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  flex: 1;
}

.btn-primary:hover {
  background: #e85a2a;
}

/* ==================== TOAST ==================== */

.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 14px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  z-index: 99999;
  min-width: 280px;
  max-width: 420px;
  animation: slideUp 0.3s ease;
}

.toast.success {
  background: #0f172a;
  color: #fff;
}

.toast.error {
  background: #dc2626;
  color: #fff;
}

.toast-icon {
  font-weight: 700;
  font-size: 16px;
}

.toast button {
  background: none;
  border: none;
  color: rgba(255,255,255,0.6);
  font-size: 20px;
  cursor: pointer;
  margin-left: auto;
  padding: 0 4px;
}

.toast button:hover {
  color: #fff;
}

/* ==================== SCROLLBAR ==================== */

.modal::-webkit-scrollbar {
  width: 4px;
}

.modal::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.modal::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

/* ==================== RESPONSIVE ==================== */

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
  }

  .btn-refresh,
  .btn-statement {
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    max-width: none;
  }

  .card-middle {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .divider {
    display: none;
  }

  .card-bottom {
    flex-direction: column;
  }

  .btn-pay,
  .btn-download {
    justify-content: center;
  }

  .payment-modal {
    max-width: 100%;
    margin: 16px;
  }

  .invoice-summary {
    flex-direction: column;
    gap: 6px;
  }

  .summary-divider {
    display: none;
  }

  .summary-item {
    flex-direction: row;
    justify-content: space-between;
    width: 100%;
    padding: 2px 0;
  }

  .fields-grid {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn-cancel {
    flex: 1;
  }

  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
    min-width: auto;
    max-width: none;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-top {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .modal-body {
    padding: 16px;
  }

  .modal-header {
    padding: 14px 16px;
  }

  .modal-footer {
    padding: 12px 16px 16px;
  }
}
</style>
