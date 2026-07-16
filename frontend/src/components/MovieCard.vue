<script setup>
defineProps({
  movie: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <article class="movie-card">
    <!-- The backend already drops posterless entries; this guard is just
         defense-in-depth against a malformed payload. -->
    <img v-if="movie.image" :src="movie.image" :alt="movie.name" loading="lazy" />
    <p class="movie-card__title">
      {{ movie.name }}
      <span v-if="movie.year">({{ movie.year }})</span>
    </p>
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
</style>
