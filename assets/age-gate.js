/**
 * Zillha Subscriber Gate — Age Gate
 *
 * Client-side DOB verification modal. Reads a cookie on load; if absent,
 * injects a modal requiring the visitor to confirm their date of birth.
 * Valid 18+ visitors get a 1-year cookie; underage or denying visitors
 * are redirected to a configured URL.
 */
(function () {
  'use strict';

  var config = window.zsgAgeGate || {};
  var COOKIE_NAME = config.cookieName || 'zsg_age_verified';
  var COOKIE_DAYS = typeof config.cookieDays === 'number' ? config.cookieDays : 365;
  var MIN_AGE = 18;

  if (zsgGetCookie(COOKIE_NAME) === '1') {
    return;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /**
   * Bootstrap the modal: inject, focus, and wire event handlers.
   *
   * @returns {void}
   */
  function init() {
    var overlay = buildModal();
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    var modal = overlay.querySelector('#zsg-age-gate-modal');
    var form = overlay.querySelector('#zsg-age-gate-form');
    var denyBtn = overlay.querySelector('#zsg-age-deny');
    var errorEl = overlay.querySelector('#zsg-age-gate-error');

    trapFocus(modal);
    focusFirst(modal);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      handleSubmit(overlay, form, errorEl);
    });

    denyBtn.addEventListener('click', function () {
      redirectAway();
    });
  }

  /**
   * Handle submit: validate DOB, set cookie if 18+, redirect if under.
   *
   * @param {HTMLElement} overlay The overlay wrapper element.
   * @param {HTMLFormElement} form The DOB form.
   * @param {HTMLElement} errorEl The error message container.
   * @returns {void}
   */
  function handleSubmit(overlay, form, errorEl) {
    var month = parseInt(form.elements.month.value, 10);
    var day = parseInt(form.elements.day.value, 10);
    var year = parseInt(form.elements.year.value, 10);

    if (!isValidDate(year, month, day)) {
      showError(errorEl);
      return;
    }

    var dob = new Date(year, month - 1, day);
    if (dob.getFullYear() !== year || dob.getMonth() !== month - 1 || dob.getDate() !== day) {
      showError(errorEl);
      return;
    }

    var age = computeAge(dob);

    if (age < MIN_AGE) {
      redirectAway();
      return;
    }

    zsgSetCookie(COOKIE_NAME, '1', COOKIE_DAYS);
    dismiss(overlay);
  }

  /**
   * Show the validation error message.
   *
   * @param {HTMLElement} errorEl The error element.
   * @returns {void}
   */
  function showError(errorEl) {
    errorEl.hidden = false;
  }

  /**
   * Compute the visitor's age in whole years from a DOB.
   *
   * @param {Date} dob Date of birth.
   * @returns {number} Age in years.
   */
  function computeAge(dob) {
    var today = new Date();
    var age = today.getFullYear() - dob.getFullYear();
    var m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    return age;
  }

  /**
   * Validate that year/month/day form a plausible date.
   *
   * @param {number} year Four-digit year.
   * @param {number} month 1-12.
   * @param {number} day 1-31.
   * @returns {boolean}
   */
  function isValidDate(year, month, day) {
    if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
      return false;
    }
    if (year < 1900 || year > new Date().getFullYear()) {
      return false;
    }
    if (month < 1 || month > 12) {
      return false;
    }
    if (day < 1 || day > 31) {
      return false;
    }
    return true;
  }

  /**
   * Remove the overlay and restore body scroll.
   *
   * @param {HTMLElement} overlay The overlay wrapper element.
   * @returns {void}
   */
  function dismiss(overlay) {
    if (overlay && overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }
    document.body.style.overflow = '';
  }

  /**
   * Redirect the visitor to the configured URL.
   *
   * @returns {void}
   */
  function redirectAway() {
    var url = config.redirectUrl || '/';
    window.location.replace(url);
  }

  /**
   * Build the modal DOM and return the overlay wrapper.
   *
   * @returns {HTMLElement}
   */
  function buildModal() {
    var overlay = document.createElement('div');
    overlay.id = 'zsg-age-gate-overlay';

    var modal = document.createElement('div');
    modal.id = 'zsg-age-gate-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'zsg-age-gate-title');

    var title = document.createElement('h2');
    title.id = 'zsg-age-gate-title';
    title.textContent = config.titleLabel || 'Age Verification Required';
    modal.appendChild(title);

    var warning = document.createElement('p');
    warning.id = 'zsg-age-gate-warning';
    warning.textContent = config.warningMessage || '';
    modal.appendChild(warning);

    var form = document.createElement('form');
    form.id = 'zsg-age-gate-form';
    form.setAttribute('novalidate', 'novalidate');

    var fieldset = document.createElement('fieldset');
    var legend = document.createElement('legend');
    legend.textContent = config.legendLabel || 'Enter your date of birth';
    fieldset.appendChild(legend);

    fieldset.appendChild(buildMonthField());
    fieldset.appendChild(buildDayField());
    fieldset.appendChild(buildYearField());

    form.appendChild(fieldset);

    var error = document.createElement('p');
    error.id = 'zsg-age-gate-error';
    error.hidden = true;
    error.setAttribute('aria-live', 'polite');
    error.textContent = config.errorMessage || 'Please enter a valid date of birth.';
    form.appendChild(error);

    var confirmBtn = document.createElement('button');
    confirmBtn.type = 'submit';
    confirmBtn.id = 'zsg-age-confirm';
    confirmBtn.textContent = config.confirmLabel || 'I am 18 or older — Enter';
    form.appendChild(confirmBtn);

    var denyBtn = document.createElement('button');
    denyBtn.type = 'button';
    denyBtn.id = 'zsg-age-deny';
    denyBtn.textContent = config.denyLabel || 'I am under 18 — Exit';
    form.appendChild(denyBtn);

    modal.appendChild(form);
    overlay.appendChild(modal);
    return overlay;
  }

  /**
   * Build the month select field with its label.
   *
   * @returns {DocumentFragment}
   */
  function buildMonthField() {
    var frag = document.createDocumentFragment();
    var label = document.createElement('label');
    label.setAttribute('for', 'zsg-dob-month');
    label.textContent = config.monthLabel || 'Month';
    frag.appendChild(label);

    var select = document.createElement('select');
    select.id = 'zsg-dob-month';
    select.name = 'month';
    select.required = true;

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '--';
    select.appendChild(placeholder);

    var names = config.monthNames || [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    for (var i = 0; i < 12; i++) {
      var opt = document.createElement('option');
      opt.value = String(i + 1);
      opt.textContent = names[i];
      select.appendChild(opt);
    }
    frag.appendChild(select);
    return frag;
  }

  /**
   * Build the day input field with its label.
   *
   * @returns {DocumentFragment}
   */
  function buildDayField() {
    var frag = document.createDocumentFragment();
    var label = document.createElement('label');
    label.setAttribute('for', 'zsg-dob-day');
    label.textContent = config.dayLabel || 'Day';
    frag.appendChild(label);

    var input = document.createElement('input');
    input.type = 'number';
    input.id = 'zsg-dob-day';
    input.name = 'day';
    input.min = '1';
    input.max = '31';
    input.required = true;
    input.placeholder = config.dayPlaceholder || 'DD';
    input.inputMode = 'numeric';
    frag.appendChild(input);
    return frag;
  }

  /**
   * Build the year input field with its label.
   *
   * @returns {DocumentFragment}
   */
  function buildYearField() {
    var frag = document.createDocumentFragment();
    var label = document.createElement('label');
    label.setAttribute('for', 'zsg-dob-year');
    label.textContent = config.yearLabel || 'Year';
    frag.appendChild(label);

    var input = document.createElement('input');
    input.type = 'number';
    input.id = 'zsg-dob-year';
    input.name = 'year';
    input.min = '1900';
    input.required = true;
    input.placeholder = config.yearPlaceholder || 'YYYY';
    input.inputMode = 'numeric';
    frag.appendChild(input);
    return frag;
  }

  /**
   * Focus the first focusable element inside the container.
   *
   * @param {HTMLElement} container The modal element.
   * @returns {void}
   */
  function focusFirst(container) {
    var list = getFocusable(container);
    if (list.length) {
      list[0].focus();
    }
  }

  /**
   * Trap Tab / Shift+Tab focus within the modal. Escape is intentionally
   * ignored — the visitor must either confirm or deny.
   *
   * @param {HTMLElement} container The modal element.
   * @returns {void}
   */
  function trapFocus(container) {
    container.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') {
        return;
      }
      var list = getFocusable(container);
      if (!list.length) {
        return;
      }
      var first = list[0];
      var last = list[list.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });
  }

  /**
   * Collect focusable descendants of a container.
   *
   * @param {HTMLElement} container The modal element.
   * @returns {HTMLElement[]}
   */
  function getFocusable(container) {
    var selector = 'input, select, button, [tabindex]:not([tabindex="-1"])';
    var nodes = container.querySelectorAll(selector);
    var out = [];
    for (var i = 0; i < nodes.length; i++) {
      if (!nodes[i].disabled) {
        out.push(nodes[i]);
      }
    }
    return out;
  }

  /**
   * Set a cookie with the given name, value, and lifetime in days.
   *
   * @param {string} name Cookie name.
   * @param {string} value Cookie value.
   * @param {number} days Lifetime in days.
   * @returns {void}
   */
  function zsgSetCookie(name, value, days) {
    var maxAge = Math.floor(days * 86400);
    document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) +
      '; max-age=' + maxAge + '; path=/; SameSite=Lax';
  }

  /**
   * Read a cookie by name.
   *
   * @param {string} name Cookie name.
   * @returns {string|null}
   */
  function zsgGetCookie(name) {
    var prefix = encodeURIComponent(name) + '=';
    var parts = document.cookie ? document.cookie.split(';') : [];
    for (var i = 0; i < parts.length; i++) {
      var c = parts[i].replace(/^\s+/, '');
      if (c.indexOf(prefix) === 0) {
        return decodeURIComponent(c.substring(prefix.length));
      }
    }
    return null;
  }
})();
