(function () {
  'use strict';

  var form = document.getElementById('custoForm');
  if (!form) return;

  var categoria = document.getElementById('categoria');
  var valor = document.getElementById('valorCusto');
  var valorLabel = document.getElementById('valorLabel');
  var info = document.getElementById('combustivelInfo');
  var km = parseFloat(form.getAttribute('data-km')) || 0;
  var multiplicador = form.getAttribute('data-ida-volta') === '1' ? 2 : 1;
  var consumo = parseFloat(form.getAttribute('data-consumo-km-l')) || 0;
  var preco = parseFloat(form.getAttribute('data-preco-combustivel')) || 0;
  var eraCombustivel = categoria.value === 'Combustível';

  function atualizarValor() {
    var combustivel = categoria.value === 'Combustível';
    if (eraCombustivel && !combustivel) valor.value = '';
    eraCombustivel = combustivel;
    valor.readOnly = combustivel;
    valorLabel.textContent = combustivel
      ? 'Valor calculado por trecho (R$)'
      : (multiplicador === 2 ? 'Valor por trecho (R$)' : 'Valor (R$)');

    if (!combustivel) {
      info.textContent = '';
      return;
    }

    if (!consumo || !preco) {
      valor.value = '';
      info.textContent = 'Cadastre o consumo (km/L) e o preço por litro em Dados pessoais.';
      return;
    }

    var custoTrecho = Math.round((km / consumo * preco) * 100) / 100;
    var litros = km * multiplicador / consumo;
    var custoTotal = custoTrecho * multiplicador;
    valor.value = custoTrecho.toFixed(2).replace('.', ',');
    info.textContent = 'Consumo estimado: ' + litros.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) +
      ' L · total da rota: R$ ' + custoTotal.toFixed(2).replace('.', ',');
  }

  categoria.addEventListener('change', atualizarValor);
  atualizarValor();
})();
