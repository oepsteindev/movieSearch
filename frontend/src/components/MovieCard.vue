<script setup>
import { ref, computed } from 'vue'
import { useFavoritesStore } from '../stores/favorites'

const props = defineProps({
  movie: {
    type: Object,
    required: true,
  },
  // 'search': shows "Add to Favorites" + the list-picker panel.
  // 'list-item': shows "(remove from list)" instead (used inside a list's detail view).
  mode: {
    type: String,
    default: 'search',
  },
})

// OMDb occasionally returns a syntactically valid poster URL that's actually
// a dead link (404 on Amazon's CDN). We can't detect that server-side without
// a live request per candidate, so the parent grid drops the card once the
// browser itself fails to load the image.
const emit = defineEmits(['image-error', 'remove-from-list'])

// Reads the store directly instead of taking props for this — wrapping the
// panel in its own component would just be the "unnecessary component"
// part2.md says to avoid, since MovieCard is already the single place that
// needs to know about favoriting.
const favoritesStore = useFavoritesStore()

const isPanelOpen = ref(false)
const selectedListIds = ref([])
const newListName = ref('')
const statusMessage = ref(null)

const canSubmit = computed(() => selectedListIds.value.length > 0 || newListName.value.trim() !== '')

function togglePanel() {
  isPanelOpen.value = !isPanelOpen.value

  if (isPanelOpen.value && favoritesStore.lists.length === 0) {
    favoritesStore.fetchLists()
  }
}

function showStatus(message) {
  statusMessage.value = message
  setTimeout(() => {
    statusMessage.value = null
  }, 3000)
}

async function submit() {
  const listIds = [...selectedListIds.value]
  const trimmedName = newListName.value.trim()

  if (trimmedName) {
    const newList = await favoritesStore.createList(trimmedName)

    if (!newList) {
      showStatus(favoritesStore.error)
      return
    }

    listIds.push(newList.id)
  }

  await favoritesStore.addItemToLists(props.movie, listIds)

  if (favoritesStore.error) {
    showStatus(favoritesStore.error)
    return
  }

  selectedListIds.value = []
  newListName.value = ''
  isPanelOpen.value = false
  showStatus('Added to favorites.')
}
</script>

<template>
  <article class="movie-card">
    <!-- The backend already drops posterless entries; this guard is just
         defense-in-depth against a malformed payload. -->
    <img
      v-if="movie.image"
      :src="movie.image"
      :alt="movie.name"
      loading="lazy"
      @error="emit('image-error', movie.id)"
    />
    <p class="movie-card__title">
      {{ movie.name }}
      <span v-if="movie.year">({{ movie.year }})</span>
    </p>

    <button v-if="mode === 'search'" type="button" class="movie-card__action" @click="togglePanel">
      Add to Favorites
    </button>
    <button v-else type="button" class="movie-card__action" @click="emit('remove-from-list', movie.id)">
      (remove from list)
    </button>

    <div v-if="mode === 'search' && isPanelOpen" class="movie-card__panel">
      <p v-if="favoritesStore.isLoadingLists" class="movie-card__panel-status">Loading lists…</p>
      <p v-else-if="favoritesStore.lists.length === 0" class="movie-card__panel-status">No lists yet — create one below.</p>
      <label v-for="list in favoritesStore.lists" :key="list.id" class="movie-card__panel-option">
        <input v-model="selectedListIds" type="checkbox" :value="list.id" />
        {{ list.name }}
      </label>

      <input
        v-model="newListName"
        type="text"
        placeholder="New list name"
        class="movie-card__panel-input"
      />

      <button type="button" :disabled="!canSubmit || favoritesStore.isSaving" @click="submit">
        {{ favoritesStore.isSaving ? 'Saving…' : 'Save' }}
      </button>

      <p v-if="statusMessage" class="movie-card__panel-status">{{ statusMessage }}</p>
    </div>
  </article>
</template>

<style scoped>
.movie-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.movie-card img {
  width: 100%;
  aspect-ratio: 2 / 3;
  object-fit: cover;
  border-radius: 6px;
  background: var(--code-bg);
}

.movie-card__title {
  font-size: 15px;
  line-height: 130%;
}

.movie-card__action {
  align-self: flex-start;
  font: inherit;
  font-size: 13px;
  color: var(--accent);
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}

.movie-card__panel {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--code-bg);
}

.movie-card__panel-option {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.movie-card__panel-input {
  font: inherit;
  font-size: 14px;
  padding: 6px 8px;
  border-radius: 4px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text-h);
}

.movie-card__panel-status {
  font-size: 13px;
  color: var(--text);
  margin: 0;
}
</style>
