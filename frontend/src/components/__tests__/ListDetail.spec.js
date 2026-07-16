import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useFavoritesStore } from '../../stores/favorites'
import ListDetail from '../ListDetail.vue'

function jsonResponse(body) {
  return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve(body) })
}

function noContentResponse() {
  return Promise.resolve({ ok: true, status: 204, json: () => Promise.resolve(null) })
}

describe('ListDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders the list name and its items', () => {
    const store = useFavoritesStore()
    store.lists = [{ id: 1, name: 'Action', itemCount: 1 }]
    store.selectedListId = 1
    store.selectedListItems = [
      { id: 10, externalId: 'tt1', name: 'The Matrix', image: 'https://example.com/1.jpg', year: 1999 },
    ]

    const wrapper = mount(ListDetail)

    expect(wrapper.text()).toContain('Action')
    expect(wrapper.text()).toContain('The Matrix (1999)')
  })

  it('shows the empty state when the list has no favorites', () => {
    const store = useFavoritesStore()
    store.lists = [{ id: 1, name: 'Action', itemCount: 0 }]
    store.selectedListId = 1
    store.selectedListItems = []

    const wrapper = mount(ListDetail)

    expect(wrapper.text()).toContain('No favorites in this list yet.')
  })

  it('removes a favorite and refreshes the list from the API', async () => {
    const store = useFavoritesStore()
    store.lists = [{ id: 1, name: 'Action', itemCount: 1 }]
    store.selectedListId = 1
    store.selectedListItems = [
      { id: 10, externalId: 'tt1', name: 'The Matrix', image: 'https://example.com/1.jpg', year: 1999 },
    ]

    global.fetch = vi.fn()
      .mockImplementationOnce(() => noContentResponse())
      .mockImplementationOnce(() => jsonResponse({ list: { id: 1, name: 'Action', items: [] } }))
      .mockImplementationOnce(() => jsonResponse({ lists: [{ id: 1, name: 'Action', itemCount: 0 }] }))

    const wrapper = mount(ListDetail)

    await wrapper.find('.movie-card__action').trigger('click')
    await flushPromises()

    expect(store.selectedListItems).toEqual([])
  })

  it('going back clears the selected list', async () => {
    const store = useFavoritesStore()
    store.lists = [{ id: 1, name: 'Action', itemCount: 0 }]
    store.selectedListId = 1
    store.selectedListItems = []

    const wrapper = mount(ListDetail)

    await wrapper.find('.list-detail__back').trigger('click')

    expect(store.selectedListId).toBe(null)
  })
})
