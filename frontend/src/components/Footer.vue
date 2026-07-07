<!-- components/Footer.vue -->
<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-logo">
          <span class="logo-icon">⚡</span>
          <span>MetroNet</span>
        </div>
        <p class="footer-text">&copy; 2025 MetroNet ISP. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'Footer'
}
</script>

<style scoped>
.footer {
  background: #1a1a2e;
  color: rgba(255,255,255,0.7);
  padding: 30px 0;
  text-align: center;
}

.footer-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 22px;
  font-weight: 700;
  color: #fff;
}

.footer-logo .logo-icon {
  color: #ff6b35;
  font-size: 26px;
}

.footer-text {
  font-size: 14px;
}
</style>
