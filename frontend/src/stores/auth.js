import { defineStore } from 'pinia'
import { authService } from '../services'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('jwt_token') || null,
    isAuthenticated: !!localStorage.getItem('jwt_token')
  }),

  getters: {
    isAdmin: (state) => {
      return state.user?.roles?.includes('ROLE_ADMIN') || false
    },
    userFullName: (state) => {
      if (!state.user) return ''
      return `${state.user.firstName || ''} ${state.user.lastName || ''}`.trim() || state.user.email
    }
  },

  actions: {
    async login(email, password) {
      try {
        const response = await authService.login(email, password)
        this.token = response.token
        localStorage.setItem('jwt_token', response.token)
        
        // Get user profile
        const profile = await authService.getProfile()
        this.user = profile
        localStorage.setItem('user', JSON.stringify(profile))
        this.isAuthenticated = true
        
        return true
      } catch (error) {
        console.error('Login failed:', error)
        throw error
      }
    },

    async logout() {
      authService.logout()
      this.user = null
      this.token = null
      this.isAuthenticated = false
    },

    async fetchProfile() {
      try {
        const profile = await authService.getProfile()
        this.user = profile
        localStorage.setItem('user', JSON.stringify(profile))
      } catch (error) {
        console.error('Failed to fetch profile:', error)
        this.logout()
      }
    }
  }
})
