<?php
/**
 * Camada de aplicação para autenticação, permissões, pedidos, financeiro e lotes.
 * Todas as alterações críticas são validadas novamente no servidor.
 */
class Sistema
{
    private $db;

    public function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->db = new mysqli(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['name'],
                $config['port']
            );
            $this->db->set_charset('utf8mb4');
        } catch (Throwable $e) {
            throw new RuntimeException('Não foi possível conectar ao banco. Confira config/config.php e importe database/umadepresp.sql.');
        }
    }

    private function stmt($sql, $types = '', array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '' && $params) {
            $refs = [];
            foreach ($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            array_unshift($refs, $types);
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        $stmt->execute();
        return $stmt;
    }

    private function one($sql, $types = '', array $params = [])
    {
        $row = $this->stmt($sql, $types, $params)->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private function all($sql, $types = '', array $params = [])
    {
        return $this->stmt($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function audit($userId, $acao, $descricao)
    {
        try {
            $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            $this->stmt(
                'INSERT INTO logs_auditoria (id_usuario, acao, descricao, ip_address) VALUES (?, ?, ?, ?)',
                'isss',
                [$userId ? (int) $userId : null, $acao, $descricao, $ip]
            );
        } catch (Throwable $e) {
            // Auditoria não deve esconder o erro principal da operação.
        }
    }

    public function acessoPermitido($chave, $maxTentativas = 8, $janelaMinutos = 15)
    {
        $row = $this->one('SELECT tentativas, inicio, bloqueado_ate FROM limites_acesso WHERE chave = ? LIMIT 1', 's', [$chave]);
        if (!$row) return true;
        $agora = time();
        if (!empty($row['bloqueado_ate']) && strtotime($row['bloqueado_ate']) > $agora) return false;
        if (strtotime($row['inicio']) < $agora - ($janelaMinutos * 60)) {
            $this->stmt('UPDATE limites_acesso SET tentativas = 0, inicio = NOW(), bloqueado_ate = NULL WHERE chave = ?', 's', [$chave]);
            return true;
        }
        return (int) $row['tentativas'] < $maxTentativas;
    }

    public function registrarFalhaAcesso($chave, $maxTentativas = 8, $janelaMinutos = 15)
    {
        $row = $this->one('SELECT tentativas, inicio FROM limites_acesso WHERE chave = ? LIMIT 1', 's', [$chave]);
        $agora = time();
        if (!$row) {
            $this->stmt('INSERT INTO limites_acesso (chave, tentativas, inicio) VALUES (?, 1, NOW())', 's', [$chave]);
            return;
        }
        if (strtotime($row['inicio']) < $agora - ($janelaMinutos * 60)) {
            $this->stmt('UPDATE limites_acesso SET tentativas = 1, inicio = NOW(), bloqueado_ate = NULL WHERE chave = ?', 's', [$chave]);
            return;
        }
        $tentativas = (int) $row['tentativas'] + 1;
        if ($tentativas >= $maxTentativas) {
            $bloqueio = date('Y-m-d H:i:s', time() + ($janelaMinutos * 60));
            $this->stmt('UPDATE limites_acesso SET tentativas = ?, bloqueado_ate = ? WHERE chave = ?', 'iss', [$tentativas, $bloqueio, $chave]);
        } else {
            $this->stmt('UPDATE limites_acesso SET tentativas = ? WHERE chave = ?', 'is', [$tentativas, $chave]);
        }
    }

    public function limparLimiteAcesso($chave)
    {
        $this->stmt('DELETE FROM limites_acesso WHERE chave = ?', 's', [$chave]);
    }

    public function obterUsuarioPorEmail($email)
    {
        return $this->one('SELECT * FROM usuarios WHERE email = ? LIMIT 1', 's', [$this->lowercase(trim($email))]);
    }

    public function obterUsuario($id)
    {
        return $this->one(
            'SELECT u.*, c.nome AS congregacao_nome FROM usuarios u LEFT JOIN congregacoes c ON c.id = u.congregacao_id WHERE u.id = ? LIMIT 1',
            'i', [(int) $id]
        );
    }

    public function autenticar($email, $senha)
    {
        $email = $this->lowercase(trim((string) $email));
        $usuario = $this->obterUsuarioPorEmail($email);
        if (!$usuario || !password_verify((string) $senha, $usuario['senha_hash']) || $usuario['status'] !== 'ativo') {
            return null;
        }
        $this->audit((int) $usuario['id'], 'login', 'Login realizado.');
        return $usuario;
    }

    public function cadastrarPreCadastro(array $dados)
    {
        $nome = trim(preg_replace('/\s+/u', ' ', (string) ($dados['nome_completo'] ?? '')));
        $email = $this->lowercase(trim((string) ($dados['email'] ?? '')));
        $nascimento = trim((string) ($dados['data_nascimento'] ?? ''));
        $telefone = $this->normalizarTelefoneContato((string) ($dados['telefone'] ?? ''));
        $congregacaoId = (int) ($dados['congregacao_id'] ?? 0);
        if ($this->comprimento($nome) < 3 || $this->comprimento($nome) > 180) {
            throw new DomainException('Informe o nome completo (de 3 a 180 caracteres).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $this->comprimento($email) > 190) {
            throw new DomainException('Informe um e-mail válido.');
        }
        $data = DateTime::createFromFormat('!Y-m-d', $nascimento);
        if (!$data || $data->format('Y-m-d') !== $nascimento || $data > new DateTime('today')) {
            throw new DomainException('Informe uma data de nascimento válida.');
        }
        $congregacao = $this->one('SELECT id, tipo FROM congregacoes WHERE id = ? LIMIT 1', 'i', [$congregacaoId]);
        if (!$congregacao || $congregacao['tipo'] !== 'congregacao') {
            throw new DomainException('Selecione uma congregação válida.');
        }
        if ($this->obterUsuarioPorEmail($email)) {
            throw new DomainException('Este e-mail já está cadastrado. Se você já se cadastrou, aguarde a aprovação da Master.');
        }
        $senhaInicial = $data->format('dmY');
        $hash = password_hash($senhaInicial, PASSWORD_DEFAULT);
        $this->stmt(
            "INSERT INTO usuarios (nome_completo, email, telefone, data_nascimento, senha_hash, congregacao_id, perfil, status) VALUES (?, ?, ?, ?, ?, ?, 'lider', 'aguardando_aprovacao')",
            'sssssi', [$nome, $email, $telefone, $nascimento, $hash, $congregacaoId]
        );
        $id = (int) $this->db->insert_id;
        $this->audit($id, 'precadastro', 'Pré-cadastro de líder aguardando aprovação.');
        return $id;
    }

    public function criarGestor(array $dados, $atorId)
    {
        $nome = trim(preg_replace('/\s+/u', ' ', (string) ($dados['nome_completo'] ?? '')));
        $email = $this->lowercase(trim((string) ($dados['email'] ?? '')));
        $nascimento = trim((string) ($dados['data_nascimento'] ?? ''));
        $telefone = $this->normalizarTelefoneContato((string) ($dados['telefone'] ?? ''));
        if ($this->comprimento($nome) < 3 || $this->comprimento($nome) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Confira o nome completo e o e-mail.');
        }
        $data = DateTime::createFromFormat('!Y-m-d', $nascimento);
        if (!$data || $data->format('Y-m-d') !== $nascimento || $data > new DateTime('today')) {
            throw new DomainException('Informe uma data de nascimento válida.');
        }
        if ($this->obterUsuarioPorEmail($email)) {
            throw new DomainException('Já existe um usuário com este e-mail.');
        }
        $hash = password_hash($data->format('dmY'), PASSWORD_DEFAULT);
        $this->stmt(
            "INSERT INTO usuarios (nome_completo, email, telefone, data_nascimento, senha_hash, congregacao_id, perfil, status) VALUES (?, ?, ?, ?, ?, NULL, 'gestao', 'ativo')",
            'sssss', [$nome, $email, $telefone, $nascimento, $hash]
        );
        $id = (int) $this->db->insert_id;
        $this->audit($atorId, 'criar_gestor', 'Criou usuário de gestão #' . $id . '.');
        return ['id' => $id, 'senha_inicial' => $data->format('dmY')];
    }

    public function listarUsuarios()
    {
        return $this->all(
            'SELECT u.id, u.nome_completo, u.email, u.telefone, u.data_nascimento, u.perfil, u.status, u.senha_inicial_ativa, u.criado_em, c.nome AS congregacao_nome
             FROM usuarios u LEFT JOIN congregacoes c ON c.id = u.congregacao_id
             ORDER BY FIELD(u.status, "aguardando_aprovacao", "ativo", "inativo", "bloqueado"), u.nome_completo'
        );
    }

    public function alterarStatusUsuario($id, $status, $atorId)
    {
        $id = (int) $id;
        if (!in_array($status, ['ativo', 'inativo', 'bloqueado'], true)) {
            throw new DomainException('Situação de usuário inválida.');
        }
        $usuario = $this->obterUsuario($id);
        if (!$usuario) {
            throw new DomainException('Usuário não encontrado.');
        }
        if ($usuario['perfil'] === 'master' && $status !== 'ativo') {
            throw new DomainException('A conta Master não pode ser desativada por este formulário.');
        }
        $this->stmt('UPDATE usuarios SET status = ? WHERE id = ?', 'si', [$status, $id]);
        $this->audit($atorId, 'status_usuario', 'Alterou usuário #' . $id . ' para ' . $status . '.');
    }

    public function obterPermissao($usuarioId, $permissao)
    {
        $row = $this->one('SELECT permitido FROM usuario_permissoes WHERE usuario_id = ? AND permissao = ? LIMIT 1', 'is', [(int) $usuarioId, $permissao]);
        return $row ? (bool) $row['permitido'] : null;
    }

    public function listarPermissoesUsuario($usuarioId)
    {
        $rows = $this->all('SELECT permissao, permitido FROM usuario_permissoes WHERE usuario_id = ?', 'i', [(int) $usuarioId]);
        $out = [];
        foreach ($rows as $row) $out[$row['permissao']] = (bool) $row['permitido'];
        return $out;
    }

    public function definirPermissao($usuarioId, $permissao, $permitido, $atorId)
    {
        $usuario = $this->obterUsuario($usuarioId);
        $permitidas = ['criar_pedido', 'pedidos_ver_todos', 'financeiro', 'lotes', 'relatorios', 'usuarios_gerenciar', 'aprovar_cadastros', 'criar_gestores', 'criar_pedido_em_nome_de_outro', 'alterar_status_operacional', 'registrar_pagamentos', 'gerar_lotes'];
        if (!$usuario || $usuario['perfil'] !== 'gestao' || !in_array($permissao, $permitidas, true)) {
            throw new DomainException('Permissão ou usuário inválido.');
        }
        $valor = $permitido ? 1 : 0;
        $this->stmt(
            'INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)',
            'isi', [(int) $usuarioId, $permissao, $valor]
        );
        $this->audit($atorId, 'permissao_usuario', 'Permissão ' . $permissao . ' do usuário #' . $usuarioId . ' definida como ' . $valor . '.');
    }

    public function listarCongregacoes($incluirBalcao = true)
    {
        $sql = 'SELECT id, nome, setor, tipo, participa_ranking, ordem_exibicao FROM congregacoes';
        if (!$incluirBalcao) $sql .= " WHERE tipo = 'congregacao'";
        $sql .= ' ORDER BY ordem_exibicao ASC, id ASC';
        return $this->all($sql);
    }

    public function listarSetores()
    {
        return $this->all("SELECT DISTINCT setor FROM congregacoes WHERE setor IS NOT NULL AND TRIM(setor) <> '' ORDER BY CAST(setor AS UNSIGNED), setor");
    }

    public function listarCamisetas()
    {
        return $this->all('SELECT id, modelo, tamanho, valor, ordem_exibicao FROM camisetas WHERE ativo = 1 ORDER BY ordem_exibicao, id');
    }

    public function listarLideresAtivos()
    {
        return $this->all("SELECT u.id, u.nome_completo, u.email, u.congregacao_id, c.nome AS congregacao_nome FROM usuarios u INNER JOIN congregacoes c ON c.id = u.congregacao_id WHERE u.perfil = 'lider' AND u.status = 'ativo' ORDER BY u.nome_completo");
    }

    private function normalizarPedido(array $dados, array $usuario, $responsavelExistentePermitido = 0)
    {
        $nome = trim(preg_replace('/\s+/u', ' ', (string) ($dados['nome_comprador'] ?? '')));
        $telefone = trim((string) ($dados['telefone'] ?? ''));
        $telefoneNumeros = preg_replace('/\D+/', '', $telefone);
        $congregacaoId = (int) ($dados['congregacao_id'] ?? 0);
        $responsavelId = (int) ($dados['responsavel_id'] ?? 0);
        $setorBalcao = trim((string) ($dados['setor_balcao'] ?? ''));
        $ehGestao = in_array($usuario['perfil'], ['master', 'gestao'], true);

        if ($this->comprimento($nome) < 3 || $this->comprimento($nome) > 150) throw new DomainException('Informe o nome completo do comprador.');
        if (strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 11) throw new DomainException('Informe um telefone válido com DDD.');

        if (!$ehGestao) {
            $congregacaoId = (int) $usuario['congregacao_id'];
            $responsavelId = (int) $usuario['id'];
        } elseif ($responsavelId > 0) {
            if (function_exists('app_has_permission') && !app_has_permission('criar_pedido_em_nome_de_outro') && $responsavelId !== (int) $responsavelExistentePermitido) throw new DomainException('Seu perfil não pode criar pedidos em nome de outro líder.');
            $responsavel = $this->one("SELECT id, perfil, status, congregacao_id FROM usuarios WHERE id = ? LIMIT 1", 'i', [$responsavelId]);
            if (!$responsavel || $responsavel['perfil'] !== 'lider' || $responsavel['status'] !== 'ativo') throw new DomainException('Selecione um líder ativo válido.');
            $congregacaoId = (int) $responsavel['congregacao_id'];
        } else {
            $responsavelId = (int) $usuario['id'];
        }

        $congregacao = $this->one('SELECT id, tipo FROM congregacoes WHERE id = ? LIMIT 1', 'i', [$congregacaoId]);
        if (!$congregacao) throw new DomainException('Selecione uma congregação válida.');
        if ($congregacao['tipo'] === 'balcao') {
            if (!$ehGestao || $responsavelId !== (int) $usuario['id']) throw new DomainException('Pedidos de Balcão devem ser registrados pela gestão e não podem pertencer a um líder.');
            $setores = array_column($this->listarSetores(), 'setor');
            if (!in_array($setorBalcao, $setores, true)) throw new DomainException('Selecione um setor válido para o pedido de Balcão.');
        } else {
            $setorBalcao = '';
        }

        $quantidades = $dados['quantidades'] ?? [];
        if (!is_array($quantidades)) throw new DomainException('As quantidades informadas são inválidas.');
        $catalogo = $this->listarCamisetas();
        $itens = [];
        $total = 0.0;
        foreach ($catalogo as $camiseta) {
            $id = (int) $camiseta['id'];
            $q = filter_var($quantidades[$id] ?? 0, FILTER_VALIDATE_INT);
            if ($q === false || $q < 0 || $q > 999) throw new DomainException('Cada quantidade deve estar entre 0 e 999.');
            if ($q > 0) {
                $subtotal = round((float) $camiseta['valor'] * $q, 2);
                $itens[] = ['camiseta_id' => $id, 'modelo' => $camiseta['modelo'], 'tamanho' => $camiseta['tamanho'], 'valor' => (float) $camiseta['valor'], 'quantidade' => $q, 'subtotal' => $subtotal];
                $total += $subtotal;
            }
        }
        if (!$itens) throw new DomainException('Adicione pelo menos uma camiseta ao pedido.');
        return ['nome' => $nome, 'telefone' => $telefone, 'congregacao_id' => $congregacaoId, 'responsavel_id' => $responsavelId, 'setor_balcao' => $setorBalcao ?: null, 'itens' => $itens, 'total' => round($total, 2)];
    }

    public function criarPedido(array $dados, array $usuario, $intencao = 'rascunho')
    {
        $pedido = $this->normalizarPedido($dados, $usuario);
        $status = $intencao === 'enviar' ? 'enviado' : 'rascunho';
        $this->db->begin_transaction();
        try {
            $this->stmt(
                "INSERT INTO pedidos (numero_pedido, id_usuario_criou, id_usuario_responsavel, nome_comprador, congregacao_id, telefone, setor_balcao, valor_total, status_operacional, status_entrega, status_pagamento) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'aguardando', 'pendente')",
                'iisissds', [(int) $usuario['id'], $pedido['responsavel_id'], $pedido['nome'], $pedido['congregacao_id'], $pedido['telefone'], $pedido['setor_balcao'], $pedido['total'], $status]
            );
            $id = (int) $this->db->insert_id;
            $numero = 'Pedido ' . str_pad((string) $id, 7, '0', STR_PAD_LEFT);
            $this->stmt('UPDATE pedidos SET numero_pedido = ? WHERE id = ?', 'si', [$numero, $id]);
            $this->inserirItensPedido($id, $pedido['itens']);
            $this->audit($usuario['id'], 'criar_pedido', 'Criou ' . $numero . ' (' . $status . ').');
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function atualizarRascunho($id, array $dados, array $usuario, $intencao = 'rascunho')
    {
        $id = (int) $id;
        $novoStatus = $intencao === 'enviar' ? 'enviado' : 'rascunho';
        $this->db->begin_transaction();
        try {
            $atual = $this->one('SELECT id, status_operacional, id_usuario_criou, id_usuario_responsavel, numero_pedido FROM pedidos WHERE id = ? FOR UPDATE', 'i', [$id]);
            if (!$atual) throw new DomainException('Pedido não encontrado.');
            if ($atual['status_operacional'] !== 'rascunho') throw new DomainException('Pedidos enviados não podem ser editados. Para corrigir quantidades, crie um novo pedido.');
            $this->garantirAcessoAoRascunho($atual, $usuario);
            if ($usuario['perfil'] === 'gestao' && function_exists('app_has_permission') && !app_has_permission('criar_pedido_em_nome_de_outro')) {
                $dados['responsavel_id'] = (int) $atual['id_usuario_responsavel'];
            }
            $pedido = $this->normalizarPedido($dados, $usuario, (int) $atual['id_usuario_responsavel']);
            $this->stmt(
                'UPDATE pedidos SET id_usuario_responsavel = ?, nome_comprador = ?, congregacao_id = ?, telefone = ?, setor_balcao = ?, valor_total = ?, status_operacional = ? WHERE id = ?',
                'isissdsi', [$pedido['responsavel_id'], $pedido['nome'], $pedido['congregacao_id'], $pedido['telefone'], $pedido['setor_balcao'], $pedido['total'], $novoStatus, $id]
            );
            $this->stmt('DELETE FROM pedido_itens WHERE pedido_id = ?', 'i', [$id]);
            $this->inserirItensPedido($id, $pedido['itens']);
            $this->audit($usuario['id'], 'editar_rascunho', 'Atualizou ' . $atual['numero_pedido'] . ' (' . $novoStatus . ').');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function inserirItensPedido($pedidoId, array $itens)
    {
        $stmt = $this->db->prepare('INSERT INTO pedido_itens (pedido_id, camiseta_id, modelo, tamanho, valor_unitario, quantidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($itens as $item) {
            $pid = (int) $pedidoId; $cid = (int) $item['camiseta_id']; $modelo = $item['modelo']; $tamanho = $item['tamanho']; $valor = (float) $item['valor']; $q = (int) $item['quantidade']; $subtotal = (float) $item['subtotal'];
            $stmt->bind_param('iissdid', $pid, $cid, $modelo, $tamanho, $valor, $q, $subtotal);
            $stmt->execute();
        }
    }

    private function garantirAcessoAoRascunho(array $pedido, array $usuario)
    {
        if ($usuario['perfil'] === 'master') return;
        if ($usuario['perfil'] === 'lider') {
            if ((int) $pedido['id_usuario_responsavel'] !== (int) $usuario['id']) throw new DomainException('Você só pode alterar seus próprios rascunhos.');
            return;
        }
        if ($usuario['perfil'] === 'gestao') {
            if (function_exists('app_has_permission') && app_has_permission('pedidos_ver_todos')) return;
            if ((int) ($pedido['id_usuario_criou'] ?? 0) === (int) $usuario['id'] || (int) ($pedido['id_usuario_responsavel'] ?? 0) === (int) $usuario['id']) return;
        }
        throw new DomainException('Você não tem permissão para alterar este rascunho.');
    }

    public function excluirRascunho($id, array $usuario)
    {
        $this->db->begin_transaction();
        try {
            $pedido = $this->one('SELECT id, numero_pedido, status_operacional, id_usuario_criou, id_usuario_responsavel FROM pedidos WHERE id = ? FOR UPDATE', 'i', [(int) $id]);
            if (!$pedido) throw new DomainException('Pedido não encontrado.');
            if ($pedido['status_operacional'] !== 'rascunho') throw new DomainException('Somente rascunhos podem ser excluídos. Pedidos enviados não podem ser apagados.');
            $this->garantirAcessoAoRascunho($pedido, $usuario);
            $this->stmt('DELETE FROM pedidos WHERE id = ?', 'i', [(int) $id]);
            $this->audit($usuario['id'], 'excluir_rascunho', 'Excluiu ' . $pedido['numero_pedido'] . '.');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function obterPedido($id)
    {
        $pedido = $this->one(
            "SELECT p.*, c.nome AS congregacao_nome, c.tipo AS congregacao_tipo,
                    resp.nome_completo AS responsavel_nome, criador.nome_completo AS criador_nome,
                    COALESCE(pg.total_recebido, 0) AS total_recebido,
                    CASE WHEN COALESCE(pg.total_recebido, 0) <= 0 THEN 'pendente'
                         WHEN COALESCE(pg.total_recebido, 0) + 0.004 < p.valor_total THEN 'parcial'
                         ELSE 'pago' END AS status_pagamento_calculado
             FROM pedidos p INNER JOIN congregacoes c ON c.id = p.congregacao_id
             LEFT JOIN usuarios resp ON resp.id = p.id_usuario_responsavel
             LEFT JOIN usuarios criador ON criador.id = p.id_usuario_criou
             LEFT JOIN (SELECT pedido_id, SUM(valor) AS total_recebido FROM pagamentos GROUP BY pedido_id) pg ON pg.pedido_id = p.id
             WHERE p.id = ? LIMIT 1", 'i', [(int) $id]
        );
        if (!$pedido) return null;
        $pedido['itens'] = $this->all('SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id', 'i', [(int) $id]);
        $pedido['status_pagamento'] = $pedido['status_pagamento_calculado'];
        $pedido['saldo_devedor'] = max(0, round((float) $pedido['valor_total'] - (float) $pedido['total_recebido'], 2));
        return $pedido;
    }

    public function podeVerPedido(array $pedido, array $usuario)
    {
        if ($usuario['perfil'] === 'master') return true;
        if ($usuario['perfil'] === 'gestao') {
            if (function_exists('app_has_permission') && app_has_permission('pedidos_ver_todos')) return true;
            return (int) ($pedido['id_usuario_criou'] ?? 0) === (int) $usuario['id'] || (int) ($pedido['id_usuario_responsavel'] ?? 0) === (int) $usuario['id'];
        }
        return $usuario['perfil'] === 'lider' && (int) $pedido['id_usuario_responsavel'] === (int) $usuario['id'];
    }

    public function listarPedidos(array $usuario, $somenteFinanceiro = false)
    {
        $where = '';
        $params = [];
        $types = '';
        if ($usuario['perfil'] === 'lider') {
            $where = ' WHERE p.id_usuario_responsavel = ?'; $types = 'i'; $params[] = (int) $usuario['id'];
        }
        if ($somenteFinanceiro) {
            $where .= ($where ? ' AND ' : ' WHERE ') . "p.status_operacional <> 'rascunho'";
        }
        $orders = $this->all(
            "SELECT p.*, c.nome AS congregacao_nome, c.tipo AS congregacao_tipo,
                    resp.nome_completo AS responsavel_nome, criador.nome_completo AS criador_nome,
                    COALESCE(pg.total_recebido, 0) AS total_recebido,
                    CASE WHEN COALESCE(pg.total_recebido, 0) <= 0 THEN 'pendente'
                         WHEN COALESCE(pg.total_recebido, 0) + 0.004 < p.valor_total THEN 'parcial'
                         ELSE 'pago' END AS status_pagamento_calculado
             FROM pedidos p INNER JOIN congregacoes c ON c.id = p.congregacao_id
             LEFT JOIN usuarios resp ON resp.id = p.id_usuario_responsavel
             LEFT JOIN usuarios criador ON criador.id = p.id_usuario_criou
             LEFT JOIN (SELECT pedido_id, SUM(valor) AS total_recebido FROM pagamentos GROUP BY pedido_id) pg ON pg.pedido_id = p.id
             $where ORDER BY p.criado_em DESC, p.id DESC",
            $types, $params
        );
        foreach ($orders as &$order) {
            $order['status_pagamento'] = $order['status_pagamento_calculado'];
            $order['saldo_devedor'] = max(0, round((float) $order['valor_total'] - (float) $order['total_recebido'], 2));
            $order['itens'] = $this->all('SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id', 'i', [(int) $order['id']]);
        }
        unset($order);
        return $orders;
    }

    public function mudarStatusOperacional($id, $novoStatus, array $usuario)
    {
        // O fluxo foi simplificado para duas confirmações: confirmar pedido e liberar produção.
        // O status antigo 'em_conferencia' continua aceito para registros já existentes.
        $proximos = ['enviado' => 'conferido', 'em_conferencia' => 'conferido', 'conferido' => 'liberado_producao'];
        $this->db->begin_transaction();
        try {
            $pedido = $this->one('SELECT id, numero_pedido, status_operacional FROM pedidos WHERE id = ? FOR UPDATE', 'i', [(int) $id]);
            if (!$pedido) throw new DomainException('Pedido não encontrado.');
            if (!isset($proximos[$pedido['status_operacional']]) || $proximos[$pedido['status_operacional']] !== $novoStatus) throw new DomainException('Transição de status inválida. Avance o pedido pela próxima etapa permitida.');
            $this->stmt('UPDATE pedidos SET status_operacional = ? WHERE id = ?', 'si', [$novoStatus, (int) $id]);
            $this->audit($usuario['id'], 'status_operacional', $pedido['numero_pedido'] . ': ' . $pedido['status_operacional'] . ' -> ' . $novoStatus . '.');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback(); throw $e;
        }
    }

    public function mudarStatusEntrega($id, $status, array $usuario)
    {
        if (!in_array($status, ['aguardando', 'entregue'], true)) throw new DomainException('Status de entrega inválido.');
        $pedido = $this->obterPedido($id);
        if (!$pedido || $pedido['status_operacional'] === 'rascunho') throw new DomainException('Pedido não encontrado ou ainda em rascunho.');
        $this->stmt('UPDATE pedidos SET status_entrega = ? WHERE id = ?', 'si', [$status, (int) $id]);
        $this->audit($usuario['id'], 'status_entrega', $pedido['numero_pedido'] . ' marcado como ' . $status . '.');
    }

    public function registrarPagamento($pedidoId, $valorDigitado, $metodo, $observacao, $token, array $usuario)
    {
        $valorTexto = trim((string) $valorDigitado);
        if (strpos($valorTexto, ',') !== false) $valorTexto = str_replace(',', '.', str_replace('.', '', $valorTexto));
        if (!is_numeric($valorTexto)) throw new DomainException('Informe um valor de pagamento válido.');
        $valor = round((float) $valorTexto, 2);
        if ($valor <= 0) throw new DomainException('O pagamento deve ser maior que zero.');
        if (!in_array($metodo, ['', 'pix', 'dinheiro', 'cartao', 'outro'], true)) throw new DomainException('Selecione uma forma de pagamento válida.');
        $token = preg_replace('/[^a-f0-9]/i', '', (string) $token);
        if (strlen($token) < 32 || strlen($token) > 128) throw new DomainException('A sessão do formulário de pagamento expirou. Atualize a página.');
        $observacao = trim((string) $observacao);
        if ($this->comprimento($observacao) > 255) throw new DomainException('A observação deve ter no máximo 255 caracteres.');

        $this->db->begin_transaction();
        try {
            $duplicado = $this->one('SELECT id FROM pagamentos WHERE token_operacao = ? LIMIT 1', 's', [$token]);
            if ($duplicado) throw new DomainException('Este pagamento já foi registrado; não foi lançado novamente.');
            $pedido = $this->one('SELECT id, numero_pedido, valor_total, status_operacional FROM pedidos WHERE id = ? FOR UPDATE', 'i', [(int) $pedidoId]);
            if (!$pedido || $pedido['status_operacional'] === 'rascunho') throw new DomainException('Somente pedidos enviados podem receber pagamentos.');
            $soma = $this->one('SELECT COALESCE(SUM(valor), 0) AS total FROM pagamentos WHERE pedido_id = ?', 'i', [(int) $pedidoId]);
            $recebido = (float) $soma['total'];
            $saldo = round((float) $pedido['valor_total'] - $recebido, 2);
            if ($valor > $saldo + 0.004) throw new DomainException('O pagamento ultrapassa o saldo restante de ' . number_format(max(0, $saldo), 2, ',', '.') . '.');
            $this->stmt(
                'INSERT INTO pagamentos (pedido_id, valor, registrado_por, metodo, observacao, token_operacao) VALUES (?, ?, ?, ?, ?, ?)',
                'idisss', [(int) $pedidoId, $valor, (int) $usuario['id'], $metodo ?: null, $observacao ?: null, $token]
            );
            $novoTotal = round($recebido + $valor, 2);
            $status = $novoTotal <= 0 ? 'pendente' : ($novoTotal + 0.004 < (float) $pedido['valor_total'] ? 'parcial' : 'pago');
            $this->stmt('UPDATE pedidos SET status_pagamento = ? WHERE id = ?', 'si', [$status, (int) $pedidoId]);
            $this->audit($usuario['id'], 'registrar_pagamento', $pedido['numero_pedido'] . ': R$ ' . number_format($valor, 2, ',', '.') . '.');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback(); throw $e;
        }
    }

    public function listarPagamentos($pedidoId)
    {
        return $this->all('SELECT pg.*, u.nome_completo AS registrado_por_nome FROM pagamentos pg LEFT JOIN usuarios u ON u.id = pg.registrado_por WHERE pg.pedido_id = ? ORDER BY pg.data_pagamento DESC, pg.id DESC', 'i', [(int) $pedidoId]);
    }

    public function obterResumoFinanceiro()
    {
        $row = $this->one(
            "SELECT COALESCE(SUM(p.valor_total), 0) AS total_vendido,
                    COUNT(*) AS quantidade_pedidos
             FROM pedidos p WHERE p.status_operacional <> 'rascunho'"
        );
        $pg = $this->one("SELECT COALESCE(SUM(pg.valor), 0) AS total_recebido FROM pagamentos pg INNER JOIN pedidos p ON p.id = pg.pedido_id WHERE p.status_operacional <> 'rascunho'");
        $row['total_recebido'] = (float) ($pg['total_recebido'] ?? 0);
        $row['saldo_aberto'] = max(0, round((float) $row['total_vendido'] - $row['total_recebido'], 2));
        return $row;
    }

    public function obterRelatorios()
    {
        $resumo = $this->obterResumoFinanceiro();
        $ranking = $this->all(
            "SELECT c.id, c.nome, c.setor, COUNT(DISTINCT p.id) AS pedidos, COALESCE(SUM(p.valor_total), 0) AS total_vendido,
                    COALESCE(SUM(pi.qtd), 0) AS camisetas
             FROM congregacoes c
             LEFT JOIN pedidos p ON p.congregacao_id = c.id AND p.status_operacional <> 'rascunho'
             LEFT JOIN (SELECT pedido_id, SUM(quantidade) AS qtd FROM pedido_itens GROUP BY pedido_id) pi ON pi.pedido_id = p.id
             WHERE c.tipo = 'congregacao' AND c.participa_ranking = 1
             GROUP BY c.id, c.nome, c.setor
             ORDER BY total_vendido DESC, camisetas DESC, MIN(c.ordem_exibicao) ASC"
        );
        $balcao = $this->one("SELECT COUNT(*) AS pedidos, COALESCE(SUM(valor_total),0) AS total_vendido FROM pedidos p INNER JOIN congregacoes c ON c.id = p.congregacao_id WHERE c.tipo = 'balcao' AND p.status_operacional <> 'rascunho'");
        return ['resumo' => $resumo, 'ranking' => $ranking, 'balcao' => $balcao ?: ['pedidos' => 0, 'total_vendido' => 0]];
    }

    public function listarPedidosElegiveisLote()
    {
        return $this->all(
            "SELECT p.id, p.numero_pedido, p.nome_comprador, p.valor_total, c.nome AS congregacao_nome
             FROM pedidos p INNER JOIN congregacoes c ON c.id = p.congregacao_id
             WHERE p.status_operacional = 'liberado_producao'
               AND NOT EXISTS (SELECT 1 FROM lote_pedidos lp WHERE lp.id_pedido = p.id)
             ORDER BY p.criado_em ASC, p.id ASC"
        );
    }

    public function criarLote(array $ids, array $usuario)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) { return $id > 0; })));
        if (!$ids) throw new DomainException('Selecione pelo menos um pedido liberado para produção.');
        $this->db->begin_transaction();
        try {
            sort($ids);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $pedidos = $this->all("SELECT p.id, p.numero_pedido, p.status_operacional FROM pedidos p WHERE p.id IN ($in) FOR UPDATE", $types, $ids);
            if (count($pedidos) !== count($ids)) throw new DomainException('Um ou mais pedidos selecionados não existem.');
            foreach ($pedidos as $p) {
                if ($p['status_operacional'] !== 'liberado_producao') throw new DomainException('Somente pedidos liberados para produção podem entrar no lote.');
                $ja = $this->one('SELECT id_lote FROM lote_pedidos WHERE id_pedido = ? LIMIT 1', 'i', [(int) $p['id']]);
                if ($ja) throw new DomainException('Um pedido selecionado já pertence a outro lote. Atualize a lista e tente novamente.');
            }
            $codigoTemporario = 'TEMP-' . bin2hex(random_bytes(8));
            $this->stmt("INSERT INTO lotes_fornecedor (codigo_lote, status, id_usuario_gerou) VALUES (?, 'preparado', ?)", 'si', [$codigoTemporario, (int) $usuario['id']]);
            $loteId = (int) $this->db->insert_id;
            $codigo = 'UMD-2027-' . str_pad((string) $loteId, 6, '0', STR_PAD_LEFT);
            $this->stmt('UPDATE lotes_fornecedor SET codigo_lote = ? WHERE id = ?', 'si', [$codigo, $loteId]);
            foreach ($ids as $id) {
                $this->stmt('INSERT INTO lote_pedidos (id_lote, id_pedido) VALUES (?, ?)', 'ii', [$loteId, $id]);
                $this->stmt("UPDATE pedidos SET status_operacional = 'lote_gerado' WHERE id = ? AND status_operacional = 'liberado_producao'", 'i', [$id]);
            }
            $this->audit($usuario['id'], 'criar_lote', 'Criou o lote ' . $codigo . ' com ' . count($ids) . ' pedido(s).');
            $this->db->commit();
            return $loteId;
        } catch (Throwable $e) {
            $this->db->rollback(); throw $e;
        }
    }

    public function listarLotes()
    {
        return $this->all(
            'SELECT l.*, u.nome_completo AS gerado_por_nome, ue.nome_completo AS enviado_por_nome, COALESCE(q.quantidade_pedidos, 0) AS quantidade_pedidos
             FROM lotes_fornecedor l LEFT JOIN usuarios u ON u.id = l.id_usuario_gerou
             LEFT JOIN usuarios ue ON ue.id = l.id_usuario_envio
             LEFT JOIN (SELECT id_lote, COUNT(*) AS quantidade_pedidos FROM lote_pedidos GROUP BY id_lote) q ON q.id_lote = l.id
             ORDER BY l.id DESC'
        );
    }

    public function obterLote($id)
    {
        $lote = $this->one('SELECT l.*, u.nome_completo AS gerado_por_nome, ue.nome_completo AS enviado_por_nome FROM lotes_fornecedor l LEFT JOIN usuarios u ON u.id = l.id_usuario_gerou LEFT JOIN usuarios ue ON ue.id = l.id_usuario_envio WHERE l.id = ? LIMIT 1', 'i', [(int) $id]);
        if (!$lote) return null;
        $lote['pedidos'] = $this->all(
            'SELECT p.id, p.numero_pedido, p.nome_comprador, c.nome AS congregacao_nome FROM lote_pedidos lp INNER JOIN pedidos p ON p.id = lp.id_pedido INNER JOIN congregacoes c ON c.id = p.congregacao_id WHERE lp.id_lote = ? ORDER BY p.id',
            'i', [(int) $id]
        );
        $lote['itens_consolidados'] = $this->all(
            'SELECT pi.modelo, pi.tamanho, SUM(pi.quantidade) AS quantidade FROM lote_pedidos lp INNER JOIN pedido_itens pi ON pi.pedido_id = lp.id_pedido WHERE lp.id_lote = ? GROUP BY pi.modelo, pi.tamanho ORDER BY MIN(pi.id)',
            'i', [(int) $id]
        );
        return $lote;
    }

    public function atualizarLote($id, $status, $numeroExterno, array $usuario)
    {
        if (!in_array($status, ['preparado', 'enviado_fornecedor', 'entregue'], true)) throw new DomainException('Status do lote inválido.');
        $lote = $this->obterLote($id);
        if (!$lote) throw new DomainException('Lote não encontrado.');
        $transicoesLote = ['preparado' => ['preparado', 'enviado_fornecedor'], 'enviado_fornecedor' => ['enviado_fornecedor', 'entregue'], 'entregue' => ['entregue']];
        if (!in_array($status, $transicoesLote[$lote['status']] ?? [], true)) throw new DomainException('Não é permitido retroceder o status deste lote.');
        $numeroExterno = trim((string) $numeroExterno);
        if ($this->comprimento($numeroExterno) > 100) throw new DomainException('O número do pedido do fornecedor deve ter até 100 caracteres.');
        $dataEnvio = $lote['data_envio'];
        $usuarioEnvio = $lote['id_usuario_envio'];
        if ($status === 'enviado_fornecedor' && !$dataEnvio) { $dataEnvio = date('Y-m-d H:i:s'); $usuarioEnvio = (int) $usuario['id']; }
        $this->stmt('UPDATE lotes_fornecedor SET status = ?, numero_pedido_externo = ?, data_envio = ?, id_usuario_envio = ? WHERE id = ?', 'sssii', [$status, $numeroExterno ?: null, $dataEnvio, $usuarioEnvio ? (int) $usuarioEnvio : null, (int) $id]);
        $this->audit($usuario['id'], 'atualizar_lote', 'Atualizou lote ' . $lote['codigo_lote'] . ' para ' . $status . '.');
    }

    public function alterarSenhaUsuario($usuarioId, $senhaAtual, $novaSenha)
    {
        $usuario = $this->obterUsuario((int) $usuarioId);
        if (!$usuario || !password_verify((string) $senhaAtual, $usuario['senha_hash'])) throw new DomainException('A senha atual está incorreta.');
        if (strlen((string) $novaSenha) < 8 || strlen((string) $novaSenha) > 200) throw new DomainException('A nova senha deve ter pelo menos 8 caracteres.');
        $hash = password_hash((string) $novaSenha, PASSWORD_DEFAULT);
        $this->stmt('UPDATE usuarios SET senha_hash = ?, senha_inicial_ativa = 0 WHERE id = ?', 'si', [$hash, (int) $usuarioId]);
        $this->audit($usuarioId, 'alterar_senha', 'Senha alterada pelo próprio usuário.');
    }

    public function recuperarSenha($email, $nascimento, $novaSenha)
    {
        $email = $this->lowercase(trim((string) $email));
        $data = DateTime::createFromFormat('!Y-m-d', trim((string) $nascimento));
        if (strlen((string) $novaSenha) < 8 || strlen((string) $novaSenha) > 200) throw new DomainException('A nova senha deve ter pelo menos 8 caracteres.');
        $usuario = $this->obterUsuarioPorEmail($email);
        if (!$data || !$usuario || $usuario['data_nascimento'] !== $data->format('Y-m-d') || $usuario['status'] !== 'ativo') {
            throw new DomainException('Não foi possível validar os dados informados. Confira e tente novamente.');
        }
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $this->stmt('UPDATE usuarios SET senha_hash = ?, senha_inicial_ativa = 0 WHERE id = ?', 'si', [$hash, (int) $usuario['id']]);
        $this->audit($usuario['id'], 'recuperar_senha', 'Senha redefinida pela validação de e-mail e nascimento.');
    }

    private function normalizarTelefoneContato($telefone)
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone);
        // Aceita telefone digitado com código do Brasil, mas armazena somente DDD + número.
        if ((strlen($digitos) === 12 || strlen($digitos) === 13) && substr($digitos, 0, 2) === '55') {
            $digitos = substr($digitos, 2);
        }
        if (strlen($digitos) !== 10 && strlen($digitos) !== 11) {
            throw new DomainException('Informe um telefone/WhatsApp válido com DDD (10 ou 11 dígitos).');
        }
        return $digitos;
    }

    private function lowercase($texto)
    {
        return function_exists('mb_strtolower') ? mb_strtolower((string) $texto, 'UTF-8') : strtolower((string) $texto);
    }

    private function comprimento($texto)
    {
        return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
    }
}
