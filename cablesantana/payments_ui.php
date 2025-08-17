<?php
/**
 * payments_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'pagos', utilizando Bootstrap 4 para el diseño.
 * Ahora los pagos se asocian directamente a los clientes.
 *
 * Requiere autenticación de usuario y rol 'editor' o 'administrador' para gestionar los pagos.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a la página de login si no hay sesión
    exit();
}

// Verificar permisos: Solo 'administrador' y 'editor' pueden gestionar pagos
$can_manage_payments = ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor');
if (!$can_manage_payments) {
    // Si no tiene permisos, redirigir a una página principal o de error
    header('Location: dashboard.php');
    exit();
}

require_once 'payment_model.php'; // Incluir el modelo de pagos
require_once 'client_model.php';  // Incluir el modelo de clientes

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de un nuevo pago
if (isset($_POST['action']) && $_POST['action'] === 'create_payment') {
    $cliente_id = $_POST['cliente_id'] ?? '';
    $monto = $_POST['monto'] ?? '';
    $fecha_pago = $_POST['fecha_pago'] ?? '';
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $referencia_pago = $_POST['referencia_pago'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if (!empty($cliente_id) && !empty($monto) && !empty($fecha_pago)) {
        $new_payment_id = createPayment($cliente_id, (float)$monto, $fecha_pago, $metodo_pago, $referencia_pago, $descripcion);
        if ($new_payment_id) {
            $message = ['type' => 'success', 'text' => 'Pago registrado exitosamente con ID: ' . $new_payment_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al registrar el pago.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Cliente, Monto, Fecha de Pago) son necesarios.'];
    }
}

// Manejar la actualización de un pago existente
if (isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $payment_id = $_POST['payment_id'] ?? 0;
    $update_data = [];

    if (isset($_POST['cliente_id_edit'])) {
        $update_data['cliente_id'] = $_POST['cliente_id_edit'];
    }
    if (isset($_POST['monto_edit'])) {
        $update_data['monto'] = (float)$_POST['monto_edit'];
    }
    if (isset($_POST['fecha_pago_edit'])) {
        $update_data['fecha_pago'] = $_POST['fecha_pago_edit'];
    }
    if (isset($_POST['metodo_pago_edit'])) {
        $update_data['metodo_pago'] = $_POST['metodo_pago_edit'];
    }
    if (isset($_POST['referencia_pago_edit'])) {
        $update_data['referencia_pago'] = $_POST['referencia_pago_edit'];
    }
    if (isset($_POST['descripcion_edit'])) {
        $update_data['descripcion'] = $_POST['descripcion_edit'];
    }

    if ($payment_id > 0 && !empty($update_data)) {
        $updated = updatePayment($payment_id, $update_data);
        if ($updated) {
            $message = ['type' => 'success', 'text' => 'Pago con ID ' . $payment_id . ' actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el pago con ID ' . $payment_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el pago.'];
    }
}

// Manejar la eliminación de un pago
if (isset($_GET['action']) && $_GET['action'] === 'delete_payment' && isset($_GET['id'])) {
    $payment_id = (int)$_GET['id'];
    if ($payment_id > 0) {
        $deleted = deletePayment($payment_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Pago con ID ' . $payment_id . ' eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el pago con ID ' . $payment_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de pago no válido para eliminar.'];
    }
}

// Obtener el término de búsqueda si existe
$search_term = $_GET['search_term'] ?? '';

// Obtener todos los clientes para los selectores
$all_clients = getAllClients();

// Obtener todos los pagos para mostrar en la tabla, aplicando el filtro de búsqueda si existe
$payments = getAllPayments($search_term);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestión de Pagos - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
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
                    <a class="nav-link" href="users_ui.php">Usuarios</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="payments_ui.php">Pagos <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="debts_ui.php">Deudas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clients_ui.php">Clientes</a>
                </li>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="audit_ui.php">Auditoría</a>
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
        <h1 class="text-center">Gestión de Pagos</h1>

        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h2 class="mb-0">Registrar Nuevo Pago</h2>
            </div>
            <div class="card-body">
                <div id="client-status-message" class="alert" style="display: none;"></div>
                <form action="payments_ui.php" method="POST">
                    <input type="hidden" name="action" value="create_payment">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cliente_id">Cliente:</label>
                            <select class="form-control" id="cliente_id" name="cliente_id" required>
                                <option value="" data-cuotacable="0" data-cuotainternet="0" data-cuotacableinternet="0">Seleccione un cliente</option>
                                <?php foreach ($all_clients as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['id']); ?>"
                                            data-cuotacable="<?php echo htmlspecialchars($client['cuotacable'] ?? 0); ?>"
                                            data-cuotainternet="<?php echo htmlspecialchars($client['cuotainternet'] ?? 0); ?>"
                                            data-cuotacableinternet="<?php echo htmlspecialchars($client['cuotacableinternet'] ?? 0); ?>">
                                        <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="monto">Monto:</label>
                            <input type="number" step="0.01" class="form-control" id="monto" name="monto" required min="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="fecha_pago">Fecha de Pago:</label>
                            <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="metodo_pago">Método de Pago:</label>
                            <select class="form-control" id="metodo_pago" name="metodo_pago">
                                <option value="">Seleccione un método</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Mercado Pago">Mercado Pago</option>
                                <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                                <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="referencia_pago">Referencia de Pago:</label>
                        <input type="text" class="form-control" id="referencia_pago" name="referencia_pago">
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Registrar Pago</button>
                </form>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="payments_ui.php" method="GET" class="form-inline">
                    <div class="form-group mb-2 mr-sm-2">
                        <label for="search_term" class="sr-only">Buscar Cliente:</label>
                        <input type="text" class="form-control" id="search_term" name="search_term" placeholder="Buscar por DNI, Nombre o Apellido" value="<?php echo htmlspecialchars($search_term); ?>" style="min-width: 300px;">
                    </div>
                    <button type="submit" class="btn btn-primary mb-2">Buscar</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="payments_ui.php" class="btn btn-secondary mb-2 ml-2">Limpiar Búsqueda</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white rounded-top">
                <h2 class="mb-0">Lista de Pagos</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($payments)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Fecha Pago</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Descripción</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['nombre'] . ' ' . $payment['apellido']); ?></td>
                                        <td>$<?php echo number_format(htmlspecialchars($payment['monto']), 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fecha_pago']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['metodo_pago'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['referencia_pago'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['descripcion'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fecha_registro']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm rounded-pill" data-toggle="modal" data-target="#editPaymentModal"
                                                data-id="<?php echo htmlspecialchars($payment['id']); ?>"
                                                data-cliente_id="<?php echo htmlspecialchars($payment['cliente_id']); ?>"
                                                data-monto="<?php echo htmlspecialchars($payment['monto']); ?>"
                                                data-fecha_pago="<?php echo htmlspecialchars($payment['fecha_pago']); ?>"
                                                data-metodo_pago="<?php echo htmlspecialchars($payment['metodo_pago']); ?>"
                                                data-referencia_pago="<?php echo htmlspecialchars($payment['referencia_pago']); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($payment['descripcion']); ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <a href="payments_ui.php?action=delete_payment&id=<?php echo htmlspecialchars($payment['id']); ?>"
                                               class="btn btn-danger btn-sm rounded-pill"
                                               onclick="return confirm('¿Estás seguro de que quieres eliminar este pago? Esta acción es irreversible.');">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay pagos registrados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-primary text-white rounded-top-lg">
                    <h5 class="modal-title" id="editPaymentModalLabel">Editar Pago</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="payments_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_payment">
                        <input type="hidden" name="payment_id" id="edit_payment_id">
                        <div class="form-group">
                            <label for="edit_cliente_id">Cliente:</label>
                            <select class="form-control" id="edit_cliente_id" name="cliente_id_edit" required>
                                <?php foreach ($all_clients as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['id']); ?>"><?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_monto">Monto:</label>
                            <input type="number" step="0.01" class="form-control" id="edit_monto" name="monto_edit" required min="0.01">
                        </div>
                        <div class="form-group">
                            <label for="edit_fecha_pago">Fecha de Pago:</label>
                            <input type="date" class="form-control" id="edit_fecha_pago" name="fecha_pago_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_metodo_pago">Método de Pago:</label>
                            <select class="form-control" id="edit_metodo_pago" name="metodo_pago_edit">
                                <option value="">Seleccione un método</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Mercado Pago">Mercado Pago</option>
                                <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                                <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_referencia_pago">Referencia de Pago:</label>
                            <input type="text" class="form-control" id="edit_referencia_pago" name="referencia_pago_edit">
                        </div>
                        <div class="form-group">
                            <label for="edit_descripcion">Descripción:</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion_edit" rows="3"></textarea>
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar Select2 en el selector de clientes del formulario principal
            $('#cliente_id').select2({
                placeholder: 'Buscar y seleccionar un cliente',
                theme: 'bootstrap4'
            });

            // Inicializar Select2 en el selector de clientes del modal de edición
            $('#edit_cliente_id').select2({
                placeholder: 'Buscar y seleccionar un cliente',
                theme: 'bootstrap4',
                dropdownParent: $('#editPaymentModal') // Importante para que la búsqueda funcione dentro del modal
            });

            // Script para actualizar el monto y verificar estado de deuda al seleccionar un cliente
            $('#cliente_id').on('change', function() {
                const clienteId = $(this).val();
                const formCard = $(this).closest('.card');
                const statusMessage = formCard.find('#client-status-message');
                const paymentForm = formCard.find('form');
                const formElements = paymentForm.find('input, select, textarea, button').not(this);

                // Reset form state
                formElements.prop('disabled', false);
                statusMessage.hide().removeClass('alert-info alert-danger');

                if (!clienteId) {
                    $('#monto').val(''); // Clear amount if no client is selected
                    return;
                }

                // 1. Update amount from data attributes
                const selectedOption = $(this).find('option:selected');
                const cuotaCable = parseFloat(selectedOption.data('cuotacable')) || 0;
                const cuotaInternet = parseFloat(selectedOption.data('cuotainternet')) || 0;
                const cuotaCableInternet = parseFloat(selectedOption.data('cuotacableinternet')) || 0;

                let monto = 0;
                if (cuotaCableInternet > 0) {
                    monto = cuotaCableInternet;
                } else {
                    monto = cuotaCable + cuotaInternet;
                }
                $('#monto').val(monto.toFixed(2));

                // 2. Check for pending debts via AJAX
                $.ajax({
                    url: 'check_client_status.php',
                    type: 'GET',
                    data: { cliente_id: clienteId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'al_dia') {
                            statusMessage.addClass('alert-info').html('Este cliente se encuentra al día. No se pueden registrar nuevos pagos.').show();
                            formElements.prop('disabled', true);
                        }
                        // If status is 'con_deuda', do nothing and keep the form enabled.
                    },
                    error: function() {
                        // In case of AJAX error, show a warning but keep the form enabled to be safe.
                        statusMessage.addClass('alert-danger').html('Advertencia: No se pudo verificar el estado de deudas del cliente.').show();
                    }
                });
            });
        });

        $('#editPaymentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var cliente_id = button.data('cliente_id');
            var monto = button.data('monto');
            var fecha_pago = button.data('fecha_pago');
            var metodo_pago = button.data('metodo_pago');
            var referencia_pago = button.data('referencia_pago');
            var descripcion = button.data('descripcion');

            var modal = $(this);
            modal.find('.modal-body #edit_payment_id').val(id);
            modal.find('.modal-body #edit_cliente_id').val(cliente_id).trigger('change'); // trigger 'change' para actualizar Select2
            modal.find('.modal-body #edit_monto').val(monto);
            modal.find('.modal-body #edit_fecha_pago').val(fecha_pago);
            modal.find('.modal-body #edit_metodo_pago').val(metodo_pago);
            modal.find('.modal-body #edit_referencia_pago').val(referencia_pago);
            modal.find('.modal-body #edit_descripcion').val(descripcion);
        });
    </script>
</body>
</html>