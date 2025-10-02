<template>
  <AdminLayout>
    <div>
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Media Library</h2>
          <p class="text-gray-600 mt-1">Manage images, videos, and files</p>
        </div>
        <button
          @click="showUploadModal = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          Upload Media
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex items-center gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search media..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <select
            v-model="typeFilter"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="">All Types</option>
            <option value="image">Images</option>
            <option value="video">Videos</option>
            <option value="document">Documents</option>
          </select>
        </div>
      </div>

      <!-- Media Grid -->
      <div v-if="filteredMedia.length > 0" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          <div
            v-for="media in filteredMedia"
            :key="media.id"
            class="group relative border border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition cursor-pointer"
            @click="selectMedia(media)"
          >
            <!-- Thumbnail -->
            <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
              <img
                v-if="media.type === 'image'"
                :src="getMediaUrl(media.filePath)"
                :alt="media.altText"
                class="w-full h-full object-cover"
              />
              <div v-else class="flex flex-col items-center justify-center p-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span class="mt-2 text-xs text-gray-500">{{ media.type }}</span>
              </div>
            </div>

            <!-- Info -->
            <div class="p-2 bg-white">
              <p class="text-xs font-medium text-gray-900 truncate">{{ media.title }}</p>
              <p class="text-xs text-gray-500">{{ formatFileSize(media.fileSize) }}</p>
            </div>

            <!-- Actions Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
              <button
                @click.stop="openDeleteModal(media)"
                class="bg-red-600 text-white p-2 rounded-lg hover:bg-red-700 transition"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No media files</h3>
        <p class="mt-2 text-gray-600">Upload your first media file to get started.</p>
        <button
          @click="showUploadModal = true"
          class="mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition"
        >
          Upload Media
        </button>
      </div>

      <!-- Upload Modal -->
      <div
        v-if="showUploadModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
        @click.self="closeUploadModal"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Upload Media</h3>
          </div>

          <form @submit.prevent="handleUpload" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                File *
              </label>
              <input
                type="file"
                @change="handleFileSelect"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Title *
              </label>
              <input
                v-model="uploadForm.title"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Media title"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Alt Text (for images)
              </label>
              <input
                v-model="uploadForm.altText"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Alternative text for accessibility"
              />
            </div>

            <div v-if="uploadError" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-sm text-red-600">{{ uploadError }}</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
              <button
                type="button"
                @click="closeUploadModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="uploading"
                class="bg-blue-600 text-white px-8 py-2 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50"
              >
                {{ uploading ? 'Uploading...' : 'Upload' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
        @click.self="closeDeleteModal"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Media</h3>
          <p class="text-gray-600 mb-6">
            Are you sure you want to delete "{{ mediaToDelete?.title }}"? This action cannot be undone.
          </p>
          <div class="flex items-center justify-end gap-3">
            <button
              @click="closeDeleteModal"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
            >
              Cancel
            </button>
            <button
              @click="confirmDelete"
              :disabled="deleting"
              class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition disabled:opacity-50"
            >
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Media Details Modal -->
      <div
        v-if="selectedMedia"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
        @click.self="selectedMedia = null"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Media Details</h3>
            <button @click="selectedMedia = null" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="selectedMedia.type === 'image'" class="mb-6">
              <img
                :src="getMediaUrl(selectedMedia.filePath)"
                :alt="selectedMedia.altText"
                class="max-w-full h-auto rounded-lg"
              />
            </div>

            <div class="space-y-3">
              <div>
                <span class="text-sm font-medium text-gray-700">Title:</span>
                <span class="ml-2 text-sm text-gray-900">{{ selectedMedia.title }}</span>
              </div>
              <div>
                <span class="text-sm font-medium text-gray-700">File:</span>
                <span class="ml-2 text-sm text-gray-900">{{ selectedMedia.filePath }}</span>
              </div>
              <div>
                <span class="text-sm font-medium text-gray-700">Type:</span>
                <span class="ml-2 text-sm text-gray-900">{{ selectedMedia.type }}</span>
              </div>
              <div>
                <span class="text-sm font-medium text-gray-700">Size:</span>
                <span class="ml-2 text-sm text-gray-900">{{ formatFileSize(selectedMedia.fileSize) }}</span>
              </div>
              <div v-if="selectedMedia.altText">
                <span class="text-sm font-medium text-gray-700">Alt Text:</span>
                <span class="ml-2 text-sm text-gray-900">{{ selectedMedia.altText }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMediaStore } from '../../stores/media'
import AdminLayout from '../../components/shared/AdminLayout.vue'

const mediaStore = useMediaStore()

const searchQuery = ref('')
const typeFilter = ref('')
const showUploadModal = ref(false)
const showDeleteModal = ref(false)
const selectedMedia = ref(null)
const mediaToDelete = ref(null)
const uploading = ref(false)
const deleting = ref(false)
const uploadError = ref('')

const uploadForm = ref({
  title: '',
  altText: '',
  file: null
})

const filteredMedia = computed(() => {
  let result = mediaStore.media

  if (searchQuery.value) {
    result = result.filter(m => 
      m.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (typeFilter.value) {
    result = result.filter(m => m.type === typeFilter.value)
  }

  return result
})

const handleFileSelect = (event) => {
  uploadForm.value.file = event.target.files[0]
  
  // Auto-fill title from filename if empty
  if (!uploadForm.value.title && uploadForm.value.file) {
    uploadForm.value.title = uploadForm.value.file.name.replace(/\.[^/.]+$/, '')
  }
}

const handleUpload = async () => {
  if (!uploadForm.value.file) return

  uploading.value = true
  uploadError.value = ''

  try {
    const formData = new FormData()
    formData.append('file', uploadForm.value.file)
    formData.append('title', uploadForm.value.title)
    if (uploadForm.value.altText) {
      formData.append('altText', uploadForm.value.altText)
    }

    await mediaStore.createMedia(formData)
    closeUploadModal()
  } catch (err) {
    uploadError.value = err.response?.data?.error || 'Failed to upload media'
    console.error('Error uploading media:', err)
  } finally {
    uploading.value = false
  }
}

const closeUploadModal = () => {
  showUploadModal.value = false
  uploadForm.value = {
    title: '',
    altText: '',
    file: null
  }
  uploadError.value = ''
}

const selectMedia = (media) => {
  selectedMedia.value = media
}

const openDeleteModal = (media) => {
  mediaToDelete.value = media
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  mediaToDelete.value = null
}

const confirmDelete = async () => {
  if (!mediaToDelete.value) return

  deleting.value = true
  try {
    await mediaStore.deleteMedia(mediaToDelete.value.id)
    closeDeleteModal()
  } catch (err) {
    console.error('Error deleting media:', err)
  } finally {
    deleting.value = false
  }
}

const getMediaUrl = (filePath) => {
  return filePath.startsWith('/') ? filePath : `/${filePath}`
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

onMounted(() => {
  mediaStore.fetchMedia()
})
</script>
