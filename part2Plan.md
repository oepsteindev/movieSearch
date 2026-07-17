# Part 2 Implementation Plan — Favorites, Multiple Lists, Persistence

## Context

Part 1 (search) is complete and verified. Part 2 (`part2.md`) is the extra-credit extension: users can save search results into one or more named favorite lists, view/open/delete lists, and remove favorites from a list — all persisted via a new Symfony/Doctrine layer and driven from the frontend without page reloads. `fixtures.md` additionally specifies a "fake auth" layer (a `User` entity plus a way to resolve one demo user) so the data model is user-scoped without building real login. Note: unlike `part1.md`/`part2.md`, `fixtures.md` is self-authored (not part of the original take-home spec) — it exists solely to give `FavoriteList` an owner, and was simplified during implementation (see "Current-user lookup" below).

Three ambiguities were resolved with the user before this plan:
1. **Add-to-Favorites UX** — an inline panel that expands directly under a `MovieCard` when its "Add to Favorites" text is clicked, listing existing lists as checkboxes plus a tiny "new list" input. No modal, no new UI pattern.
2. **Favorite schema** — follow `fixtures.md` literally: `FavoriteItem` belongs to exactly one `FavoriteList` (one-to-many). Saving a result to N lists creates N independent `FavoriteItem` rows, each an independent snapshot of the movie. No shared/dedup entity, no join table.
3. **List name uniqueness** — enforced per owner (`UNIQUE(owner_id, name)`), not globally, matching `fixtures.md`'s user-scoped model.

