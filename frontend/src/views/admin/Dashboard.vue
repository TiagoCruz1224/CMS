<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Pages</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalPages }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Published</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.publishedPages }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Draft Pages</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.draftPages }}</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
              <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Media Files</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalMedia }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Pages -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Pages</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          <div v-else-if="recentPages.length === 0" class="text-center py-8 text-gray-500">
            No pages yet. Create your first page!
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="page in recentPages"
              :key="page.id"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
            >
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900">{{ page.title }}</h4>
                <p class="text-sm text-gray-600 mt-1">{{ page.slug }}</p>
              </div>
              <div class="flex items-center space-x-4">
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="getStatusClass(page.status)"
                >
                  {{ page.status }}
                </span>
                <router-link
                  :to="`/admin/pages/${page.id}/edit`"
                  class="text-blue-600 hover:text-blue-800 font-medium text-sm"
                >
                  Edit
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <router-link
          to="/admin/pages/create"
          class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition flex items-center space-x-4"
        >
          <div class="bg-blue-100 rounded-full p-3">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">Create Page</h4>
            <p class="text-sm text-gray-600">Add a new page</p>
          </div>
        </router-link>

        <router-link
          to="/admin/media"
          class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition flex items-center space-x-4"
        >
          <div class="bg-green-100 rounded-full p-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">Upload Media</h4>
            <p class="text-sm text-gray-600">Add images/files</p>
          </div>
        </router-link>

        <router-link
          to="/admin/pages"
          class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition flex items-center space-x-4"
        >
          <div class="bg-purple-100 rounded-full p-3">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">View All Pages</h4>
            <p class="text-sm text-gray-600">Manage content</p>
          </div>
        </router-link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePageStore } from '../../stores/page'
import { useMediaStore } from '../../stores/media'
import AdminLayout from '../../components/shared/AdminLayout.vue'

const pageStore = usePageStore()
const mediaStore = useMediaStore()

const loading = ref(true)

const recentPages = computed(() => pageStore.pages.slice(0, 5))

const stats = computed(() => ({
  totalPages: pageStore.pages.length,
  publishedPages: pageStore.pages.filter(p => p.status === 'published').length,
  draftPages: pageStore.pages.filter(p => p.status === 'draft').length,
  totalMedia: mediaStore.media.length
}))

const getStatusClass = (status) => {
  const classes = {
    published: 'bg-green-100 text-green-800',
    draft: 'bg-yellow-100 text-yellow-800',
    archived: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(async () => {
  try {
    await Promise.all([
      pageStore.fetchPages({ limit: 10 }),
      mediaStore.fetchMedia()
    ])
  } finally {
    loading.value = false
  }
})
</script>
