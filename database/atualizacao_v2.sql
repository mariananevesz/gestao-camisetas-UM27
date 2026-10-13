-- Atualização da primeira versão para a numeração "Pedido 0000001".
-- Este script não apaga pedidos nem altera valores, compradores ou status.

USE `umadepresp_camisetas`;

UPDATE `pedidos`
SET `numero_pedido` = CONCAT('Pedido ', LPAD(`id`, 7, '0'))
WHERE `numero_pedido` LIKE 'UMA-2027-%';
