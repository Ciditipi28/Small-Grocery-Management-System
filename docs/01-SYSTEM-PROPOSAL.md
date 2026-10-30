# System Proposal: Small Grocery Inventory and Sales System

Prepared: October 30, 2026  
Status: **Draft based on a fictional business scenario**

Implementation update (October 30, 2026): email-verified staff registration adds an `account_invitations` table, bringing the implemented database to 16 tables. The original 15-table proposal below needs reconciliation with this approved feature. See [Gmail OTP setup](05-GMAIL-OTP-SETUP.md).

**Basis of this proposal:** Neighborhood Mini Grocery is a fictional example retained at the user's request. No shop interview, observation, or actual business record has been supplied. Descriptions of current manual work are assumptions for design purposes, not research findings. The proposed scope, database, pages, and business rules remain working designs.

## 1. System name

**Small Grocery Inventory and Sales System**

Example business: **Neighborhood Mini Grocery** (fictional).

## 2. Background of the study

For this example, Neighborhood Mini Grocery is a single neighborhood shop selling packaged everyday products such as canned food, noodles, biscuits, bottled drinks, soap, and shampoo sachets. An owner or manager receives supplier deliveries and maintains product prices. An owner or cashier serves walk-in customers.

The assumed manual process uses a sales notebook, paper delivery records, and physical shelf counts. Staff would need to compare these separate records to estimate stock, find an earlier sale, or review daily takings. Whether an actual shop follows these procedures must be established through research.

The proposed system will connect product records, receiving, cash sales, customer returns, and stock adjustments in one PHP/MySQL application. Posting a transaction will update the related inventory quantities and preserve a record of the responsible staff member. A dashboard and operational reports will summarize the stored records.

## 3. Statement of the problem

The project addresses how a small grocery could maintain consistent inventory and cash-sales records when the assumed manual records are separate.

The design will address these questions:

1. How can staff determine available stock without reconstructing it from separate sales and delivery records?
2. How can the shop record several products in one delivery or sale and calculate transaction totals consistently?
3. How can an owner trace a customer refund to the original sale and distinguish saleable returns from damaged returns?
4. How can opening stock and physical-count differences be documented without silently changing product quantities?
5. How can authorized users retrieve past transactions, identify low stock, and review date-filtered sales and refunds?
6. How can the system preserve historical prices and restrict sensitive actions to authorized staff?

These are proposed design problems. Their frequency, severity, and actual effects on a shop have not been measured.

## 4. Objectives

### General objective

To design and develop a PHP/MySQL system for a small grocery that records received goods, walk-in cash sales, sale-linked refunds, and stock adjustments, and provides inventory information and operational reports.

### Specific objectives

1. Maintain searchable products, categories, selling units, suppliers, and authorized staff accounts.
2. Update stock when a valid delivery, sale, return, or adjustment is posted, while rejecting changes that would create negative stock.
3. Calculate sale totals, required cash tender, and change using server-validated quantities and prices.
4. Link returns to original sale items, limit returns to the remaining eligible quantity, and restore only saleable quantities to available stock.
5. Record opening stock and stock corrections with reasons and responsible users.
6. Provide a MySQL-backed dashboard, stock views, transaction histories, and searchable, date-filtered operational reports.
7. Preserve transaction prices and costs, record significant actions, and enforce the proposed owner/manager and cashier permissions.

## 5. Target users

| User or participant | Proposed involvement |
|---|---|
| Owner / store manager | Maintain master records and staff access; record deliveries; set prices; process sales, returns, and stock adjustments; review all reports and activity history. |
| Cashier / store attendant | Search active products and available stock, complete cash sales, and view permitted sales and receipts. Exact visibility rules remain to be finalized. |
| Walk-in customer | Purchase goods and, where permitted by the eventual return policy, request a sale-linked return. No application account. |
| Supplier | Provide goods and delivery information that authorized staff record. No application account. |

The division of work is a proposed model, not a confirmed staffing arrangement.

## 6. Scope

The first version will cover one shop and the following functions:

| Area | Included functions |
|---|---|
| Access | Reuse and adapt the instructor-provided authentication after inspecting its files; enforce roles on protected pages and action handlers. |
| Master records | Add, search, edit, and deactivate products, categories, units, suppliers, and staff accounts as appropriate. Preserve referenced history. |
| Receiving | Record supplier references, products, positive whole-number quantities, and unit costs; post received goods to stock. |
| Cash sales | Build a cart, recheck available stock, calculate totals and cash change, and produce a sale detail/receipt view. |
| Customer returns | Select an original posted sale, record returned and saleable quantities, calculate the refund using original prices, and restore eligible stock. |
| Stock control | Show stock on hand, low-stock and out-of-stock products, movement history, opening quantities, and reasoned stock adjustments. |
| Dashboard | Show database-derived sales, refunds, net cash takings, stock alerts, recent records, and a useful seven-day sales/refunds chart. Cashier information will follow permissions. |
| Reports | Show sales, returns/refunds, stock on hand, stock movements, received goods, and top-selling products with relevant filters and printable results. |
| Accountability | Automatically record significant account/master-data changes and transaction posting in a read-only activity history. |

Products will use whole-number quantities and one stock/selling unit each. A pack and an individually sold piece will be separate product records; automatic conversion is outside this version. Barcode hardware is not required.

The baseline design contains **15 purposeful database tables** and **20 planned functional pages/forms**, including **19 after login**. Their existing purposes and relationships are preserved in the [detailed handoff](context/START-HERE-Grocery-Project.md). These counts describe the design, not completed software. Add/edit dialogs are not counted as separate pages just to increase the total.

