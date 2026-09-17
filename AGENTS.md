# UstaGo Avto Backend

## Project Shape

- Laravel 11 REST API for the UstaGo Avto customer, mechanic, and admin marketplace.
- Routes and middleware are defined in [routes/api.php](routes/api.php) and [bootstrap/app.php](bootstrap/app.php).
- Controllers handle HTTP orchestration; Form Requests validate input; API Resources define response data; Eloquent Models own relationships; Policies and role middleware enforce access; domain mutations belong in services such as [app/Services/BookingService.php](app/Services/BookingService.php).
- Use [README.md](README.md) for the endpoint catalog, data model, environment setup, and broader product behavior.

## Commands

```bash
composer install
composer test
vendor/bin/pint --test
php artisan migrate --seed
php artisan serve
```

- `composer test` runs the PHPUnit suite under `tests/Feature` with in-memory SQLite. The PHP `pdo_sqlite` extension must be enabled.
- Local development uses MySQL; configure `.env` from `.env.example`. Do not use development MySQL settings for tests.
- There is no application build step. Run the focused PHPUnit test file first when changing one feature, then the full suite.

## Implementation Rules

- Preserve the JSON envelope: successful responses use `{success, message, data}` and failures use `{success, message, errors}`. Reuse [app/Traits/ApiResponse.php](app/Traits/ApiResponse.php) in API controllers.
- Keep API request fields and route parameters consistent with existing camelCase conventions such as `masterServiceId` and `verificationStatus`; database columns remain snake_case.
- Keep authorization layered: `auth:sanctum`, `role:*` middleware, then controller ownership checks or a Policy where applicable. Do not rely on a Form Request's `authorize()` alone for resource ownership unless the surrounding feature already does so.
- Keep booking status transitions, slot locking, status logs, and booking notifications in [app/Services/BookingService.php](app/Services/BookingService.php), rather than duplicating them in controllers.
- Scope mechanic-owned reads and writes to the authenticated user's `masterProfile`. Use route-model binding names that match controller parameter names.
- Follow existing test patterns: `RefreshDatabase`, factories, `actingAs(..., 'sanctum')`, JSON assertions, and database assertions. Add or update a feature test for behavior changes.
- Prefer `whenLoaded()` in Resources to avoid accidental relationship queries, and preserve existing public response shapes unless the API contract intentionally changes.

## Change Hygiene

- Keep changes focused and preserve unrelated user work.
- Do not commit changes unless explicitly requested.
- Before finishing, run the narrowest relevant test, then `composer test`; run `vendor/bin/pint --test` when PHP files changed.
- Treat `.env` and generated storage files as local configuration. Never add secrets to source or tests.