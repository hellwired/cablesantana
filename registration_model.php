<?php
/**
 * registration_model.php
 *
 * Handles the logic for public client registration.
 */

require_once 'db_connection.php';
require_once 'user_model.php';
require_once 'payment_model.php';
require_once 'plan_model.php';

/**
 * Registers a new client, creates a user account, subscription, and initial invoice.
 * This function uses a transaction to ensure data integrity.
 *
 * @param array $data An associative array containing all necessary data:
 *              ['nombre', 'apellido', 'dni', 'direccion', 'correo_electronico', 'contrasena', 'plan_id']
 * @return int|false The ID of the newly created invoice on success, or false on failure.
 */
function registerClient(array $data) {
    $conn = connectDB();
    if (!$conn) {
        error_log("Failed to connect to DB in registerClient");
        return false;
    }

    $conn->begin_transaction();

    try {
        // 1. Create the Client
        $stmt_client = $conn->prepare("INSERT INTO clientes (nombre, apellido, dni, direccion, correo_electronico) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt_client) {
            throw new Exception("Failed to prepare client statement: " . $conn->error);
        }
        $stmt_client->bind_param("sssss", $data['nombre'], $data['apellido'], $data['dni'], $data['direccion'], $data['correo_electronico']);
        if (!$stmt_client->execute()) {
            throw new Exception("Failed to create client: " . $stmt_client->error);
        }
        $cliente_id = $stmt_client->insert_id;
        $stmt_client->close();

        // 2. Create the User
        // The createUser function from user_model.php already hashes the password.
        // We use the email as the username for public-facing accounts.
        $user_id = createUser($data['correo_electronico'], $data['contrasena'], 'cliente', $data['correo_electronico'], $cliente_id);
        if (!$user_id) {
            // createUser logs its own errors, but we need to throw to trigger rollback.
            throw new Exception("Failed to create user account.");
        }

        // 3. Get Plan Details
        $plan = getPlanById($data['plan_id']);
        if (!$plan) {
            throw new Exception("Invalid Plan ID: " . $data['plan_id']);
        }

        // 4. Create the Subscription
        $current_date = date('Y-m-d');
        // Align next billing date to the 1st of the next month
        $next_month_date = date('Y-m-01', strtotime('+1 month'));
        
        $suscripcion_id = createSubscription($cliente_id, $data['plan_id'], $current_date, $next_month_date);
        if (!$suscripcion_id) {
            throw new Exception("Failed to create subscription.");
        }

        // 5. Create the initial Invoice (Prorated)
        // Calculate proration
        $days_in_month = (int)date('t');
        $current_day = (int)date('j');
        $days_remaining = $days_in_month - $current_day + 1;
        $daily_rate = $plan['precio_mensual'] / $days_in_month;
        $prorated_amount = round($daily_rate * $days_remaining, 2);

        // If registering on the 1st, full amount. If last day, 1 day amount.
        $invoice_id = createInvoice($suscripcion_id, $cliente_id, $prorated_amount, $current_date, $next_month_date, 'pendiente');
        if (!$invoice_id) {
            throw new Exception("Failed to create initial invoice.");
        }

        // If all went well, commit the transaction
        $conn->commit();
        closeDB($conn);

        return $invoice_id;

    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $conn->rollback();
        error_log("Client registration failed: " . $e->getMessage());
        closeDB($conn);
        return false;
    }
}
?>