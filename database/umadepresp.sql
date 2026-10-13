-- Sistema de Gestão de Camisetas – UMADEPRESP 2027
-- Importe este arquivo pelo phpMyAdmin ou pelo cliente MySQL.

CREATE DATABASE IF NOT EXISTS `umadepresp_camisetas`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `umadepresp_camisetas`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `congregacoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(180) NOT NULL,
  `setor` VARCHAR(10) DEFAULT NULL COMMENT 'Identificador interno; não é exibido na interface',
  `tipo` ENUM('balcao', 'congregacao') NOT NULL DEFAULT 'congregacao',
  `participa_ranking` TINYINT(1) NOT NULL DEFAULT 1,
  `ordem_exibicao` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_congregacoes_ordem` (`ordem_exibicao`),
  KEY `idx_congregacoes_ranking` (`participa_ranking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `camisetas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `modelo` VARCHAR(60) NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ordem_exibicao` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_camiseta_modelo_tamanho` (`modelo`, `tamanho`),
  KEY `idx_camisetas_ordem` (`ordem_exibicao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(180) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `telefone` VARCHAR(30) DEFAULT NULL,
  `data_nascimento` DATE NOT NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `congregacao_id` INT UNSIGNED DEFAULT NULL,
  `perfil` ENUM('master', 'gestao', 'lider') NOT NULL DEFAULT 'lider',
  `status` ENUM('aguardando_aprovacao', 'ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'aguardando_aprovacao',
  `senha_inicial_ativa` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_status` (`status`),
  KEY `idx_usuarios_perfil` (`perfil`),
  CONSTRAINT `fk_usuarios_congregacao` FOREIGN KEY (`congregacao_id`) REFERENCES `congregacoes` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuario_permissoes` (
  `usuario_id` INT UNSIGNED NOT NULL,
  `permissao` VARCHAR(60) NOT NULL,
  `permitido` TINYINT(1) NOT NULL DEFAULT 0,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `permissao`),
  CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero_pedido` VARCHAR(30) DEFAULT NULL,
  `nome_comprador` VARCHAR(150) NOT NULL,
  `congregacao_id` INT UNSIGNED NOT NULL,
  `telefone` VARCHAR(30) NOT NULL,
  `valor_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `id_usuario_criou` INT UNSIGNED DEFAULT NULL,
  `id_usuario_responsavel` INT UNSIGNED DEFAULT NULL,
  `setor_balcao` VARCHAR(10) DEFAULT NULL,
  `status_operacional` ENUM('rascunho', 'enviado', 'em_conferencia', 'conferido', 'liberado_producao', 'lote_gerado') NOT NULL DEFAULT 'rascunho',
  `status_entrega` ENUM('aguardando', 'entregue') NOT NULL DEFAULT 'aguardando',
  `status_pagamento` ENUM('pendente', 'parcial', 'pago') NOT NULL DEFAULT 'pendente',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pedidos_numero` (`numero_pedido`),
  KEY `idx_pedidos_congregacao` (`congregacao_id`),
  KEY `idx_pedidos_data` (`criado_em`),
  KEY `idx_pedidos_pagamento` (`status_pagamento`),
  KEY `idx_pedidos_entrega` (`status_entrega`),
  KEY `idx_pedidos_status_operacional` (`status_operacional`),
  KEY `idx_pedidos_responsavel` (`id_usuario_responsavel`),
  CONSTRAINT `fk_pedidos_usuario_criou` FOREIGN KEY (`id_usuario_criou`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_pedidos_usuario_responsavel` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_pedidos_congregacao`
    FOREIGN KEY (`congregacao_id`) REFERENCES `congregacoes` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pedido_itens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id` INT UNSIGNED NOT NULL,
  `camiseta_id` INT UNSIGNED DEFAULT NULL,
  `modelo` VARCHAR(60) NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `valor_unitario` DECIMAL(10,2) NOT NULL,
  `quantidade` INT UNSIGNED NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_itens_pedido` (`pedido_id`),
  KEY `idx_itens_camiseta` (`camiseta_id`),
  CONSTRAINT `fk_itens_pedido`
    FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_itens_camiseta`
    FOREIGN KEY (`camiseta_id`) REFERENCES `camisetas` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pagamentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id` INT UNSIGNED NOT NULL,
  `valor` DECIMAL(12,2) NOT NULL,
  `data_pagamento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED DEFAULT NULL,
  `metodo` ENUM('pix', 'dinheiro', 'cartao', 'outro') DEFAULT NULL,
  `observacao` VARCHAR(255) DEFAULT NULL,
  `token_operacao` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pagamentos_token_operacao` (`token_operacao`),
  KEY `idx_pagamentos_pedido` (`pedido_id`),
  KEY `idx_pagamentos_data` (`data_pagamento`),
  CONSTRAINT `fk_pagamentos_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_pagamentos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lotes_fornecedor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_lote` VARCHAR(50) NOT NULL,
  `data_geracao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_gerou` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('preparado', 'enviado_fornecedor', 'entregue') NOT NULL DEFAULT 'preparado',
  `numero_pedido_externo` VARCHAR(100) DEFAULT NULL,
  `data_envio` DATETIME DEFAULT NULL,
  `id_usuario_envio` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lotes_codigo` (`codigo_lote`),
  CONSTRAINT `fk_lotes_gerou` FOREIGN KEY (`id_usuario_gerou`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_lotes_envio` FOREIGN KEY (`id_usuario_envio`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lote_pedidos` (
  `id_lote` INT UNSIGNED NOT NULL,
  `id_pedido` INT UNSIGNED NOT NULL,
  `adicionado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lote`, `id_pedido`),
  UNIQUE KEY `uk_lote_pedido_unico` (`id_pedido`),
  CONSTRAINT `fk_lote_pedidos_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes_fornecedor` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_lote_pedidos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED DEFAULT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_data` (`data_hora`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `congregacoes`
  (`id`, `nome`, `setor`, `tipo`, `participa_ranking`, `ordem_exibicao`)
VALUES
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
  (98, 'GIRASSOIS', '40', 'congregacao', 1, 98)
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `setor` = VALUES(`setor`),
  `tipo` = VALUES(`tipo`),
  `participa_ranking` = VALUES(`participa_ranking`),
  `ordem_exibicao` = VALUES(`ordem_exibicao`);

INSERT INTO `camisetas`
  (`id`, `modelo`, `tamanho`, `valor`, `ativo`, `ordem_exibicao`)
VALUES
  (1, 'Baby Look', 'PP', 45.00, 1, 1),
  (2, 'Baby Look', 'P', 45.00, 1, 2),
  (3, 'Baby Look', 'M', 45.00, 1, 3),
  (4, 'Baby Look', 'G', 45.00, 1, 4),
  (5, 'Baby Look', 'GG', 45.00, 1, 5),
  (6, 'Tradicional', 'PP', 45.00, 1, 6),
  (7, 'Tradicional', 'P', 45.00, 1, 7),
  (8, 'Tradicional', 'M', 45.00, 1, 8),
  (9, 'Tradicional', 'G', 45.00, 1, 9),
  (10, 'Tradicional', 'GG', 45.00, 1, 10),
  (11, 'Infantil', 'Nº 4', 45.00, 1, 11),
  (12, 'Infantil', 'Nº 6', 45.00, 1, 12),
  (13, 'Infantil', 'Nº 8', 45.00, 1, 13),
  (14, 'Infantil', 'Nº 10', 45.00, 1, 14),
  (15, 'Infantil', 'Nº 12', 45.00, 1, 15),
  (16, 'Especial', 'XG', 50.00, 1, 16),
  (17, 'Especial', 'XGG', 50.00, 1, 17)
ON DUPLICATE KEY UPDATE
  `modelo` = VALUES(`modelo`),
  `tamanho` = VALUES(`tamanho`),
  `valor` = VALUES(`valor`),
  `ativo` = VALUES(`ativo`),
  `ordem_exibicao` = VALUES(`ordem_exibicao`);

-- Atualiza automaticamente pedidos criados com a numeração da primeira versão.
UPDATE `pedidos`
SET `numero_pedido` = CONCAT('Pedido ', LPAD(`id`, 7, '0'))
WHERE `numero_pedido` LIKE 'UMA-2027-%';


CREATE TABLE IF NOT EXISTS `limites_acesso` (
  `chave` CHAR(64) NOT NULL,
  `tentativas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bloqueado_ate` DATETIME DEFAULT NULL,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conta Master inicial para primeiro acesso local.
-- E-mail: master@umadepresp.com.br | Senha inicial: 01011990
-- Troque a senha após o primeiro acesso, principalmente antes de publicar o sistema.
INSERT IGNORE INTO `usuarios`
  (`nome_completo`, `email`, `data_nascimento`, `senha_hash`, `congregacao_id`, `perfil`, `status`, `senha_inicial_ativa`)
VALUES
  ('Administradora Master', 'master@umadepresp.com.br', '1990-01-01', '$2y$12$sfDcSxN0p2yilN/J8yUNluJeLLiqVCDRCW.iq1MdPfvQqMw8JUcYm', NULL, 'master', 'ativo', 0);
