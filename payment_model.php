<?php
/**
 * payment_model.php
 *
 * Este archivo contiene las funciones para interactuar con las tablas 'pagos' y 'deudas'
 * de la base de datos, realizando operaciones CRUD (Crear, Leer, Actualizar, Eliminar).
 * Incluye la conexión a la base de datos a través de db_connection.php.
 */

require_once 'db_connection.php'; // Incluir el archivo de conexión a la base de datos

// --- Funciones CRUD para la tabla 'pagos' ---

/**
 * Obtiene el ID del método de pago basado en su nombre.
 * Centraliza la lógica para evitar "números mágicos" en el código.
 *
 * @param string $nombre Nombre del método de pago.
 * @return int ID del método de pago (default: 1 - Efectivo).
 */
function getPaymentMethodId($nombre)
{
    $map = [
        'Efectivo' => 1,
        'Transferencia' => 2,
        'Mercado Pago' => 3,
        'Tarjeta de Débito' => 4,
        'Tarjeta de Crédito' => 5
    ];
    return $map[$nombre] ?? 1;
}

/**
 * Obtiene todos los métodos de pago disponibles en la base de datos.
 *
 * @return array Lista de métodos de pago con id y nombre.
 */
function getAllPaymentMethods() {
    $conn = connectDB();
    if (!$conn) return [];
    
    $methods = [];
    $result = $conn->query("SELECT * FROM metodos_pago ORDER BY id ASC");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $methods[] = $row;
        }
    }
    
    closeDB($conn);
    return $methods;
}

/**
 * Crea un nuevo registro de pago en la base de datos.
 *
 * @param int $factura_id ID de la factura asociada al pago.
 * @param float $monto Monto del pago.
 * @param string $fecha_pago Fecha en que se realizó el pago (formato 'YYYY-MM-DD').
 * @param string $estado Estado del pago (ej. 'exitoso', 'fallido').
 * @param int $metodo_pago_id ID del método de pago.
 * @param string|null $gateway_transaccion_id ID de la transacción en la pasarela de pago.
 * @return int|false El ID del pago insertado si es exitoso, o false en caso de error.
 */
function createPayment($factura_id, $monto, $fecha_pago, $estado, $metodo_pago_id, $referencia_pago = null, $descripcion = null, $gateway_transaccion_id = null)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO pagos (factura_id, monto, fecha_pago, estado, metodo_pago_id, referencia_pago, descripcion, gateway_transaccion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de pago: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("idssisss", $factura_id, $monto, $fecha_pago, $estado, $metodo_pago_id, $referencia_pago, $descripcion, $gateway_transaccion_id);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de pago: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Obtiene un registro de pago por su ID.
 *
 * @param int $id El ID del pago.
 * @return array|null Un array asociativo con los datos del pago, o null si no se encuentra.
 */
function getPaymentById($id)
{
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM pagos WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de pago por ID: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $payment;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Obtiene todos los pagos de la base de datos, con opción de búsqueda por nombre de cliente.
 *
 * @param string $search_term Término de búsqueda para el nombre o apellido del cliente.
 * @return array Un array de arrays asociativos con los datos de todos los pagos.
 */
function getAllPayments($search_term = '')
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }



    // Include payment method name and client id
    $sql = "SELECT p.*, c.nombre, c.apellido, mp.nombre as metodo_pago_nombre, f.cliente_id 
            FROM pagos p 
            JOIN facturas f ON p.factura_id = f.id 
            JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN metodos_pago mp ON p.metodo_pago_id = mp.id";

    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        // Añadir cláusula WHERE para buscar por nombre, apellido o DNI
        $sql .= " WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.dni LIKE ?";
    }

    $sql .= " ORDER BY p.fecha_pago DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error preparing getAllPayments query: " . $conn->error);
        closeDB($conn);
        return [];
    }

    if (!empty($search_term)) {
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    }

    if (!$stmt->execute()) {
         error_log("Error executing getAllPayments: " . $stmt->error);
         $stmt->close();
         closeDB($conn);
         return [];
    }
    
    $result = $stmt->get_result();

    $payments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }

    $stmt->close();
    closeDB($conn);
    return $payments;
}

/**
 * Obtiene los pagos realizados por un cliente específico.
 *
 * @param int $cliente_id El ID del cliente.
 * @return array Un array de arrays asociativos con los pagos del cliente.
 */
