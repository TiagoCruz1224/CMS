import api from './api'

export const authService = {
  async login(email, password) {
    const response = await api.post('/login', { email, password })
    return response.data
  },

  async register(userData) {
    const response = await api.post('/register', userData)
    return response.data
  },

  async getProfile() {
    const response = await api.get('/profile')
    return response.data
  },

  async updateProfile(userData) {
    const response = await api.put('/profile', userData)
    return response.data
  },

  logout() {
    localStorage.removeItem('jwt_token')
    localStorage.removeItem('user')
  }
}

export const pageService = {
  async getAll(params = {}) {
    const response = await api.get('/pages', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/pages/${id}`)
    return response.data
  },

  async create(pageData) {
    const response = await api.post('/pages', pageData)
    return response.data
  },

  async update(id, pageData) {
    const response = await api.put(`/pages/${id}`, pageData)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/pages/${id}`)
    return response.data
  },

  async getRevisions(id) {
    const response = await api.get(`/pages/${id}/revisions`)
    return response.data
  }
}

export const blockService = {
  async getById(id) {
    const response = await api.get(`/blocks/${id}`)
    return response.data
  },

  async create(blockData) {
    const response = await api.post('/blocks', blockData)
    return response.data
  },

  async update(id, blockData) {
    const response = await api.put(`/blocks/${id}`, blockData)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/blocks/${id}`)
    return response.data
  }
}

export const mediaService = {
  async getAll() {
    const response = await api.get('/media')
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/media/${id}`)
    return response.data
  },

  async create(mediaData) {
    const response = await api.post('/media', mediaData)
    return response.data
  },

  async update(id, mediaData) {
    const response = await api.put(`/media/${id}`, mediaData)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/media/${id}`)
    return response.data
  }
}

export const categoryService = {
  async getAll() {
    const response = await api.get('/categories')
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/categories/${id}`)
    return response.data
  },

  async create(categoryData) {
    const response = await api.post('/categories', categoryData)
    return response.data
  },

  async update(id, categoryData) {
    const response = await api.put(`/categories/${id}`, categoryData)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/categories/${id}`)
    return response.data
  }
}
