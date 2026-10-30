(function () {
  'use strict';

  document.querySelectorAll('[data-date-filter]').forEach(function (form) {
    var select = form.querySelector('[name="periodo"]');
    var fields = form.querySelectorAll('[data-period-field]');
    if (!select) return;

    function atualizarCampos() {
      fields.forEach(function (field) {
        var ativo = field.getAttribute('data-period-field') === select.value;
        field.hidden = !ativo;
        field.querySelectorAll('input').forEach(function (input) {
          input.disabled = !ativo;
          input.required = ativo;
        });
      });
    }

    select.addEventListener('change', atualizarCampos);
    atualizarCampos();
  });
})();
