-- =============================================================
-- reset_db.sql
-- CableColor: Reset de datos (mantiene tabla usuarios intacta)
--
-- Borra TODOS los datos de clientes, pagos, facturas, etc.
-- NO toca las tablas usuarios ni planes
-- Restaura datos base: configuracion, metodos_pago
--
-- Ejecutar UNA SOLA VEZ. Accion IRREVERSIBLE.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar datos transaccionales
TRUNCATE TABLE `arqueos_caja`;
TRUNCATE TABLE `auditoria`;
TRUNCATE TABLE `notificaciones`;
TRUNCATE TABLE `pagos`;
TRUNCATE TABLE `facturas`;
TRUNCATE TABLE `suscripcion_cupon`;
TRUNCATE TABLE `suscripciones`;
TRUNCATE TABLE `deudas`;
TRUNCATE TABLE `clientes`;
TRUNCATE TABLE `cupones`;

-- Limpiar configuracion y metodos_pago para restaurar desde cero
TRUNCATE TABLE `configuracion`;
TRUNCATE TABLE `metodos_pago`;
TRUNCATE TABLE `metodos_pago_archivado`;

-- -------------------------------------------------------
-- Restaurar metodos_pago base
-- -------------------------------------------------------
INSERT INTO `metodos_pago` (`id`, `nombre`) VALUES
(1, 'Efectivo'),
(2, 'Transferencia Bancaria'),
(3, 'Tarjeta de Débito'),
(4, 'Tarjeta de Crédito'),
(5, 'Cheque');

-- -------------------------------------------------------
-- Restaurar configuracion base
-- -------------------------------------------------------
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('whatsapp_template',
 'Hola {nombre}! Tenes {facturas} factura(s) pendiente(s) por un total de ${monto}. Por favor regulariza tu situacion. Gracias - Cable Santana.',
 'Template de mensaje WhatsApp para morosos'),
('whatsapp_recibo_template',
 'Hola {nombre}! Confirmamos tu pago de ${monto} por Factura #{factura_id} ({plan}). Fecha: {fecha}. Saldo pendiente: ${saldo_pendiente}. Gracias - Cable Santana. Ver recibo: {link_recibo}',
 'Template de recibo digital post-cobro');

SET FOREIGN_KEY_CHECKS = 1;

-- Verificacion
SELECT 'RESET COMPLETADO' AS resultado;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'cable_santana'
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;