function getPaymentsByClientId($cliente_id)
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    // Join with metodos_pago
    $stmt = $conn->prepare("SELECT p.*, c.nombre, c.apellido, mp.nombre as metodo_pago_nombre 
                            FROM pagos p 
                            JOIN facturas f ON p.factura_id = f.id 
                            JOIN clientes c ON f.cliente_id = c.id 
                            LEFT JOIN metodos_pago mp ON p.metodo_pago_id = mp.id
                            WHERE f.cliente_id = ? ORDER BY p.fecha_pago DESC");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de pagos por cliente: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $payments;
}

/**
 * Actualiza la información de un pago existente.
 *
 * @param int $id El ID del pago a actualizar.
 * @param array $data Un array asociativo con los campos a actualizar.
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updatePayment($id, $data)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $set_clauses = [];
    $params = [];
    $types = '';

    foreach ($data as $key => $value) {
        $set_clauses[] = "$key = ?";
        $params[] = $value;
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    if (empty($set_clauses)) {
        closeDB($conn);
        return false;
    }

    $sql = "UPDATE pagos SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de actualización de pago: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $params[] = $id;
    $types .= 'i';

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error al ejecutar la actualización de pago: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Elimina un registro de pago por su ID.
 *
 * @param int $id El ID del pago a eliminar.
 * @return bool True si la eliminación fue exitosa, false en caso contrario.
 */
function deletePayment($id)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM pagos WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de eliminación de pago: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error al ejecutar la eliminación de pago: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

// --- Funciones CRUD para la tabla 'deudas' ---

/**
 * Crea un nuevo registro de deuda en la base de datos.
 *
 * @param int $usuario_id ID del usuario asociado a la deuda.
 * @param string $concepto Concepto de la deuda.
 * @param float $monto_original Monto original de la deuda.
 * @param string $fecha_vencimiento Fecha de vencimiento (formato 'YYYY-MM-DD').
 * @return int|false El ID de la deuda insertada si es exitoso, o false en caso de error.
 */
function createDebt($usuario_id, $concepto, $monto_original, $fecha_vencimiento)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Al crear una deuda, el monto_pendiente es igual al monto_original
    $monto_pendiente = $monto_original;
    $estado = 'pendiente';

    $stmt = $conn->prepare("INSERT INTO deudas (usuario_id, concepto, monto_original, monto_pendiente, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de deuda: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("isddss", $usuario_id, $concepto, $monto_original, $monto_pendiente, $fecha_vencimiento, $estado);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de deuda: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Obtiene un registro de deuda por su ID.
 *
 * @param int $id El ID de la deuda.
 * @return array|null Un array asociativo con los datos de la deuda, o null si no se encuentra.
 */
function getDebtById($id)
{
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM deudas WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de deuda por ID: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $debt = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $debt;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Obtiene todas las deudas de la base de datos.
 *
 * @return array Un array de arrays asociativos con los datos de todas las deudas.
 */
function getAllDebts()
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT d.*, u.nombre_usuario FROM deudas d JOIN usuarios u ON d.usuario_id = u.id ORDER BY d.fecha_vencimiento ASC, d.fecha_creacion DESC";
    $result = $conn->query($sql);

    $deudas = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $deudas[] = $row;
        }
    }
    closeDB($conn);
    return $deudas;
}

/**
 * Obtiene las deudas de un usuario específico.
 *
 * @param int $usuario_id El ID del usuario.
 * @return array Un array de arrays asociativos con las deudas del usuario.
 */
function getDebtsByUserId($usuario_id)
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT d.*, u.nombre_usuario FROM deudas d JOIN usuarios u ON d.usuario_id = u.id WHERE d.usuario_id = ? ORDER BY d.fecha_vencimiento ASC, d.fecha_creacion DESC");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de deudas por usuario: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $deudas = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $deudas[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $deudas;
}

/**
 * Actualiza la información de una deuda existente.
 *
 * @param int $id El ID de la deuda a actualizar.
 * @param array $data Un array asociativo con los campos a actualizar.
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updateDebt($id, $data)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $set_clauses = [];
    $params = [];
    $types = '';

    foreach ($data as $key => $value) {
        $set_clauses[] = "$key = ?";
        $params[] = $value;
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    if (empty($set_clauses)) {
        closeDB($conn);
        return false;
    }

    $sql = "UPDATE deudas SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de actualización de deuda: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $params[] = $id;
    $types .= 'i';

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error al ejecutar la actualización de deuda: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Elimina un registro de deuda por su ID.
 *
 * @param int $id El ID de la deuda a eliminar.
 * @return bool True si la eliminación fue exitosa, false en caso contrario.
 */
