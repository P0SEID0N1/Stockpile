# Stockpile

Stockpile is a self-hosted stock portfolio tracker with:

- `apps/api`: Laravel backend plus JSON API
- `apps/web`: Bootstrap Blade views loaded by the Laravel app
- `apps/apple`: shared Swift package, macOS app target, and iOS source scaffold
- `script/build_and_run.sh`: macOS app build/launch entrypoint for Codex

## Implemented v1 scope

- Personal-use portfolio tracking
- Snapshot-based holdings and performance history
- Near-real-time quote refresh through a configurable market-data provider
- Generic CSV import for holdings snapshots
- Trade journal and allocation-planning endpoints
- Bootstrap web dashboard
- Shared Swift models, API client, and Apple UI scaffold

## Backend quick start

```bash
cd apps/api
cp .env.example .env
php artisan migrate
php artisan serve
```

Create the first user at `/setup`.

## Verification

- Laravel tests: `cd apps/api && php artisan test`
- Laravel routes: `cd apps/api && php artisan route:list`
- Apple package: code scaffolded, but local build verification is blocked in this environment by the installed Command Line Tools / SDK mismatch and the absence of full Xcode.

## Self-host deployment checklist

- Use MySQL in production and keep `.env` secrets only on the server.
- Terminate TLS at Nginx or Apache and force HTTPS/correct cookie settings.
- Run `php artisan migrate --force` during deploys.
- Configure cron for `php artisan schedule:run`.
- Run a queue worker if you want quote refresh/import processing off the request path.
- Set up database backups and test restore.
- Rotate Laravel logs and monitor disk usage for import files and logs.
