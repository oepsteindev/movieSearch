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

## Services

| Service  | Container port | Host port | Notes |
|----------|-----------------|-----------|-------|
| mysql    | 3306            | 3306      | Credentials come from `.env` |
| backend  | 8000            | 8000      | Symfony local server, bound to all interfaces via `--allow-all-ip` |
| frontend | 5173            | 5173      | Vite dev server, bound to `0.0.0.0` |

Code in `backend/` and `frontend/` is bind-mounted into the containers, so edits on the host are picked up immediately by Symfony's server and Vite's HMR.
