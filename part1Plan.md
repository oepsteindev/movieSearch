# Part 1 Implementation Plan — Symfony API + Vue 3/Pinia Frontend

## Context

`backend/` and `frontend/` are freshly scaffolded (Symfony 7.4 skeleton, Vue 3 + Vite) with no application code yet — `docker/`, `docker-compose.yml`, and the containers are already working. This plan covers building the Part 1 feature described in `part1.md`: a Symfony endpoint that queries the OMDb API and normalizes the response, and a minimal Vue/Pinia frontend that searches it live as the user types, showing an initial unfiltered grid when the search box is empty.

Three ambiguities in `part1.md` were resolved with the user before designing this plan:
1. **Initial (no-search-term) result set** — OMDb has no "browse all" endpoint, so the backend uses a fixed hardcoded seed term (`"movie"`) against OMDb's `s=` search, paginating to assemble up to 100 results.
2. **Caching** — the default/no-search-term list is cached via Symfony's built-in filesystem `cache.app` adapter (no new dependency), 1 hour TTL, keyed on the seed term. User searches are never cached.
3. **Missing images** — any movie without a real poster is dropped entirely from results (not shown without an image), on both the default list and user searches. Because dropping entries can shrink the set below the cap, fetching keeps paging (bounded by a page ceiling) to backfill toward 100.

A cross-cutting need not previously discussed: **CORS**. The Vue dev server (`:5173`) calls the Symfony server (`:8000`) directly via `fetch`, which is cross-origin. Since it's a public, read-only, credential-less `GET`, the plan sets `Access-Control-Allow-Origin: *` directly on the JSON responses rather than pulling in `nelmio/cors-bundle` — avoids a new dependency for one header.

## Backend

### Config
- `backend/.env`: add a placeholder `OMDB_API_KEY=` entry (mirrors the existing `DATABASE_URL` pattern) — purely defensive so config compiles outside docker-compose; the real key is already injected as a genuine env var by docker-compose and takes precedence.
- No new YAML config needed:
  - Bind the API key directly on the service constructor via `#[Autowire('%env(OMDB_API_KEY)%')]`.
  - OMDb's base URL is not secret/environment-specific — hardcode as a class constant, not a config parameter (YAGNI).
  - `CacheInterface` (aliased to `cache.app`, filesystem adapter by default) autowires with zero config changes.

### `backend/src/Dto/MovieDto.php`
- `final readonly class MovieDto implements \JsonSerializable`.
- Constructor: `string $id, string $name, string $image, ?int $year = null` — `image` is non-nullable because decision #3 means a DTO is only ever constructed for entries that have one.
- `jsonSerialize()` returns `['id', 'name', 'image', 'year']`. Using `JsonSerializable` means controllers can `json_encode` directly — no need to invoke the Serializer component for this simple shape, even though it's installed.

### `backend/src/Service/Exception/ExternalMovieSearchException.php`
- `final class ExternalMovieSearchException extends \RuntimeException {}` — lets the controller distinguish "upstream failed" from "validation failed" and answer with the right HTTP status.

### `backend/src/Service/ExternalMovieSearchService.php`
Constructor: `HttpClientInterface $httpClient, CacheInterface $cache, #[Autowire('%env(OMDB_API_KEY)%')] string $apiKey`.

Constants: `BASE_URL`, `SEED_TERM = 'movie'`, `DEFAULT_CACHE_KEY = 'movies_default_list'`, `CACHE_TTL_SECONDS = 3600`, `RESULT_CAP = 100`, `MAX_PAGES = 20`.

**`search(string $term): array`** (returns `MovieDto[]`):
- Trim the term.
- Empty → wrap in `$cache->get(DEFAULT_CACHE_KEY, fn (ItemInterface $item) => [expires in 3600s] fetchAndNormalize(SEED_TERM))`. If the callback throws, `CacheInterface::get()` does not cache the failure — a transient OMDb outage never gets stuck cached.
- Non-empty → `fetchAndNormalize($term)` directly, uncached.

