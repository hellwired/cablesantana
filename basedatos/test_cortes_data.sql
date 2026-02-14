-- Script para Generar Deudores de Prueba (Cortes de Servicio)
-- Ejecuta esto para simular clientes que deben 2 o más facturas.

-- 1. Seleccionamos 2 clientes al azar para asignarles deudas antiguas.
-- (Ajusta los ID 15 y 17 por IDs que existan en tu tabla 'clientes' si estos no existen)

-- Caso 1: Cliente con 2 facturas vencidas (Debió pagar hace 2 y 3 meses)
INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado)
VALUES 
(3, 15, 25000.00, DATE_SUB(NOW(), INTERVAL 3 MONTH), DATE_SUB(NOW(), INTERVAL 3 MONTH), 'vencida'),
(3, 15, 25000.00, DATE_SUB(NOW(), INTERVAL 2 MONTH), DATE_SUB(NOW(), INTERVAL 2 MONTH), 'vencida');

-- Caso 2: Otro cliente con 3 facturas vencidas (Más crítico)
INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado)
VALUES 
(4, 17, 17000.00, DATE_SUB(NOW(), INTERVAL 4 MONTH), DATE_SUB(NOW(), INTERVAL 4 MONTH), 'vencida'),
(4, 17, 17000.00, DATE_SUB(NOW(), INTERVAL 3 MONTH), DATE_SUB(NOW(), INTERVAL 3 MONTH), 'vencida'),
(4, 17, 17000.00, DATE_SUB(NOW(), INTERVAL 2 MONTH), DATE_SUB(NOW(), INTERVAL 2 MONTH), 'vencida');

-- Verificación:
-- SELECT * FROM vista_cortes_servicio;
