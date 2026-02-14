<?php
/**
 * plans_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'planes', utilizando Bootstrap 5 para el diseño.
 * Incluye el modelo de plan para interactuar con la base de datos.
 *
 * Requiere autenticación de usuario y rol 'administrador' para acceder.
 */

require_once 'header.php'; // Incluye sesión, header y navbar
require_once 'plan_model.php'; // Incluir el modelo de plan

// Verificar rol de administrador (seguridad adicional a la del header)
if ($_SESSION['rol'] !== 'administrador') {
    // Si no es administrador, redirigir o mostrar error
    echo "<div class='container mt-5'><div class='alert alert-danger'>Acceso denegado. Se requieren permisos de administrador.</div></div>";
    require_once 'footer.php';
    exit();
}

// Inicializar un array para mensajes de éxito o error
$message = ['type' => '', 'text' => ''];

// --- Lógica para manejar las operaciones CRUD ---

// Manejar la creación de un nuevo plan
if (isset($_POST['action']) && $_POST['action'] === 'create_plan') {
    $nombre_plan = $_POST['nombre_plan'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio_mensual = $_POST['precio_mensual'] ?? 0.00;
    $tipo_facturacion = $_POST['tipo_facturacion'] ?? '';
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (!empty($nombre_plan) && !empty($descripcion) && !empty($tipo_facturacion)) {
        $new_plan_id = createPlan($nombre_plan, $descripcion, $precio_mensual, $tipo_facturacion, $activo);
        if ($new_plan_id) {
            $message = ['type' => 'success', 'text' => 'Plan "' . htmlspecialchars($nombre_plan) . '" creado exitosamente con ID: ' . $new_plan_id . '.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al crear el plan.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Todos los campos obligatorios (Nombre del Plan, Descripción, Tipo de Facturación) son necesarios para crear un plan.'];
    }
}

// Manejar la actualización de un plan existente
if (isset($_POST['action']) && $_POST['action'] === 'update_plan') {
    $plan_id = $_POST['plan_id'] ?? 0;
    $update_data = [];

    if (isset($_POST['nombre_plan_edit'])) {
        $update_data['nombre_plan'] = $_POST['nombre_plan_edit'];
    }
    if (isset($_POST['descripcion_edit'])) {
        $update_data['descripcion'] = $_POST['descripcion_edit'];
    }
    if (isset($_POST['precio_mensual_edit'])) {
        $update_data['precio_mensual'] = $_POST['precio_mensual_edit'];
    }
    if (isset($_POST['tipo_facturacion_edit'])) {
        $update_data['tipo_facturacion'] = $_POST['tipo_facturacion_edit'];
    }
    $update_data['activo'] = isset($_POST['activo_edit']) ? 1 : 0;

    if ($plan_id > 0 && !empty($update_data)) {
        $updated = updatePlan($plan_id, $update_data);
        if ($updated) {
            $message = ['type' => 'success', 'text' => 'Plan con ID ' . $plan_id . ' actualizado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al actualizar el plan con ID ' . $plan_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'No se proporcionaron datos válidos para actualizar el plan.'];
    }
}

// Manejar la eliminación de un plan
if (isset($_GET['action']) && $_GET['action'] === 'delete_plan' && isset($_GET['id'])) {
    $plan_id = (int)$_GET['id'];
    if ($plan_id > 0) {
        $deleted = deletePlan($plan_id);
        if ($deleted) {
            $message = ['type' => 'success', 'text' => 'Plan con ID ' . $plan_id . ' eliminado exitosamente.'];
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al eliminar el plan con ID ' . $plan_id . '.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'ID de plan no válido para eliminar.'];
    }
}

// Obtener todos los planes para mostrar en la tabla
$plans = getAllPlans();

?>

<!-- Contenido Principal -->
<div class="row">
    <div class="col-12">
        <h1 class="text-center mb-4 text-dark display-6 fw-bold">Gestión de Planes</h1>

        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario para Crear Plan -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Plan</h5>
            </div>
            <div class="card-body">
                <form action="plans_ui.php" method="POST">
                    <input type="hidden" name="action" value="create_plan">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre_plan" class="form-label">Nombre del Plan:</label>
                            <input type="text" class="form-control" id="nombre_plan" name="nombre_plan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="precio_mensual" class="form-label">Precio Mensual:</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="precio_mensual" name="precio_mensual" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción:</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_facturacion" class="form-label">Tipo de Facturación:</label>
                            <select class="form-select" id="tipo_facturacion" name="tipo_facturacion" required>
                                <option value="">Seleccione tipo</option>
                                <option value="fija">Fija</option>
                                <option value="variable">Variable</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Crear Plan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla para Mostrar Planes -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Lista de Planes</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($plans)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Plan</th>
                                    <th>Descripción</th>
                                    <th>Precio Mensual</th>
                                    <th>Tipo Facturación</th>
                                    <th>Activo</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($plan['nombre_plan']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($plan['descripcion']); ?></td>
                                        <td><span class="badge bg-primary fs-6">$<?php echo number_format(htmlspecialchars($plan['precio_mensual']), 2); ?></span></td>
                                        <td><?php echo htmlspecialchars($plan['tipo_facturacion']); ?></td>
                                        <td>
                                            <?php if ($plan['activo']): ?>
                                                <span class="badge bg-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($plan['fecha_creacion']); ?></td>
                                        <td>
                                            <div class="d-grid gap-2 d-md-block">
                                                <!-- Botón para Abrir Modal de Edición -->
                                                <button type="button" class="btn btn-info btn-sm text-white" 
                                                    data-bs-toggle="modal" data-bs-target="#editPlanModal"
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    data-nombre_plan="<?php echo htmlspecialchars($plan['nombre_plan']); ?>"
                                                    data-descripcion="<?php echo htmlspecialchars($plan['descripcion']); ?>"
                                                    data-precio_mensual="<?php echo htmlspecialchars($plan['precio_mensual']); ?>"
                                                    data-tipo_facturacion="<?php echo htmlspecialchars($plan['tipo_facturacion']); ?>"
                                                    data-activo="<?php echo $plan['activo'] ? '1' : '0'; ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <!-- Botón de Eliminar -->
                                                <a href="plans_ui.php?action=delete_plan&id=<?php echo $plan['id']; ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este plan? Esta acción es irreversible.');">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        No hay planes registrados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Plan -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editPlanModalLabel">Editar Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="plans_ui.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_plan">
                    <input type="hidden" name="plan_id" id="edit_plan_id">
                    <div class="mb-3">
                        <label for="edit_nombre_plan" class="form-label">Nombre del Plan:</label>
                        <input type="text" class="form-control" id="edit_nombre_plan" name="nombre_plan_edit" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion_edit" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_precio_mensual" class="form-label">Precio Mensual:</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" id="edit_precio_mensual" name="precio_mensual_edit" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipo_facturacion" class="form-label">Tipo de Facturación:</label>
                        <select class="form-select" id="edit_tipo_facturacion" name="tipo_facturacion_edit" required>
                            <option value="fija">Fija</option>
                            <option value="variable">Variable</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_activo" name="activo_edit" value="1">
                        <label class="form-check-label" for="edit_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Incluir el footer que carga jQuery, Bootstrap Bundle y DataTables
require_once 'footer.php'; 
?>

<script>
    // Script para pasar datos al modal de edición
    // Esperamos a que el documento esté listo (jQuery ya cargado en footer)
    $(document).ready(function() {
        var editModal = document.getElementById('editPlanModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-id');
            var nombre_plan = button.getAttribute('data-nombre_plan');
            var descripcion = button.getAttribute('data-descripcion');
            var precio_mensual = button.getAttribute('data-precio_mensual');
            var tipo_facturacion = button.getAttribute('data-tipo_facturacion');
            var activo = button.getAttribute('data-activo');

            // Update the modal's content.
            var modalBodyInputId = editModal.querySelector('#edit_plan_id');
            var modalBodyInputNombre = editModal.querySelector('#edit_nombre_plan');
            var modalBodyInputDescripcion = editModal.querySelector('#edit_descripcion');
            var modalBodyInputPrecio = editModal.querySelector('#edit_precio_mensual');
            var modalBodyInputTipo = editModal.querySelector('#edit_tipo_facturacion');
            var modalBodyInputActivo = editModal.querySelector('#edit_activo');

            modalBodyInputId.value = id;
            modalBodyInputNombre.value = nombre_plan;
            modalBodyInputDescripcion.value = descripcion;
            modalBodyInputPrecio.value = precio_mensual;
            modalBodyInputTipo.value = tipo_facturacion;
            modalBodyInputActivo.checked = (activo == '1');
        });
    });
</script>