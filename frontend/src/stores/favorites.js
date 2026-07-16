import { defineStore } from 'pinia'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

// Shared by every action below: fetch, parse the JSON body (DELETE endpoints
// return 204 with no body), and throw using the API's error message when the
// response isn't ok.
async function requestJson(url, options, fallbackError) {
  const response = await fetch(url, options)
  const data = response.status === 204 ? null : await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(data?.error || fallbackError)
  }

  return data
}

export const useFavoritesStore = defineStore('favorites', {
  state: () => ({
    lists: [],
    selectedListId: null,
    selectedListItems: [],
    isLoadingLists: false,
    isSaving: false,
    error: null,
  }),

  actions: {
    async fetchLists() {
      this.isLoadingLists = true
      this.error = null

      try {
        const data = await requestJson(`${API_BASE_URL}/api/lists`, {}, 'Unable to fetch lists right now.')
        this.lists = data.lists
      } catch (err) {
        this.error = err.message
      } finally {
        this.isLoadingLists = false
      }
    },

    async fetchList(id) {
      this.isLoadingLists = true
      this.error = null

      try {
        const data = await requestJson(`${API_BASE_URL}/api/lists/${id}`, {}, 'Unable to fetch this list right now.')
        this.selectedListItems = data.list.items
      } catch (err) {
        this.error = err.message
        this.selectedListItems = []
      } finally {
        this.isLoadingLists = false
      }
    },

    async selectList(id) {
      this.selectedListId = id
      await this.fetchList(id)
    },

    // Returns the created list (so callers can immediately favorite an item
    // into it), or null if creation failed — the error is left in state.error.
    async createList(name) {
      this.isSaving = true
      this.error = null

      try {
        const data = await requestJson(`${API_BASE_URL}/api/lists`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name }),
        }, 'Unable to create this list right now.')

        await this.fetchLists()
        return data.list
      } catch (err) {
        this.error = err.message
        return null
      } finally {
        this.isSaving = false
      }
    },

    async deleteList(id) {
      this.isSaving = true
      this.error = null

      try {
        await requestJson(`${API_BASE_URL}/api/lists/${id}`, { method: 'DELETE' }, 'Unable to delete this list right now.')

        if (this.selectedListId === id) {
          this.selectedListId = null
          this.selectedListItems = []
        }

        await this.fetchLists()
      } catch (err) {
        this.error = err.message
      } finally {
        this.isSaving = false
      }
    },

    async addItemToLists(movie, listIds) {
      this.isSaving = true
      this.error = null

      try {
        await Promise.all(listIds.map((listId) => requestJson(`${API_BASE_URL}/api/lists/${listId}/favorites`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            externalId: movie.id,
            name: movie.name,
            image: movie.image,
            year: movie.year,
          }),
        }, 'Unable to add this favorite right now.')))

        await this.fetchLists()
      } catch (err) {
        this.error = err.message
      } finally {
        this.isSaving = false
      }
    },

    async removeFavorite(listId, itemId) {
      this.isSaving = true
      this.error = null

      try {
        await requestJson(`${API_BASE_URL}/api/lists/${listId}/favorites/${itemId}`, { method: 'DELETE' }, 'Unable to remove this favorite right now.')

        await this.fetchList(listId)
        await this.fetchLists()
      } catch (err) {
        this.error = err.message
      } finally {
        this.isSaving = false
      }
    },
  },
})
