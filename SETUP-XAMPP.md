# Put the system in VS Code and phpMyAdmin

For a free online server, use [the live-hosting guide](docs/06-LIVE-HOSTING.md), `config.hosting.example.php`, and `mini_grocery_hosting_schema.sql`. The XAMPP instructions below configure this computer only.

## Files to use

- `SmallGrocery-htdocs.zip`: hosting-upload variant. Extract it locally and upload its contents directly into the domain's `htdocs`; there is no outer SmallGrocery folder. It contains the same checked source as the project ZIP, with private configuration excluded.
- `SmallGrocery-Latest.zip`: the current source package. Extract its `SmallGrocery` folder and open that whole folder in VS Code.
- `mini_grocery_fresh.sql`: fresh installation only. Creates the `mini_grocery` database, 16 tables, roles, categories, and units. It does not include accounts, passwords, inventory, sales, or Gmail settings.
- `mini_grocery_update_002_email_otp.sql`: an update for an existing grocery database that needs the email OTP feature. Select your existing database before importing it. It adds missing email fields and the invitation table, preserving records. It is safe to rerun.

The SQL files are both inside the ZIP under `database/` and provided separately beside the ZIP. `RELEASE-MANIFEST.json` lists the packaged files and SHA-256 hashes.

## First installation with XAMPP and phpMyAdmin

1. Extract the ZIP so the project folder is `C:\xampp\htdocs\SmallGrocery`.
2. In VS Code select **File → Open Folder**, then select `C:\xampp\htdocs\SmallGrocery`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`. On the Import tab, choose `database/mini_grocery_fresh.sql` from the extracted project and click **Go / Import**. The SQL creates and selects `mini_grocery`; all 16 tables should appear. Use this only for a new installation.
5. Copy `config.local.example.php` to `config.local.php`. Check `db_host`, `db_port`, `db_name`, `db_user`, and `db_password` against your local MySQL setup. The template uses port 3306 and database `mini_grocery`. Set `registration_url` to `http://localhost/SmallGrocery/public/index.php?page=register` for this Apache layout.
6. In the VS Code PowerShell terminal, from the extracted project folder, run:

   ```powershell
   & 'C:\xampp\php\php.exe' 'scripts\create-owner.php'
   ```

   Choose your first owner username, full name, and password. The password prompt is visible while typing. This command only works when no accounts exist. It does not add another owner to an existing system.

7. Open **http://localhost/SmallGrocery/public/** and sign in with that account.
8. For email OTP registration, follow `docs/05-GMAIL-OTP-SETUP.md`. Enter Gmail credentials locally through `scripts/configure-gmail.ps1`; they are not included in the package. Then invite staff from Staff accounts.
9. Add products and enter opening stock using a Stock Adjustment. Optional fictional demo data can be added with `scripts/seed-demo.php` while the product catalogue is empty.

Do not run `scripts/install.php` after importing SQL: it is an alternative installer for an empty database and refuses to overwrite existing tables. After importing through phpMyAdmin, use `scripts/create-owner.php` instead.

The ZIP includes a root `.htaccess` that routes the folder root to `public/` and blocks browser access to private project folders. It requires Apache `mod_rewrite` and AllowOverride support. The preferred hosted configuration is still an Apache virtual host whose document root is `public/`. Do not remove the protection to expose configuration, SQL, or scripts through a browser.

## If you already use this system

1. Export/backup your existing database in phpMyAdmin and keep a copy of your project folder.
2. Extract the new ZIP to a temporary folder. Copy its application files into your project while preserving your existing `config.local.php` and any runtime data. The package deliberately does not contain private configuration.
3. Read `CHANGELOG.md`. Import only the update SQL named for your current version, if required. Select your existing grocery database in phpMyAdmin first. Do not import `mini_grocery_fresh.sql` into a working database.
4. Existing users, passwords, inventory, and transactions remain in your database. Sign in using your existing owner account. No first-owner setup is needed.
5. Refresh the browser. Gmail sender configuration is needed only if it has not already been set up locally.

For the current email OTP update, import `mini_grocery_update_002_email_otp.sql` if your database still has only the original 15 tables. If the database has already been migrated by this chat, no additional database import is needed. The same SQL is safe to rerun when uncertain.

## Exporting after future edits

From the project folder, run:

```powershell
.\scripts\export-release.ps1
```

This writes a dated ZIP, `SmallGrocery-Latest.zip`, fresh SQL, and update SQL into `releases/`. It uses an explicit list of source directories, excludes private files and runtime data, and creates a file-hash manifest. If PowerShell script execution is blocked, use your trusted local PowerShell setup to run the script; exporting does not require changing system policy.

There is no live database backup in the release. Export your actual records separately through phpMyAdmin when making a backup.
