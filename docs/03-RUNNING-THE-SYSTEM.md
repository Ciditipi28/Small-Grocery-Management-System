# Running the grocery system

## Local preview prepared on October 30, 2026

Open **http://127.0.0.1:8085** while the local preview processes are running. The generated owner login is saved in `var/preview-access.txt` in this project. That file and `config.local.php` are ignored by version control.

The preview uses XAMPP's PHP and an isolated MariaDB instance on **127.0.0.1:3307**, with data stored in `var/test-mysql`. It does not use or change existing XAMPP databases. Despite the directory's name, this instance also holds the persistent local demonstration database `mini_grocery`. Do not delete that directory if you want to keep the demonstration records.

The demo contains one clearly labeled product and supplier, opening stock, receiving, a sale, a return, and a damage adjustment. The expected stock is **14**, gross sales **75.00**, refunds **25.00**, and net cash takings **50.00**. These are fictional records, not business research.

The HTTP smoke test creates a cashier account named `smoke_cashier` and deactivates it when checks finish. Create your own cashier account through Staff Accounts.

## Fresh XAMPP installation

1. Start MySQL in XAMPP.
2. Copy `config.local.example.php` to `config.local.php`. Set the MySQL connection values. A normal XAMPP installation commonly uses port 3306; the prepared preview uses 3307.
3. From the project directory, run:

   ```powershell
   & 'C:\xampp\php\php.exe' 'scripts\install.php'
   ```

   Choose an owner username, full name, and password of 12–72 characters. The interactive password prompt is visible; use a private terminal. There is no built-in default owner password. The installer refuses to overwrite a database containing tables.

4. Start a development preview:

   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8085 -t public
   ```

5. Open http://127.0.0.1:8085 and sign in.

For Apache, configure its document root or a virtual host to this project's **public** directory. The configuration, application code, SQL, credentials, and tests belong outside the web root. The PHP development server is for local development.

Requirements: PHP 8.2 or later with PDO MySQL and mbstring; MySQL/MariaDB with InnoDB and enforced CHECK constraints. The first version was tested using the PHP and MariaDB bundled with the installed XAMPP.

## Optional demo data

After installing an empty database, run:

```powershell
& 'C:\xampp\php\php.exe' 'scripts\seed-demo.php'
```

The seeder refuses to run if products already exist. For a clean demonstration, create a new empty database under a different name and run the installer there; it will not erase posted history.

## Suggested walkthrough

1. Sign in as owner; add suppliers, categories, units, and products. To add a cashier, configure Gmail, invite their email from Staff accounts, and have them verify the code to create their account. See [Gmail OTP setup](05-GMAIL-OTP-SETUP.md).
2. Open Adjustments → New Stock Adjustment. Use a positive quantity and reason `Opening stock`.
3. Record a delivery with products, quantities, and unit costs.
4. Open Checkout, select products, enter quantities and cash tender. Review the estimated total/change, then post. The server independently recalculates prices and validates stock.
5. Open the sale details to print the receipt or start a return as owner. Specify how many returned units are saleable.
6. Review Stock on Hand, Stock Movements, Reports, and Activity History.
7. Edit a master record and uncheck Active to deactivate it. Existing transactions remain available.

Only posted transactions are supported in this first build. A failed post rolls back its writes. There is no posted-document edit/delete action. Sales corrections can be represented by an appropriate sale-linked return; the general correction/reversal workflow remains pending. Do not use an inventory adjustment as a substitute for correcting cash history.

Currency is a configuration label and is initially blank. Product prices are used directly, with no separately calculated tax, discounts, or regulated receipt format. These business rules still require confirmation.

## Restarting the prepared preview

From the project folder, start the isolated database in a hidden window:

```powershell
Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file="C:\Users\christian\OneDrive\Desktop\SystemProj\var\test-mysql\my.ini"','--bind-address=127.0.0.1' -WindowStyle Hidden
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8085 -t public
```

Start these only when their ports are free. To stop the isolated database gracefully:

```powershell
& 'C:\xampp\mysql\bin\mysqladmin.exe' --host=127.0.0.1 --port=3307 -u root shutdown
```

For a normal XAMPP deployment, use XAMPP's service controls and the port configured for that installation instead.

## Verification

```powershell
& 'C:\xampp\php\php.exe' 'tests\integration.php'
& 'C:\xampp\php\php.exe' 'tests\concurrency.php'
python 'tests\http_smoke.py'
```

The PHP tests create uniquely named `grocery_test_*` databases and drop only those databases afterward. The configured database user therefore needs create/drop privileges for testing. The HTTP test targets the prepared running preview, reads `var/preview-access.txt`, expects seeded demo documents with ID 1, and creates/updates then deactivates its test cashier. It is a local development check, not a production test script.
