<template>
  <div class="invoices-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">My Invoices</h1>
        <p class="page-subtitle">View and manage your billing history</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <span class="summary-label">Total Invoices</span>
          <span class="summary-value">{{ invoices.length }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Pending</span>
          <span class="summary-value">{{ pendingCount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Paid</span>
          <span class="summary-value">{{ paidCount }}</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Total Amount</span>
          <span class="summary-value">{{ totalAmount }}</span>
        </div>
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
        <div class="search-group">
          <input
            type="text"
            v-model="searchQuery"
            class="search-input"
            placeholder="Search invoices..."
          >
        </div>
      </div>

      <!-- Invoices List -->
      <div v-if="filteredInvoices.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
        </div>
        <h3>No Invoices Found</h3>
        <p>{{ searchQuery ? 'No invoices match your search criteria.' : 'You don\'t have any invoices yet.' }}</p>
      </div>

      <div v-else class="invoices-list">
        <div
          v-for="invoice in filteredInvoices"
          :key="invoice.id"
          class="invoice-card"
        >
          <div class="invoice-header">
            <div class="invoice-info">
              <span class="invoice-number">Invoice #{{ String(invoice.id).padStart(4, '0') }}</span>
              <span class="invoice-date">{{ formatDate(invoice.created_at) }}</span>
            </div>
            <span class="invoice-status" :class="invoice.status">
              {{ invoice.status }}
            </span>
          </div>

          <div class="invoice-body">
            <div class="invoice-details">
              <div class="detail-item">
                <span class="detail-label">Amount</span>
                <span class="detail-value">{{ invoice.amount }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Due Date</span>
                <span class="detail-value">{{ formatDate(invoice.due_date) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Description</span>
                <span class="detail-value">{{ invoice.description || 'Internet Service' }}</span>
              </div>
            </div>

            <div class="invoice-actions">
              <button
                v-if="invoice.status === 'pending' || invoice.status === 'overdue'"
                @click="payInvoice(invoice.id)"
                class="action-btn pay-btn"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
                Pay Now
              </button>
              <button @click="downloadInvoice(invoice.id)" class="action-btn download-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
  </div>
</template>

<script>
export default {
  name: 'InvoicesPage',
  data() {
    return {
      activeFilter: 'all',
      filters: [
        { key: 'all', label: 'All' },
        { key: 'paid', label: 'Paid' },
        { key: 'pending', label: 'Pending' },
        { key: 'overdue', label: 'Overdue' }
      ],
      searchQuery: '',
      invoices: [
        {
          id: 1,
          amount: '85,000 MMK',
          status: 'paid',
          created_at: '2025-01-15',
          due_date: '2025-01-30',
          description: 'Internet Service - January 2025'
        },
        {
          id: 2,
          amount: '120,000 MMK',
          status: 'pending',
          created_at: '2025-01-20',
          due_date: '2025-02-10',
          description: 'Internet Service - February 2025'
        },
        {
          id: 3,
          amount: '65,000 MMK',
          status: 'overdue',
          created_at: '2024-12-20',
          due_date: '2025-01-10',
          description: 'Internet Service - December 2024'
        },
        {
          id: 4,
          amount: '85,000 MMK',
          status: 'paid',
          created_at: '2024-12-15',
          due_date: '2024-12-30',
          description: 'Internet Service - December 2024'
        },
        {
          id: 5,
          amount: '45,000 MMK',
          status: 'paid',
          created_at: '2024-11-15',
          due_date: '2024-11-30',
          description: 'Internet Service - November 2024'
        }
      ]
    }
  },
  computed: {
    pendingCount() {
      return this.invoices.filter(i => i.status === 'pending' || i.status === 'overdue').length
    },
    paidCount() {
      return this.invoices.filter(i => i.status === 'paid').length
    },
    totalAmount() {
      const total = this.invoices.reduce((sum, inv) => {
        const amount = parseInt(inv.amount.replace(/[^0-9]/g, ''))
        return sum + amount
      }, 0)
      return total.toLocaleString() + ' MMK'
    },
    filteredInvoices() {
      let filtered = this.invoices

      // Filter by status
      if (this.activeFilter !== 'all') {
        filtered = filtered.filter(inv => inv.status === this.activeFilter)
      }

      // Filter by search
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(inv =>
          inv.description.toLowerCase().includes(query) ||
          String(inv.id).includes(query)
        )
      }

      return filtered
    }
  },
  methods: {
    payInvoice(id) {
      if (!confirm('Are you sure you want to pay this invoice?')) return

      const invoice = this.invoices.find(i => i.id === id)
      if (invoice) {
        invoice.status = 'paid'
        alert('Payment successful! Invoice has been marked as paid.')
      }
    },

    downloadInvoice(id) {
      alert(`Downloading invoice #${String(id).padStart(4, '0')}...`)
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.invoices-page {
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
  margin-bottom: 32px;
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
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
}

/* Filters */
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
  border: 2px solid #e0e0e0;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

.filter-btn--active {
  background: #ff6b35;
  border-color: #ff6b35;
  color: #fff;
}

.search-group {
  flex: 1;
  max-width: 300px;
}

.search-input {
  width: 100%;
  padding: 8px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #ff6b35;
}

/* Invoices List */
.invoices-list {
  display: grid;
  gap: 16px;
}

.invoice-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: box-shadow 0.3s;
}

.invoice-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  border-bottom: 1px solid #e8e8e8;
  margin-bottom: 12px;
}

.invoice-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.invoice-number {
  font-weight: 600;
  color: #1a1a2e;
  font-size: 16px;
}

.invoice-date {
  color: #888;
  font-size: 14px;
}

.invoice-status {
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.invoice-status.paid {
  background: #e8f5e9;
  color: #2e7d32;
}

.invoice-status.pending {
  background: #fff3e0;
  color: #e65100;
}

.invoice-status.overdue {
  background: #ffebee;
  color: #c62828;
}

.invoice-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.invoice-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 16px;
  flex: 1;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 12px;
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

.invoice-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.pay-btn {
  background: #4caf50;
  color: #fff;
}

.pay-btn:hover {
  background: #388e3c;
}

.download-btn {
  background: #e3f2fd;
  color: #1976d2;
}

.download-btn:hover {
  background: #bbdefb;
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

  .search-group {
    max-width: none;
  }

  .invoice-body {
    flex-direction: column;
    align-items: stretch;
  }

  .invoice-actions {
    justify-content: flex-end;
  }

  .invoice-details {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 480px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .invoice-header {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }

  .invoice-info {
    flex-wrap: wrap;
  }

  .invoice-details {
    grid-template-columns: 1fr;
  }

  .invoice-actions {
    flex-direction: column;
  }

  .action-btn {
    justify-content: center;
  }
}
</style>
