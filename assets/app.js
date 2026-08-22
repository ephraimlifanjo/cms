document.addEventListener('click', async (event) => {
  const copy = event.target.closest('[data-copy]');
  if (copy) {
    try { await navigator.clipboard.writeText(copy.dataset.copy || ''); copy.textContent = 'Copié ✓'; setTimeout(() => copy.textContent = 'Copier', 1400); } catch (_) {}
  }
  const toggle = event.target.closest('[data-menu-toggle]');
  if (toggle) document.querySelector('.dash-sidebar')?.classList.toggle('open');
});
document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
  if (!window.confirm(form.dataset.confirm || 'Confirmer cette action ?')) event.preventDefault();
}));
document.querySelectorAll('input[type="color"]').forEach((input) => input.addEventListener('input', () => {
  const code = input.parentElement?.querySelector('code'); if (code) code.textContent = input.value;
}));
