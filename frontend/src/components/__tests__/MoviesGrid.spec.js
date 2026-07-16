import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import MoviesGrid from '../MoviesGrid.vue'

const movies = [
  { id: 'tt1', name: 'Foo', image: 'https://example.com/1.jpg', year: 1999 },
  { id: 'tt2', name: 'Bar', image: 'https://example.com/2.jpg', year: null },
]

describe('MoviesGrid', () => {
  it('renders a card per movie', () => {
    const wrapper = mount(MoviesGrid, {
      props: { movies, isLoading: false, error: null },
    })

    expect(wrapper.findAll('.movie-card')).toHaveLength(2)
    expect(wrapper.text()).toContain('Foo (1999)')
    expect(wrapper.text()).toContain('Bar')
    expect(wrapper.text()).not.toContain('Bar (')
  })

  it('shows a loading state and no cards while loading', () => {
    const wrapper = mount(MoviesGrid, {
      props: { movies: [], isLoading: true, error: null },
    })

    expect(wrapper.text()).toContain('Loading')
    expect(wrapper.findAll('.movie-card')).toHaveLength(0)
  })

  it('shows the error message when one is present', () => {
    const wrapper = mount(MoviesGrid, {
      props: { movies: [], isLoading: false, error: 'Boom' },
    })

    expect(wrapper.text()).toContain('Boom')
  })

  it('shows an empty state when there are no movies', () => {
    const wrapper = mount(MoviesGrid, {
      props: { movies: [], isLoading: false, error: null },
    })

    expect(wrapper.text()).toContain('No movies found.')
  })

  it('drops a card entirely once its image fails to load', async () => {
    const wrapper = mount(MoviesGrid, {
      props: { movies, isLoading: false, error: null },
    })

    expect(wrapper.findAll('.movie-card')).toHaveLength(2)

    await wrapper.findAll('img')[0].trigger('error')

    expect(wrapper.findAll('.movie-card')).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Foo')
    expect(wrapper.text()).toContain('Bar')
  })
})
