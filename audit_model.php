<?php
/**
 * audit_model.php
 *
 * Este archivo contiene las funciones para interactuar con la tabla 'auditoria'
 * de la base de datos, registrando acciones importantes del sistema.
 * Incluye la conexión a la base de datos a través de db_connection.php.
 */

require_once 'db_connection.php'; // Incluir el archivo de conexión a la base de datos

/**
 * Registra una acción en la tabla de auditoría.
 *
 * @param int|null $usuario_id ID del usuario que realizó la acción (puede ser null si no hay usuario logueado).
 * @param string $accion Descripción de la acción realizada (ej. 'Usuario creado', 'Pago registrado').
 * @param string|null $tabla_afectada Nombre de la tabla afectada.
 * @param int|null $registro_afectado_id ID del registro en la tabla afectada.
 * @param string|null $detalle_anterior Detalles del estado anterior del registro (JSON o texto).
 * @param string|null $detalle_nuevo Detalles del estado nuevo del registro (JSON o texto).
 * @param string|null $direccion_ip Dirección IP desde donde se realizó la acción.
 * @return int|false El ID del registro de auditoría insertado si es exitoso, o false en caso de error.
 */
function logAuditAction($usuario_id, $accion, $tabla_afectada = null, $registro_afectado_id = null, $detalle_anterior = null, $detalle_nuevo = null, $direccion_ip = null) {
    $conn = connectDB();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos para registrar auditoría.");
        return false;
    }

    // Obtener la IP del cliente si no se proporciona
    if ($direccion_ip === null) {
        $direccion_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    $stmt = $conn->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_afectado_id, detalle_anterior, detalle_nuevo, direccion_ip) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de auditoría: " . $conn->error);
        closeDB($conn);
        return false;
    }

    // Convertir detalles a JSON si son arrays, o mantener como null/string
    $detalle_anterior_json = is_array($detalle_anterior) ? json_encode($detalle_anterior) : $detalle_anterior;
    $detalle_nuevo_json = is_array($detalle_nuevo) ? json_encode($detalle_nuevo) : $detalle_nuevo;

    $stmt->bind_param("isissis", $usuario_id, $accion, $tabla_afectada, $registro_afectado_id, $detalle_anterior_json, $detalle_nuevo_json, $direccion_ip);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de auditoría: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Obtiene todos los registros de auditoría de la base de datos.
 *
 * @return array Un array de arrays asociativos con los datos de los registros de auditoría.
 */
function getAllAuditLogs() {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    // Se une con la tabla usuario para obtener el nombre de usuario asociado a la acción
    $sql = "SELECT a.*, u.nombre_usuario FROM auditoria a LEFT JOIN usuario u ON a.usuario_id = u.id ORDER BY a.fecha_accion DESC";
    $result = $conn->query($sql);

    $logs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    closeDB($conn);
    return $logs;
}

/**
 * Obtiene los registros de auditoría para un usuario específico.
 *
 * @param int $usuario_id El ID del usuario.
 * @return array Un array de arrays asociativos con los registros de auditoría del usuario.
 */
function getAuditLogsByUserId($usuario_id) {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT a.*, u.nombre_usuario FROM auditoria a JOIN usuario u ON a.usuario_id = u.id WHERE a.usuario_id = ? ORDER BY a.fecha_accion DESC");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de auditoría por usuario: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $logs;
}
?>
