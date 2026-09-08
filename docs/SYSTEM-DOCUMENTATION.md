# JerseyConnect — System Documentation

This document compiles the technical facts of the **JerseyConnect** system as implemented in this
repository. It is written to feed directly into the capstone manuscript chapters identified in
`CAPSTONE-FORMAT-DISCUSSION.docx` — mainly **Chapter 3 (Technical Background)** and
**Chapter 4 (Methodology, Results, and Discussion)**. Section numbers below mirror the numbering in
that format guide so content can be copied across with minimal rework.

Everything here is derived from the actual codebase (`composer.json`, `package.json`,
`database/migrations`, `app/Models`, `app/Http/Controllers`, `routes/`, `resources/js`) as of
2026-09-04. Where the manuscript needs information the code cannot provide (hardware specs used for
testing, respondent counts, survey results, ISO 25010 scores), a **`[TO FILL]`** placeholder marks it —
these are research/evaluation activities that happen outside the codebase.

---

## What JerseyConnect Is (for framing Chapter 1 context)

JerseyConnect is a web-based **custom jersey/uniform ordering and design-request management system**.
Clients browse a catalog of jersey templates (by sport), submit a custom design request (team name,
colors, logo, quantity), pay a GCash down payment with proof-of-payment upload, discuss the design with
staff through an in-app chat thread, and track the resulting order through a
production → shipping → delivery pipeline with courier assignment and a map-based delivery address.
Admin staff manage the jersey catalog, review/approve design requests and payments, assign couriers,
configure GCash payment details, message clients, manage client accounts, and view a sales dashboard.

---

## Chapter 3 — Technical Background

### 3.1 Software Requirements

| Component | Requirement |
|---|---|
| Server-side language runtime | PHP **^8.3** |
| Backend framework | Laravel Framework **^13.8** |
| Package manager (PHP) | Composer |
| Package manager (JS) | npm (`.npmrc` present) |
| Node.js | Required to run Vite build (version not pinned in repo — `[TO FILL]`: record the Node version used) |
| Web server (production) | Nginx/Apache or `php artisan serve` for local dev |
| Database server | MySQL (see §3.5) |
| Session/Cache/Queue driver | Database driver (configured in `.env.example`) |

### 3.2 Hardware Requirements

The repository does not encode hardware requirements — these depend on deployment target and testing
environment and should be documented from the actual development/deployment machine and any load
testing performed.

- **Development machine:** `[TO FILL]` (CPU, RAM, storage used during development)
- **Deployment/server target:** `[TO FILL]` (e.g., shared hosting, VPS specs, cloud tier)
- **Minimum client requirements:** modern browser with JavaScript enabled, internet connection sufficient
  to load a Vite-bundled SPA and interact with a Leaflet map (delivery address picker).

### 3.3 Programming Languages

- **PHP ^8.3** — backend application logic (Laravel controllers, models, middleware).
- **TypeScript ^5.6.3** (`strict: true`) — frontend application logic.
- **Vue 3 SFCs (`.vue`)** — frontend UI components and pages.
- **Blade** — minimal use, limited to Laravel's root app template (Inertia handles page rendering after
  that; there is no separate Blade view layer for application pages).
- **SQL** — via Eloquent migrations/query builder (MySQL dialect).

### 3.4 Development Tools

| Tool | Purpose |
|---|---|
| Vite ^8.0.0 + `laravel-vite-plugin` ^3.1 + `@vitejs/plugin-vue` | Frontend build/bundling, HMR |
| Tailwind CSS ^3.2.1 + `@tailwindcss/forms` | Utility-first styling, custom theme (colors: ink/paper/panel/accent/cobalt; fonts: Inter, Bebas Neue, IBM Plex Mono) |
| `vue-tsc` | TypeScript type-checking for Vue SFCs |
| Laravel Pint | PHP code style / formatting |
| Composer | PHP dependency management |
| Pest PHP ^4.7 (`pestphp/pest`, `pestphp/pest-plugin-laravel`) | Automated testing (PHPUnit-compatible) |
| `fakerphp/faker` | Test/seeder data generation |
| Laravel Tinker | REPL for debugging/data inspection |
| Ziggy (`tightenco/ziggy` ^2.0) | Exposes Laravel named routes to the Vue/TS frontend |
| `composer dev` script | Runs `artisan serve` + `queue:listen` + `npm run dev` concurrently for local development |

### 3.5 Database Technologies

- **RDBMS:** MySQL (`.env.example`: `DB_CONNECTION=mysql`, `DB_DATABASE=printcode_db`).
- **ORM:** Laravel Eloquent.
- **Migrations:** 13 migration files under `database/migrations/`, version-controlled schema.
- Sessions, cache, and queue jobs are also persisted to the database (not Redis) per `.env.example`.

