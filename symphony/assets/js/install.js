/*!
 * Sym8
 * https://sym8.io
 * @author: tiloschroeder
 * Copyright (c) 2026
 * License MIT
 */
function init(o) {
  "loading" !== document.readyState
  ? o()
  : document.addEventListener
  ? document.addEventListener("DOMContentLoaded", o)
  : document.attachEvent("onreadystatechange", function () {
    "complete" === document.readyState && o();
  });
}

init(function () {
  // prevent re-submitting a form via reload
  if (window.history.replaceState) {
    window.history.replaceState(null, "", window.location.href);
  }

  // Provide a visual cue for required fields when native browser
  // validation feedback is unavailable or insufficient.
  const form = document.querySelector('form');
  const submitBtn = document.querySelector('input[type="submit"]');
  if (form && submitBtn) {
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
      const initialValue = field.value;
      field.addEventListener('invalid', function () {
        this.setAttribute('aria-invalid', 'true');
      });

      field.addEventListener('input', function () {
        if (
            initialValue === '' &&
            this.value.trim() !== ''  &&
            this.getAttribute('aria-invalid') === 'true'
        ) {
          this.removeAttribute('aria-invalid');
        }
      });
    });
  }

  // toggle database password field
  const btnDb = document.querySelector('.show-hide-db-password');
  const passwordDb = document.querySelector('#database-password');
  if (btnDb && passwordDb) {
    btnDb.addEventListener("click", function (e) {
      // prevent form submit
      e.preventDefault();

      // toggle the type attribute
      const type = passwordDb.getAttribute("type") === "password" ? "text" : "password";
      passwordDb.setAttribute("type", type);

      // toggle the aria-pressed attribute
      const isPressed = btnDb.getAttribute('aria-pressed') === 'true';
      const nextState = !isPressed;

      btnDb.setAttribute('aria-pressed', String(nextState));
      btnDb.setAttribute(
        'aria-label',
        nextState ? btnDb.dataset.labelHide : btnDb.dataset.labelShow
      );

      // toggle the visibility icons
      const iconHide = btnDb.querySelector('.icon-hide');
      const iconShow = btnDb.querySelector('.icon-show');
      iconHide.toggleAttribute("hidden");
      iconShow.toggleAttribute("hidden");

      // Set the focus back to the password field.
      // This keeps the on-screen keyboard open on touch devices.
      // Tabbed users on desktop can immediately continue editing the password.
      passwordDb.focus();
    });
  }

  // toggle both password fields
  const btn = document.querySelector('.show-hide-password');
  const password = document.querySelector('#password');
  const passwordConfirm = document.querySelector('#password-confirm');
  if (btn && password) {
    btn.addEventListener("click", function (e) {
      // prevent form submit
      e.preventDefault();

      // toggle the type attribute for both fields
      const type = password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);
      passwordConfirm.setAttribute("type", type);

      // toggle the aria-pressed attribute
      const isPressed = btn.getAttribute('aria-pressed') === 'true';
      const nextState = !isPressed;

      btn.setAttribute('aria-pressed', String(nextState));
      btn.setAttribute(
        'aria-label',
        nextState ? btn.dataset.labelHide : btn.dataset.labelShow
      );

      // toggle the visibility icons
      const iconHide = btn.querySelector('.icon-hide');
      const iconShow = btn.querySelector('.icon-show');
      iconHide.toggleAttribute("hidden");
      iconShow.toggleAttribute("hidden");

      // Set the focus back to the password field.
      // This keeps the on-screen keyboard open on touch devices.
      // Tabbed users on desktop can immediately continue editing the password.
      password.focus();
    });
  }

});
