<?php
/**
 * demo_flow.php
 * 
 * Script de demostración para visualizar el flujo completo del nuevo sistema.
 * 1. Registra un cliente (Prorrateo).
 * 2. Muestra la factura generada.
 * 3. Simula un pago exitoso.
 * 4. Muestra el impacto en las métricas.
 */

require_once 'registration_model.php';
require_once 'payment_model.php';
require_once 'dashboard_model.php';
require_once 'client_model.php';

echo "=== INICIO DE DEMOSTRACIÓN ===\n\n";

// 1. Limpieza previa (opcional, para no llenar de basura, o usamos datos únicos)
$unique_id = uniqid();
$email = "demo_$unique_id@example.com";
$dni = substr(time(), -8); // DNI pseudo-único

echo "1. Registrando nuevo cliente: Juan Demo ($email)...\n";
echo "   Fecha actual: " . date('Y-m-d') . "\n";
echo "   Días restantes en el mes: " . (date('t') - date('j') + 1) . "\n";

// Datos de prueba
$data = [
    'nombre' => 'Juan',
    'apellido' => 'Demo',
    'dni' => $dni,
    'direccion' => 'Calle Falsa 123',
    'correo_electronico' => $email,
    'contrasena' => 'password123',
    'plan_id' => 1 // Asumimos que existe el plan ID 1
];

// Registrar
$invoice_id = registerClient($data);

if ($invoice_id) {
    echo "   [OK] Cliente registrado. Factura inicial ID: $invoice_id\n";
    
    // 2. Ver detalles de la factura (Prorrateo)
    $invoice = getInvoiceWithDetailsById($invoice_id);
    echo "\n2. Detalles de la Factura Generada (Prorrateo):\n";
    echo "   Plan: " . $invoice['nombre_plan'] . "\n";
    echo "   Monto Mensual Plan: $" . number_format($invoice['precio_mensual'], 2) . "\n";
    echo "   Monto Facturado (Prorrateado): $" . number_format($invoice['monto'], 2) . "\n";
    echo "   Estado: " . $invoice['estado'] . "\n";

    // 3. Métricas ANTES del pago
    echo "\n3. Métricas ANTES del pago:\n";
    echo "   MRR: $" . number_format(calculateMRR(), 2) . "\n";
    echo "   Deuda Pendiente: $" . number_format(getTotalPendingDebt(), 2) . "\n";

    // 4. Simular Pago (Stripe Callback)
    echo "\n4. Simulando pago exitoso (Webhook de Stripe)...\n";
    // Simulamos lo que haría confirmar_pago.php
    $conn = connectDB();
    $conn->begin_transaction();
    createPayment($invoice_id, $invoice['monto'], date('Y-m-d H:i:s'), 'exitoso', 3, 'demo_stripe_sess_' . $unique_id);
    updateInvoiceStatus($invoice_id, 'pagada');
    $conn->commit();
    closeDB($conn);
    echo "   [OK] Pago registrado.\n";

    // 5. Métricas DESPUÉS del pago
    echo "\n5. Métricas DESPUÉS del pago:\n";
    echo "   MRR: $" . number_format(calculateMRR(), 2) . " (Debe mantenerse igual, ya que la suscripción ya estaba activa)\n";
    echo "   Deuda Pendiente: $" . number_format(getTotalPendingDebt(), 2) . " (Debe haber bajado)\n";
    echo "   LTV Estimado: $" . number_format(calculateLTV(), 2) . "\n";

} else {
    echo "   [ERROR] Falló el registro del cliente.\n";
}

echo "\n=== FIN DE DEMOSTRACIÓN ===\n";
?>
