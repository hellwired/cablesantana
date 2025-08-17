<?php
require_once 'payment_model.php';

header('Content-Type: application/json');

if (!isset($_GET['cliente_id']) || !is_numeric($_GET['cliente_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

$cliente_id = (int)$_GET['cliente_id'];

// La función hasPendingDebts devuelve true si hay deudas pendientes, y false si no las hay (o en caso de error).
$has_debts = hasPendingDebts($cliente_id);

// Si NO tiene deudas pendientes, el cliente está al día.
if (!$has_debts) {
    echo json_encode(['status' => 'al_dia']);
} else {
    echo json_encode(['status' => 'con_deuda']);
}
?>