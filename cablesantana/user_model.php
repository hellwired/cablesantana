<?php
/**
 * user_model.php
 *
 * Este archivo contiene las funciones para interactuar con la tabla 'usuario'
 * de la base de datos, realizando operaciones CRUD (Crear, Leer, Actualizar, Eliminar).
 * Incluye la conexión a la base de datos a través de db_connection.php.
 * Ahora también incluye funciones para registrar acciones en la tabla de auditoría.
 */

require_once 'db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once 'audit_model.php';   // Incluir el modelo de auditoría

/**
 * Obtiene el ID del usuario actual de la sesión.
 *
 * @return int|null El ID del usuario si está logueado, de lo contrario null.
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Crea un nuevo usuario en la base de datos.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @param string $contrasena Contraseña (se recomienda pasarla ya hasheada).
 * @param string $rol Rol del usuario ('administrador', 'editor', 'visor').
 * @param string|null $email Correo electrónico del usuario (opcional).
 * @return int|false El ID del usuario insertado si es exitoso, o false en caso de error.
 */
function createUser($nombre_usuario, $contrasena, $rol, $email = null) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    // Hashear la contraseña de forma segura antes de guardarla
    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuario (nombre_usuario, contrasena, email, rol) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Error al preparar la consulta de inserción de usuario: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("ssss", $nombre_usuario, $hashed_password, $email, $rol);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);

        // Registrar acción de auditoría
        logAuditAction(
            getCurrentUserId(),
            'Usuario creado',
            'usuario',
            $last_id,
            null,
            ['id' => $last_id, 'nombre_usuario' => $nombre_usuario, 'rol' => $rol, 'email' => $email]
        );
        return $last_id;
    } else {
        // Capturar errores de duplicidad (ej. UNIQUE constraint)
        if ($conn->errno == 1062) { // Código de error para entrada duplicada
            error_log("Error de duplicidad al crear usuario: " . $stmt->error);
        } else {
            error_log("Error al ejecutar la inserción de usuario: " . $stmt->error);
        }
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Obtiene un usuario por su ID.
 *
 * @param int $id El ID del usuario.
 * @return array|null Un array asociativo con los datos del usuario, o null si no se encuentra.
 */
function getUserById($id) {
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, email, rol, fecha_creacion, activo FROM usuario WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de usuario por ID: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $user;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Obtiene un usuario por su nombre de usuario.
 *
 * @param string $nombre_usuario El nombre de usuario.
 * @return array|null Un array asociativo con los datos del usuario, o null si no se encuentra.
 */
function getUserByUsername($nombre_usuario) {
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, contrasena, email, rol, fecha_creacion, activo FROM usuario WHERE nombre_usuario = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de usuario por nombre de usuario: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        closeDB($conn);
        return $user;
    } else {
        $stmt->close();
        closeDB($conn);
        return null;
    }
}

/**
 * Obtiene todos los usuarios de la base de datos.
 *
 * @return array Un array de arrays asociativos con los datos de los usuarios.
 */
function getAllUsers() {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT id, nombre_usuario, email, rol, fecha_creacion, activo FROM usuario";
    $result = $conn->query($sql);

    $users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    closeDB($conn);
    return $users;
}

/**
 * Actualiza la información de un usuario existente.
 *
 * @param int $id El ID del usuario a actualizar.
 * @param array $data Un array asociativo con los campos a actualizar (ej. ['email' => 'nuevo@email.com', 'rol' => 'editor']).
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updateUser($id, $data) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $original_user = getUserById($id); // Obtener datos originales para auditoría
    if (!$original_user) {
        return false; // Usuario no encontrado
    }

    $set_clauses = [];
    $params = [];
    $types = '';

    foreach ($data as $key => $value) {
        if ($key === 'contrasena') {
            // Hashear la nueva contraseña de forma segura si se proporciona
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $set_clauses[] = "$key = ?";
        $params[] = $value;
        // Determinar el tipo de parámetro para bind_param
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i'; // Los booleanos se tratan como int (0 o 1)
        } else {
            $types .= 's';
        }
    }

    if (empty($set_clauses)) {
        closeDB($conn);
        return false; // No hay datos para actualizar
    }

    $sql = "UPDATE usuario SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de actualización de usuario: " . $conn->error);
        closeDB($conn);
        return false;
    }

    // Añadir el ID al final de los parámetros y su tipo
    $params[] = $id;
    $types .= 'i';

    // Usar call_user_func_array para bind_param con un número variable de argumentos
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);

        // Registrar acción de auditoría si hubo cambios
        if ($affected_rows > 0) {
            $updated_user = getUserById($id); // Obtener datos actualizados
            logAuditAction(
                getCurrentUserId(),
                'Usuario actualizado',
                'usuario',
                $id,
                $original_user,
                $updated_user
            );
        }
        return $affected_rows > 0; // Retorna true si al menos una fila fue afectada
    } else {
        error_log("Error al ejecutar la actualización de usuario: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Elimina un usuario por su ID.
 *
 * @param int $id El ID del usuario a eliminar.
 * @return bool True si la eliminación fue exitosa, false en caso contrario.
 */
function deleteUser($id) {
    $conn = connectDB();
    if (!$conn) {
        return false;
    }

    $original_user = getUserById($id); // Obtener datos originales para auditoría
    if (!$original_user) {
        return false; // Usuario no encontrado
    }

    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta de eliminación de usuario: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        closeDB($conn);

        // Registrar acción de auditoría si se eliminó
        if ($affected_rows > 0) {
            logAuditAction(
                getCurrentUserId(),
                'Usuario eliminado',
                'usuario',
                $id,
                $original_user,
                null
            );
        }
        return $affected_rows > 0; // Retorna true si al menos una fila fue eliminada
    } else {
        error_log("Error al ejecutar la eliminación de usuario: " . $stmt->error);
        $stmt->close();
        closeDB($conn);
        return false;
    }
}

