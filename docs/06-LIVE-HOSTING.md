# Free live hosting and working email OTP

Prepared October 30, 2026. **Status: prepared for deployment; live deployment has not been verified.** The user supplied the domain **ciditipi.free.je**. Live database settings and server-side Gmail credentials still need configuration. A passing local SMTP fixture is not proof of real Gmail delivery.

## Selected free option

InfinityFree advertises PHP, MySQL/MariaDB, free subdomains, SSL, and .htaccess support: [official hosting features](https://www.infinityfree.com/). Its administrator confirms PHPMailer with an external SMTP provider on port 587/TLS is supported: [official SMTP support thread](https://forum.infinityfree.com/t/support-for-php-mailer/115366).

This is a candidate for the school-project demonstration. Free hosting has resource limits, and the actual hosted database connection, HTTPS, and Gmail delivery must be tested after setup. The provider does not supply this app's Gmail credentials.

The connected Sites hosting runtime uses Cloudflare Workers, which does not directly run this PHP/MySQL application. GitHub stores the source; it is not the PHP/database server.

## 1. Create the free hosting account

At [InfinityFree](https://www.infinityfree.com/), create an account, verify your email, create a free hosting account, and select a free subdomain. Keep account passwords private. Complete any account agreement or verification yourself.

The public site address is safe to share in this chat so the registration link can be configured. Do not send hosting passwords, Gmail App Passwords, or private SQL here.

## 2. Enable SSL

Open **https://ciditipi.free.je**. InfinityFree's current documentation says free subdomains are automatically covered by a system SSL certificate and do not need a custom certificate: [official HTTPS guide](https://forum.infinityfree.com/t/how-to-get-free-ssl-https-on-infinityfree/49323). Wait until this HTTPS address opens without a certificate error before enabling the production configuration. A newly assigned address may still need time to become available.

## 3. Create the live database

In the hosting control panel, create a MySQL database. Record its exact assigned hostname, full prefixed database name, database username, and password locally. InfinityFree requires its supplied MySQL hostname, not `localhost`, and its free databases cannot be connected to directly from this computer: [official MySQL instructions](https://forum.infinityfree.com/t/how-to-connect-with-mysql/49340).

Open the hosting phpMyAdmin, select that new empty database, and import **`database/mini_grocery_hosting_schema.sql`** from the release. This import contains no `CREATE DATABASE` or fixed `USE mini_grocery` command, so it works with provider-assigned names. It creates the 16 tables and lookup seeds, without users or transactions.

This is a fresh live installation. It does not copy the local demonstration's accounts or records. If moving existing business records, plan a separate private database backup/import; never publish that backup to GitHub or the public web folder. Do not run the fresh schema over a populated database. An existing earlier installation uses the additive OTP update SQL instead.

## 4. Create the first live owner privately

The existing first-owner CLI command cannot connect to InfinityFree's database from this computer. Instead, from the project folder on your own computer, run:

```powershell
& 'C:\xampp\php\php.exe' 'scripts\export-first-owner.php'
```

Choose a new live username, full name, and strong password (12-72 characters). The interactive password prompt is visible; use a private terminal. Do not reuse the earlier preview credentials.

The command creates **`var/deployment/first-owner.sql`**, containing the chosen password's hash. Import that private file through the hosting phpMyAdmin while the live grocery database is selected. Keep it out of the public hosting directory and GitHub. The file is ignored by Git and excluded from release ZIPs. The SQL only creates an owner when the users table is empty; repeating it cannot add accounts to an existing installation.

## 5. Upload the source

Extract `SmallGrocery-Latest.zip` locally. Use the hosting file manager or FTP to upload the contents of its `SmallGrocery` folder into the website's `htdocs` directory. Include the root `.htaccess` and the `public`, `app`, and `vendor` directories. Keep the relative layout intact. Do not upload the local `var`, `.git`, `releases`, private owner SQL, or database backups.

The entry address in this layout is **https://ciditipi.free.je/public/**. Upload the contents inside the extracted `SmallGrocery` folder directly into `htdocs`, so `public` is directly inside `htdocs`. The root `.htaccess` redirects the website root there and denies direct access to private project directories. Before adding credentials, verify that requesting `/database/schema.sql`, `/app/functions.php`, and `/config.php` returns a denial, not file contents. If a host ignores `.htaccess`, place private directories outside the web root or configure a document root pointing to `public` before proceeding.

## 6. Set the hosting configuration

On the hosting server, copy **`config.hosting.example.php`** to **`config.local.php`**, beside `config.php`. Fill in the assigned database values and your public HTTPS registration URL. Keep the separate local XAMPP configuration unchanged.

For the upload layout above, the registration URL is:

```text
https://ciditipi.free.je/public/index.php?page=register
```

`require_https` enables canonical HTTPS redirects and secure session cookies. If the provider terminates HTTPS through a proxy, its PHP HTTPS detection must be checked on the actual server. Do not bypass certificate checks or trust arbitrary client-supplied forwarded headers to fix a redirect problem.

The complete hosting file can look like this after you replace the uppercase placeholders privately. Keep the quote marks and commas. This file belongs on the server, not in GitHub or this chat:

```php
<?php
return [
    'db_host' => 'COPY_MYSQL_HOSTNAME_HERE',
    'db_port' => 3306,
    'db_name' => 'COPY_FULL_DATABASE_NAME_HERE',
    'db_user' => 'COPY_MYSQL_USERNAME_HERE',
    'db_password' => 'COPY_MYSQL_PASSWORD_HERE',
    'store_name' => 'Neighborhood Mini Grocery',
    'timezone' => 'Asia/Manila',
    'currency' => '',
    'require_https' => true,
    'mail_smtp_host' => 'smtp.gmail.com',
    'mail_smtp_port' => 587,
    'mail_smtp_security' => 'tls',
    'mail_smtp_username' => 'YOUR-SENDER@gmail.com',
    'mail_smtp_password' => 'PASTE_16_CHARACTER_APP_PASSWORD',
    'mail_from' => 'YOUR-SENDER@gmail.com',
    'registration_url' => 'https://ciditipi.free.je/public/index.php?page=register',
];
```

The MySQL password, website owner password, and Gmail App Password are different credentials. Use each only in its matching field. Do not carry over localhost/3307/XAMPP credentials to the hosting configuration.

## 7. Configure Gmail on the server

Create a Google App Password after enabling 2-Step Verification, following [Google's instructions](https://support.google.com/accounts/answer/185833). In the hosting server's private `config.local.php`, enter:

```php
'mail_smtp_host' => 'smtp.gmail.com',
'mail_smtp_port' => 587,
'mail_smtp_security' => 'tls',
'mail_smtp_username' => 'YOUR-SENDER@gmail.com',
'mail_smtp_password' => 'YOUR-APP-PASSWORD',
'mail_from' => 'YOUR-SENDER@gmail.com',
```

These are placeholders, not working credentials. The sender/from addresses must match. Use an App Password, not your usual Google password. Enter the credentials only in the hosting file editor/private configuration; never in GitHub or chat. Do not disable TLS certificate verification if SMTP fails.

The local PowerShell Gmail setup script configures this computer only. It does not configure the hosting server.

## 8. Verify live behavior

1. Open the public HTTPS address and sign in with the newly created live owner.
2. Confirm that HTTP visits redirect to HTTPS and private project paths are denied.
3. Create a product and an opening-stock adjustment. Refresh and sign in again to verify live MySQL persistence.
4. Invite an email address you control from Staff accounts, assigning Cashier access. Check that the actual Gmail message arrives and contains the public registration URL, with no localhost address.
5. Open the registration page signed out or in a private browser window. Verify the code and create the cashier account. Sign in to that account and confirm owner-only actions are denied.
6. Test wrong, expired, reused, and resent codes. A submitted/queued email is not confirmation of inbox arrival; record actual receipt and successful registration before declaring live OTP verified.

Until these checks have passed on the actual public URL, report the app as deployment-ready rather than live/verified. Do not post a public setup route or install endpoint that can create additional owner accounts.
