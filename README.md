# Netgen Layouts Demo

A rebuild of the [SymfonyCasts Netgen Layouts tutorial](https://symfonycasts.com/screencast/netgen-layouts) on a
current stack: **Symfony 8.1**, **PHP 8.4/8.5**, **Netgen Layouts 2.0**. The original tutorial repo targets
Symfony 5.4 (EOL) and Netgen Layouts 1.3, which won't run on this machine's PHP version — so this project was
scaffolded fresh from [netgen-layouts/layouts-symfony-site](https://github.com/netgen-layouts/layouts-symfony-site)
(the official current demo) and the tutorial's app-specific code (Recipe entity, controllers, Netgen integration
classes) was ported over and adapted to the new APIs.

## Setup

```bash
composer install
docker compose up -d database
symfony console app:netgen:migrate         # loads Netgen's own schema (see "Database" below)
symfony console doctrine:migrations:migrate --no-interaction
symfony console doctrine:fixtures:load --no-interaction
symfony server:start -d
```

Then visit the local URL Symfony CLI prints (or `symfony open:local`).

### Logging in

- **Real accounts**: register at `/register` (email verification via `symfonycasts/verify-email-bundle`, no real
  mailer configured — check `var/log/dev.log` or the Symfony profiler's email panel for the verification link).
- **Admin**: username `admin`, password `admin` — an in-memory user (see `config/packages/security.yaml`), not a
  database row. It has `ROLE_NGLAYOUTS_ADMIN` and `ROLE_ADMIN`, giving access to:
  - `/nglayouts/admin` — Netgen Layouts admin (Layout Studio, mappings, content browser)
  - `/admin` — EasyAdmin (Recipe CRUD)

## Database: why Postgres + a custom migration command

Netgen Layouts' own `doctrine:migrations:migrate` path is **hardcoded to MySQL** — one of its migrations calls
`$this->abortIf(!$this->connection->getDatabasePlatform() instanceof MySQLPlatform, ...)`. But
[netgen-layouts/layouts-symfony-site](https://github.com/netgen-layouts/layouts-symfony-site) (the official current
demo) defaults to Postgres, and Netgen publishes a maintained Postgres schema dump for exactly this reason (see
[layouts-standard#6](https://github.com/netgen-layouts/layouts-standard/issues/6)):
`vendor/netgen/layouts-core/resources/data/schema.pgsql.sql`.

So this project:

1. Runs on Postgres (`compose.yaml`), matching the official demo.
2. Uses a **separate `nglayouts` DBAL connection** (see `config/packages/netgen_layouts.yaml`) pointed at the same
   database by default (`NGLAYOUTS_DATABASE_URL` falls back to `DATABASE_URL`), so Netgen's tables are managed
   independently of the app's own Doctrine ORM/migrations.
3. Ships `App\Command\NetgenMigrateCommand` (`app:netgen:migrate`), which reads `schema.pgsql.sql` and executes it
   directly against the `nglayouts` connection in a transaction — no `psql` binary required. Pass `--reset` to drop
   and recreate all `nglayouts_*` tables/sequences.
4. Excludes `nglayouts_*` tables from the app's own schema via `schema_filter: '~^(?!nglayouts_)~'` on the `default`
   connection, so `doctrine:migrations:diff`/`make:migration` never try to touch or drop them.

Run `app:netgen:migrate` once after a fresh `composer install` (before the app's own migrations); it's idempotent
(`CREATE TABLE IF NOT EXISTS`) but **not** re-run automatically on `composer install`/`update`.

## Docker + the Symfony CLI

`compose.yaml` maps the Postgres container's port to a **random host port** (Docker's default when you write
`ports: - "5432"` with no explicit host port). Always invoke commands through `symfony console` / `symfony server:start`,
not raw `php bin/console` — the Symfony CLI detects the running compose service and injects the real `DATABASE_URL`
(with the actual mapped port) as an environment variable, which takes precedence over whatever's in `.env`. Run
`symfony var:export --multiline | grep DATABASE` to see what it resolves to.

## Gotcha: password hasher matching is order-sensitive

`security.yaml`'s in-memory `admin` user needs the `plaintext` algorithm, but `InMemoryUser` also implements
`PasswordAuthenticatedUserInterface` (which has the generic `auto` rule). Symfony's `PasswordHasherFactory` picks the
**first matching entry** in `password_hashers`, in declaration order — so the more specific `InMemoryUser` rule
**must be listed before** the `PasswordAuthenticatedUserInterface` rule, or `auto` silently wins and login fails with
"presented password is invalid" for no obvious reason.

## What got adapted vs. skipped from the tutorial

Ported and adapted to current APIs:

- `Layouts/RecipeValueLoader.php`, `RecipeValueConverter.php`, `LatestRecipeQueryTypeHandler.php`,
  `VerticalWhitespacePlugin.php` — unchanged, `netgen/layouts-core` 2.0's relevant interfaces didn't break these.
- `ContentBrowser/BrowserRootLocation.php`, `RecipeBrowserItem.php` — `netgen/content-browser` 2.0 replaced getter
  methods (`getLocationId()`, `getName()`, ...) with PHP 8.4 **property hooks** on `LocationInterface`/`ItemInterface`.
- `ContentBrowser/RecipeBrowserBackend.php` — `search()`/`searchCount(string $text, ...)` were replaced with
  `searchItems(SearchQuery $query)`/`searchItemsCount(SearchQuery $query)`.
- `RecipeFactory` — rewritten for Foundry v2 (`PersistentObjectFactory`, `defaults()`/`initialize()`) from the
  tutorial's Foundry v1 (`ModelFactory`, `getDefaults()`).
- `Controller/Admin/DashboardController.php`/`RecipeCrudController.php` — EasyAdmin 5 requires
  `#[AdminDashboard(routePath:, routeName:)]` instead of a plain `#[Route]` on the dashboard's `index()`, and
  `MenuItem::linkToCrud($label, $icon, $entityFqcn)` was replaced by `MenuItem::linkTo($controllerFqcn, $label, $icon)`.

Skipped:

- **Contentful integration** (`Layouts/ContentfulSlugger.php`, the Skills/Ads content and templates) —
  `contentful/contentful-bundle` doesn't support Symfony 8 yet (`^5.4|^6.0|^7.0` only). Everything Recipe-related has
  zero Contentful dependency, so this only cost the Skills page and the two ad/skill item-view templates.
- **Rendering through an actual Netgen-built page layout** — `recipes/list.html.twig` and `recipes/show.html.twig`
  extend `base.html.twig` instead of `nglayouts.layoutTemplate`. The tutorial's real layouts (zones, blocks, the
  homepage hero/subscribe/skills sections) live in a database the tutorial repo doesn't export, so there's nothing to
  restore — building an actual Layout + Layout Resolver mapping rule for a route is manual work in `/nglayouts/admin`.
  The Recipe value type, "Latest Recipes" query type, and content browser backend are all wired up and confirmed to
  register correctly (see `config/packages/netgen_layouts.yaml` / `netgen_content_browser.yaml`), so a "list" block
  using the "Latest Recipes" query with the `one_by_two` view type will render real recipe data once such a layout
  exists — that's the natural next step.

## Recipe content

`src/DataFixtures/AppFixtures.php` creates 25 fake recipes via `RecipeFactory` (Faker-generated names/text, real
images copied from `src/DataFixtures/images/`). Recipe images actually used by fixture data live in
`public/uploads/recipes/` and are gitignored — they're regenerated every time fixtures load, not source.
