# Project delivery instructions

The user wants files they can open in VS Code and import through phpMyAdmin, and updated files whenever the system is edited.

- After changing application code, assets, configuration templates, database design, or setup scripts, run `scripts/export-release.ps1` from the project root before delivering the work. Fix relevant failures first.
- Include links to `releases/SmallGrocery-Latest.zip` and the relevant generated SQL files in the final response. State whether SQL import is required for that update.
- Update `CHANGELOG.md` with the actual changes and any migration/setup requirements. Keep `SETUP-XAMPP.md` accurate.
- Never put `config.local.php`, SMTP credentials, generated preview credentials, runtime databases, sessions, logs, or real user/transaction data in a release package. The package includes schema/lookup seeds, not a live database dump.
- Keep fresh-install SQL separate from additive update SQL. Never tell the user to reimport the fresh schema over their existing working database.
- Preserve existing records during updates. Do not reset or replace a user's private configuration.
