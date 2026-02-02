PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "event_configurations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "reg_start_date" VARCHAR(10) NOT NULL,
  "reg_end_date" VARCHAR(10) NOT NULL,
  "event_date" VARCHAR(10) NOT NULL,
  "event_name" VARCHAR(255) NOT NULL,
  "category" VARCHAR(64) NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS "event_configurations__event_unique" ON "event_configurations" ("category", "event_date", "event_name");
CREATE INDEX IF NOT EXISTS "event_configurations__category" ON "event_configurations" ("category");
CREATE INDEX IF NOT EXISTS "event_configurations__event_date" ON "event_configurations" ("event_date");

CREATE TABLE IF NOT EXISTS "event_registrations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "full_name" VARCHAR(255) NOT NULL,
  "email" VARCHAR(254) NOT NULL,
  "college_name" VARCHAR(255) NOT NULL,
  "department" VARCHAR(255) NOT NULL,
  "category" VARCHAR(64) NOT NULL,
  "event_date" VARCHAR(10) NOT NULL,
  "event_name_id" INTEGER NOT NULL,
  "created" INTEGER NOT NULL,
  CONSTRAINT "event_registrations__event_configuration_fk" FOREIGN KEY ("event_name_id") REFERENCES "event_configurations" ("id")
);

CREATE UNIQUE INDEX IF NOT EXISTS "event_registrations__email_event_date" ON "event_registrations" ("email", "event_date");
CREATE INDEX IF NOT EXISTS "event_registrations__event_name_id" ON "event_registrations" ("event_name_id");
CREATE INDEX IF NOT EXISTS "event_registrations__event_date" ON "event_registrations" ("event_date");
COMMIT;
