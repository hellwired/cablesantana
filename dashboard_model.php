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
function getTotalClients()
{
    $conn = connectDB();
    if (!$conn)
        return 0;

    $sql = "SELECT COUNT(id) as total FROM clientes";
    $result = $conn->query($sql);
    $total = 0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (int) $total;
}

/**
 * Obtiene el número total de usuarios activos.
 *
 * @return int El número total de usuarios.
 */
function getTotalUsers()
{
    $conn = connectDB();
    if (!$conn)
        return 0;

    $sql = "SELECT COUNT(id) as total FROM usuarios WHERE activo = TRUE";
    $result = $conn->query($sql);
    $total = 0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (int) $total;
}

/**
 * Obtiene la suma total de todas las deudas pendientes.
 *
 * @return float El monto total de la deuda pendiente.
 */
function getTotalPendingDebt()
{
    $conn = connectDB();
    if (!$conn)
        return 0.0;

    // Suma de facturas pendientes o vencidas de los últimos 2 meses
    $sql = "SELECT SUM(monto) as total FROM facturas 
            WHERE estado IN ('pendiente', 'vencida') 
            AND fecha_vencimiento >= DATE_SUB(NOW(), INTERVAL 2 MONTH)";

    $result = $conn->query($sql);
    $total = 0.0;
    if ($result) {
        $total = $result->fetch_assoc()['total'];
    }
    closeDB($conn);
    return (float) $total;
}

/**
 * Obtiene la suma total de los pagos recibidos en el mes actual.
 *
 * @return float El monto total de los pagos de este mes.
 */
function getTotalPaymentsThisMonth()
{
    $conn = connectDB();
    if (!$conn)
        return 0.0;

    $first_day_of_month = date('Y-m-01 00:00:00');
    $last_day_of_month = date('Y-m-t 23:59:59');

    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos WHERE fecha_pago BETWEEN ? AND ?");
    $stmt->bind_param("ss", $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = 0.0;
    if ($result) {
        $total = (float)$result->fetch_assoc()['total'];
    }
    $stmt->close();
    closeDB($conn);
    return (float) $total;
}

/**
 * Obtiene los últimos pagos registrados.
 *
 * @param int $limit El número de pagos a obtener.
 * @return array Una lista de los pagos más recientes.
 */
function getRecentPayments($limit = 5)
{
    $conn = connectDB();
    if (!$conn)
        return [];

    $sql = "SELECT p.*, c.nombre, c.apellido, c.fecha_registro FROM pagos p JOIN facturas f ON p.factura_id = f.id JOIN clientes c ON f.cliente_id = c.id ORDER BY p.fecha_pago DESC LIMIT ?";
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
function getRecentClients($limit = 5)
{
    $conn = connectDB();
    if (!$conn)
        return [];

    $sql = "SELECT * FROM clientes ORDER BY fecha_registro DESC LIMIT ?";
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
function getOverdueClients()
{
    $conn = connectDB();
    if (!$conn)
        return [];

    $today = date('Y-m-d');

    // Seleccionar usuarios que tienen deudas cuyo estado no es 'pagado' y la fecha de vencimiento ya pasó.
    // Se une con la tabla de usuarios para obtener el nombre del usuario.
    $sql = "SELECT u.nombre_usuario, d.id as deuda_id, d.concepto, d.monto_pendiente, d.fecha_vencimiento
            FROM deudas d
            JOIN usuarios u ON d.usuario_id = u.id
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

/**
 * Obtiene las facturas pendientes de pago (incluyendo las no vencidas).
 * Útil para ver qué cobros están próximos.
 *
 * @param int $limit Límite de resultados.
 * @return array Lista de facturas pendientes.
 */
function getPendingInvoices($limit = 5)
{
    $conn = connectDB();
    if (!$conn)
        return [];

    $sql = "SELECT f.id, f.monto, f.fecha_vencimiento, f.cliente_id, c.nombre, c.apellido 
            FROM facturas f 
            JOIN clientes c ON f.cliente_id = c.id 
            WHERE f.estado != 'pagada' 
            ORDER BY f.fecha_vencimiento ASC 
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $invoices;
}

/**
 * Calculate Monthly Recurring Revenue (MRR).
 * Sum of monthly price of all active subscriptions.
 */
function calculateMRR()
{
    $conn = connectDB();
    if (!$conn)
        return 0;

    $sql = "SELECT SUM(p.precio_mensual) as mrr 
            FROM suscripciones s 
            JOIN planes p ON s.plan_id = p.id 
            WHERE s.estado = 'activa'";

    $result = $conn->query($sql);
    $mrr = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $mrr = $row['mrr'] ?? 0;
    }
    closeDB($conn);
    return (float) $mrr;
}

/**
 * Calculate Churn Rate (Monthly).
 * Retrieves the value from the 'configuracion' table.
 */
function calculateChurnRate()
{
    $conn = connectDB();
    if (!$conn)
        return 5.0; // Default fallback

    $sql = "SELECT valor FROM configuracion WHERE clave = 'churn_rate'";
    $result = $conn->query($sql);

    $churn_rate = 5.0; // Default
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $churn_rate = (float) $row['valor'];
    }

    closeDB($conn);
    return $churn_rate;
}

/**
 * Update the Churn Rate configuration.
 * 
 * @param float $new_rate The new churn rate percentage.
 * @return bool True on success, False on failure.
 */
function updateChurnRate($new_rate)
{
    $conn = connectDB();
    if (!$conn)
        return false;

    $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'churn_rate'");
    $stmt->bind_param("s", $new_rate); // Storing as string/text in DB

    $success = $stmt->execute();

    $stmt->close();
    closeDB($conn);
    return $success;
}

/**
 * Calculate Lifetime Value (LTV).
 * ARPU / Churn Rate
 */
function calculateLTV()
{
    $mrr = calculateMRR();
    $conn = connectDB();
    $active_count = $conn->query("SELECT COUNT(*) as count FROM suscripciones WHERE estado = 'activa'")->fetch_assoc()['count'];
    closeDB($conn);

    if ($active_count == 0)
        return 0;

    $arpu = $mrr / $active_count;
    $churn_rate = calculateChurnRate(); // Percentage

    if ($churn_rate <= 0) {
        $churn_rate = 5; // Default 5% churn for estimation if 0
    }

    return ($arpu / ($churn_rate / 100));
}
?>