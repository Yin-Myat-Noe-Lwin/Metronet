<template>
  <div class="admin-customer-detail">
    <div class="back-link">
      <router-link to="/admin/customers">← Back to Customers</router-link>
    </div>
    <h2>Customer Details</h2>
    <div class="customer-info">
      <p><strong>Customer ID:</strong> #{{ customerId }}</p>
      <p><strong>Name:</strong> John Doe</p>
      <p><strong>Email:</strong> john@example.com</p>
      <p><strong>Phone:</strong> +95 9 123 4567</p>
      <p><strong>Status:</strong> <span class="status-badge active">Active</span></p>
    </div>
  </div>
</template>
<script>
export default {
  name: 'AdminCustomerDetail',
  computed: {
    customerId() {
      return this.$route.params.id
    }
  }
}
</script>
<style scoped>
.admin-customer-detail { padding: 20px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #ff6b35; text-decoration: none; }
.back-link a:hover { text-decoration: underline; }
.customer-info { background: #fff; padding: 20px; border-radius: 8px; }
.status-badge { padding: 2px 10px; border-radius: 50px; font-size: 12px; font-weight: 600; }
.status-badge.active { background: #e8f5e9; color: #2e7d32; }
</style>
