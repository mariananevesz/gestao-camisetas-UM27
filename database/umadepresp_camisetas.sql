-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13-Out-2026 às 21:53
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `umadepresp_camisetas`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `camisetas`
--

CREATE TABLE `camisetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `tamanho` varchar(20) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ordem_exibicao` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `camisetas`
--

INSERT INTO `camisetas` (`id`, `modelo`, `tamanho`, `valor`, `ativo`, `ordem_exibicao`) VALUES
(1, 'Baby Look', 'PP', '45.00', 1, 1),
(2, 'Baby Look', 'P', '45.00', 1, 2),
(3, 'Baby Look', 'M', '45.00', 1, 3),
(4, 'Baby Look', 'G', '45.00', 1, 4),
(5, 'Baby Look', 'GG', '45.00', 1, 5),
(6, 'Tradicional', 'PP', '45.00', 1, 6),
(7, 'Tradicional', 'P', '45.00', 1, 7),
(8, 'Tradicional', 'M', '45.00', 1, 8),
(9, 'Tradicional', 'G', '45.00', 1, 9),
(10, 'Tradicional', 'GG', '45.00', 1, 10),
(11, 'Infantil', 'Nº 4', '45.00', 1, 11),
(12, 'Infantil', 'Nº 6', '45.00', 1, 12),
(13, 'Infantil', 'Nº 8', '45.00', 1, 13),
(14, 'Infantil', 'Nº 10', '45.00', 1, 14),
(15, 'Infantil', 'Nº 12', '45.00', 1, 15),
(16, 'Especial', 'XG', '50.00', 1, 16),
(17, 'Especial', 'XGG', '50.00', 1, 17);

-- --------------------------------------------------------

--
-- Estrutura da tabela `congregacoes`
--

