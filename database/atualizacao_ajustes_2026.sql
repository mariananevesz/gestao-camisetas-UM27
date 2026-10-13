-- Atualização aditiva para o banco criado com database/umadepresp.sql.
-- Não apaga usuários, pedidos, itens, pagamentos, congregações ou lotes.
-- Execute com o banco umadepresp_camisetas selecionado no phpMyAdmin.
USE `umadepresp_camisetas`;
SET NAMES utf8mb4;

-- Adiciona telefone aos usuários, se ainda não existir.
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'telefone');
SET @sql := IF(@col = 0, 'ALTER TABLE `usuarios` ADD COLUMN `telefone` VARCHAR(30) DEFAULT NULL AFTER `email`', 'SELECT 1');
PREPARE stmt_telefone FROM @sql; EXECUTE stmt_telefone; DEALLOCATE PREPARE stmt_telefone;

-- A forma antiga Transferência deixa de ser uma opção. Registros de teste que a usavam
-- ficam como "Não informado" para preservar o histórico sem perder o lançamento.
UPDATE `pagamentos` SET `metodo` = NULL WHERE `metodo` = 'transferencia';
ALTER TABLE `pagamentos` MODIFY COLUMN `metodo` ENUM('pix', 'dinheiro', 'cartao', 'outro') DEFAULT NULL;
