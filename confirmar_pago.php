<?php
session_start();

require_once 'payment_model.php';
require_once 'classes/StripePaymentGateway.php';
use CableColor\StripePaymentGateway;

// 1. Validate Session ID
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_SANITIZE_STRING);
$factura_id = filter_input(INPUT_GET, 'factura_id', FILTER_VALIDATE_INT);

if (!$session_id || !$factura_id) {
    die("Datos de confirmación inválidos.");
}

// 2. Verify with Stripe
$gateway = new StripePaymentGateway();
if ($gateway->verifyPayment($session_id)) {
    
    // 3. Update Database
    $conn = connectDB();
    if (!$conn) {
        die("Error de conexión a la base de datos.");
    }
    $conn->begin_transaction();

    try {
        // Check if already paid to avoid duplicates
        $invoice = getInvoiceWithDetailsById($factura_id);
        if ($invoice['estado'] === 'pagada') {
             header('Location: gracias.php?factura_id=' . $factura_id);
             exit();
        }

        // Create Payment Record
        $payment_id = createPayment(
            $factura_id,
            $invoice['monto'],
            date('Y-m-d H:i:s'),
            'exitoso',
            3, // 3 = Stripe
            $session_id
        );

        if (!$payment_id) {
            throw new Exception("No se pudo registrar el pago.");
        }

        // Update Invoice
        $invoice_updated = updateInvoiceStatus($factura_id, 'pagada');

        if (!$invoice_updated) {
            throw new Exception("No se pudo actualizar el estado de la factura.");
        }

        $conn->commit();
        closeDB($conn);

        // Redirect to success
        header('Location: gracias.php?factura_id=' . $factura_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        closeDB($conn);
        die("Error al procesar el pago: " . $e->getMessage());
    }

} else {
    die("El pago no pudo ser verificado o fue cancelado.");
}
?>