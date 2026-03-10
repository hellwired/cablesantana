<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * clients_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para las operaciones CRUD
 * de la tabla 'cliente', utilizando Bootstrap 5 para el diseño.
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

// Verificar roles permitidos
$allowed_roles = ['administrador', 'editor', 'visor'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: dashboard.php'); // Redirigir si no es un rol permitido
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Verificar token CSRF en peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Token inválido, detener la ejecución
        die('Error de validación CSRF.');
    }
}

require_once 'client_model.php'; // Incluir el modelo de cliente
require_once 'plan_model.php';   // Incluir el modelo de planes
require_once 'audit_model.php';   // Incluir el modelo de auditoría para logAuditAction

// Lógica para manejar las acciones de CRUD
$message = ['text' => '', 'type' => '']; // Para mostrar mensajes de éxito o error

// Obtener todos los planes para el formulario
$plans = getAllPlans();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Asegurarse de que el usuario tiene el rol adecuado para realizar cambios
    if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor') {
        switch ($_POST['action']) {
            case 'create_client':
                $result = createClient(
                    $_POST['dni'],
                    $_POST['nombre'],
                    $_POST['apellido'],
                    $_POST['direccion'] ?? null,
                    $_POST['correo_electronico'] ?? null,
                    (int) ($_POST['plan_id'] ?? 0),
                    $_POST['notas_cliente'] ?? null,
                    $_POST['telefono'] ?? null,
                    $_POST['whatsapp_apikey'] ?? null
                );
                if ($result === 'DUPLICATE_DNI') {
                    $message = ['text' => 'Error: Ya existe un cliente con ese DNI.', 'type' => 'danger'];
                } elseif ($result) {
                    $message = ['text' => 'Cliente creado con éxito.', 'type' => 'success'];
                } else {
                    $message = ['text' => 'Error al crear el cliente.', 'type' => 'danger'];
                }
                break;

            case 'update_client':
                $client_id = (int) $_POST['client_id'];
                $data_to_update = [
                    'dni' => $_POST['dni_edit'],
                    'nombre' => $_POST['nombre_edit'],
                    'apellido' => $_POST['apellido_edit'],
                    'direccion' => $_POST['direccion_edit'] ?? null,
                    'correo_electronico' => $_POST['correo_electronico_edit'] ?? null,
                    'notas_cliente' => $_POST['notas_cliente_edit'] ?? null,
                    'telefono' => $_POST['telefono_edit'] ?? null,
                    'whatsapp_apikey' => $_POST['whatsapp_apikey_edit'] ?? null
                ];

                if (updateClient($client_id, $data_to_update)) {
                    $updated = true;
                } else {
                    $updated = false; // Could remain false if only plan changed
                }

                // Update Plan if provided
                if (isset($_POST['plan_id_edit']) && $_POST['plan_id_edit'] > 0) {
                    if (updateClientSubscriptionPlan($client_id, (int) $_POST['plan_id_edit'])) {
                        $updated = true;
                    }
                }

                if ($updated) {
                    $message = ['text' => 'Cliente actualizado con éxito.', 'type' => 'success'];
                } else {
                    $message = ['text' => 'Error al actualizar el cliente o no se hicieron cambios.', 'type' => 'danger'];
                }
                break;

            case 'delete_client':
                if ($_SESSION['rol'] === 'administrador') {
                    $client_id = (int) $_POST['id'];
                    if (deleteClient($client_id)) {
                        $message = ['text' => 'Cliente eliminado con éxito.', 'type' => 'success'];
                    } else {
                        $message = ['text' => 'Error al eliminar el cliente.', 'type' => 'danger'];
                    }
                }
                break;
        }
    }
}

// Obtener clientes (Búsqueda o Todos)
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($searchTerm)) {
    $clients = searchClients($searchTerm);
} else {
    // Limit default view to 50 for performance? Or just show all for now as requested.
    $clients = getAllClients();
}

