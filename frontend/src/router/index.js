import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import PlansPage from '../views/PlansPage.vue'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'
import ProfilePage from '../views/ProfilePage.vue'
import SubscriptionsPage from '../views/SubscriptionsPage.vue'
import InvoicesPage from '../views/InvoicesPage.vue'
import NotificationsPage from '../views/NotificationsPage.vue'
import PaymentsPage from '../views/PaymentsPage.vue'

// Admin Layout and Pages
import AdminLayout from '../layout/AdminLayout.vue'
import AdminCustomers from '../views/admin/Customers.vue'
import AdminPlans from '../views/admin/Plans.vue'
import AdminSubscriptions from '../views/admin/Subscriptions.vue'
import AdminInvoices from '../views/admin/Invoices.vue'
import AdminPayments from '../views/admin/Payments.vue'
import AdminCPEs from '../views/admin/CPEs.vue'
import AdminServiceAreas from '../views/admin/ServiceAreas.vue'
import AdminCustomerDetail from '../views/admin/CustomerDetail.vue'

const routes = [
  // Public Routes
  {
    path: '/',
    name: 'Home',
    component: HomePage,
    meta: {
      title: 'MetroNet - Fast Reliable Internet',
      requiresAuth: false
    }
  },
  {
    path: '/plans',
    name: 'Plans',
    component: PlansPage,
    meta: {
      title: 'MetroNet - Our Internet Plans',
      requiresAuth: false
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage,
    meta: {
      title: 'MetroNet - Sign In',
      requiresAuth: false,
      guestOnly: true
    }
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage,
    meta: {
      title: 'MetroNet - Create Account',
      requiresAuth: false,
      guestOnly: true
    }
  },

  // Authenticated User Routes
  {
    path: '/profile',
    name: 'Profile',
    component: ProfilePage,
    meta: {
      title: 'MetroNet - My Profile',
      requiresAuth: true
    }
  },
  {
    path: '/subscriptions',
    name: 'Subscriptions',
    component: SubscriptionsPage,
    meta: {
      title: 'MetroNet - My Subscriptions',
      requiresAuth: true
    }
  },
  {
    path: '/invoices',
    name: 'Invoices',
    component: InvoicesPage,
    meta: {
      title: 'MetroNet - My Invoices',
      requiresAuth: true
    }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: NotificationsPage,
    meta: {
      title: 'MetroNet - Notifications',
      requiresAuth: true
    }
  },
  {
    path: '/payments',
    name: 'Payments',
    component: PaymentsPage,
    meta: {
      title: 'MetroNet - Payment History',
      requiresAuth: true
    }
  },

  // Admin Routes (role 0 = admin)
  {
    path: '/admin',
    component: AdminLayout,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    },
    children: [
      {
        path: '',
        redirect: '/admin/customers' // Redirect to customers
      },
      {
        path: 'customers',
        name: 'admin-customers',
        component: AdminCustomers,
        meta: {
          title: 'Admin - Customers',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'customers/:id',
        name: 'admin-customer-detail',
        component: AdminCustomerDetail,
        meta: {
          title: 'Admin - Customer Detail',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'plans',
        name: 'admin-plans',
        component: AdminPlans,
        meta: {
          title: 'Admin - Plans',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'subscriptions',
        name: 'admin-subscriptions',
        component: AdminSubscriptions,
        meta: {
          title: 'Admin - Subscriptions',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'invoices',
        name: 'admin-invoices',
        component: AdminInvoices,
        meta: {
          title: 'Admin - Invoices',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'payments',
        name: 'admin-payments',
        component: AdminPayments,
        meta: {
          title: 'Admin - Payments',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'cpes',
        name: 'admin-cpes',
        component: AdminCPEs,
        meta: {
          title: 'Admin - CPEs',
          requiresAuth: true,
          requiresAdmin: true
        }
      },
      {
        path: 'service-areas',
        name: 'admin-service-areas',
        component: AdminServiceAreas,
        meta: {
          title: 'Admin - Service Areas',
          requiresAuth: true,
          requiresAdmin: true
        }
      }
    ]
  },

  // 404 Catch-all
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Navigation Guard - Handle Authentication and Authorization
router.beforeEach((to, from, next) => {
  // Set page title
  document.title = to.meta.title || 'MetroNet ISP'

  // Check if user is logged in
  const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'

  // Get user role (0 = admin, 1 = user)
  const userRole = parseInt(localStorage.getItem('userRole') || '1')
  const isAdmin = userRole === 0

  // Debug logging
  console.log('🔍 Router Guard - Path:', to.path)
  console.log('🔍 Router Guard - isLoggedIn:', isLoggedIn)
  console.log('🔍 Router Guard - userRole:', userRole)
  console.log('🔍 Router Guard - isAdmin:', isAdmin)
  console.log('🔍 Router Guard - requiresAdmin:', to.meta.requiresAdmin)

  // Route requires authentication
  if (to.meta.requiresAuth && !isLoggedIn) {
    console.log('🔴 Redirecting to login - requires auth')
    next({
      path: '/login',
      query: { redirect: to.fullPath }
    })
    return
  }

  // Route is for guests only (login/register)
  if (to.meta.guestOnly && isLoggedIn) {
    console.log('🔴 Redirecting to home - guest only')
    next('/')
    return
  }

  // Route requires admin access (role 0 = admin)
  if (to.meta.requiresAdmin) {
    if (!isLoggedIn) {
      console.log('🔴 Redirecting to login - not logged in')
      next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
      return
    }
    if (!isAdmin) {
      console.log('🔴 Redirecting to home - not admin (role: ' + userRole + ')')
      next('/')
      return
    }
    console.log('✅ Admin access granted')
  }

  // Allow navigation
  next()
})

export default router
