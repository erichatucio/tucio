# Stockroom — LavaLust API + React (Laboratory Exercises 5 and 6)

Stockroom is a React product-management client backed by a LavaLust JSON API and Aiven MySQL. Product operations use parameterized database queries in the API only; the browser never connects to MySQL. The API uses LavaLust's `Api` library for JSON responses, JWT access/refresh tokens, rate limiting, and bearer-token validation.

The server-rendered product manager is available at `/login` and `/products`. It is a separate interface from the React client at `/`; the React frontend's design and source are unchanged.

## Stack

- LavaLust 4.6 / PHP 8.3 with PDO MySQL
- React 19 and Vite
- Aiven MySQL over verified TLS
- Render Docker Web Service for the React client and LavaLust API on one origin

## Database migrations

Migration files are in `app/migrations/`:

| Version | Tables |
| --- | --- |
| `000_initial_setup` | `migrations` |
| `001_create_users_table` | `users` |
| `002_create_refresh_tokens_table` | `refresh_tokens` |
| `003_create_products_table` | `products` |

The product migration is idempotent and leaves an existing `products` table and its rows untouched. The API provisions the configured administrator as a row in `users` on the first successful API login. Refresh-token values are stored hashed.

This LavaLust version discovers commands from `app/commands/` (plural). The CLI generator was used to create the migration command; it is named `MigrationCommand` to avoid colliding with LavaLust's own `Migration` library. Use:

```powershell
php lava make:controller MigrationController
php lava make:command Migration
php lava migration run
php lava migration status
php lava migration create-migration create_products_table
php lava migration rollback
php lava migration rollback-all
php lava migration refresh
```

`rollback`, `rollback-all`, and `refresh` refuse to run when `APP_ENV=production`. Use them only against a disposable development database. The migration HTTP routes required by the exercise are registered in `app/config/routes.php`, but the controller returns 404 for web requests: all migration execution is restricted to CLI. Render runs `php lava migration run` as the container starts, applying only pending migrations. This creates `migrations`, `users`, and `refresh_tokens` on the configured Aiven database and skips the already-present `products` table.

## API

All routes are under `/api`. Except login and refresh, requests require `Authorization: Bearer <access_token>`.

| Method | Route | Authentication | Result |
| --- | --- | --- | --- |
| `POST` | `/api/auth/register` | Public, rate-limited | Validate registration details, create a user, return the account |
| `POST` | `/api/auth/login` | Public, rate-limited | Verify an administrator or registered account, return access and refresh tokens |
| `POST` | `/api/auth/refresh` | Refresh token in JSON body | Rotate refresh token and issue a new access token |
| `POST` | `/api/auth/logout` | Access token and matching refresh token | Revoke refresh token |
| `GET` | `/api/auth/me` | Bearer token | Return current account |
| `GET` | `/api/products` | `read` scope | List products, newest first |
| `GET` | `/api/products/{id}` | `read` scope | Read one product |
| `POST` | `/api/products` | `write` scope | Create product; responds `201` |
| `PUT` | `/api/products/{id}` | `write` scope | Replace product fields |
| `PATCH` | `/api/products/{id}` | `write` scope | Update product fields |
| `DELETE` | `/api/products/{id}` | `delete` scope | Delete product |

Product values are validated server-side. CORS allows `http://localhost:5173`, `http://127.0.0.1:5173`, and the origin in `FRONTEND_ORIGIN`; set that variable to the exact HTTPS origin of the deployed React site. JSON API paths are excluded from cookie CSRF checks because state-changing requests use bearer tokens and never rely on cookies.

## Local development

1. Copy `.env.example` to `.env`; set the administrator credentials, MySQL connection values, and `DB_SSL_CA` to the Aiven CA file path. Do not commit `.env`.
2. Generate separate JWT and refresh-token secrets without printing them:

   ```powershell
   php lava jwt:generate
   ```

3. Run the migrations against the intended database:

   ```powershell
   php lava migration run
   ```

4. Start the API in one terminal:

   ```powershell
   php lava serve
   ```

5. In another terminal, configure the client API origin and start Vite:

   ```powershell
   Set-Location frontend
   Copy-Item .env.example .env
   npm install
   npm run dev
   ```

   The client defaults to `http://localhost:3000`; the API CORS default is `http://localhost:5173`.

## Render deployment

The Docker image builds the React client in a Node stage, installs its static files in the Apache public document root, and serves the client at `https://tucio.onrender.com/`. API routes remain under `/api` on that same origin. The image listens on Render's port `10000` and applies pending migrations at startup. The root `render.yaml` also retains the standalone Static Site configuration for `https://tucio-product-frontend.onrender.com/`.

Configure the required values in Render **Environment**:

| Variable | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_KEY` | Persistent random application key |
| `APP_URL` | The API service's HTTPS URL ending in `/` |
| `ADMIN_USERNAME` | Administrator login |
| `ADMIN_PASSWORD_HASH` | PHP `password_hash()` output (never plaintext) |
| `DB_DRIVER`, `DB_CHARSET` | `mysql`, `utf8mb4` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Aiven's connection details |
| `DB_SSL_CA_CERT` | Full Aiven CA certificate PEM contents |
| `JWT_SECRET`, `REFRESH_TOKEN_KEY` | Two independent random values, each at least 32 characters. The Render Blueprint generates both automatically; if deploying an existing service without syncing the Blueprint, generate and set them separately in the service's Environment settings. |
| `FRONTEND_ORIGIN` | `https://tucio-product-frontend.onrender.com` (exact HTTPS origin, no trailing slash) |

The combined Docker deployment uses the current page origin as the API URL, so the UI and API work together at `https://tucio.onrender.com/`. For local Vite development and the standalone Static Site, set `VITE_API_URL` to the API origin. The frontend's [README](frontend/README.md) has local setup and verification steps.

Never place Aiven credentials, JWT secrets, password hashes, or the CA certificate in either Git repository or any `VITE_*` variable.

## Submission checks

- While signed out, API product routes respond `401`; login returns tokens only for valid credentials.
- A signed-in React user can list, add, edit, and delete products.
- The deployed Aiven database contains `migrations`, `users`, `refresh_tokens`, and `products`.
- `https://tucio.onrender.com/` serves the React client, and its `/api` routes serve the protected LavaLust API.
