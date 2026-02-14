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
require_once 'audit_model.php';   // Incluir el modelo de auditoría
require_once 'plan_model.php';    // Incluir el modelo de planes

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de un nuevo pago
if (isset($_POST['action']) && $_POST['action'] === 'create_payment') {
    $cliente_id = $_POST['cliente_id'] ?? '';

    // Lógica para asignar plan al vuelo si se seleccionó uno
    if (isset($_POST['new_plan_id']) && !empty($_POST['new_plan_id']) && !empty($cliente_id)) {
        $new_plan_id = (int) $_POST['new_plan_id'];
        updateClientSubscriptionPlan($cliente_id, $new_plan_id);
    }

    $monto = $_POST['monto'] ?? '';
    $fecha_pago = $_POST['fecha_pago'] ?? '';
    // Append current time to the date to avoid 00:00:00
    if (!empty($fecha_pago)) {
        $fecha_pago .= ' ' . date('H:i:s');
    }
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $referencia_pago = $_POST['referencia_pago'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if (!empty($cliente_id) && !empty($monto) && !empty($fecha_pago)) {
        try {
            $invoice_ids = $_POST['invoice_ids'] ?? [];

            if (!empty($invoice_ids) && is_array($invoice_ids)) {
                // MULTIPLE INVOICE LOGIC
                $metodo_pago_id = (int)$_POST['metodo_pago']; // Usar ID directo del form
                $payments_created = 0;
                $conn = connectDB(); // Open connection for validation

                foreach ($invoice_ids as $inv_id) {
                    // Verify invoice belongs to client and get amount
                    $stmt_v = $conn->prepare("SELECT monto FROM facturas WHERE id = ? AND cliente_id = ?");
                    $stmt_v->bind_param("ii", $inv_id, $cliente_id);
                    $stmt_v->execute();
                    $res_v = $stmt_v->get_result();

                    if ($res_v->num_rows > 0) {
                        $inv_data = $res_v->fetch_assoc();
                        $amount_to_pay = $inv_data['monto'];

                        // Create Payment
                        $pid = createPayment($inv_id, (float) $amount_to_pay, $fecha_pago, 'exitoso', $metodo_pago_id, $referencia_pago, $descripcion);
                        if ($pid) {
                            updateInvoiceStatusBasedOnBalance($inv_id);
                            $payments_created++;
                            // Audit per payment
                            logAuditAction($_SESSION['user_id'] ?? null, 'Pago de Factura', 'pagos', $pid, null, "FacID: $inv_id, Monto: $amount_to_pay");
                        }
                    }
                    $stmt_v->close();
                }
                closeDB($conn);

                if ($payments_created > 0) {
            $message = ['type' => 'success', 'text' => "Se registraron $payments_created pagos correctamente."];
                } else {
                    $message = ['type' => 'danger', 'text' => 'No se pudo procesar ningún pago. Verifique los datos.'];
                }
            } else {
                    // SINGLE / ADVANCE PAYMENT LOGIC (Fallback)
                    $invoice = getOldestUnpaidInvoice($cliente_id);

                    if ($invoice) {
                        // Usar ID directo
                        $metodo_pago_id = (int)$_POST['metodo_pago'];

                    $new_payment_id = createPayment($invoice['id'], (float) $monto, $fecha_pago, 'exitoso', $metodo_pago_id, $referencia_pago, $descripcion);
                    if ($new_payment_id) {
                        updateInvoiceStatusBasedOnBalance($invoice['id']);
                        $_SESSION['message'] = ['type' => 'success', 'text' => 'Pago registrado exitosamente (Modo Automático).'];
                        header('Location: payments_ui.php');
                        exit();
                    }
                } else {
                    // Si no hay factura pendiente, intentamos crear un pago adelantado
                    $metodo_pago_id = (int)$_POST['metodo_pago'];
                    $new_payment_id = createAdvanceInvoice($cliente_id, (float) $monto, $fecha_pago, $metodo_pago_id, $referencia_pago, $descripcion);

                    if ($new_payment_id) {
                        $userId = $_SESSION['user_id'] ?? null;
                        logAuditAction($userId, 'Pago Adelantado', 'pagos', $new_payment_id, null, "Monto: $monto, ClienteID: $cliente_id");
                        $_SESSION['message'] = ['type' => 'success', 'text' => 'Pago adelantado registrado exitosamente.'];
                        header('Location: payments_ui.php');
                        exit();
                    } else {
                        $message = ['type' => 'danger', 'text' => 'Error al registrar el pago adelantado.'];
                    }
                }
            }
            // For multiple payments loop logic previously separate
            if (isset($payments_created) && $payments_created > 0) {
                 $_SESSION['message'] = ['type' => 'success', 'text' => "Se registraron $payments_created pagos correctamente."];
                 header('Location: payments_ui.php');
                 exit();
            }

        } catch (Throwable $e) {
            error_log("Error CRITICO en create_payment: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            $message = ['type' => 'danger', 'text' => 'Error interno del servidor: ' . $e->getMessage()];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Cliente, Monto, Fecha de Pago) son necesarios.'];
    }
}

// Manejar la actualización de un pago existente
if (isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $payment_id = $_POST['payment_id'] ?? 0;
    $update_data = [];

    // if (isset($_POST['cliente_id_edit'])) {
    //    $update_data['cliente_id'] = $_POST['cliente_id_edit'];
    // }
    if (isset($_POST['monto_edit'])) {
        $update_data['monto'] = (float) $_POST['monto_edit'];
    }
    if (isset($_POST['fecha_pago_edit'])) {
        $fecha_temp = $_POST['fecha_pago_edit'];
        // Si viene solo fecha YYYY-MM-DD, le agregamos la hora actual
        if (strlen($fecha_temp) == 10) {
            $fecha_temp .= ' ' . date('H:i:s');
        }
        $update_data['fecha_pago'] = $fecha_temp;
    }
    if (isset($_POST['metodo_pago_edit'])) {
        $update_data['metodo_pago_id'] = (int)$_POST['metodo_pago_edit'];
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
            // AUDIT: Log payment update
            $userId = $_SESSION['user_id'] ?? null;
            logAuditAction($userId, 'Actualización de Pago', 'pagos', $payment_id, null, json_encode($update_data));

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Pago con ID ' . $payment_id . ' actualizado exitosamente.'];
            header('Location: payments_ui.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el pago con ID ' . $payment_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el pago.'];
    }
}

// Manejar la eliminación de un pago
if (isset($_GET['action']) && $_GET['action'] === 'delete_payment' && isset($_GET['id']) && $_SESSION['rol'] === 'administrador') {
    $payment_id = (int) $_GET['id'];
    if ($payment_id > 0) {
        $deleted = deletePayment($payment_id);
        if ($deleted) {
            // AUDIT: Log payment deletion
            $userId = $_SESSION['user_id'] ?? null;
            logAuditAction($userId, 'Eliminación de Pago', 'pagos', $payment_id, "ID: $payment_id", null);

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Pago con ID ' . $payment_id . ' eliminado exitosamente.'];
            header('Location: payments_ui.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el pago con ID ' . $payment_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de pago no válido para eliminar.'];
    }
}

// Manejar la asignación de plan
if (isset($_POST['action']) && $_POST['action'] === 'assign_plan') {
    $cliente_id_assign = $_POST['cliente_id_assign'] ?? 0;
    $plan_id_assign = $_POST['plan_id_assign'] ?? 0;

    if ($cliente_id_assign > 0 && $plan_id_assign > 0) {
        $res = updateClientSubscriptionPlan($cliente_id_assign, $plan_id_assign);
        if ($res) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Plan asignado correctamente.'];
            header('Location: payments_ui.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al asignar el plan.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Datos inválidos para asignación de plan.'];
    }
}

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Obtener el término de búsqueda si existe
$search_term = $_GET['search_term'] ?? '';

// Obtener todos los clientes (Mantener para modal de edición por compatibilidad, aunque idealmente debería ser AJAX también)
// El registro principal usará AJAX.
$all_clients = getAllClients();
$all_plans = getAllPlans(); 
$payment_methods = getAllPaymentMethods(); // Obtener métodos de pago dinámicos
// error_log("Methods count: " . count($payment_methods)); // DEBUG


// Obtener todos los pagos para mostrar en la tabla, aplicando el filtro de búsqueda si existe
$payments = getAllPayments($search_term);

require_once 'header.php';
?>
<!-- Select2 CSS (Specific to this page) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Custom styles for Select2 to match Bootstrap 5 */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }
</style>


<h1 class="text-center">Gestión de Pagos</h1>

<?php if (!empty($message['text'])): ?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $message['text']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <div class="row g-3">
                <div class="mb-3 col-md-6">
                    <label for="cliente_id">Cliente: <span id="client-plan-info" class="badge bg-secondary ms-2"
                            style="display:none;"></span></label>
                    <div class="input-group">
                        <select class="form-control" id="cliente_id" name="cliente_id" required style="width: 100%;">
                            <option value="">Buscar cliente...</option>
                            <!-- Options load via AJAX -->
                        </select>
                        <button class="btn btn-outline-warning" type="button" id="btn-assign-plan" style="display:none;"
                            data-bs-toggle="modal" data-bs-target="#assignPlanModal">
                            <i class="fas fa-edit"></i> Asignar Plan
                        </button>
                    </div>
                    
                    <!-- Selector de Plan para Clientes Nuevos/Sin Plan -->
                    <div id="plan-selection-container" class="mt-2 p-2 border border-warning rounded" style="display:none; background-color: #fff3cd;">
                        <label for="new_plan_id" class="form-label text-dark fw-bold mb-1"><i class="fas fa-exclamation-circle"></i> Cliente sin Plan - Seleccione uno:</label>
                        <select class="form-control form-control-sm border-warning" id="new_plan_id" name="new_plan_id">
                            <option value="">-- Seleccione Plan --</option>
                            <?php foreach ($all_plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['precio_mensual']; ?>">
                                    <?php echo htmlspecialchars($plan['nombre_plan']) . ' - $' . number_format($plan['precio_mensual'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Container for dynamic invoice checkboxes -->
                    <div id="invoices-container" class="mt-2" style="display:none;"></div>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="monto">Monto:</label>
                    <input type="number" step="0.01" class="form-control" id="monto" name="monto" required min="0.01">
                </div>
            </div>
            <div class="row g-3">
                <div class="mb-3 col-md-6">
                    <label for="fecha_pago">Fecha de Pago:</label>
                    <input type="date" class="form-control" id="fecha_pago" name="fecha_pago"
                        value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="metodo_pago">Método de Pago:</label>
                    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                        <option value="">Seleccione un método</option>
                        <?php foreach ($payment_methods as $pm): ?>
                            <option value="<?php echo $pm['id']; ?>"><?php echo htmlspecialchars($pm['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="referencia_pago">Referencia de Pago:</label>
                <input type="text" class="form-control" id="referencia_pago" name="referencia_pago">
            </div>
            <div class="mb-3">
                <label for="descripcion">Descripción:</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary w-100">Registrar Pago</button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-success w-100" id="btn-generate-qr">
                        <i class="fas fa-qrcode"></i> Generar QR de Pago
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="payments_ui.php" method="GET" class="form-inline">
            <div class="mb-3 mb-2 me-sm-2">
                <label for="search_term" class="sr-only">Buscar Cliente:</label>
                <input type="text" class="form-control" id="search_term" name="search_term"
                    placeholder="Buscar por DNI, Nombre o Apellido"
                    value="<?php echo htmlspecialchars($search_term); ?>" style="min-width: 300px;">
            </div>
            <button type="submit" class="btn btn-primary mb-2">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="payments_ui.php" class="btn btn-secondary mb-2 ms-2">Limpiar Búsqueda</a>
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
                    <thead class="table-dark">
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
                                <td><?php echo htmlspecialchars($payment['metodo_pago_nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['referencia_pago'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['descripcion'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['fecha_registro']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm rounded-pill" data-bs-toggle="modal"
                                        data-bs-target="#editPaymentModal"
                                        data-id="<?php echo htmlspecialchars($payment['id']); ?>"
                                        data-cliente_id="<?php echo htmlspecialchars($payment['cliente_id']); ?>"
                                        data-monto="<?php echo htmlspecialchars($payment['monto']); ?>"
                                        data-fecha_pago="<?php echo htmlspecialchars($payment['fecha_pago']); ?>"
                                        data-metodo_pago="<?php echo htmlspecialchars($payment['metodo_pago_id']); ?>"
                                        data-referencia_pago="<?php echo htmlspecialchars($payment['referencia_pago']); ?>"
                                        data-descripcion="<?php echo htmlspecialchars($payment['descripcion']); ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                        <a href="payments_ui.php?action=delete_payment&id=<?php echo htmlspecialchars($payment['id']); ?>"
                                            class="btn btn-danger btn-sm rounded-pill"
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este pago? Esta acción es irreversible.');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    <?php endif; ?>
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

<!-- Modal para QR de Mercado Pago -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="qrModalLabel"><i class="fas fa-qrcode"></i> Escanear para Pagar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="lead">Escanea este código con la App de Mercado Pago</p>
                <div id="qrcode-container" class="d-flex justify-content-center my-3"></div>
                <h4 class="font-weight-bold" id="qr-amount-display"></h4>
                <p class="text-muted small mt-2">El pago se acreditará instantáneamente.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición de Pago -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content rounded-lg">
            <div class="modal-header bg-primary text-white rounded-top-lg">
                <h5 class="modal-title" id="editPaymentModalLabel">Editar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="payments_ui.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="payment_id" id="edit_payment_id">
                    <div class="mb-3">
                        <label for="edit_cliente_id">Cliente (No editable):</label>
                        <select class="form-control" id="edit_cliente_id" name="cliente_id_edit" disabled>
                            <?php foreach ($all_clients as $client): ?>
                                <option value="<?php echo htmlspecialchars($client['id']); ?>">
                                    <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_monto">Monto:</label>
                        <input type="number" step="0.01" class="form-control" id="edit_monto" name="monto_edit" required
                            min="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="edit_fecha_pago">Fecha de Pago:</label>
                        <input type="date" class="form-control" id="edit_fecha_pago" name="fecha_pago_edit" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_metodo_pago">Método de Pago:</label>
                        <select class="form-control" id="edit_metodo_pago" name="metodo_pago_edit" required>
                            <option value="">Seleccione un método</option>
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?php echo $pm['id']; ?>"><?php echo htmlspecialchars($pm['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_referencia_pago">Referencia de Pago:</label>
                        <input type="text" class="form-control" id="edit_referencia_pago" name="referencia_pago_edit">
                    </div>
                    <div class="mb-3">
                        <label for="edit_descripcion">Descripción:</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion_edit"
                            rows="3"></textarea>
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

<!-- Modal para Asignar Plan -->
<div class="modal fade" id="assignPlanModal" tabindex="-1" aria-labelledby="assignPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="assignPlanModalLabel">Asignar Plan a Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="payments_ui.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_plan">
                    <input type="hidden" name="cliente_id_assign" id="assign_cliente_id_val">
                    <p><strong>Cliente:</strong> <span id="assign_cliente_nombre"></span></p>

                    <div class="mb-3">
                        <label for="plan_id_assign">Seleccionar Plan:</label>
                        <select class="form-control" name="plan_id_assign" id="plan_id_assign" required>
                            <option value="">-- Seleccione un Plan --</option>
                            <?php foreach ($all_plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo htmlspecialchars($plan['nombre_plan']) . ' - $' . number_format($plan['precio_mensual'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<!-- Select2 JS (Must load AFTER jQuery from footer.php) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- QRCode.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    $(document).ready(function () {
        // Inicializar Select2 con AJAX para el selector principal
        $('#cliente_id').select2({
            placeholder: 'Buscar por Nombre, DNI...',
            theme: 'bootstrap-5',
            ajax: {
                url: 'ajax_search_clients.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            templateResult: formatClientResult,
            templateSelection: formatClientSelection
        });

        function formatClientResult(client) {
            if (!client.id) {
                return client.text;
            }
            var $container = $(
                "<div class='d-flex justify-content-between align-items-center'>" +
                "<span>" + client.text + "</span>" +
                "</div>"
            );

            if (client.plan_nombre) {
                $container.append("<span class='badge bg-info rounded-pill ms-2'>" + client.plan_nombre + "</span>");
            } else if (client.id) {
                $container.append("<span class='badge bg-warning text-dark rounded-pill ms-2'>Sin Plan</span>");
            }

            return $container;
        }

        function formatClientSelection(client) {
            // Update UI based on selection here or in the 'select2:select' event, 
            // but we need the text returned here.
            return client.text || client.nombre + ' ' + client.apellido;
        }

        // Inicializar Select2 en el selector de clientes del modal de edición
        $('#edit_cliente_id').select2({
            placeholder: 'Buscar y seleccionar un cliente',
            theme: 'bootstrap-5',
            dropdownParent: $('#editPaymentModal')
        });

        // Script para actualizar el monto y verificar estado de deuda al seleccionar un cliente
        $('#cliente_id').on('change', function () {
            const clienteId = $(this).val();

            // Try to get data from Select2 (for AJAX loaded items)
            var data = $(this).select2('data')[0];

            // Plan Assignment UI Logic
            $('#client-plan-info').hide();
            $('#btn-assign-plan').hide();
            $('#assign_cliente_id_val').val(clienteId);

            // Default: Enable submit
            $('button[type="submit"]').prop('disabled', false);

            if (data && data.text) {
                $('#assign_cliente_nombre').text(data.text);
            }

            // Check plan_id (safest) or plan_nombre
            // Note: ajax_search_clients returning plan_id as 0 if null
            var hasPlan = (data && data.plan_id && data.plan_id != '0');

            if (hasPlan) {
                $('#client-plan-info').text('Plan: ' + data.plan_nombre).removeClass('bg-warning text-dark').addClass('bg-secondary text-white').show();
                
                // Ocultar selector de nuevo plan si ya tiene uno
                $('#plan-selection-container').slideUp();
                $('#new_plan_id').prop('required', false).val('');
                
            } else if (clienteId) {
                // No Plan Assigned
                $('#client-plan-info').text('⚠️ SIN PLAN').removeClass('bg-secondary text-white').addClass('bg-warning text-dark').show();
                
                // Mostrar selector de planes integrado
                $('#plan-selection-container').slideDown();
                $('#btn-assign-plan').hide(); // Ocultamos el botón antiguo del modal ya que usamos el selector integrado
                
                // Hacer requerido el plan
                $('#new_plan_id').prop('required', true);

                // No deshabilitamos el botón de pago, permitimos flujo: Seleccionar Plan -> Pagar
                $('button[type="submit"]').prop('disabled', false);
                
                // Limpiar monto para forzar selección
                $('#monto').val('');
            }

            const formCard = $(this).closest('.card');
            const statusMessage = formCard.find('#client-status-message');
            const paymentForm = formCard.find('form');
            const formElements = paymentForm.find('input, select, textarea, button').not(this).not('#btn-assign-plan');

            // Reset form state
            formElements.prop('disabled', false);
            statusMessage.hide().removeClass('alert-info alert-danger alert-warning alert-success');

            if (!clienteId) {
                $('#monto').val(''); // Clear amount if no client is selected
                return;
            }

            // Check for smart status via AJAX
            $.ajax({
                url: 'check_client_status.php',
                type: 'GET',
                data: { cliente_id: clienteId },
                dataType: 'json',
                success: function (response) {
                    console.log('Client Status Response:', response);

                    // RENDER INVOICES TABLE
                    const invoicesContainer = $('#invoices-container');
                    invoicesContainer.empty();

                    if (response.invoices && response.invoices.length > 0) {
                        let tableHtml = '<label class="form-label mt-2">Facturas Pendientes (Seleccione para pagar):</label>';
                        tableHtml += '<div class="table-responsive"><table class="table table-sm table-bordered table-hover">';
                        tableHtml += '<thead class="table-light"><tr><th width="5%"><input type="checkbox" id="check-all-invoices" checked></th><th>Periodo/Vencimiento</th><th>Monto</th></tr></thead>';
                        tableHtml += '<tbody>';

                        let calculatedTotal = 0;
                        response.invoices.forEach(inv => {
                            tableHtml += `<tr>
                                <td class="text-center">
                                    <input type="checkbox" class="invoice-checkbox" name="invoice_ids[]" value="${inv.id}" data-amount="${inv.monto}" checked>
                                </td>
                                <td>Vence: ${inv.fecha_vencimiento} <span class="badge bg-secondary">${inv.estado}</span></td>
                                <td class="text-end">$${parseFloat(inv.monto).toFixed(2)}</td>
                             </tr>`;
                            calculatedTotal += parseFloat(inv.monto);
                        });

                        tableHtml += '</tbody></table></div>';
                        invoicesContainer.html(tableHtml);
                        invoicesContainer.show();

                        // Set total and make read-only mainly to avoid confusion, 
                        // though we might allow manual override if partial payment is needed (but plan says full payment per invoice).
                        // Let's enforce full payment per invoice for now to keep it simple.
                        $('#monto').val(calculatedTotal.toFixed(2));
                        $('#monto').prop('readonly', true);

                    } else {
                        invoicesContainer.hide();
                        $('#monto').val(''); // Reset if no debts (will be set by 'al_dia' logic below if needed)
                        $('#monto').prop('readonly', false);
                    }

                    // Set suggested amount (Fallback if no invoices logic or for advance payment)
                    if (response.status === 'al_dia') {
                        $('#monto').val(parseFloat(response.amount).toFixed(2));
                        $('#monto').prop('readonly', false); // Allow edit for advance payments
                    } else if (response.invoice_count > 0) {
                        // Recalculate based on checkboxes (logic below)
                    }

                    // Handle Invoice Checkbox Toggle
                    $(document).on('change', '.invoice-checkbox, #check-all-invoices', function () {
                        if (this.id === 'check-all-invoices') {
                            $('.invoice-checkbox').prop('checked', $(this).prop('checked'));
                        }

                        let newTotal = 0;
                        $('.invoice-checkbox:checked').each(function () {
                            newTotal += parseFloat($(this).data('amount'));
                        });

                        // Add surcharges if applicable (simple logic: if status is risk/moroso, maybe add fixed surcharge? 
                        // For now let's just sum the invoice amounts. Surcharges in response.amount logic 
                        // might need to be applied as a separate line item or distributed.
                        // Ideally, surcharges should be separate invoices or added to the debt.
                        // Given current logic in check_client_status, surcharges are calculated on the fly.
                        // Let's stick to paying the INVOICE nominal amount for this iteration 
                        // and trust the backend to handle status updates.
                        // OR: If the plan implies paying raw debt + surcharge, we might be missing the surcharge here.

                        // REVISION: The previous 'response.amount' INCLUDED surcharges. 
                        // If we switch to invoice selection, we pay specific invoices. 
                        // Let's assume surcharges are handled separately or we just sum the invoice face value.
                        $('#monto').val(newTotal.toFixed(2));
                    });

                    // Handle BLOCKED state (Service Cutoff)
                    if (response.blocked === true) {
                        // formElements.prop('disabled', true); // Do NOT disable form, allow payment to restore service!
                        
                        statusMessage.removeClass('alert-info alert-danger alert-warning alert-success')
                            .addClass('alert-danger')
                            .html(`<i class="fas fa-ban me-1"></i> ${response.message}`)
                            .slideDown();
                        // return; // Do NOT return, allow valid invoice rendering
                    }

                    // ... (Alert logic same as before) ... 
                    let alertClass = 'alert-success';
                    let iconClass = 'fa-check-circle';

                    switch (response.status) {
                        // ... copy existing cases ...
                        case 'riesgo_corte': alertClass = 'alert-danger'; iconClass = 'fa-exclamation-triangle'; break;
                        case 'moroso_2': alertClass = 'alert-warning'; iconClass = 'fa-exclamation-circle'; break;
                        case 'moroso_1': alertClass = 'alert-warning'; iconClass = 'fa-clock'; break;
                        case 'al_dia': alertClass = 'alert-success'; iconClass = 'fa-check-circle'; break;
                    }

                    if (response.message) {
                        statusMessage.removeClass('alert-info alert-danger alert-warning alert-success')
                            .addClass(alertClass)
                            .html(`<i class="fas ${iconClass} me-1"></i> ${response.message}`)
                            .slideDown();
                    }
                }
            });
        });

        // Lógica para Generar QR
        $('#btn-generate-qr').on('click', function () {
            const monto = $('#monto').val();
            const clienteId = $('#cliente_id').val();
            const clienteNombre = $('#cliente_id option:selected').text().trim();

            if (!monto || monto <= 0) {
                alert('Por favor ingrese un monto válido para generar el QR.');
                return;
            }

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando...');

            $.ajax({
                url: 'generate_mp_preference.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    monto: monto,
                    concepto: 'Pago de Servicios - ' + clienteNombre,
                }),
                success: function (response) {
                    btn.prop('disabled', false).html(originalText);

                    if (response.error) {
                        alert('Error: ' + response.error);
                    } else if (response.qr_link) {
                        $('#qrcode-container').empty();
                        new QRCode(document.getElementById("qrcode-container"), {
                            text: response.qr_link,
                            width: 200,
                            height: 200
                        });
                        $('#qr-amount-display').text('$' + parseFloat(monto).toFixed(2));
                        $('#qrModal').modal('show');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html(originalText);
                    alert('Error de conexión al generar el QR.');
                }
            });
        });

        // Listener para cambio de plan en el selector integrado
        $('#new_plan_id').on('change', function() {
            var selectedOption = $(this).find(':selected');
            var price = selectedOption.data('price');
            if (price) {
                $('#monto').val(parseFloat(price).toFixed(2));
                // Opcional: Podríamos poner una descripción por defecto
                // $('#descripcion').val('Pago inicial - Plan ' + selectedOption.text().trim());
            } else {
                $('#monto').val('');
            }
        });

        // Use event delegation for dynamically loaded content or just standard listener
        document.addEventListener('DOMContentLoaded', function () {
            var editModal = document.getElementById('editPaymentModal');
            if (editModal) {
                // Listener if needed
            }
        });

        // Reverting to jQuery for modal event to properly fetch .data()
        // Ensure we target the modal correctly.
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
            // Must trigger simple 'change' for basic listeners, but for Select2 sometimes we need to be explicit if it doesn't pick up
            modal.find('.modal-body #edit_cliente_id').val(cliente_id).trigger('change');
            modal.find('.modal-body #edit_monto').val(monto);
            // fecha_pago often comes as "YYYY-MM-DD HH:MM:SS", input[type=date] needs "YYYY-MM-DD"
            var fechaSolo = fecha_pago ? fecha_pago.split(' ')[0] : '';
            modal.find('.modal-body #edit_fecha_pago').val(fechaSolo);
            modal.find('.modal-body #edit_metodo_pago').val(metodo_pago);
            modal.find('.modal-body #edit_referencia_pago').val(referencia_pago);
            modal.find('.modal-body #edit_descripcion').val(descripcion);
        });
    }); // End of document.ready
</script>
</body>

</html>