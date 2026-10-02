# LavaLust Product CRUD (Laboratory Exercise No. 5)

A small LavaLust MVC application with session authentication and full product CRUD. Product data is stored in MySQL; production connections must use TLS. The app is prepared for deployment to Render with its Docker runtime.

## Requirements

- PHP 8.3 with `pdo_mysql`, `session`, and Apache `mod_rewrite` (or Docker)
- A MySQL database, such as Aiven MySQL
- An Aiven CA certificate for a verified TLS connection

## 1. Create the Aiven database

Create a MySQL service and database in Aiven. Copy the host, port, database name, username, password, and CA certificate from the Aiven service connection information. Run [database/products.sql](./database/products.sql) against that database to create the required `products` table.

The table has the requested columns: auto-increment `id`, `product_name`, `description`, `price`, `quantity`, and `created_at`.

## 2. Configure the application

LavaLust loads the root `.env` file for local development. Copy `.env.example` to `.env` and fill in local values. Render should use its Environment settings instead. Never commit `.env` or actual secrets.

Required variables:

| Variable | Purpose |
| --- | --- |
| `APP_ENV` | Use `development` locally and `production` on Render. |
| `APP_URL` | Full application URL ending with `/`, e.g. `https://your-service.onrender.com/`. |
| `APP_KEY` | Random secret used for sessions and CSRF; generate with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. |
| `ADMIN_USERNAME` | Login username for the application administrator. |
| `ADMIN_PASSWORD_HASH` | PHP `password_hash()` output for the administrator password; never store the plaintext password here. |
| `DB_DRIVER` | `mysql`. |
| `DB_HOST`, `DB_PORT` | Aiven host and port. |
| `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Aiven database name, username, and password. |
| `DB_CHARSET` | `utf8mb4`. |
| `DB_SSL_CA_CERT` | Full PEM contents of Aiven's CA certificate (recommended on Render). For local `.env`, set `DB_SSL_CA` to the readable CA file path because the `.env` parser does not support multiline values. |

Generate an administrator password hash without putting the password in the command history:

```powershell
php -r '$password = readline("Admin password: "); echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;'
```

Set the returned hash as `ADMIN_PASSWORD_HASH` in the local environment or Render. In PowerShell, environment variables can be set for the current shell with `$env:NAME = "value"` before starting the PHP server. Alternatively, configure them in the Apache virtual host.

## 3. Run locally

Configure the variables above in Apache or your shell, set the web server document root to `public/`, and enable URL rewriting. Open the configured `APP_URL`; the root route redirects to the sign-in page. Product CRUD routes require an authenticated session. All form submissions use CSRF protection, and deletion requires a confirmation page followed by a POST.

## 4. Deploy to Render

1. Push this repository to your GitHub repository.
2. In Render, create a **Web Service** connected to the repository and select **Docker** as the runtime. The Dockerfile serves the application from `public/` on port `10000`, Render's default web-service port.
3. Add the variables in the table above under the Render service's Environment settings. Use the values from Aiven. Set `APP_ENV=production`, `APP_URL` to the final HTTPS Render URL, and `DB_SSL_CA_CERT` to the PEM contents of Aiven's CA certificate.
4. Generate a unique `APP_KEY` and set it only in Render's environment settings. Set `ADMIN_PASSWORD_HASH` to the output of `password_hash()` and keep the plaintext password private.
5. Deploy. Visit `/login`, authenticate, and test creating, listing, editing, and deleting a product. Confirm the row appears in the Aiven `products` table.

The production MySQL connector refuses to open a connection unless a CA file/path is configured. It verifies the MySQL server certificate and uses TLS.

## Routes

| Route | Method | Description |
| --- | --- | --- |
| `/login` | GET, POST | Administrator sign-in |
| `/logout` | POST | Sign out |
| `/products` | GET | List products |
| `/products/create` | GET, POST | Add a product |
| `/products/edit/{id}` | GET, POST | Edit a product |
| `/products/delete/{id}` | GET, POST | Confirm and delete a product |

Product routes check the authenticated session before accessing the database. Mutation routes require POST and a valid CSRF token.

## Verification and submission

Check the live Render URL while signed out (product routes must redirect to login), then sign in and exercise each CRUD operation. Capture screenshots of login, product list, add, edit, delete confirmation/result, and the Aiven table. Submit your GitHub repository URL, Render URL, and the requested screenshots with the assignment.
