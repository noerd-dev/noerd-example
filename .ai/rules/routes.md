---
paths:
  - 'routes/**'
---

# Routes

## Never override noerd's /login path from routes/web.php
noerd registers `Route::redirect('login', '/noerd/login')` (ANY method) from its package provider, which boots before routes/web.php is loaded.

Without route caching the app's later same-path route wins (last registration wins in the router's method bucket), but with `php artisan route:cache` — as on the deployed demo — the compiled matcher takes the first registered route, so noerd's redirect wins and the app's override silently disappears in production only.

Any app-side login screen must therefore live on its own path (currently `/demo-login`, named `login`), not on `/login`.