function deleteDebt($id)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM deudas WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de eliminación de deuda: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error al ejecutar la eliminación de deuda: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Actualiza el estado de una deuda basándose en su monto pendiente y fecha de vencimiento.
 * Debería llamarse después de cada operación que afecte el monto pendiente de una deuda.
 *
 * @param int $debt_id El ID de la deuda a actualizar.
 * @return bool True si el estado fue actualizado o no necesitaba actualización, false en caso de error.
 */
function updateDebtStatus($debt_id)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $debt = getDebtById($debt_id);
    if (!$debt) {
        closeDB($conn);
        return false; // Deuda no encontrada
    }

    $current_status = $debt['estado'];
    $monto_pendiente = (float) $debt['monto_pendiente'];
    $fecha_vencimiento = $debt['fecha_vencimiento'];
    $new_status = $current_status;

    $today = date('Y-m-d');

    if ($monto_pendiente <= 0) {
        $new_status = 'pagado';
    } elseif ($monto_pendiente > 0 && $today > $fecha_vencimiento) {
        $new_status = 'vencido';
    } elseif ($monto_pendiente > 0 && $monto_pendiente < (float) $debt['monto_original'] && $today <= $fecha_vencimiento) {
        $new_status = 'parcialmente_pagado';
    } elseif ($monto_pendiente == (float) $debt['monto_original'] && $today <= $fecha_vencimiento) {
        $new_status = 'pendiente';
    }

    if ($new_status !== $current_status) {
        $stmt = $conn->prepare("UPDATE deudas SET estado = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Error al preparar la consulta de actualización de estado de deuda: " . $conn->error);
            closeDB($conn);
            return false;
        }
        $stmt->bind_param("si", $new_status, $debt_id);
        if ($stmt->execute()) {
            $stmt->close();
            closeDB($conn);
            return true;
        } else {
            error_log("Error al ejecutar la actualización de estado de deuda: " . $stmt->error);
            $stmt->close();
            closeDB($conn);
            return false;
        }
    }
    closeDB($conn);
    return true; // No se necesitaba actualización de estado
}

/**
 * Registra un pago y actualiza el monto pendiente de la deuda asociada.
 *
 * @param int $usuario_id ID del usuario que realiza el pago.
 * @param int $debt_id ID de la deuda a la que se aplica el pago.
 * @param float $monto_pagado Monto que se está pagando.
 * @param string $fecha_pago Fecha en que se realizó el pago (formato 'YYYY-MM-DD').
 * @param string|null $metodo_pago Método de pago.
 * @param string|null $referencia_pago Referencia o número de transacción del pago.
 * @param string|null $descripcion Descripción del pago.
 * @return bool True si el pago y la actualización de deuda fueron exitosos, false en caso contrario.
 */
/**
 * Obtiene la factura pendiente más antigua de un cliente.
 *
 * @param int $cliente_id El ID del cliente.
 * @return array|null Datos de la factura o null si no hay pendientes.
 */
function getOldestUnpaidInvoice($cliente_id)
{
    $conn = connectDB();
    if (!$conn)
        return null;

    $stmt = $conn->prepare("SELECT * FROM facturas WHERE cliente_id = ? AND estado != 'pagada' ORDER BY fecha_vencimiento ASC LIMIT 1");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoice = null;
    if ($result->num_rows > 0) {
        $invoice = $result->fetch_assoc();
    }

    $stmt->close();
    closeDB($conn);
    return $invoice;
}

/**
 * Registra un pago y actualiza el monto pendiente de la deuda asociada.
 *
 * @param int $usuario_id ID del usuario que realiza el pago.
 * @param int $debt_id ID de la deuda a la que se aplica el pago.
 * @param float $monto_pagado Monto que se está pagando.
 * @param string $fecha_pago Fecha en que se realizó el pago (formato 'YYYY-MM-DD').
 * @param string|null $metodo_pago Método de pago.
 * @param string|null $referencia_pago Referencia o número de transacción del pago.
 * @param string|null $descripcion Descripción del pago.
 * @return bool True si el pago y la actualización de deuda fueron exitosos, false en caso contrario.
 */
