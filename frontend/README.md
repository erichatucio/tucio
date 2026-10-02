# Product Management frontend

React and Vite client for the LavaLust product API. It includes account registration and login, automatic access-token refresh, and authenticated product listing, creation, editing, and deletion. Product data and credentials are handled by the API; the browser does not connect to MySQL.

## Local setup

Install dependencies and provide the API base URL:

```powershell
Copy-Item .env.example .env.local
npm install
npm run dev
```

Set `VITE_API_URL` in `.env.local` to the API origin, without a trailing slash or `/api` suffix. The example file points to the deployed API:

```dotenv
VITE_API_URL=https://tucio.onrender.com
```

The Vite development server runs at `http://localhost:5173` (or `http://127.0.0.1:5173`). Both origins are allowed by the API for local development.

## Build

```powershell
npm run lint
npm run build
```

## Deployment

- Main app and API: `https://tucio.onrender.com`
- Standalone frontend mirror: `https://tucio-product-frontend.onrender.com`

The API Docker image builds this React app and serves `dist` from the same origin as the API. When `VITE_API_URL` is not set, the app uses its current origin, so the public app calls `https://tucio.onrender.com/api/...` without cross-origin requests. The optional Render Static Site builds this directory with `npm install && npm run build`, publishes `dist`, and rewrites application routes to `/index.html`; configure `VITE_API_URL=https://tucio.onrender.com` on that site. The API's `FRONTEND_ORIGIN` allows the standalone frontend origin; `http://localhost:5173` and `http://127.0.0.1:5173` are also allowed for local testing.

## Verification checklist

1. Open the frontend URL in a private window; the Product Management login screen appears.
2. Choose **Create an account**, register a new username and password, then log in; the dashboard greets the username and loads products.
3. Select **Add Product**, submit valid product details, and verify the product appears in the list.
4. Edit that product, save, and verify the updated values appear.
5. Delete it and confirm the browser asks before deletion; verify it disappears after confirmation.
6. Log out and verify the login screen returns. Reloading the dashboard URL while signed out must not expose product data.
7. As a signed-out user, opening `https://tucio.onrender.com/api/products` directly returns an unauthorized response; this endpoint requires a bearer token.
