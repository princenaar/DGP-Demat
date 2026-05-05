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


<claude-mem-context>
# Memory Context

# [DGP-Demat] recent context, 2026-04-30 11:33pm UTC

Legend: 🎯session 🔴bugfix 🟣feature 🔄refactor ✅change 🔵discovery ⚖️decision 🚨security_alert 🔐security_note
Format: ID TIME TYPE TITLE
Fetch details: get_observations([IDs]) | Search: mem-search skill

Stats: 50 obs (17 216t read) | 446 549t work | 96% savings

### Apr 29, 2026
85 4:43p 🔵 LayoutPartialsTest Assertions Diverged from Template After Commit 245875e
86 4:44p 🔴 Footer Copyright Bar Restored Portal Tagline to Fix Failing LayoutPartialsTest
87 " ✅ DGP-Demat Full Test Suite Green After Footer Fix
88 4:46p 🔵 DGP-Demat Repository State Before Phase 1 Upgrade
89 " ✅ Phase 0 Changes: Ministry Rename and Mail Cleanup in DGP-Demat
90 " ⚖️ Phase 1 Upgrade Decisions: Branching, Seeding, Audit, and Schema Strategy
S14 User resolved all 5 Phase 1 blockers; execution plan created with task queue for Phase 0 audit and branch setup (Apr 29, 4:46 PM)
S15 Phase 0 commit landed on feature/upgrade-v2-phase-0; awaiting user decisions on package-lock.json, build.zip gitignore, and PR push approval (Apr 29, 4:53 PM)
91 4:54p ⚖️ Phase 1 Full Database Migration Task Plan Defined
92 " 🔵 DemandeNotification.php Contains No English Fragments
93 " 🔵 Phase 0 Audit: Email Templates Clean, App Locale Confirmed French
94 4:55p 🔵 Phase 0 Audit: Breeze Auth Views Use Translation Keys, No Raw English Text
95 " 🔵 Auth Views Use __() Universally; Legacy auth/passwords/ Subdirectory Also Exists
96 " 🔵 All 21 Auth View Translation Keys Present in fr.json — Zero Gaps
97 4:56p 🔵 Stray Files Inventory: package-lock.json Updated, outputs/ and build.zip Are Artifacts
98 " 🔵 Artifact Paths Already Covered by .gitignore — No Commit Risk
99 " ✅ Feature Branch feature/upgrade-v2-phase-0 Created
100 " 🔵 Test Suite Green on feature/upgrade-v2-phase-0 — 65 Tests, 218 Assertions
101 " 🔴 Pint Fixed Line Endings in DemandeComplementMail.php and DomainObjectsTest.php
102 " ✅ Phase 0 Files Staged for Commit — package-lock.json Deliberately Excluded
S16 Fix broken mobile navigation in DGP-Demat: public homepage nav too cramped, admin panel shows no nav at all — implement hamburger navigation (Apr 29, 4:58 PM)
103 4:59p 🔵 .gitignore Confirmed: No Pattern Covers public/build.zip
### Apr 30, 2026
104 7:20a 🔵 DGP-Demat Laravel Project Structure Identified
105 " 🔵 welcome.blade.php Homepage Content Audited Before Redesign
106 " 🔵 CSS Setup Uses Tailwind v4 Import Syntax
107 " 🟣 Homepage Redesigned with Modern UI and Email Delivery Messaging
108 7:23a 🔵 DGP-Demat Uses Tailwind v3 with Senegalese Government Design System
109 " 🔵 welcome.blade.php Extends layouts.public with Institutional Senegalese Government Layout
110 " 🔵 "Espace agent" Exists in Three Locations Across the Public-Facing Homepage
111 " 🔵 Bootstrap 5 Not Actually Used on Homepage Despite CLAUDE.md Stack Listing
112 " ⚖️ Homepage Redesign Scoped to welcome.blade.php @section('content') + footer.blade.php
113 " ⚖️ Homepage Redesign Implementation Plan Written with Fraunces Serif Font and 5-Section Layout
114 10:29p 🔵 Mobile Navigation Missing Hamburger Menu in DGP-Demat
115 10:30p 🔵 Root Cause: Admin Layout Uses Inline Nav with `hidden md:flex`, Ignores Existing Mobile-Ready navigation.blade.php
116 " ⚖️ Hamburger Nav Fix Plan: Refactor Both Layouts Away from PHP String Nav Injection
118 " ⚖️ Implementation Plan Written: Hamburger Nav via Dual `$nav`/`$navMobile` Slot Pattern in institutional-header
119 " 🔵 Mobile Nav Bugs Identified in DGP-Demat Layouts
120 " ⚖️ Hamburger Nav Plan: Centralize Responsive Toggle in institutional-header Partial
117 10:31p 🔵 LayoutPartialsTest Only Covers Footer — Nav Refactor Won't Break Existing Tests
121 10:43p 🔴 institutional-header.blade.php Refactored with Alpine Hamburger Toggle
122 " 🔴 Public Layout Nav Fixed for Mobile with $publicNavMobile Drawer Variable
123 " 🔴 Authenticated Layout Fixed: $authNavMobile Added to Admin Panel
124 " 🔵 DGP-Demat Tailwind Config Uses Custom Brand Color Tokens
125 10:44p 🔴 ink-100 Color Token Added to Tailwind Config
126 " ✅ Full Test Suite Passes After Hamburger Nav Implementation
S17 Logo appeared too large after hamburger nav refactor — reverted logo size to original h-16 (Apr 30, 10:44 PM)
127 10:49p 🔴 Logo Size Reverted — Responsive Shrink Removed
S18 Logo appeared huge after hamburger nav changes — root cause was stale compiled CSS, not the class; h-12 md:h-16 restored as final logo sizing (Apr 30, 10:49 PM)
128 10:51p ✅ Logo Size Re-set to h-12 md:h-16 (Responsive Sizing Restored)
S19 Style Connexion/Déconnexion as distinct buttons; decision on whether to keep or hide the login link; rename "Connexion" to "Espace administrateur" (Apr 30, 10:51 PM)
S21 Login page navbar broken on mobile — decision needed: remove navbar or fix it; simplify login page; add back-to-home navigation (Apr 30, 10:56 PM)
129 10:57p 🟣 "Connexion" Renamed to "Espace administrateur" and Styled as Green Pill Button
130 " 🟣 Déconnexion Styled as Red Outline Pill Button in Authenticated Layout
S22 Login page /login navbar broken on mobile — recommendation: remove navbar, make logo clickable, add back-to-home link; awaiting user confirmation (Apr 30, 10:58 PM)
S20 Style Connexion/Déconnexion as distinct buttons and rename "Connexion" to "Espace administrateur" — completed and verified (Apr 30, 10:58 PM)
131 11:05p 🔵 Login Page Uses Separate guest.blade.php Layout with Broken Mobile Nav
132 11:08p 🟣 Institutional Header Logo Made Clickable Home Link; Hamburger Button Conditionally Rendered
134 " ✅ Full Test Suite Passes After Login Page Simplification
133 11:09p ✅ Login Page (guest.blade.php) Simplified: Nav Removed, Back-to-Home Link Added
S23 Simplify login page, remove navbar, add back-to-home link, make logo clickable on all pages (Apr 30, 11:09 PM)
**Investigated**: Login page structure and how institutional-header is used across three layouts (public, app, guest). Discovered guest layout passed its own nav that was never updated for mobile responsiveness, causing a broken hamburger experience.

