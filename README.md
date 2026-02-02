# Drupal-web-dev
A custom Drupal module for managing event configurations and user registrations. Features include a custom database schema, AJAX-dependent dropdowns, email notifications via Mail API, and CSV export functionality.

## Side test project repo
This is my own repo (side test project):

https://github.com/iamgkkj/event-registrar.git

## Module location
This repository contains the custom module at:

`web/modules/custom/event_registrar/`

## Installation steps
This repository is module-only. To run it, place the module inside a Drupal site:

1. Copy/symlink `web/modules/custom/event_registrar` into your Drupal site's `web/modules/custom/`.
2. Enable the module:
   - `drush en event_registrar -y`
3. Rebuild caches:
   - `drush cr`

## URLs
Admin:
- Create event configuration:
  - `/admin/config/event-registrar/create-event`
- Notification settings:
  - `/admin/config/event-registrar/notifications`
- Registrations listing + CSV export:
  - `/admin/config/event-registrar/registrations`

Public:
- Event registration form:
  - `/event/register`

## Database tables
### `event_configurations`
Stores event details and registration windows:
- `id`
- `reg_start_date`
- `reg_end_date`
- `event_date`
- `event_name`
- `category`

### `event_registrations`
Stores user registrations:
- `id`
- `full_name`
- `email`
- `college_name`
- `department`
- `category`
- `event_date`
- `event_name_id` (FK to `event_configurations.id`)
- `created` (timestamp)

SQL dump for the custom tables:
- `event_registrar_tables.sql` (schema-only, two custom tables only)

## Validation rules
- Prevents duplicate registrations using:
  - `Email + Event Date`
- Validates:
  - Email format (email element)
  - Special characters are not allowed in text fields
- Validates registration window using the selected event's `reg_start_date` and `reg_end_date`.

## Email notifications
Implemented via:
- `event_registrar.module` (`hook_mail()`)
- Mail sending from `EventRegistrationForm` using injected `plugin.manager.mail`

Emails:
- User confirmation email is always sent.
- Admin notification email is optional and controlled by config:
  - `/admin/config/event-registrar/notifications`

Email content includes:
- Name
- Event Date
- Event Name
- Category


