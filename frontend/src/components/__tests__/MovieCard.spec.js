import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MovieCard from '../MovieCard.vue'

describe('MovieCard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders an image when the movie has one', () => {
    const wrapper = mount(MovieCard, {
      props: {
        movie: { id: 'tt1', name: 'The Matrix', image: 'https://example.com/1.jpg', year: 1999 },
      },
    })

    const img = wrapper.find('img')
    expect(img.exists()).toBe(true)
    expect(img.attributes('src')).toBe('https://example.com/1.jpg')
    expect(img.attributes('alt')).toBe('The Matrix')
  })

  it('omits the image entirely when the movie has none', () => {
    const wrapper = mount(MovieCard, {
      props: {
        movie: { id: 'tt2', name: 'No Poster Movie', image: null, year: null },
      },
    })

    expect(wrapper.find('img').exists()).toBe(false)
  })

  it('emits image-error with the movie id when the image fails to load', async () => {
    const wrapper = mount(MovieCard, {
      props: {
        movie: { id: 'tt3', name: 'Dead Link Movie', image: 'https://example.com/dead.jpg', year: null },
      },
    })

    await wrapper.find('img').trigger('error')

    expect(wrapper.emitted('image-error')).toEqual([['tt3']])
  })

  it('shows "Add to Favorites" in search mode (the default)', () => {
    const wrapper = mount(MovieCard, {
      props: {
        movie: { id: 'tt1', name: 'The Matrix', image: 'https://example.com/1.jpg', year: 1999 },
      },
    })

    expect(wrapper.text()).toContain('Add to Favorites')
    expect(wrapper.text()).not.toContain('remove from list')
  })

  it('shows a remove link and emits remove-from-list in list-item mode', async () => {
    const wrapper = mount(MovieCard, {
      props: {
        movie: { id: 10, name: 'The Matrix', image: 'https://example.com/1.jpg', year: 1999 },
        mode: 'list-item',
      },
    })

    expect(wrapper.text()).toContain('remove from list')
    expect(wrapper.text()).not.toContain('Add to Favorites')

    await wrapper.find('.movie-card__action').trigger('click')

    expect(wrapper.emitted('remove-from-list')).toEqual([[10]])
  })
})
