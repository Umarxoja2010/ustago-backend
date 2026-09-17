# UstaGo Avto — Backend (Phases 3–12)

Laravel 11 + MySQL + Sanctum REST API backend for the UstaGo Avto marketplace
(car owners ↔ mechanics/workshops ↔ admin). Built to match the real screens
and data shapes found in the uploaded frontend (`ustago-avto.zip`), which is
a **React app (TanStack Start)**, not Flutter.

This drop covers: project skeleton, full database schema (migrations),
Eloquent models with relationships, and **Sanctum authentication**
(register / login / logout / me / update profile). Master/Booking/Review/
Admin controllers, requests, resources, and policies come in the next phases.

## Auth API (Phase 7)

Bearer-token auth via Sanctum personal access tokens (not cookie/SPA mode —
simplest for a decoupled React frontend during dev).

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| POST | `/api/auth/register` | — | Register as `customer` or `mechanic`. Mechanic registration also creates a `master_profiles` row (`verification_status: pending`). `admin` is rejected by validation — admins are created via seeder/tinker only. |
| POST | `/api/auth/login` | — | `identifier` (email or phone) + `password`. Returns `{user, token}`. |
| POST | `/api/auth/logout` | Bearer | Revokes the current token. |
| GET | `/api/user` | Bearer | Current user, including `workshop` block if mechanic. |
| PATCH | `/api/user` | Bearer | Update name/email/phone/city/avatar/password. |

Response envelope matches your spec exactly:
```json
{ "success": true, "message": "Login successful", "data": { "user": {...}, "token": "..." } }
```
`UserResource` (`app/Http/Resources/UserResource.php`) is shaped to match the
frontend's `AuthUser` type in `src/lib/auth.tsx` 1:1 — including the nested
`workshop` object — so wiring the real API in later phases is closer to a
drop-in swap for the mock `useAuth()`.

**Decisions worth knowing about:**
- Frontend allows phone-only signup with no email; the API mirrors the
  existing mock behavior and synthesizes `{digits}@ustago.local` as a
  placeholder so the `email` column (unique, not nullable) stays populated.
