<?php
/**
 * plan_model.php
 *
 * Este archivo contiene las funciones para interactuar con la tabla 'planes'
 * de la base de datos.
 */

require_once 'db_connection.php';

/**
 * Obtiene todos los planes activos de la base de datos.
 *
 * @return array Un array de arrays asociativos con los datos de los planes.
 */
function getAllPlans() {
    $conn = connectDB();
    if (!$conn) {
        error_log("Error de conexión en getAllPlans");
        return [];
    }

    $sql = "SELECT id, nombre_plan, precio_mensual, descripcion, tipo_facturacion, activo, fecha_creacion FROM planes WHERE activo = 1 ORDER BY nombre_plan";
    $result = $conn->query($sql);

    // Check for query errors
    if ($result === false) {
        error_log("Error en la consulta getAllPlans: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $num_rows = $result->num_rows;
    // die("DEBUG: La consulta encontró {$num_rows} planes."); // Optional: uncomment to see the count

    $plans = [];
    if ($num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    closeDB($conn);
    return $plans;
}

/**
 * Obtiene un plan por su ID.
 *
 * @param int $id El ID del plan.
 * @return array|null Un array asociativo con los datos del plan, o null si no se encuentra.
 */
function getPlanById($id) {
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM planes WHERE id = ?");
    if (!$stmt) {
        error_log("Error preparing get plan by ID query: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $plan = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $plan;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Crea un nuevo plan en la base de datos.
 *
 * @param string $nombre_plan Nombre del plan.
 * @param string $descripcion Descripción del plan.
 * @param float $precio_mensual Precio mensual del plan.
 * @param string $tipo_facturacion Tipo de facturación (fija, variable).
 * @param bool $activo Si el plan está activo o no.
 * @return int|false El ID del plan insertado si es exitoso, o false en caso de error.
 */
function createPlan($nombre_plan, $descripcion, $precio_mensual, $tipo_facturacion, $activo) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO planes (nombre_plan, descripcion, precio_mensual, tipo_facturacion, activo) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de plan: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("ssdsi", $nombre_plan, $descripcion, $precio_mensual, $tipo_facturacion, $activo);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $last_id;
    } else {
        error_log("Error al ejecutar la inserción de plan: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Actualiza la información de un plan existente.
 *
 * @param int $id El ID del plan a actualizar.
 * @param array $data Un array asociativo con los campos a actualizar.
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updatePlan($id, $data) {
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
        return false; // No hay datos para actualizar
    }

    $sql = "UPDATE planes SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de actualización de plan: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $params[] = $id;
    $types .= 'i';

    // Create an array of references for bind_param
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    // Prepend the types string to the references array
    array_unshift($refs, $types);

    call_user_func_array([$stmt, 'bind_param'], $refs);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);
        return $affected_rows > 0;
    } else {
        error_log("Error al ejecutar la actualización de plan: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Elimina un plan por su ID.
 *
 * @param int $id El ID del plan a eliminar.
 * @return bool True si la eliminación fue exitosa, false en caso contrario.
 */
function deletePlan($id) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM planes WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de eliminación de plan: " . $conn->error);
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
        error_log("Error al ejecutar la eliminación de plan: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

?>