CREATE TABLE `congregacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(180) NOT NULL,
  `setor` varchar(10) DEFAULT NULL COMMENT 'Identificador interno; não é exibido na interface',
  `tipo` enum('balcao','congregacao') NOT NULL DEFAULT 'congregacao',
  `participa_ranking` tinyint(1) NOT NULL DEFAULT 1,
  `ordem_exibicao` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `congregacoes`
--

INSERT INTO `congregacoes` (`id`, `nome`, `setor`, `tipo`, `participa_ranking`, `ordem_exibicao`) VALUES
(1, 'Balcão', NULL, 'balcao', 0, 1),
(2, 'SEDE', '1', 'congregacao', 1, 2),
(3, 'CREMONESI', '1', 'congregacao', 1, 3),
(4, 'JARDIM LEONOR', '1', 'congregacao', 1, 4),
(5, 'JOÃO DOMINGOS', '1', 'congregacao', 1, 5),
(6, 'VILA GENI', '1', 'congregacao', 1, 6),
(7, 'COBRAL', '1', 'congregacao', 1, 7),
(8, 'BELA VISTA', '2', 'congregacao', 1, 8),
(9, 'BELA VISTA', '3', 'congregacao', 1, 9),
(10, 'CENTRO/ALFA', '3', 'congregacao', 1, 10),
(11, 'JARDIM NATAL MARRAFON/ CANÁ DA GALILÉIA', '3', 'congregacao', 1, 11),
(12, 'JARDIM SÃO PEDRO/CAFARNAUM', '3', 'congregacao', 1, 12),
(13, 'JARDIM SOLEDADE/BETANIA', '3', 'congregacao', 1, 13),
(14, 'VILA MARQUES/ CANAÃ', '3', 'congregacao', 1, 14),
(15, 'MARTINOPOLIS', '4', 'congregacao', 1, 15),
(16, 'CANAÃ', '4', 'congregacao', 1, 16),
(17, 'JACKLAITS', '4', 'congregacao', 1, 17),
(18, 'JERUSALÉM', '4', 'congregacao', 1, 18),
(19, 'MORIÁ', '4', 'congregacao', 1, 19),
(20, 'PORTAL DO SOL', '4', 'congregacao', 1, 20),
(21, 'VILA ESCÓCIA', '4', 'congregacao', 1, 21),
(22, 'ALVARES MACHADO', '5', 'congregacao', 1, 22),
(23, 'CHÁCARA COBRAL/VALE DA BENÇÃO', '5', 'congregacao', 1, 23),
(24, 'JARDIM HORIZONTE / CANAÃ', '5', 'congregacao', 1, 24),
(25, 'BAIRRO RAIO DO SOL/BETEL', '5', 'congregacao', 1, 25),
(26, 'ALFREDO MARCONDES', '6', 'congregacao', 1, 26),
(27, 'REGENTE FEIJÓ', '7', 'congregacao', 1, 27),
(28, 'JARDIM REGINA', '7', 'congregacao', 1, 28),
(29, 'JARDIM SÃO BENTO', '7', 'congregacao', 1, 29),
(30, 'VILA XAVIER', '7', 'congregacao', 1, 30),
(31, 'ENEIDA', '8', 'congregacao', 1, 31),
(32, 'AMELIOPOLIS', '8', 'congregacao', 1, 32),
(33, 'FLORESTA DO SUL', '8', 'congregacao', 1, 33),
(34, 'ANHUMAS', '9', 'congregacao', 1, 34),
(35, 'CAIABU', '10', 'congregacao', 1, 35),
(36, 'OURO BRANCO', '10', 'congregacao', 1, 36),
(37, 'TEÇAINDA', '10', 'congregacao', 1, 37),
(38, 'CENTRO PIRAPOZINHO', '11', 'congregacao', 1, 38),
(39, 'BELEM/ JARDIM VANTINI1', '11', 'congregacao', 1, 39),
(40, 'BETEL/ JARDIM MORADA DO SOL', '11', 'congregacao', 1, 40),
(41, 'ROSA DE SARON/JARDIM SANTA ROSA', '11', 'congregacao', 1, 41),
(42, 'VALE DA BENÇÃO', '11', 'congregacao', 1, 42),
(43, 'INDIANA', '12', 'congregacao', 1, 43),
(44, 'BETESDA', '12', 'congregacao', 1, 44),
(45, 'SANTO EXPEDITO', '13', 'congregacao', 1, 45),
(46, 'MAANAIM', '13', 'congregacao', 1, 46),
(47, 'TARABAI', '14', 'congregacao', 1, 47),
(48, 'BANCO DA TERRA', '14', 'congregacao', 1, 48),
(49, 'PALÚ', '14', 'congregacao', 1, 49),
(50, 'NARANDIBA', '15', 'congregacao', 1, 50),
(51, 'ESPIGÃO', '16', 'congregacao', 1, 51),
(52, 'SÃO SEBASTIÃO', '16', 'congregacao', 1, 52),
(53, 'PANORAMA', '19', 'congregacao', 1, 53),
(54, 'PINHEIROS', '19', 'congregacao', 1, 54),
(55, 'VALE DO SOL', '20', 'congregacao', 1, 55),
(56, 'SANTA FÉ', '20', 'congregacao', 1, 56),
(57, 'MARACANÃ', '21', 'congregacao', 1, 57),
(58, 'MARÉ MANSA', '21', 'congregacao', 1, 58),
(59, 'PENIEL', '21', 'congregacao', 1, 59),
(60, 'PLANALTO', '22', 'congregacao', 1, 60),
(61, 'CAMBUCI', '22', 'congregacao', 1, 61),
(62, 'NOVA PRUDENTE', '22', 'congregacao', 1, 62),
(63, 'NOVA PLANALTINA', '23', 'congregacao', 1, 63),
(64, 'SANTA MÔNICA', '23', 'congregacao', 1, 64),
(65, 'ALVORADA', '24', 'congregacao', 1, 65),
(66, 'SUMARÉ', '24', 'congregacao', 1, 66),
(67, 'VILA BRASIL', '25', 'congregacao', 1, 67),
(68, 'VILA ITI', '25', 'congregacao', 1, 68),
(69, 'VILA MARCONDES', '26', 'congregacao', 1, 69),
(70, 'VILA FURQUIM', '26', 'congregacao', 1, 70),
(71, 'INDUSTRIAL', '27', 'congregacao', 1, 71),
(72, 'ALEXANDRINA', '28', 'congregacao', 1, 72),
(73, 'BRASIL NOVO', '28', 'congregacao', 1, 73),
(74, 'WATAL ISHIBASHI', '28', 'congregacao', 1, 74),
(75, 'HUMBERTO SALVADOR', '29', 'congregacao', 1, 75),
(76, 'AUGUSTO DE PAULA', '29', 'congregacao', 1, 76),
(77, 'GUANABARA', '30', 'congregacao', 1, 77),
(78, 'JABAQUARA', '30', 'congregacao', 1, 78),
(79, 'SÃO MATEUS', '31', 'congregacao', 1, 79),
(80, 'JARDIM ELDORADO', '31', 'congregacao', 1, 80),
(81, 'SANTA CLARA', '31', 'congregacao', 1, 81),
(82, 'SÃO GERALDO', '32', 'congregacao', 1, 82),
(83, 'SANTA PAULA', '33', 'congregacao', 1, 83),
(84, 'BALNEÁRIO', '33', 'congregacao', 1, 84),
(85, 'CEDRAL', '34', 'congregacao', 1, 85),
(86, 'JEQUITIBAS', '34', 'congregacao', 1, 86),
(87, 'MONTE ALTO', '34', 'congregacao', 1, 87),
(88, 'PAULISTA', '35', 'congregacao', 1, 88),
(89, 'REGINA', '36', 'congregacao', 1, 89),
(90, 'AVIAÇÃO', '36', 'congregacao', 1, 90),
(91, 'MORADO DO SOL', '37', 'congregacao', 1, 91),
(92, 'MONTALVÃO', '37', 'congregacao', 1, 92),
(93, 'ANA JACINTA', '38', 'congregacao', 1, 93),
(94, 'BETESDA', '38', 'congregacao', 1, 94),
(95, 'MÁRIO AMATO', '38', 'congregacao', 1, 95),
(96, 'JARDINS', '39', 'congregacao', 1, 96),
(97, 'CINQUENTENÁRIO', '40', 'congregacao', 1, 97),
(98, 'GIRASSOIS', '40', 'congregacao', 1, 98);

-- --------------------------------------------------------

--
-- Estrutura da tabela `limites_acesso`
--

CREATE TABLE `limites_acesso` (
  `chave` char(64) NOT NULL,
  `tentativas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `bloqueado_ate` datetime DEFAULT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `limites_acesso`
