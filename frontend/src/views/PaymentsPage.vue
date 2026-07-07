<template>
  <div class="payments-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Payment History</h1>
        <p class="page-subtitle">View all your transactions and payment history</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <span class="summary-label">Total Payments</span>
          <span class="summary-value">{{ payments.length }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Total Amount</span>
          <span class="summary-value">{{ totalAmount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Last Payment</span>
          <span class="summary-value">{{ lastPaymentDate }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Payment Methods</span>
          <span class="summary-value">{{ paymentMethods.length }}</span>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button @click="showPaymentModal = true" class="action-btn primary-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Make Payment
        </button>
        <button @click="showAddMethodModal = true" class="action-btn secondary-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
          </svg>
          Add Payment Method
        </button>
        <button @click="downloadStatement" class="action-btn secondary-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Download Statement
        </button>
      </div>

      <!-- Filters -->
      <div class="filters">
        <div class="filter-group">
          <button
            v-for="filter in filters"
            :key="filter.key"
            class="filter-btn"
            :class="{ 'filter-btn--active': activeFilter === filter.key }"
            @click="activeFilter = filter.key"
          >
            {{ filter.label }}
          </button>
        </div>
        <div class="date-range">
          <label>From</label>
          <input type="date" v-model="dateFrom" class="date-input">
          <label>To</label>
          <input type="date" v-model="dateTo" class="date-input">
        </div>
      </div>

      <!-- Payments List -->
      <div v-if="filteredPayments.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
        </div>
        <h3>No Payments Found</h3>
        <p>You haven't made any payments yet. Start by making your first payment.</p>
        <button @click="showPaymentModal = true" class="browse-btn">Make Payment</button>
      </div>

      <div v-else class="payments-list">
        <div
          v-for="payment in filteredPayments"
          :key="payment.id"
          class="payment-card"
        >
          <div class="payment-header">
            <div class="payment-info">
              <span class="payment-reference">#{{ payment.reference || String(payment.id).padStart(6, '0') }}</span>
              <span class="payment-date">{{ formatDate(payment.created_at) }}</span>
            </div>
            <span class="payment-status" :class="payment.status">
              {{ payment.status }}
            </span>
          </div>

          <div class="payment-body">
            <div class="payment-details">
              <div class="detail-item">
                <span class="detail-label">Amount</span>
                <span class="detail-value amount">{{ payment.amount }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Method</span>
                <span class="detail-value">{{ payment.method }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Invoice</span>
                <span class="detail-value">#{{ String(payment.invoice_id || payment.id).padStart(4, '0') }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">{{ payment.transaction_id || 'N/A' }}</span>
              </div>
            </div>

            <div class="payment-actions">
              <button @click="viewReceipt(payment.id)" class="action-btn receipt-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                  <polyline points="10 9 9 9 8 9"/>
                </svg>
                Receipt
              </button>
              <button v-if="payment.status === 'pending' || payment.status === 'failed'" @click="retryPayment(payment.id)" class="action-btn retry-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="23 4 23 10 17 10"/>
                  <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Retry
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="filteredPayments.length > 0">
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">
          Previous
        </button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">
          Next
        </button>
      </div>
    </div>

    <!-- Make Payment Modal -->
    <div class="modal-overlay" v-if="showPaymentModal" @click.self="showPaymentModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Make Payment</h2>
          <button class="modal-close" @click="showPaymentModal = false">✕</button>
        </div>
        <form @submit.prevent="processPayment" class="payment-form">
          <div class="form-group">
            <label for="paymentInvoice">Select Invoice <span class="required">*</span></label>
            <select id="paymentInvoice" v-model="paymentForm.invoice_id" required class="form-input">
              <option value="">Select an invoice</option>
              <option v-for="invoice in pendingInvoices" :key="invoice.id" :value="invoice.id">
                #{{ String(invoice.id).padStart(4, '0') }} - {{ invoice.amount }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="paymentMethod">Payment Method <span class="required">*</span></label>
            <select id="paymentMethod" v-model="paymentForm.method" required class="form-input">
              <option value="">Select payment method</option>
              <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                {{ method.name }} - {{ method.type }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="paymentAmount">Amount</label>
            <input type="text" id="paymentAmount" v-model="paymentForm.amount" disabled class="form-input">
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="paymentForm.save_method">
              Save this payment method for future
            </label>
          </div>

          <div class="form-actions">
            <button type="submit" class="submit-btn" :disabled="isProcessing">
              {{ isProcessing ? 'Processing...' : 'Pay Now' }}
            </button>
            <button type="button" class="cancel-btn" @click="showPaymentModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Payment Method Modal -->
    <div class="modal-overlay" v-if="showAddMethodModal" @click.self="showAddMethodModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Add Payment Method</h2>
          <button class="modal-close" @click="showAddMethodModal = false">✕</button>
        </div>
        <form @submit.prevent="addPaymentMethod" class="payment-form">
          <div class="form-group">
            <label for="methodName">Method Name <span class="required">*</span></label>
            <input type="text" id="methodName" v-model="newMethod.name" required class="form-input" placeholder="e.g., My Visa Card">
          </div>

          <div class="form-group">
            <label for="methodType">Type <span class="required">*</span></label>
            <select id="methodType" v-model="newMethod.type" required class="form-input">
              <option value="">Select type</option>
              <option value="credit_card">Credit Card</option>
              <option value="debit_card">Debit Card</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="digital_wallet">Digital Wallet</option>
            </select>
          </div>

          <div class="form-group">
            <label for="cardNumber">Card/Account Number <span class="required">*</span></label>
            <input type="text" id="cardNumber" v-model="newMethod.card_number" required class="form-input" placeholder="XXXX-XXXX-XXXX-XXXX">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="expiryDate">Expiry Date</label>
              <input type="month" id="expiryDate" v-model="newMethod.expiry" class="form-input">
            </div>
            <div class="form-group">
              <label for="cvv">CVV</label>
              <input type="password" id="cvv" v-model="newMethod.cvv" class="form-input" placeholder="***" maxlength="4">
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="newMethod.set_default">
              Set as default payment method
            </label>
          </div>

          <div class="form-actions">
            <button type="submit" class="submit-btn" :disabled="isAddingMethod">
              {{ isAddingMethod ? 'Adding...' : 'Add Method' }}
            </button>
            <button type="button" class="cancel-btn" @click="showAddMethodModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal-overlay" v-if="showReceiptModal" @click.self="showReceiptModal = false">
      <div class="modal receipt-modal">
        <div class="modal-header">
          <h2>Payment Receipt</h2>
          <button class="modal-close" @click="showReceiptModal = false">✕</button>
        </div>
        <div class="receipt-content" v-if="selectedPayment">
          <div class="receipt-header">
            <div class="receipt-logo">
              <span class="logo-icon">⚡</span>
              <span class="logo-text">MetroNet</span>
            </div>
            <p class="receipt-sub">Official Payment Receipt</p>
          </div>

          <div class="receipt-details">
            <div class="receipt-row">
              <span class="receipt-label">Receipt #</span>
              <span class="receipt-value">#{{ selectedPayment.reference || String(selectedPayment.id).padStart(6, '0') }}</span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Date</span>
              <span class="receipt-value">{{ formatDate(selectedPayment.created_at) }}</span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Amount</span>
              <span class="receipt-value amount">{{ selectedPayment.amount }}</span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Method</span>
              <span class="receipt-value">{{ selectedPayment.method }}</span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Status</span>
              <span class="receipt-value status-badge" :class="selectedPayment.status">
                {{ selectedPayment.status }}
              </span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Transaction ID</span>
              <span class="receipt-value">{{ selectedPayment.transaction_id || 'N/A' }}</span>
            </div>
          </div>

          <div class="receipt-footer">
            <button @click="downloadReceipt(selectedPayment.id)" class="download-receipt-btn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              Download PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaymentsPage',
  data() {
    return {
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'completed', label: 'Completed' },
        { key: 'pending', label: 'Pending' },
        { key: 'failed', label: 'Failed' }
      ],
      dateFrom: '',
      dateTo: '',
      currentPage: 1,
      itemsPerPage: 5,
      showPaymentModal: false,
      showAddMethodModal: false,
      showReceiptModal: false,
      isProcessing: false,
      isAddingMethod: false,
      selectedPayment: null,
      payments: [
        {
          id: 1,
          reference: 'PAY-2025-001',
          amount: '85,000 MMK',
          status: 'completed',
          method: 'Credit Card',
          invoice_id: 1,
          transaction_id: 'TXN-123456789',
          created_at: '2025-01-30T10:30:00'
        },
        {
          id: 2,
          reference: 'PAY-2025-002',
          amount: '120,000 MMK',
          status: 'pending',
          method: 'Bank Transfer',
          invoice_id: 2,
          transaction_id: 'TXN-987654321',
          created_at: '2025-01-25T14:15:00'
        },
        {
          id: 3,
          reference: 'PAY-2025-003',
          amount: '65,000 MMK',
          status: 'completed',
          method: 'Digital Wallet',
          invoice_id: 3,
          transaction_id: 'TXN-456789123',
          created_at: '2025-01-15T09:45:00'
        },
        {
          id: 4,
          reference: 'PAY-2024-004',
          amount: '85,000 MMK',
          status: 'completed',
          method: 'Credit Card',
          invoice_id: 4,
          transaction_id: 'TXN-789123456',
          created_at: '2024-12-30T11:20:00'
        },
        {
          id: 5,
          reference: 'PAY-2024-005',
          amount: '45,000 MMK',
          status: 'failed',
          method: 'Debit Card',
          invoice_id: 5,
          transaction_id: 'TXN-321654987',
          created_at: '2024-12-20T16:00:00'
        }
      ],
      paymentMethods: [
        { id: 1, name: 'Visa Card', type: 'credit_card', last4: '4567' },
        { id: 2, name: 'KBZ Bank', type: 'bank_transfer', last4: '7890' },
        { id: 3, name: 'Wave Money', type: 'digital_wallet', last4: '1234' }
      ],
      paymentForm: {
        invoice_id: '',
        method: '',
        amount: '',
        save_method: false
      },
      newMethod: {
        name: '',
        type: '',
        card_number: '',
        expiry: '',
        cvv: '',
        set_default: false
      },
      pendingInvoices: [
        { id: 2, amount: '120,000 MMK' },
        { id: 6, amount: '85,000 MMK' }
      ]
    }
  },
  computed: {
    totalAmount() {
      const total = this.payments.reduce((sum, p) => {
        const amount = parseInt(p.amount.replace(/[^0-9]/g, ''))
        return sum + amount
      }, 0)
      return total.toLocaleString() + ' MMK'
    },
    lastPaymentDate() {
      if (this.payments.length === 0) return 'N/A'
      const last = this.payments[0]
      return this.formatDate(last.created_at)
    },
    filteredPayments() {
      let filtered = this.payments

      if (this.activeFilter !== 'all') {
        filtered = filtered.filter(p => p.status === this.activeFilter)
      }

      if (this.dateFrom) {
        filtered = filtered.filter(p => p.created_at >= this.dateFrom)
      }

      if (this.dateTo) {
        filtered = filtered.filter(p => p.created_at <= this.dateTo)
      }

      // Pagination
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return filtered.slice(start, end)
    },
    totalPages() {
      const total = this.payments.length
      return Math.ceil(total / this.itemsPerPage)
    }
  },
  methods: {
    processPayment() {
      this.isProcessing = true

      setTimeout(() => {
        this.isProcessing = false
        this.showPaymentModal = false
        alert('Payment processed successfully!')

        // Add new payment to list
        this.payments.unshift({
          id: this.payments.length + 1,
          reference: 'PAY-2025-' + String(this.payments.length + 1).padStart(3, '0'),
          amount: this.paymentForm.amount || '85,000 MMK',
          status: 'completed',
          method: this.paymentMethods.find(m => m.id === parseInt(this.paymentForm.method))?.name || 'Unknown',
          invoice_id: parseInt(this.paymentForm.invoice_id),
          transaction_id: 'TXN-' + Math.random().toString(36).substring(2, 11).toUpperCase(),
          created_at: new Date().toISOString()
        })

        this.paymentForm = {
          invoice_id: '',
          method: '',
          amount: '',
          save_method: false
        }
      }, 2000)
    },

    addPaymentMethod() {
      this.isAddingMethod = true

      setTimeout(() => {
        this.isAddingMethod = false
        this.showAddMethodModal = false
        alert('Payment method added successfully!')

        this.paymentMethods.push({
          id: this.paymentMethods.length + 1,
          name: this.newMethod.name,
          type: this.newMethod.type,
          last4: this.newMethod.card_number.slice(-4)
        })

        this.newMethod = {
          name: '',
          type: '',
          card_number: '',
          expiry: '',
          cvv: '',
          set_default: false
        }
      }, 1500)
    },

    viewReceipt(id) {
      const payment = this.payments.find(p => p.id === id)
      if (payment) {
        this.selectedPayment = payment
        this.showReceiptModal = true
      }
    },

    retryPayment(id) {
      if (!confirm('Would you like to retry this payment?')) return

      const payment = this.payments.find(p => p.id === id)
      if (payment) {
        payment.status = 'pending'
        alert('Payment retry initiated. Please check back later.')
      }
    },

    downloadStatement() {
      alert('Downloading payment statement...')
    },

    downloadReceipt(id) {
      alert(`Downloading receipt for payment #${id}...`)
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.payments-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 40px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.page-subtitle {
  color: #666;
  font-size: 16px;
}

/* Summary Cards */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.summary-label {
  display: block;
  font-size: 13px;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a2e;
}

/* Quick Actions */
.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.primary-btn {
  background: #ff6b35;
  color: #fff;
}

.primary-btn:hover {
  background: #e85a2a;
}

.secondary-btn {
  background: #fff;
  color: #555;
  border: 2px solid #e0e0e0;
}

.secondary-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

/* Filters */
.filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
  background: #fff;
  padding: 16px 20px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 6px 16px;
  background: none;
  border: 2px solid transparent;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-btn:hover {
  color: #ff6b35;
}

.filter-btn--active {
  background: #ff6b35;
  color: #fff;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #555;
}

.date-input {
  padding: 6px 12px;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 13px;
  transition: border-color 0.3s;
}

.date-input:focus {
  outline: none;
  border-color: #ff6b35;
}

/* Payments List */
.payments-list {
  display: grid;
  gap: 16px;
}

.payment-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: box-shadow 0.3s;
}

.payment-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.payment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  border-bottom: 1px solid #e8e8e8;
  margin-bottom: 12px;
}

.payment-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.payment-reference {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 15px;
}

.payment-date {
  color: #888;
  font-size: 13px;
}

.payment-status {
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.payment-status.completed {
  background: #e8f5e9;
  color: #2e7d32;
}

.payment-status.pending {
  background: #fff3e0;
  color: #e65100;
}

.payment-status.failed {
  background: #ffebee;
  color: #c62828;
}

.payment-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.payment-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  flex: 1;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 11px;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a2e;
  margin-top: 2px;
}

.detail-value.amount {
  color: #ff6b35;
  font-size: 16px;
}

.payment-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.receipt-btn {
  padding: 6px 16px;
  background: #e3f2fd;
  color: #1976d2;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.3s;
}

.receipt-btn:hover {
  background: #bbdefb;
}

.retry-btn {
  padding: 6px 16px;
  background: #ffebee;
  color: #c62828;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.3s;
}

.retry-btn:hover {
  background: #ffcdd2;
}

/* Pagination */
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
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #555;
  cursor: pointer;
  transition: all 0.3s;
}

.page-btn:hover:not(:disabled) {
  border-color: #ff6b35;
  color: #ff6b35;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #666;
  font-size: 14px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 16px;
  padding: 32px;
  max-width: 560px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.receipt-modal {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.modal-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #888;
  cursor: pointer;
  padding: 4px 8px;
  transition: color 0.3s;
}

.modal-close:hover {
  color: #1a1a2e;
}

/* Payment Form */
.payment-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.required {
  color: #e74c3c;
}

.form-input {
  padding: 10px 14px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 15px;
  font-family: inherit;
  transition: border-color 0.3s;
}

.form-input:focus {
  outline: none;
  border-color: #ff6b35;
}

.form-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 400;
  color: #555;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #ff6b35;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

.submit-btn {
  padding: 12px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  flex: 1;
}

.submit-btn:hover:not(:disabled) {
  background: #e85a2a;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-btn {
  padding: 12px 32px;
  background: #e0e0e0;
  color: #666;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.cancel-btn:hover {
  background: #d0d0d0;
}

/* Receipt */
.receipt-content {
  padding: 8px 0;
}

.receipt-header {
  text-align: center;
  padding-bottom: 16px;
  border-bottom: 2px dashed #e0e0e0;
  margin-bottom: 16px;
}

.receipt-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
}

.receipt-logo .logo-icon {
  color: #ff6b35;
  margin-right: 8px;
}

.receipt-logo .logo-text {
  background: linear-gradient(135deg, #ff6b35, #f7931e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.receipt-sub {
  color: #888;
  font-size: 14px;
}

.receipt-details {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.receipt-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid #f5f5f5;
}

.receipt-label {
  color: #888;
  font-size: 14px;
}

.receipt-value {
  font-weight: 500;
  color: #1a1a2e;
}

.receipt-value.amount {
  font-size: 18px;
  color: #ff6b35;
}

.status-badge {
  padding: 2px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.completed {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge.failed {
  background: #ffebee;
  color: #c62828;
}

.receipt-footer {
  text-align: center;
  padding-top: 16px;
  border-top: 2px dashed #e0e0e0;
}

.download-receipt-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.download-receipt-btn:hover {
  background: #e85a2a;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 12px;
}

.empty-icon {
  color: #ccc;
  margin-bottom: 16px;
}

.empty-state h3 {
  color: #1a1a2e;
  margin-bottom: 8px;
}

.empty-state p {
  color: #888;
  margin-bottom: 20px;
}

.browse-btn {
  padding: 12px 32px;
  background: #ff6b35;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.browse-btn:hover {
  background: #e85a2a;
}

/* Responsive */
@media (max-width: 768px) {
  .page-title {
    font-size: 26px;
  }

  .summary-grid {
    grid-template-columns: 1fr 1fr;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .date-range {
    flex-wrap: wrap;
  }

  .date-input {
    flex: 1;
    min-width: 120px;
  }

  .payment-body {
    flex-direction: column;
    align-items: stretch;
  }

  .payment-details {
    grid-template-columns: 1fr 1fr;
  }

  .payment-actions {
    justify-content: flex-end;
  }

  .quick-actions {
    flex-direction: column;
  }

  .action-btn {
    justify-content: center;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .modal {
    padding: 24px;
  }
}

@media (max-width: 480px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .payment-header {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }

  .payment-info {
    flex-wrap: wrap;
  }

  .payment-details {
    grid-template-columns: 1fr;
  }

  .payment-actions {
    flex-direction: column;
  }

  .form-actions {
    flex-direction: column;
  }

  .receipt-row {
    flex-direction: column;
    gap: 4px;
  }
}
</style>
