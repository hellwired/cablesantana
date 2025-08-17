<?php
/**
 * users_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'usuario', utilizando Bootstrap 4 para el diseño.
 * Incluye el modelo de usuario para interactuar con la base de datos.
 *
 * Requiere autenticación de usuario y rol 'administrador' para acceder.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado y tiene rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    // Si no está logueado o no es administrador, redirigir al login
    header('Location: login.php');
    exit();
}

require_once 'user_model.php'; // Incluir el modelo de usuario

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de un nuevo usuario
if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $email = $_POST['email'] ?? '';
    $rol = $_POST['rol'] ?? '';

    if (!empty($nombre_usuario) && !empty($contrasena) && !empty($rol)) {
        $new_user_id = createUser($nombre_usuario, $contrasena, $rol, $email);
        if ($new_user_id) {
            $message = ['type' => 'success', 'text' => 'Cliente "' . htmlspecialchars($nombre_usuario) . '" creado exitosamente con ID: ' . $new_user_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al crear el cliente. El nombre de usuario o email podrían ya existir.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Nombre de Usuario, Contraseña, Rol) son necesarios para crear un cliente.'];
    }
}

// Manejar la actualización de un usuario existente
if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id = $_POST['user_id'] ?? 0;
    $update_data = [];

    if (isset($_POST['nombre_usuario_edit'])) {
        $update_data['nombre_usuario'] = $_POST['nombre_usuario_edit'];
    }
    if (isset($_POST['email_edit'])) {
        $update_data['email'] = $_POST['email_edit'];
    }
    if (isset($_POST['rol_edit'])) {
        $update_data['rol'] = $_POST['rol_edit'];
    }
    if (isset($_POST['contrasena_edit']) && !empty($_POST['contrasena_edit'])) {
        $update_data['contrasena'] = $_POST['contrasena_edit']; // Se hasheará en user_model.php
    }
    // Para el campo 'activo', se maneja con un checkbox
    $update_data['activo'] = isset($_POST['activo_edit']) ? TRUE : FALSE;


    if ($user_id > 0 && !empty($update_data)) {
        $updated = updateUser($user_id, $update_data);
        if ($updated) {
            $message = ['type' => 'success', 'text' => 'Cliente con ID ' . $user_id . ' actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el cliente con ID ' . $user_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el cliente.'];
    }
}

// Manejar la eliminación de un usuario
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    if ($user_id > 0) {
        $deleted = deleteUser($user_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Cliente con ID ' . $user_id . ' eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el cliente con ID ' . $user_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de cliente no válido para eliminar.'];
    }
}

// Obtener todos los usuarios para mostrar en la tabla
$users = getAllUsers();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestión de Usuarios - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para iconos (opcional, pero útil) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
            margin-bottom: 30px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #343a40;
            margin-bottom: 20px;
        }
        .form-control, .btn {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-info:hover {
            background-color: #117a8b;
            border-color: #117a8b;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bd2130;
            border-color: #bd2130;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .alert {
            border-radius: 8px;
        }
        .navbar {
            border-radius: 0 0 15px 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="dashboard.php">Cable Santana</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="users_ui.php">Gestión de Usuarios <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments_ui.php">Gestión de Pagos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="debts_ui.php">Gestión de Deudas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clients_ui.php">Gestión de Clientes</a>
                </li>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="audit_ui.php">Log de Auditoría</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="navbar-text mr-3">
                        Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['rol']); ?>)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light rounded-pill" href="logout.php">Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center">Gestión de Usuarios</h1>

        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Formulario para Crear Usuario -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h2 class="mb-0">Crear Nuevo Usuario</h2>
            </div>
            <div class="card-body">
                <form action="users_ui.php" method="POST">
                    <input type="hidden" name="action" value="create_user">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre_usuario">Nombre de Usuario:</label>
                            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contrasena">Contraseña:</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rol">Rol:</label>
                            <select class="form-control" id="rol" name="rol" required>
                                <option value="">Seleccione un rol</option>
                                <option value="administrador">Administrador</option>
                                <option value="editor">Editor</option>
                                <option value="visor">Visor</option>
                                <option value="cliente">Cliente</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Crear Usuario</button>
                </form>
            </div>
        </div>

        <!-- Tabla para Mostrar Usuarios -->
        <div class="card">
            <div class="card-header bg-info text-white rounded-top">
                <h2 class="mb-0">Lista de Usuarios</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Activo</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nombre_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($user['rol']); ?></td>
                                        <td>
                                            <?php if ($user['activo']): ?>
                                                <span class="badge badge-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['fecha_creacion']); ?></td>
                                        <td>
                                            <!-- Botón para Abrir Modal de Edición -->
                                            <button type="button" class="btn btn-info btn-sm rounded-pill" data-toggle="modal" data-target="#editUserModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-nombre_usuario="<?php echo htmlspecialchars($user['nombre_usuario']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-rol="<?php echo htmlspecialchars($user['rol']); ?>"
                                                data-activo="<?php echo $user['activo'] ? '1' : '0'; ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <!-- Botón de Eliminar -->
                                            <a href="users_ui.php?action=delete_user&id=<?php echo $user['id']; ?>"
                                               class="btn btn-danger btn-sm rounded-pill"
                                               onclick="return confirm('¿Estás seguro de que quieres eliminar a este cliente? Esta acción es irreversible.');">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay clientes registrados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-primary text-white rounded-top-lg">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="users_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group">
                            <label for="edit_nombre_usuario">Nombre de Usuario:</label>
                            <input type="text" class="form-control" id="edit_nombre_usuario" name="nombre_usuario_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email:</label>
                            <input type="email" class="form-control" id="edit_email" name="email_edit">
                        </div>
                        <div class="form-group">
                            <label for="edit_rol">Rol:</label>
                            <select class="form-control" id="edit_rol" name="rol_edit" required>
                                <option value="administrador">Administrador</option>
                                <option value="editor">Editor</option>
                                <option value="visor">Visor</option>
                                <option value="cliente">Cliente</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_contrasena">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                            <input type="password" class="form-control" id="edit_contrasena" name="contrasena_edit">
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="edit_activo" name="activo_edit" value="1">
                            <label class="form-check-label" for="edit_activo">Activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary rounded-pill">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script para pasar datos al modal de edición
        $('#editUserModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que disparó el modal
            var id = button.data('id');
            var nombre_usuario = button.data('nombre_usuario');
            var email = button.data('email');
            var rol = button.data('rol');
            var activo = button.data('activo');

            var modal = $(this);
            modal.find('.modal-body #edit_user_id').val(id);
            modal.find('.modal-body #edit_nombre_usuario').val(nombre_usuario);
            modal.find('.modal-body #edit_email').val(email);
            modal.find('.modal-body #edit_rol').val(rol);
            modal.find('.modal-body #edit_activo').prop('checked', activo == '1');
            modal.find('.modal-body #edit_contrasena').val(''); // Limpiar campo de contraseña al abrir
        });
    </script>
</body>
</html>
