-- Datos de prueba adicionales para Reactivación (> 3 meses)

-- Cliente con 4 facturas vencidas
INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado)
VALUES 
(3, 15, 25000.00, DATE_SUB(NOW(), INTERVAL 5 MONTH), DATE_SUB(NOW(), INTERVAL 5 MONTH), 'vencida'),
(3, 15, 25000.00, DATE_SUB(NOW(), INTERVAL 6 MONTH), DATE_SUB(NOW(), INTERVAL 6 MONTH), 'vencida');
-- (Sumado a las 2 anteriores, tendrá 4)

-- Cliente con 5 facturas vencidas (Otro ID: 17 ya tiene 3, agregamos 2 mas)
INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado)
VALUES 
(4, 17, 17000.00, DATE_SUB(NOW(), INTERVAL 5 MONTH), DATE_SUB(NOW(), INTERVAL 5 MONTH), 'vencida'),
(4, 17, 17000.00, DATE_SUB(NOW(), INTERVAL 6 MONTH), DATE_SUB(NOW(), INTERVAL 6 MONTH), 'vencida');
