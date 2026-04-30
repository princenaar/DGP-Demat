# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**DGP-Demat** — Dématérialisation des actes administratifs (Senegalese Ministry of Health, DGP). Web app for citizens/agents to request administrative certificates, workflow them through approval states, sign them, and verify authenticity via QR code.

Stack: Laravel 13 (PHP ^8.3) · Blade + Tailwind 3 + Bootstrap 5 + Alpine.js (Vite) · MySQL (dev via `.env`) / SQLite (tests) · Spatie Permission · barryvdh/laravel-dompdf · simplesoftwareio/simple-qrcode · yajra/laravel-datatables · laravel/boost (MCP).

Domain language is **French** — model names, routes, states, columns, and UI strings use French (`demande`, `etat`, `structure`, `justificatif`, etc.). Preserve French when adding new domain code; only code-side identifiers like helpers can be English.

## Commands

Use `php` directly (this repo's convention, not `py`, despite the global note).

```bash
# All-in-one dev environment (server + queue worker + log tail + vite) — preferred
composer dev

# Individual pieces
php artisan serve                 # HTTP server
php artisan queue:listen --tries=1
php artisan schedule:work         # local scheduler for daily digests
php artisan pail --timeout=0      # live log tail
npm run dev                        # Vite dev server
npm run build                      # production assets

# Database (SQLite file is database/database.sqlite, committed as empty)
php artisan migrate
php artisan migrate:fresh --seed  # reset + seed reference data & default users

# Tests (PHPUnit 12; testing config forces sqlite :memory: and array mail/cache/queue)
composer test                      # clears config then runs artisan test
php artisan test                   # direct
php artisan test --filter=SomeTest # single test class/method
php artisan test tests/Feature/ExampleTest.php

# Code style
./vendor/bin/pint                  # Laravel Pint formatter

# IDE helpers (regenerate _ide_helper.php after model/schema changes)
php artisan ide-helper:generate
php artisan ide-helper:models -N
```

## Architecture

### Demande = the one aggregate

Everything revolves around `App\Models\Demande` (a certificate request). It belongs to `TypeDocument`, `Structure`, `EtatDemande` (current state), and an assigned `agent` (User); it has many `FichierJustificatif` (uploaded supporting docs) and `HistoriqueEtat` (state-change log). Controllers: `DemandeController` holds most business logic; `JustificatifController` streams attached files.

### State machine (authoritative in `DemandeController::changerEtat`)

States are string constants on `EtatDemande` (seeded rows — look them up by `nom`, not by id):

```
EN ATTENTE   → RECEPTIONNEE
RECEPTIONNEE → VALIDEE | REFUSEE
VALIDEE      → DEMANDE DE COMPLEMENTS | EN SIGNATURE
EN SIGNATURE → SIGNEE | SUSPENDUE
```

The `$transitionsValides` array in `changerEtat()` is the single source of truth for allowed transitions — extend both it and `EtatDemande::labels()` when adding a state. Side effects per transition also live in this method's `switch`:

- `VALIDEE`: assigns `agent_id` from the request.
- `DEMANDE DE COMPLEMENTS`: only the assigned agent may trigger it; generates a 3-day `URL::temporarySignedRoute('demandes.edit', …)` and mails it to the requester. The public `demandes.edit` / `demandes.update` routes are reachable only via this signed URL (`signed` middleware).
- `SIGNEE`: stamps a random `code_qr`, renders the final PDF (see below), saves it under `storage/app/demandes_signees/`, and emails it.

`changerEtat` also appends every comment to `demande.commentaire` prefixed with timestamp + author — this column is an audit log, never overwrite it.

### Document-type-driven PDF rendering

`TypeDocument` has a `code` (`AFM`, `TRV`, `ADM`, `CTRV`, `ANA`) and a `champs_requis` JSON map. `DemandeController::generatePDF()` resolves the template dynamically:

```php
Pdf::loadView("demandes.pdf.{$demande->typeDocument->code}", …)
```

So **adding a new document type requires three things in lockstep**: a row in `TypeDocumentSeeder`, a Blade template at `resources/views/demandes/pdf/{CODE}.blade.php`, and matching `champs_requis` flags that the `demandes.create`/`demandes.edit` forms read to toggle fields (`date_prise_service`, `date_fin_service`, `date_depart_retraite`, `categorie_socioprofessionnelle`).

### Roles (Spatie Permission)

Four roles seeded in `RolesAndPermissionsSeeder` + `DefaultUsersSeeder`: `ADMIN`, `CHEF_DE_DIVISION`, `AGENT`, `DRH`. Route guarding examples are in `routes/web.php` (`role:ADMIN` middleware on `/users`). `DemandeController::data()` hides demandes from agents not assigned to them — replicate that pattern when adding list endpoints.

### Public vs authenticated routes

Public (no auth) — citizen-facing:
- `GET /demandes/create`, `POST /demandes` — submit a request
- `GET /demandes/verifier/{code}` — QR-code authenticity check
- `GET /demandes/{demande}/edit`, `PUT /demandes/update` — **gated by `signed` middleware only**; accessible only via the signed URL emailed during the `DEMANDE DE COMPLEMENTS` transition
- `GET /justificatifs/{id}` — note this is currently public; treat as intentional unless told otherwise

Everything else is behind `auth` (list, show, state changes, PDF view).

### Mail

All outbound notifications funnel through `App\Services\DemandeMailService::envoyer()` (wraps `DemandeNotification`) for generic messages, or dedicated `Mail\Demande*Mail` mailables (`DemandeComplementMail`, `DemandeSigneeMail`) for the two transactional cases. Prefer extending the service for new generic notifications; only create a new Mailable when you need a distinct template or attachment.

### Frontend

Entry points are `resources/js/app.js` (Vite) + Blade. Tables use yajra/laravel-datatables with server-side AJAX — list pages render an empty table and fetch rows from a `*.data` JSON endpoint (see `DemandeController::data()` for the canonical pattern, including the `actions` rawColumn that renders a partial per row).

## Conventions worth knowing

- Lookup seeded reference data by business key, not id: `EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id')`. Migrations/seeders are rerunnable via `firstOrCreate` — keep it that way.
- `date_*` columns on `Demande` are cast to `datetime`; form inputs are date-only, so read/display accordingly.
- Uploaded justificatifs go to the `local` disk under `justificatifs/`; signed PDFs under `demandes_signees/`. Never serve them via a public disk — they're streamed through controllers.
- `_ide_helper.php` at the repo root is generated; don't hand-edit. Regenerate after schema changes.
- Recent commits are terse French phrases describing a delivered feature (e.g. `Attestation de travail OK`, `Matricule hidden and not mandatory on load`). Match that style.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
