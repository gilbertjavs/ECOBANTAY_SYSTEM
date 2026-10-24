(function () {
  var eyeIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  var hiddenIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4.1-.9"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>';

  document.querySelectorAll('input[type="password"]').forEach(function (input) {
    if (input.dataset.visibilityToggle === 'ready') return;
    input.dataset.visibilityToggle = 'ready';

    var wrapper = document.createElement('span');
    wrapper.className = 'password-visibility';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-visibility-toggle';
    button.setAttribute('aria-label', 'Show password');
    button.setAttribute('aria-pressed', 'false');
    button.title = 'Show password';
    button.innerHTML = eyeIcon;
    wrapper.appendChild(button);

    button.addEventListener('click', function () {
      var showPassword = input.type === 'password';
      input.type = showPassword ? 'text' : 'password';
      button.innerHTML = showPassword ? hiddenIcon : eyeIcon;
      button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
      button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
      button.title = showPassword ? 'Hide password' : 'Show password';
      input.focus();
    });
  });
})();
