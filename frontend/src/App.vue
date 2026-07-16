<script setup>
import { onMounted } from 'vue'
import { useMoviesStore } from './stores/movies'
import SearchInput from './components/SearchInput.vue'
import MoviesGrid from './components/MoviesGrid.vue'

const store = useMoviesStore()

onMounted(() => {
  if (!store.hasLoaded) {
    store.fetchMovies()
  }
})
</script>

<template>
  <main>
    <h1>MovieSearch</h1>
    <SearchInput />
    <MoviesGrid :movies="store.movies" :isLoading="store.isLoading" :error="store.error" />
  </main>
</template>
