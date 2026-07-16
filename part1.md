# Part 1 Brief for Claude — Symfony API Integration + Minimal Vue Front End

Part 1 of this project:
 as a small full-stack app using the latest **Symfony** for the backend API
 The absolute simplest **Vue 3** frontend with **Vite** and a **Pinia** store. The UI should be responsive, work in all browsers, and handle all searching without page reloads.

## Goal

Create a searchable title/name browser where:
- The Symfony backend consumes an external API.
- Create a base url http://www.omdbapi.com/?i=tt3896198&apikey= and get the api key from our .env.
- The Vue frontend calls only the custom Symfony endpoint, not the external API.
- Results render in a responsive grid.
- Filtering updates automatically as the user types, require a 4 char minimum before calling the API for a title to limmit API calls.
- When the search box is empty, the UI shows an unfiltered initial result set, max 100 records.

## Architecture expectations

Use this stack and keep it intentionally simple:
- Backend: Symfony latest, API-style controller(s), HttpClient for API calls.
- Frontend: Vue 3 with Vite.
- State: one lightweight Pinia store for search term, results, loading state, and error state.
- Communication: Vue calls Symfony JSON endpoints via fetch, no axios.
- Styling: minimal CSS, responsive grid, accessible form controls.
- No unnecessary abstractions, no overengineering, no complex component library.

## External API choice with Marvel and LOTR unavailble or not serving immages
http://www.omdbapi.com/?i=tt3896198&apikey=

- If the API does not provide images, omit images cleanly instead of faking them.

## Functional requirements

Implement the following:

1. Search input
- A single search field at the top of the page.
- Results should update automatically as the user types.
- Do not require a search button or Enter key.
- Use a small debounce to avoid excessive requests.

2. Initial state
- When no search term is present, fetch and display an unfiltered result set from the backend once. Before you implement, talk to me about caching the list to limit api hits
- Reuse the same grid layout for everything.

3. Backend integration
- Symfony should expose a custom endpoint `/api/movies` for method `GET`.
- Accept a query parameter for the search term `?search=`.
- Symfony should call the external API, normalize the response, and return a clean JSON payload for the frontend using a DTO, so the frontend can rely on a stable shape, use: `id`, `name`, `image`, and optional metadata.
- Cap returned results at 100 items.

4. Frontend rendering
- Display results in a responsive grid below the search field.
- Each item/movie must show the movie name.
- Show an image only if one exists in the normalized API response, if there is no image omit the result.
- Additional metadata is fine if it helps users distinguish movies, like the year in (), but keep the UI simple.

5. JavaScript behavior
- All result updates must happen client-side without full page reloads.
- Loading and error states should be handled gracefully.
- Use the same results grid for both initial load and active search.

## Suggested implementation shape

Use a straightforward structure like this:

### Backend
- `src/Controller/Api/MovieSearchController.php`
- `src/Service/ExternalMovieSearchService.php`
- `src/MoviesDto`

### Frontend
- `src/main.js`
- `src/App.vue`
- `src/stores/movies.js`
- `src/components/SearchInput.vue`
- `src/components/MoviesGrid.vue`
- `src/components/MovieCard.vue`

Keep the component tree shallow.

## Pinia store expectations

The store should probably own:
- `searchTerm`
- `movies`
- `isLoading`
- `error`
- `hasLoaded`

The store should expose:
- `fetchMovies(searchTerm = '')`
- optional debounced search action, or debounce in the search component
- derived helpers such as movie count

## UX expectations

Keep the UI polished but minimal:
- Search input with clear label or accessible placeholder.
- Responsive card grid, 1 column on small screens, more columns on larger screens.
- Empty state when no results match.
- Loading indicator or skeleton.
- Friendly error state if the backend or upstream API fails.

## Backend expectations

The backend should demonstrate good Symfony habits:
- Environment variables for upstream API key or base URL if needed, kept in a .env file.
- HttpClient usage through a service, not directly in the controller.
- Clean JSON responses via DTO.
- Input validation for the search parameter.
- Defensive handling for missing fields in upstream responses.
- Reasonable timeout/error handling.

## Testing expectations

For Part 1, include tests such as:
- Symfony test for the API endpoint returning normalized JSON.
- Symfony test for the external API service, preferably mocking the upstream client.
- Frontend test for rendering search results

All tests should pass.

## Deliverable expectations

Produce code that feels interview-ready:
- Clear README notes for setup and running both backend and frontend.
- Sensible project structure.
- Small, understandable components.
- No unnecessary complexity.
- Clean separation between Symfony API consumption and Vue presentation.

## Important constraints

- Build this specifically in Symfony + Vue 3 + Pinia + Vite.
- Keep the Vue side as simple as possible.
- Use the backend as the only layer that talks to the external API.
- Do not add pagination; just limit display to 100 items.
- Design for responsiveness and cross-browser functionality.
- Do NOT over-engineer or add extra ayers of complexity