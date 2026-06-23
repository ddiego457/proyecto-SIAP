(function () {
  const input = document.getElementById('password');
  const toggle = document.getElementById('togglePassword');
  if (!input || !toggle) return;

  function setExpanded(isShown) {
    input.type = isShown ? 'text' : 'password';
  }

  function getCurrentShown() {
    return input.type === 'text';
  }

  toggle.addEventListener('click', function () {
    setExpanded(!getCurrentShown());
    toggle.setAttribute(
      'aria-label',
      !getCurrentShown() ? 'Ocultar contraseña' : 'Mostrar contraseña'
    );
  });

  toggle.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggle.click();
    }
  });
})();
