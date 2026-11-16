# Production security settings

Settings the live site (`https://oneway.ridelogic.in`) must have. They live in the **production** `.env`
on the server — never copy the local `.env` to production.

## Required `.env` values

| Setting | Required value | Why |
|---|---|---|
| `SESSION_SECURE_COOKIE` | `true` | Adds the `Secure` flag to the session cookie **and** the `XSRF-TOKEN` cookie, so browsers only ever send them over HTTPS. Without it, the cookies could travel over a plain-HTTP request (e.g. a mistyped `http://` link before the HTTPS redirect) and be intercepted. |
| `APP_ENV` | `production` | Production error handling and behaviour. |
| `APP_DEBUG` | `false` | Never show stack traces, configuration or query details to visitors. |
| `APP_URL` | `https://oneway.ridelogic.in` | Correct absolute links, canonical URLs and redirects. |
| `APP_KEY` | the existing production key | Must be set and must **not** be regenerated — changing it signs out every admin and invalidates encrypted data. |

Already safe by default (leave unset unless there is a reason to change them):
`SESSION_HTTP_ONLY` (defaults to `true` — JavaScript cannot read the session cookie) and
`SESSION_SAME_SITE` (defaults to `lax` — the cookie is not sent on cross-site POSTs).

## After changing the production `.env`

1. If production uses a configuration cache, rebuild it so the new values load:
   `php artisan config:clear` then `php artisan config:cache` (run on the **server only** — never locally,
   where it would make the test suite use the real database).
2. Sign in to `/admin` again (existing admin sessions keep their old cookie until they expire).
3. Verify — the `Set-Cookie` headers must include `secure`:

   ```bash
   curl -sI https://oneway.ridelogic.in/admin/login | grep -i '^set-cookie'
   # expected, for both cookies:  ...; secure; httponly; samesite=lax   (XSRF-TOKEN has no httponly, by design)
   ```

## Web server

* The document root must be Laravel's `public/` directory (production: `public_html/oneway` → `oneway-app/public`).
  Nothing above `public/` — `.env`, `storage/`, `database/`, `config/`, `vendor/` — may be reachable over HTTP.
* The project-root `.htaccess` blocks those paths when the project folder itself is web-served (as on local
  XAMPP). It is harmless on production, where only `public/` is served.

## Environment loading

`bootstrap/app.php` calls `Env::disablePutenv()` so `.env` values stay inside each request. This prevents
requests from losing `.env` values under concurrency on threaded PHP (XAMPP's Apache module on Windows).
It has no effect on correctly configured process-based hosting and needs no production change.
