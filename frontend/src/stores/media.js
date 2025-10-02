import { defineStore } from 'pinia'
import { mediaService } from '../services'

export const useMediaStore = defineStore('media', {
  state: () => ({
    media: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchMedia() {
      this.loading = true
      this.error = null
      try {
        this.media = await mediaService.getAll()
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch media'
        console.error('Error fetching media:', error)
      } finally {
        this.loading = false
      }
    },

    async createMedia(mediaData) {
      this.loading = true
      this.error = null
      try {
        const newMedia = await mediaService.create(mediaData)
        this.media.unshift(newMedia)
        return newMedia
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create media'
        console.error('Error creating media:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteMedia(id) {
      this.loading = true
      this.error = null
      try {
        await mediaService.delete(id)
        this.media = this.media.filter(m => m.id !== id)
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete media'
        console.error('Error deleting media:', error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