--

INSERT INTO `limites_acesso` (`chave`, `tentativas`, `inicio`, `bloqueado_ate`, `atualizado_em`) VALUES
('af48604f8abb5a4c0b7847fca0126cde7be277bed3dd48452d006e3c24c5c7ef', 1, '2026-10-13 16:39:11', NULL, '2026-10-13 16:39:11');

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs_auditoria`
--

CREATE TABLE `logs_auditoria` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `logs_auditoria`
--

INSERT INTO `logs_auditoria` (`id`, `id_usuario`, `acao`, `descricao`, `data_hora`, `ip_address`) VALUES
(47, 1, 'login', 'Login realizado.', '2026-10-13 15:02:31', '::1'),
(48, 1, 'alterar_senha', 'Senha alterada pelo próprio usuário.', '2026-10-13 15:02:58', '::1'),
(49, 1, 'criar_pedido', 'Criou Pedido 0000006 (enviado).', '2026-10-13 15:14:08', '::1'),
(50, 1, 'status_entrega', 'Pedido 0000006 marcado como entregue.', '2026-10-13 15:32:57', '::1'),
(51, 1, 'status_operacional', 'Pedido 0000006: enviado -> conferido.', '2026-10-13 15:32:59', '::1'),
(52, 1, 'status_operacional', 'Pedido 0000006: conferido -> liberado_producao.', '2026-10-13 15:33:00', '::1'),
(53, 1, 'registrar_pagamento', 'Pedido 0000006: R$ 185,00.', '2026-10-13 15:33:16', '::1'),
(54, 1, 'criar_pedido', 'Criou Pedido 0000007 (rascunho).', '2026-10-13 16:06:45', '::1'),
(55, 1, 'editar_rascunho', 'Atualizou Pedido 0000007 (enviado).', '2026-10-13 16:06:56', '::1'),
(56, 1, 'status_operacional', 'Pedido 0000007: enviado -> conferido.', '2026-10-13 16:07:32', '::1'),
(57, 1, 'status_operacional', 'Pedido 0000007: conferido -> liberado_producao.', '2026-10-13 16:07:36', '::1'),
(58, 1, 'registrar_pagamento', 'Pedido 0000007: R$ 25,00.', '2026-10-13 16:09:18', '::1'),
(59, 1, 'status_entrega', 'Pedido 0000007 marcado como entregue.', '2026-10-13 16:09:30', '::1'),
(60, 1, 'registrar_pagamento', 'Pedido 0000007: R$ 20,00.', '2026-10-13 16:09:47', '::1'),
(61, 1, 'status_entrega', 'Pedido 0000007 marcado como aguardando.', '2026-10-13 16:10:53', '::1'),
(62, 1, 'criar_pedido', 'Criou Pedido 0000008 (enviado).', '2026-10-13 16:21:11', '::1'),
(63, 1, 'status_operacional', 'Pedido 0000008: enviado -> conferido.', '2026-10-13 16:21:23', '::1'),
(64, 1, 'status_operacional', 'Pedido 0000008: conferido -> liberado_producao.', '2026-10-13 16:21:42', '::1'),
(65, 1, 'registrar_pagamento', 'Pedido 0000008: R$ 25,00.', '2026-10-13 16:24:14', '::1'),
(66, 1, 'status_entrega', 'Pedido 0000008 marcado como entregue.', '2026-10-13 16:33:11', '::1'),
(67, 1, 'registrar_pagamento', 'Pedido 0000008: R$ 20,00.', '2026-10-13 16:33:33', '::1'),
(68, 1, 'criar_lote', 'Criou o lote UMD-2027-000002 com 2 pedido(s).', '2026-10-13 16:34:06', '::1'),
(69, 1, 'login', 'Login realizado.', '2026-10-13 16:39:27', '::1'),
(70, 1, 'criar_gestor', 'Criou usuário de gestão #6.', '2026-10-13 16:40:13', '::1'),
(71, 1, 'status_usuario', 'Alterou usuário #6 para ativo.', '2026-10-13 16:40:19', '::1'),
(72, 1, 'permissao_usuario', 'Permissão criar_pedido do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(73, 1, 'permissao_usuario', 'Permissão pedidos_ver_todos do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(74, 1, 'permissao_usuario', 'Permissão financeiro do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(75, 1, 'permissao_usuario', 'Permissão lotes do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(76, 1, 'permissao_usuario', 'Permissão relatorios do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(77, 1, 'permissao_usuario', 'Permissão usuarios_gerenciar do usuário #6 definida como 0.', '2026-10-13 16:41:04', '::1'),
(78, 1, 'permissao_usuario', 'Permissão aprovar_cadastros do usuário #6 definida como 0.', '2026-10-13 16:41:04', '::1'),
(79, 1, 'permissao_usuario', 'Permissão criar_gestores do usuário #6 definida como 0.', '2026-10-13 16:41:04', '::1'),
(80, 1, 'permissao_usuario', 'Permissão criar_pedido_em_nome_de_outro do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(81, 1, 'permissao_usuario', 'Permissão alterar_status_operacional do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(82, 1, 'permissao_usuario', 'Permissão registrar_pagamentos do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(83, 1, 'permissao_usuario', 'Permissão gerar_lotes do usuário #6 definida como 1.', '2026-10-13 16:41:04', '::1'),
(84, 1, 'status_usuario', 'Alterou usuário #6 para ativo.', '2026-10-13 16:41:11', '::1'),
(85, 6, 'login', 'Login realizado.', '2026-10-13 16:41:57', '::1'),
(86, 6, 'criar_lote', 'Criou o lote UMD-2027-000003 com 1 pedido(s).', '2026-10-13 16:42:51', '::1'),
(87, 1, 'login', 'Login realizado.', '2026-10-13 16:44:38', '::1');

-- --------------------------------------------------------

--
-- Estrutura da tabela `lotes_fornecedor`
--

CREATE TABLE `lotes_fornecedor` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo_lote` varchar(50) NOT NULL,
  `data_geracao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario_gerou` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('preparado','enviado_fornecedor','entregue') NOT NULL DEFAULT 'preparado',
  `numero_pedido_externo` varchar(100) DEFAULT NULL,
  `data_envio` datetime DEFAULT NULL,
  `id_usuario_envio` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `lotes_fornecedor`
