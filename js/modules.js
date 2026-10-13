(function () {
  'use strict';
  function ready(fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
  ready(function () {
    const congregacaoBusca = document.getElementById('congregacao_busca');
    const congregacaoId = document.getElementById('congregacao_id');
    const balconyFields = document.querySelector('[data-balcao-fields]');
    const responsible = document.getElementById('responsavel_id');
    const sector = document.getElementById('setor_balcao');
    function toggleBalcony() {
      if (!congregacaoId) return;
      const isBalcony = congregacaoId.value === '1';
      if (balconyFields) balconyFields.hidden = !isBalcony;
      if (sector) sector.required = isBalcony;
    }
    if (congregacaoBusca && congregacaoId) {
      congregacaoBusca.addEventListener('input', function () {
        const opts = document.querySelectorAll('[data-option-id]');
        const val = congregacaoBusca.value.trim().toLocaleLowerCase('pt-BR');
        let match = null;
        opts.forEach(function (opt) { if (opt.dataset.optionLabel.toLocaleLowerCase('pt-BR') === val) match = opt; });
        if (match) congregacaoId.value = match.dataset.optionId;
        toggleBalcony();
      });
      document.querySelectorAll('[data-option-id]').forEach(function (opt) {
        opt.addEventListener('click', function () {
          congregacaoBusca.value = opt.dataset.optionLabel;
          congregacaoId.value = opt.dataset.optionId;
          toggleBalcony();
        });
      });
    }
    if (responsible && congregacaoBusca && congregacaoId) {
      responsible.addEventListener('change', function () {
        const opt = responsible.options[responsible.selectedIndex];
        const congregation = opt && opt.dataset.congregacaoId;
        if (congregation) {
          congregacaoId.value = congregation;
          const button = Array.from(document.querySelectorAll('[data-option-id]')).find(function (el) { return el.dataset.optionId === congregation; });
          if (button) congregacaoBusca.value = button.dataset.optionLabel;
          toggleBalcony();
        }
      });
    }
    toggleBalcony();

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        const message = form.dataset.confirm || 'Tem certeza que deseja continuar?';
        if (!window.confirm(message)) event.preventDefault();
      });
    });

    document.querySelectorAll('[data-copy-message]').forEach(function (button) {
      button.addEventListener('click', async function () {
        const field = document.getElementById(button.dataset.copyMessage);
        if (!field) return;
        try {
          await navigator.clipboard.writeText(field.value || field.textContent || '');
          const original = button.textContent;
          button.textContent = 'Copiado!';
          setTimeout(function () { button.textContent = original; }, 1500);
        } catch (e) {
          field.focus(); field.select();
          window.alert('Selecione e copie a mensagem manualmente.');
        }
      });
    });
  });
})();
