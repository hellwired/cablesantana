<?php
/**
 * ajax_generate_invoices.php
 *
 * Endpoint AJAX (POST) para la generacion masiva de facturas mensuales.
 * Protegido con sesion + rol admin + CSRF.
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticacion
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Inicie sesion.']);
    exit;
}

// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo administradores pueden generar facturas.']);
    exit;
}

// Verificar metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo no permitido.']);
    exit;
}

// Verificar CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalido.']);
    exit;
}

require_once 'facturacion_model.php';
require_once 'audit_model.php';

$action = $_POST['action'] ?? 'generate';

if ($action === 'status') {
    // Retornar estado actual
    $status = getInvoiceGenerationStatus();
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $status]);
    exit;
}

if ($action === 'generate') {
    // Generar facturas
    $result = generateMonthlyInvoices();

    // Registrar en auditoria
    logAuditAction(
        (int) $_SESSION['user_id'],
        'Generacion masiva de facturas',
        'facturas',
        null,
        null,
        [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'total_amount' => $result['total_amount'],
            'overdue_updated' => $result['overdue_updated'],
            'errors_count' => count($result['errors'])
        ]
    );

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

ob_end_clean();
echo json_encode(['success' => false, 'error' => 'Accion no reconocida.']);
