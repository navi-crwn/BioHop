/* BioHop documentation pages — theme toggle + persisted preference.
   Original, dependency-free. Respects system preference and reduced motion. */
(function () {
  "use strict";

  var KEY = "biohop-docs-theme";

  function systemTheme() {
    return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  }

  function stored() {
    try {
      var v = window.localStorage.getItem(KEY);
      return v === "light" || v === "dark" ? v : null;
    } catch (e) {
      return null;
    }
  }

  function apply(theme, persist) {
    var root = document.documentElement;
    if (theme === systemTheme()) {
      root.removeAttribute("data-theme");
    } else {
      root.setAttribute("data-theme", theme);
    }
    if (persist) {
      try { window.localStorage.setItem(KEY, theme); } catch (e) { /* storage unavailable */ }
    }
    var btn = document.getElementById("theme-toggle");
    if (btn) {
      var next = theme === "dark" ? "light" : "dark";
      var label = btn.getAttribute("data-label-" + next);
      if (!label) { label = "Switch to " + next + " theme"; }
      btn.textContent = label;
      btn.setAttribute("data-action", next);
    }
  }

  function init() {
    apply(stored() || systemTheme(), false);

    var btn = document.getElementById("theme-toggle");
    if (btn) {
      btn.addEventListener("click", function () {
        var root = document.documentElement;
        var current = root.getAttribute("data-theme") || systemTheme();
        apply(current === "dark" ? "light" : "dark", true);
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