require_once 'header.php';
?>
<!-- Quill Theme CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<div class="container-fluid px-4">
    <h1 class="text-center my-4">Gestión de Clientes</h1>

    <?php if (!empty($message['text'])): ?>
        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $message['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor'): ?>
        <!-- Formulario para Crear Cliente -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h2 class="mb-0">Registrar Nuevo Cliente</h2>
            </div>
            <div class="card-body">
                <form action="clients_ui.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="create_client">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="dni" class="form-label">DNI:</label>
                            <input type="text" class="form-control" id="dni" name="dni" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="apellido" class="form-label">Apellido:</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección:</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>
                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label">Correo Electrónico:</label>
                        <input type="email" class="form-control" id="correo_electronico" name="correo_electronico">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Telefono (WhatsApp):</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="+549XXXXXXXXXX">
                            <small class="text-muted">Formato internacional. Ej: +5491112345678</small>
                        </div>
                        <div class="col-md-6">
                            <label for="whatsapp_apikey" class="form-label">
                                WhatsApp API Key
                                <i class="fas fa-question-circle text-info" data-bs-toggle="tooltip" data-bs-placement="top"
                                   title="Para obtener el API Key, el cliente debe enviar un mensaje a CallMeBot (+34 644 71 80 27) con el texto: I allow callmebot to send me messages. Luego recibira su API key."></i>
                            </label>
                            <input type="text" class="form-control" id="whatsapp_apikey" name="whatsapp_apikey" placeholder="API Key de CallMeBot">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notas_cliente" class="form-label">Notas del Cliente:</label>
                        <div id="editor-create" style="height: 150px;"></div>
                        <input type="hidden" name="notas_cliente" id="notas_cliente_create">
                    </div>
                    <div class="mb-3">
                        <label for="plan_id" class="form-label">Plan Contratado:</label>
                        <select class="form-select" id="plan_id" name="plan_id" required>
                            <option value="" disabled selected>Seleccione un plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo htmlspecialchars($plan['nombre_plan']) . ' ($' . number_format($plan['precio_mensual'], 2) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrar Cliente</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table for Showing Clients -->
    
    <!-- Search Bar -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="clients_ui.php" method="GET" class="row g-3 align-items-center">
                <div class="col-auto flex-grow-1">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" name="search" placeholder="Buscar por DNI, Nombre, Apellido, Email o Dirección..." value="<?php echo htmlspecialchars($searchTerm); ?>" autocomplete="off">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="clients_ui.php" class="btn btn-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
    <!-- ... (header continues) ... -->
        <div class="card-header bg-info text-white rounded-top">
            <h2 class="mb-0">Lista de Clientes</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($clients)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="clientsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Dirección</th>
                                <th>Email</th>
                                <th>Fecha Registro</th>
                                <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor'): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <?php include 'client_rows_partial.php'; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No hay clientes registrados.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor'): ?>
    <!-- Modal para Editar Cliente -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-primary text-white rounded-top">
                    <h5 class="modal-title" id="editClientModalLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="clients_ui.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_client">
                        <input type="hidden" name="client_id" id="edit_client_id">
                        <div class="mb-3">
                            <label for="edit_dni" class="form-label">DNI:</label>
                            <input type="text" class="form-control" id="edit_dni" name="dni_edit" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre_edit" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_apellido" class="form-label">Apellido:</label>
                            <input type="text" class="form-control" id="edit_apellido" name="apellido_edit" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_direccion" class="form-label">Dirección:</label>
                            <input type="text" class="form-control" id="edit_direccion" name="direccion_edit">
                        </div>
                        <div class="mb-3">
                            <label for="edit_correo_electronico" class="form-label">Correo Electrónico:</label>
                            <input type="email" class="form-control" id="edit_correo_electronico"
                                name="correo_electronico_edit">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_telefono" class="form-label">Telefono (WhatsApp):</label>
                                <input type="text" class="form-control" id="edit_telefono" name="telefono_edit" placeholder="+549XXXXXXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_whatsapp_apikey" class="form-label">
                                    WhatsApp API Key
                                    <i class="fas fa-question-circle text-info" data-bs-toggle="tooltip" data-bs-placement="top"
                                       title="Para obtener el API Key, el cliente debe enviar un mensaje a CallMeBot (+34 644 71 80 27) con el texto: I allow callmebot to send me messages."></i>
                                </label>
                                <input type="text" class="form-control" id="edit_whatsapp_apikey" name="whatsapp_apikey_edit">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_notas_cliente" class="form-label">Notas del Cliente:</label>
                            <div id="editor-edit" style="height: 150px;"></div>
                            <input type="hidden" name="notas_cliente_edit" id="notas_cliente_edit">
                        </div>
                        <!-- Plan Editing -->
                        <div class="mb-3">
                            <label for="edit_plan_id" class="form-label">Plan Contratado:</label>
                            <select class="form-select" id="edit_plan_id" name="plan_id_edit">
                                <option value="" disabled selected>Seleccione un plan</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo htmlspecialchars($plan['nombre_plan']) . ' ($' . number_format($plan['precio_mensual'], 2) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
