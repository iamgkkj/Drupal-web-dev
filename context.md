# Project Context

## Project
- **Name**: Drupal Event Registrar (custom module)
- **Workspace**: `Drupal-web-dev`
- **Module path (planned)**: `web/modules/custom/event_registrar/`

## Goals
- Build a Drupal custom module that:
  - Lets admins create event configurations (registration window, event date, name, category).
  - Provides a public registration form with chained AJAX dropdowns.
  - Validates registrations (format, duplicates, registration window) and stores them.
  - Provides an admin reporting page with filtering and CSV export.
  - Sends email notifications (user confirmation + optional admin notification).

## Key Technical Constraints
- Use **Dependency Injection** (avoid global wrappers like `\Drupal::database()` / `\Drupal::config()` where applicable).
- Use **Config API** for notification settings (not DB table).
- Create and use DB tables:
  - `event_configurations`
  - `event_registrations`

## Execution Plan (Phases)
### Phase 1: Structure & Database (Foundation)
- Create module skeleton (`.info.yml`, `composer.json`).
- Create `event_registrar.install` with `hook_schema()` for both tables.
- Create `event_registrar.permissions.yml`.
- Checkpoint: Enable module and confirm tables exist.

### Phase 2: Event Configuration (Admin)
- Create Event Config form + route: `/admin/config/event-registrar/create-event`.
- Save to `event_configurations` using injected database service.
- Create Notification Settings form + route: `/admin/config/event-registrar/notifications`.
- Store admin email + enable flag via Config API.

### Phase 3: Public Registration Form (AJAX + Validation)
- Route: `/event/register`.
- Chained AJAX selects:
  - Category -> Event Date -> Event Name
- Validation:
  - Regex validation for Name/College
  - Duplicate check (email + event_date)
  - Registration window check vs reg_start_date/reg_end_date
- Submission:
  - Insert into `event_registrations`
  - Trigger email flow

### Phase 4: Admin Listing & CSV Export
- Filter form with AJAX.
- Query join between registrations and configurations.
- CSV export response.

### Phase 5: Email Notifications
- Implement `hook_mail()` in `event_registrar.module`.
- Send user confirmation + optional admin email using `plugin.manager.mail`.

### Phase 6: QA & Submission
- Strict DI audit (search for `\Drupal::`).
- Coding standards.
- Export SQL dumps for both custom tables.
- Documentation/README.

## Current Status
- **Current phase**: Phase 1
- **Overall status**: In progress

## Progress Tracker
### Done
- Created root `.gitignore` for env files, `.windsurf/`, and screening task PDF.
- Created module skeleton:
  - `web/modules/custom/event_registrar/event_registrar.info.yml`
  - `web/modules/custom/event_registrar/composer.json`
  - `web/modules/custom/event_registrar/event_registrar.permissions.yml`
- Created database schema file:
  - `web/modules/custom/event_registrar/event_registrar.install` (tables: `event_configurations`, `event_registrations`)
- Initialized git repo changes and pushed to `origin/main`.

### In Progress
- Phase 1: enable module and verify tables (pending Drupal site / Drush availability).

### Remaining
- Phase 2: admin forms (event config + notification settings)
- Phase 3: public registration form (AJAX + validation + persistence)
- Phase 4: admin reporting + CSV
- Phase 5: mail integration
- Phase 6: QA + submission artifacts

## What’s Working
- Module skeleton files exist in the expected module folder.

## What’s Not Working / Blockers
- Exact DB column requirements for `event_configurations` / `event_registrations` are not available in-repo.
- Repo does not currently include a full Drupal codebase (`web/` was created for the module, but Drupal core/site is not present), so enabling via Drush cannot be validated yet.

## Notes / Decisions
- (none yet)
