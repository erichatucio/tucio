# Stockroom React client

React/Vite frontend for the LavaLust API. It supports login, product list, create, edit, delete, and logout. All product data flows through the API; the client has no MySQL credentials.

## Development

Copy `.env.example` to `.env`, set `VITE_API_BASE_URL` to the LavaLust origin, then:

```sh
npm ci
npm run dev
```

For a production build:

```sh
npm run build
```

Set `VITE_API_BASE_URL` in the frontend hosting provider to the deployed API origin. Also allow the frontend's exact HTTPS origin in the API's `FRONTEND_ORIGIN`.
