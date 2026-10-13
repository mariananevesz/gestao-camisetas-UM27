
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    const congregacaoBusca = document.getElementById('congregacao_busca');
    const congregacaoId = document.getElementById('congregacao_id');
    const balconyFields = document.querySelector('[data-balcao-fields]');
    const responsible = document.getElementById('responsavel_id');
    const sector = document.getElementById('setor_balcao');

    function toggleBalcony() {
      if (!congregacaoId) return;

      const isBalcony = congregacaoId.value === '1';

      if (balconyFields) {
        balconyFields.hidden = !isBalcony;
      }

      if (sector) {
        sector.required = isBalcony;
      }
    }

    if (congregacaoBusca && congregacaoId) {
      congregacaoBusca.addEventListener('input', function () {
        const opts = document.querySelectorAll('[data-option-id]');
        const val = congregacaoBusca.value.trim().toLocaleLowerCase('pt-BR');
        let match = null;

        opts.forEach(function (opt) {
          if (
            opt.dataset.optionLabel.toLocaleLowerCase('pt-BR') === val
          ) {
            match = opt;
          }
        });

        if (match) {
          congregacaoId.value = match.dataset.optionId;
        }

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

          const button = Array.from(
            document.querySelectorAll('[data-option-id]')
          ).find(function (el) {
            return el.dataset.optionId === congregation;
          });

          if (button) {
            congregacaoBusca.value = button.dataset.optionLabel;
          }

          toggleBalcony();
        }
      });
    }

    toggleBalcony();

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        const message =
          form.dataset.confirm || 'Tem certeza que deseja continuar?';

        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });
    });

    document.querySelectorAll('[data-copy-message]').forEach(function (button) {
      button.addEventListener('click', async function () {
        const field = document.getElementById(button.dataset.copyMessage);

        if (!field) return;

        try {
          await navigator.clipboard.writeText(
            field.value || field.textContent || ''
          );

          const original = button.textContent;
          button.textContent = 'Copiado!';

          setTimeout(function () {
            button.textContent = original;
          }, 1500);
        } catch (e) {
          field.focus();
          field.select();

          window.alert('Selecione e copie a mensagem manualmente.');
        }
      });
    });
  });
})();

/*
 * Gestão de pedidos:
 * - Busca geral em todas as seções.
 * - Pesquisa sem diferenciar acentos ou maiúsculas.
 * - Contagem de resultados por seção.
 * - Abertura automática das seções com resultados.
 * - Restauração da organização padrão ao limpar a busca.
 */
(function () {
  'use strict';

  const input = document.getElementById('busca-pedidos');

  if (!input) return;

  const sections = Array.from(
    document.querySelectorAll('[data-order-section]')
  );

  const feedback = document.getElementById('pedido-busca-feedback');
  const noResults = document.getElementById('pedido-busca-sem-resultados');

  function normalizarTexto(valor) {
    return String(valor || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLocaleLowerCase('pt-BR');
  }

  function filtrarPedidos() {
    const termo = normalizarTexto(input.value.trim());
    let totalEncontrado = 0;

    sections.forEach(function (section) {
      const cards = Array.from(
        section.querySelectorAll('[data-order-card]')
      );

      let encontradosNaSecao = 0;

      cards.forEach(function (card) {
        const texto = normalizarTexto(
          card.dataset.search || card.textContent
        );

        const corresponde = termo === '' || texto.includes(termo);

        card.hidden = !corresponde;

        if (corresponde) {
          encontradosNaSecao += 1;
        }
      });

      const contador = section.querySelector('[data-group-count]');

      if (contador) {
        contador.textContent =
          termo === ''
            ? section.dataset.totalCount
            : String(encontradosNaSecao);
      }

      const mensagemVazia = section.querySelector('[data-group-empty]');

      if (mensagemVazia) {
        mensagemVazia.hidden = termo !== '' || cards.length > 0;
      }

      // Durante a busca, esconde as seções sem resultados.
      section.hidden =
        termo !== '' && encontradosNaSecao === 0;

      if (termo !== '') {
        // Abre automaticamente as seções que possuem resultados.
        section.open = encontradosNaSecao > 0;
      } else {
        // Restaura o estado padrão de cada seção.
        section.open = section.dataset.defaultOpen === 'true';
      }

      totalEncontrado += encontradosNaSecao;
    });

    if (termo === '') {
      if (feedback) {
        feedback.textContent =
          'Pesquise por número, comprador, líder ou congregação.';
      }

      if (noResults) {
        noResults.hidden = true;
      }

      return;
    }

    if (feedback) {
      feedback.textContent =
        totalEncontrado === 1
          ? '1 pedido encontrado.'
          : totalEncontrado + ' pedidos encontrados.';
    }

    if (noResults) {
      noResults.hidden = totalEncontrado > 0;
    }
  }

  input.addEventListener('input', filtrarPedidos);
})();
