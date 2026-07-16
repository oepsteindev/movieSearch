<script setup>
import { onMounted } from 'vue'
import { useMoviesStore } from './stores/movies'
import { useFavoritesStore } from './stores/favorites'
import SearchInput from './components/SearchInput.vue'
import MoviesGrid from './components/MoviesGrid.vue'
import FavoriteLists from './components/FavoriteLists.vue'
import ListDetail from './components/ListDetail.vue'

const store = useMoviesStore()
const favoritesStore = useFavoritesStore()

onMounted(() => {
  if (!store.hasLoaded) {
    store.fetchMovies()
  }
})
</script>

<template>
  <main>
    <h1>MovieSearch</h1>

    <hr class="section-divider" />

    <ListDetail v-if="favoritesStore.selectedListId" />
    <FavoriteLists v-else />

    <hr class="section-divider" />

    <SearchInput />
    <MoviesGrid :movies="store.movies" :isLoading="store.isLoading" :error="store.error" />
  </main>
</template>

<style scoped>
.section-divider {
  border: none;
  border-top: 2px solid var(--accent);
  margin: 32px 0;
}
</style>
