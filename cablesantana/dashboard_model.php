<?php
/**
 * dashboard_model.php
 *
 * Este archivo contiene las funciones para obtener las métricas clave
 * que se mostrarán en el dashboard.
 */

require_once 'db_connection.php';

/**
 * Obtiene el número total de clientes activos.
 *
 * @return int El número total de clientes.
 */
function getTotalClients() {
    $conn = connectDB();
    if (!$conn) return 0;

    $sql = "SELECT COUNT(id) as total FROM cliente";
    $result = $conn->query($sql);
    $total = 0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (int)$total;
}

/**
 * Obtiene el número total de usuarios activos.
 *
 * @return int El número total de usuarios.
 */
function getTotalUsers() {
    $conn = connectDB();
    if (!$conn) return 0;

    $sql = "SELECT COUNT(id) as total FROM usuario WHERE activo = TRUE";
    $result = $conn->query($sql);
    $total = 0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (int)$total;
}

/**
 * Obtiene la suma total de todas las deudas pendientes.
 *
 * @return float El monto total de la deuda pendiente.
 */
function getTotalPendingDebt() {
    $conn = connectDB();
    if (!$conn) return 0.0;

    $sql = "SELECT SUM(monto_pendiente) as total FROM deudas WHERE estado != 'pagado'";
    $result = $conn->query($sql);
    $total = 0.0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (float)$total;
}

/**
 * Obtiene la suma total de los pagos recibidos en el mes actual.
 *
 * @return float El monto total de los pagos de este mes.
 */
function getTotalPaymentsThisMonth() {
    $conn = connectDB();
    if (!$conn) return 0.0;

    $first_day_of_month = date('Y-m-01');
    $last_day_of_month = date('Y-m-t');

    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos WHERE fecha_pago BETWEEN ? AND ?");
    $stmt->bind_param("ss", $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = 0.0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    $stmt->close();
    closeDB($conn);
    return (float)$total;
}

/**
 * Obtiene los últimos pagos registrados.
 *
 * @param int $limit El número de pagos a obtener.
 * @return array Una lista de los pagos más recientes.
 */
function getRecentPayments($limit = 5) {
    $conn = connectDB();
    if (!$conn) return [];

    $sql = "SELECT p.*, c.nombre, c.apellido FROM pagos p JOIN cliente c ON p.cliente_id = c.id ORDER BY p.fecha_pago DESC, p.fecha_registro DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $payments;
}

/**
 * Obtiene los últimos clientes registrados.
 *
 * @param int $limit El número de clientes a obtener.
 * @return array Una lista de los clientes más recientes.
 */
function getRecentClients($limit = 5) {
    $conn = connectDB();
    if (!$conn) return [];

    $sql = "SELECT * FROM cliente ORDER BY fecha_registro DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $clients = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $clients;
}

/**
 * Obtiene una lista de usuarios con deudas vencidas.
 *
 * @return array Una lista de usuarios con sus deudas vencidas.
 */
function getOverdueClients() {
    $conn = connectDB();
    if (!$conn) return [];

    $today = date('Y-m-d');

    // Seleccionar usuarios que tienen deudas cuyo estado no es 'pagado' y la fecha de vencimiento ya pasó.
    // Se une con la tabla de usuarios para obtener el nombre del usuario.
    $sql = "SELECT u.nombre_usuario, d.id as deuda_id, d.concepto, d.monto_pendiente, d.fecha_vencimiento
            FROM deudas d
            JOIN usuario u ON d.usuario_id = u.id
            WHERE d.estado != 'pagado' AND d.fecha_vencimiento < ?
            ORDER BY d.fecha_vencimiento ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de clientes morosos: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $overdue_users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $overdue_users[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $overdue_users;
}

?>