## 7. Limitations and pending decisions

The initial version excludes multiple branches, weighted goods, pack-to-piece conversion, customer credit, installments, supplier balances, online ordering, online payments, delivery routing, batch-level expiry tracking, and profit accounting.

The following decisions remain open:

- Currency, applicable pricing/tax treatment, and required receipt fields.
- Return eligibility, timing, refund approval, and the exact role permissions.
- How posted transactions will be corrected or reversed while preserving stock, cash, and audit history.
- Final field definitions and constraints, the full database dictionary, and formal diagram presentation.
- Compatibility with the actual instructor-provided login code and session structure.

Ordinary product edits will not directly change stock. Draft transactions will not affect stock. Posted history will be preserved; the proposal does not authorize silently editing or deleting posted transactions. A correction policy must be designed before implementing that workflow.

Sales and refunds will support operational cash summaries. **Net cash takings mean sales posted during a period minus refunds posted during that period, including refunds for earlier sales. They are not profit.** No stock-costing method for profit reporting has been designed.

## 8. Black-box analysis: inputs, processes, and outputs

This is an analysis of the **proposed system**, not evidence of an observed manual process.

| Workflow | Inputs | Processes | Outputs |
|---|---|---|---|
| Maintain records | Product details, prices, category/unit, supplier details, staff access details | Validate required and unique values; apply permissions; save or deactivate records | Searchable master records and relevant activity entries |
| Receive goods | Supplier, delivery reference, products, quantities, costs | Validate lines and permissions; calculate total; post document and stock increases together | Receiving record, total cost, updated quantities |
| Complete cash sale | Products, quantities, cash tender | Recheck and lock stock during posting; calculate totals/change on the server; commit sale and stock changes together | Receipt details, change due, reduced stock |
| Process return | Original sale/lines, returned quantity, saleable quantity, reason | Check remaining returnable quantities; calculate refund at original price; post refund and eligible stock increases together | Return record, refund total, updated saleable stock |
| Adjust stock | Products, signed quantity changes, reason | Check authorization and resulting stock; post documented changes together | Adjustment record, revised stock, movement history |
| Review operations | Date range and relevant product, supplier, receipt, or status filters | Query permitted posted records and current stock | Dashboard, filtered histories, reports and printable views |

A failed transaction post must leave no partial document, inventory change, or misleading posting log. Totals and quantities must be validated in PHP as well as through browser controls.

## 9. Proposed technology and design

The application will use HTML, CSS, JavaScript, and Bootstrap or another appropriate CSS framework, with PHP for server processing and MySQL for persistent relational storage. Development and demonstration will use XAMPP, Visual Studio Code, and a supported browser such as Chrome, Edge, or Firefox.

Reusable PHP components will provide database access, navigation, layouts, and access checks. The database design will use primary and foreign keys, suitable uniqueness constraints, decimal money fields, historical sale-price and receiving-cost values, and database transactions for posting. Stock will reconcile to posted receiving minus posted sales plus posted saleable returns plus posted signed adjustments.

The supplied login is an integration dependency. Its actual files have not been identified as the project's authentication source or verified; a second disconnected login system is not part of the plan.

## 10. Expected benefits

The intended benefits are easier retrieval of sales and deliveries, clearer stock movement records, consistent transaction calculations, and useful daily operational information. These are expected outcomes of the proposed design. No measured time savings, error reductions, or business results are claimed.

## 11. Proposed acceptance checks

These are future checks for the implemented system. **They have not been run against an application.** Amounts below have no currency symbol because currency is undecided.

| Check | Expected result |
|---|---|
| Opening 10 units, receive 8, sell 3, accept 1 saleable return, adjust down 2 | Stock equals `10 + 8 - 3 + 1 - 2 = 14`. |
| Sell 3 units at 25.00, accept 100.00 cash, refund 1 unit the same day | Sale total 75.00, change 25.00, refund 25.00, net cash takings 50.00 for these transactions. |
| Save a draft or fail a posting validation | A draft leaves stock unchanged; a failed post leaves no partial committed transaction or stock change. |
| Submit a posting twice or sell the last units concurrently | One posting takes effect; concurrent posting cannot create negative stock. |
| Return a sale item more than once | Cumulative posted returns cannot exceed the original sold quantity; refund uses the original price; only saleable quantities restore stock. |
| Change a current product price or deactivate a referenced record | Earlier transaction values and relationships remain intact. |
| Request an owner-only page or action as cashier | Access is denied, including direct requests to action handlers. |
| Compare dashboard/reports with posted records and restart XAMPP | Totals agree; saved records survive refresh, logout/login, and restart. |

## 12. Work required before implementation

Continue by drafting the posted-transaction correction policy and the complete database dictionary, then align the ERD, flowchart, use cases, permissions, and page list. Inspect the actual authentication files before settling the user/session integration.

If an accessible shop becomes available, use the [research worksheet](02-RESEARCH-VALIDATION.md) to record actual manual inputs, steps, outputs, roles, and rules. Update the proposal only from documented findings. The fictional scenario can support design work, but it does not establish the assignment's real-process research evidence.

## References and basis

- The supplied [project handoff](context/START-HERE-Grocery-Project.md), prepared October 30, 2026, is the source of the existing scenario, scope, 15-table/20-page baseline, and proposed acceptance example.
- *Semi-Final-and-Final-Topic.pdf* is the assignment guide. Proposal and other pre-coding submissions are listed on pages 12-13; size requirements appear on pages 5 and 7. A proposal draft does not complete the research, dictionary, diagram review, implementation, or demonstration requirements.
