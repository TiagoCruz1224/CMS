<template>
  <AdminLayout>
    <div>
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Blocks</h2>
          <p class="text-gray-600 mt-1">Manage reusable content blocks</p>
        </div>
        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Block
        </button>
      </div>

      <!-- Blocks Grid -->
      <div v-if="blockStore.blocks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="block in blockStore.blocks"
          :key="block.id"
          class="bg-white rounded-lg shadow p-6 hover:shadow-md transition"
        >
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="font-semibold text-gray-900">{{ block.name }}</h3>
              <span class="text-xs text-gray-500 mt-1 inline-block">{{ block.type }}</span>
            </div>
            <div class="flex gap-2">
              <button
                @click="openEditModal(block)"
                class="text-blue-600 hover:text-blue-800 transition"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="openDeleteModal(block)"
                class="text-red-600 hover:text-red-800 transition"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          
          <div class="text-sm text-gray-600 line-clamp-3">
            {{ block.content }}
          </div>

          <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <span>Order: {{ block.sortOrder }}</span>
            <span v-if="block.page">Page: {{ block.page.title }}</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No blocks yet</h3>
        <p class="mt-2 text-gray-600">Get started by creating your first content block.</p>
        <button
          @click="openCreateModal"
          class="mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition"
        >
          Create Block
        </button>
      </div>

      <!-- Create/Edit Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">
              {{ editingBlock ? 'Edit Block' : 'Create Block' }}
            </h3>
          </div>

          <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Name *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Block name"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Type *
              </label>
              <select
                v-model="form.type"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option value="text">Text</option>
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="html">HTML</option>
                <option value="widget">Widget</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Content *
              </label>
              <textarea
                v-model="form.content"
                required
                rows="6"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Block content..."
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Sort Order
              </label>
              <input
                v-model.number="form.sortOrder"
                type="number"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="0"
              />
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-sm text-red-600">{{ error }}</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="bg-blue-600 text-white px-8 py-2 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (editingBlock ? 'Update' : 'Create') }}
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
          <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Block</h3>
          <p class="text-gray-600 mb-6">
            Are you sure you want to delete "{{ blockToDelete?.name }}"? This action cannot be undone.
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
              :disabled="loading"
              class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition disabled:opacity-50"
            >
              {{ loading ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useBlockStore } from '../../stores/block'
import AdminLayout from '../../components/shared/AdminLayout.vue'

const blockStore = useBlockStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingBlock = ref(null)
const blockToDelete = ref(null)
const loading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  type: 'text',
  content: '',
  sortOrder: 0
})

const openCreateModal = () => {
  editingBlock.value = null
  form.value = {
    name: '',
    type: 'text',
    content: '',
    sortOrder: 0
  }
  error.value = ''
  showModal.value = true
}

const openEditModal = (block) => {
  editingBlock.value = block
  form.value = {
    name: block.name,
    type: block.type,
    content: block.content,
    sortOrder: block.sortOrder
  }
  error.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingBlock.value = null
}

const openDeleteModal = (block) => {
  blockToDelete.value = block
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  blockToDelete.value = null
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    if (editingBlock.value) {
      await blockStore.updateBlock(editingBlock.value.id, form.value)
    } else {
      await blockStore.createBlock(form.value)
    }
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to save block'
    console.error('Error saving block:', err)
  } finally {
    loading.value = false
  }
}

const confirmDelete = async () => {
  if (!blockToDelete.value) return

  loading.value = true
  try {
    await blockStore.deleteBlock(blockToDelete.value.id)
    closeDeleteModal()
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to delete block'
    console.error('Error deleting block:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  blockStore.fetchBlocks()
})
</script>
