import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import FavoriteLists from '../FavoriteLists.vue'

function jsonResponse(body, status = 200) {
  return Promise.resolve({ ok: status < 300, status, json: () => Promise.resolve(body) })
}

function noContentResponse() {
  return Promise.resolve({ ok: true, status: 204, json: () => Promise.resolve(null) })
}

describe('FavoriteLists', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders lists with their item counts', async () => {
    global.fetch = vi.fn(() => jsonResponse({
      lists: [
        { id: 1, name: 'Action', itemCount: 3 },
        { id: 2, name: 'Comedy', itemCount: 0 },
      ],
    }))

    const wrapper = mount(FavoriteLists)
    await flushPromises()

    expect(wrapper.text()).toContain('Action (3)')
    expect(wrapper.text()).toContain('Comedy (0)')
  })

  it('shows an empty state when there are no lists', async () => {
    global.fetch = vi.fn(() => jsonResponse({ lists: [] }))

    const wrapper = mount(FavoriteLists)
    await flushPromises()

    expect(wrapper.text()).toContain('No lists yet.')
  })

  it('creates a list and shows a success status', async () => {
    global.fetch = vi.fn()
      .mockImplementationOnce(() => jsonResponse({ lists: [] }))
      .mockImplementationOnce(() => jsonResponse({ list: { id: 5, name: 'New List', itemCount: 0 } }, 201))
      .mockImplementationOnce(() => jsonResponse({ lists: [{ id: 5, name: 'New List', itemCount: 0 }] }))

    const wrapper = mount(FavoriteLists)
    await flushPromises()

    await wrapper.find('#new-list-name').setValue('New List')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('List created.')
    expect(wrapper.text()).toContain('New List (0)')
  })

  it('shows the duplicate-name error from the API', async () => {
    global.fetch = vi.fn()
      .mockImplementationOnce(() => jsonResponse({ lists: [] }))
      .mockImplementationOnce(() => jsonResponse({ error: 'A list named "Dup" already exists.' }, 409))

    const wrapper = mount(FavoriteLists)
    await flushPromises()

    await wrapper.find('#new-list-name').setValue('Dup')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('A list named "Dup" already exists.')
  })

  it('deletes a list', async () => {
    global.fetch = vi.fn()
      .mockImplementationOnce(() => jsonResponse({ lists: [{ id: 1, name: 'To Delete', itemCount: 0 }] }))
      .mockImplementationOnce(() => noContentResponse())
      .mockImplementationOnce(() => jsonResponse({ lists: [] }))

    const wrapper = mount(FavoriteLists)
    await flushPromises()

    await wrapper.find('.favorite-lists__delete').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('No lists yet.')
  })
})
