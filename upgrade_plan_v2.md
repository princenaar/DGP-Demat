# DGP-Demat Upgrade v2 — Implementation Plan

## Context

DGP-Demat ships its first wave of features (citizen demande submission, hard-coded state machine, manual transitions, 5 seeded document types, Bootstrap+Tailwind admin) but the backend is rigid: states and types are constants in PHP, there is no dashboard, no scheduler, no per-type workflow, no auto-dispatch, the categorie socio-professionnelle is free text, and three PDF templates still carry the previous ministry name. This plan turns the demande aggregate into a **data-driven workflow** sized against the RACI matrix in `outputs/raci_matrice/matrice_procedures_etats_roles.xlsx`, lets the DRH division add new procedures without code changes, automates the obvious validations, and surfaces work-to-do via a dashboard and daily digest mails.

User-confirmed decisions:
- Auto-validation = rule engine **+** auto-assign to a User (both).
- Workflow stored per type (`workflow_transitions` keyed by `type_document_id`), seeded uniformly from the xlsx.
- Two-step form: type buttons first, statut asked inside step 2.
- Filament: recommendation/rationale only, no implementation in this round.
- Daily digest = personal per-user backlog.
- `pieces_requises` = soft list (no per-piece upload slots yet).
- Auto-dispatch = single `default_agent_id` per type.

---

## Phase 0 — Trivial fixes (item 1, item 5)

**Ministry name (item 1)** — replace previous ministry wording with `Ministère de la Santé et de l'Hygiène publique` in three PDFs only:
- `resources/views/demandes/pdf/ADM.blade.php:17`
- `resources/views/demandes/pdf/CTRV.blade.php:17`
- `resources/views/demandes/pdf/TRV.blade.php:17`

The header/footer partials already use the new name. Run a final case-insensitive grep to confirm zero remaining hits.

**English → French (item 5)** — repository scan reports no significant English UI strings. Concrete touch-ups:
- `app/Mail/DemandeComplementMail.php` subject `"Demande Complement Mail"` → `"Demande de compléments"`.
- Audit `app/Notifications/DemandeNotification.php` (default subject/lines) and the two markdown mail templates under `resources/views/emails/demande/` for English fragments.
- Audit Breeze auth views for any leftover English (login/register).

---

## Phase 1 — Schema (items 2, 6, 7, 11, 12)

All new migrations under `database/migrations/`. Use `php artisan make:migration --no-interaction` for each, run via `php artisan migrate` against the dev MySQL and assert tests still pass on sqlite :memory:.

1. **`categories_socioprofessionnelles`** (item 2) — `id`, `libelle` (unique), `code` (nullable), `ordre`, timestamps. New `App\Models\CategorieSocioprofessionnelle`. Seeder `CategorieSocioprofessionnelleSeeder` with the values currently observed in production data (start with: `Médecin`, `Infirmier`, `Sage-femme`, `Administratif`, `Technicien`, `Ouvrier`, `Autre` — confirm list before seeding).
2. **`demandes` change** — add `categorie_socioprofessionnelle_id` FK (nullable, restrict on delete). Backfill from existing free-text column in a follow-up migration that pattern-matches known values; **keep the old text column for one release** to avoid data loss.
3. **`type_documents` columns** (items 7, 11, 12):
   - `eligibilite` enum/string nullable: `null` (=tous) / `etatique` / `contractuel`. Seeded from xlsx (only ANA = `etatique`).
   - `default_agent_id` FK → users, nullable, nullOnDelete. Drives auto-dispatch.
   - `description` text nullable, `icone` string nullable (heroicon class or asset path) — feeds the type-button UI.
4. **`pieces_requises`** (item 6) — `id`, `type_document_id` FK (cascadeOnDelete), `libelle`, `description` nullable, `obligatoire` bool default true, `ordre`. Model `App\Models\PieceRequise`, hasMany on `TypeDocument`.
5. **`workflow_transitions`** (item 8) — `id`, `type_document_id` FK (cascadeOnDelete), `etat_source_id` FK → etat_demandes, `etat_cible_id` FK → etat_demandes, `role_requis` string nullable (Spatie role name), `automatique` bool default false, `ordre`. Model `App\Models\WorkflowTransition`. A `(type_document_id, etat_source_id, etat_cible_id)` unique index.

---

## Phase 2 — Workflow engine (items 4, 8)

Move state-machine truth out of `DemandeController::changerEtat` (DemandeController.php:134-217) into a service.

- New `app/Services/WorkflowEngine.php` with:
  - `transitionsFor(Demande): Collection<WorkflowTransition>` — current state's outgoing edges for that type.
  - `peut(Demande, EtatDemande $cible, User $user): bool` — checks edge exists for the type, user role matches `role_requis`, and demande-specific guards (e.g. agent assignment match for `DEMANDE DE COMPLEMENTS`).
  - `transitionner(Demande, EtatDemande $cible, ?array $payload): Demande` — applies the transition, runs the matching side-effect handler (extracted from the existing switch), writes `HistoriqueEtat`, appends to `commentaire` audit log, then **recursively triggers any outgoing transition flagged `automatique` whose guard predicate passes**.
