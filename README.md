# Small Grocery Inventory and Sales System

Updated: October 30, 2026

The project now has a **working first PHP/MySQL build**. On October 30, the user authorized implementation for one store using **Neighborhood Mini Grocery**, a fictional example business. Research and final design submissions remain unfinished.

**Scope confirmed on October 30, 2026:** build the system for one grocery store, with its own inventory, sales, and staff accounts. Email-verified staff registration now adds one purposeful invitation table, for **16 tables**, while preserving the original grocery workflows.

## Project documents

- [VS Code and phpMyAdmin setup](SETUP-XAMPP.md): install the exported source ZIP and fresh SQL, or apply a database update while preserving existing records. Updated packages are written to `releases/` after system edits.
- [Gmail OTP setup](docs/05-GMAIL-OTP-SETUP.md): owner invitations, email verification, private sender setup, and migration/testing notes. Gmail credentials still need local configuration for actual delivery.
- [Running the system](docs/03-RUNNING-THE-SYSTEM.md): local preview, owner access, installation, demo walkthrough, and test commands.
- [Build status](docs/04-BUILD-STATUS.md): implemented features, verified checks, and explicit remaining work.
- [System proposal](docs/01-SYSTEM-PROPOSAL.md): the next pre-coding deliverable, drafted from the existing design. Unverified business assumptions are labeled.
- [Research and validation worksheet](docs/02-RESEARCH-VALIDATION.md): questions and blank evidence fields for validating the example against an actual shop when available.
- [Original handoff](docs/context/START-HERE-Grocery-Project.md): an unchanged copy of the supplied context, including the detailed design, 15-table baseline, 20-page list, diagram sources, and assignment summary.

## Current status

| Item | Status |
|---|---|
| Topic and sample scenario | Selected; fictional example retained |
| Formal system proposal | Draft prepared; business assumptions remain unverified |
| Real-shop research and black-box evidence | Pending; worksheet prepared, no interviews or observations recorded |
| System flowchart | Mermaid source in the handoff; final rendering and review pending |
| Use case diagram | Draft actor/function model in the handoff; formal UML presentation pending |
| ERD | 15-table draft in the handoff; final fields, constraints, and rendered diagram pending |
| Database dictionary | Field outline only; full field-by-field dictionary pending |
| Page/form list | 20-page draft in the handoff, including 19 pages after login |
| Posted-transaction corrections/reversals | Posted documents preserved; general reversal/draft workflows pending |
| Instructor-provided authentication | Not supplied in this project; single integrated login implemented; adaptation pending if supplied |
| PHP application, MySQL database, implementation tests | First build created; integration, concurrency, HTTP and initial browser checks passed |

## Baseline to preserve

Use PHP, MySQL, HTML, CSS, JavaScript, an appropriate CSS framework, and XAMPP. Plan for one shop, packaged goods, whole-number quantities, walk-in cash sales, received deliveries, sale-linked refunds, and documented stock adjustments.

The current implementation has 16 purposeful database tables and the original 20 planned functions, plus email-registration screens. Keep the owner/manager and cashier roles, historical transaction values, posting transactions, nonnegative stock, and sale-linked return checks. Final submission documents still need reconciliation with the implementation.

Do not add credit accounts, supplier balances, weighted goods, pack conversion, online ordering/payments, batch expiry, or profit accounting without agreeing on a scope change. Net cash takings are not profit.

## Next work

1. Walk through the local build using the running guide; review the interface and workflows.
2. Validate business assumptions and settle correction/reversal and draft policies.
3. Complete the dictionary and diagrams against the actual 16-table SQL schema, including email invitations.
4. Adapt the single login if instructor files become available; finish report/chart and interface refinements.
5. Verify backup/restore, persistence after restart, mobile/print behavior, and the XAMPP Apache demonstration. See Build Status for the full remaining-work list.

Research-dependent decisions can be drafted provisionally. Do not record an assumed answer as an interview result or describe planned features as implemented.
