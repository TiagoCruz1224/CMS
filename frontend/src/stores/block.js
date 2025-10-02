import { defineStore } from 'pinia'
import { blockService } from '../services'

export const useBlockStore = defineStore('block', {
  state: () => ({
    blocks: []
  }),
  
  actions: {
    async fetchBlocks() {
      try {
        const response = await blockService.getAll()
        this.blocks = response.data
      } catch (error) {
        console.error('Failed to fetch blocks:', error)
        throw error
      }
    },

    async createBlock(blockData) {
      try {
        const response = await blockService.create(blockData)
        this.blocks.push(response.data)
        return response.data
      } catch (error) {
        console.error('Failed to create block:', error)
        throw error
      }
    },

    async updateBlock(id, blockData) {
      try {
        const response = await blockService.update(id, blockData)
        const index = this.blocks.findIndex(b => b.id === id)
        if (index !== -1) {
          this.blocks[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error('Failed to update block:', error)
        throw error
      }
    },

    async deleteBlock(id) {
      try {
        await blockService.delete(id)
        this.blocks = this.blocks.filter(b => b.id !== id)
      } catch (error) {
        console.error('Failed to delete block:', error)
        throw error
      }
    }
  }
})
