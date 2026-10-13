# Verificação desta versão

## Verificações estáticas realizadas

- Sintaxe PHP: todos os arquivos `.php` passaram por `php -l` no ambiente de preparação.
- Identidade visual: `css/style.css` é byte a byte idêntico ao arquivo do ZIP original (28.931 bytes). As mudanças de layout foram isoladas em `css/modules.css`.
- Dados originais: o SQL completo mantém as 98 congregações e as 17 combinações originais de modelo/tamanho.
- Formulários: pré-cadastro e criação de usuários de gestão agora exigem telefone/WhatsApp com DDD; os links de mensagem usam o número cadastrado.
- Financeiro: a interface e a validação do servidor aceitam somente PIX, Cartão, Dinheiro, Não informado e Outro. O layout usa dois cartões por linha em telas amplas e uma coluna em telas estreitas.
- Gestão de pedidos: a progressão foi reduzida a duas confirmações operacionais antes da liberação para produção; pedidos estão separados em Rascunhos, Em aberto e Finalizados. A entrega usa uma ação explícita, sem seletor ambíguo.
- Lotes: a consulta de elegibilidade usa `lote_pedidos.id_pedido`, que corresponde ao nome real da coluna no banco.
- Regras de escopo: não foi adicionado fluxo de upload de comprovantes PIX.
- Integridade SQL: não foi encontrado o padrão inválido de duas regras `ON DELETE` na mesma chave estrangeira. Foi incluído o script aditivo `database/atualizacao_ajustes_2026.sql` para o banco já criado com `database/umadepresp.sql`.
- Senha Master em instalação nova: o hash foi verificado com `password_verify` para a senha inicial `01011990`. Em bancos onde a conta Master já existe, os scripts não sobrescrevem a senha existente.

## Limitação importante

Não foi possível executar testes de integração reais com MySQL/MariaDB neste ambiente de preparação, pois não há servidor MySQL disponível e o PHP CLI daqui não tem a extensão `mysqli`. Portanto, a sintaxe e os ajustes estáticos foram verificados, mas **os fluxos completos ainda precisam ser testados no seu XAMPP antes de uso real ou hospedagem**. Não considere este pacote certificado para produção apenas com base na verificação estática.

## Roteiro recomendado no XAMPP

1. Para um banco novo, importe `database/umadepresp.sql` e entre com `master@umadepresp.com.br` / `01011990`. Para o banco já criado com este SQL, execute `database/atualizacao_ajustes_2026.sql`; não reimporte o SQL completo por cima.
2. Faça um pré-cadastro de líder com telefone, aprove-o na tela Usuários e confira se o botão Preparar WhatsApp abre a conversa para o número cadastrado.
3. Crie um usuário de gestão com telefone e confira se a mensagem de acesso também abre no WhatsApp correto.
4. Como líder, crie um rascunho, edite-o, envie-o e tente editar/excluir o pedido enviado. A edição/exclusão deve ser bloqueada.
5. No Financeiro, confira que aparecem dois cartões por linha em tela ampla e apenas as cinco formas de pagamento definidas. Cadastre um pedido de R$ 145,00, lance R$ 120,00 e confira saldo de R$ 25,00/status Parcial; lance os R$ 25,00 restantes e confira Pago. Tente ultrapassar o saldo e reenviar a mesma submissão.
6. Na Gestão de pedidos, clique em Confirmar pedido e depois em Confirmado para produção — OK. O pedido liberado deve aparecer em Lotes fornecedor; gere o lote e confira que não reaparece como elegível. Imprima o PDF duas vezes e confirme que ele não tem nome, telefone, líder ou valores.
7. Registre um pedido de Balcão, selecione um setor válido e confirme que o relatório de vendas do Balcão não aparece no ranking de congregações.
8. Crie dois líderes na mesma congregação e confirme que cada um vê somente os próprios pedidos. Tente abrir manualmente o PDF de um pedido pertencente ao outro líder.
9. Confirme que a Gestão consegue registrar pagamentos e que um líder recebe acesso negado se tentar abrir a tela/ação financeira diretamente.