- Side-effect handlers stay where they are (assigning agent, sending complements mail, generating PDF + QR + signed mail) but become dispatched via a `match($cible->nom)` inside the engine — same code, moved.
- `DemandeController::changerEtat` becomes a thin wrapper that authorises and delegates to the engine.

**Auto-validation rule engine (item 4 — "rule engine" half)** — register an automatic transition `RECEPTIONNEE → VALIDEE` for any type where the admin enables it; gate it on a `DemandeValidationRules::estEligibleAValidationAuto(Demande): bool` predicate that checks:
- All `champs_requis` keys present and non-empty on the demande row.
- Demande's statut matches type's `eligibilite` (or `eligibilite is null`).
- All `pieces_requises` with `obligatoire = true` have at least one matching `FichierJustificatif`.
- Spatial sanity (e.g. `date_fin_service > date_prise_service`).

Seed `workflow_transitions` from the xlsx for the 5 existing types using the uniform 7-transition template; mark zero transitions automatic by default — admins enable per type.

**Auto-dispatch (item 4 — "auto-assign" half + item 12)** — when a demande enters `RECEPTIONNEE`, if `type_document.default_agent_id` is set, immediately set `demande.agent_id` to that user and log it in `historiques`. Add an "Imputer" action to `demandes.show` (visible to CHEF_DE_DIVISION/ADMIN) that calls a new `DemandeController::imputer` route, posts an `agent_id` and writes the change to history. Tests: `tests/Feature/AutoDispatchTest.php`, `tests/Feature/WorkflowEngineTest.php`.

---

## Phase 3 — Forms (items 2, 3, 6)

`resources/views/demandes/create.blade.php` (today: 288 lines, single page, `<select>` for type at line 140) becomes a two-step Alpine flow on a single page:

- **Step 1**: grid of `<button>` cards, one per `TypeDocument`. Each card shows `icone`, `nom`, `description`. Clicking sets `selectedTypeId` and reveals step 2. No statut filter at this stage (per user choice).
- **Step 2**: existing form, but:
  - `champs_requis` driven from the chosen type (already works).
  - `categorie_socioprofessionnelle` becomes a `<select>` populated from `CategorieSocioprofessionnelle::orderBy('ordre')->get()` — replaces the current text input at create.blade.php:221.
  - A new "Pièces à fournir" panel (above the file uploader at create.blade.php:247) lists `pieces_requises` for the chosen type with libellé + obligatoire badge, all reactive to `selectedTypeId`.
  - The statut radio (étatique/contractuel) is shown here. On submit, server-side `DemandeController::store` rejects mismatches with `type.eligibilite`.

Mirror the categorie select and pieces list on `resources/views/demandes/edit.blade.php`. Keep the existing signed-URL gate on edit/update unchanged.

**Validation request** — new `app/Http/Requests/StoreDemandeRequest.php` and `UpdateDemandeRequest.php` to consolidate the inline rules in the controller; add `categorie_socioprofessionnelle_id` `exists:categories_socioprofessionnelles,id`, eligibility check, and per-piece file presence when `obligatoire`.

---

## Phase 4 — Dashboard (item 9)

`/dashboard` currently renders the empty Breeze `dashboard.blade.php`. Replace with a real overview:

- New `app/Http/Controllers/DashboardController.php` with `__invoke()` returning aggregates:
  - Counts per `EtatDemande` (filtered by `auth()->user()` scope: AGENT sees only assigned; CHEF/ADMIN/DRH see all).
  - Demandes awaiting **the current user's** action (computed via `WorkflowEngine::transitionsFor` to find which states list the user's role).
  - Counts per `TypeDocument` over the last 30 days.
  - Average time-to-signature (signed_at − created_at on demandes in `SIGNEE`).
- Update `routes/web.php:33` to point `/dashboard` to `DashboardController`.
- View at `resources/views/dashboard.blade.php` rebuilt with summary cards + a "Mes demandes à traiter" datatable powered by yajra/laravel-datatables (reuse the pattern from `DemandeController::data()`).

---

## Phase 5 — Daily digest mail (item 10)

- New `app/Console/Commands/EnvoyerResumeQuotidien.php` (signature `resume:quotidien`). For each active user, computes the same "à traiter par moi" list as the dashboard and dispatches a queued mail.
- New `app/Mail/ResumeQuotidienMail.php` with markdown template `resources/views/emails/resume-quotidien.blade.php` listing demandes with link to `demandes.show`. Skip the user if their list is empty.
- Schedule in `routes/console.php` (Laravel 13 closure-style):
  ```php
  Schedule::command('resume:quotidien')->weekdays()->dailyAt('07:30')->timezone('Africa/Dakar');
  ```
- Document `php artisan schedule:work` for dev / `cron` entry for prod in CLAUDE.md.

Test: `tests/Feature/ResumeQuotidienTest.php` asserts `Mail::fake()` receives one mail per user-with-backlog and zero for empty queues.

---

## Phase 6 — Filament migration advice (item 13, recommendation only)