### 3.6 Network Architecture

- **Client–server model over HTTP(S):** browser (Vue 3 SPA shell) communicates with the Laravel backend
  via Inertia.js requests (XHR requests that return page-component + prop payloads rather than raw JSON
  or full page reloads).
- **No separate REST/JSON API** is exposed for the SPA itself — there is no `routes/api.php` in use;
  Inertia serves as the single request/response bridge between Vue pages and Laravel controllers.
- Static/user-uploaded assets (jersey images, payment proofs, chat attachments, GCash QR code) are served
  from Laravel's public storage disk (`Storage::disk('public')`).
- `[TO FILL]`: production network diagram (load balancer, HTTPS termination, CDN if any) once a hosting
  environment is finalized.

### 3.7 Software Architecture

- **Pattern:** Laravel's standard **MVC**, with **Inertia.js** replacing the traditional
  Blade view layer — controllers call `Inertia::render()` and pass server-fetched data as props
  directly into Vue "page" components, giving SPA-like navigation without a separate REST API.
- **Monolithic single-application** deployment (backend and frontend build ship together; not
  microservices).
- Directory-level separation by **role/module**: controllers, routes, and Vue pages are each grouped
  into `client/*`, `admin/*`, and `Auth/*` namespaces (see §4.2/§4.3 route and page breakdown).

### 3.8 Security Features

- **Authentication:** Laravel Breeze (`laravel/breeze` ^2.4) — session/cookie-based auth using Laravel's
  built-in `Auth` facade.
- **Role-based access control (RBAC):** `users.role` enum column (`admin` | `client`), enforced by two
  custom route middleware:
  - `EnsureUserIsAdmin` — redirects non-admins away from `/admin/*` routes to the client home.
  - `EnsureUserIsClient` — redirects admins away from `/client/*` routes to the admin dashboard, and
    blocks admin emails from client-only guest actions.
  - Registered as the `admin` / `client` route middleware aliases seen in `routes/web.php`.
- **Account status control:** `users.status` enum (`active` | `inactive`) exists in the schema for
  disabling accounts — `[TO FILL]`: confirm/describe exactly where this is enforced (e.g., login
  controller) if the manuscript will claim this as an implemented control.
- **Password hashing:** Laravel default bcrypt hashing (`BCRYPT_ROUNDS=12`); the `User` model casts
  `password` as `'hashed'`.
- **CSRF protection:** Laravel's default web middleware group (implicit, not overridden).
- **Sensitive-field masking:** `User` model uses a `#[Hidden(['password','remember_token'])]` attribute
  so these fields never serialize into Inertia props sent to the browser.
- **Authorization (ownership checks):** manual per-resource checks in controllers rather than Laravel
  Policies — e.g., `DesignRequestController::authorizeOwner()` and `OrderController::authorizeOwner()`
  throw a 403 if the authenticated user does not own the resource.
- **Rate limiting:** `throttle:api` middleware on state-changing client/admin routes;
  `throttle:6,1` on email verification/notification routes (Breeze default).
- **File upload validation:** e.g., payment proof images validated as `required|image|max:4096` before
  storage.
- **Input validation:** inline Form Request-style validation in controllers, including a Philippine
  mobile number regex (`^09\d{9}$`) for GCash numbers.
- No custom field-level encryption beyond Laravel's default `APP_KEY`-based facilities was found in use.

### 3.9 System Architecture

**High-level component diagram (textual — render as a proper figure in the manuscript):**

```
                     ┌─────────────────────────────┐
                     │        Client Browser         │
                     │  Vue 3 SPA (Inertia pages)    │
                     │  + Leaflet map (address pick) │
                     └───────────────┬───────────────┘
                                     │ HTTP(S) — Inertia requests
                     ┌───────────────▼───────────────┐
                     │        Laravel Application     │
                     │  Routes → Middleware (auth,     │
                     │  admin/client) → Controllers    │
                     │  → Eloquent Models              │
                     └───────┬───────────────┬─────────┘
                             │               │
                 ┌───────────▼───┐   ┌───────▼─────────┐
                 │  MySQL Database │   │ Public Storage   │
                 │  (Eloquent ORM) │   │ (images, proofs, │
                 └─────────────────┘   │  QR, attachments)│
                                       └───────────────────┘
```

**Modules by role** (used again in §4.3 for the Use Case/DFD write-up):

- **Client module:** jersey catalog browsing, design request submission & tracking, GCash payment +
  proof upload, order tracking, in-app messaging, profile management.
- **Admin module:** dashboard/analytics, jersey template CRUD, design request review & payment
  approval, order status management, courier management, GCash settings, messaging, client account
  management.
- **Auth module:** registration, login, password reset, email verification (Breeze-generated).

