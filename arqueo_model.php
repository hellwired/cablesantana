<?php
/**
 * arqueo_model.php
 *
 * Modelo para el Arqueo de Caja Diario.
 * Calcula el total de cobros en efectivo (metodo_pago_id=1)
 * realizados por un cobrador (visor) en una fecha determinada,
 * y permite registrar el resultado del arqueo para su historial.
 */

require_once 'db_connection.php';
require_once 'audit_model.php';

/**
 * Obtiene los usuarios con rol 'visor' activos (cobradores de campo).
 *
 * @return array Lista de visores con id y nombre_usuario.
 */
function getVisorUsers(): array
{
    $conn = connectDB();
    if (!$conn) return [];

    $stmt = $conn->prepare(
        "SELECT id, nombre_usuario FROM usuarios WHERE rol = 'visor' AND activo = 1 ORDER BY nombre_usuario ASC"
    );
    if (!$stmt) { closeDB($conn); return []; }
    $stmt->execute();
    $result = $stmt->get_result();
    $visores = [];
    while ($row = $result->fetch_assoc()) {
        $visores[] = $row;
    }
    $stmt->close();
    closeDB($conn);
    return $visores;
}

/**
 * Calcula el total de efectivo cobrado por un usuario en una fecha específica.
 * Solo cuenta pagos exitosos en efectivo (metodo_pago_id = 1)
 * registrados por ese usuario (campo registrado_por en pagos).
 *
 * @param int    $visor_id  ID del cobrador (usuario con rol visor).
 * @param string $fecha     Fecha en formato 'YYYY-MM-DD'.
 * @return array {
 *   total_esperado: float,
 *   cantidad_cobros: int,
 *   detalle: array con cada pago (pago_id, nombre, apellido, dni, monto, fecha_pago, factura_id)
 * }
 */
function calcularArqueoEfectivo(int $visor_id, string $fecha): array
{
    $conn = connectDB();
    $empty = ['total_esperado' => 0.0, 'cantidad_cobros' => 0, 'detalle' => []];
    if (!$conn) return $empty;

    $fecha_inicio = $fecha . ' 00:00:00';
    $fecha_fin    = $fecha . ' 23:59:59';

    $sql = "SELECT
                p.id AS pago_id,
                p.monto,
                p.fecha_pago,
                p.referencia_pago,
                c.nombre,
                c.apellido,
                c.dni,
                f.id AS factura_id
            FROM pagos p
            JOIN facturas f ON p.factura_id = f.id
            JOIN clientes c ON f.cliente_id = c.id
            WHERE p.metodo_pago_id = 1
              AND p.estado = 'exitoso'
              AND p.registrado_por = ?
              AND p.fecha_pago BETWEEN ? AND ?
            ORDER BY p.fecha_pago ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { closeDB($conn); return $empty; }
    $stmt->bind_param("iss", $visor_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();

    $detalle = [];
    $total   = 0.0;
    while ($row = $result->fetch_assoc()) {
        $detalle[] = $row;
        $total += (float)$row['monto'];
    }
    $stmt->close();
    closeDB($conn);

    return [
        'total_esperado'  => $total,
        'cantidad_cobros' => count($detalle),
        'detalle'         => $detalle
    ];
}

/**
 * Registra el resultado de un arqueo de caja en la tabla arqueos_caja.
 * Llama automáticamente a logAuditAction() tras un registro exitoso.
 *
 * @param int    $admin_id     ID del administrador que realiza el arqueo.
 * @param int    $visor_id     ID del cobrador auditado.
 * @param string $fecha        Fecha del período (YYYY-MM-DD).
 * @param float  $esperado     Total calculado por el sistema.
 * @param float  $real         Total físico recibido del cobrador.
 * @param string $estado       'cuadrado', 'faltante' o 'sobrante'.
 * @param float  $diferencia   Diferencia: monto_real - monto_esperado.
 * @param string $observaciones Notas adicionales del admin.
 * @return int|false ID del arqueo insertado, o false en caso de error.
 */
function registrarArqueo(int $admin_id, int $visor_id, string $fecha, float $esperado, float $real, string $estado, float $diferencia, string $observaciones = ''): int|false
{
    $conn = connectDB();
    if (!$conn) return false;

    $stmt = $conn->prepare(
        "INSERT INTO arqueos_caja (admin_id, visor_id, fecha_arqueo, monto_esperado, monto_real, diferencia, estado, observaciones)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) { closeDB($conn); return false; }
    $stmt->bind_param("iisdddss", $admin_id, $visor_id, $fecha, $esperado, $real, $diferencia, $estado, $observaciones);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        logAuditAction(
            $admin_id,
            'Arqueo de Caja',
            'arqueos_caja',
            $id,
            null,
            json_encode([
                'visor_id' => $visor_id,
                'fecha'    => $fecha,
                'esperado' => $esperado,
                'real'     => $real,
                'estado'   => $estado,
                'diferencia' => $diferencia
            ])
        );
        closeDB($conn);
        return $id;
    }

    $stmt->close();
    closeDB($conn);
    return false;
}

/**
 * Obtiene el historial de arqueos de caja con nombres de cobrador y admin.
 *
 * @param int $limit Cantidad máxima de registros a retornar.
 * @return array Lista de arqueos ordenados por fecha descendente.
 */
function getArqueosHistorial(int $limit = 200): array
{
    $conn = connectDB();
    if (!$conn) return [];

    $sql = "SELECT
                a.*,
                v.nombre_usuario AS cobrador_nombre,
                adm.nombre_usuario AS admin_nombre
            FROM arqueos_caja a
            LEFT JOIN usuarios v ON a.visor_id = v.id
            LEFT JOIN usuarios adm ON a.admin_id = adm.id
            ORDER BY a.fecha_arqueo DESC, a.fecha_registro DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { closeDB($conn); return []; }
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $arqueos = [];
    while ($row = $result->fetch_assoc()) {
        $arqueos[] = $row;
    }
    $stmt->close();
    closeDB($conn);
    return $arqueos;
}