**`fetchAndNormalize(string $term): array`** (pagination/backfill loop):
1. `$collected = []`, `$page = 1`.
2. While `count($collected) < RESULT_CAP` and `$page <= MAX_PAGES`:
   - Fetch one OMDb page (`s=$term&page=$page`).
   - If the response is a failure (`Response !== 'True'`) or a transport error occurs → stop.
   - Normalize each raw entry; keep only entries with a usable poster/title/id; append until cap is hit.
   - If the raw page had fewer than 10 entries, that was OMDb's last page → stop.
   - Otherwise increment page and continue (this is the backfill-toward-100 behavior).
3. Return the (possibly smaller) collected list.

**`fetchPage(string $term, int $page): ?array`**: calls OMDb with `apikey`, `s`, `page` query params, `timeout: 5`, `max_duration: 8`; decodes via `toArray()`; catches `Symfony\Contracts\HttpClient\Exception\ExceptionInterface` and rethrows as `ExternalMovieSearchException`; returns `null` on `Response !== 'True'`, else the raw `Search` array.

**`normalize(array $entry): ?MovieDto`**: drops entries with missing/`"N/A"` `Poster`, or missing `imdbID`/`Title`; parses `Year` (handles ranges like `"2014–2016"` by taking the leading 4 digits); returns a `MovieDto` or `null`.

### `backend/src/Controller/Api/MovieSearchController.php`
- `#[Route('/api/movies', name: 'api_movies_search', methods: ['GET'])]`.
- Read `search` query param, trim it.
- If non-empty and `mb_strlen < 4` → `400` `{"error": "Search term must be at least 4 characters."}` (manual `if`, not the Validator component — one trivial rule doesn't need constraint objects; also defense-in-depth mirroring the frontend's own gate).
- Call `ExternalMovieSearchService::search()`; catch `ExternalMovieSearchException` → `502` `{"error": "Unable to fetch movies right now. Please try again later."}`.
- Success → `200` `{"results": MovieDto[], "count": N}`.
- Set `Access-Control-Allow-Origin: *` on every response (success and error paths).
- No manual route file changes needed — `config/routes.yaml` already imports all `#[Route]`-attributed controllers.

### Backend tests
**`backend/tests/Service/ExternalMovieSearchServiceTest.php`** (plain `TestCase`, built with `MockHttpClient` + `ArrayAdapter` — the latter satisfies `CacheInterface` directly, zero new dependencies):
- Normalization drops no-image entries.
- Backfill pagination pulls from a second page when the first has dropped entries.
- Cap enforcement stops at exactly 100 and doesn't over-fetch.
- Upstream failure (`Response: "False"` or HTTP 500) throws `ExternalMovieSearchException`.
- Caching: `search('')` called twice hits the mock client once (cached); `search('batman')` called twice hits it twice (uncached).

**`backend/tests/Controller/MovieSearchControllerTest.php`** (`WebTestCase`, swaps `HttpClientInterface` in the test container with a `MockHttpClient` per test to avoid relying on the real filesystem cache across test runs):
- `?search=batman` with a mixed mock response → `200`, correct result count/shape, CORS header present.
- `?search=abc` (3 chars) → `400`, zero HTTP calls made (validation short-circuits).
- No `search` param → `200`, non-empty results (exercises the default/cache-write path once).
- Upstream 500 → `502`.

## Frontend

### `frontend/src/main.js`
Wire up the already-installed-but-unused `pinia` package: `createApp(App).use(createPinia()).mount('#app')`.