---

## Chapter 4 — Methodology, Results, and Discussion

### 4.1 Requirements Analysis

**Functional Requirements** (derived from implemented controllers/routes — restate in manuscript
voice, e.g. "The system shall..."):

- FR1. The system shall allow clients to browse jersey templates filtered by sport.
- FR2. The system shall allow clients to submit a custom design request (team name, colors, font
  style, logo upload, estimated quantity, notes) against a chosen template.
- FR3. The system shall allow clients to submit GCash payment proof (reference number, screenshot)
  for a design request's down payment.
- FR4. The system shall allow clients and admins to exchange messages on a per-design-request thread,
  with read/unread tracking.
- FR5. The system shall allow admins to review, approve, request revision on, or cancel design
  requests, and to approve/reject down payments.
- FR6. The system shall automatically generate an order (with a unique order number) once a design
  request is approved and paid.
- FR7. The system shall allow clients to set/update a delivery address, including map-based
  latitude/longitude selection.
- FR8. The system shall allow admins to progress an order through a defined status pipeline
  (processing → in production → ready for delivery → shipped → delivered → completed).
- FR9. The system shall allow admins to record courier and shipment receipt details per order.
- FR10. The system shall allow admins to manage the jersey template catalog (CRUD).
- FR11. The system shall allow admins to manage GCash account details and QR code shown to clients.
- FR12. The system shall allow admins to manage client accounts (view/update).
- FR13. The system shall provide admins a dashboard with sales/order analytics.
- FR14. The system shall enforce role-based access so clients and admins only reach their respective
  modules.

**Non-functional Requirements** (framework/config-backed — quantify targets for the manuscript where
marked `[TO FILL]`):

- NFR1. **Security** — role-based authorization, hashed passwords, CSRF-protected forms, rate-limited
  state-changing endpoints (implemented; see §3.8).
- NFR2. **Usability** — responsive Tailwind-based UI; `[TO FILL]`: target usability score / method
  (e.g., SUS survey) if evaluated.
- NFR3. **Performance** — `[TO FILL]`: target page load time / concurrent user count if load-tested.
- NFR4. **Reliability/Availability** — `[TO FILL]`: uptime target if deployed to a hosting environment.
- NFR5. **Maintainability** — TypeScript strict mode, PHP 8.3 attributes-based models, Pest test
  suite, Laravel Pint formatting — supports code consistency and refactor safety.
- NFR6. **Compatibility** — modern evergreen browsers (Chrome/Edge/Firefox); `[TO FILL]`: list any
  formally tested browser/OS matrix.

### 4.2 Requirements Documentation

**User Requirements** — two user roles are implemented: **Client** and **Admin** (see `users.role`
enum). Each role's needs map to the functional requirements above (clients: FR1–FR4, FR7; admins:
FR5, FR6, FR8–FR13; both: FR14).

**System Requirements** — see §3.1–§3.2.

**Use Case Description** — primary actors and their key use cases:

- **Client:** Register/Login, Browse Jersey Templates, Submit Design Request, Upload Payment Proof,
  Message Admin, Track Order, Update Delivery Address, Update Profile.
- **Admin:** Login, Manage Jersey Templates, Review Design Requests, Approve/Reject Payments, Manage
  Orders & Status, Manage Couriers, Manage GCash Settings, Message Clients, Manage Client Accounts,
  View Dashboard.

`[TO FILL]`: render these as a formal Use Case Diagram (actors + ellipses) for the manuscript figure.

### 4.3 System Design

The following are code-verified inputs for the corresponding manuscript diagrams — draw the actual
figures (flowcharts, DFD, ERD, UML) in your diagramming tool of choice using this data.

**Entity Relationship Diagram (ERD) — entities and relationships:**

| Entity (table) | Key Relationships |
|---|---|
| `user_infos` | 1—1 with `users` |
| `users` | belongsTo `user_infos`; hasMany `design_requests`, `orders`, `messages` |
| `jerseys` | referenced by `design_requests` as the template |
| `design_requests` | belongsTo `users`, belongsTo `jerseys` (as template); hasOne `orders`; hasOne `message_threads` |
| `orders` | belongsTo `design_requests`, belongsTo `users`; hasOne `addresses`; hasOne `courier_receipts` |
| `addresses` | belongsTo `orders` (1—1, includes lat/lng) |
| `couriers` | hasMany `courier_receipts` |
| `courier_receipts` | belongsTo `orders` (1—1), belongsTo `couriers` |
| `gcash_settings` | singleton (no FK — one active row via `firstOrCreate`) |
| `message_threads` | belongsTo `design_requests` (1—1); hasMany `messages` |
| `messages` | belongsTo `message_threads`, belongsTo `users` |

