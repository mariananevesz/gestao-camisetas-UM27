-- MIGRACAO ADITIVA PARA QUEM JA POSSUI O BANCO ORIGINAL UMADEPRESP.
-- Faça um backup completo antes de executar. Este script não apaga pedidos, itens,
-- congregações ou camisetas. Pedidos existentes passam a ser considerados enviados.
USE `umadepresp_camisetas`;
SET NAMES utf8mb4;

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
  PRIMARY KEY (`id`), UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_status` (`status`), KEY `idx_usuarios_perfil` (`perfil`),
  CONSTRAINT `fk_usuarios_congregacao` FOREIGN KEY (`congregacao_id`) REFERENCES `congregacoes` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'telefone');
SET @sql := IF(@col = 0, 'ALTER TABLE `usuarios` ADD COLUMN `telefone` VARCHAR(30) DEFAULT NULL AFTER `email`', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS `usuario_permissoes` (
  `usuario_id` INT UNSIGNED NOT NULL,
  `permissao` VARCHAR(60) NOT NULL,
  `permitido` TINYINT(1) NOT NULL DEFAULT 0,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `permissao`),
  CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_criou');
SET @sql := IF(@col = 0, 'ALTER TABLE `pedidos` ADD COLUMN `id_usuario_criou` INT UNSIGNED DEFAULT NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_responsavel');
SET @sql := IF(@col = 0, 'ALTER TABLE `pedidos` ADD COLUMN `id_usuario_responsavel` INT UNSIGNED DEFAULT NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'setor_balcao');
SET @sql := IF(@col = 0, 'ALTER TABLE `pedidos` ADD COLUMN `setor_balcao` VARCHAR(10) DEFAULT NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'status_operacional');
SET @sql := IF(@col = 0, 'ALTER TABLE `pedidos` ADD COLUMN `status_operacional` ENUM(''rascunho'', ''enviado'', ''em_conferencia'', ''conferido'', ''liberado_producao'', ''lote_gerado'') NOT NULL DEFAULT ''enviado''', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Acrescenta 'parcial' sem alterar os estados antigos.
ALTER TABLE `pedidos` MODIFY COLUMN `status_pagamento` ENUM('pendente', 'parcial', 'pago') NOT NULL DEFAULT 'pendente';

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND INDEX_NAME = 'idx_pedidos_status_operacional');
SET @sql := IF(@idx = 0, 'ALTER TABLE `pedidos` ADD KEY `idx_pedidos_status_operacional` (`status_operacional`)', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND INDEX_NAME = 'idx_pedidos_responsavel');
SET @sql := IF(@idx = 0, 'ALTER TABLE `pedidos` ADD KEY `idx_pedidos_responsavel` (`id_usuario_responsavel`)', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_pedidos_usuario_criou');
SET @sql := IF(@fk = 0, 'ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_usuario_criou` FOREIGN KEY (`id_usuario_criou`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @fk := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_pedidos_usuario_responsavel');
SET @sql := IF(@fk = 0, 'ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_usuario_responsavel` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS `pagamentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id` INT UNSIGNED NOT NULL,
  `valor` DECIMAL(12,2) NOT NULL,
  `data_pagamento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED DEFAULT NULL,
  `metodo` ENUM('pix', 'dinheiro', 'cartao', 'outro') DEFAULT NULL,
  `observacao` VARCHAR(255) DEFAULT NULL,
  `token_operacao` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_pagamentos_token_operacao` (`token_operacao`),
  KEY `idx_pagamentos_pedido` (`pedido_id`), KEY `idx_pagamentos_data` (`data_pagamento`),
  CONSTRAINT `fk_pagamentos_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_pagamentos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `pagamentos` SET `metodo` = NULL WHERE `metodo` = 'transferencia';
ALTER TABLE `pagamentos` MODIFY COLUMN `metodo` ENUM('pix', 'dinheiro', 'cartao', 'outro') DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `lotes_fornecedor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_lote` VARCHAR(50) NOT NULL,
  `data_geracao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_gerou` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('preparado', 'enviado_fornecedor', 'entregue') NOT NULL DEFAULT 'preparado',
  `numero_pedido_externo` VARCHAR(100) DEFAULT NULL,
  `data_envio` DATETIME DEFAULT NULL,
  `id_usuario_envio` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_lotes_codigo` (`codigo_lote`),
  CONSTRAINT `fk_lotes_gerou` FOREIGN KEY (`id_usuario_gerou`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_lotes_envio` FOREIGN KEY (`id_usuario_envio`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lote_pedidos` (
  `id_lote` INT UNSIGNED NOT NULL,
  `id_pedido` INT UNSIGNED NOT NULL,
  `adicionado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lote`, `id_pedido`), UNIQUE KEY `uk_lote_pedido_unico` (`id_pedido`),
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
  PRIMARY KEY (`id`), KEY `idx_auditoria_data` (`data_hora`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `limites_acesso` (
  `chave` CHAR(64) NOT NULL,
  `tentativas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bloqueado_ate` DATETIME DEFAULT NULL,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Converte o status antigo 'pago' em um lançamento histórico integral, sem duplicar se a migração for repetida.
INSERT INTO `pagamentos` (`pedido_id`, `valor`, `data_pagamento`, `registrado_por`, `metodo`, `observacao`, `token_operacao`)
SELECT p.`id`, p.`valor_total`, p.`atualizado_em`, NULL, NULL, 'Migração do status de pagamento da versão anterior', CONCAT('MIGRACAO-LEGADO-', p.`id`)
FROM `pedidos` p
WHERE p.`status_pagamento` = 'pago'
  AND p.`valor_total` > 0
  AND NOT EXISTS (SELECT 1 FROM `pagamentos` pg WHERE pg.`pedido_id` = p.`id`);

-- Conta Master inicial. Se já existir, a senha atual não é sobrescrita.
INSERT IGNORE INTO `usuarios`
  (`nome_completo`, `email`, `data_nascimento`, `senha_hash`, `congregacao_id`, `perfil`, `status`, `senha_inicial_ativa`)
VALUES
  ('Administradora Master', 'master@umadepresp.com.br', '1990-01-01', '$2y$12$sfDcSxN0p2yilN/J8yUNluJeLLiqVCDRCW.iq1MdPfvQqMw8JUcYm', NULL, 'master', 'ativo', 0);

-- As linhas antigas de pedidos ficam preservadas e bloqueadas para edição, pois passam a status 'enviado'.
