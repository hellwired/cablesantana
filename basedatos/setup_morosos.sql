-- Script de Configuración para Módulo de Morosos (1 Mes Vencido + Recargo)

-- 1. Crear Vista de Deudores (1 o más facturas pendientes/vencidas)
CREATE OR REPLACE VIEW vista_deudores AS
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
    COUNT(f.id) >= 1;

-- 2. Insertar Configuración de Recargo (si no existe)
-- Usamos INSERT IGNORE para no fallar si ya existe
INSERT IGNORE INTO configuracion (clave, valor, descripcion) 
VALUES ('recargo_mora', '0', 'Monto fijo de recargo para clientes con deuda');