**Order status flow (state machine)** — `Order` model defines a `STATUS_FLOW` constant with a
`nextAllowedStatuses()` helper enforcing this linear pipeline:

```
processing → in_production → ready_for_delivery → shipped → delivered → completed
```

**Design request status flow:**

```
pending_review → in_discussion → revision_requested → waiting_for_down_payment
→ pending_down_payment_review → approved  (or → cancelled at applicable points)
```

**Database Design** — see §4.3 ERD table above and the migration list; this can be expanded into a
full schema diagram (tables, columns, data types, keys) directly from `database/migrations/`.

**Interface Design** — implemented Vue pages, organized by role (use as the screen inventory for
Interface Design and later for §4.8 screenshots):

- *Client pages:* Home (catalog/request design), Design (request tracking + GCash payment), Orders
  (order tracking), Chat (messaging), Profile.
- *Admin pages:* Dashboard (analytics), Jersey (template CRUD), Design (request review), Orders,
  Shipping, Sales, Couriers, Gcash, Messages, Users, Profile.
- *Auth pages:* Login, Register, Forgot Password, Reset Password, Confirm Password, Verify Email.
- *Shared components:* reusable form inputs, `Modal`, `Table`, `Dropdown`, nav links, `ImageUpload`,
  and `LocationPicker` (Leaflet-based map picker for delivery coordinates).

### 4.4 Software Development

- **Coding:** PHP 8.3 (Laravel 13, attribute-based Eloquent models) on the backend; TypeScript + Vue 3
  SFCs on the frontend; Inertia.js as the bridge (no hand-rolled REST API layer to maintain in
  parallel).
- **Integration:** Vite bundles and hot-reloads the frontend against the Laravel backend during
  development (`composer dev` runs both concurrently with the queue listener).
- **Deployment:** `[TO FILL]` — document actual hosting/deployment steps once a target environment
  (e.g., shared hosting, VPS, cloud platform) is chosen; Laravel's standard production build is
  `npm run build` + standard Laravel deployment (config caching, migrations, storage link).

### 4.5 Testing

Current automated test coverage (`tests/`, using Pest/PHPUnit):

- `Feature/Auth/` — AuthenticationTest, EmailVerificationTest, PasswordConfirmationTest,
  PasswordResetTest, PasswordUpdateTest, RegistrationTest (full Breeze auth flow coverage).
- `Feature/ProfileTest.php` — profile update/delete coverage.
- `Feature/ExampleTest.php`, `Unit/ExampleTest.php` — default Laravel scaffold stubs (not
  domain-specific).

**Gap to disclose honestly in the manuscript:** there are currently no automated feature tests for the
core domain modules (jersey catalog, design requests, orders, messaging, couriers, GCash settings) —
only authentication and profile flows are covered. If the manuscript claims Unit/Integration/System
testing for these modules, that testing should be performed (manually or via new Pest tests) before
being reported as completed. Recommended before defense:

- [ ] Feature tests for design request submission, status transitions, and payment approval.
- [ ] Feature tests for order status pipeline transitions and courier receipt recording.
- [ ] Feature tests for role-based access (client blocked from `/admin/*` and vice versa).
- [ ] Manual **User Acceptance Testing** with actual client/admin users — `[TO FILL]`: results.
- [ ] **Security Testing** — verify ownership checks, rate limits, and RBAC middleware behave as
  designed under adversarial input — `[TO FILL]`: results.
- [ ] **Performance Testing** — `[TO FILL]`: results if conducted.

### 4.6–4.9

- **4.6 Prototype Description / 4.8 Implementation Results (screenshots, modules):** use the page
  inventory in §4.3 "Interface Design" as the shot list; capture from a running instance
  (`composer dev`).
- **4.7 Implementation Plan (deployment/training/maintenance):** `[TO FILL]` — depends on chosen
  hosting and rollout plan.
- **4.9 System Evaluation (ISO 25010, respondents, statistical treatment, results):** `[TO FILL]` —
  this is a research activity (instrument design, respondent sampling, data collection) outside what
  the codebase can supply.

---

## Appendix Cross-References

This documentation directly supports:

- **Appendix H** (System Screenshots) — via the page inventory in §4.3.
- **Appendix K** (Database Schema) — via §4.3 ERD table and `database/migrations/`.
- **Appendix J** (Source Code) — this repository.

## Open Items Requiring Manuscript-Author Input

1. Hardware specs (dev machine + deployment target) — §3.2.
2. Node.js version used for builds — §3.1.
3. Non-functional requirement targets (performance, uptime, usability score) — §4.1.
4. Deployment/hosting plan — §4.4, §4.7.
5. Domain-specific automated tests and their results — §4.5.
6. System evaluation instrument, respondents, and statistical results (ISO 25010) — §4.9.
