-- migration_notificaciones.sql
-- Script de migracion para agregar soporte de notificaciones WhatsApp a morosos.
-- Ejecutar manualmente en el servidor de base de datos.
-- Fecha: 2026-02-13

-- 1. Agregar campo telefono y apikey a clientes
ALTER TABLE clientes ADD COLUMN telefono VARCHAR(20) DEFAULT NULL AFTER correo_electronico;
ALTER TABLE clientes ADD COLUMN whatsapp_apikey VARCHAR(100) DEFAULT NULL AFTER telefono;

-- 2. Crear tabla de notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT(11) NOT NULL,
    tipo ENUM('whatsapp', 'email', 'sms') NOT NULL DEFAULT 'whatsapp',
    mensaje TEXT NOT NULL,
    estado ENUM('enviado', 'fallido', 'pendiente') DEFAULT 'pendiente',
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_detalle TEXT DEFAULT NULL,
    factura_id INT(11) DEFAULT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Agregar template de mensaje en configuracion
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('whatsapp_template', 'Hola {nombre}, le informamos que tiene {facturas} factura(s) pendiente(s) por un total de ${monto}. Por favor regularice su situacion. Cable Color Santa Ana.', 'Plantilla de mensaje WhatsApp para morosos')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
