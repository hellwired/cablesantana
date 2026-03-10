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
require_once 'payment_model.php'; // Include the payment model

/**
 * Create a new client in the database and subscribe them to a plan.
 *
 * @param string $dni Client's DNI.
 * @param string $nombre Client's first name.
 * @param string $apellido Client's last name.
 * @param string|null $direccion Client's address (optional).
 * @param string|null $correo_electronico Client's email (optional).
 * @param int $plan_id The ID of the plan to subscribe the client to.
 * @param string|null $notas_cliente Client's notes (optional).
 * @return int|string|false The ID of the inserted client if successful, 'DUPLICATE_DNI' if DNI exists, or false on error.
 */
function createClient($dni, $nombre, $apellido, $direccion = null, $correo_electronico = null, $plan_id = 0, $notas_cliente = null, $telefono = null, $whatsapp_apikey = null)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Check for duplicate DNI
    $stmt_check = $conn->prepare("SELECT id FROM clientes WHERE dni = ?");
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

    // The 'clientes' table no longer has cuota columns, so we remove them from the insert
    $stmt = $conn->prepare("INSERT INTO clientes (dni, nombre, apellido, direccion, correo_electronico, telefono, whatsapp_apikey, notas_cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error preparing client insert query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("ssssssss", $dni, $nombre, $apellido, $direccion, $correo_electronico, $telefono, $whatsapp_apikey, $notas_cliente);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        // Keep connection open for subsequent operations

        // Log audit action
        logAuditAction(
            getCurrentUserId(),
            'Cliente creado',
            'clientes',
            $last_id,
            null,
            ['id' => $last_id, 'dni' => $dni, 'nombre' => $nombre, 'apellido' => $apellido, 'plan_id' => $plan_id, 'notas_cliente' => $notas_cliente]
        );

        // --- New logic to create subscription, invoice, and payment based on plan_id ---
        if ($plan_id > 0) {
            require_once 'plan_model.php';
            $plan = getPlanById($plan_id);

            if ($plan) {
                $current_date = date('Y-m-d');
                $next_month_date = date('Y-m-d', strtotime('+1 month'));
                $initial_payment_amount = $plan['precio_mensual'];

                // 1. Create Subscription
                $suscripcion_id = createSubscription($last_id, $plan_id, $current_date, $next_month_date);

                if ($suscripcion_id) {
                    // 2. Create Invoice for the initial payment
                    // Create as 'pendiente' so the admin can register the payment manually in payments_ui.php
                    $invoice_id = createInvoice($suscripcion_id, $last_id, $initial_payment_amount, $current_date, $next_month_date, 'pendiente');

                    if ($invoice_id) {
                        // 3. Do NOT create Payment record automatically.
                        // The admin will go to payments_ui.php to register it.
                        // createPayment($invoice_id, $initial_payment_amount, date('Y-m-d H:i:s'), 'exitoso', 1, 'Pago inicial en oficina');
                    } else {
                        error_log("Error creating initial invoice for client ID: " . $last_id);
                    }
                } else {
                    error_log("Error creating initial subscription for client ID: " . $last_id);
                }
            } else {
                error_log("Invalid plan_id provided for new client: " . $plan_id);
            }
        }
        // --- End of new logic ---

        closeDB($conn); // Close connection after all operations
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
function getClientById($id)
{
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, dni, nombre, apellido, direccion, correo_electronico, telefono, whatsapp_apikey, notas_cliente, fecha_registro FROM clientes WHERE id = ?");
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
function getAllClients()
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    // Simplificando query usando columnas existentes en la tabla clientes
    // Query corregida para obtener datos del plan mediante JOINS
    $sql = "SELECT c.id, c.dni, c.nombre, c.apellido, c.direccion, c.correo_electronico, c.telefono, c.whatsapp_apikey, c.notas_cliente, c.fecha_registro,
            p.precio_mensual, p.nombre_plan, s.plan_id
            FROM clientes c
            LEFT JOIN (
                SELECT cliente_id, MAX(id) as max_sub_id
                FROM suscripciones
                GROUP BY cliente_id
            ) latest_sub ON c.id = latest_sub.cliente_id
            LEFT JOIN suscripciones s ON latest_sub.max_sub_id = s.id
            LEFT JOIN planes p ON s.plan_id = p.id
            ORDER BY c.apellido, c.nombre";

    $result = $conn->query($sql);

    // Check for query failure
    if (!$result) {
        error_log("Error in getAllClients query: " . $conn->error);
        closeDB($conn);
        return [];
    }

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
function updateClient($id, $data)
{
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

    $sql = "UPDATE clientes SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing client update query: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $params[] = $id;
    $types .= 'i';

    // Create references for bind_param
    $bind_params = [];
    $bind_params[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_params[] = &$params[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_params);

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
                'clientes',
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
 * This will also delete related records in other tables.
 *
 * @param int $id The ID of the client to delete.
 * @return bool True if the deletion was successful, false otherwise.
 */
function deleteClient($id)
{
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Get original data for auditing before deleting
        $original_client = getClientById($id);
        if (!$original_client) {
            // Client not found, roll back and return
            $conn->rollback();
            closeDB($conn);
            return false;
        }

        // Delete related invoices first
        $stmt_facturas = $conn->prepare("DELETE FROM facturas WHERE cliente_id = ?");
        if (!$stmt_facturas) {
            throw new Exception("Error preparing facturas delete query: " . $conn->error);
        }
        $stmt_facturas->bind_param("i", $id);
        $stmt_facturas->execute();
        $stmt_facturas->close();

        // Now, delete the client. Related subscriptions and transactions will be deleted by CASCADE.
        $stmt_cliente = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        if (!$stmt_cliente) {
            throw new Exception("Error preparing clientes delete query: " . $conn->error);
        }
        $stmt_cliente->bind_param("i", $id);
        $stmt_cliente->execute();
        $affected_rows = $stmt_cliente->affected_rows;
        $stmt_cliente->close();

        if ($affected_rows > 0) {
            // Log audit action
            logAuditAction(
                getCurrentUserId(),
                'Cliente eliminado',
                'clientes',
                $id,
                $original_client,
                null
            );
            // Commit the transaction
            $conn->commit();
            closeDB($conn);
            return true;
        } else {
            // If no rows were affected, it means the client didn't exist.
            $conn->rollback();
            closeDB($conn);
            return false;
        }
    } catch (Exception $e) {
        // If any query fails, roll back the transaction
        $conn->rollback();
        error_log("Error deleting client: " . $e->getMessage());
        closeDB($conn);
        return false;
    }
}

/**
 * Get a client by their DNI.
 *
 * @param string $dni The client's DNI.
 * @return array|null An associative array with client data, or null if not found.
 */
function getClientByDni($dni)
{
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM clientes WHERE dni = ?");
    if (!$stmt) {
        error_log("Error preparing get client by DNI query: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("s", $dni);
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
 * Get a client by their email.
 *
 * @param string $email The client's email.
 * @return array|null An associative array with client data, or null if not found.
 */
function getClientByEmail($email)
{
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo_electronico = ?");
    if (!$stmt) {
        error_log("Error preparing get client by email query: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("s", $email);
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
 * Get the monthly price for a client based on their latest subscription.
 *
 * @param int $client_id The client's ID.
 * @return float The monthly price, or 0.0 if not found.
 */
function getClientMonthlyPrice($client_id)
{
    $conn = connectDB();
    if (!$conn) {
        return 0.0;
    }

    $sql = "SELECT p.precio_mensual 
            FROM suscripciones s 
            JOIN planes p ON s.plan_id = p.id 
            WHERE s.cliente_id = ? 
            ORDER BY s.id DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        closeDB($conn);
        return 0.0;
    }

    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $price = 0.0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $price = (float) $row['precio_mensual'];
    }

    $stmt->close();
    closeDB($conn);
    return $price;
}

/**
 * Update the client's subscription plan.
 * Updates the latest subscription if it exists, or creates a new one if not.
 * 
 * @param int $client_id
 * @param int $plan_id
 * @return bool
 */
function updateClientSubscriptionPlan($client_id, $plan_id)
{
    if ($plan_id <= 0)
        return false;

    $conn = connectDB();
    if (!$conn)
        return false;

    // Check for existing latest subscription
    $sql = "SELECT id FROM suscripciones WHERE cliente_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Update existing
        $sub = $res->fetch_assoc();
        $sub_id = $sub['id'];
        $stmt->close();

        $upd = $conn->prepare("UPDATE suscripciones SET plan_id = ? WHERE id = ?");
        $upd->bind_param("ii", $plan_id, $sub_id);
        $result = $upd->execute();
        $upd->close();
    } else {
        // Create new active subscription
        $stmt->close();
        $current_date = date('Y-m-d');
        // Default next billing to 1 month from now? Or today? Let's say 1 month.
        $next_month = date('Y-m-d', strtotime('+1 month'));

        $ins = $conn->prepare("INSERT INTO suscripciones (cliente_id, plan_id, estado, fecha_inicio, fecha_proximo_cobro) VALUES (?, ?, 'activa', ?, ?)");
        $ins->bind_param("iiss", $client_id, $plan_id, $current_date, $next_month);
        $result = $ins->execute();
        $ins->close();
    }

    closeDB($conn);
    return $result;
}

/**
 * Search clients by a search term.
 *
 * @param string $term The search term.
 * @return array An array of associative arrays with the matching clients.
 */
function searchClients($term)
{
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $term = "%" . $conn->real_escape_string($term) . "%";

    // Simplificando query usando columnas existentes en la tabla clientes
    // Query corregida para buscador usando JOINS
    $sql = "SELECT c.id, c.dni, c.nombre, c.apellido, c.direccion, c.correo_electronico, c.telefono, c.whatsapp_apikey, c.notas_cliente, c.fecha_registro,
            p.precio_mensual, p.nombre_plan, s.plan_id
            FROM clientes c
            LEFT JOIN (
                SELECT cliente_id, MAX(id) as max_sub_id
                FROM suscripciones
                GROUP BY cliente_id
            ) latest_sub ON c.id = latest_sub.cliente_id
            LEFT JOIN suscripciones s ON latest_sub.max_sub_id = s.id
            LEFT JOIN planes p ON s.plan_id = p.id
            WHERE c.dni LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR c.direccion LIKE ? OR c.correo_electronico LIKE ?
            ORDER BY c.apellido, c.nombre";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("sssss", $term, $term, $term, $term, $term);
    
    if (!$stmt->execute()) {
        error_log("Error executing searchClients query: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return [];
    }

    $result = $stmt->get_result();

    if (!$result) {
         error_log("Error getting result in searchClients: " . $stmt->error);
         $stmt->close();
         closeDB($conn);
         return [];
    }

    $clients = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }

    $stmt->close();
    closeDB($conn);
    return $clients;
}


