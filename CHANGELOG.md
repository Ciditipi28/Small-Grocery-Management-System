# System changes

## October 30, 2026 — Free hosting preparation

- Identified InfinityFree as a compatible free PHP/MySQL hosting candidate with external Gmail SMTP support; account creation, hosting configuration, and real delivery verification remain pending.
- Added a hosting configuration template and opt-in HTTPS enforcement. Local XAMPP behavior is preserved.
- Added shared-hosting schema SQL without CREATE DATABASE/USE, and a local exporter for a private first-owner SQL import on hosts without remote CLI access.
- Added a deployment guide and isolated hosting-preparation tests. Private first-owner SQL stays under ignored var/ and is excluded from releases.
- No database migration is needed for an already migrated local 16-table installation. A new live database imports the hosting schema and its separately generated private owner SQL.

## October 30, 2026 — Git preparation

- Excluded private `config.local.php` files at every folder level, including accidental copies under vendor directories.
- Excluded generated Python bytecode/cache files.
- No database import or migration is required for this update.

## October 30, 2026 — VS Code and phpMyAdmin delivery

- Added a reproducible project ZIP export with separate fresh-install and email-OTP update SQL files.
- Added phpMyAdmin/XAMPP instructions and a local CLI first-owner setup for users who import the schema manually.
- Added root Apache access rules for the common `htdocs/SmallGrocery/public/` installation.
- Recorded the user's preference to provide refreshed files after future system edits.
- Packages exclude local configuration, credentials, runtime databases/logs, and real records.

Database requirement: fresh installs import the fresh SQL; existing original 15-table installations import update 002 once (reruns are safe). Already migrated 16-table previews do not need an import for this packaging update.

## October 30, 2026 — Email OTP account creation

- New staff accounts require owner invitation and verification of an emailed six-digit code.
- Added expiry, attempt/send limits, resend/revocation, and transactional registration.
- Added one invitation table (16 total) and nullable verified-email fields on users.
- Gmail sender setup remains local and private; existing logins remain usable.

## October 30, 2026 — First application build

- Implemented the initial one-store PHP/MySQL application, integrated login, master records, receiving, checkout, returns, stock adjustments, dashboard, and basic reports.
- Added atomic posting, stock locking, audit history, historical prices, and integration/concurrency/HTTP checks.
