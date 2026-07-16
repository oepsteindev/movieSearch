import { defineStore } from 'pinia'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

export const useMoviesStore = defineStore('movies', {
  state: () => ({
    searchTerm: '',
    movies: [],
    isLoading: false,
    error: null,
    hasLoaded: false,
  }),

  getters: {
    movieCount: (state) => state.movies.length,
  },

  actions: {
    async fetchMovies(term = '') {
      const trimmed = term.trim()

      this.isLoading = true
      this.error = null

      const url = trimmed
        ? `${API_BASE_URL}/api/movies?search=${encodeURIComponent(trimmed)}`
        : `${API_BASE_URL}/api/movies`

      try {
        const response = await fetch(url)
        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Unable to fetch movies right now.')
        }

        this.movies = data.results
        this.hasLoaded = true
      } catch (err) {
        this.error = err.message
        this.movies = []
      } finally {
        this.isLoading = false
      }
    },
  },
})
