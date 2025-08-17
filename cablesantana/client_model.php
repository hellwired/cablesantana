<?php
/**
 * client_model.php
 *
 * Este archivo contiene las funciones para interactuar con la tabla 'cliente'
 * de la base de datos, realizando operaciones CRUD (Crear, Leer, Actualizar, Eliminar).
 * Incluye la conexión a la base de datos a través de db_connection.php y el modelo de auditoría.
 */

require_once 'db_connection.php'; // Include the database connection file
require_once 'audit_model.php';   // Include the audit model

/**
 * Get the current user ID from the session.
 *
 * @return int|null The user ID if logged in, otherwise null.
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Create a new client in the database.
 *
 * @param string $dni Client's DNI.
 * @param string $nombre Client's first name.
 * @param string $apellido Client's last name.
 * @param string|null $direccion Client's address (optional).
 * @param string|null $correo_electronico Client's email (optional).
 * @param float $cuotacable Monthly cable fee.
 * @param float $cuotainternet Monthly internet fee.
 * @param float $cuotacableinternet Monthly combined cable and internet fee.
 * @return int|string|false The ID of the inserted client if successful, 'DUPLICATE_DNI' if DNI exists, or false on error.
 */
function createClient($dni, $nombre, $apellido, $direccion = null, $correo_electronico = null, $cuotacable = 0.00, $cuotainternet = 0.00, $cuotacableinternet = 0.00) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Check for duplicate DNI
    $stmt_check = $conn->prepare("SELECT id FROM cliente WHERE dni = ?");
    if (!$stmt_check) {
        error_log("Error preparing DNI check query: " . $conn->error);
        closeDB($conn);
        return false;
    }
    $stmt_check->bind_param("s", $dni);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        closeDB($conn);
        return 'DUPLICATE_DNI'; // Specific error for duplicate DNI
    }
    $stmt_check->close();

    $stmt = $conn->prepare("INSERT INTO cliente (dni, nombre, apellido, direccion, correo_electronico, cuotacable, cuotainternet, cuotacableinternet) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error preparing client insert query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("sssssddd", $dni, $nombre, $apellido, $direccion, $correo_electronico, $cuotacable, $cuotainternet, $cuotacableinternet);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);

        // Log audit action
        logAuditAction(
            getCurrentUserId(),
            'Cliente creado',
            'cliente',
            $last_id,
            null,
            ['id' => $last_id, 'dni' => $dni, 'nombre' => $nombre, 'apellido' => $apellido, 'cuotacable' => $cuotacable, 'cuotainternet' => $cuotainternet, 'cuotacableinternet' => $cuotacableinternet]
        );
        return $last_id;
    } else {
        if ($conn->errno == 1062) { // Duplicate entry error code
            error_log("Duplicate entry error creating client: " . $stmt->error);
        } else {
            error_log("Error executing client insert: " . $stmt->error);
        }
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Get a client by their ID.
 *
 * @param int $id The client's ID.
 * @return array|null An associative array with client data, or null if not found.
 */
function getClientById($id) {
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id = ?");
    if (!$stmt) {
        error_log("Error preparing get client by ID query: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $client = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $client;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Get all clients from the database.
 *
 * @return array An array of associative arrays with all client data.
 */
function getAllClients() {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT * FROM cliente ORDER BY apellido, nombre";
    $result = $conn->query($sql);

    $clients = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }
    closeDB($conn);
    return $clients;
}

/**
 * Update an existing client's information.
 *
 * @param int $id The ID of the client to update.
 * @param array $data An associative array with fields to update (e.g., ['direccion' => 'New Address']).
 * @return bool True if the update was successful, false otherwise.
 */
function updateClient($id, $data) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $original_client = getClientById($id); // Get original data for auditing
    if (!$original_client) {
        return false; // Client not found
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
        return false; // No data to update
    }

    $sql = "UPDATE cliente SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing client update query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $params[] = $id;
    $types .= 'i';

    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);

        // Log audit action if changes occurred
        if ($affected_rows > 0) {
            $updated_client = getClientById($id); // Get updated data
            logAuditAction(
                getCurrentUserId(),
                'Cliente actualizado',
                'cliente',
                $id,
                $original_client,
                $updated_client
            );
        }
        return $affected_rows > 0;
    } else {
        error_log("Error executing client update: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Delete a client by their ID.
 *
 * @param int $id The ID of the client to delete.
 * @return bool True if the deletion was successful, false otherwise.
 */
function deleteClient($id) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $original_client = getClientById($id); // Get original data for auditing
    if (!$original_client) {
        return false; // Client not found
    }

    $stmt = $conn->prepare("DELETE FROM cliente WHERE id = ?");
    if (!$stmt) {
        error_log("Error preparing client delete query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);

        // Log audit action if deleted
        if ($affected_rows > 0) {
            logAuditAction(
                getCurrentUserId(),
                'Cliente eliminado',
                'cliente',
                $id,
                $original_client,
                null
            );
        }
        return $affected_rows > 0;
    } else {
        error_log("Error executing client delete: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}
