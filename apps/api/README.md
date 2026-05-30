# Stockpile API and Web App

This Laravel application serves both the JSON API and the Bootstrap web UI for
the Stockpile portfolio tracker.

## What is implemented

- Email/password auth for the web app and bearer-token auth for Apple clients
- Portfolio, account, holding, journal, benchmark, planning, and import APIs
- Snapshot-based performance analytics and benchmark comparison
- Generic holdings CSV preview and commit flow
- Bootstrap-based web dashboard rendered from `../web/views`
- Queue-ready quote refresh job and scheduled quote refresh command
- Tiingo-backed quote refresh support for current/latest market prices

## Local development

1. Copy `.env.example` to `.env`.
2. Configure your database. The example is set for MySQL, but SQLite works for local testing.
3. Run migrations:

```bash
php artisan migrate
```

4. Create the first account by visiting `/setup`.
5. Start the app locally:

```bash
php artisan serve
```

To use live market data, set these in `.env`:

```env
MARKET_DATA_PROVIDER=tiingo
TIINGO_API_TOKEN=your_tiingo_token
TIINGO_BASE_URL=https://api.tiingo.com
```

The current implementation uses Tiingo's daily prices endpoint for latest
quotes. Tiingo's separate IEX real-time feed can be added later if you need
intraday exchange data.

## CSV import format

Required columns:

```text
account_name,account_type,symbol,asset_type,quantity,cost_basis_total,snapshot_date
```

Optional columns:

```text
currency,name,sector,notes,coupon_rate,maturity_date
```

## Scheduled jobs

The quote refresh command is:

```bash
php artisan portfolio:refresh-quotes
```

The scheduler entry is already defined in `routes/console.php`. On your server,
run Laravel's scheduler every minute through cron:

```bash
* * * * * php /path/to/apps/api/artisan schedule:run >> /dev/null 2>&1
```

If you want queued quote refresh and import work off the request path, run a
queue worker as well:

```bash
php artisan queue:work --queue=default
```
