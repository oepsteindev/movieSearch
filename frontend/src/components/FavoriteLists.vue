<script setup>
import { ref, onMounted } from 'vue'
import { useFavoritesStore } from '../stores/favorites'

const favoritesStore = useFavoritesStore()

const newListName = ref('')
const statusMessage = ref(null)

onMounted(() => {
  favoritesStore.fetchLists()
})

function showStatus(message) {
  statusMessage.value = message
  setTimeout(() => {
    statusMessage.value = null
  }, 3000)
}

async function createList() {
  const name = newListName.value.trim()

  if (!name) {
    return
  }

  const list = await favoritesStore.createList(name)

  if (!list) {
    showStatus(favoritesStore.error)
    return
  }

  newListName.value = ''
  showStatus('List created.')
}

async function deleteList(id) {
  await favoritesStore.deleteList(id)
  showStatus(favoritesStore.error || 'List deleted.')
}

function selectList(id) {
  favoritesStore.selectList(id)
}
</script>

<template>
  <section class="favorite-lists">
    <h2>My Lists</h2>

    <form class="favorite-lists__form" @submit.prevent="createList">
      <label for="new-list-name">New list name</label>
      <input id="new-list-name" v-model="newListName" type="text" autocomplete="off" />
      <button type="submit" :disabled="favoritesStore.isSaving">Create list</button>
    </form>

    <p v-if="statusMessage" class="favorite-lists__status">{{ statusMessage }}</p>

    <p v-if="favoritesStore.isLoadingLists" class="favorite-lists__status">Loading lists…</p>
    <p v-else-if="favoritesStore.lists.length === 0" class="favorite-lists__status">No lists yet.</p>
    <ul v-else class="favorite-lists__list">
      <li v-for="list in favoritesStore.lists" :key="list.id" class="favorite-lists__item">
        <button type="button" class="favorite-lists__name" @click="selectList(list.id)">
          {{ list.name }} ({{ list.itemCount }})
        </button>
        <button type="button" class="favorite-lists__delete" @click="deleteList(list.id)">Delete</button>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.favorite-lists__form {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.favorite-lists__form label {
  font-size: 14px;
  color: var(--text);
}

.favorite-lists__form input {
  font: inherit;
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text-h);
}

.favorite-lists__status {
  font-size: 14px;
  color: var(--text);
  margin-bottom: 12px;
}

.favorite-lists__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.favorite-lists__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 6px;
}

.favorite-lists__name {
  font: inherit;
  color: var(--text-h);
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: left;
}

.favorite-lists__delete {
  font: inherit;
  font-size: 13px;
  color: var(--accent);
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}
</style>
