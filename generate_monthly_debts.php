<?php
/**
 * generate_monthly_debts.php
 *
 * Generates monthly invoices for all active subscriptions.
 * Should be run via Cron on the 1st of every month.
 */

require_once 'db_connection.php';
require_once 'payment_model.php';

echo "<pre>";
echo "Iniciando facturación mensual (" . date('Y-m-d') . ")...\n";

$conn = connectDB();
if (!$conn) die("Error de conexión DB");

// 1. Get active subscriptions
$sql = "SELECT s.id as suscripcion_id, s.cliente_id, s.plan_id, p.precio_mensual, p.nombre_plan, c.nombre, c.apellido 
        FROM suscripciones s 
        JOIN planes p ON s.plan_id = p.id 
        JOIN clientes c ON s.cliente_id = c.id 
        WHERE s.estado = 'activa'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $count = 0;
    $current_month = date('Y-m');
    $due_date = date('Y-m-05'); // Due on the 5th

    while ($sub = $result->fetch_assoc()) {
        // Check if already billed for this month (to prevent duplicates if script runs twice)
        // We check if there is an invoice for this subscription in the current month
        $check_sql = "SELECT id FROM facturas 
                      WHERE suscripcion_id = ? 
                      AND DATE_FORMAT(fecha_emision, '%Y-%m') = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("is", $sub['suscripcion_id'], $current_month);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows == 0) {
            // Create Invoice
            $invoice_id = createInvoice(
                $sub['suscripcion_id'],
                $sub['cliente_id'],
                $sub['precio_mensual'],
                date('Y-m-d'),
                $due_date,
                'pendiente'
            );

            // Create Debt Record (Legacy support, as the system uses 'deudas' table for tracking)
            // Ideally we should unify Facturas and Deudas, but for now we sync them.
            createDebt(
                $sub['cliente_id'], 
                "Factura #" . $invoice_id . " - " . $sub['nombre_plan'], 
                $sub['precio_mensual'], 
                $due_date
            );

            echo "Facturada suscripción ID {$sub['suscripcion_id']} ($ {$sub['precio_mensual']})\n";
            $count++;
        } else {
            echo "Suscripción ID {$sub['suscripcion_id']} ya facturada este mes.\n";
        }
        $stmt_check->close();
    }
    echo "Proceso finalizado. $count facturas generadas.\n";
} else {
    echo "No hay suscripciones activas.\n";
}

closeDB($conn);
echo "</pre>";
?>