- The `workshop.services` free-text field captured at signup (e.g. "Engine
  repair, Oil change") is stored as-is in `master_profiles.about` for now —
  it's a description, not real priced offerings. The mechanic sets up actual
  priced `master_services` rows afterward via the Master API (Phase 9),
  matching your rule that price is master-controlled.
- Login validates against `Auth::validate()` rather than a manual
  `Hash::check()` — same result, standard Laravel idiom.

**Tests included:** `tests/Feature/AuthTest.php` — register (customer +
mechanic, including the `master_profiles` side-effect), reject
admin-registration, login by email or phone, wrong-password rejection,
authenticated `/api/user` + logout, and unauthenticated-request rejection.
Run with `composer test` (uses in-memory SQLite; the PHP `pdo_sqlite` extension
must be enabled for tests, see `phpunit.xml`).

## Local MySQL setup

This repository includes the Laravel CLI entrypoint, MySQL database
configuration, and a ready-to-copy `.env.example`. For a local MySQL or
phpMyAdmin installation:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a database named `ustago_avto` in phpMyAdmin, then set `DB_USERNAME`,
`DB_PASSWORD`, and `DB_HOST` in `.env` if your MySQL credentials differ from
the defaults. Run the schema and seed data with:

```bash
php artisan migrate --seed
php artisan serve
```

The application expects MySQL through `pdo_mysql`. Feature tests use SQLite
independently and require `pdo_sqlite`; they never use the development
database.

## What's in this package

```
app/Models/            13 Eloquent models with relationships
database/migrations/   15 migrations (users, sanctum, and 11 domain tables)
config/                database.php, sanctum.php, cors.php
bootstrap/app.php       Laravel 11 bootstrap (JSON-only exception rendering)
artisan                 Laravel CLI entrypoint
routes/api.php          stub — /api/ping only, for now
.env.example
composer.json
```

Dependencies are installed with the normal Composer workflow. Do not copy the
project into a scratch Laravel installation; this repository already contains
its `artisan` entrypoint and Laravel 11 bootstrap.

Or, simpler — start a fresh `laravel new` project and copy these folders in.

## Database setup

```bash
# in .env: set DB_DATABASE / DB_USERNAME / DB_PASSWORD for your MySQL instance
php artisan migrate
```

## Entity-relationship summary

| Table | Purpose | Key relationships |
|---|---|---|
| `users` | all 3 roles (`customer`, `mechanic`, `admin`) in one table, `role` enum | has one `master_profile` (if mechanic) |
| `master_profiles` | one per mechanic user — workshop identity, location, verification, rolled-up `rating`/`review_count` | belongs to `user`; has many `master_services`, `working_hours`, `bookings`, `reviews` |
| `services` | global catalog (Engine repair, Oil change, ...) — powers search facets | has many `master_services` |
| `master_services` | a master's own priced offering of a catalog service (price/duration set per-master, per your "narx master tomonidan boshqariladi" rule) | belongs to `master_profile` + `service`; has many `bookings` |
| `working_hours` | 7 rows per master (Mon–Sun) | belongs to `master_profile` |
| `vehicles` | customer's cars, referenced by bookings | belongs to `user` |
| `bookings` | core transaction; snapshots price at booking time; `status` enum pending/accepted/rejected/completed/cancelled | belongs to `user`, `master_profile`, `master_service`, `vehicle`; has many `status_logs`; has one `review` |
| `booking_status_logs` | timeline events (requested → confirmed → in service → completed) shown on the customer booking screen | belongs to `booking` |
| `reviews` | one per completed booking (`booking_id` is unique) — enforces "one review per booking" from your spec | belongs to `booking`, `user`, `master_profile` |
| `favorites` | customer ↔ master, unique pair | belongs to `user`, `master_profile` |
| `messages` | lightweight chat between customer and mechanic, optionally tied to a booking | belongs to `sender`/`receiver` (both `users`), optional `booking` |
| `notifications` | per-user notifications **and** admin broadcasts (`user_id` null + `audience` set) | belongs to `user` (nullable) |

Not modeled as a real table yet: **weekly/monthly earnings charts** and
**popular services / signup charts** shown on the admin dashboard — those
read naturally as aggregate queries over `bookings`/`payouts`/`users` rather
than stored data, and will be built as report endpoints in a later phase
unless you'd rather materialize them.

## Design notes worth knowing about

- **Double-booking prevention**: not yet a DB constraint — `bookings` has an
  index on `(master_profile_id, date, time)` to make the conflict check fast,
  but the actual "is this slot free" rule (only `pending`/`accepted` block
  the slot) has to live in the booking `FormRequest`/service layer, since a
  unique index can't express "unique only among non-cancelled rows" portably
  in MySQL. Coming in the Booking API phase.
- **Rating rollups**: `master_profiles.rating`/`review_count` are
  denormalized for fast list/search queries, recalculated via
  `MasterProfile::recalculateRating()` — call this from the Review
  controller after create/hide/delete.
- **Role middleware**: `app/Http/Middleware/EnsureUserHasRole.php` is
  registered as the `role` alias — use as `->middleware('role:mechanic')` or
  `->middleware('role:mechanic,admin')` once routes exist.
- **JSON error envelope**: `bootstrap/app.php` forces all exceptions
  (validation, 404, 403, 500, ...) through JSON rendering so the frontend
  always gets your `{success, message, errors}` shape, not an HTML error page.

## Customer / Master / Booking API (Phases 8–10)

Bearer-token auth (`auth:sanctum`) + role gating (`role:customer` /
`role:mechanic`) on every route below except public browsing.

| Method | Endpoint | Auth | Role | Purpose |
|---|---|---|---|---|
| GET | `/api/masters` | — | — | Search/filter verified masters (name, city, district, service, price range, min rating), paginated |
| GET | `/api/masters/{id}` | — | — | Public master profile (services, working hours) — 404 if not verified |
| GET/POST/PATCH/DELETE | `/api/vehicles[/{id}]` | Bearer | customer | Own vehicle CRUD |
| GET | `/api/favorites` | Bearer | customer | List favorited masters |
| POST/DELETE | `/api/masters/{id}/favorite` | Bearer | customer | Favorite / unfavorite (idempotent) |
| GET/PATCH | `/api/master/profile` | Bearer | mechanic | Own workshop profile |
| GET/POST/PATCH/DELETE | `/api/master/services[/{id}]` | Bearer | mechanic | Own priced services — price/duration are always master-set |
| GET/PUT | `/api/master/working-hours` | Bearer | mechanic | Bulk-replace all 7 weekday rows in one call |
| GET | `/api/bookings` | Bearer | any | Own bookings (customer) or bookings against own workshop (mechanic); `?status=` filter |
| GET | `/api/bookings/{id}` | Bearer | any | Single booking + timeline — `BookingPolicy::view` enforces ownership |
| POST | `/api/bookings` | Bearer | customer | Create booking — snapshots price, rejects double-booked slots |
| PATCH | `/api/bookings/{id}/status` | Bearer | any | `cancelled` → customer-only (`BookingPolicy::cancel`); `accepted`/`rejected`/`completed` → mechanic-only on own workshop (`BookingPolicy::manage`) |
| PATCH | `/api/bookings/{id}/reschedule` | Bearer | customer | Move date/time — **only while `pending`** (added during Phase 13 frontend wiring, see note below) |
| GET | `/api/notifications` | Bearer | any | Own notifications + role-matching broadcasts |
| PATCH | `/api/notifications/{id}/read` | Bearer | any | Mark read |

**Booking status machine** (`app/Services/BookingService.php`):
`pending → accepted → completed`, or `pending/accepted → rejected/cancelled`.
Any other transition is rejected with a 422. Every transition appends a
`booking_status_logs` row for the customer-facing timeline; `completed`
also increments `master_profiles.jobs_count`.

**Double-booking prevention**: enforced in `BookingService::assertSlotIsFree()`
at create time — a conflict is any other booking on the same
`master_profile_id` + `date` + `time` still `pending` or `accepted`.
Cancelling/rejecting a booking frees the slot immediately (covered by
`BookingTest::test_cancelled_slot_frees_up_the_time`).

**Service ownership / master-controlled pricing**: `MasterServiceController`
scopes every read/write to `$request->user()->masterProfile`, and
`StoreMasterServiceRequest` blocks adding the same catalog service twice to
one workshop. A mechanic can never touch another workshop's services,
working hours, or bookings — enforced in the controller (`authorizeOwnership()`)
and via `BookingPolicy` for bookings specifically.

### Verification performed this session

This sandbox has no route to `packagist.org` (confirmed again this session —
`composer install` fails with `HTTP 403` on `repo.packagist.org`), so a real
`php artisan test` run against the actual Laravel framework isn't possible
here. What I could and did do instead, with `php-cli` + `composer` installed
via apt (Ubuntu's mirrors, not packagist):

- **`php -l` on every file** in `app/`, `database/`, `routes/`, `tests/` — zero syntax errors.
- **Import resolution check** — every `use App\...` statement in every file resolves to a real file at the expected path.
- **Relationship cross-check** — every Eloquent relation/method called from a controller or resource (`->masterProfile`, `->masterServices`, `->statusLogs`, `->favorites`, `->isCustomer()`, etc.) is confirmed to exist on the target model.
- **Route ↔ controller signature check** — every `{param}` in `routes/api.php` matches the corresponding type-hinted variable name in the controller method (route-model binding relies on this).
- **Duplicate-route check** — no colliding method+URI pairs.

What this does **not** catch: runtime/DB-level issues that only PHPUnit
against a real database would surface (e.g. a subtly wrong query, a
migration/model column mismatch under real MySQL). You should run
`php artisan test` locally once `vendor/` is installed — the full suite is
in `tests/Feature/`: `AuthTest`, `VehicleTest`, `MasterServiceTest`,
`WorkingHourTest`, `BookingTest`, `FavoriteTest`, `MasterSearchTest`
(29 test methods total, verified via `grep -c "public function test_"`).
Seed first with `php artisan db:seed` (adds the
service catalog + one admin account — set `ADMIN_EMAIL`/`ADMIN_PHONE`/`ADMIN_PASSWORD`
in `.env` before seeding in anything but local dev).

## Reviews & rating rollups (Phase 11)

| Method | Endpoint | Auth | Role | Purpose |
|---|---|---|---|---|
| POST | `/api/reviews` | Bearer | customer | Review own completed booking — one per booking |
| GET | `/api/masters/{id}/reviews` | — | — | Public, paginated, **hidden reviews excluded** |
| GET | `/api/master/reviews` | Bearer | mechanic | Own workshop's reviews, hidden included (so the mechanic can see what got moderated) |

Rules enforced in `ReviewController::store`:
- the booking must belong to the requesting customer (403 otherwise)
- the booking must be `completed` (422 otherwise)
- one review per booking — checked via `$booking->review` and backed by the
  DB-level unique constraint on `reviews.booking_id` as a second line of defense

On successful creation, `MasterProfile::recalculateRating()` (added back in
Phase 3–6) re-averages `rating`/`review_count` from non-hidden reviews —
covered by `ReviewTest::test_customer_can_review_own_completed_booking_and_rating_rolls_up`.
Hiding/moderating a review is an admin action and lands in Phase 12.

### Verification performed this session

Same constraints as before — no `packagist.org` access, so no live
`php artisan test`. Ran the full static-check suite again on top of the new
files: `php -l` (zero syntax errors across every file in the project, not
just the new ones), import resolution, relation cross-check
(`Review::booking/user/masterProfile`, `MasterProfile::reviews`/`recalculateRating`,
`Booking::review`), route-param ↔ controller-signature match for the 3 new
routes, and a project-wide duplicate-route scan. All clean.
`tests/Feature/ReviewTest.php` adds 6 more test methods (35 total across the
suite) — rating rollup, non-completed rejection, duplicate rejection, cross-
customer rejection, hidden-excluded-from-public, mechanic-sees-own-hidden.

## Admin API + reports (Phase 12)

Everything below is gated by `auth:sanctum` + `role:admin`, prefixed `/api/admin`.

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/users` | List users — filter `role`, `status`, `q` (name/email/phone), paginated |
| GET | `/users/{id}` | Single user |
| PATCH | `/users/{id}/status` | Suspend/reactivate — **blocked against admin targets** (403), to prevent accidental lockout |
| GET | `/masters` | List workshops — filter `verificationStatus`, `q`, paginated, includes owner info |
| GET | `/masters/{id}` | Single workshop, any verification status (unlike the public endpoint) |
| PATCH | `/masters/{id}/verification` | Set `pending`/`verified`/`rejected`/`suspended` — this is what makes a workshop publicly searchable |
| GET/POST/PATCH/DELETE | `/services[/{id}]` | Global service catalog CRUD — delete is **blocked with 409** if any mechanic currently offers that service (would silently strip their pricing otherwise) |
| GET | `/bookings[/{id}]` | All bookings across every workshop — filter `status`, `masterProfileId`, `userId` |
| GET | `/reviews` | All reviews including hidden — filter `masterProfileId`, `hidden` |
| PATCH | `/reviews/{id}/hide` | Toggle visibility — triggers `recalculateRating()` since hidden reviews are excluded from the average |
| POST | `/notifications/broadcast` | Platform announcement to `all` / `customers` / `mechanics` (shows up in the normal `/api/notifications` feed for matching users) |
| GET | `/reports/overview` | Headline counts: users by role/status, masters by verification status, bookings by status + completed revenue, review counts + platform average rating |
| GET | `/reports/signups?days=30` | Daily signup counts split by role — feeds the admin dashboard's signup chart |
| GET | `/reports/revenue?days=30` | Daily completed-booking revenue — feeds the revenue chart |
| GET | `/reports/top-services?limit=5` | Most-booked catalog services by completed-booking count |

This closes out the gap flagged back in Phase 3–6: *"weekly/monthly earnings
charts and popular services / signup charts... will be built as report
endpoints in a later phase"* — that's `/reports/*` above. They're computed
on the fly from `bookings`/`users`/`reviews` rather than stored, so there's
no risk of the numbers drifting from reality.

**Two safety guards worth knowing about**, both enforced in the controller,
neither requested explicitly but both defensible given the spec's "admin
manages everything" scope:
- an admin can't suspend/reactivate *another admin* via `/users/{id}/status`
- a catalog service can't be deleted while any mechanic still has it priced

### Verification performed this session

Same approach as every phase — no `packagist.org` access in this sandbox, so
no live `php artisan test`. Ran, on the whole project (not just new files):
`php -l` syntax check (clean), import-resolution check (clean), a proper
prefix-aware route-collision scan across all 45+ named routes (previous
phases used a naive string grep that flagged same-literal routes under
different prefixes as false positives — this session's checker actually
tracks nested `prefix()` groups and confirmed zero true collisions), and
relationship cross-checks for every new controller.

Caught and fixed two real bugs during review before they shipped:
`ReportController::signups()` and `::revenue()` both called `->count`/`->total`
on a possibly-`null` lookup result for dates with zero rows (a day with no
signups or no completed bookings) — fixed with `?->` null-safe access on
both. Also added `tests/Feature/AdminTest.php` with 10 test methods covering
every endpoint above (45 test methods total across the suite as of this
phase, verified by grepping `public function test_` — not eyeballed).

**Post-Phase-12 addendum**: while wiring the React frontend (Phase 13), it
turned out the UI has a "reschedule booking" control with no backend
endpoint behind it — Phase 10 only built status transitions, not a
date/time change. Rather than ship dead UI, added
`PATCH /api/bookings/{id}/reschedule` (customer-only, restricted to
`pending` bookings — once a workshop has accepted, the customer cancels and
rebooks instead of silently moving a confirmed slot), reusing the same
double-booking guard as booking creation. 3 more tests in `BookingTest.php`
cover it — 48 test methods total after that addendum.

**Post-Phase-12 addendum #2**: continuing Phase 13 into the mechanic-side
frontend surfaced two more gaps, both now fixed:

- `GET /api/services` — a mechanic adding a priced service needs to browse
  the global service catalog first, but the only catalog-listing endpoint
  that existed was admin-only (`GET /api/admin/services`). Added a public,
  read-only `ServiceController::index` reusing the existing `ServiceResource`.
  Catalog *mutation* stays admin-only — this is listing only.
- `GET /api/master/earnings/weekly` and `GET /api/master/earnings/monthly` —
  the mechanic dashboard and earnings screen show revenue charts
  (`weeklyEarnings`/`monthlyEarnings` in the old mock). These are
  **calculated revenue** from the mechanic's own completed bookings' prices,
  grouped by day (current Mon–Sun week) or month (last 6 months) —
  `MasterEarningsController`. Deliberately **not** a payout/disbursement
  system: no endpoint here or anywhere creates a `Payout` record, because no
  real payout workflow (who pays out, on what schedule, via what rail) was
  ever defined. The `payouts` table/model from Phase 3–6 stays unused; the
  frontend's "Payout history" section now shows an honest empty state
  instead of fabricated data.

4 more tests (`MasterEarningsTest`, `ServiceCatalogTest`) — **52 test
methods total** now.

You should still run the real suite locally once `vendor/` is installed —
the date-series-filling logic in the reports especially deserves a real DB run.

## Phase 14 — event-driven booking/review notifications

Closes a gap flagged throughout Phase 13: the notification screens (both
customer and mechanic) were fully wired to `GET /api/notifications`, but
nothing in the booking or review lifecycle ever created a `Notification`
row — only admin broadcasts did. In practice both screens stayed empty for
real usage. Fixed by adding notification creation at each real lifecycle
event, no new endpoints or schema:

| Event | Who gets notified | Where |
|---|---|---|
| Booking created | Mechanic ("New booking request") | `BookingService::create()` |
| Booking accepted / rejected / completed | Customer | `BookingService::transition()` |
| Booking cancelled | Mechanic | `BookingService::transition()` |
| Booking rescheduled | Mechanic | `BookingService::reschedule()` |
| Review submitted | Mechanic ("New review received") | `ReviewController::store()` |

The accepted/rejected/completed → notify-customer and cancelled →
notify-mechanic split relies on the fact that `transition()` is only ever
reached two ways: the mechanic-scoped route (`BookingPolicy::manage`, which
only allows accepted/rejected/completed) or the customer-scoped route
(`BookingPolicy::cancel`, which only allows cancelled) — so the actor is
always inferable from the target status, no extra parameter needed. The
admin override endpoint (`Admin\BookingController::updateStatus`) bypasses
`transition()` entirely and does **not** trigger these notifications — an
admin force-changing a booking for dispute resolution isn't the same event
as a normal user-flow transition, and layering notification logic onto an
override path felt like the wrong place for it.

7 new tests added (`BookingTest` ×6, `ReviewTest` ×1) — **65 test methods
total** now. Each test asserts both the positive case (the right user got
notified) and a negative case (the *other* user did not) — verified with
`assertDatabaseHas`/`assertDatabaseMissing` since there's no way to run
these live in this sandbox (see below).

## Next phases (not in this package yet)

8–10. ~~Customer / Master / Booking API~~ — done, see above
11. ~~Reviews & rating rollups~~ — done, see above
12. ~~Admin API + reports~~ — done, see above
13. ~~React (TanStack Start) API client + wiring~~ — done, see `/home/claude/ustago-avto/PHASE13-STATUS.md` (frontend repo, not part of this zip). Customer, mechanic, and admin all wired; a handful of endpoints in this backend were added mid-phase to support real screens (reschedule, service catalog listing, mechanic earnings, admin booking override, admin notification history, a couple of resource field fixes) — all documented in this README's Phase 12 addenda above and in the frontend status doc.
14. ~~Event-driven notifications~~ — done, see above. Remaining documented gaps: messages feature has no UI consumer (not built), mechanic payout history and admin platform settings both need a product decision before they're engineering tasks, mechanic-detail SEO tags are still generic.
15–16. More tests, deployment

**On live verification**: `composer install` still fails in this sandbox —
`repo.packagist.org` returns HTTP 403, confirmed again this phase. No
`vendor/`, no live `php artisan test`, no Laravel boot, no real MySQL run.
Every test added across every phase of this project has been verified by
static means only (`php -l`, import resolution, route-collision checks,
and careful manual tracing of the assertions against the actual code) —
never actually executed. Run the full suite for real before trusting any
of it in production.
