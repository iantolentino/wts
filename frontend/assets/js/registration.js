(() => {
  const form = document.querySelector('[data-registration]');
  if (!form) return;
  const password = form.elements.password;
  const confirmation = form.elements.confirm_password;
  const feedback = document.getElementById('password-feedback');
  const show = document.getElementById('show-passwords');
  const update = () => {
    const match = password.value === confirmation.value;
    confirmation.setCustomValidity(confirmation.value && !match ? 'Passwords do not match.' : '');
    feedback.textContent = !confirmation.value ? 'Re-enter your password to confirm it.' : match ? 'Passwords match.' : 'Passwords do not match.';
    feedback.dataset.match = confirmation.value ? String(match) : '';
  };
  password.addEventListener('input', update);
  confirmation.addEventListener('input', update);
  show.addEventListener('change', () => {
    password.type = confirmation.type = show.checked ? 'text' : 'password';
  });
  update();
})();
