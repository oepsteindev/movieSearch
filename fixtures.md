# Symfony favorites setup

Build the favorites feature as if authentication already exists, but do not build login or registration.

## What to create

Create these pieces:

- `User` entity, if the project does not already have one
- `FavoriteList` entity
- `FavoriteItem` entity
- `AppFixtures` with one demo user
- A small private helper on `FavoritesService` that returns the demo user (see "Current user logic" below — this is a single-consumer lookup, not a standalone auth service, so it doesn't need its own class)

## Entity relationships

Use this structure:

- A `User` has many `FavoriteList` records
- A `FavoriteList` belongs to one `User`
- A `FavoriteList` has many `FavoriteItem` records
- A `FavoriteItem` belongs to one `FavoriteList`

Do not make favorites global and do not hardcode `user_id = 1` in controllers.

## User entity

If needed, create a minimal `User` entity with:

- `id`
- `email`
- `roles`
- `password`

No `OneToMany` collection back to `FavoriteList` — nothing in the code ever
navigates `user.lists`, it's always queried via `FavoriteListRepository`, so
the collection would be unused mapping.

## FavoriteList entity

Create a `FavoriteList` entity with:

- `id`
- `name`
- `owner` relation to `User`

Make `owner` required. No `items` collection to `FavoriteItem` for the same
reason as above — items are always queried via `FavoriteItemRepository`.

## FavoriteItem entity

Create a `FavoriteItem` entity with:

- `id`
- `externalId` (the OMDb `imdbID`)
- `name`
- `image`
- `year` (nullable)
- `favoriteList` relation to `FavoriteList`

Make `favoriteList` required.

## Fixture

Create one demo user in `AppFixtures`:

- email: `demo@example.com`
- role: `ROLE_USER`
- password: placeholder value is fine

Also create:

- one sample favorite list for that user
- two sample favorite items in that list

## Current user logic

There's only ever one user in this demo (no login/registration), and only one
service (`FavoritesService`) ever needs to know who it is — so this is a
private method on that service, not a standalone provider class. Pulling it
into its own class/interface would be premature: there's a single consumer
and a single implementation, so there's nothing to decouple yet. If real auth
is ever added, this is still the one place that would change.

Add a private method to `FavoritesService`, e.g. `getCurrentUser(): User`:

- inject `UserRepository`
- load the user with email `demo@example.com`
- throw a plain `\RuntimeException` if the user does not exist — this means
  fixtures haven't been loaded yet. No dedicated exception class: nothing
  catches this by type (unlike duplicate-name or not-found errors, which
  controllers translate into specific HTTP statuses), so a custom class
  would add a file without adding behavior.

## How to use it

When creating a list:

- get the current user via the private `getCurrentUser()` method
- assign that user as the list owner
- save the list

When loading lists:

- get the current user via `getCurrentUser()`
- only query lists for that user

When editing or deleting a list:

- get the current user
- find the list by both `id` and `owner`

## Repository methods

Add methods such as:

- `findByOwner(User $user): array`
- `findOneByIdAndOwner(int $id, User $user): ?FavoriteList`

## Important rules

- no login page
- no registration page
- no real auth flow
- no request-supplied `user_id`
- keep the data model user-scoped

## Output

Return code in separate fenced code blocks for:

- `User.php` if needed
- `FavoriteList.php`
- `FavoriteItem.php`
- `AppFixtures.php`
- `FavoriteListRepository.php`
- `FavoritesService.php` (with the private `getCurrentUser()` method described above)