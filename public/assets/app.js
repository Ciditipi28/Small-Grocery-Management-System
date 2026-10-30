document.addEventListener('click', (event) => {
  const target = event.target;
  const confirmation = target.closest('[data-confirm]');
  if (confirmation && !window.confirm(confirmation.dataset.confirm)) event.preventDefault();
  if (target.closest('.print-button')) window.print();
  if (target.closest('#add-line')) {
    const body = document.querySelector('#transaction-lines');
    if (body.children.length >= 100) return;
    const line = body.firstElementChild.cloneNode(true);
    line.querySelectorAll('input').forEach(input => input.value = input.name === 'quantity[]' ? '1' : '0');
    line.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    body.append(line);
  }
  if (target.closest('.remove-line')) {
    const line = target.closest('tr');
    if (line.parentElement.children.length > 1) line.remove();
  }
  updateCheckout();
});
function updateCheckout() {
  const output = document.querySelector('#checkout-sum');
  if (!output) return;
  let total = 0;
  document.querySelectorAll('.transaction-line').forEach(line => {
    const option = line.querySelector('select').selectedOptions[0];
    const price = Math.round(Number(option?.dataset.price || 0) * 100);
    const quantity = Number(line.querySelector('[name="quantity[]"]').value || 0);
    total += price * quantity;
  });
  const cash = Math.round(Number(document.querySelector('[name="cash_tendered"]').value || 0) * 100);
  output.textContent = (total / 100).toFixed(2);
  document.querySelector('#checkout-change').textContent = ((cash - total) / 100).toFixed(2);
}
document.addEventListener('input', updateCheckout);
document.addEventListener('change', event => {
  if (event.target.matches('[data-confirm-off]') && !event.target.checked && !window.confirm(event.target.dataset.confirmOff)) event.target.checked = true;
  updateCheckout();
});
updateCheckout();
document.addEventListener('submit', event => {
  const button = event.submitter;
  if (button) { button.disabled = true; button.textContent = 'Saving…'; }
});