function processPaymentForDebt($usuario_id, $debt_id, $monto_pagado, $fecha_pago, $metodo_pago = null, $referencia_pago = null, $descripcion = null)
{
    // Esta función necesita ser refactorizada para usar el nuevo sistema de facturas.
    // Por ahora, intentaremos buscar una factura asociada al cliente para mantener compatibilidad.

    $invoice = getOldestUnpaidInvoice($usuario_id); // Asumimos usuario_id == cliente_id
    if (!$invoice) {
        error_log("No se encontró factura pendiente para el cliente $usuario_id al procesar deuda $debt_id");
        return false;
    }

    $conn = connectDB();
    if (!$conn)
        return false;

    $conn->begin_transaction();

    try {
        // 1. Crear el registro de pago vinculado a la factura
        // Mapeo básico de método de pago a ID (ajustar según tu tabla metodos_pago real)
        $metodo_pago_id = 1; // Default: Efectivo
        if ($metodo_pago === 'Transferencia')
            $metodo_pago_id = 2;
        if ($metodo_pago === 'Mercado Pago')
            $metodo_pago_id = 3;

        $payment_id = createPayment($invoice['id'], $monto_pagado, $fecha_pago, 'exitoso', $metodo_pago_id, $referencia_pago);

        if (!$payment_id) {
            throw new Exception("Error al registrar el pago.");
        }

        // 2. Actualizar estado de la factura
        updateInvoiceStatus($invoice['id'], 'pagada');

        // 3. Actualizar la deuda (Legacy)
        $debt = getDebtById($debt_id);
        if ($debt) {
            $nuevo_monto_pendiente = (float) $debt['monto_pendiente'] - (float) $monto_pagado;
            if ($nuevo_monto_pendiente < 0)
                $nuevo_monto_pendiente = 0;

            updateDebt($debt_id, ['monto_pendiente' => $nuevo_monto_pendiente]);
            updateDebtStatus($debt_id);
        }

        $conn->commit();
        closeDB($conn);
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error en processPaymentForDebt: " . $e->getMessage());
        closeDB($conn);
        return false;
    }
}

/**
 * Verifica si un cliente tiene deudas pendientes.
 *
 * @param int $cliente_id El ID del cliente.
 * @return bool True si tiene deudas pendientes, false en caso contrario.
 */
function hasPendingDebts($cliente_id)
{
    $conn = connectDB();
    if (!$conn) {
        // En caso de error de conexión, es más seguro permitir el pago.
        return false;
    }

    // La columna 'usuario_id' en la tabla 'deudas' se usa para almacenar el 'cliente_id'.
    $stmt = $conn->prepare("SELECT id FROM deudas WHERE usuario_id = ? AND estado != 'pagado' LIMIT 1");
    if (!$stmt) {
        error_log("Error al preparar la consulta de verificación de deudas: " . $conn->error);
        closeDB($conn);
        return false; // Permitir pago en caso de error de consulta
    }

    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_pending = $result->num_rows > 0;

    $stmt->close();
    closeDB($conn);

    return $has_pending;
}

/**
 * Crea una nueva suscripción en la base de datos.
 *
 * @param int $cliente_id ID del cliente.
 * @param int $plan_id ID del plan.
 * @param string $fecha_inicio Fecha de inicio de la suscripción (formato 'YYYY-MM-DD').
 * @param string $fecha_proximo_cobro Fecha del próximo cobro (formato 'YYYY-MM-DD').
 * @param string $estado Estado de la suscripción (por defecto 'activa').
 * @return int|false El ID de la suscripción insertada si es exitoso, o false en caso de error.
 */
function createSubscription($cliente_id, $plan_id, $fecha_inicio, $fecha_proximo_cobro, $estado = 'activa')
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO suscripciones (cliente_id, plan_id, estado, fecha_inicio, fecha_proximo_cobro) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de suscripción: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("iisss", $cliente_id, $plan_id, $estado, $fecha_inicio, $fecha_proximo_cobro);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de suscripción: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Crea una nueva factura en la base de datos.
 *
 * @param int $suscripcion_id ID de la suscripción asociada.
 * @param int $cliente_id ID del cliente asociado.
 * @param float $monto Monto total de la factura.
 * @param string $fecha_emision Fecha de emisión de la factura (formato 'YYYY-MM-DD').
 * @param string $fecha_vencimiento Fecha de vencimiento de la factura (formato 'YYYY-MM-DD').
 * @param string $estado Estado de la factura (por defecto 'pendiente').
 * @return int|false El ID de la factura insertada si es exitoso, o false en caso de error.
 */
