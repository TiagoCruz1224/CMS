<template>
  <AdminLayout>
    <div class="max-w-4xl">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Page' : 'Create Page' }}</h2>
        <p class="text-gray-600 mt-1">{{ isEdit ? 'Update page content' : 'Add a new page to your site' }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
              Title *
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Enter page title"
            />
          </div>

          <!-- Slug -->
          <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
              Slug *
            </label>
            <input
              id="slug"
              v-model="form.slug"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="page-url-slug"
            />
            <p class="text-sm text-gray-500 mt-1">URL-friendly version of the title</p>
          </div>

          <!-- Content -->
          <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
              Content
            </label>
            <textarea
              id="content"
              v-model="form.content"
              rows="8"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Page content..."
            ></textarea>
          </div>

          <!-- Status -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              id="status"
              v-model="form.status"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <!-- SEO Section -->
          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Settings</h3>
            
            <div class="space-y-4">
              <div>
                <label for="metaTitle" class="block text-sm font-medium text-gray-700 mb-2">
                  Meta Title
                </label>
                <input
                  id="metaTitle"
                  v-model="form.metaTitle"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  placeholder="SEO title for search engines"
                />
              </div>

              <div>
                <label for="metaDescription" class="block text-sm font-medium text-gray-700 mb-2">
                  Meta Description
                </label>
                <textarea
                  id="metaDescription"
                  v-model="form.metaDescription"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  placeholder="SEO description (max 160 characters)"
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Meta Keywords
                </label>
                <input
                  v-model="keywordsInput"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  placeholder="keyword1, keyword2, keyword3"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between bg-white rounded-lg shadow p-6">
          <router-link
            to="/admin/pages"
            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            Cancel
          </router-link>
          
          <button
            type="submit"
            :disabled="loading"
            class="bg-blue-600 text-white px-8 py-2 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!loading">{{ isEdit ? 'Update Page' : 'Create Page' }}</span>
            <span v-else>Saving...</span>
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePageStore } from '../../stores/page'
import AdminLayout from '../../components/shared/AdminLayout.vue'

const router = useRouter()
const route = useRoute()
const pageStore = usePageStore()

const isEdit = computed(() => !!route.params.id)
const loading = ref(false)
const error = ref('')

const form = ref({
  title: '',
  slug: '',
  content: '',
  status: 'draft',
  metaTitle: '',
  metaDescription: '',
  metaKeywords: []
})

const keywordsInput = ref('')

// Auto-generate slug from title
watch(() => form.value.title, (newTitle) => {
  if (!isEdit.value && newTitle) {
    form.value.slug = newTitle
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '')
  }
})

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    // Parse keywords
    if (keywordsInput.value) {
      form.value.metaKeywords = keywordsInput.value
        .split(',')
        .map(k => k.trim())
        .filter(k => k)
    }

    if (isEdit.value) {
      await pageStore.updatePage(route.params.id, form.value)
    } else {
      await pageStore.createPage(form.value)
    }

    router.push('/admin/pages')
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to save page'
    console.error('Error saving page:', err)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (isEdit.value) {
    loading.value = true
    try {
      await pageStore.fetchPageById(route.params.id)
      const page = pageStore.currentPage
      
      if (page) {
        form.value = {
          title: page.title,
          slug: page.slug,
          content: page.content || '',
          status: page.status,
          metaTitle: page.metaTitle || '',
          metaDescription: page.metaDescription || '',
          metaKeywords: page.metaKeywords || []
        }
        
        if (page.metaKeywords?.length) {
          keywordsInput.value = page.metaKeywords.join(', ')
        }
      }
    } catch (err) {
      error.value = 'Failed to load page'
      console.error('Error loading page:', err)
    } finally {
      loading.value = false
    }
  }
})
</script>
