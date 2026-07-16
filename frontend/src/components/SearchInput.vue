<script setup>
import { computed, watch } from 'vue'
import { useMoviesStore } from '../stores/movies'

const DEBOUNCE_MS = 400
const MIN_SEARCH_LENGTH = 4

const store = useMoviesStore()

const searchTerm = computed({
  get: () => store.searchTerm,
  set: (value) => {
    store.searchTerm = value
  },
})

let debounceTimer = null

watch(searchTerm, (value) => {
  clearTimeout(debounceTimer)

  debounceTimer = setTimeout(() => {
    const trimmed = value.trim()

    // Only fetch on an empty term (the default list) or once the user has
    // typed enough to search — this is the app's only 4-char gate on the
    // read side (the backend independently enforces the same rule).
    if (trimmed.length === 0 || trimmed.length >= MIN_SEARCH_LENGTH) {
      store.fetchMovies(trimmed)
    }
  }, DEBOUNCE_MS)
})
</script>

<template>
  <div class="search-input">
    <label for="movie-search">Search movies</label>
    <input
      id="movie-search"
      v-model="searchTerm"
      type="search"
      autocomplete="off"
      placeholder="Type at least 4 characters…"
    />
  </div>
</template>

<style scoped>
.search-input {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 24px;
}

.search-input label {
  font-size: 14px;
  color: var(--text);
}

.search-input input {
  font: inherit;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text-h);
}

.search-input input:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}
</style>
