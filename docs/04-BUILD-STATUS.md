# First build status

Updated: October 30, 2026

This is a working local development version for one store. It is not a claim that all assignment submissions or final acceptance work are complete.

Hosting preparation update: InfinityFree has been researched as a compatible free PHP/MySQL/Gmail-SMTP candidate. Hosting configuration, optional HTTPS enforcement, a provider-neutral schema import, and a private first-owner SQL exporter are prepared. The app is not deployed; a hosting account/public URL, server credentials, and real Gmail delivery verification are still pending. See `06-LIVE-HOSTING.md`.

The 12 hosting-preparation checks, 27 registration checks with local SMTP, release SQL checks, authenticated page/access checks, and archive privacy/hash checks passed locally. No actual hosting account or Gmail inbox was used for those checks.

## Implemented

- Redesigned shared interface with grouped navigation, responsive transaction forms, and a remembered light/dark toggle on login, registration, and signed-in pages. The sales/refunds chart now shares one scale and includes exact figures; recent receiving information is shown on the owner dashboard. See `07-UI-UPDATE.md` for upload instructions; no database migration is needed.
- Sixteen InnoDB tables (original 15 plus account invitations) with primary/foreign keys, unique document numbers, decimal money, positive-quantity/nonnegative-stock constraints, and historical sale price/name/SKU values.
- Owner-invited, email-verified staff creation with Gmail SMTP support, expiry, attempt/send limits, resend/revocation, and single-use code consumption. Existing logins are preserved. Real Gmail delivery awaits local sender configuration; see the Gmail OTP setup guide.
- One integrated password-hashed login, current-account/role checks, regenerated sessions, CSRF protection, escaped output, prepared SQL, local session login throttling, and logout.
- Owner maintenance forms for products, categories, units, suppliers, and staff, including deactivation and automatic activity records. Stock cannot be changed through the product form.
- Immediately posted multi-line deliveries, sales, sale-linked refunds, and signed adjustments. Atomic writes include document, lines, stock, and activity log. Submission keys prevent repeat posting. Stock is locked and checked during posting; returns lock the original sale and respect prior return quantities.
- Checkout estimates totals and change in the browser and recalculates them on the server.
- Dashboard, seven-day sales display with refund figures, filtered/paginated transaction histories and master lists, receipts/detail views, stock status, stock reconciliation, and basic printable sales/refund, top-selling, and supplier receiving summaries.
- Owner-only actions and cashier access to own sales, stock, and movements. No receiving costs, staff management, activity history, or global sales reports for cashiers.
- Responsive local CSS and JavaScript, without a network dependency for runtime assets.

The existing 20 planned functions have routes in this build; some shared PHP views serve several routes. Functional completeness and report depth still need review against the final approved specification. Additional return/adjustment details and entry views are available without counting dialogs as extra pages.

## Verified

- All PHP files passed syntax checks.
- Twenty integration checks passed, including the 14-unit/50.00 example, duplicate posts, excessive returns, inactive products, permissions, and rollback after a deliberately injected audit-write failure. Twenty-seven additional registration checks and a local SMTP transport check passed without sending real emails.
- Two independent PHP processes with different cashiers competed for the last unit: exactly one sale succeeded, the other was rejected, and stock ended at zero.
- HTTP checks passed for 24 authenticated route variants and six owner-only access denials, plus login, invalid CSRF handling, and cashier checkout access.
- Owner sign-in and checkout estimates of 75.00 total and 25.00 change were checked in the browser. The dashboard was visually reviewed.

## Remaining work

- Real-shop research, confirmation of payment/pricing/receipt/return policies, and validation of actual user permissions.
- Full dictionary, revised ERD matching the implemented column names, formal diagrams, and complete submission documentation. The original handoff remains an unchanged historical design source.
- Inspection and adaptation of the actual instructor-supplied authentication if it becomes available. No such files were supplied in this project; the current login is the application's single authentication system.
- Draft saving/cancellation, a general posted-document correction/reversal policy, and its implementation. Current schema supports posted documents only; it will need a migration when those lifecycle features are designed.
- More detailed report filters/exports and broader print/accessibility review. Recent receiving and a common-scale sales/refunds chart are now implemented.
- Integration of an appropriate CSS framework if the instructor requires it; the current interface uses custom CSS.
- Database backup/restore, restart/persistence checks, broader mobile/print/browser review, accessibility refinement, and end-to-end XAMPP Apache demonstration.
- Deployment hardening and broader authentication abuse protections before internet hosting. This build is a local school-project preview.

The transaction schema uses `id` primary keys and shared names such as `document_no`, `created_by`, and `posted_at`. Returns use `total_amount` for the refund amount. These are implementation choices to document in the final dictionary, rather than claims that the older outline already matches the database field for field.
