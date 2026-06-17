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
});
