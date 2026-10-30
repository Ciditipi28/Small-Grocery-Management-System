# Gmail verification for new staff accounts

Implemented October 30, 2026. Real Gmail delivery needs sender configuration; no Gmail credentials have been supplied or saved by the agent. The feature was tested with an isolated local SMTP fixture, not a real mailbox.

## How account creation works

1. The owner signs in and opens **Staff accounts**.
2. Under **Invite a staff member**, the owner enters the staff email and assigns Owner or Cashier access.
3. The app sends a six-digit verification code to that address. No login account exists yet.
4. The recipient opens **Create an account with your email code** from the login page, or follows the registration address in the email.
5. They enter their name, invited email, username, password/confirmation, and email code. The server creates the account only after checking the invitation and code, then sends them back to sign in.

The verification step applies to new account creation. It is not an OTP requirement on every login, and existing accounts remain usable. Initial CLI installation is a trusted bootstrap step for the first owner; invitations cover subsequent staff accounts. The regular staff save endpoint rejects attempts to create accounts directly.

## Configure the Gmail sender locally

Google requires 2-Step Verification to create an App Password. Availability can depend on the account's security or organization settings. See [Google's App Password instructions](https://support.google.com/accounts/answer/185833).

1. Choose the Gmail account that will send the grocery emails.
2. Enable 2-Step Verification in that Google Account, then create an App Password for this project.
3. In a private PowerShell terminal, from this project folder, run:

   ```powershell
   .\scripts\configure-gmail.ps1
   ```

4. Enter the Gmail sender address and App Password when prompted. The password input is hidden. It is stored only in the ignored `config.local.php` file, outside the public web root. The setup command preserves the existing database connection settings and does not send a message.
5. Refresh Staff accounts and send an invitation to a staff member whose email you intend to authorize. The page reports a delivery error if Gmail rejects the message. Check the recipient's inbox and spam folder.

Do not enter your normal Gmail password or paste an App Password into chat. If PowerShell blocks execution of the setup script, enter the values manually in `config.local.php` using `config.local.example.php` as a guide; the application does not require changing PowerShell policy.

The configuration uses `smtp.gmail.com`, port `587`, and STARTTLS with certificate checks enabled, following [Google's SMTP connection documentation](https://developers.google.com/workspace/gmail/imap/imap-smtp). The sender and SMTP username should match. Delivery uses [PHPMailer 7.1.1](https://github.com/PHPMailer/PHPMailer/tree/v7.1.1); its upstream source and license are included under `vendor/phpmailer`.

An alternative to storing credentials in the local file is supplying `GROCERY_SMTP_USERNAME` and `GROCERY_SMTP_PASSWORD` as environment variables to the PHP process before starting it. File settings take precedence. Never commit or publish credentials.

`registration_url` must be the address staff can open. The local preview defaults to `http://127.0.0.1:8085/index.php?page=register`, which only works on the same computer. For a later LAN/hosted installation, configure the actual registration URL and HTTPS appropriately. Receiving a Gmail message does not make the local application accessible from another device.

## Codes, retries, and invitation control

- Codes expire after 10 minutes and allow five failed verification attempts.
- Codes are randomly generated. Only a password hash is stored in the database. Codes are not displayed in the application, placed in URLs, written to logs, or saved in a development outbox.
- Resending waits at least 60 seconds and is limited to five emails per invitation per hour. A successfully sent replacement invalidates the previous code.
- Public resend requests give a generic response and never invite an unknown address. A visitor cannot assign their own role.
- Invitation history lets the owner resend or revoke pending invitations. Revoked invitations cannot be used; the owner can re-invite that email after the normal cooldown.
- Verification, user creation, invitation consumption, and registration audit entry commit together. An already used code cannot create another account.
- A successful SMTP submission is not a guarantee of inbox delivery. If mail fails or the inbox has no message, review configuration and resend after the cooldown.

## Database changes

The system now has **16 purposeful tables**. `account_invitations` stores authorization, role, code hash/expiry, attempts, resend counters, issuer, and used/revoked state. `users.email` is nullable/unique and `users.email_verified_at` records verification; earlier accounts retain null values.

For an existing database run:

```powershell
& 'C:\xampp\php\php.exe' 'scripts\migrate.php'
```

The migration can be rerun and preserves existing accounts, inventory, and transactions. Fresh installations include these changes in `database/schema.sql`.

## Tests

```powershell
python 'tests\smtp_registration.py'
& 'C:\xampp\php\php.exe' 'tests\integration.php'
python 'tests\http_smoke.py'
```

Registration checks use uniquely named temporary databases and a local SMTP fixture. They never send email to Gmail or any other external inbox. Tests cover verification, wrong/expired/used/revoked codes, permanent failed-attempt counts, cooldown/hourly send limits, role tampering, bypass attempts, rollback on delivery failure, and the actual PHPMailer SMTP transport to loopback.
