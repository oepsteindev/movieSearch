# Part 2 Favorites, Multiple Lists, Persistence, and Tests

Build Part 2 as the extra-credit extension of the same as described in part1.md.
Keep the frontend minimal and straightforward, but implement the favorites/list functionality cleanly with API-backed persistence and JavaScript-driven interactions only.

## Goal

Extend the search app so users can:
- Create multiple named favorite lists.
- Save any number of search results into one or more lists.
- Ability to view all lists.
- Open a list to view its favorites.
- Ability to Delete lists.
- Ability to Delete favorites from a list.
- See item counts per list next to name of list in ().
- Persist all data through the symfony backend via new entities and repos.

## Architecture expectations

Continue using:
- Backend: Symfony.
- Frontend: Vue 3 + Vite.
- State: Pinia.
- Persistence: API-backed storage, in MySQL.


## Core functional requirements

Implement the following:

1. Multiple favorite lists
- Users can create multiple lists.
- Each list has a unique name.
- If the chosen name already exists, show a useful validation error and require a new name.

2. Add favorites to one or more lists
- A user can favorite any number of search results.
- A single search result can be saved to more than one list.
- Each result should have small text underneath that says `Add to Favorites`
- The add-to-list interaction should be handled without reloading the page.

3. Lists index view
- Provide a view showing all saved lists.
- Lists must be displayed in alphabetical order.
- Each list should show its total number of stored favorites.
- Each list should have a delete action.

4. List detail view
- Clicking a list should display the favorites inside it, but reuse the same grid as to not make unnecessary components.
- Favorites in a list should be sorted by modified date.
- Each favorite should be removable from the list. via small text underneath saying (remove from list).

5. Persistence
- Persist lists and favorites using Symfony Movie entity.
- All create/read/delete operations should happen through Symfony API endpoints.
- All UI actions should be handled in JavaScript without page reloads.

## Data model expectations

Keep the schema simple and explicit.

A clean approach would be:
- `favorite_lists`
  - `id`
  - `name` (unique)
  - timestamps
- `favorites`
  - `id`
  - external movie identifier
  - movie name
  - image URL 
  - any small metadata snapshot worth preserving
  - timestamps
- join table, if treating favorites as reusable entities across lists
  - `favorite_list_id`
  - `favorite_id`
  - modified date for sorting logic

An equally valid approach is storing saved items directly per list entry if that makes the implementation simpler. Choose the model that keeps behavior clear and easy to test.

## API expectations

Likely backend endpoints:
- `GET /api/lists`
- `POST /api/lists`
- `DELETE /api/lists/{id}`
- `GET /api/lists/{id}`
- `POST /api/lists/{id}/favorites`
- `DELETE /api/lists/{listId}/favorites/{favoriteId}`

Expectations for the API:
- Return clean JSON responses via new or existing DTO if compatible.
- Validate duplicate list names.
- Return sorted lists alphabetically.
- Return favorites sorted by modified date.
- Return item counts so the frontend does not have to calculate everything manually from incomplete payloads.

## Frontend expectations

Keep the UI minimal but functional:
- A search/results view from Part 1.
- A simple lists view or side panel.
- A list detail view for favorites in the selected list.
- Compact form for creating a list.
- Simple (add to favorites) action from result cards

Do not overcomplicate the frontend architecture. the smallest number of Vue components plus one or two Pinia stores is enough.

## Pinia expectations

Suggested state split:
- Search/movies store for Part 1 concerns.
- Lists/favorites store for Part 2 concerns.

The lists/favorites store likely owns:
- `lists`
- `selectedList`
- `selectedListItems`
- `isLoadingLists`
- `isSaving`
- `error`

Likely actions:
- `fetchLists()`
- `createList(name)`
- `deleteList(id)`
- `selectList(id)` / `fetchList(id)`
- `addItemToLists(item, listIds)`
- `removeFavorite(listId, favoriteId)`

## UX expectations

- Duplicate-name errors should be clear, you can use Toast msgs or just simple output.
- Deleting a list should update the UI instantly after success with a success msg.
- Deleting a favorite should update the list detail view without reloadand a status msg.
- Counts should stay in sync.
- Empty states should be present for no lists and empty lists.

## Backend quality expectations

Use straightforward Symfony practices:
- Doctrine entities and migrations
- Validation constraints for unique list names.
- Repository queries or service methods that enforce required sorting.
- API controllers that stay thin, use a Favorites service and delegate work.
- Consistent JSON response format.

## Fixtures for data
- Use insturctions from fixtures.md


