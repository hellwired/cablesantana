-- Este script corrige la tabla `pagos` y limpia los registros huérfanos.
-- Versión 3: Se ha eliminado el primer paso que borraba la llave foránea, ya que parece que ya no existe.

-- Paso 1: Renombrar la columna `usuario_id` a `cliente_id` (si aún no se ha hecho).
-- Si este paso da error porque la columna ya se llama `cliente_id`, puedes ignorarlo y ejecutar el siguiente.
ALTER TABLE `pagos` CHANGE `usuario_id` `cliente_id` INT(11) NOT NULL;

-- !! PASO IMPORTANTE DE LIMPIEZA !!
-- Paso 2: Eliminar los pagos "huérfanos" que no corresponden a ningún cliente existente.
DELETE FROM `pagos`
WHERE `cliente_id` NOT IN (SELECT `id` FROM `cliente`);

-- Paso 3: Añadir la nueva llave foránea que apunta a la tabla `cliente`.
ALTER TABLE `pagos` ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Paso 4 (Opcional): Añadir un índice a la nueva columna.
ALTER TABLE `pagos` ADD INDEX `idx_cliente_id` (`cliente_id`);
