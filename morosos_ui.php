<?php
/**
 * morosos_ui.php
 *
 * Muestra el listado de clientes con 1 o más mes de deuda.
 * Permite buscar por DNI/Nombre y configurar recargo (admin).
 */

session_start();

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar roles permitidos
$allowed_roles = ['administrador', 'editor'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: dashboard.php');
    exit();
}

require_once 'morosos_model.php';
require_once 'notificacion_model.php';

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$messageType = '';

// Handle configuration update (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_config') {
    if ($_SESSION['rol'] === 'administrador') {
        $surchargeVal = $_POST['surcharge_value'] ?? 0;
        $graceVal = $_POST['grace_days'] ?? 0;

        $ok1 = updateSurchargeValue($surchargeVal);
        $ok2 = updateGraceDays($graceVal);

        if ($ok1 && $ok2) {
            $message = "Configuración actualizada correctamente.";
            $messageType = "success";
        } else {
            $message = "Error al actualizar la configuración.";
            $messageType = "danger";
        }
    }
}

// Get params
$searchTerm = $_GET['search'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$debtors = getDebtors($searchTerm);
$surcharge = getSurchargeValue();
$graceDays = getGraceDays();

require_once 'header.php';
?>

<div class="row">
    <!-- Configuration Card (Admin Only) -->
    <?php if ($_SESSION['rol'] === 'administrador'): ?>
        <div class="col-12 mb-4">
            <div class="card shadow border-left-primary">
                <div class="card-header bg-warning text-dark">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-cog"></i> Configuración de Cobranza</h6>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3 align-items-center">
                        <input type="hidden" name="action" value="update_config">

                        <!-- Recargo -->
                        <div class="col-auto">
                            <label for="surcharge_value" class="col-form-label fw-bold">Recargo ($):</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" step="0.01" class="form-control" id="surcharge_value"
                                name="surcharge_value" value="<?php echo $surcharge; ?>" style="width: 100px;">
                        </div>

                        <!-- Grace Days -->
                        <div class="col-auto">
                            <label for="grace_days" class="col-form-label fw-bold">Días Gracia:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" step="1" class="form-control" id="grace_days" name="grace_days"
                                value="<?php echo $graceDays; ?>" style="width: 80px;">
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">
                                Recargo: Monto extra por mes de deuda. <br>
                                Días Gracia: Días después del vencimiento antes de considerar la factura vencida.
                            </small>
                        </div>
                    </form>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> mt-3 py-2 mb-0">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Debtors List -->
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Listado de Morosos (1+ Mes Vencido)</h6>
                <div>
                    <button type="button" class="btn btn-success btn-sm" id="btnWhatsApp" onclick="previewNotifications()">
                        <i class="fab fa-whatsapp"></i> Enviar Notificaciones WhatsApp
                    </button>
                    <a href="notificaciones_ui.php" class="btn btn-outline-light btn-sm ms-1">
                        <i class="fas fa-history"></i> Historial
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search"
                            placeholder="Buscar por DNI, Nombre o Apellido..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <?php if ($searchTerm): ?>
                            <a href="morosos_ui.php" class="btn btn-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableMorosos" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>DNI</th>
                                <th>Dirección</th>
                                <th class="text-center">Fac. Pendientes</th>
                                <th class="text-end">Deuda Base</th>
                                <th class="text-end text-danger bg-light">A Pagar (+Recargo)</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($debtors)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No se encontraron clientes con deuda.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($debtors as $client):
                                    // Logic Change: Surcharge applies PER MONTH (per invoice owed)
                                    $monthsOwed = $client['facturas_adeudadas'];
                                    $totalSurcharge = $surcharge * $monthsOwed;
                                    $totalToPay = $client['total_deuda'] + $totalSurcharge;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($client['dni']); ?>
                                        </td>
                                        <td><small>
                                                <?php echo htmlspecialchars($client['direccion']); ?>
                                            </small></td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark rounded-pill px-2">
                                                <?php echo $monthsOwed; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            $
                                            <?php echo number_format($client['total_deuda'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-end fw-bold text-danger bg-light">
                                            $
                                            <?php echo number_format($totalToPay, 2, ',', '.'); ?>
                                            <?php if ($surcharge > 0): ?>
                                                <small class="d-block text-muted" style="font-size: 0.7em;">
                                                    ($<?php echo $surcharge; ?> x <?php echo $monthsOwed; ?> meses =
                                                    +$<?php echo number_format($totalSurcharge, 2); ?>)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="payments_ui.php?search_term=<?php echo urlencode($client['dni']); ?>"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-hand-holding-usd"></i> Cobrar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuracion de Template WhatsApp (Admin Only) -->
<?php if ($_SESSION['rol'] === 'administrador'):
    $whatsappTemplate = getWhatsAppTemplate();
?>
    <div class="col-12 mt-4">
        <div class="card shadow border-left-success">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold"><i class="fab fa-whatsapp"></i> Configuracion de Mensaje WhatsApp</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label for="whatsappTemplate" class="form-label fw-bold">Plantilla del mensaje:</label>
                    <textarea class="form-control" id="whatsappTemplate" rows="3"><?php echo htmlspecialchars($whatsappTemplate); ?></textarea>
                    <small class="text-muted">
                        Variables disponibles: <code>{nombre}</code> = Nombre completo del cliente,
                        <code>{facturas}</code> = Cantidad de facturas pendientes,
                        <code>{monto}</code> = Monto total de deuda
                    </small>
                </div>
                <button type="button" class="btn btn-success btn-sm" onclick="saveTemplate()">
                    <i class="fas fa-save"></i> Guardar Template
                </button>
                <span id="templateSaveResult" class="ms-2"></span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Preview de Notificaciones -->
<div class="modal fade" id="whatsappPreviewModal" tabindex="-1" aria-labelledby="whatsappPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="whatsappPreviewModalLabel">
                    <i class="fab fa-whatsapp"></i> Preview de Notificaciones WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewLoading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando preview...</p>
                </div>
                <div id="previewContent" style="display:none;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Los siguientes clientes recibiran un mensaje de WhatsApp:
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Telefono</th>
                                    <th>Facturas</th>
                                    <th>Deuda</th>
                                    <th>Mensaje</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                    <div id="previewEmpty" class="alert alert-warning" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        No hay clientes morosos con telefono y API key configurados, o ya fueron notificados en las ultimas 24 horas.
                    </div>
                </div>
                <div id="sendingProgress" style="display:none;">
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                        <p class="mt-2">Enviando mensajes... Esto puede tardar unos segundos.</p>
                        <div class="progress">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="sendProgressBar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <div id="sendResults" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnConfirmSend" onclick="sendNotifications()" style="display:none;">
                    <i class="fab fa-whatsapp"></i> Confirmar y Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<script>
    // DataTable init
    $(document).ready(function () {
        if ($.fn.DataTable) {
            $('#dataTableMorosos').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                searching: false,
                order: [[3, "desc"]]
            });
        }
    });

    var csrfToken = '<?php echo $csrf_token; ?>';

    function previewNotifications() {
        var modal = new bootstrap.Modal(document.getElementById('whatsappPreviewModal'));
        modal.show();

        document.getElementById('previewLoading').style.display = 'block';
        document.getElementById('previewContent').style.display = 'none';
        document.getElementById('sendingProgress').style.display = 'none';
        document.getElementById('sendResults').style.display = 'none';
        document.getElementById('btnConfirmSend').style.display = 'none';

        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'preview');

        fetch('ajax_send_notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('previewLoading').style.display = 'none';
            document.getElementById('previewContent').style.display = 'block';

            if (data.success && data.data.length > 0) {
                var tbody = document.getElementById('previewTableBody');
                tbody.innerHTML = '';
                data.data.forEach(function(item) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td><strong>' + escapeHtml(item.nombre) + '</strong></td>'
                        + '<td>' + escapeHtml(item.telefono) + '</td>'
                        + '<td class="text-center">' + item.facturas + '</td>'
                        + '<td class="text-end">$' + item.deuda + '</td>'
                        + '<td><small>' + escapeHtml(item.mensaje) + '</small></td>';
                    tbody.appendChild(tr);
                });
                document.getElementById('previewEmpty').style.display = 'none';
                document.getElementById('btnConfirmSend').style.display = 'inline-block';
            } else {
                document.getElementById('previewTableBody').innerHTML = '';
                document.getElementById('previewEmpty').style.display = 'block';
                document.getElementById('btnConfirmSend').style.display = 'none';
            }
        })
        .catch(err => {
            document.getElementById('previewLoading').style.display = 'none';
            document.getElementById('previewContent').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
            document.getElementById('previewContent').style.display = 'block';
        });
    }

    function sendNotifications() {
        if (!confirm('¿Confirma enviar los mensajes de WhatsApp a los clientes listados?')) return;

        document.getElementById('previewContent').style.display = 'none';
        document.getElementById('btnConfirmSend').style.display = 'none';
        document.getElementById('sendingProgress').style.display = 'block';

        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'send');

        fetch('ajax_send_notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('sendingProgress').style.display = 'none';
            var resultsDiv = document.getElementById('sendResults');

            if (data.success) {
                var d = data.data;
                var html = '<div class="alert alert-' + (d.failed > 0 ? 'warning' : 'success') + '">';
                html += '<strong><i class="fas fa-check-circle"></i> Envio completado</strong><br>';
                html += 'Enviados: <strong>' + d.sent + '</strong><br>';
                html += 'Fallidos: <strong>' + d.failed + '</strong>';
                html += '</div>';

                if (d.details.length > 0) {
                    html += '<table class="table table-sm"><thead><tr><th>Cliente</th><th>Telefono</th><th>Estado</th><th>Error</th></tr></thead><tbody>';
                    d.details.forEach(function(item) {
                        var badge = item.estado === 'enviado'
                            ? '<span class="badge bg-success">Enviado</span>'
                            : '<span class="badge bg-danger">Fallido</span>';
                        html += '<tr><td>' + escapeHtml(item.nombre) + '</td><td>' + escapeHtml(item.telefono) + '</td><td>' + badge + '</td><td><small>' + escapeHtml(item.error || '-') + '</small></td></tr>';
                    });
                    html += '</tbody></table>';
                }

                resultsDiv.innerHTML = html;
            } else {
                resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Error desconocido') + '</div>';
            }
            resultsDiv.style.display = 'block';
        })
        .catch(err => {
            document.getElementById('sendingProgress').style.display = 'none';
            document.getElementById('sendResults').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
            document.getElementById('sendResults').style.display = 'block';
        });
    }

    function saveTemplate() {
        var template = document.getElementById('whatsappTemplate').value.trim();
        if (!template) { alert('El template no puede estar vacio.'); return; }

        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'update_template');
        formData.append('template', template);

        fetch('ajax_send_notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            var span = document.getElementById('templateSaveResult');
            if (data.success) {
                span.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Guardado</span>';
            } else {
                span.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> ' + (data.error || 'Error') + '</span>';
            }
            setTimeout(() => { span.innerHTML = ''; }, 3000);
        })
        .catch(err => {
            document.getElementById('templateSaveResult').innerHTML = '<span class="text-danger">Error: ' + err.message + '</span>';
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
</script>