<?php endif; ?>

<?php require_once 'footer.php'; ?>

<!-- Quill JS (Specific to this page) -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

        // Initialize DataTables
        $('#clientsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            }
        });

        // Client-side validation
        function validateDNI(dni) {
            return /^[0-9]+$/.test(dni);
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(String(email).toLowerCase());
        }

        const dniInput = document.getElementById('dni');
        const emailInput = document.getElementById('correo_electronico');
        const editDniInput = document.getElementById('edit_dni');
        const editEmailInput = document.getElementById('edit_correo_electronico');

        if (dniInput) {
            dniInput.addEventListener('input', function () {
                if (this.value && !validateDNI(this.value)) {
                    this.setCustomValidity('El DNI solo debe contener números.');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        if (emailInput) {
            emailInput.addEventListener('input', function () {
                if (this.value && !validateEmail(this.value)) {
                    this.setCustomValidity('Por favor, introduce un correo electrónico válido.');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        if (editDniInput) {
            editDniInput.addEventListener('input', function () {
                if (this.value && !validateDNI(this.value)) {
                    this.setCustomValidity('El DNI solo debe contener números.');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        if (editEmailInput) {
            editEmailInput.addEventListener('input', function () {
                if (this.value && !validateEmail(this.value)) {
                    this.setCustomValidity('Por favor, introduce un correo electrónico válido.');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        // Initialize Quill editors
        const quillCreate = new Quill('#editor-create', {
            theme: 'snow',
            placeholder: 'Escribe notas importantes aquí...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        quillCreate.on('text-change', function () {
            document.getElementById('notas_cliente_create').value = quillCreate.root.innerHTML;
        });

        var quillEdit = new Quill('#editor-edit', {
            theme: 'snow',
            placeholder: 'Edita las notas del cliente aquí...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        quillEdit.on('text-change', function () {
            document.getElementById('notas_cliente_edit').value = quillEdit.root.innerHTML;
        });

        // Modal script
        const editClientModal = document.getElementById('editClientModal');
        if (editClientModal) {
            editClientModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const dni = button.getAttribute('data-dni');
                const nombre = button.getAttribute('data-nombre');
                const apellido = button.getAttribute('data-apellido');
                const direccion = button.getAttribute('data-direccion');
                const correo_electronico = button.getAttribute('data-correo_electronico');
                const notas_cliente_attr = button.getAttribute('data-notas_cliente');
                const plan_id = button.getAttribute('data-plan_id');
                const telefono = button.getAttribute('data-telefono');
                const whatsapp_apikey = button.getAttribute('data-whatsapp_apikey');

                const modal = this;
                modal.querySelector('#edit_client_id').value = id;
                modal.querySelector('#edit_dni').value = dni;
                modal.querySelector('#edit_nombre').value = nombre;
                modal.querySelector('#edit_apellido').value = apellido;
                modal.querySelector('#edit_direccion').value = direccion;
                modal.querySelector('#edit_correo_electronico').value = correo_electronico;
                modal.querySelector('#edit_telefono').value = telefono || '';
                modal.querySelector('#edit_whatsapp_apikey').value = whatsapp_apikey || '';

                // Select Plan
                if (plan_id) {
                    modal.querySelector('#edit_plan_id').value = plan_id;
                } else {
                    modal.querySelector('#edit_plan_id').value = "";
                }

                // Decode HTML entities from the data attribute for Quill editor
                const tempTextarea = document.createElement('textarea');
                tempTextarea.innerHTML = notas_cliente_attr;
                const decoded_notas = tempTextarea.value;

                if (quillEdit) {
                    quillEdit.root.innerHTML = decoded_notas;
                    document.getElementById('notas_cliente_edit').value = decoded_notas;
                }
            });
        }
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('clientsTableBody');
    let debounceTimer;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value;
            
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetch('search_clients_ajax.php?search=' + encodeURIComponent(term))
                    .then(response => response.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                    })
                    .catch(error => console.error('Error in live search:', error));
            }, 300); // 300ms delay
        });
    }
});
</script>
<?php require_once 'footer.php'; ?>