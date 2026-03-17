<?php
/**
 * check_client_status.php
 *
 * Endpoint AJAX que retorna el estado de deuda de un cliente:
 * - Facturas pendientes/vencidas
 * - Estado: al_dia | en_riesgo | moroso
 * - Monto sugerido para el cobro
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db_connection.php';
require_once 'client_model.php';

$cliente_id = isset($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : 0;

if ($cliente_id <= 0) {
    ob_end_clean();
    echo json_encode(['error' => 'cliente_id inválido']);
    exit;
}

try {
    $conn = connectDB();
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $today = date('Y-m-d');

    // Obtener todas las facturas no pagadas del cliente
    $stmt = $conn->prepare(
        "SELECT id, monto, fecha_vencimiento, estado
         FROM facturas
         WHERE cliente_id = ? AND estado != 'pagada'
         ORDER BY fecha_vencimiento ASC"
    );
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = [];
    $invoice_count = 0;
    $overdue_count = 0;
    $total_debt = 0.0;

    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'id'               => (int) $row['id'],
            'monto'            => (float) $row['monto'],
            'fecha_vencimiento'=> $row['fecha_vencimiento'],
            'estado'           => $row['estado'],
        ];
        $invoice_count++;
        $total_debt += (float) $row['monto'];
        if ($row['fecha_vencimiento'] < $today) {
            $overdue_count++;
        }
    }
    $stmt->close();
    closeDB($conn);

    // Determinar estado y monto sugerido
    if ($invoice_count === 0) {
        // Cliente al día → monto sugerido = precio mensual de su plan
        $plan_price = getClientMonthlyPrice($cliente_id);
        $status = 'al_dia';
        $amount = $plan_price;
    } elseif ($overdue_count > 0) {
        $status = 'moroso';
        $amount = $total_debt;
    } else {
        $status = 'en_riesgo';
        $amount = $total_debt;
    }

    ob_end_clean();
    echo json_encode([
        'status'        => $status,
        'amount'        => $amount,
        'invoices'      => $invoices,
        'invoice_count' => $invoice_count,
        'overdue_count' => $overdue_count,
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