/**
 * Verifica las credenciales de un usuario con migración de hash de contraseña.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @param string $contrasena Contraseña en texto plano.
 * @return array|null Un array asociativo con los datos del usuario si es válido, o null.
 */
function verifyUser($nombre_usuario, $contrasena) {
    $conn = connectDB();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, contrasena, email, rol, activo FROM usuario WHERE nombre_usuario = ? AND activo = TRUE");
    if (!$stmt) {
        error_log("Error al preparar la consulta de verificación: " . $conn->error);
        closeDB($conn);
        return null;
    }

    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $password_match = false;
        $needs_rehash = false;

        // Primero, intentar verificar con el nuevo método (password_verify)
        if (password_verify($contrasena, $user['contrasena'])) {
            $password_match = true;
            // Comprobar si el hash necesita ser actualizado a un algoritmo más nuevo
            if (password_needs_rehash($user['contrasena'], PASSWORD_DEFAULT)) {
                $needs_rehash = true;
            }
        }
        // Si falla, intentar verificar con el método antiguo (sha256) como fallback
        elseif (hash('sha256', $contrasena) === $user['contrasena']) {
            $password_match = true;
            $needs_rehash = true; // Forzar rehash porque es un hash antiguo
        }

        if ($password_match) {
            // Si la contraseña es correcta y necesita ser actualizada
            if ($needs_rehash) {
                $new_hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE usuario SET contrasena = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $new_hashed_password, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    logAuditAction($user['id'], 'Contraseña actualizada automáticamente a nuevo hash', 'usuario', $user['id']);
                } else {
                    error_log("Error al preparar la consulta de actualización de contraseña para el usuario ID: " . $user['id']);
                }
            }

            unset($user['contrasena']);
            logAuditAction($user['id'], 'Inicio de sesión exitoso', 'usuario', $user['id']);
            $stmt->close();
            closeDB($conn);
            return $user;
        }
    }

    // Si el usuario no existe o la contraseña es incorrecta
    logAuditAction(null, 'Intento de inicio de sesión fallido', 'usuario', null, ['nombre_usuario' => $nombre_usuario]);
    if (isset($stmt)) $stmt->close();
    closeDB($conn);
    return null;
}

/**
 * Obtiene todos los usuarios con un rol específico.
 *
 * @param string $role El rol a filtrar.
 * @return array Un array de arrays asociativos con los datos de los usuarios.
 */
function getUsersByRole($role) {
    $conn = connectDB();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, email, rol, fecha_creacion, activo FROM usuario WHERE rol = ? ORDER BY nombre_usuario ASC");
    if (!$stmt) {
        error_log("Error al preparar la consulta de obtención de usuarios por rol: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    $stmt->close();
    closeDB($conn);
    return $users;
}
