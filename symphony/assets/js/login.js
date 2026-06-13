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
  const submitBtn = document.querySelector('button[type="submit"]');
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
            this.value.trim() !== '' &&
            this.getAttribute('aria-invalid') === 'true'
        ) {
          this.removeAttribute('aria-invalid');
        }
      });
    });
  }

  let suppressScroll = false;
  // toggle password field
  const btn = document.querySelector('.show-hide-password');
  const password = document.querySelector('#password');
  if (btn && password) {
    btn.addEventListener("click", function (e) {
      // prevent form submit
      e.preventDefault();

      // toggle the type attribute
      const type = password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);

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
      suppressScroll = true;
      password.focus();
    });
  }

  // If the field "Password" is obscured by the on-screen keyboard,
  // move it into the viewport.
  if (password) {
    if (window.matchMedia('(pointer: coarse)').matches) {
      password.addEventListener('focus', function () {
        if (suppressScroll) {
          suppressScroll = false;
          return;
        }
        password.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      });
    }
  }

  // Temporarily disable the virtual keyboard on mobile devices
  // while applying focus to the first input field.
  // This keeps the visual focus state without immediately opening
  // the on-screen keyboard in mobile browsers.
  window.addEventListener('load', function () {
    const field = document.querySelector('[data-autofocus]');
    if (field) {
      if (window.matchMedia('(pointer: coarse)').matches) {
        field.setAttribute('inputmode', 'none');
        field.focus();
        setTimeout(() => {
          field.removeAttribute('inputmode');
        }, 50);
      } else {
        field.focus();
      }
    }
  });

});
