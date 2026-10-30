'use strict';

const menuButtons = () => document.querySelectorAll('[data-menu-toggle]');
const mobileNavigation = typeof window.matchMedia === 'function'
  ? window.matchMedia('(max-width: 900px)')
  : null;

function setNavigationOpen(open, restoreFocus = false) {
  const sidebar = document.querySelector('#app-sidebar');
  if (!sidebar) return false;
  const isMobile = mobileNavigation ? mobileNavigation.matches : window.innerWidth <= 900;
  const shouldOpen = Boolean(open && isMobile);
  document.body.classList.toggle('nav-open', shouldOpen);
  menuButtons().forEach(button => button.setAttribute('aria-expanded', String(shouldOpen)));
  const backdrop = document.querySelector('.sidebar-backdrop[data-nav-close]');
  if (backdrop) backdrop.hidden = !shouldOpen;
  // Release the background before returning focus to its menu button.
  const workspace = document.querySelector('.workspace');
  if (workspace) workspace.inert = shouldOpen;
  if (restoreFocus) document.querySelector('[data-menu-toggle]')?.focus();
  return shouldOpen;
}

document.addEventListener('click', event => {
  if (!(event.target instanceof Element)) return;
  const target = event.target;

  const menu = target.closest('[data-menu-toggle]');
  if (menu) {
    const open = !document.body.classList.contains('nav-open');
    if (setNavigationOpen(open)) document.querySelector('#app-sidebar nav a')?.focus();
    return;
  }
  if (target.closest('[data-nav-close]')) {
    setNavigationOpen(false, true);
    return;
  }
  if (target.closest('#app-sidebar nav a') && mobileNavigation?.matches) setNavigationOpen(false);

  const passwordButton = target.closest('[data-password-toggle]');
  if (passwordButton) {
    const inputId = passwordButton.getAttribute('aria-controls');
    const input = inputId ? document.getElementById(inputId) : null;
    if (!(input instanceof HTMLInputElement)) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    passwordButton.setAttribute('aria-pressed', String(visible));
    passwordButton.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    passwordButton.setAttribute('title', visible ? 'Hide password' : 'Show password');
    const label = passwordButton.querySelector('[data-password-label]');
    if (label) label.textContent = visible ? 'Hide' : 'Show';
    return;
  }

  const confirmation = target.closest('[data-confirm]');
  if (confirmation && !window.confirm(confirmation.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  if (target.closest('.print-button')) window.print();
  if (target.closest('#add-line')) {
    const body = document.querySelector('#transaction-lines');
    if (!body?.firstElementChild || body.children.length >= 100) return;
    const line = body.firstElementChild.cloneNode(true);
    line.querySelectorAll('input').forEach(input => input.value = input.name === 'quantity[]' ? '1' : '0');
    line.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    body.append(line);
    line.querySelector('select')?.focus();
  }
  if (target.closest('.remove-line')) {
    const line = target.closest('tr');
    if (line?.parentElement && line.parentElement.children.length > 1) line.remove();
  }
  updateCheckout();
});
function updateCheckout() {
  const output = document.querySelector('#checkout-sum');
  if (!output) return;
  let total = 0;
  document.querySelectorAll('.transaction-line').forEach(line => {
    const select = line.querySelector('select');
    const option = select?.selectedOptions[0];
    const price = Math.round(Number(option?.dataset.price || 0) * 100);
    const quantity = Number(line.querySelector('[name="quantity[]"]')?.value || 0);
    total += price * quantity;
  });
  const cash = Math.round(Number(document.querySelector('[name="cash_tendered"]')?.value || 0) * 100);
  output.textContent = (total / 100).toFixed(2);
  const change = document.querySelector('#checkout-change');
  if (change) change.textContent = ((cash - total) / 100).toFixed(2);
}
document.addEventListener('input', updateCheckout);
document.addEventListener('change', event => {
  if (!(event.target instanceof Element)) return;
  if (event.target.matches('[data-confirm-off]') && !event.target.checked && !window.confirm(event.target.dataset.confirmOff)) event.target.checked = true;
  updateCheckout();
});
document.addEventListener('keydown', event => {
  if (!document.body.classList.contains('nav-open')) return;
  if (event.key === 'Escape') {
    event.preventDefault();
    setNavigationOpen(false, true);
    return;
  }
  if (event.key !== 'Tab') return;
  const sidebar = document.querySelector('#app-sidebar');
  if (!sidebar) return;
  const controls = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(element =>
    element.tabIndex >= 0 && !element.closest('[hidden]') && element.getClientRects().length > 0
  );
  if (!controls.length) return;
  const first = controls[0];
  const last = controls[controls.length - 1];
  const outside = !sidebar.contains(document.activeElement);
  if (event.shiftKey && (document.activeElement === first || outside)) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && (document.activeElement === last || outside)) {
    event.preventDefault();
    first.focus();
  }
});
const resetDesktopNavigation = event => { if (!event.matches) setNavigationOpen(false); };
if (mobileNavigation?.addEventListener) mobileNavigation.addEventListener('change', resetDesktopNavigation);
else if (mobileNavigation?.addListener) mobileNavigation.addListener(resetDesktopNavigation);

updateCheckout();
setNavigationOpen(false);
document.addEventListener('submit', event => {
  if (event.defaultPrevented) return;
  const button = event.submitter;
  if (button) { button.disabled = true; button.textContent = 'Saving…'; }
});