--

INSERT INTO `lotes_fornecedor` (`id`, `codigo_lote`, `data_geracao`, `id_usuario_gerou`, `status`, `numero_pedido_externo`, `data_envio`, `id_usuario_envio`) VALUES
(2, 'UMD-2027-000002', '2026-10-13 16:34:06', 1, 'preparado', NULL, NULL, NULL),
(3, 'UMD-2027-000003', '2026-10-13 16:42:51', 6, 'preparado', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `lote_pedidos`
--

CREATE TABLE `lote_pedidos` (
  `id_lote` int(10) UNSIGNED NOT NULL,
  `id_pedido` int(10) UNSIGNED NOT NULL,
  `adicionado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `lote_pedidos`
--

INSERT INTO `lote_pedidos` (`id_lote`, `id_pedido`, `adicionado_em`) VALUES
(2, 6, '2026-10-13 16:34:06'),
(2, 7, '2026-10-13 16:34:06'),
(3, 8, '2026-10-13 16:42:51');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `data_pagamento` datetime NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `metodo` enum('pix','dinheiro','cartao','outro') DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `token_operacao` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `pedido_id`, `valor`, `data_pagamento`, `registrado_por`, `metodo`, `observacao`, `token_operacao`) VALUES
(4, 6, '185.00', '2026-10-13 15:33:16', 1, 'pix', NULL, '5aebb57bc271594add2004de09d8cab863b1257a6251a0093282eb46325c0a52'),
(5, 7, '25.00', '2026-10-13 16:09:18', 1, 'pix', NULL, 'b4b6a0c671d62c3822e532d07cfc617d3a261d3a103d5abfbcae931b0437da06'),
(6, 7, '20.00', '2026-10-13 16:09:47', 1, NULL, NULL, '6177a848d7cc65b0759b9c2e7ef2dd48601c4453c81c0e8635a56b84975725d0'),
(7, 8, '25.00', '2026-10-13 16:24:14', 1, 'cartao', NULL, '7aec7128bd40c1b662e3bf84053391ef14a3707ba217a652e3725f7be2ee7ac3'),
(8, 8, '20.00', '2026-10-13 16:33:33', 1, 'pix', NULL, '136e466c18cff1e9dae5c8c7f3407e960b74dc9c07e4f80f1d9c76fdce977e94');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_pedido` varchar(30) DEFAULT NULL,
  `nome_comprador` varchar(150) NOT NULL,
  `congregacao_id` int(10) UNSIGNED NOT NULL,
  `telefone` varchar(30) NOT NULL,
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_usuario_criou` int(10) UNSIGNED DEFAULT NULL,
  `id_usuario_responsavel` int(10) UNSIGNED DEFAULT NULL,
  `setor_balcao` varchar(10) DEFAULT NULL,
  `status_operacional` enum('rascunho','enviado','em_conferencia','conferido','liberado_producao','lote_gerado') NOT NULL DEFAULT 'rascunho',
  `status_entrega` enum('aguardando','entregue') NOT NULL DEFAULT 'aguardando',
  `status_pagamento` enum('pendente','parcial','pago') NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `numero_pedido`, `nome_comprador`, `congregacao_id`, `telefone`, `valor_total`, `id_usuario_criou`, `id_usuario_responsavel`, `setor_balcao`, `status_operacional`, `status_entrega`, `status_pagamento`, `criado_em`, `atualizado_em`) VALUES
(6, 'Pedido 0000006', 'oioioioioi', 1, '(18) 99600-1271', '185.00', 1, 1, '1', 'lote_gerado', 'entregue', 'pago', '2026-10-13 15:14:08', '2026-10-13 16:34:06'),
(7, 'Pedido 0000007', 'Mari', 2, '(18) 99632-0649', '45.00', 1, 1, NULL, 'lote_gerado', 'aguardando', 'pago', '2026-10-13 16:06:45', '2026-10-13 16:34:06'),
(8, 'Pedido 0000008', 'Luna', 93, '(18) 99632-0649', '45.00', 1, 1, NULL, 'lote_gerado', 'entregue', 'pago', '2026-10-13 16:21:11', '2026-10-13 16:42:51');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pedido_itens`
--

CREATE TABLE `pedido_itens` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `camiseta_id` int(10) UNSIGNED DEFAULT NULL,
  `modelo` varchar(60) NOT NULL,
  `tamanho` varchar(20) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `quantidade` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `pedido_itens`
--

INSERT INTO `pedido_itens` (`id`, `pedido_id`, `camiseta_id`, `modelo`, `tamanho`, `valor_unitario`, `quantidade`, `subtotal`) VALUES
(32, 6, 4, 'Baby Look', 'G', '45.00', 2, '90.00'),
(33, 6, 8, 'Tradicional', 'M', '45.00', 1, '45.00'),
(34, 6, 17, 'Especial', 'XGG', '50.00', 1, '50.00'),
(36, 7, 4, 'Baby Look', 'G', '45.00', 1, '45.00'),
(37, 8, 6, 'Tradicional', 'PP', '45.00', 1, '45.00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome_completo` varchar(180) NOT NULL,
  `email` varchar(190) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `data_nascimento` date NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `congregacao_id` int(10) UNSIGNED DEFAULT NULL,
  `perfil` enum('master','gestao','lider') NOT NULL DEFAULT 'lider',
  `status` enum('aguardando_aprovacao','ativo','inativo','bloqueado') NOT NULL DEFAULT 'aguardando_aprovacao',
  `senha_inicial_ativa` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome_completo`, `email`, `telefone`, `data_nascimento`, `senha_hash`, `congregacao_id`, `perfil`, `status`, `senha_inicial_ativa`, `criado_em`, `atualizado_em`) VALUES
(1, 'Administrador Master', 'master@umadepresp', NULL, '1990-01-01', '$2y$10$aSfyR7sV.VtbYZJVLZ3pB.DQyvoHFfmZTumhsxKf6dZMnKjZ5UZ7C', NULL, 'master', 'ativo', 0, '2026-10-13 14:58:30', '2026-10-13 15:02:58'),
(6, 'William Henoch Alves Pereira', 'william@gmail.com', '18997011271', '2000-07-18', '$2y$10$Fg7uRolXi6lvHTch3.OSXupW3JFYe6CPLs7OgvpikhM8PHtBERoNq', NULL, 'gestao', 'ativo', 1, '2026-10-13 16:40:13', '2026-10-13 16:40:13');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario_permissoes`
--

CREATE TABLE `usuario_permissoes` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `permissao` varchar(60) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 0,
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuario_permissoes`
--

INSERT INTO `usuario_permissoes` (`usuario_id`, `permissao`, `permitido`, `atualizado_em`) VALUES
(6, 'alterar_status_operacional', 1, '2026-10-13 16:41:04'),
(6, 'aprovar_cadastros', 0, '2026-10-13 16:41:04'),
(6, 'criar_gestores', 0, '2026-10-13 16:41:04'),
(6, 'criar_pedido', 1, '2026-10-13 16:41:04'),
(6, 'criar_pedido_em_nome_de_outro', 1, '2026-10-13 16:41:04'),
(6, 'financeiro', 1, '2026-10-13 16:41:04'),
(6, 'gerar_lotes', 1, '2026-10-13 16:41:04'),
(6, 'lotes', 1, '2026-10-13 16:41:04'),
(6, 'pedidos_ver_todos', 1, '2026-10-13 16:41:04'),
(6, 'registrar_pagamentos', 1, '2026-10-13 16:41:04'),
(6, 'relatorios', 1, '2026-10-13 16:41:04'),
(6, 'usuarios_gerenciar', 0, '2026-10-13 16:41:04');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `camisetas`
--
ALTER TABLE `camisetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_camiseta_modelo_tamanho` (`modelo`,`tamanho`),
  ADD KEY `idx_camisetas_ordem` (`ordem_exibicao`);

--
-- Índices para tabela `congregacoes`
--
ALTER TABLE `congregacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_congregacoes_ordem` (`ordem_exibicao`),
  ADD KEY `idx_congregacoes_ranking` (`participa_ranking`);

--
-- Índices para tabela `limites_acesso`
--
ALTER TABLE `limites_acesso`
  ADD PRIMARY KEY (`chave`);

--
-- Índices para tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_data` (`data_hora`),
  ADD KEY `fk_auditoria_usuario` (`id_usuario`);

--
-- Índices para tabela `lotes_fornecedor`
--
ALTER TABLE `lotes_fornecedor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_lotes_codigo` (`codigo_lote`),
  ADD KEY `fk_lotes_gerou` (`id_usuario_gerou`),
  ADD KEY `fk_lotes_envio` (`id_usuario_envio`);

--
-- Índices para tabela `lote_pedidos`
--
ALTER TABLE `lote_pedidos`
  ADD PRIMARY KEY (`id_lote`,`id_pedido`),
  ADD UNIQUE KEY `uk_lote_pedido_unico` (`id_pedido`);

--
-- Índices para tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pagamentos_token_operacao` (`token_operacao`),
  ADD KEY `idx_pagamentos_pedido` (`pedido_id`),
  ADD KEY `idx_pagamentos_data` (`data_pagamento`),
  ADD KEY `fk_pagamentos_usuario` (`registrado_por`);

--
-- Índices para tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pedidos_numero` (`numero_pedido`),
  ADD KEY `idx_pedidos_congregacao` (`congregacao_id`),
  ADD KEY `idx_pedidos_data` (`criado_em`),
  ADD KEY `idx_pedidos_pagamento` (`status_pagamento`),
  ADD KEY `idx_pedidos_entrega` (`status_entrega`),
  ADD KEY `idx_pedidos_status_operacional` (`status_operacional`),
  ADD KEY `idx_pedidos_responsavel` (`id_usuario_responsavel`),
  ADD KEY `fk_pedidos_usuario_criou` (`id_usuario_criou`);

--
-- Índices para tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_itens_pedido` (`pedido_id`),
  ADD KEY `idx_itens_camiseta` (`camiseta_id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_status` (`status`),
  ADD KEY `idx_usuarios_perfil` (`perfil`),
  ADD KEY `fk_usuarios_congregacao` (`congregacao_id`);

--
-- Índices para tabela `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD PRIMARY KEY (`usuario_id`,`permissao`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `camisetas`
--
ALTER TABLE `camisetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `congregacoes`
--
ALTER TABLE `congregacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT de tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT de tabela `lotes_fornecedor`
--
ALTER TABLE `lotes_fornecedor`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `lotes_fornecedor`
--
ALTER TABLE `lotes_fornecedor`
  ADD CONSTRAINT `fk_lotes_envio` FOREIGN KEY (`id_usuario_envio`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lotes_gerou` FOREIGN KEY (`id_usuario_gerou`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `lote_pedidos`
--
ALTER TABLE `lote_pedidos`
  ADD CONSTRAINT `fk_lote_pedidos_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes_fornecedor` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lote_pedidos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pagamentos_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagamentos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_congregacao` FOREIGN KEY (`congregacao_id`) REFERENCES `congregacoes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_usuario_criou` FOREIGN KEY (`id_usuario_criou`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_usuario_responsavel` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `fk_itens_camiseta` FOREIGN KEY (`camiseta_id`) REFERENCES `camisetas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itens_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_congregacao` FOREIGN KEY (`congregacao_id`) REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
