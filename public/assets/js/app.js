/* DSK - comportamentos comuns */
(function () {
  document.querySelectorAll('[data-uppercase]').forEach(function (input) {
    input.addEventListener('input', function () {
      var start = input.selectionStart;
      var end = input.selectionEnd;
      input.value = input.value.toUpperCase();
      if (start !== null && end !== null) input.setSelectionRange(start, end);
    });
  });

  var sb = document.getElementById('sidebar');
  var bd = document.getElementById('backdrop');
  var bt = document.getElementById('btnToggleSidebar');
  if (bt && sb && bd) {
    bt.addEventListener('click', function () { sb.classList.toggle('open'); bd.classList.toggle('show'); });
    bd.addEventListener('click', function () { sb.classList.remove('open'); bd.classList.remove('show'); });
  }

  document.querySelectorAll('[data-history-back]').forEach(function (button) {
    var storageKey = 'dsk_client_navigation';
    var currentUrl = window.location.pathname + window.location.search;
    var isEditing = new URLSearchParams(window.location.search).has('editar');
    var navigation = JSON.parse(window.sessionStorage.getItem(storageKey) || '[]');

    if (!isEditing) {
      var currentIndex = navigation.lastIndexOf(currentUrl);
      navigation = currentIndex === -1 ? navigation.concat(currentUrl) : navigation.slice(0, currentIndex + 1);
      window.sessionStorage.setItem(storageKey, JSON.stringify(navigation));
    }

    button.addEventListener('click', function () {
      var pages = JSON.parse(window.sessionStorage.getItem(storageKey) || '[]');
      if (!isEditing && pages[pages.length - 1] === currentUrl) {
        pages.pop();
      }
      var previousUrl = pages[pages.length - 1] || button.getAttribute('data-fallback');
      window.sessionStorage.setItem(storageKey, JSON.stringify(pages));
      window.location.href = previousUrl;
    });
  });

  // linhas clicáveis (ignora cliques em botões/links/forms)
  document.querySelectorAll('tr[data-href]').forEach(function (tr) {
    tr.style.cursor = 'pointer';
    tr.addEventListener('click', function (e) {
      if (e.target.closest('a,button,form,input,select')) return;
      window.location = tr.getAttribute('data-href');
    });
  });

  // confirmação antes de excluir
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