### `frontend/src/stores/movies.js`
Options-style `defineStore('movies', {...})` (maps directly onto the spec's explicit state/action list).
- State: `searchTerm: ''`, `movies: []`, `isLoading: false`, `error: null`, `hasLoaded: false`.
- Getter: `movieCount`.
- Action `fetchMovies(term = '')`: guards 1–3 character terms (no-op, defense-in-depth backstop — the primary gate lives in the component, see below); builds the URL from `import.meta.env.VITE_API_BASE_URL + '/api/movies'`; sets `isLoading`/`error`/`movies`/`hasLoaded` around the `fetch` call.

### Components (flat under `frontend/src/components/`)
- **`SearchInput.vue`**: `<script setup>` with a computed two-way binding to `store.searchTerm`; a `watch()` debounces (~400ms) and calls `store.fetchMovies(trimmed)` only when `trimmed.length === 0 || trimmed.length >= 4` — this is where the 4-char gate is actually enforced on the calling side. Accessible labeled `<input type="search">`, no submit button/Enter handling.
- **`MoviesGrid.vue`**: presentational, props-driven (`movies`, `isLoading`, `error`) rather than reading the store directly — reusable for both initial load and search, and testable without bootstrapping Pinia. Renders loading/error/empty states, else a `.movies-grid` of `MovieCard`s.
- **`MovieCard.vue`**: props `movie {id, name, image, year}`; `<img v-if="movie.image">` (defense-in-depth even though the backend already filters), name, and `(year)` if present.

### `frontend/src/App.vue`
`onMounted`: if `!store.hasLoaded`, call `store.fetchMovies()` once for the initial grid. Composes `<SearchInput />` + `<MoviesGrid :movies :isLoading :error>`.

### CSS (`frontend/src/style.css`)
Single responsive rule, no media queries needed:
```css
.movies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
```
Plus basic focus-visible styling on the input and `object-fit: cover` on card images.

Optional cleanup (not spec-required): remove the unused `HelloWorld.vue` and default Vite boilerplate assets.

### Frontend test tooling (new dependency, justified by part1.md's explicit "frontend test for rendering search results" requirement)
- Add devDependencies: `vitest`, `@vue/test-utils`, `jsdom`.
- Update `vite.config.js` to import `defineConfig` from `vitest/config` (drop-in re-export) and add `test: { environment: 'jsdom', globals: true }` — keeps one config file.
- Add `"test": "vitest run"` npm script.
- `frontend/src/components/__tests__/MoviesGrid.spec.js`: renders cards for valid movies, renders loading/error/empty states correctly.
- `frontend/src/components/__tests__/MovieCard.spec.js`: renders `<img>` when `image` is set, omits it when `null`.
- No test for the Pinia store or debounce timing — not required by the spec, would be scope creep for Part 1.

## Documentation
Append a short "Running tests" section to the existing root `README.md`:
```bash
docker compose exec backend php bin/phpunit
docker compose exec frontend npm test
```

## Suggested build order
1. Backend: `.env` placeholder → `MovieDto` → `ExternalMovieSearchException` → `ExternalMovieSearchService` (+ unit tests) → `MovieSearchController` (+ functional tests incl. CORS header).
2. Manually smoke-test `/api/movies` and `/api/movies?search=batman` against the real OMDb key via docker-compose.
3. Frontend: `main.js` Pinia wiring → `stores/movies.js` → `MovieCard.vue` → `MoviesGrid.vue` → `SearchInput.vue` → `App.vue` → CSS.
4. Frontend test tooling → component tests.
5. README "Running tests" section.

## Verification
- `docker compose exec backend php bin/phpunit` — all backend tests pass.
- `docker compose exec frontend npm test` — all frontend tests pass.
- Manual: open `http://localhost:5173`, confirm the initial 100-ish movie grid loads (seed term `"movie"`), type a 1–3 character term (no request fires), type a 4+ character title (grid updates without reload after the debounce), clear the field (grid reverts to the cached default list), and confirm every rendered card has an image (none are missing).

## Critical files
- `backend/src/Service/ExternalMovieSearchService.php`
- `backend/src/Controller/Api/MovieSearchController.php`
- `backend/src/Dto/MovieDto.php`
- `frontend/src/stores/movies.js`
- `frontend/src/components/SearchInput.vue`
