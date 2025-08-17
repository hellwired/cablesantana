-- Este script corrige la tabla `pagos` para que se relacione con los clientes en lugar de los usuarios.

-- Paso 1: Eliminar la llave foránea existente en la tabla `pagos`.
-- El nombre de la restricción es 'pagos_ibfk_1' según el archivo .sql que proporcionaste.
ALTER TABLE `pagos` DROP FOREIGN KEY `pagos_ibfk_1`;

-- Paso 2: Renombrar la columna `usuario_id` a `cliente_id` y asegurarse de que no sea nula.
ALTER TABLE `pagos` CHANGE `usuario_id` `cliente_id` INT(11) NOT NULL;

-- Paso 3: Añadir la nueva llave foránea que apunta a la tabla `cliente`.
-- Esto asegura que un pago solo puede existir si está asociado a un cliente válido.
ALTER TABLE `pagos` ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Opcional pero recomendado: Añadir un índice a la nueva columna para mejorar el rendimiento.
-- La creación de la llave foránea normalmente crea un índice, pero es bueno asegurarse.
-- Si el índice ya existe por la FK, este comando podría dar un error de duplicado, lo cual es seguro ignorar.
ALTER TABLE `pagos` ADD INDEX `idx_cliente_id` (`cliente_id`);

