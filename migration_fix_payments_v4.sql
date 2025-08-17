-- Este script finaliza la corrección de la tabla `pagos`.
-- Ejecute estos pasos, ya que los anteriores parecen haber sido completados parcialmente.

-- Paso 1: Eliminar los pagos "huérfanos" que no corresponden a ningún cliente existente.
DELETE FROM `pagos`
WHERE `cliente_id` NOT IN (SELECT `id` FROM `cliente`);

-- Paso 2: Añadir la nueva llave foránea que apunta a la tabla `cliente`.
-- Si esta restricción ya existe, MySQL dará un error, lo cual es seguro ignorar.
ALTER TABLE `pagos` ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Paso 3 (Opcional): Añadir un índice a la nueva columna.
-- Si el índice ya existe, MySQL dará un error, lo cual es seguro ignorar.
ALTER TABLE `pagos` ADD INDEX `idx_cliente_id` (`cliente_id`);
