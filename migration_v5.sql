-- =============================================================
-- migration_v5.sql
-- CableColor: Bloqueo de Pagos + Arqueo de Caja + Recibo Digital
-- Ejecutar UNA SOLA VEZ en el servidor de produccion.
-- =============================================================

-- PASO 1: Nuevas columnas en tabla pagos
-- referencia_pago y descripcion: estaban en el codigo PHP pero faltaban en el schema
-- bloqueado: DEFAULT 1 = los pagos nuevos quedan bloqueados al instante
-- registrado_por: quien registro el pago (necesario para arqueo por cobrador)

ALTER TABLE `pagos`
    ADD COLUMN `referencia_pago`   VARCHAR(255) DEFAULT NULL   AFTER `metodo_pago_id`,
    ADD COLUMN `descripcion`       TEXT         DEFAULT NULL   AFTER `referencia_pago`,
    ADD COLUMN `bloqueado`         TINYINT(1)   NOT NULL DEFAULT 1 AFTER `descripcion`,
    ADD COLUMN `registrado_por`    INT(11)      DEFAULT NULL   AFTER `bloqueado`,
    ADD COLUMN `motivo_desbloqueo` TEXT         DEFAULT NULL   AFTER `registrado_por`,
    ADD COLUMN `desbloqueado_por`  INT(11)      DEFAULT NULL   AFTER `motivo_desbloqueo`,
    ADD COLUMN `fecha_desbloqueo`  DATETIME     DEFAULT NULL   AFTER `desbloqueado_por`;

ALTER TABLE `pagos`
    ADD KEY `idx_registrado_por`  (`registrado_por`),
    ADD KEY `idx_desbloqueado_por` (`desbloqueado_por`);

-- PASO 2: Tabla arqueos_caja para arqueo de caja diario
CREATE TABLE IF NOT EXISTS `arqueos_caja` (
    `id`             INT(11)        NOT NULL AUTO_INCREMENT,
    `admin_id`       INT(11)        NOT NULL    COMMENT 'Administrador que realizo el arqueo',
    `visor_id`       INT(11)        DEFAULT NULL COMMENT 'Cobrador auditado (NULL = sin filtro)',
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

-- PASO 3: Template de recibo WhatsApp en tabla configuracion
-- INSERT IGNORE es idempotente: no falla si ya existe la clave
INSERT IGNORE INTO `configuracion` (`clave`, `valor`)
VALUES (
    'whatsapp_recibo_template',
    'Hola {nombre}! Confirmamos tu pago de ${monto} por Factura #{factura_id} ({plan}). Fecha: {fecha}. Saldo pendiente: ${saldo_pendiente}. Gracias - Cable Santana.'
);

-- VERIFICACION (ejecutar manualmente para confirmar):
-- SELECT COLUMN_NAME, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_NAME = 'pagos' AND TABLE_SCHEMA = DATABASE()
--   ORDER BY ORDINAL_POSITION;
-- SELECT * FROM configuracion WHERE clave = 'whatsapp_recibo_template';
-- SHOW CREATE TABLE arqueos_caja;