Confirmed from reading the current codebase:
- `doctrine/doctrine-bundle`, `doctrine/orm`, `doctrine/doctrine-migrations-bundle`, and `symfony/validator` are already in `backend/composer.json`; `src/Entity`, `src/Repository`, and `migrations/` exist but are empty — this is a clean slate.
- `config/packages/doctrine.yaml` already maps `src/Entity` via PHP attributes with `naming_strategy: underscore_number_aware` (so `FavoriteList`/`favoriteListId` map to `favorite_list`/`favorite_list_id` automatically).
- No `doctrine/doctrine-fixtures-bundle` yet — needed as a new dev dependency since `fixtures.md` explicitly requires `AppFixtures` (justified, same bar as adding Vitest for Part 1's required frontend tests).
- No `symfony/maker-bundle` — entities are hand-written (matches this project's style) and migrations generated via the already-installed `doctrine:migrations:diff` command, not `make:migration`.
- `MovieSearchController`'s `jsonWithCors()` pattern and `ExternalMovieSearchException`-style custom exceptions are the established conventions to reuse.
- Frontend: `movies.js` (options-store Pinia), `MovieCard.vue`/`MoviesGrid.vue`/`SearchInput.vue`, and CSS variables (`--text`, `--text-h`, `--bg`, `--border`, `--accent`, `--code-bg`) in `style.css` are the established conventions to reuse. No router library is installed — Part 2 stays single-page with plain `v-if` view toggling (no new dependency).

## Backend

### Entities (`backend/src/Entity/`)

- **`User.php`** — minimal, per `fixtures.md`: `id`, `email` (unique, length 180), `roles` (array), `password` (placeholder string, never checked — no real auth). `#[ORM\Table(name: 'users')]` explicitly, since `user` is a reserved word in MySQL. No `OneToMany` collection to `FavoriteList` — it's never navigated in code (all list lookups go through `FavoriteListRepository`), so mapping it would be unused dead weight.
- **`FavoriteList.php`** — `id`, `name`, `owner` (many-to-one `User`, required/non-nullable), `createdAt`. Table-level `#[ORM\UniqueConstraint(name: 'uniq_owner_name', columns: ['owner_id', 'name'])]` as a DB-level backstop behind the app-level check. No `items` collection for the same reason as above — items are always queried via `FavoriteItemRepository`.
- **`FavoriteItem.php`** — `id`, `externalId` (the OMDb `imdbID`), `name`, `image`, `year` (nullable int), `favoriteList` (many-to-one, required, `#[ORM\JoinColumn(onDelete: 'CASCADE')]` so deleting a list cleanly deletes its items at the DB level), `createdAt`. No `updatedAt`/edit action exists in this spec, so "modified date" sorting uses `createdAt` — documented once in the entity, not re-explained elsewhere.

### Repositories (`backend/src/Repository/`)

- **`UserRepository.php`** — standard `ServiceEntityRepository`, plus `findByEmail(string $email): ?User`.
- **`FavoriteListRepository.php`** — `findByOwner(User $owner): array` (ordered alphabetically by `name`, matches "lists index" requirement), `findOneByIdAndOwner(int $id, User $owner): ?FavoriteList`, `nameExistsForOwner(User $owner, string $name): bool`.
- **`FavoriteItemRepository.php`** — `findByListOrderedByModified(FavoriteList $list): array` (ordered by `createdAt` DESC — most recently added first). Explicit repository method rather than an entity-level `#[ORM\OrderBy]` annotation, so the sort is a visible, testable query rather than mapping magic.

### Current-user lookup

`fixtures.md` was self-authored (not part of the original take-home spec) purely to give `FavoriteList` an owner. An earlier draft of this plan called for a standalone `CurrentUserProvider` service (plus a dedicated `CurrentUserNotFoundException`), but since there's only one consumer (`FavoritesService`) and only one user, both were unnecessary ceremony and were removed during implementation. The final design: a private `getCurrentUser(): User` method directly on `FavoritesService` — hardcodes the lookup email (`demo@example.com`) as a class constant, injects `UserRepository`, and throws a plain `\RuntimeException` if the demo user isn't seeded (no dedicated exception class — nothing catches this by type, unlike the duplicate-name/not-found cases below that controllers must translate into specific HTTP statuses). Every list/favorite operation calls this one method — never a request-supplied user id. If real auth is ever added, this is still the one place that changes.

### Fixtures (`backend/src/DataFixtures/AppFixtures.php`)

New dev dependency: `doctrine/doctrine-fixtures-bundle`. `AppFixtures extends Fixture`: creates the demo `User` (`demo@example.com`, `ROLE_USER`, placeholder password string), one sample `FavoriteList` ("My Favorites"), two sample `FavoriteItem`s in it. Loaded manually via `doctrine:fixtures:load` (documented command, not run automatically on every boot — auto-loading would either duplicate or wipe data the developer creates through the UI).

### DTOs (`backend/src/Dto/`, same `JsonSerializable` pattern as `MovieDto`)

- **`FavoriteItemDto`**: `id`, `externalId`, `name`, `image`, `year`, `addedAt` (ISO 8601 string).
- **`FavoriteListDto`**: `id`, `name`, `itemCount` — used for the lists index so the frontend never has to compute counts itself.
- **`FavoriteListDetailDto`**: `id`, `name`, `items: FavoriteItemDto[]` — used for the single-list view.

### Exceptions (`backend/src/Service/Exception/`)

- `DuplicateListNameException` — thrown by the service before insert; message includes the offending name so the controller can pass it straight through.
- `ListNotFoundException` — thrown when a list id doesn't exist or isn't owned by the current user (same 404 either way — never leak whether a list exists for another user).

### Service layer (`backend/src/Service/FavoritesService.php`)

Thin controllers, one service, per `part2.md`'s explicit ask. Constructor: `EntityManagerInterface`, `FavoriteListRepository`, `FavoriteItemRepository`, `UserRepository`.

- `listLists(): FavoriteListDto[]` — current user's lists, alphabetical, with counts.
- `createList(string $name): FavoriteListDto` — trims name, checks `nameExistsForOwner()`, throws `DuplicateListNameException` if a duplicate; otherwise persists and returns the DTO.
- `deleteList(int $id): void` — resolve via `findOneByIdAndOwner`, throw `ListNotFoundException` if missing, else remove (cascades to items at the DB level).
- `getListDetail(int $id): FavoriteListDetailDto` — resolve list (ownership-checked), items via `findByListOrderedByModified`.
- `addItemToList(int $listId, string $externalId, string $name, string $image, ?int $year): FavoriteItemDto` — resolves the list (ownership-checked), creates one `FavoriteItem` row, persists, returns its DTO. (Adding "to one or more lists" is a frontend concern: the store calls this once per selected list — no batch endpoint, matching the exact endpoint list in `part2.md`.)
- `removeItem(int $listId, int $itemId): void` — resolve list (ownership-checked), resolve item scoped to that list, remove.

### Controllers (`backend/src/Controller/Api/FavoriteListController.php`)

Same attribute-routing/CORS conventions as `MovieSearchController`. Extract the duplicated `jsonWithCors()` helper into a small shared `CorsJsonResponseTrait` (used by both controllers) — the only cross-cutting DRY change to existing Part 1 code.

- `GET /api/lists` → 200, `{lists: FavoriteListDto[]}`
- `POST /api/lists` (body `{name}`) → 201 on success; catch `DuplicateListNameException` → 409 `{error: "..."}`
- `GET /api/lists/{id}` → 200, `{list: FavoriteListDetailDto}`; catch `ListNotFoundException` → 404
- `DELETE /api/lists/{id}` → 204; catch `ListNotFoundException` → 404
- `POST /api/lists/{id}/favorites` (body `{externalId, name, image, year}`) → 201, `{item: FavoriteItemDto}`; 404 if list not found
- `DELETE /api/lists/{listId}/favorites/{favoriteId}` → 204; 404 if list or item not found

### Migrations

After entities are written: `docker compose exec backend php bin/console doctrine:migrations:diff`, review the generated file, then `doctrine:migrations:migrate`. `docker/backend/docker-entrypoint.sh` runs `doctrine:migrations:migrate --no-interaction` automatically on every boot (idempotent, keeps the "just run `docker compose up`" experience the README already promises) — this also resolves Part 1's README caveat about migrations never running.

### Backend tests

- `FavoritesServiceTest` (real ORM against the test DB): duplicate name rejected, alphabetical list ordering, item count correctness, cross-user isolation (a list owned by a different user is never returned/matched by `findOneByIdAndOwner`), items sorted by `createdAt` DESC, and `getCurrentUser()` throws when the demo user isn't seeded.
- `FavoriteListControllerTest` (`WebTestCase`, same style as `MovieSearchControllerTest`): full CRUD happy path, duplicate-name 409, not-found 404s, CORS header present on all responses.
- Test isolation: since these tests hit the real (test) database rather than a mock, add a small shared base (`ApiTestCase extends WebTestCase`) that begins a Doctrine transaction in `setUp()` and rolls it back in `tearDown()` — no new dependency, a few lines, keeps tests from polluting each other or requiring fixture reloads between runs.

## Frontend

### New Pinia store (`frontend/src/stores/favorites.js`)

Options-style, mirrors `movies.js`. State: `lists`, `selectedListId`, `selectedListItems`, `isLoadingLists`, `isSaving`, `error`. Actions: `fetchLists()`, `createList(name)` (surfaces the 409 error message from the API), `deleteList(id)`, `selectList(id)` (sets `selectedListId`, calls `fetchList`), `fetchList(id)`, `addItemToLists(item, listIds)` (loops a `POST` per id, then re-fetches `lists` so counts stay in sync), `removeFavorite(listId, itemId)` (`DELETE`, then refreshes `selectedListItems` and `lists` counts).

### Components

- **`MovieCard.vue`** (extended, not replaced): new prop `mode: 'search' | 'list-item'` (default `'search'`).
  - `mode === 'search'`: renders "Add to Favorites" text; clicking expands an inline panel (reads `useFavoritesStore()` directly — an intentional, spec-driven exception to props-only components, since wrapping this in a separate component would itself be the "unnecessary component" `part2.md` says to avoid) with a checkbox per existing list plus a compact new-list input; submitting calls `favoritesStore.addItemToLists(movie, selectedListIds)`.
  - `mode === 'list-item'`: renders "(remove from list)" text instead, emitting `remove-from-list` with the item's id — actual removal is handled one level up (`ListDetail.vue` calling the store), keeping `MovieCard` free of list-id context it doesn't otherwise have.
- **`MoviesGrid.vue`** (extended): new `mode` prop, passed through to each `MovieCard`; re-emits `remove-from-list`. This is the "reuse the same grid" component `part2.md` asks for — used for both search results and list-detail favorites.
- **`FavoriteLists.vue`** (new): lists index — compact create-list form at top (with inline duplicate-name error), then each list as `name (count)` + "Delete" + click-to-select, alphabetical (server-sorted, no client re-sort needed), empty state "No lists yet."
- **`ListDetail.vue`** (new): shown once a list is selected — list name + "back to lists," renders `<MoviesGrid :movies="favoritesStore.selectedListItems" mode="list-item" @remove-from-list="...">`, empty state "No favorites in this list yet."
- **`App.vue`**: adds a "My Lists" section below the existing search section, toggling between `FavoriteLists` and `ListDetail` based on `favoritesStore.selectedListId` — plain `v-if`, no router.

### UX details

- Status messages (list created, list deleted, favorite added/removed, duplicate-name error) are local component state cleared with `setTimeout`, same lightweight pattern as `SearchInput.vue`'s debounce — no toast library.
- Styling reuses existing CSS variables (`--text`, `--text-h`, `--border`, `--accent`, `--code-bg`) already defined in `style.css`; small additions only (e.g. `.favorite-lists__item`, `.add-to-favorites` panel), no new global rules needed.

### Frontend tests

No new devDependencies (Vitest/`@vue/test-utils`/jsdom already installed). New specs: `FavoriteLists.spec.js` (renders sorted lists with counts, create-list success/duplicate-error, delete), `ListDetail.spec.js` (renders items, remove-from-list emits/updates), and extend `MovieCard.spec.js` for both new `mode` branches.

## Documentation

Replace the Part 2 placeholder in `README.md` with: new API endpoints, the fixtures-loading command (`docker compose exec backend php bin/console doctrine:fixtures:load`), and an updated Known Limitations note (the "no migrations run" caveat is resolved — migrations now run automatically on boot).

## Build order / progress

1. ✅ Backend entities (`User`, `FavoriteList`, `FavoriteItem`) → repositories → migration (`diff` + review + `migrate`) → wire auto-migrate into the entrypoint.
2. ✅ `doctrine/doctrine-fixtures-bundle` → `AppFixtures` → load fixtures, verify demo data via direct DB query.
3. ✅ DTOs → exceptions → `FavoritesService` (+ `FavoritesServiceTest`).
4. ✅ `CorsJsonResponseTrait` (refactor `MovieSearchController` to use it) → `FavoriteListController` (+ `FavoriteListControllerTest`, incl. the transaction-rollback `ApiTestCase` base).
5. ✅ Manual smoke test of all 6 endpoints via curl/docker exec against the real test setup.
6. ✅ Frontend: `stores/favorites.js` → extend `MovieCard.vue`/`MoviesGrid.vue` → `FavoriteLists.vue` → `ListDetail.vue` → wire into `App.vue`.
7. ✅ Frontend component tests.
8. ✅ README Part 2 section.

## Verification

- `docker compose exec backend php bin/phpunit` — all backend tests (Part 1 + new Part 2) pass.
- `docker compose exec frontend npm test` — all frontend tests pass.
- Manual: create two lists (second with a duplicate name → clear error), favorite a search result into both at once, open each list and confirm the item and count, remove a favorite and confirm the count/grid update live, delete a list and confirm it disappears — all without a page reload.

## Critical files

- `backend/src/Entity/{User,FavoriteList,FavoriteItem}.php`
- `backend/src/Service/FavoritesService.php`
- `backend/src/Controller/Api/FavoriteListController.php`
- `backend/src/DataFixtures/AppFixtures.php`
- `frontend/src/stores/favorites.js`
- `frontend/src/components/{MovieCard,MoviesGrid,FavoriteLists,ListDetail}.vue`
