<?php
/**
 * generate_mp_preference.php
 *
 * Genera una preferencia de pago en Mercado Pago y devuelve el link de pago.
 * Se utiliza para generar el QR en el frontend.
 */

require_once 'db_connection.php'; // Para cargar variables de entorno y configuración

header('Content-Type: application/json');

// Verificar autenticación (opcional pero recomendado)
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$monto = $input['monto'] ?? 0;
$concepto = $input['concepto'] ?? 'Pago de Servicios';
$email_cliente = $input['email'] ?? 'test_user_123@testuser.com'; // Email dummy si no hay uno real

if ($monto <= 0) {
    echo json_encode(['error' => 'El monto debe ser mayor a 0']);
    exit;
}

// Obtener credenciales del entorno
$access_token = $_ENV['MP_ACCESS_TOKEN'] ?? '';

if (empty($access_token) || $access_token === 'YOUR_ACCESS_TOKEN_HERE') {
    echo json_encode(['error' => 'Credenciales de Mercado Pago no configuradas']);
    exit;
}

// Configurar preferencia
$preference_data = [
    "items" => [
        [
            "title" => $concepto,
            "quantity" => 1,
            "currency_id" => "ARS",
            "unit_price" => (float)$monto
        ]
    ],
    "payer" => [
        "email" => $email_cliente
    ],
    "back_urls" => [
        "success" => "https://cablesantana.com/success", // URLs dummy por ahora
        "failure" => "https://cablesantana.com/failure",
        "pending" => "https://cablesantana.com/pending"
    ],
    "auto_return" => "approved",
    "payment_methods" => [
        "excluded_payment_types" => [
            ["id" => "ticket"],
            ["id" => "atm"]
        ],
        "installments" => 1
    ],
    "statement_descriptor" => "CABLE SANTANA"
];

// Llamada a la API de Mercado Pago
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/checkout/preferences");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'Error de conexión con Mercado Pago: ' . $error]);
    exit;
}

if ($http_code !== 201 && $http_code !== 200) {
    $response_data = json_decode($response, true);
    echo json_encode(['error' => 'Error de Mercado Pago: ' . ($response_data['message'] ?? 'Desconocido')]);
    exit;
}

$mp_response = json_decode($response, true);

// Devolvemos el init_point (URL de pago) y el ID de preferencia
echo json_encode([
    'preference_id' => $mp_response['id'],
    'init_point' => $mp_response['init_point'], // Link para redirigir
    'qr_link' => $mp_response['init_point'] // Usaremos esto para generar el QR
]);
