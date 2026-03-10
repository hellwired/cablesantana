-- Script para crear una vista que identifique clientes con 2 o más facturas pendientes
-- Esto facilita la generación del informe de cortes de servicio.

CREATE OR REPLACE VIEW vista_cortes_servicio AS
SELECT 
    c.id AS cliente_id,
    c.dni,
    c.nombre,
    c.apellido,
    c.direccion,
    c.correo_electronico,
    COUNT(f.id) AS facturas_adeudadas,
    SUM(f.monto) AS total_deuda,
    MIN(f.fecha_vencimiento) AS fecha_vencimiento_mas_antigua
FROM 
    clientes c
JOIN 
    facturas f ON c.id = f.cliente_id
WHERE 
    f.estado IN ('pendiente', 'vencida')
GROUP BY 
    c.id
HAVING 
    COUNT(f.id) >= 2;
