import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Admin views
import AdminLogin from '../views/admin/Login.vue'
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminPages from '../views/admin/Pages.vue'
import AdminPageForm from '../views/admin/PageForm.vue'
import AdminBlocks from '../views/admin/Blocks.vue'
import AdminMedia from '../views/admin/Media.vue'

const routes = [
  // Public routes
  {
    path: '/',
    redirect: '/admin/dashboard'
  },

  // Admin routes
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminLogin,
    meta: { requiresAuth: false, layout: 'blank' }
  },
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, title: 'Dashboard' }
  },
  {
    path: '/admin/pages',
    name: 'admin-pages',
    component: AdminPages,
    meta: { requiresAuth: true, title: 'Pages' }
  },
  {
    path: '/admin/pages/create',
    name: 'admin-page-create',
    component: AdminPageForm,
    meta: { requiresAuth: true, title: 'Create Page' }
  },
  {
    path: '/admin/pages/:id/edit',
    name: 'admin-page-edit',
    component: AdminPageForm,
    meta: { requiresAuth: true, title: 'Edit Page' }
  },
  {
    path: '/admin/blocks',
    name: 'admin-blocks',
    component: AdminBlocks,
    meta: { requiresAuth: true, title: 'Blocks' }
  },
  {
    path: '/admin/media',
    name: 'admin-media',
    component: AdminMedia,
    meta: { requiresAuth: true, title: 'Media' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.meta.requiresAuth

  if (requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'admin-login', query: { redirect: to.fullPath } })
  } else if (to.name === 'admin-login' && authStore.isAuthenticated) {
    next({ name: 'admin-dashboard' })
  } else {
    next()
  }
})

export default router
