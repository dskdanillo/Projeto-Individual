(function () {
  const KEY = 'dsk_theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.body && document.body.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-btn]').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-theme-btn') === theme);
    });
    localStorage.setItem(KEY, theme);
  }

  function getTheme() {
    return localStorage.getItem(KEY) || 'dark';
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getTheme());
    document.querySelectorAll('[data-theme-btn]').forEach(btn => {
      btn.addEventListener('click', function () {
        applyTheme(this.getAttribute('data-theme-btn'));
      });
    });
  });

  // apply immediately to avoid flash, before DOMContentLoaded paints body
  document.documentElement.setAttribute('data-theme', getTheme());

  window.DSKTheme = { applyTheme, getTheme };
})();
