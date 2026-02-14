-- Script de Configuración para Módulo de Reactivación (> 3 Meses Vencido)

-- 1. Crear Vista de Servicio Cortado (> 3 facturas pendientes)
CREATE OR REPLACE VIEW vista_servicio_cortado AS
SELECT 
    c.id AS cliente_id,
    c.dni,
    c.nombre,
    c.apellido,
    c.direccion,
    c.correo_electronico,
    COUNT(f.id) AS facturas_adeudadas,
    SUM(f.monto) AS total_deuda_acumulada,
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
    COUNT(f.id) > 3;
