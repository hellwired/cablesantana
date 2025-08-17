<?php
/**
 * generate_monthly_debts.php
 *
 * Este script genera las deudas mensuales para todos los clientes activos.
 * Debería ejecutarse una vez al mes (ej. el día 1 de cada mes) a través de un cron job.
 */

echo "<pre>"; // Usar <pre> para un output más legible en el navegador

require_once 'client_model.php';
require_once 'payment_model.php';

function generateDebts() {
    echo "Iniciando la generación de deudas mensuales...\n";

    $clients = getAllClients();
    if (empty($clients)) {
        echo "No se encontraron clientes para generar deudas.\n";
        return;
    }

    $current_month = date('Y-m');
    $due_date = date('Y-m-05'); // Fecha de vencimiento es el 5 de este mes
    $debts_created = 0;

    foreach ($clients as $client) {
        $client_id = $client['id'];
        $total_fee = (float)$client['cuotacable'] + (float)$client['cuotainternet'] + (float)$client['cuotacableinternet'];

        if ($total_fee <= 0) {
            echo "Cliente ID $client_id ({$client['nombre']} {$client['apellido']}) no tiene cuotas asignadas. Saltando...\n";
            continue;
        }

        // Verificar si ya existe una deuda para este cliente en el mes actual
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT id FROM deudas WHERE usuario_id = ? AND DATE_FORMAT(fecha_vencimiento, '%Y-%m') = ?");
        $stmt->bind_param("is", $client_id, $current_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            echo "El cliente ID $client_id ya tiene una deuda para el mes $current_month. Saltando...\n";
            closeDB($conn);
            continue;
        }
        closeDB($conn);

        // Crear la nueva deuda
        $concept = "Servicio Mensual " . date("F Y");
        $new_debt_id = createDebt($client_id, $concept, $total_fee, $due_date);

        if ($new_debt_id) {
            echo "Deuda creada para el cliente ID $client_id ({$client['nombre']} {$client['apellido']}) por un monto de ".$total_fee.". ID de deuda: $new_debt_id.\n";
            $debts_created++;
        } else {
            echo "ERROR: No se pudo crear la deuda para el cliente ID $client_id.\n";
        }
    }

    echo "\nProceso completado. Se crearon $debts_created deudas nuevas.\n";
}

// Ejecutar la función
generateDebts();

echo "</pre>";

?>