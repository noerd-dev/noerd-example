---
paths:
  - 'app/Http/**'
---

# Http

## Demo credentials live in the session, and every login URL runs through DemoLoginController
The demo login screen prefills its fields from `demo_email` / `demo_password` in the session. Put them there with `session()->put()`, never `flash()`: flashed credentials only survive the single redirect from `/`, so a direct hit on `/demo-login`, a refresh or a bookmark rendered empty fields.

`RedirectToDemoLogin` (appended to the `web` group) sends guests from `noerd.login` and from `login`-without-usable-credentials back to `/`, so DemoLoginController stays the only place that provisions a demo user. It re-provisions when the session's demo user no longer exists, because `demo:cleanup` and `demo:reset` delete demo users behind live sessions.
