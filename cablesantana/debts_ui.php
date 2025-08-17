<?php
/**
 * debts_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'deudas', utilizando Bootstrap 4 para el diseño.
 * Incluye el modelo de pagos y deudas para interactuar con la base de datos.
 *
 * Requiere autenticación de usuario y rol 'editor' o 'administrador' para crear/editar/eliminar.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a la página de login si no hay sesión
    exit();
}

require_once 'client_model.php';    // Para obtener la lista de clientes para los selectores
require_once 'payment_model.php'; // Incluir el modelo de pagos y deudas

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// Verificar permisos: Solo 'administrador' y 'editor' pueden gestionar deudas
$can_manage_debts = ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor');
// Los roles 'visor' y 'cliente' solo pueden ver sus propias deudas
$is_viewer = ($_SESSION['rol'] === 'visor' || $_SESSION['rol'] === 'cliente');

// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de una nueva deuda
if (isset($_POST['action']) && $_POST['action'] === 'create_debt' && $can_manage_debts) {
        $cliente_id = $_POST['cliente_id'] ?? '';
    $concepto = $_POST['concepto'] ?? '';
    $monto_original = $_POST['monto_original'] ?? '';
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';

    if (!empty($cliente_id) && !empty($concepto) && !empty($monto_original) && !empty($fecha_vencimiento)) {
        $new_debt_id = createDebt($cliente_id, $concepto, (float)$monto_original, $fecha_vencimiento);
        if ($new_debt_id) {
            $message = ['type' => 'success', 'text' => 'Deuda registrada exitosamente con ID: ' . $new_debt_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al registrar la deuda.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Usuario, Concepto, Monto Original, Fecha de Vencimiento) son necesarios para registrar una deuda.'];
    }
}

// Manejar la actualización de una deuda existente
if (isset($_POST['action']) && $_POST['action'] === 'update_debt' && $can_manage_debts) {
    $debt_id = $_POST['debt_id'] ?? 0;
    $update_data = [];

    if (isset($_POST['cliente_id_edit'])) {
        $update_data['cliente_id'] = $_POST['cliente_id_edit'];
    }
    if (isset($_POST['concepto_edit'])) {
        $update_data['concepto'] = $_POST['concepto_edit'];
    }
    if (isset($_POST['monto_original_edit'])) {
        $update_data['monto_original'] = (float)$_POST['monto_original_edit'];
    }
    if (isset($_POST['monto_pendiente_edit'])) {
        $update_data['monto_pendiente'] = (float)$_POST['monto_pendiente_edit'];
    }
    if (isset($_POST['fecha_vencimiento_edit'])) {
        $update_data['fecha_vencimiento'] = $_POST['fecha_vencimiento_edit'];
    }
    if (isset($_POST['estado_edit'])) {
        $update_data['estado'] = $_POST['estado_edit'];
    }

    if ($debt_id > 0 && !empty($update_data)) {
        $updated = updateDebt($debt_id, $update_data);
        if ($updated) {
            // Después de actualizar, recalcular el estado
            updateDebtStatus($debt_id);
            $message = ['type' => 'success', 'text' => 'Deuda con ID ' . $debt_id . ' actualizada exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar la deuda con ID ' . $debt_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el deuda.'];
    }
}

// Manejar la eliminación de una deuda
if (isset($_GET['action']) && $_GET['action'] === 'delete_debt' && isset($_GET['id']) && $can_manage_debts) {
    $debt_id = (int)$_GET['id'];
    if ($debt_id > 0) {
        $deleted = deleteDebt($debt_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Deuda con ID ' . $debt_id . ' eliminada exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar la deuda con ID ' . $debt_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de deuda no válido para eliminar.'];
    }
}

// Manejar el procesamiento de un pago para una deuda específica
if (isset($_POST['action']) && $_POST['action'] === 'process_debt_payment' && $can_manage_debts) {
    $debt_id = $_POST['debt_id_payment'] ?? 0;
    $monto_pagado = $_POST['monto_pagado'] ?? 0;
    $fecha_pago = $_POST['fecha_pago_debt'] ?? date('Y-m-d');
    $metodo_pago = $_POST['metodo_pago_debt'] ?? null;
    $referencia_pago = $_POST['referencia_pago_debt'] ?? null;
    $descripcion = $_POST['descripcion_debt'] ?? null;

    if ($debt_id > 0 && $monto_pagado > 0) {
        // Usar el ID del usuario logueado para registrar el pago
        $usuario_que_paga_id = $_SESSION['user_id'];
        $processed = processPaymentForDebt($usuario_que_paga_id, $debt_id, (float)$monto_pagado, $fecha_pago, $metodo_pago, $referencia_pago, $descripcion);

        if ($processed) {
            $message = ['type' => 'success', 'text' => 'Pago de $' . number_format((float)$monto_pagado, 2, ',', '.') . ' aplicado a la deuda ID ' . $debt_id . ' exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al procesar el pago para la deuda ID ' . $debt_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Monto de pago o ID de deuda no válidos.'];
    }
}


// Obtener todos los clientes para los selectores
$all_clients = getAllClients();

// Obtener las deudas para mostrar en la tabla
if ($is_viewer) {
    // Si es un visor o cliente, solo puede ver sus propias deudas
    $debts = getDebtsByUserId($_SESSION['user_id']);
} else {
    // Administradores y editores pueden ver todas las deudas
    $debts = getAllDebts();
}

// Actualizar el estado de todas las deudas al cargar la página (opcional, se puede hacer con un cron job en producción)
foreach ($debts as $debt_item) {
    updateDebtStatus($debt_item['id']);
}
// Volver a cargar las deudas después de la posible actualización de estado
if ($is_viewer) {
    $debts = getDebtsByUserId($_SESSION['user_id']);
} else {
    $debts = getAllDebts();
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestión de Deudas - Cable Santana</title>
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
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #1e7e34;
            border-color: #1e7e34;
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
                <li class="nav-item active">
                    <a class="nav-link" href="debts_ui.php">Gestión de Deudas <span class="sr-only">(current)</span></a>
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
        <h1 class="text-center">Gestión de Deudas</h1>

        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($can_manage_debts): ?>
        <!-- Formulario para Crear Deuda -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h2 class="mb-0">Registrar Nueva Deuda</h2>
            </div>
            <div class="card-body">
                <form action="debts_ui.php" method="POST">
                    <input type="hidden" name="action" value="create_debt">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cliente_id">Cliente:</label>
                            <select class="form-control" id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccione un cliente</option>
                                <?php foreach ($all_clients as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['id']); ?>"><?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?> (DNI: <?php echo htmlspecialchars($client['dni']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="concepto">Concepto:</label>
                            <input type="text" class="form-control" id="concepto" name="concepto" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="monto_original">Monto Original:</label>
                            <input type="number" step="0.01" class="form-control" id="monto_original" name="monto_original" required min="0.01">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Registrar Deuda</button>
                </form>
            </div>
        </div>
        <?php endif; // Fin del formulario de creación para roles con permiso ?>

        <!-- Tabla para Mostrar Deudas -->
        <div class="card">
            <div class="card-header bg-info text-white rounded-top">
                <h2 class="mb-0">Lista de Deudas</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($debts)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Concepto</th>
                                    <th>Monto Original</th>
                                    <th>Monto Pendiente</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <?php if ($can_manage_debts): ?>
                                    <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($debts as $debt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($debt['id']); ?></td>
                                        <td><?php echo htmlspecialchars($debt['nombre_cliente'] . ' ' . $debt['apellido_cliente']); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($debt['monto_original']), 2, ',', '.'); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($debt['monto_pendiente']), 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($debt['fecha_vencimiento']); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($debt['estado']) {
                                                case 'pendiente': $badge_class = 'badge-warning'; break;
                                                case 'pagado': $badge_class = 'badge-success'; break;
                                                case 'vencido': $badge_class = 'badge-danger'; break;
                                                case 'parcialmente_pagado': $badge_class = 'badge-info'; break;
                                                default: $badge_class = 'badge-secondary'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $debt['estado']))); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($debt['fecha_creacion']); ?></td>
                                        <?php if ($can_manage_debts): ?>
                                        <td>
                                            <!-- Botón para Abrir Modal de Edición -->
                                            <button type="button" class="btn btn-info btn-sm rounded-pill" data-toggle="modal" data-target="#editDebtModal"
                                                data-id="<?php echo $debt['id']; ?>"
                                                data-cliente_id="<?php echo htmlspecialchars($debt['cliente_id']); ?>"
                                                data-concepto="<?php echo htmlspecialchars($debt['concepto']); ?>"
                                                data-monto_original="<?php echo htmlspecialchars($debt['monto_original']); ?>"
                                                data-monto_pendiente="<?php echo htmlspecialchars($debt['monto_pendiente']); ?>"
                                                data-fecha_vencimiento="<?php echo htmlspecialchars($debt['fecha_vencimiento']); ?>"
                                                data-estado="<?php echo htmlspecialchars($debt['estado']); ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <!-- Botón para Abrir Modal de Pago -->
                                            <button type="button" class="btn btn-success btn-sm rounded-pill" data-toggle="modal" data-target="#processPaymentModal"
                                                data-id="<?php echo $debt['id']; ?>"
                                                data-cliente_id="<?php echo htmlspecialchars($debt['cliente_id']); ?>"
                                                data-concepto="<?php echo htmlspecialchars($debt['concepto']); ?>"
                                                data-monto_pendiente="<?php echo htmlspecialchars($debt['monto_pendiente']); ?>">
                                                <i class="fas fa-dollar-sign"></i> Pagar
                                            </button>
                                            <!-- Botón de Eliminar -->
                                            <a href="debts_ui.php?action=delete_debt&id=<?php echo $debt['id']; ?>"
                                               class="btn btn-danger btn-sm rounded-pill"
                                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta deuda? Esta acción es irreversible.');">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay deudas registradas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($can_manage_debts): ?>
    <!-- Modal para Editar Deuda -->
    <div class="modal fade" id="editDebtModal" tabindex="-1" role="dialog" aria-labelledby="editDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-primary text-white rounded-top-lg">
                    <h5 class="modal-title" id="editDebtModalLabel">Editar Deuda</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="debts_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_debt">
                        <input type="hidden" name="debt_id" id="edit_debt_id">
                        <div class="form-group">
                            <label for="edit_cliente_id">Cliente:</label>
                            <select class="form-control" id="edit_cliente_id" name="cliente_id_edit" required>
                                <?php foreach ($all_clients as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['id']); ?>"><?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?> (DNI: <?php echo htmlspecialchars($client['dni']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_concepto">Concepto:</label>
                            <input type="text" class="form-control" id="edit_concepto" name="concepto_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_monto_original">Monto Original:</label>
                            <input type="number" step="0.01" class="form-control" id="edit_monto_original" name="monto_original_edit" required min="0.01">
                        </div>
                        <div class="form-group">
                            <label for="edit_monto_pendiente">Monto Pendiente:</label>
                            <input type="number" step="0.01" class="form-control" id="edit_monto_pendiente" name="monto_pendiente_edit" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="edit_fecha_vencimiento">Fecha de Vencimiento:</label>
                            <input type="date" class="form-control" id="edit_fecha_vencimiento" name="fecha_vencimiento_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_estado">Estado:</label>
                            <select class="form-control" id="edit_estado" name="estado_edit" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="pagado">Pagado</option>
                                <option value="vencido">Vencido</option>
                                <option value="parcialmente_pagado">Parcialmente Pagado</option>
                            </select>
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

    <!-- Modal para Procesar Pago de Deuda -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1" role="dialog" aria-labelledby="processPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-success text-white rounded-top-lg">
                    <h5 class="modal-title" id="processPaymentModalLabel">Registrar Pago a Deuda</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="debts_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="process_debt_payment">
                        <input type="hidden" name="debt_id_payment" id="payment_debt_id">
                        <div class="form-group">
                            <label>Usuario de la Deuda:</label>
                            <input type="text" class="form-control" id="payment_usuario_nombre" readonly>
                        </div>
                        <div class="form-group">
                            <label>Concepto de la Deuda:</label>
                            <input type="text" class="form-control" id="payment_concepto" readonly>
                        </div>
                        <div class="form-group">
                            <label>Monto Pendiente Actual:</label>
                            <input type="text" class="form-control" id="payment_monto_pendiente_actual" readonly>
                        </div>
                        <div class="form-group">
                            <label for="monto_pagado">Monto a Pagar:</label>
                            <input type="number" step="0.01" class="form-control" id="monto_pagado" name="monto_pagado" required min="0.01">
                        </div>
                        <div class="form-group">
                            <label for="fecha_pago_debt">Fecha de Pago:</label>
                            <input type="date" class="form-control" id="fecha_pago_debt" name="fecha_pago_debt" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="metodo_pago_debt">Método de Pago:</label>
                            <input type="text" class="form-control" id="metodo_pago_debt" name="metodo_pago_debt" placeholder="Ej. Efectivo, Transferencia">
                        </div>
                        <div class="form-group">
                            <label for="referencia_pago_debt">Referencia de Pago:</label>
                            <input type="text" class="form-control" id="referencia_pago_debt" name="referencia_pago_debt">
                        </div>
                        <div class="form-group">
                            <label for="descripcion_debt">Descripción (opcional):</label>
                            <textarea class="form-control" id="descripcion_debt" name="descripcion_debt" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success rounded-pill">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; // Fin del modal de pago para roles con permiso ?>


    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script para pasar datos al modal de edición de deuda
        $('#editDebtModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que disparó el modal
            var id = button.data('id');
            var usuario_id = button.data('usuario_id');
            var concepto = button.data('concepto');
            var monto_original = button.data('monto_original');
            var monto_pendiente = button.data('monto_pendiente');
            var fecha_vencimiento = button.data('fecha_vencimiento');
            var estado = button.data('estado');

            var modal = $(this);
            modal.find('.modal-body #edit_debt_id').val(id);
            modal.find('.modal-body #edit_cliente_id').val(cliente_id);
            modal.find('.modal-body #edit_concepto').val(concepto);
            modal.find('.modal-body #edit_monto_original').val(monto_original);
            modal.find('.modal-body #edit_monto_pendiente').val(monto_pendiente);
            modal.find('.modal-body #edit_fecha_vencimiento').val(fecha_vencimiento);
            modal.find('.modal-body #edit_estado').val(estado);
        });

        // Script para pasar datos al modal de procesamiento de pago
        $('#processPaymentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que disparó el modal
            var id = button.data('id');
            var usuario_id = button.data('usuario_id'); // No se usa directamente en el formulario, pero puede ser útil
            var concepto = button.data('concepto');
            var monto_pendiente = button.data('monto_pendiente');

            var modal = $(this);
            modal.find('.modal-body #payment_debt_id').val(id);
            // Obtener el nombre de usuario de la tabla para mostrarlo en el modal
            var username = button.closest('tr').find('td:nth-child(2)').text(); // Asumiendo que el nombre de usuario está en la segunda columna
            modal.find('.modal-body #payment_usuario_nombre').val(username);
            modal.find('.modal-body #payment_concepto').val(concepto);
            modal.find('.modal-body #payment_monto_pendiente_actual').val(monto_pendiente);
            modal.find('.modal-body #monto_pagado').val(monto_pendiente); // Sugerir el monto pendiente por defecto
        });
    </script>
</body>
</html>