function createInvoice($suscripcion_id, $cliente_id, $monto, $fecha_emision, $fecha_vencimiento, $estado = 'pendiente')
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de factura: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("iidsss", $suscripcion_id, $cliente_id, $monto, $fecha_emision, $fecha_vencimiento, $estado);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de factura: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Ensures that a default plan with ID 1 exists. If not, it creates one.
 * This is a helper function for initial setup or when creating clients with default subscriptions.
 *
 * @return int|false The ID of the default plan (1) if it exists or was created, or false on error.
 */
/**
 * Obtiene una factura con detalles del cliente y plan.
 *
 * @param int $invoice_id El ID de la factura.
 * @return array|null Un array asociativo con los datos de la factura y detalles, o null si no se encuentra.
 */
/**
 * Actualiza el estado de una factura.
 *
 * @param int $invoice_id El ID de la factura a actualizar.
 * @param string $status El nuevo estado (ej. 'pagada', 'vencida').
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updateInvoiceStatus($invoice_id, $status)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE facturas SET estado = ? WHERE id = ?");
    if (!$stmt) {
        error_log("Error preparing updateInvoiceStatus query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("si", $status, $invoice_id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error executing updateInvoiceStatus: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Calcula el saldo pendiente de una factura (Monto Total - Pagos Realizados).
 *
 * @param int $invoice_id ID de la factura.
 * @return float El saldo pendiente.
 */
function getInvoiceBalance($invoice_id)
{
    $conn = connectDB();
    if (!$conn)
        return 0.0;

    // Obtener monto total de la factura
    $stmt = $conn->prepare("SELECT monto FROM facturas WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $stmt->close();
        closeDB($conn);
        return 0.0;
    }
    $invoice = $res->fetch_assoc();
    $total_amount = (float) $invoice['monto'];
    $stmt->close();

    // Sumar pagos realizados
    $stmt_payments = $conn->prepare("SELECT SUM(monto) as total_pagado FROM pagos WHERE factura_id = ? AND estado = 'exitoso'");
    $stmt_payments->bind_param("i", $invoice_id);
    $stmt_payments->execute();
    $res_payments = $stmt_payments->get_result();
    $row_payments = $res_payments->fetch_assoc();
    $total_paid = (float) $row_payments['total_pagado'];
    $stmt_payments->close();

    closeDB($conn);

    return max(0, $total_amount - $total_paid);
}


/**
 * Crea una factura por adelantado y registra su pago.
 * Identifica la próxima fecha de vencimiento basada en la última factura del cliente
 * y genera una nueva factura para el mes siguiente.
 *
 * @param int $cliente_id ID del cliente.
 * @param float $monto Monto del pago (debe coincidir con el precio del plan).
 * @param string $fecha_pago Fecha del pago.
 * @param int $metodo_pago_id ID del método de pago.
 * @param string|null $referencia_pago Referencia del pago.
 * @param string|null $descripcion Descripción opcional.
 * @return int|false ID del pago registrado o false en caso de error.
 */
