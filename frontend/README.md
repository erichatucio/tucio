# Product Management frontend

React and Vite client for the LavaLust product API. It includes account registration and login, automatic access-token refresh, and authenticated product listing, creation, editing, and deletion. Product data and credentials are handled by the API; the browser does not connect to MySQL.

## Local setup

Install dependencies and provide the API base URL:

```powershell
Copy-Item .env.example .env
npm install
npm run dev
```

Set `VITE_API_URL` in `.env` to the API origin, without a trailing slash or `/api` suffix. For the deployed API:

```dotenv
VITE_API_URL=https://tucio.onrender.com
```

The Vite development server runs at `http://localhost:5173`. The API must allow that origin in CORS for local development.

## Build

```powershell
npm run lint
npm run build
```

## Deployment

- Frontend: `https://tucio-frontend.onrender.com`
- API: `https://tucio.onrender.com`

The Render Static Site builds this directory with `npm install && npm run build`, publishes `dist`, and rewrites application routes to `/index.html`. Configure `VITE_API_URL` on the Static Site before building. The API's `FRONTEND_ORIGIN` must contain the frontend's exact HTTPS origin; `http://localhost:5173` is also allowed by the API for local testing.

## Verification checklist

1. Open the frontend URL in a private window; the Product Management login screen appears.
2. Choose **Create an account**, register a new username and password, then log in; the dashboard greets the username and loads products.
3. Select **Add Product**, submit valid product details, and verify the product appears in the list.
4. Edit that product, save, and verify the updated values appear.
5. Delete it and confirm the browser asks before deletion; verify it disappears after confirmation.
6. Log out and verify the login screen returns. Reloading the dashboard URL while signed out must not expose product data.
7. As a signed-out user, opening `https://tucio.onrender.com/api/products` directly returns an unauthorized response; this endpoint requires a bearer token.
