<?php
/**
 * ajax_send_notifications.php
 *
 * Endpoint AJAX (POST) para envio de notificaciones WhatsApp a morosos.
 * Acciones: preview (lista quien recibira) o send (envia mensajes).
 * Protegido con sesion + rol admin/editor + CSRF.
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

// Verificar roles permitidos
$allowed_roles = ['administrador', 'editor'];
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowed_roles)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tiene permisos para esta accion.']);
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

require_once 'notificacion_model.php';
require_once 'audit_model.php';

$action = $_POST['action'] ?? '';

if ($action === 'preview') {
    // Retornar lista de clientes que serian notificados con el mensaje que recibirian
    $debtors = getDebtorsForNotification();
    $template = getWhatsAppTemplate();

    $preview = [];
    foreach ($debtors as $debtor) {
        $message = buildNotificationMessage($debtor, $template);
        $preview[] = [
            'cliente_id' => $debtor['cliente_id'],
            'nombre' => $debtor['nombre'] . ' ' . $debtor['apellido'],
            'telefono' => $debtor['telefono'],
            'facturas' => $debtor['facturas_pendientes'],
            'deuda' => number_format((float) $debtor['total_deuda'], 2),
            'mensaje' => $message
        ];
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $preview, 'template' => $template]);
    exit;
}

if ($action === 'send') {
    $debtors = getDebtorsForNotification();
    $template = getWhatsAppTemplate();

    $results = [
        'sent' => 0,
        'failed' => 0,
        'details' => []
    ];

    foreach ($debtors as $debtor) {
        $message = buildNotificationMessage($debtor, $template);
        $factura_ids = explode(',', $debtor['factura_ids']);
        $first_factura_id = (int) $factura_ids[0];

        // Enviar mensaje
        $sendResult = sendWhatsAppMessage($debtor['telefono'], $debtor['whatsapp_apikey'], $message);

        $estado = $sendResult['success'] ? 'enviado' : 'fallido';
        $error = $sendResult['error'];

        // Registrar en tabla notificaciones
        logNotification(
            (int) $debtor['cliente_id'],
            'whatsapp',
            $message,
            $estado,
            $error,
            $first_factura_id
        );

        if ($sendResult['success']) {
            $results['sent']++;
        } else {
            $results['failed']++;
        }

        $results['details'][] = [
            'nombre' => $debtor['nombre'] . ' ' . $debtor['apellido'],
            'telefono' => $debtor['telefono'],
            'estado' => $estado,
            'error' => $error
        ];

        // Rate limit: esperar 2 segundos entre envios (requisito de CallMeBot)
        if (next($debtors) !== false) {
            sleep(2);
        }
    }

    // Registrar en auditoria
    logAuditAction(
        (int) $_SESSION['user_id'],
        'Envio masivo de notificaciones WhatsApp',
        'notificaciones',
        null,
        null,
        [
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'total' => count($debtors)
        ]
    );

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

if ($action === 'update_template') {
    // Solo admin puede actualizar template
    if ($_SESSION['rol'] !== 'administrador') {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Solo administradores pueden editar el template.']);
        exit;
    }

    $new_template = trim($_POST['template'] ?? '');
    if (empty($new_template)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'El template no puede estar vacio.']);
        exit;
    }

    $old_template = getWhatsAppTemplate();
    $updated = updateWhatsAppTemplate($new_template);

    if ($updated) {
        logAuditAction(
            (int) $_SESSION['user_id'],
            'Template WhatsApp actualizado',
            'configuracion',
            null,
            $old_template,
            $new_template
        );
    }

    ob_end_clean();
    echo json_encode(['success' => $updated]);
    exit;
}

ob_end_clean();
echo json_encode(['success' => false, 'error' => 'Accion no reconocida.']);