**Recommendation: keep public/citizen pages on the current Blade stack; move the authenticated admin (list/show/state changes/dashboard/users/types/workflows) to Filament 3 in a follow-up project, NOT in this plan.**

Why move admin:
- Filament gives you Tables (replaces yajra), Forms (replaces the manual Blade+Alpine forms for admin CRUD), Resources (replaces hand-written controllers for `TypeDocument`, `PieceRequise`, `WorkflowTransition`, `CategorieSocioprofessionnelle`, `User`), Widgets (replaces the dashboard built in Phase 4), Actions (perfect fit for the `changerEtat` button row), Notifications, and Spatie role integration out of the box. Most of items 9, 12 and the type/workflow CRUD become declarative.
- The new schema in Phase 1 is exactly the kind of data Filament shines on (relations, JSON fields, role-gated actions).

Why **not** migrate citizen-facing pages:
- `demandes.create`, `demandes.verifier/{code}`, the signed-URL `demandes.edit`/`update`, and the public confirmation flows are tightly designed around Senegalese citizen UX (multi-step icon picker, QR verification page). Filament Panels are admin-shaped; rebuilding citizen UI on Filament would fight the framework.

Why **not now**:
- Items 1–12 are deliverable in 2–3 weeks on the current stack with no rewrite. A Filament migration is a separate ~2-week project that should follow once Phase 1's data model has stabilised — otherwise you migrate twice.

Trade-off summary in plan; concrete steps deferred.

---

## Phase 7 — Plan persistence (item 15)

After approval, the plan is copied verbatim to `upgrade_plan_v2.md` at the repo root (committed alongside Phase 0's first PR).

---

## Critical files (modified)

- `app/Http/Controllers/DemandeController.php` — slim down `changerEtat`, add `imputer`.
- `app/Models/{Demande,TypeDocument,EtatDemande}.php` — new relations, casts.
- `app/Services/WorkflowEngine.php` *(new)*.
- `app/Services/DemandeValidationRules.php` *(new)*.
- `app/Http/Controllers/DashboardController.php` *(new)*.
- `app/Console/Commands/EnvoyerResumeQuotidien.php` *(new)*.
- `app/Http/Requests/{Store,Update}DemandeRequest.php` *(new)*.
- `app/Mail/{DemandeComplementMail.php, ResumeQuotidienMail.php}`.
- `database/migrations/*` (5 new migrations described in Phase 1).
- `database/seeders/{TypeDocumentSeeder, CategorieSocioprofessionnelleSeeder, WorkflowTransitionSeeder, PieceRequiseSeeder}.php`.
- `resources/views/demandes/{create,edit}.blade.php` — two-step UI, categorie select, pieces panel.
- `resources/views/demandes/pdf/{ADM,CTRV,TRV}.blade.php:17` — ministry name.
- `resources/views/dashboard.blade.php` — full rebuild.
- `resources/views/emails/resume-quotidien.blade.php` *(new)*.
- `routes/web.php` — DashboardController, imputer route.
- `routes/console.php` — schedule.

## Reused existing utilities

- `App\Models\HistoriqueEtat` — already logs every transition; the engine writes to it as today.
- `App\Services\DemandeMailService::envoyer()` — reused for any new generic notification.
- `yajra/laravel-datatables` — reused for the dashboard "à traiter" table; same `DemandeController::data()` pattern.
- `App\Notifications\DemandeNotification` — reused for non-PDF notifications.
- `EtatDemande::labels()` — preserved as the human-readable name source for UI.
- `Spatie\Permission` `role:` middleware — reused for the new `imputer` route.

## Verification

End-to-end (run after each phase):

```bash
composer test                              # full PHPUnit suite, sqlite :memory:
php artisan test --filter=WorkflowEngine   # after Phase 2
php artisan test --filter=Dashboard        # after Phase 4
php artisan test --filter=ResumeQuotidien  # after Phase 5
vendor/bin/pint --dirty --format agent     # before each commit
```

Manual smoke (Phase 3 onwards, run `composer dev`):
1. `/demandes/create` → click each type icon → confirm step 2 shows correct champs, pieces, categorie select.
2. Submit a complete demande as a contractuel user with type ANA → expect 422 (eligibility).
3. As CHEF, transition `EN ATTENTE → RECEPTIONNEE` on a type with `default_agent_id` set → confirm `demande.agent_id` populated and history entry written.
4. Enable auto-validation flag on a type, submit a fully-correct demande → confirm it lands in `VALIDEE` without manual click.
5. As an AGENT with assigned demandes, run `php artisan resume:quotidien` → confirm only that agent receives a non-empty mail.
6. Open `/dashboard` as each role → confirm scoping (AGENT sees only their assignments).
7. Generate a signed PDF for ADM/CTRV/TRV → confirm new ministry name appears.
8. `php artisan schedule:list` → confirm the cron entry is present.

Database sanity (laravel-boost MCP):
- `database-schema` confirms the 5 new tables and FKs.
- `database-query` `SELECT type_document_id, COUNT(*) FROM workflow_transitions GROUP BY 1` returns 7 rows × number of seeded types.
