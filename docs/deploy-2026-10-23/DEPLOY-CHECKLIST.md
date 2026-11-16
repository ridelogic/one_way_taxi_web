# Deployment checklist — Admin CMS update (prepared 2026-10-23)

Target: `https://oneway.ridelogic.in` · app dir `/home/u582318487/oneway-app` · SSH `ssh -p 65002 u582318487@217.21.94.111`

**Status: prepared, NOT deployed. Nothing below has been run on the server.**

## Baseline (what is live today)

The last live release is the 2026-10-18 09:21 "palette" release. Verified read-only over HTTPS on 2026-10-23:

* live serves `build/assets/app-Bw1IlDvh.css`, SHA-256 identical to that release;
* live `css/admin.css`, `js/admin.js` are the 2026-10-10 versions; `images/demo/admin-login-scene.webp` is 404;
* live `/admin/login` is the old login page; session cookie is already `one_way_taxi_session; secure; httponly; samesite=lax`.

The 2026-10-18 "mobile hero" bundle was prepared but never deployed (live still references `app-Bw1IlDvh.css`).

## What goes live

**Group A — Admin CMS (39 files)**: checksums in `group-a-admin.sha256`.
Admin redesign + login page, input-handling/validation fixes, admin security headers, and the 419 logout fix.

**Group B — public mobile hero (optional, 4 files)**: checksums in `group-b-public-hero.sha256`.
`hero-slider.blade.php`, `resources/css/app.css`, `public/build/assets/app-Cmdi2yXZ.css` (new), `public/build/manifest.json`.
Independent of Group A (admin pages do not use the Vite bundle). Deploy only if you want the mobile hero fix too.

**Never upload**: `.env`, `.htaccess` (project root, XAMPP only), `.phpunit.result.cache`, `database/database.sqlite`,
`inspect-db.php`, `inspect-hero-slides.php`, `live-homepage.html`, `local-homepage.html`, `storage/**`,
`bootstrap/cache/**`, `tests/**`, `node_modules/**`, `vendor/**`, `docs/**`.

## Database

* **No migrations.** Newest migration is `2026_10_12_000001_add_subheading_to_hero_slides_table`; confirm on the
  server with `php artisan migrate:status` (read-only) that all show `Ran`.
* **Do NOT run `db:seed` / `HomepageLayoutSeeder`** — it rewrites CMS content. Live content (colours, fleet, etc.)
  has been edited on the live DB and must be preserved.
* No `.env` change needed: no `APP_KEY` change, cookie name and secure flag are already correct on live.

## Step 1 — read-only pre-check (needs approval to SSH)

Upload `local-full.sha256` to `~/deploy-backups/2026-10-23-admin/` (outside the app), then:

```bash
cd /home/u582318487/oneway-app
sha256sum -c --quiet ~/deploy-backups/2026-10-23-admin/local-full.sha256 2>&1 | sort
php artisan migrate:status | tail -5
ls bootstrap/cache/            # config.php / routes-v7.php present => caches are in use
php -v | head -1               # expect 8.3.x
```

Expected: the only `FAILED` / missing files are exactly the Group A (+ Group B) files.
**Stop** if any other file differs — that means live has an edit not in local, and it must be reviewed first.

## Step 2 — backup

```bash
# group-a-admin.sha256 (and group-b if used) uploaded to $B in Step 1
B=~/deploy-backups/2026-10-23-admin; cd /home/u582318487/oneway-app
cut -c67- $B/group-a-admin.sha256 | while read f; do [ -e "$f" ] && echo "$f"; done > $B/existing.txt
tar czf $B/before.tar.gz -T $B/existing.txt public/build
cut -c67- $B/group-a-admin.sha256 | while read f; do [ -e "$f" ] || echo "$f"; done > $B/new-files.txt
```

Optional DB safety dump (read-only; DB is not modified by this release), as in the 2026-10-18 release:
`mysqldump --single-transaction --no-tablespaces u582318487_oneway | gzip > $B/db.sql.gz` (credentials from the production `.env`, never printed).

## Step 3 — deploy

1. Upload the release tarball (built locally from the group manifest) to `$B`, verify `sha256sum -c`.
2. Extract into `/home/u582318487/oneway-app` in one go (`tar xzf … -C /home/u582318487/oneway-app`).
   If Group B is included, `public/build/manifest.json` must land **after** `app-Cmdi2yXZ.css` (or in the same extract).
3. Verify: `sha256sum -c $B/group-a-admin.sha256` → all OK.

## Step 4 — caches

```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
# only if Step 1 showed cached config/routes were in use:
php artisan config:cache && php artisan route:cache
```

Do **not** run `key:generate`, `migrate:fresh`, `db:wipe`, `db:seed`, or `optimize:clear` against unknown caches.
If old PHP behaviour persists, restart PHP from hPanel (LiteSpeed OPcache).

## Step 5 — smoke test (live)

* `curl -sI https://oneway.ridelogic.in/admin/login` → 200, `X-Frame-Options: DENY`, `Cache-Control: no-store, private`,
  session cookie still `one_way_taxi_session … secure; httponly`.
* `/images/demo/admin-login-scene.webp` → 200; `/css/admin.css` matches the Group A checksum.
* Browser: sign in, open Dashboard, Homepage, Routes, Cities, Media, Settings, Slides, Bookings; save one harmless
  edit and revert it; Sign out → login page. Sign out from a second, already-signed-out tab → login page (not 419).
* Homepage `/`, `/book`, `/fleet`, `/contact` → 200 and look unchanged (Group B: hero on mobile).

## Rollback

```bash
cd /home/u582318487/oneway-app && B=~/deploy-backups/2026-10-23-admin
tar xzf $B/before.tar.gz -C .                 # restores every replaced file + public/build
xargs -r rm -f < $B/new-files.txt              # removes files this release added
php artisan config:clear && php artisan route:clear && php artisan view:clear   # then re-cache if used before
```

No database or `.env` change is involved, so rollback is files-only. Admin sessions survive both deploy and rollback.
