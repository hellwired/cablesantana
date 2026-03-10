<?php
/**
 * users_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'usuario', y ahora incluye una vista para el sistema DOCSIS.
 *
 * Requiere autenticación de usuario y rol 'administrador' para acceder.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CORRECCIÓN: Usar las variables de sesión que definimos en el login.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// CORRECCIÓN: Incluir el modelo de Usuario orientado a objetos que creamos.
// Asume que la carpeta 'models' está en la raíz del proyecto.
require_once __DIR__ . '/models/Usuario.php';

$usuario_model = new Usuario();
$message = ['type' => '', 'text' => ''];

// --- Lógica para manejar las operaciones CRUD (Adaptada a la clase Usuario) ---

// Manejar la creación de un nuevo usuario
if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $usuario_model->nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $usuario_model->password = $_POST['contrasena'] ?? '';
    $usuario_model->email = $_POST['email'] ?? '';
    $usuario_model->rol = $_POST['rol'] ?? '';

    if (!empty($usuario_model->nombre_usuario) && !empty($usuario_model->password) && !empty($usuario_model->rol)) {
        if ($usuario_model->crear()) {
            $message = ['type' => 'success', 'text' => 'Usuario creado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al crear el usuario.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos son obligatorios.'];
    }
}

// Manejar la actualización de un usuario existente
if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $usuario_model->id = $_POST['user_id'] ?? 0;
    $usuario_model->nombre_usuario = $_POST['nombre_usuario_edit'] ?? '';
    $usuario_model->email = $_POST['email_edit'] ?? '';
    $usuario_model->rol = $_POST['rol_edit'] ?? '';
    $usuario_model->password = $_POST['contrasena_edit'] ?? ''; // El modelo lo hasheará si no está vacío
    $usuario_model->activo = isset($_POST['activo_edit']);

    if ($usuario_model->id > 0) {
        if ($usuario_model->actualizar()) {
            $message = ['type' => 'success', 'text' => 'Usuario actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el usuario.'];
        }
    }
}

// Manejar la eliminación de un usuario
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
    $usuario_model->id = (int)$_GET['id'];
    // Prevenir que el admin se borre a sí mismo
    if ($usuario_model->id > 0 && $usuario_model->id != $_SESSION['usuario_id']) {
        if ($usuario_model->eliminar()) {
            $message = ['type' => 'success', 'text' => 'Usuario eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el usuario.'];
        }
    }
}

// Obtener todos los usuarios para mostrar en la tabla
$users = $usuario_model->leerTodos();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestión de Usuarios - Cable Santana</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .container-fluid { padding-left: 0; padding-right: 0; }
        .main-container { margin-top: 30px; margin-bottom: 30px; background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1, h2 { color: #343a40; margin-bottom: 20px; }
        .form-control, .btn { border-radius: 8px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Cable Santana</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="users-tab" data-toggle="tab" href="#users" role="tab" aria-controls="users" aria-selected="true">Gestión de Usuarios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="docsis-tab" data-toggle="tab" href="#docsis" role="tab" aria-controls="docsis" aria-selected="false">Status Modem</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="clients_ui.php">Gestión de Clientes</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="navbar-text mr-3">
                        <!-- CORRECCIÓN: Usar las variables de sesión correctas -->
                        Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['usuario_rol']); ?>)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light" href="logout.php">Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="main-container">
                    <h1 class="text-center">Gestión de Usuarios</h1>
                    <?php if (!empty($message['text'])): ?>
                        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message['text']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white"><h2 class="mb-0 h5">Crear Nuevo Usuario</h2></div>
                        <div class="card-body">
                            <form action="users_ui.php" method="POST">
                                <input type="hidden" name="action" value="create_user">
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label>Nombre de Usuario:</label><input type="text" class="form-control" name="nombre_usuario" required></div>
                                    <div class="form-group col-md-6"><label>Contraseña:</label><input type="password" class="form-control" name="contrasena" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label>Email:</label><input type="email" class="form-control" name="email" required></div>
                                    <div class="form-group col-md-6"><label>Rol:</label><select class="form-control" name="rol" required><option value="">Seleccione rol</option><option value="admin">Administrador</option><option value="empleado">Empleado</option><option value="visor">Visor</option></select></div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Crear Usuario</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-info text-white"><h2 class="mb-0 h5">Lista de Usuarios</h2></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="thead-dark"><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Activo</th><th>Creación</th><th>Acciones</th></tr></thead>
                                    <tbody>
                                        <?php if (!empty($users)) foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['nombre_usuario']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($user['rol']); ?></td>
                                                <td><span class="badge badge-<?php echo $user['activo'] ? 'success' : 'danger'; ?>"><?php echo $user['activo'] ? 'Sí' : 'No'; ?></span></td>
                                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($user['fecha_creacion']))); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#editUserModal" data-id="<?php echo $user['id']; ?>" data-nombre_usuario="<?php echo htmlspecialchars($user['nombre_usuario']); ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-rol="<?php echo htmlspecialchars($user['rol']); ?>" data-activo="<?php echo $user['activo'] ? '1' : '0'; ?>"><i class="fas fa-edit"></i></button>
                                                    <?php if ($_SESSION['usuario_id'] != $user['id']): ?>
                                                        <a href="users_ui.php?action=delete_user&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro?');"><i class="fas fa-trash-alt"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="docsis" role="tabpanel" aria-labelledby="docsis-tab">
                <div class="main-container">
                    <h1 class="text-center">Status del Sistema DOCSIS</h1>
                    <p>Esta ventana carga la interfaz del sistema de módems. Si no se muestra, puede deberse a restricciones de la red o del navegador.</p>
                    <div class="embed-responsive embed-responsive-16by9" style="height: 75vh;">
                        <iframe class="embed-responsive-item" src="http://192.168.0.1/Docsis_system.php"></iframe>
                    </div>
                    <a href="http://192.168.0.1/Docsis_system.php" class="btn btn-primary mt-3" target="_blank">Abrir en Nueva Pestaña</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <form action="users_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group"><label>Nombre de Usuario:</label><input type="text" class="form-control" id="edit_nombre_usuario" name="nombre_usuario_edit" required></div>
                        <div class="form-group"><label>Email:</label><input type="email" class="form-control" id="edit_email" name="email_edit" required></div>
                        <div class="form-group"><label>Rol:</label><select class="form-control" id="edit_rol" name="rol_edit" required><option value="admin">Administrador</option><option value="empleado">Empleado</option><option value="visor">Visor</option></select></div>
                        <div class="form-group"><label>Nueva Contraseña (dejar en blanco para no cambiar):</label><input type="password" class="form-control" id="edit_contrasena" name="contrasena_edit"></div>
                        <div class="form-group form-check"><input type="checkbox" class="form-check-input" id="edit_activo" name="activo_edit" value="1"><label class="form-check-label" for="edit_activo">Activo</label></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('#editUserModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nombre_usuario = button.data('nombre_usuario');
            var email = button.data('email');
            var rol = button.data('rol');
            var activo = button.data('activo');
            var modal = $(this);
            modal.find('#edit_user_id').val(id);
            modal.find('#edit_nombre_usuario').val(nombre_usuario);
            modal.find('#edit_email').val(email);
            modal.find('#edit_rol').val(rol);
            modal.find('#edit_activo').prop('checked', activo == '1');
            modal.find('#edit_contrasena').val('');
        });
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('lastTab', $(this).attr('href'));
        });
        var lastTab = localStorage.getItem('lastTab');
        if (lastTab) {
            $('[href="' + lastTab + '"]').tab('show');
        }
    </script>
</body>
</html>
