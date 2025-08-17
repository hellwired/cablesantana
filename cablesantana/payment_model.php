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
 * Crea un nuevo registro de pago en la base de datos.
 *
 * @param int $cliente_id ID del cliente asociado al pago.
 * @param float $monto Monto del pago.
 * @param string $fecha_pago Fecha en que se realizó el pago (formato 'YYYY-MM-DD').
 * @param string|null $metodo_pago Método de pago.
 * @param string|null $referencia_pago Referencia o número de transacción del pago.
 * @param string|null $descripcion Descripción del pago.
 * @return int|false El ID del pago insertado si es exitoso, o false en caso de error.
 */
function createPayment($cliente_id, $monto, $fecha_pago, $metodo_pago = null, $referencia_pago = null, $descripcion = null) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO pagos (cliente_id, monto, fecha_pago, metodo_pago, referencia_pago, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de pago: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("idssss", $cliente_id, $monto, $fecha_pago, $metodo_pago, $referencia_pago, $descripcion);

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
function getPaymentById($id) {
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
function getAllPayments($search_term = '') {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT p.*, c.nombre, c.apellido FROM pagos p JOIN cliente c ON p.cliente_id = c.id";
    
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        // Añadir cláusula WHERE para buscar por nombre, apellido o DNI
        $sql .= " WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.dni LIKE ?";
    }
    
    $sql .= " ORDER BY p.fecha_pago DESC, p.fecha_registro DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($search_term)) {
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    }

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
 * Obtiene los pagos realizados por un cliente específico.
 *
 * @param int $cliente_id El ID del cliente.
 * @return array Un array de arrays asociativos con los pagos del cliente.
 */
function getPaymentsByClientId($cliente_id) {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT p.*, c.nombre, c.apellido FROM pagos p JOIN cliente c ON p.cliente_id = c.id WHERE p.cliente_id = ? ORDER BY p.fecha_pago DESC, p.fecha_registro DESC");
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
function updatePayment($id, $data) {
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
function deletePayment($id) {
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
function createDebt($usuario_id, $concepto, $monto_original, $fecha_vencimiento) {
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
function getDebtById($id) {
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
function getAllDebts() {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT d.*, u.nombre_usuario FROM deudas d JOIN usuario u ON d.usuario_id = u.id ORDER BY d.fecha_vencimiento ASC, d.fecha_creacion DESC";
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
function getDebtsByUserId($usuario_id) {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT d.*, u.nombre_usuario FROM deudas d JOIN usuario u ON d.usuario_id = u.id WHERE d.usuario_id = ? ORDER BY d.fecha_vencimiento ASC, d.fecha_creacion DESC");
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
function updateDebt($id, $data) {
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
function deleteDebt($id) {
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
function updateDebtStatus($debt_id) {
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
    $monto_pendiente = (float)$debt['monto_pendiente'];
    $fecha_vencimiento = $debt['fecha_vencimiento'];
    $new_status = $current_status;

    $today = date('Y-m-d');

    if ($monto_pendiente <= 0) {
        $new_status = 'pagado';
    } elseif ($monto_pendiente > 0 && $today > $fecha_vencimiento) {
        $new_status = 'vencido';
    } elseif ($monto_pendiente > 0 && $monto_pendiente < (float)$debt['monto_original'] && $today <= $fecha_vencimiento) {
        $new_status = 'parcialmente_pagado';
    } elseif ($monto_pendiente == (float)$debt['monto_original'] && $today <= $fecha_vencimiento) {
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
function processPaymentForDebt($usuario_id, $debt_id, $monto_pagado, $fecha_pago, $metodo_pago = null, $referencia_pago = null, $descripcion = null) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Iniciar una transacción para asegurar la atomicidad
    $conn->begin_transaction();

    try {
        // 1. Crear el registro de pago
        $payment_id = createPayment($usuario_id, $monto_pagado, $fecha_pago, $metodo_pago, $referencia_pago, $descripcion);
        if (!$payment_id) {
            throw new Exception("Error al registrar el pago.");
        }

        // 2. Obtener la deuda actual
        $debt = getDebtById($debt_id);
        if (!$debt) {
            throw new Exception("Deuda no encontrada.");
        }

        // 3. Calcular el nuevo monto pendiente
        $nuevo_monto_pendiente = (float)$debt['monto_pendiente'] - (float)$monto_pagado;
        if ($nuevo_monto_pendiente < 0) {
            $nuevo_monto_pendiente = 0; // Evitar montos negativos
        }

        // 4. Actualizar el monto pendiente de la deuda
        $updated_debt = updateDebt($debt_id, ['monto_pendiente' => $nuevo_monto_pendiente]);
        if (!$updated_debt) {
            throw new Exception("Error al actualizar el monto pendiente de la deuda.");
        }

        // 5. Actualizar el estado de la deuda (esto se hará automáticamente con updateDebtStatus)
        $status_updated = updateDebtStatus($debt_id);
        if (!$status_updated) {
             // Esto no debería hacer fallar la transacción si la actualización de estado es un "no-op"
             // Pero si es un error real, podríamos querer revertir.
             error_log("Advertencia: No se pudo actualizar el estado de la deuda " . $debt_id);
        }

        // Si todo fue bien, confirmar la transacción
        $conn->commit();
        closeDB($conn);
        return true;

    } catch (Exception $e) {
        // Si algo falla, revertir la transacción
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
function hasPendingDebts($cliente_id) {
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

?>