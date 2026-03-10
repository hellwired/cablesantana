-- Este script corrige la tabla `pagos` y limpia los registros huérfanos.

-- Paso 1: Eliminar la llave foránea existente en la tabla `pagos`.
ALTER TABLE `pagos` DROP FOREIGN KEY `pagos_ibfk_1`;

-- Paso 2: Renombrar la columna `usuario_id` a `cliente_id`.
ALTER TABLE `pagos` CHANGE `usuario_id` `cliente_id` INT(11) NOT NULL;

-- !! PASO IMPORTANTE DE LIMPIEZA !!
-- Paso 3: Eliminar los pagos "huérfanos" que no corresponden a ningún cliente existente.
-- Esto es necesario para poder crear la nueva relación.
DELETE FROM `pagos`
WHERE `cliente_id` NOT IN (SELECT `id` FROM `cliente`);

-- Paso 4: Añadir la nueva llave foránea que apunta a la tabla `cliente`.
ALTER TABLE `pagos` ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Paso 5 (Opcional): Añadir un índice a la nueva columna.
-- Si el índice ya existe por la FK, este comando podría dar un error de duplicado, lo cual es seguro ignorar.
ALTER TABLE `pagos` ADD INDEX `idx_cliente_id` (`cliente_id`);
