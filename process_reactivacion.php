<?php
/**
 * process_reactivacion.php
 *
 * Procesa la reactivación de un servicio cortado.
 * Realiza una transacción cobrando:
 * 1. Las 2 facturas pendientes más recientes.
 * 2. Una nueva factura generada por el Recargo de Reactivación.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar roles permitidos (visor también puede reactivar en campo)
$allowed_roles = ['administrador', 'editor', 'visor'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: dashboard.php');
    exit();
}

require_once 'db_connection.php';
require_once 'reactivacion_model.php';
require_once 'audit_model.php'; // Para registrar la acción

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {
    $clienteId = (int) $_POST['cliente_id'];
    $userId = $_SESSION['user_id'];

    // Obtener información del cliente y su deuda
    $clients = getCutoffClients(); // Podríamos optimizar para buscar 1, pero esto sirve
    $foundClient = null;
    foreach ($clients as $c) {
        if ($c['cliente_id'] == $clienteId) {
            $foundClient = $c;
            break;
        }
    }

    if (!$foundClient) {
        die("Error: Cliente no encontrado o no cumple requisitos de corte.");
    }

    // Calcular montos
    $costInfo = calculateReactivationCost($clienteId, $foundClient['facturas_adeudadas']);
    $montoFacturas = $costInfo['monto_facturas'];
    $montoRecargo = $costInfo['recargo_total'];

    // Método de pago: Asumimos 'Efectivo' (ID 1) o buscamos dinámicamente.
    // Para simplificar en este script standalone, usaremos ID 1 (ajustar según DB real).
    $metodo_pago_id = 1;

    $conn = connectDB();
    $conn->begin_transaction();

    try {
        // PASO 1: Generar Factura por Recargo (si aplica)
        $facturaRecargoId = 0;
        if ($montoRecargo > 0) {
            // Asumimos que existe una suscripción activa (tomamos la última usada)
            // O usamos un ID genérico. Buscamos la suscripción del cliente.
            $stmtSub = $conn->prepare("SELECT id FROM suscripciones WHERE cliente_id = ? LIMIT 1");
            $stmtSub->bind_param("i", $clienteId);
            $stmtSub->execute();
            $resSub = $stmtSub->get_result();
            $subData = $resSub->fetch_assoc();
            $suscripcionId = $subData['id'] ?? 0; // Si no hay, usar 0 o manejar error
            $stmtSub->close();

            // Insertar Factura de Recargo
            $sqlInsFac = "INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado) VALUES (?, ?, ?, NOW(), NOW(), 'pendiente')";
            $stmtIns = $conn->prepare($sqlInsFac);
            $stmtIns->bind_param("iid", $suscripcionId, $clienteId, $montoRecargo);
            $stmtIns->execute();
            $facturaRecargoId = $stmtIns->insert_id;
            $stmtIns->close();

            // Pagar inmediatamente la factura de recargo
            $sqlPayRec = "INSERT INTO pagos (factura_id, monto, estado, metodo_pago_id, descripcion) VALUES (?, ?, 'exitoso', ?, 'Pago Recargo Reactivacion')";
            $stmtPay = $conn->prepare($sqlPayRec);
            $stmtPay->bind_param("idi", $facturaRecargoId, $montoRecargo, $metodo_pago_id);
            $stmtPay->execute();
            $thisPayId = $stmtPay->insert_id;
            $stmtPay->close();

            // Actualizar estado factura recargo
            $conn->query("UPDATE facturas SET estado = 'pagada', fecha_pago = NOW() WHERE id = $facturaRecargoId");

            // Audit
            logAuditAction($userId, 'Pago Recargo Reactivación', 'pagos', $thisPayId, null, "Cliente: $clienteId, Monto: $montoRecargo");
        }

        // PASO 2: Pagar las dos facturas más recientes
        // Buscar las 2 más recientes pendientes
        $sqlRecent = "SELECT id, monto FROM facturas WHERE cliente_id = ? AND estado IN ('pendiente', 'vencida') ORDER BY fecha_vencimiento DESC LIMIT 2";
        $stmtRec = $conn->prepare($sqlRecent);
        $stmtRec->bind_param("i", $clienteId);
        $stmtRec->execute();
        $resRec = $stmtRec->get_result();

        while ($inv = $resRec->fetch_assoc()) {
            $invId = $inv['id'];
            $invMonto = $inv['monto'];

            // Insertar Pago
            $sqlPay = "INSERT INTO pagos (factura_id, monto, estado, metodo_pago_id, descripcion) VALUES (?, ?, 'exitoso', ?, 'Pago Parcial Reactivacion')";
            $stmtP = $conn->prepare($sqlPay);
            $stmtP->bind_param("idi", $invId, $invMonto, $metodo_pago_id);
            $stmtP->execute();
            $payId = $stmtP->insert_id;
            $stmtP->close();

            // Actualizar Factura
            $conn->query("UPDATE facturas SET estado = 'pagada', fecha_pago = NOW() WHERE id = $invId");

            // Audit
            logAuditAction($userId, 'Pago Factura Reactivación', 'pagos', $payId, null, "FacID: $invId");
        }
        $stmtRec->close();

        // PASO 3: Auditoría General de la Reactivación
        logAuditAction($userId, 'Reactivación de Servicio', 'clientes', $clienteId, "Servicio Cortado", "Servicio Reactivado");

        $conn->commit();
        $conn->close();

        // Redireccionar con éxito
        header("Location: reactivacion_ui.php?success=1&client_id=$clienteId");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        die("Error en la transacción: " . $e->getMessage());
    }

} else {
    // Si intentan entrar directo
    header('Location: reactivacion_ui.php');
    exit();
}
?>