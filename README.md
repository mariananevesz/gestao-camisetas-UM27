# Gestão de Camisetas — UMADEPRESP 2027

Aplicação web em PHP/MySQL para pré-cadastro de líderes, pedidos de camisetas, conferência operacional, pagamentos parciais, relatórios e lotes consolidados para o fornecedor. O arquivo `css/style.css` original, as fontes Inter, o JavaScript original e o serviço de PDF foram preservados; as telas novas usam um arquivo CSS complementar separado.

## Requisitos

- PHP 7.4+ (recomendado PHP 8.1+), com extensões `mysqli`, `mbstring` e `openssl`.
- MySQL 5.7+ ou MariaDB 10.4+.
- Apache/Nginx; para acesso pela internet, HTTPS obrigatório.

## Instalação nova no XAMPP

1. Extraia a pasta `Gestao-Camisetas-UMADEPRESP-2027` em `C:\xampp\htdocs\`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. Abra `http://localhost/phpmyadmin`.
4. Importe `database/umadepresp.sql`. O script cria o banco `umadepresp_camisetas`, as tabelas, os dados das 98 congregações e os 17 tamanhos/modelos originais.
5. Confira `config/config.php`. O padrão é MySQL em `localhost:3306`, banco `umadepresp_camisetas`, usuário `root` e senha vazia.
6. Abra `http://localhost/Gestao-Camisetas-UMADEPRESP-2027/`.

### Primeiro acesso Master

- Login: `master@umadepresp.com.br`
- Senha inicial: `01011990` (data de nascimento 01/01/1990 no formato DDMMAAAA)

Entre e altere a senha em **Minha conta**. Não publique o sistema com a senha inicial. Se a conta Master já existir no banco, os scripts usam `INSERT IGNORE` e não sobrescrevem a senha existente.

## Atualizar um banco original existente

**Não importe o arquivo completo `database/umadepresp.sql` por cima de um banco existente esperando que ele altere as tabelas.** Antes de atualizar:

1. Faça backup pelo phpMyAdmin: selecione `umadepresp_camisetas` → **Exportar** → método **Personalizado** → SQL.
2. Confirme que o backup foi salvo e, idealmente, teste a restauração em outro banco.
3. Selecione o banco original `umadepresp_camisetas` e execute `database/migracao_completa.sql`.
4. Substitua os arquivos da aplicação pelos arquivos deste pacote, mantendo o backup fora da pasta pública.
5. Confira a configuração e teste o acesso localmente.

A migração é aditiva: preserva pedidos, itens, congregações e camisetas existentes. Pedidos antigos são marcados como `enviado` para impedir que sejam editados como se fossem rascunhos. Se a versão antiga marcava um pedido como `pago`, a migração cria um lançamento histórico integral com a data de atualização disponível (a versão antiga não guardava o histórico individual dos pagamentos). Execute a migração apenas sobre o banco original desta aplicação, não sobre o ZIP incompleto gerado por outra ferramenta com esquema diferente.

## Atualização da versão já importada/testada

Se você já importou `database/umadepresp.sql` e começou a testar esta versão, **não precisa reimportar o SQL completo**. Selecione o banco `umadepresp_camisetas` no phpMyAdmin e execute uma vez o arquivo `database/atualizacao_ajustes_2026.sql`. Ele adiciona o campo de telefone dos usuários e remove a forma de pagamento Transferência, sem apagar pedidos, usuários, congregações ou pagamentos já existentes. Faça essa atualização antes de abrir a nova versão do código, porque as telas de usuários passam a consultar o campo `telefone`.

## Regras principais

- Modelos e preços preservados: Baby Look PP/P/M/G/GG a R$ 45; Tradicional PP/P/M/G/GG a R$ 45; Infantil Nº 4/6/8/10/12 a R$ 45; Especial XG/XGG a R$ 50.
- Líderes fazem pré-cadastro com nome, e-mail, telefone/WhatsApp, data de nascimento e congregação. A tela informa que os dados de acesso serão enviados ao WhatsApp após aprovação; o login só funciona depois da aprovação.
- A senha inicial é a data de nascimento no formato DDMMAAAA, armazenada somente como hash. A recuperação valida e-mail e nascimento, permite escolher uma nova senha e limita tentativas.
- Perfis: Master, Gestão e Líder. A Master pode aprovar usuários, criar gestores e alterar permissões. Gestão tem acesso operacional/financeiro por padrão. Líder vê apenas pedidos associados a ele.
- A Gestão pode lançar pedidos em nome de um líder. O sistema guarda separadamente quem criou o pedido e quem é o responsável.
- Pedidos podem ser salvos como rascunho e editados/excluídos somente enquanto rascunho. Depois de enviados, os dados originais ficam bloqueados; correções devem ser lançadas em novos pedidos.
- O fluxo operacional foi simplificado para duas confirmações da Gestão: Enviado → Pedido confirmado → Liberado para produção → Incluído em lote. O status antigo `em_conferencia` continua aceito para registros existentes.
- As formas de pagamento disponíveis são PIX, Cartão, Dinheiro, Não informado e Outro. Pagamentos são lançamentos históricos individuais, com valor positivo, token de idempotência, bloqueio de duplicidade e validação para não ultrapassar o saldo. O status financeiro é calculado a partir do total recebido.
- O Balcão é registrado pela Gestão, exige comprador, telefone e setor escolhido da lista existente e não participa do ranking de congregações.
- Só pedidos liberados para produção e ainda sem lote podem ser incluídos em um lote. A chave única do banco impede que o mesmo pedido entre em dois lotes.
- O PDF do fornecedor contém apenas o código do lote e o consolidado por modelo/tamanho/quantidade; não contém comprador, telefone, líder ou valores financeiros.
- Ações administrativas, financeiras e operacionais validam permissões no servidor e usam CSRF. Senhas usam `password_hash`/`password_verify`; as consultas usam prepared statements.

## Antes de hospedar

- Configure um usuário MySQL dedicado com privilégios mínimos; não use `root` em produção.
- Defina as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` ou ajuste `config/config.php`.
- Ative HTTPS, desative a exibição de erros PHP ao público e mantenha os arquivos de backup fora de `htdocs`/`public_html`.
- Faça backups frequentes do banco e teste a restauração. O sistema não escolhe nem configura automaticamente um provedor de hospedagem.

## Verificação

O pacote deve ser validado no seu ambiente com PHP/MySQL antes de uso real. A verificação estática de sintaxe PHP não substitui os testes de integração no XAMPP; veja `TESTES_E_LIMITACOES.md` para a lista de verificações feitas e pendentes.
