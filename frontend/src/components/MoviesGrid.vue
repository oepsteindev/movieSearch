<script setup>
import MovieCard from './MovieCard.vue'

defineProps({
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
</script>

<template>
  <p v-if="isLoading" class="movies-grid__status">Loading movies…</p>
  <p v-else-if="error" class="movies-grid__status movies-grid__status--error">
    {{ error }}
  </p>
  <p v-else-if="movies.length === 0" class="movies-grid__status">
    No movies found.
  </p>
  <div v-else class="movies-grid">
    <MovieCard v-for="movie in movies" :key="movie.id" :movie="movie" />
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