function createAdvanceInvoice($cliente_id, $monto, $fecha_pago, $metodo_pago_id, $referencia_pago = null, $descripcion = null)
{
    $conn = connectDB();
    if (!$conn)
        return false;

    // 1. Obtener la última suscripción del cliente
    $stmt_sub = $conn->prepare("SELECT id, plan_id FROM suscripciones WHERE cliente_id = ? ORDER BY id DESC LIMIT 1");
    $stmt_sub->bind_param("i", $cliente_id);
    $stmt_sub->execute();
    $res_sub = $stmt_sub->get_result();

    if ($res_sub->num_rows === 0) {
        error_log("No se encontró suscripción para cliente $cliente_id al crear pago adelantado.");
        $stmt_sub->close();
        closeDB($conn);
        return false;
    }

    $subscription = $res_sub->fetch_assoc();
    $suscripcion_id = $subscription['id'];
    $stmt_sub->close();

    // 2. Determinar la última fecha de vencimiento registrada
    $stmt_last_inv = $conn->prepare("SELECT fecha_vencimiento FROM facturas WHERE cliente_id = ? ORDER BY fecha_vencimiento DESC LIMIT 1");
    $stmt_last_inv->bind_param("i", $cliente_id);
    $stmt_last_inv->execute();
    $res_last_inv = $stmt_last_inv->get_result();

    if ($res_last_inv->num_rows > 0) {
        $last_inv = $res_last_inv->fetch_assoc();
        $last_due_date = $last_inv['fecha_vencimiento'];

        // La nueva fecha de vencimiento será +1 mes desde la última
        $next_due_date = date('Y-m-d', strtotime($last_due_date . ' +1 month'));

        // Check for edge cases where +1 month lands on a non-existent day (e.g. Jan 31 + 1 month)
        // PHP's strtotime handles this but usually rolls over (Mar 3). 
        // For simplicity we trust strtotime or could enforce "day 10" policy if needed.
    } else {
        // Si no hay facturas previas (raro, pero posible en migración), usar la fecha actual + 1 mes
        $next_due_date = date('Y-m-d', strtotime('+1 month'));
    }
    $stmt_last_inv->close();

    // La fecha de emisión es hoy
    $fecha_emision = date('Y-m-d');

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 3. Crear la nueva factura
        // createInvoice($suscripcion_id, $cliente_id, $monto, $fecha_emision, $fecha_vencimiento, $estado = 'pendiente')
        // Usamos una función interna o refactorizamos createInvoice para no abrir/cerrar conex repetidamente.
        // Como createInvoice abre su propia conexión, debemos tener cuidado con la transacción.
        // SOLUCION: Copiaremos la lógica de inserción aquí para mantener la transacción atómica.

        $stmt_ins_inv = $conn->prepare("INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pagada')");
        $stmt_ins_inv->bind_param("iidss", $suscripcion_id, $cliente_id, $monto, $fecha_emision, $next_due_date);

        if (!$stmt_ins_inv->execute()) {
            throw new Exception("Error insertando factura adelantada: " . $stmt_ins_inv->error);
        }
        $new_invoice_id = $stmt_ins_inv->insert_id;
        $stmt_ins_inv->close();

        // 4. Registrar el pago vinculado a esta nueva factura
        $stmt_pay = $conn->prepare("INSERT INTO pagos (factura_id, monto, fecha_pago, estado, metodo_pago_id, gateway_transaccion_id) VALUES (?, ?, ?, 'exitoso', ?, ?)");
        $stmt_pay->bind_param("idsis", $new_invoice_id, $monto, $fecha_pago, $metodo_pago_id, $referencia_pago);

        if (!$stmt_pay->execute()) {
            throw new Exception("Error insertando pago adelantado: " . $stmt_pay->error);
        }
        $new_payment_id = $stmt_pay->insert_id;
        $stmt_pay->close();

        $conn->commit();
        closeDB($conn);
        return $new_payment_id;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transacción fallida en createAdvanceInvoice: " . $e->getMessage());
        closeDB($conn);
        return false;
    }
}

/**
 * Actualiza el estado de una factura basándose en su saldo pendiente.
 * Si el saldo es 0, la marca como 'pagada'.
 * Si tiene saldo, determina si es 'pendiente' o 'vencida' según la fecha.
 *
 * @param int $invoice_id El ID de la factura.
 * @return bool True si se actualizó correctamente.
 */
function updateInvoiceStatusBasedOnBalance($invoice_id)
{
    // 1. Obtener saldo pendiente real
    $balance = getInvoiceBalance($invoice_id);

    // 2. Si el saldo es 0 (o negativo por error), está pagada
    if ($balance <= 0.01) {
        return updateInvoiceStatus($invoice_id, 'pagada');
    }

    // 3. Si hay saldo, verificar si está vencida o pendiente
    $conn = connectDB();
    if (!$conn) return false;

    $stmt = $conn->prepare("SELECT fecha_vencimiento, estado FROM facturas WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        $stmt->close();
        closeDB($conn);
        return false;
    }

    $inv = $res->fetch_assoc();
    $stmt->close();
    closeDB($conn);

    $today = date('Y-m-d');
    $new_status = 'pendiente';

    if ($inv['fecha_vencimiento'] < $today) {
        $new_status = 'vencida';
    }

    // Solo actualizamos si el estado es diferente al actual (aunque updateInvoiceStatus lo haría igual, ahorramos lógica si extendemos)
    if ($inv['estado'] !== $new_status) {
         return updateInvoiceStatus($invoice_id, $new_status);
    }

    return true;
}
?>