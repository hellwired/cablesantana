-- =============================================================
-- migration_v5_fix.sql
-- CableColor: Columnas faltantes en pagos + tabla arqueos_caja
--
-- Aplicar cuando migration_v5.sql falló parcialmente porque
-- referencia_pago y descripcion ya existían en la tabla pagos.
-- Ejecutar UNA SOLA VEZ en producción.
-- =============================================================

-- PASO 1: Agregar solo las columnas que aún faltan en pagos
ALTER TABLE `pagos`
    ADD COLUMN `bloqueado`         TINYINT(1)   NOT NULL DEFAULT 1   AFTER `descripcion`,
    ADD COLUMN `registrado_por`    INT(11)      DEFAULT NULL          AFTER `bloqueado`,
    ADD COLUMN `motivo_desbloqueo` TEXT         DEFAULT NULL          AFTER `registrado_por`,
    ADD COLUMN `desbloqueado_por`  INT(11)      DEFAULT NULL          AFTER `motivo_desbloqueo`,
    ADD COLUMN `fecha_desbloqueo`  DATETIME     DEFAULT NULL          AFTER `desbloqueado_por`;

-- Índices para las nuevas columnas
ALTER TABLE `pagos`
    ADD KEY `idx_registrado_por`   (`registrado_por`),
    ADD KEY `idx_desbloqueado_por` (`desbloqueado_por`);

-- PASO 2: Crear tabla arqueos_caja
CREATE TABLE IF NOT EXISTS `arqueos_caja` (
    `id`             INT(11)        NOT NULL AUTO_INCREMENT,
    `admin_id`       INT(11)        NOT NULL    COMMENT 'Administrador que realizo el arqueo',
    `visor_id`       INT(11)        DEFAULT NULL COMMENT 'Cobrador auditado',
    `fecha_arqueo`   DATE           NOT NULL    COMMENT 'Fecha del periodo cobrado',
    `monto_esperado` DECIMAL(10,2)  NOT NULL    COMMENT 'Total calculado por el sistema',
    `monto_real`     DECIMAL(10,2)  NOT NULL    COMMENT 'Total fisico recibido del cobrador',
    `diferencia`     DECIMAL(10,2)  NOT NULL    COMMENT 'monto_real - monto_esperado',
    `estado`         ENUM('cuadrado','faltante','sobrante') NOT NULL,
    `observaciones`  TEXT           DEFAULT NULL,
    `fecha_registro` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_visor_fecha` (`visor_id`, `fecha_arqueo`),
    KEY `idx_admin_id`    (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- PASO 3: Template de recibo WhatsApp (idempotente)
INSERT IGNORE INTO `configuracion` (`clave`, `valor`)
VALUES (
    'whatsapp_recibo_template',
    'Hola {nombre}! Confirmamos tu pago de ${monto} por Factura #{factura_id} ({plan}). Fecha: {fecha}. Saldo pendiente: ${saldo_pendiente}. Gracias - Cable Santana.'
);

-- VERIFICACION:
-- SHOW COLUMNS FROM pagos LIKE 'bloqueado';
-- SHOW TABLES LIKE 'arqueos_caja';
