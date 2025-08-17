<?php
/**
 * clients_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'cliente', utilizando Bootstrap 4 para el diseño.
 * Incluye el modelo de cliente para interactuar con la base de datos.
 *
 * Requiere autenticación de usuario y rol 'editor' o 'administrador' para acceder y gestionar clientes.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a la página de login si no hay sesión
    exit();
}

require_once 'client_model.php'; // Incluir el modelo de cliente
require_once 'audit_model.php';   // Incluir el modelo de auditoría para logAuditAction

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// Verificar permisos: Solo 'administrador' y 'editor' pueden gestionar clientes
$can_manage_clients = ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor');

// Si el usuario no tiene permisos, redirigir o mostrar un mensaje de acceso denegado
if (!$can_manage_clients) {
    // Podrías redirigir a una página de inicio o mostrar un error
    header('Location: users_ui.php'); // Redirigir a una página accesible
    exit();
}

// La función getCurrentUserId() ha sido eliminada de aquí, ya que está definida en client_model.php


// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de un nuevo cliente
if (isset($_POST['action']) && $_POST['action'] === 'create_client') {
    $dni = $_POST['dni'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $direccion = $_POST['direccion'] ?? null;
    $correo_electronico = $_POST['correo_electronico'] ?? null;
    $cuotacable = $_POST['cuotacable'] ?? 0.00;
    $cuotainternet = $_POST['cuotainternet'] ?? 0.00;
    $cuotacableinternet = $_POST['cuotacableinternet'] ?? 0.00;

    if (!empty($dni) && !empty($nombre) && !empty($apellido)) {
        $new_client_id = createClient($dni, $nombre, $apellido, $direccion, $correo_electronico, (float)$cuotacable, (float)$cuotainternet, (float)$cuotacableinternet);
        if ($new_client_id === 'DUPLICATE_DNI') {
            $message = ['type' => 'danger', 'text' => 'Error: Ya existe un cliente con el DNI ingresado.'];
        } elseif ($new_client_id) {
            $message = ['type' => 'success', 'text' => 'Cliente "' . htmlspecialchars($nombre) . ' ' . htmlspecialchars($apellido) . '" creado exitosamente con ID: ' . $new_client_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al crear el cliente. El DNI o correo electrónico podrían ya existir.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Los campos DNI, Nombre y Apellido son obligatorios para crear un cliente.'];
    }
}

// Manejar la actualización de un cliente existente
if (isset($_POST['action']) && $_POST['action'] === 'update_client') {
    $client_id = $_POST['client_id'] ?? 0;
    $update_data = [];

    if (isset($_POST['dni_edit'])) {
        $update_data['dni'] = $_POST['dni_edit'];
    }
    if (isset($_POST['nombre_edit'])) {
        $update_data['nombre'] = $_POST['nombre_edit'];
    }
    if (isset($_POST['apellido_edit'])) {
        $update_data['apellido'] = $_POST['apellido_edit'];
    }
    if (isset($_POST['direccion_edit'])) {
        $update_data['direccion'] = $_POST['direccion_edit'];
    }
    if (isset($_POST['correo_electronico_edit'])) {
        $update_data['correo_electronico'] = $_POST['correo_electronico_edit'];
    }
    if (isset($_POST['cuotacable_edit'])) {
        $update_data['cuotacable'] = (float)$_POST['cuotacable_edit'];
    }
    if (isset($_POST['cuotainternet_edit'])) {
        $update_data['cuotainternet'] = (float)$_POST['cuotainternet_edit'];
    }
    if (isset($_POST['cuotacableinternet_edit'])) {
        $update_data['cuotacableinternet'] = (float)$_POST['cuotacableinternet_edit'];
    }

    if ($client_id > 0 && !empty($update_data)) {
        // La auditoría ya se realiza dentro de updateClient en client_model.php
        $updated = updateClient($client_id, $update_data);
        if ($updated) {
            $message = ['type' => 'success', 'text' => 'Cliente con ID ' . $client_id . ' actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el cliente con ID ' . $client_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el cliente.'];
    }
}

// Manejar la eliminación de un cliente
if (isset($_GET['action']) && $_GET['action'] === 'delete_client' && isset($_GET['id'])) {
    $client_id = (int)$_GET['id'];
    if ($client_id > 0) {
        // La auditoría ya se realiza dentro de deleteClient en client_model.php
        $deleted = deleteClient($client_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Cliente con ID ' . $client_id . ' eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el cliente con ID ' . $client_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de cliente no válido para eliminar.'];
    }
}

// Obtener todos los clientes para mostrar en la tabla
$clients = getAllClients();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestión de Clientes - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para iconos -->
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
                <li class="nav-item">
                    <a class="nav-link" href="users_ui.php">Gestión de Usuarios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments_ui.php">Gestión de Pagos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="debts_ui.php">Gestión de Deudas</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="clients_ui.php">Gestión de Clientes <span class="sr-only">(current)</span></a>
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
        <h1 class="text-center">Gestión de Clientes</h1>

        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Formulario para Crear Cliente -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h2 class="mb-0">Registrar Nuevo Cliente</h2>
            </div>
            <div class="card-body">
                <form action="clients_ui.php" method="POST">
                    <input type="hidden" name="action" value="create_client">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="dni">DNI:</label>
                            <input type="text" class="form-control" id="dni" name="dni" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="nombre">Nombre:</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="apellido">Apellido:</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>
                    <div class="form-group">
                        <label for="correo_electronico">Correo Electrónico:</label>
                        <input type="email" class="form-control" id="correo_electronico" name="correo_electronico">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="cuotacable">Cuota Cable:</label>
                            <input type="number" step="0.01" class="form-control" id="cuotacable" name="cuotacable" value="0.00" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="cuotainternet">Cuota Internet:</label>
                            <input type="number" step="0.01" class="form-control" id="cuotainternet" name="cuotainternet" value="0.00" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="cuotacableinternet">Cuota Cable + Internet:</label>
                            <input type="number" step="0.01" class="form-control" id="cuotacableinternet" name="cuotacableinternet" value="0.00" min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Registrar Cliente</button>
                </form>
            </div>
        </div>

        <!-- Tabla para Mostrar Clientes -->
        <div class="card">
            <div class="card-header bg-info text-white rounded-top">
                <h2 class="mb-0">Lista de Clientes</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>DNI</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Dirección</th>
                                    <th>Email</th>
                                    <th>Cuota Cable</th>
                                    <th>Cuota Internet</th>
                                    <th>Cuota C+I</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['id']); ?></td>
                                        <td><?php echo htmlspecialchars($client['dni']); ?></td>
                                        <td><?php echo htmlspecialchars($client['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($client['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($client['direccion'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($client['correo_electronico'] ?? 'N/A'); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($client['cuotacable']), 2, ',', '.'); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($client['cuotainternet']), 2, ',', '.'); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($client['cuotacableinternet']), 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($client['fecha_registro']); ?></td>
                                        <td>
                                            <!-- Botón para Abrir Modal de Edición -->
                                            <button type="button" class="btn btn-info btn-sm rounded-pill" data-toggle="modal" data-target="#editClientModal"
                                                data-id="<?php echo $client['id']; ?>"
                                                data-dni="<?php echo htmlspecialchars($client['dni']); ?>"
                                                data-nombre="<?php echo htmlspecialchars($client['nombre']); ?>"
                                                data-apellido="<?php echo htmlspecialchars($client['apellido']); ?>"
                                                data-direccion="<?php echo htmlspecialchars($client['direccion']); ?>"
                                                data-correo_electronico="<?php echo htmlspecialchars($client['correo_electronico']); ?>"
                                                data-cuotacable="<?php echo htmlspecialchars($client['cuotacable']); ?>"
                                                data-cuotainternet="<?php echo htmlspecialchars($client['cuotainternet']); ?>"
                                                data-cuotacableinternet="<?php echo htmlspecialchars($client['cuotacableinternet']); ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <!-- Botón de Eliminar -->
                                            <a href="clients_ui.php?action=delete_client&id=<?php echo $client['id']; ?>"
                                               class="btn btn-danger btn-sm rounded-pill"
                                               onclick="return confirm('¿Estás seguro de que quieres eliminar este cliente? Esta acción es irreversible.');">
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

    <!-- Modal para Editar Cliente -->
    <div class="modal fade" id="editClientModal" tabindex="-1" role="dialog" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-primary text-white rounded-top-lg">
                    <h5 class="modal-title" id="editClientModalLabel">Editar Cliente</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="clients_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_client">
                        <input type="hidden" name="client_id" id="edit_client_id">
                        <div class="form-group">
                            <label for="edit_dni">DNI:</label>
                            <input type="text" class="form-control" id="edit_dni" name="dni_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_nombre">Nombre:</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_apellido">Apellido:</label>
                            <input type="text" class="form-control" id="edit_apellido" name="apellido_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_direccion">Dirección:</label>
                            <input type="text" class="form-control" id="edit_direccion" name="direccion_edit">
                        </div>
                        <div class="form-group">
                            <label for="edit_correo_electronico">Correo Electrónico:</label>
                            <input type="email" class="form-control" id="edit_correo_electronico" name="correo_electronico_edit">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="edit_cuotacable">Cuota Cable:</label>
                                <input type="number" step="0.01" class="form-control" id="edit_cuotacable" name="cuotacable_edit" min="0">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="edit_cuotainternet">Cuota Internet:</label>
                                <input type="number" step="0.01" class="form-control" id="edit_cuotainternet" name="cuotainternet_edit" min="0">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="edit_cuotacableinternet">Cuota C+I:</label>
                                <input type="number" step="0.01" class="form-control" id="edit_cuotacableinternet" name="cuotacableinternet_edit" min="0">
                            </div>
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
        // Script para pasar datos al modal de edición de cliente
        $('#editClientModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que disparó el modal
            var id = button.data('id');
            var dni = button.data('dni');
            var nombre = button.data('nombre');
            var apellido = button.data('apellido');
            var direccion = button.data('direccion');
            var correo_electronico = button.data('correo_electronico');
            var cuotacable = button.data('cuotacable');
            var cuotainternet = button.data('cuotainternet');
            var cuotacableinternet = button.data('cuotacableinternet');

            var modal = $(this);
            modal.find('.modal-body #edit_client_id').val(id);
            modal.find('.modal-body #edit_dni').val(dni);
            modal.find('.modal-body #edit_nombre').val(nombre);
            modal.find('.modal-body #edit_apellido').val(apellido);
            modal.find('.modal-body #edit_direccion').val(direccion);
            modal.find('.modal-body #edit_correo_electronico').val(correo_electronico);
            modal.find('.modal-body #edit_cuotacable').val(cuotacable);
            modal.find('.modal-body #edit_cuotainternet').val(cuotainternet);
            modal.find('.modal-body #edit_cuotacableinternet').val(cuotacableinternet);
        });
    </script>
</body>
</html>
