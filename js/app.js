document.addEventListener('DOMContentLoaded', () => {
    const moeda = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    document.querySelectorAll('[data-fechar-alerta]').forEach((botao) => {
        botao.addEventListener('click', () => botao.closest('[data-alerta]')?.remove());
    });

    document.querySelectorAll('[data-alerta]').forEach((alerta) => {
        window.setTimeout(() => {
            if (alerta.isConnected) alerta.remove();
        }, 7000);
    });

    const telefone = document.querySelector('#telefone');
    if (telefone) {
        const aplicarMascara = () => {
            const numeros = telefone.value.replace(/\D/g, '').slice(0, 11);
            let valor = numeros;

            if (numeros.length > 2) {
                valor = `(${numeros.slice(0, 2)}) ${numeros.slice(2)}`;
            }
            if (numeros.length > 6 && numeros.length <= 10) {
                valor = `(${numeros.slice(0, 2)}) ${numeros.slice(2, 6)}-${numeros.slice(6)}`;
            }
            if (numeros.length > 10) {
                valor = `(${numeros.slice(0, 2)}) ${numeros.slice(2, 7)}-${numeros.slice(7)}`;
            }

            telefone.value = valor;
        };

        telefone.addEventListener('input', aplicarMascara);
        aplicarMascara();
    }

    document.querySelectorAll('[data-combobox]').forEach((combobox) => {
        const busca = combobox.querySelector('[role="combobox"]');
        const campoId = combobox.querySelector('input[type="hidden"]');
        const lista = combobox.querySelector('[role="listbox"]');
        const opcoes = [...lista.querySelectorAll('[role="option"]')];
        const vazio = lista.querySelector('[data-combobox-vazio]');

        const normalizar = (texto) => {
            const valor = String(texto || '');
            const semAcentos = typeof valor.normalize === 'function'
                ? valor.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : valor;
            return semAcentos.toLocaleLowerCase('pt-BR').trim();
        };

        const abrir = () => {
            lista.hidden = false;
            busca.setAttribute('aria-expanded', 'true');
        };

        const fechar = () => {
            lista.hidden = true;
            busca.setAttribute('aria-expanded', 'false');
        };

        const exibirTodas = () => {
            opcoes.forEach((opcao) => { opcao.hidden = false; });
            vazio.hidden = true;
            abrir();
        };

        const filtrar = () => {
            const termo = normalizar(busca.value);
            let visiveis = 0;

            opcoes.forEach((opcao) => {
                const corresponde = normalizar(opcao.dataset.optionLabel).includes(termo);
                opcao.hidden = !corresponde;
                if (corresponde) visiveis += 1;
            });

            vazio.hidden = visiveis !== 0;
            abrir();
        };

        const selecionar = (opcao) => {
            busca.value = opcao.dataset.optionLabel;
            campoId.value = opcao.dataset.optionId;
            opcoes.forEach((item) => item.setAttribute(
                'aria-selected',
                item.dataset.optionId === campoId.value ? 'true' : 'false'
            ));
            fechar();
            busca.dispatchEvent(new Event('change'));
        };

        opcoes.forEach((opcao) => {
            opcao.setAttribute(
                'aria-selected',
                opcao.dataset.optionId === campoId.value ? 'true' : 'false'
            );
            opcao.addEventListener('mousedown', (evento) => evento.preventDefault());
            opcao.addEventListener('click', () => selecionar(opcao));
        });

        busca.addEventListener('click', () => {
            exibirTodas();
            if (campoId.value && typeof busca.select === 'function') busca.select();
        });

        busca.addEventListener('focus', () => {
            if (lista.hidden) exibirTodas();
        });

        busca.addEventListener('input', () => {
            campoId.value = '';
            opcoes.forEach((opcao) => opcao.setAttribute('aria-selected', 'false'));
            filtrar();
        });

        busca.addEventListener('search', () => {
            campoId.value = '';
            filtrar();
        });

        busca.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape') {
                fechar();
                return;
            }

            if (evento.key === 'ArrowDown') {
                evento.preventDefault();
                abrir();
                const primeiraVisivel = opcoes.find((opcao) => !opcao.hidden);
                primeiraVisivel?.focus();
            }

            if (evento.key === 'Enter' && !lista.hidden) {
                const primeiraVisivel = opcoes.find((opcao) => !opcao.hidden);
                if (primeiraVisivel) {
                    evento.preventDefault();
                    selecionar(primeiraVisivel);
                }
            }
        });

        lista.addEventListener('keydown', (evento) => {
            const visiveis = opcoes.filter((opcao) => !opcao.hidden);
            const atual = visiveis.indexOf(document.activeElement);

            if (evento.key === 'ArrowDown' && atual < visiveis.length - 1) {
                evento.preventDefault();
                visiveis[atual + 1].focus();
            }
            if (evento.key === 'ArrowUp') {
                evento.preventDefault();
                if (atual > 0) visiveis[atual - 1].focus();
                else busca.focus();
            }
            if (evento.key === 'Escape') {
                fechar();
                busca.focus();
            }
        });

        document.addEventListener('click', (evento) => {
            if (!combobox.contains(evento.target)) fechar();
        });
    });

    document.querySelectorAll('[data-pedido-form]').forEach((formulario) => {
        const linhas = [...formulario.querySelectorAll('[data-camiseta-linha]')];
        const totalItens = formulario.querySelector('[data-total-itens]');
        const totalValor = formulario.querySelector('[data-total-valor]');

        const atualizarResumo = () => {
            let itens = 0;
            let valor = 0;

            linhas.forEach((linha) => {
                const quantidade = Number(linha.querySelector('input[type="number"]').value) || 0;
                const preco = Number(linha.dataset.preco) || 0;
                itens += quantidade;
                valor += quantidade * preco;
            });

            totalItens.textContent = itens;
            totalValor.textContent = moeda.format(valor);
        };

        linhas.forEach((linha) => {
            const input = linha.querySelector('input[type="number"]');
            const menos = linha.querySelector('[data-quantidade-menos]');
            const mais = linha.querySelector('[data-quantidade-mais]');

            menos.addEventListener('click', () => {
                input.value = Math.max(0, (Number(input.value) || 0) - 1);
                atualizarResumo();
            });

            mais.addEventListener('click', () => {
                input.value = Math.min(999, (Number(input.value) || 0) + 1);
                atualizarResumo();
            });
        });

        const mostrarErro = (campo, mensagem) => {
            const erro = formulario.querySelector(`[data-erro-campo="${campo}"]`);
            if (erro) erro.textContent = mensagem;

            if (campo === 'congregacao_id') {
                formulario.querySelector('#congregacao_busca')?.classList.add('invalido');
            } else {
                formulario.querySelector(`[name="${campo}"]`)?.classList.add('invalido');
            }
        };

        const limparErros = () => {
            formulario.querySelectorAll('[data-erro-campo]').forEach((erro) => { erro.textContent = ''; });
            formulario.querySelectorAll('.invalido').forEach((campo) => campo.classList.remove('invalido'));
        };

        formulario.addEventListener('submit', (evento) => {
            limparErros();
            const nome = formulario.querySelector('[name="nome_comprador"]');
            const congregacaoId = formulario.querySelector('[name="congregacao_id"]');
            const telefoneCampo = formulario.querySelector('[name="telefone"]');
            const quantidadeTotal = linhas.reduce(
                (total, linha) => total + (Number(linha.querySelector('input[type="number"]').value) || 0),
                0
            );
            let primeiroInvalido = null;

            if (nome.value.trim().length < 3) {
                mostrarErro('nome_comprador', 'Informe o nome completo do comprador.');
                primeiroInvalido ||= nome;
            }

            if (!congregacaoId.value) {
                mostrarErro('congregacao_id', 'Selecione uma opção da lista.');
                primeiroInvalido ||= formulario.querySelector('#congregacao_busca');
            }

            const numerosTelefone = telefoneCampo.value.replace(/\D/g, '');
            if (numerosTelefone.length < 10 || numerosTelefone.length > 11) {
                mostrarErro('telefone', 'Informe um telefone válido com DDD.');
                primeiroInvalido ||= telefoneCampo;
            }

            if (quantidadeTotal < 1) {
                mostrarErro('quantidades', 'Adicione pelo menos uma camiseta ao pedido.');
                primeiroInvalido ||= linhas[0]?.querySelector('button');
            }

            if (primeiroInvalido) {
                evento.preventDefault();
                primeiroInvalido.focus();
                primeiroInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const botao = formulario.querySelector('button[type="submit"]');
            if (botao) {
                botao.disabled = true;
                botao.style.opacity = '.7';
                botao.textContent = 'Salvando...';
            }
        });

        atualizarResumo();
    });

    const modal = document.querySelector('#modal-status');
    if (modal) {
        const campoId = modal.querySelector('#modal-pedido-id');
        const numero = modal.querySelector('#modal-pedido-numero');
        const entrega = modal.querySelector('#modal-entrega');
        const pagamento = modal.querySelector('#modal-pagamento');

        document.querySelectorAll('[data-abrir-status]').forEach((botao) => {
            botao.addEventListener('click', () => {
                campoId.value = botao.dataset.pedidoId;
                numero.textContent = botao.dataset.pedidoNumero;
                entrega.value = botao.dataset.entrega;
                pagamento.value = botao.dataset.pagamento;

                if (typeof modal.showModal === 'function') modal.showModal();
                else modal.setAttribute('open', '');
            });
        });

        modal.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
            botao.addEventListener('click', () => {
                if (typeof modal.close === 'function') modal.close();
                else modal.removeAttribute('open');
            });
        });

        modal.addEventListener('click', (evento) => {
            if (evento.target === modal && typeof modal.close === 'function') modal.close();
        });
    }

    document.querySelectorAll('[data-confirmar-exclusao]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            const confirmou = window.confirm('Tem certeza que deseja excluir este pedido? Essa ação não poderá ser desfeita.');
            if (!confirmou) evento.preventDefault();
        });
    });
});
