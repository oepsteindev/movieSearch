# MovieSearch

A searchable movie browser: Symfony API backend + Vue 3 frontend, backed by MySQL. See [part1.md](part1.md) and [part2.md](part2.md) for the full functional spec.

## Stack

- **Backend**: Symfony (latest), served via the Symfony local web server.
- **Frontend**: Vue 3 + Vite + Pinia, served via the Vite dev server.
- **Database**: MySQL 8.4.
- All three run in Docker containers via Docker Compose.

## Project layout

```
backend/          Symfony app (scaffolded automatically on first run)
frontend/          Vue 3 app (scaffolded automatically on first run)
docker/backend/    Backend Dockerfile + container entrypoint
docker/frontend/   Frontend Dockerfile + container entrypoint
docker-compose.yml Wires backend, frontend, and mysql together
```

`backend/` and `frontend/` are empty until the containers are started for the first time — the entrypoint scripts detect an empty app directory and scaffold a fresh Symfony/Vue project into it via Composer/npm, so the generated code lands in your working tree (not just inside the image).

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin)
- An OMDb API key: https://www.omdbapi.com/apikey.aspx

## Setup

1. Copy the example env file and fill in your OMDb API key:

   ```bash
   cp .env.example .env
   ```

   Then edit `.env` and set `OMDB_API_KEY=<your key>`. `.env` is gitignored since it holds real credentials; `.env.example` is the committed template.

2. Build and start everything:

   ```bash
   docker compose up -d --build
   ```

   On first run this will:
   - Start MySQL and wait for it to become healthy.
   - Scaffold a new Symfony project into `backend/` (Composer) and start `symfony server:start` on port 8000.
   - Scaffold a new Vue 3 + Vite project into `frontend/` (npm), install Pinia, and start the Vite dev server on port 5173.

   First boot takes a few minutes while Composer/npm install dependencies. Subsequent runs reuse the scaffolded apps and start much faster.

3. Open the app:
   - Frontend: http://localhost:5173
   - Backend API: http://localhost:8000

## Everyday commands

```bash
docker compose up -d          # start containers (no rebuild)
docker compose logs -f backend    # follow Symfony server logs
docker compose logs -f frontend   # follow Vite dev server logs
docker compose down            # stop and remove containers (data volumes persist)
```

Run Symfony console commands (e.g. migrations) inside the backend container:

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
```

Run npm commands inside the frontend container:

```bash
docker compose exec frontend npm run build
```

## Manual test walkthrough

Once all three containers are up (`docker compose ps` should show `mysql`, `backend`, and `frontend` all running), try the app end-to-end:

1. Open http://localhost:5173 in your browser.
2. **Initial load** — a grid of up to 100 movies should appear automatically (poster, title, year), with nothing typed in the search box yet. This is the cached "default list" (internally seeded from the term `"movie"`).
3. **Type 1–3 characters** (e.g. `bat`) — nothing should happen; the grid stays as-is. This confirms the 4-character minimum is enforced (no request is sent).
4. **Type a 4th character** (e.g. `batm` or `batman`) — after a short pause (~400ms debounce), the grid should update to matching results without a page reload. (See "Known limitations" below if a search you expect to match returns nothing.)
5. **Clear the search box** — the grid should revert to the original default list almost instantly (served from the backend's 1-hour cache, not a fresh OMDb call).
6. **Check every card has an image** — none should be missing; the backend drops any movie without a poster rather than showing a broken image.
7. **Resize the browser window narrower** — the grid should reflow to fewer columns.
8. **(Optional) Error state** — run `docker compose stop backend`, reload the page, and confirm a friendly error message appears instead of a crash. Run `docker compose start backend` afterward to restore it.

## Known limitations

- **OMDb's search only matches whole words, not partial words.** `s=` (OMDb's search parameter, which every query here goes through) treats each word in your search term as needing to match a complete word in the title — it does not do prefix/substring matching within a word. For example, searching `"lego"` finds "The Lego Movie" (a complete word match), but searching `"the simp"` returns nothing for "The Simpsons Movie" even though it's a visible result in the default list, because `"simp"` is only a partial word and OMDb doesn't treat it as a prefix of `"Simpsons"`. This is upstream OMDb behavior, not a bug in this app — searching with complete words (e.g. `"simpsons"` instead of `"the simp"`) returns the expected result.
- **A small number of movies may briefly appear then disappear from the grid.** OMDb occasionally reports a poster URL that looks valid but is actually a dead link (404) on Amazon's CDN. The backend only filters OMDb's explicit "no poster" signal (`"N/A"`); a dead-but-valid-looking URL can't be caught server-side without a live request per candidate. Instead, the frontend detects the failed image load in the browser and removes that card from the grid at that point — so on a slow connection you may see a card flash briefly before it's dropped.

## Running tests

```bash
docker compose exec backend php bin/phpunit
docker compose exec frontend npm test
```

## Services

| Service  | Container port | Host port | Notes |
|----------|-----------------|-----------|-------|
| mysql    | 3306            | 3306      | Credentials come from `.env` |
| backend  | 8000            | 8000      | Symfony local server, bound to all interfaces via `--allow-all-ip` |
| frontend | 5173            | 5173      | Vite dev server, bound to `0.0.0.0` |

Code in `backend/` and `frontend/` is bind-mounted into the containers, so edits on the host are picked up immediately by Symfony's server and Vite's HMR.