**Learned**: By making the hamburger button and mobile drawer conditionally rendered (only when `$navMobile` is non-empty), the institutional-header partial can serve multiple use cases: full nav on public/app layouts, clean minimal header on login. The logo-as-home-link pattern uses Alpine group styling for hover effects, signaling clickability without visual clutter. Conditional rendering prevents orphaned UI elements.

**Completed**: - `institutional-header.blade.php`: Logo + ministry text wrapped in `<a href="{{ url('/') }}">` with `group` class and `aria-label="Retour à l'accueil"`; ministry text gains `group-hover:text-senegal-green transition-colors` on hover. Hamburger button wrapped in `@if($hasMobileNav)` — only renders when drawer has content. Mobile drawer also conditional via `@if($hasMobileNav)`.
    - `guest.blade.php`: Removed `$guestNav` block entirely (3 nav links). Institutional header called with no parameters. Added "← Retour à l'accueil" link with left-arrow SVG above the login form card. Login card spacing adjusted to `mt-3` (tighter, since link above).
    - Full test suite: 83 tests pass, 286 assertions, 0 failures. Pint: clean.

**Next Steps**: Work complete for this feature. User must rebuild assets (`npm run dev` or `npm run build`) so Tailwind picks up the new utility classes (`group-hover:text-senegal-green`, `transition-colors`, `inline-flex`, `mr-1`, etc.) and the page renders correctly.


Access 447k tokens of past work via get_observations([IDs]) or mem-search skill.
</claude-mem-context>