<?php
/**
 * facturacion_model.php
 *
 * Modelo para la generacion masiva de facturas mensuales.
 * Permite generar facturas para todas las suscripciones activas
 * y consultar el estado de generacion del mes actual.
 */

require_once 'db_connection.php';

/**
 * Genera facturas mensuales para todas las suscripciones activas.
 * - Verifica si ya existe factura del mes actual para evitar duplicados
 * - Crea facturas con fecha_vencimiento = ultimo dia del mes
 * - Actualiza fecha_proximo_cobro de la suscripcion
 * - Marca facturas vencidas pasadas de 'pendiente' a 'vencida'
 * - Todo en una transaccion para atomicidad
 *
 * @return array Resumen: created, skipped, total_amount, errors, overdue_updated
 */
function generateMonthlyInvoices(): array
{
    $result = [
        'created' => 0,
        'skipped' => 0,
        'total_amount' => 0.0,
        'errors' => [],
        'overdue_updated' => 0
    ];

    $conn = connectDB();
    if (!$conn) {
        $result['errors'][] = 'No se pudo conectar a la base de datos.';
        return $result;
    }

    $conn->begin_transaction();

    try {
        // 1. Marcar facturas pasadas vencidas: pendiente -> vencida si ya vencieron
        $today = date('Y-m-d');
        $stmt_overdue = $conn->prepare(
            "UPDATE facturas SET estado = 'vencida' WHERE estado = 'pendiente' AND fecha_vencimiento < ?"
        );
        $stmt_overdue->bind_param("s", $today);
        $stmt_overdue->execute();
        $result['overdue_updated'] = $stmt_overdue->affected_rows;
        $stmt_overdue->close();

        // 2. Obtener suscripciones activas con datos del plan
        $sql = "SELECT s.id AS suscripcion_id, s.cliente_id, s.plan_id, p.precio_mensual
                FROM suscripciones s
                JOIN planes p ON s.plan_id = p.id
                WHERE s.estado = 'activa'";
        $res = $conn->query($sql);

        if (!$res) {
            throw new Exception("Error consultando suscripciones: " . $conn->error);
        }

        $current_month = date('Y-m');
        $fecha_emision = date('Y-m-d');
        $fecha_vencimiento = date('Y-m-t'); // ultimo dia del mes actual
        $next_month_first = date('Y-m-01', strtotime('+1 month'));

        while ($sub = $res->fetch_assoc()) {
            $suscripcion_id = (int) $sub['suscripcion_id'];
            $cliente_id = (int) $sub['cliente_id'];
            $monto = (float) $sub['precio_mensual'];

            // 3. Verificar si ya existe factura para este mes
            $stmt_check = $conn->prepare(
                "SELECT id FROM facturas
                 WHERE suscripcion_id = ? AND DATE_FORMAT(fecha_emision, '%Y-%m') = ?"
            );
            $stmt_check->bind_param("is", $suscripcion_id, $current_month);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result();

            if ($check_result->num_rows > 0) {
                $result['skipped']++;
                $stmt_check->close();
                continue;
            }
            $stmt_check->close();

            // 4. Crear factura
            $stmt_insert = $conn->prepare(
                "INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado)
                 VALUES (?, ?, ?, ?, ?, 'pendiente')"
            );
            $stmt_insert->bind_param("iidss", $suscripcion_id, $cliente_id, $monto, $fecha_emision, $fecha_vencimiento);

            if ($stmt_insert->execute()) {
                $result['created']++;
                $result['total_amount'] += $monto;
            } else {
                $result['errors'][] = "Error creando factura para suscripcion #$suscripcion_id: " . $stmt_insert->error;
            }
            $stmt_insert->close();

            // 5. Actualizar fecha_proximo_cobro de la suscripcion
            $stmt_update = $conn->prepare(
                "UPDATE suscripciones SET fecha_proximo_cobro = ? WHERE id = ?"
            );
            $stmt_update->bind_param("si", $next_month_first, $suscripcion_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $result['errors'][] = 'Error en transaccion: ' . $e->getMessage();
        error_log("Error en generateMonthlyInvoices: " . $e->getMessage());
    }

    closeDB($conn);
    return $result;
}

/**
 * Obtiene el estado de generacion de facturas del mes actual.
 * Retorna cuantas suscripciones activas hay vs cuantas ya tienen factura este mes.
 *
 * @return array {total_active: int, already_generated: int, pending: int}
 */
function getInvoiceGenerationStatus(): array
{
    $status = [
        'total_active' => 0,
        'already_generated' => 0,
        'pending' => 0
    ];

    $conn = connectDB();
    if (!$conn) return $status;

    // Total suscripciones activas
    $res1 = $conn->query("SELECT COUNT(*) AS total FROM suscripciones WHERE estado = 'activa'");
    if ($res1) {
        $status['total_active'] = (int) $res1->fetch_assoc()['total'];
    }

    // Cuantas ya tienen factura este mes
    $current_month = date('Y-m');
    $stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT s.id) AS generated
         FROM suscripciones s
         JOIN facturas f ON f.suscripcion_id = s.id
         WHERE s.estado = 'activa' AND DATE_FORMAT(f.fecha_emision, '%Y-%m') = ?"
    );
    $stmt->bind_param("s", $current_month);
    $stmt->execute();
    $res2 = $stmt->get_result();
    if ($res2) {
        $status['already_generated'] = (int) $res2->fetch_assoc()['generated'];
    }
    $stmt->close();

    $status['pending'] = $status['total_active'] - $status['already_generated'];

    closeDB($conn);
    return $status;
}
