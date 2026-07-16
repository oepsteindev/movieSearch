<script setup>
import { computed } from 'vue'
import { useFavoritesStore } from '../stores/favorites'
import MoviesGrid from './MoviesGrid.vue'

const favoritesStore = useFavoritesStore()

const listName = computed(() => {
  const list = favoritesStore.lists.find((l) => l.id === favoritesStore.selectedListId)
  return list ? list.name : ''
})

function goBack() {
  favoritesStore.selectedListId = null
  favoritesStore.selectedListItems = []
}

function removeFavorite(itemId) {
  favoritesStore.removeFavorite(favoritesStore.selectedListId, itemId)
}
</script>

<template>
  <section class="list-detail">
    <button type="button" class="list-detail__back" @click="goBack">← Back to lists</button>
    <h2>{{ listName }}</h2>

    <MoviesGrid
      :movies="favoritesStore.selectedListItems"
      :isLoading="favoritesStore.isLoadingLists"
      mode="list-item"
      @remove-from-list="removeFavorite"
    />
  </section>
</template>

<style scoped>
.list-detail__back {
  font: inherit;
  font-size: 14px;
  color: var(--accent);
  background: none;
  border: none;
  padding: 0;
  margin-bottom: 12px;
  cursor: pointer;
}
</style>
