# CMS

A content management system with a Symfony 6.4 REST API and a Vue 3 admin dashboard. Editors manage pages built from
ordered content blocks, with drafts, scheduled publishing, SEO metadata and revision history.

**Status:** work in progress. Runs locally; not deployed.

## Features

- Pages: create, edit and delete pages with a slug, template and SEO metadata (meta title, description, keywords,
  Open Graph tags, indexable flag).
- Publishing workflow: `draft`, `scheduled`, `published` and `archived` states, with an optional publication window
  (`publishAt` / `unpublishAt`).
- Block-based content: each page holds ordered blocks of type text, image, video, gallery, code, quote or HTML.
- Revision history: a revision is stored on every create and update, and revisions can be read through the API.
- Categories, managed through the API (only admins can write).
- JWT authentication, registration and profile endpoints, and two roles (`ROLE_USER`, `ROLE_ADMIN`).
- Admin dashboard (Vue 3) with page and media counts, plus page, block and media screens.

## Tech stack

| Layer | Technologies |
|---|---|
| Backend | PHP 8.2+, Symfony 6.4, Doctrine ORM, LexikJWTAuthenticationBundle, NelmioCorsBundle |
| Frontend | Vue 3, Pinia, Vue Router 4, Axios, Vite 4, Tailwind CSS 3 |
| Database | MySQL 8 (SQLite for the test suite) |
| Tests | PHPUnit 11 |

## Getting started

### Prerequisites

- PHP 8.2+ with the `sodium` and `pdo_mysql` extensions (plus `pdo_sqlite` for the tests)
- Composer
- MySQL 8.0, or Docker to run the bundled `backend/compose.yaml`
- Node.js 18+ and Yarn

### Quick start

```bash
git clone https://github.com/TiagoCruz1224/CMS.git
cd CMS/backend
cp .env.example .env
composer install
docker compose up -d database
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php -S localhost:8000 -t public/
```

Copy `.env.example` before `composer install`, because its post-install scripts boot Symfony. Skip the
`docker compose` line if you use a local MySQL server, and set `DATABASE_URL` in `.env` instead.

In a second terminal, from the repository root:

```bash
cd frontend
yarn install
yarn dev
```

The dashboard runs at `http://localhost:5173` and the API at `http://localhost:8000/api`.

### Create an admin user

Registration always creates a regular user (`ROLE_USER`); roles sent to the API are ignored. Register through the
API, then promote the account from `backend/`:

```bash
curl -X POST http://localhost:8000/api/register -H "Content-Type: application/json" -d '{"email": "<admin-email>", "password": "<password>"}'
php bin/console dbal:run-sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = '<admin-email>'"
```

Full setup, environment variables and troubleshooting: [setup guide](SETUP.md).

## Running the tests

The backend has functional API tests that run against SQLite, so no MySQL server is needed.

```bash
cd backend
php bin/phpunit
```

Current result: 11 tests, 49 assertions. The suite (`backend/tests/Controller/Api/PermissionsTest.php`) covers:

- registration and profile updates cannot grant roles;
- changing email or password requires the current password;
- invalid input is rejected before it is saved;
- drafts and scheduled pages outside their publication window, including their blocks, media and revisions, are
  visible only to their author and admins.

`backend/test-api.sh` is an end-to-end smoke test for a running server: it registers its own users, promotes the admin
with `bin/console`, and exits with a non-zero status if any check fails.

## Security

- Stateless JWT authentication (LexikJWTAuthenticationBundle).
- Passwords hashed with Symfony's `auto` password hasher.
- Roles are never taken from the request; new accounts get `ROLE_USER`.
- Page visibility is enforced by a voter (`PageVoter`); hidden pages answer `404`.
- Request data is validated with Symfony Validator before it is persisted.

## API

| Resource | Endpoints | Access |
|---|---|---|
| Auth | `POST /api/register`, `POST /api/login`, `GET` / `PUT /api/profile` | Public (register, login); authenticated (profile) |
| Pages | `GET`, `POST /api/pages`; `GET`, `PUT`, `DELETE /api/pages/{id}`; `GET /api/pages/{id}/revisions` | Public read of published pages; revisions and writes need authentication |
| Blocks | `POST /api/blocks`; `GET`, `PUT`, `DELETE /api/blocks/{id}` | Authenticated |
| Media | `GET`, `POST /api/media`; `GET`, `PUT`, `DELETE /api/media/{id}` | Authenticated |
| Categories | `GET`, `POST /api/categories`; `GET`, `PUT`, `DELETE /api/categories/{id}` | Public read; admin write |

Full reference, including `?status=published`: [API documentation](backend/API_DOCUMENTATION.md).

## Project structure

| Path | Contents |
|---|---|
| `backend/` | Symfony API: controllers, entities, repositories, services, security voter, migrations, tests |
| `frontend/` | Vue 3 admin dashboard: views, Pinia stores, API services, router |
| `SETUP.md` | Full local setup |
| `backend/API_DOCUMENTATION.md` | API reference |

## Known limitations

- Media upload does not work end to end yet: the API stores media metadata (URL, type, alt text) but does not accept
  file uploads.
- Categories and page revisions are available through the API only; the dashboard has no screens for them.
- The Blocks screen cannot list blocks: the API has no `GET /api/blocks` endpoint (blocks are read one at a time or with
  their page). Restoring a page revision is not supported either.
- Dark mode covers the layout and login screen only.
- No frontend tests yet.
- Not deployed; there is no production configuration.

## Author

Built by Tiago Cruz ([@TiagoCruz1224](https://github.com/TiagoCruz1224)).
