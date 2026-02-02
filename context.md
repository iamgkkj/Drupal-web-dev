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
- **Current phase**: Phase 6
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
- Implemented admin routes and forms:
  - `/admin/config/event-registrar/create-event` (`EventConfigForm`)
  - `/admin/config/event-registrar/notifications` (`NotificationSettingsForm`)

### In Progress
- Phase 6: final submission artifacts (README + composer.lock + SQL dump) and final QA.

### Done (Implemented)
- Phase 3: public registration form (AJAX + validation + persistence).
- Phase 4: admin registrations listing + CSV export.
- Phase 5: email notifications.

### Phase 3 Notes
- Route added: `/event/register` (`EventRegistrationForm`).
- Chained selects implemented: Category -> Event Date -> Event Name (AJAX).
- Validation implemented:
  - No special characters for Name/College/Department.
  - Duplicate check: Email + Event Date.
  - Registration window check for selected event.
- Submission implemented: insert into `event_registrations`.

### Phase 4 Notes
- Route added: `/admin/config/event-registrar/registrations` (`RegistrationFilterForm`).
- Filters implemented:
  - Event Date dropdown
  - Event Name dropdown (AJAX depends on selected date)
- Displays total participants for selected date + event.
- Displays results table (AJAX): Name, Email, Event Date, College Name, Department, Submission Date.
- CSV export implemented for the selected date + event.

### Remaining
- Phase 6: QA + submission artifacts

### Phase 5 Notes
- Added `event_registrar.module` with `hook_mail()` keys:
  - `user_confirmation`
  - `admin_notification`
- Registration submission now sends:
  - User confirmation email
  - Admin notification email (only if enabled in config and admin email is set)
- Email content includes: Name, Event date, Event Name, Category (plus other submitted fields).

## What’s Working
- Module skeleton files exist in the expected module folder.
- Admin configuration forms and routes have been added to the module.

## What’s Not Working / Blockers
- Repo does not currently include a full Drupal codebase (`web/` was created for the module, but Drupal core/site is not present), so enabling via Drush must be validated in an external Drupal site.

## QA / Verification
- Module synced and cache rebuilt successfully in external Drupal site: `/home/gopal/Desktop/event-site`.
- Verified tables exist:
  - `event_configurations`
  - `event_registrations`
- Generated SQL dump for custom tables:
  - `event_registrar_tables.sql`
- Added `composer.json` and `composer.lock` to this repository root (copied from the external Drupal site) to satisfy submission format.

## Notes / Decisions
- (none yet)
