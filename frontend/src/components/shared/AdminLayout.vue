<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 dark:bg-gray-950 text-white transform transition-transform duration-300 ease-in-out" 
           :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      <div class="flex items-center justify-between p-4 border-b border-gray-800 dark:border-gray-700">
        <h1 class="text-xl font-bold">CMS Admin</h1>
        <button @click="toggleSidebar" class="lg:hidden text-gray-400 hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <nav class="p-4 space-y-2">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.to"
          class="flex items-center px-4 py-3 rounded-lg transition-colors"
          :class="isActive(item.to) ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span>{{ item.name }}</span>
        </router-link>
      </nav>

      <div class="absolute bottom-0 w-full p-4 border-t border-gray-800">
        <button
          @click="handleLogout"
          class="flex items-center w-full px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="lg:ml-64">
      <!-- Top navbar -->
      <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
          <button @click="toggleSidebar" class="lg:hidden text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ pageTitle }}</h2>

          <div class="flex items-center space-x-4">
            <!-- Theme Toggle -->
            <button
              @click="themeStore.toggleTheme()"
              class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              :title="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
              <!-- Sun Icon (Light Mode) -->
              <svg v-if="!themeStore.isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <!-- Moon Icon (Dark Mode) -->
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>

            <span class="text-sm text-gray-600 dark:text-gray-300">{{ userFullName }}</span>
            <div class="w-8 h-8 bg-blue-600 dark:bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
              {{ userInitials }}
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6">
        <slot />
      </main>
    </div>

    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      @click="toggleSidebar"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useThemeStore } from '../../stores/theme'

const router = useRouter()
const route = useRoute()
const themeStore = useThemeStore()
const authStore = useAuthStore()

const sidebarOpen = ref(false)

const navigation = [
  {
    name: 'Dashboard',
    to: '/admin/dashboard',
    icon: 'svg'
  },
  {
    name: 'Pages',
    to: '/admin/pages',
    icon: 'svg'
  },
  {
    name: 'Blocks',
    to: '/admin/blocks',
    icon: 'svg'
  },
  {
    name: 'Media',
    to: '/admin/media',
    icon: 'svg'
  }
]

const pageTitle = computed(() => route.meta.title || 'Admin')
const userFullName = computed(() => authStore.userFullName || 'User')
const userInitials = computed(() => {
  const name = authStore.user?.firstName || authStore.user?.email || 'U'
  return name.charAt(0).toUpperCase()
})

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/admin/login')
}
</script>
