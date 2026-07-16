<script setup>
import { reactive, computed } from 'vue'
import MovieCard from './MovieCard.vue'

const props = defineProps({
  movies: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
})

// Movies whose poster URL turned out to be a dead link once the browser
// actually tried to load it (see MovieCard's image-error event) — dropped
// from the grid entirely, matching how the backend drops posterless movies.
const failedImageIds = reactive({})

const visibleMovies = computed(() => props.movies.filter((movie) => !failedImageIds[movie.id]))

function handleImageError(id) {
  failedImageIds[id] = true
}
</script>

<template>
  <p v-if="isLoading" class="movies-grid__status">Loading movies…</p>
  <p v-else-if="error" class="movies-grid__status movies-grid__status--error">
    {{ error }}
  </p>
  <p v-else-if="visibleMovies.length === 0" class="movies-grid__status">
    No movies found.
  </p>
  <div v-else class="movies-grid">
    <MovieCard
      v-for="movie in visibleMovies"
      :key="movie.id"
      :movie="movie"
      @image-error="handleImageError"
    />
  </div>
</template>

<style scoped>
.movies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.5rem 1rem;
}

.movies-grid__status {
  text-align: center;
  padding: 40px 0;
}

.movies-grid__status--error {
  color: #c0392b;
}
</style>
