import { defineStore } from 'pinia'
import { pageService } from '../services'

export const usePageStore = defineStore('page', {
  state: () => ({
    pages: [],
    currentPage: null
  }),
  
  actions: {
    async fetchPages() {
      try {
        const response = await pageService.getAll()
        this.pages = response.data
      } catch (error) {
        console.error('Failed to fetch pages:', error)
        throw error
      }
    },

    async fetchPageById(id) {
      try {
        const response = await pageService.getById(id)
        this.currentPage = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch page:', error)
        throw error
      }
    },    async fetchPageById(id) {
      this.loading = true
      this.error = null
      try {
        this.currentPage = await pageService.getById(id)
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch page'
        console.error('Error fetching page:', error)
      } finally {
        this.loading = false
      }
    },

    async createPage(pageData) {
      this.loading = true
      this.error = null
      try {
        const newPage = await pageService.create(pageData)
        this.pages.unshift(newPage)
        return newPage
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create page'
        console.error('Error creating page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePage(id, pageData) {
      this.loading = true
      this.error = null
      try {
        const updatedPage = await pageService.update(id, pageData)
        const index = this.pages.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pages[index] = updatedPage
        }
        if (this.currentPage?.id === id) {
          this.currentPage = updatedPage
        }
        return updatedPage
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update page'
        console.error('Error updating page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletePage(id) {
      this.loading = true
      this.error = null
      try {
        await pageService.delete(id)
        this.pages = this.pages.filter(p => p.id !== id)
        if (this.currentPage?.id === id) {
          this.currentPage = null
        }
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete page'
        console.error('Error deleting page:', error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
