/* Runs in the document head so the selected theme is applied before first paint. */
(() => {
  'use strict';

  const storageKey = 'grocery-theme';
  const systemTheme = typeof window.matchMedia === 'function'
    ? window.matchMedia('(prefers-color-scheme: dark)')
    : null;
  const validTheme = value => value === 'light' || value === 'dark';
  let preference = null;

  try {
    const stored = window.localStorage.getItem(storageKey);
    preference = validTheme(stored) ? stored : null;
  } catch (_) {
    // A private browser or storage policy can deny access; the theme still works.
  }

  function selectedTheme() {
    return preference || (systemTheme?.matches ? 'dark' : 'light');
  }

  function updateControls(theme) {
    const nextLabel = theme === 'dark' ? 'Light mode' : 'Dark mode';
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      button.setAttribute('aria-pressed', String(theme === 'dark'));
      button.setAttribute('aria-label', 'Switch to ' + nextLabel.toLowerCase());
      button.setAttribute('title', 'Switch to ' + nextLabel.toLowerCase());
      button.querySelectorAll('[data-theme-label]').forEach(label => label.textContent = nextLabel);
      button.querySelectorAll('.theme-icon-light').forEach(icon => icon.hidden = theme !== 'dark');
      button.querySelectorAll('.theme-icon-dark').forEach(icon => icon.hidden = theme === 'dark');
    });
  }

  function applyTheme() {
    const theme = selectedTheme();
    document.documentElement.dataset.theme = theme;
    updateControls(theme);
  }

  // No inline style is needed: app.css sets color-scheme for each data-theme value.
  applyTheme();

  document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const button = event.target.closest('[data-theme-toggle]');
    if (!button) return;
    preference = selectedTheme() === 'dark' ? 'light' : 'dark';
    try { window.localStorage.setItem(storageKey, preference); } catch (_) { /* In-memory fallback. */ }
    applyTheme();
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyTheme, { once: true });
  } else {
    applyTheme();
  }

  window.addEventListener('storage', event => {
    if (event.key !== storageKey && event.key !== null) return;
    preference = validTheme(event.newValue) ? event.newValue : null;
    applyTheme();
  });

  const followSystemTheme = () => { if (!preference) applyTheme(); };
  if (systemTheme?.addEventListener) systemTheme.addEventListener('change', followSystemTheme);
  else if (systemTheme?.addListener) systemTheme.addListener(followSystemTheme);
})();
