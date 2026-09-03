---
paths:
  - 'resources/views/livewire/**'
---

# Livewire

## Authenticate through noerd's guard, never plain Auth::attempt()
noerd protects its routes with the guard from `config('noerd.auth.guard')` (default `noerd`). That is only the app's default guard while `NOERD_AUTH_DEFAULT=true` is set in the environment — local `.env` and `phpunit.xml` set it, a deployed environment may not.

So log in with `NoerdAuth::guard()->attempt(...)` and read the user with `NoerdAuth::user()`. Plain `Auth::attempt()` authenticates the default guard, noerd keeps seeing a guest, and the redirect to `noerd.apps` bounces straight back to `/noerd/login` — which looks exactly like "the login button does nothing".
