<?php
/**
 * users_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'usuarios', utilizando Bootstrap 4 para el diseño.
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
        $new_user_id = createUser($nombre_usuario, $contrasena, $rol, $email, null); // Pass null for cliente_id
        if ($new_user_id) {
            $message = ['type' => 'success', 'text' => 'Usuario "' . htmlspecialchars($nombre_usuario) . '" creado exitosamente con ID: ' . $new_user_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al crear el usuario. El nombre de usuario o email podrían ya existir.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Nombre de Usuario, Contraseña, Rol) son necesarios para crear un usuario.'];
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

    // Add cliente_id to update data if provided and not empty
    if (isset($_POST['cliente_id_edit']) && $_POST['cliente_id_edit'] !== '') {
        $update_data['cliente_id'] = (int) $_POST['cliente_id_edit'];
    } else {
        // If it's empty, set to NULL in DB
        $update_data['cliente_id'] = null;
    }

    if ($user_id > 0 && !empty($update_data)) {
        $updated = updateUser($user_id, $update_data);
        if ($updated) {
            $message = ['type' => 'success', 'text' => 'Usuario con ID ' . $user_id . ' actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el usuario con ID ' . $user_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el usuario.'];
    }
}

// Manejar la eliminación de un usuario
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
    $user_id = (int) $_GET['id'];
    if ($user_id > 0) {
        $deleted = deleteUser($user_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Usuario con ID ' . $user_id . ' eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el usuario con ID ' . $user_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de usuario no válido para eliminar.'];
    }
}

// Obtener todos los usuarios para mostrar en la tabla
$users = getAllUsers();

require_once 'header.php';
?>

<div class="container py-4">
    <h1 class="text-center mb-4">Gestión de Usuarios</h1>

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
                                <th>ID de Cliente</th>
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
                                    <td><?php echo htmlspecialchars($user['cliente_id'] ?? 'N/A'); ?></td>
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
                                        <button type="button" class="btn btn-info btn-sm rounded-pill" data-toggle="modal"
                                            data-target="#editUserModal" data-id="<?php echo $user['id']; ?>"
                                            data-nombre_usuario="<?php echo htmlspecialchars($user['nombre_usuario']); ?>"
                                            data-cliente_id="<?php echo htmlspecialchars($user['cliente_id'] ?? ''); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-rol="<?php echo htmlspecialchars($user['rol']); ?>"
                                            data-activo="<?php echo $user['activo'] ? '1' : '0'; ?>">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <!-- Botón de Eliminar -->
                                        <a href="users_ui.php?action=delete_user&id=<?php echo $user['id']; ?>"
                                            class="btn btn-danger btn-sm rounded-pill"
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción es irreversible.');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No hay usuarios registrados.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content rounded-lg">
            <div class="modal-header bg-primary text-white rounded-top-lg">
                <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="users_ui.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_nombre_usuario" class="form-label">Nombre de Usuario:</label>
                        <input type="text" class="form-control" id="edit_nombre_usuario" name="nombre_usuario_edit"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="edit_email" name="email_edit">
                    </div>
                    <div class="mb-3">
                        <label for="edit_rol" class="form-label">Rol:</label>
                        <select class="form-select" id="edit_rol" name="rol_edit" required>
                            <option value="administrador">Administrador</option>
                            <option value="editor">Editor</option>
                            <option value="visor">Visor</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cliente_id" class="form-label">ID de Cliente Asociado:</label>
                        <input type="number" class="form-control" id="edit_cliente_id" name="cliente_id_edit"
                            placeholder="Dejar en blanco si no es un cliente" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_contrasena" class="form-label">Nueva Contraseña (dejar en blanco para no
                            cambiar):</label>
                        <input type="password" class="form-control" id="edit_contrasena" name="contrasena_edit">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_activo" name="activo_edit" value="1">
                        <label class="form-check-label" for="edit_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para pasar datos al modal de edición -->
<script>
    // Use event delegation for dynamically loaded content or just ensuring standard jQuery readiness
    // Note: We are including jQuery in footer, so this script block must be AFTER footer include in execution order, 
    // but physically here it's before. However, the DOMContentLoaded logic or using jQuery's ready inside footer 
    // implies we should put this script AFTER the footer include.
    // Let's rely on standard JS for the modal event to avoid dependency issues if jQuery loads later.

    document.addEventListener('DOMContentLoaded', function () {
        var editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var nombre_usuario = button.getAttribute('data-nombre_usuario');
                var cliente_id = button.getAttribute('data-cliente_id');
                var email = button.getAttribute('data-email');
                var rol = button.getAttribute('data-rol');
                var activo = button.getAttribute('data-activo');

                var modal = this;
                modal.querySelector('#edit_user_id').value = id;
                modal.querySelector('#edit_nombre_usuario').value = nombre_usuario;
                modal.querySelector('#edit_cliente_id').value = cliente_id;
                modal.querySelector('#edit_email').value = email;
                modal.querySelector('#edit_rol').value = rol;
                modal.querySelector('#edit_activo').checked = (activo == '1');
                modal.querySelector('#edit_contrasena').value = '';
            });
        }
    });
</script>

<?php require_once 'footer.php'; ?>