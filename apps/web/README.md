# Stockpile Web Layer

The Stockpile web UI is rendered by the Laravel app in `apps/api` and loaded from
this directory as an additional Blade view path.

- `views/` contains the Bootstrap-based web interface.
- Static assets are served from `apps/api/public`.
- Routes live in `apps/api/routes/web.php`.
