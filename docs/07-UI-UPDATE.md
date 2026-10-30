# Interface update and light/dark mode

Updated October 30, 2026.

The interface now uses compact grouped navigation, consistent icons, cleaner tables and forms, updated sign-in/registration screens, a common-scale sales/refunds chart, and responsive checkout fields. The light/dark button is available on public and signed-in screens.

## Update the live website

1. Download and extract the latest `releases/SmallGrocery-htdocs.zip` on your computer.
2. In InfinityFree File Manager, open the domain's `htdocs` folder.
3. Upload/replace the extracted **app** and **public** folders, keeping their contents and paths intact. Do not delete the whole hosting directory.
4. Keep the existing server **config.local.php**. It contains your live database/Gmail settings and is deliberately excluded from the ZIP.
5. Refresh the website. If it still shows old styling, use a hard refresh (Ctrl+F5 on Windows). The shared templates also use content-versioned CSS/JS addresses to refresh changed assets.

**No SQL import or database migration is required for this update.** Existing products, users, sales, OTP invitations, and Gmail settings stay in their existing database/configuration. Do not reimport fresh SQL or first-owner SQL for a UI update.

The full VS Code source is also available in `releases/SmallGrocery-Latest.zip`. The changed runtime files are:

- `app/ui.php` (new shared head, icons, and theme/password controls)
- `app/views.php`
- `app/registration_views.php`
- `public/index.php`
- `public/assets/app.css`
- `public/assets/app.js`
- `public/assets/theme.js` (new)

The new files must be uploaded too; replacing only the CSS will not install the theme controls and updated layout.

## Theme behavior

- First visit follows the browser/system appearance.
- The button switches between light and dark, and its label describes the next action.
- A chosen appearance is remembered on that browser for this website and synchronized across its tabs.
- The saved theme is applied before the stylesheet paints, including sign-in and email-registration pages.
- If browser storage is blocked, switching still works for the current page.
- Print styles use light paper colors and hide navigation/appearance controls.

## Mobile behavior

- Below the sidebar breakpoint, use the menu button to open navigation. Close it with its close button, the backdrop, or Escape.
- The open drawer keeps keyboard focus inside navigation and makes the main workspace inert.
- Checkout, receiving, return, and adjustment lines stack as labeled fields on phone-sized screens.
- Long history/report tables scroll inside their container.

## Verification

- PHP syntax checks passed for the updated templates/controller.
- Five Node theme tests passed: system fallback, persisted choice, reload, cross-tab updates, and blocked storage.
- HTTP checks passed for 24 authenticated route variants, the public theme head, and six cashier access denials. Account mutations still check server permissions and CSRF.
- Browser review covered both appearances, mobile navigation, registration/password visibility, theme persistence across pages/reload, and checkout's 75.00 total / 25.00 change example.
- Release archives were checked against their file hashes and private-file exclusions.

This is a locally verified interface update. Uploading it to the live server is separate from verifying that server's database and actual Gmail OTP delivery.
