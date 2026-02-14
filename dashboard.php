<?php
declare(strict_types=1);

/**
 * dashboard.php
 *
 * Esta es la página principal del panel de administración, que muestra métricas clave.
 */

// 1. SEGURIDAD PRIORITARIA: Verificar autenticación antes de ejecutar lógica o BD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Restringir acceso al dashboard solo para administradores
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'editor') {
    header('Location: payments_ui.php');
    exit();
}

require_once 'dashboard_model.php';
require_once 'facturacion_model.php';

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 2. OBTENCIÓN DE DATOS
// Solo ejecutamos esto una vez verificado el usuario
$total_clients = getTotalClients();
$total_users = getTotalUsers();
$total_pending_debt = getTotalPendingDebt();
$total_payments_month = getTotalPaymentsThisMonth();
$recent_payments = getRecentPayments(10); // Aumentamos el límite para tener más datos
$overdue_clients = getOverdueClients(); // Obtener clientes morosos

require_once 'header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
    <h1 class="h3 mb-0 text-gray-800">Panel de Control</h1>
</div>

<!-- Analytics Row -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3 card-metric">
            <div class="card-body">
                <div>
                    <div class="metric-value">$<?php echo number_format(calculateMRR(), 2); ?></div>
                    <div class="metric-label">MRR (Ingresos Recurrentes)</div>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3 card-metric">
            <div class="card-body">
                <div>
                    <div class="metric-value"><?php echo calculateChurnRate(); ?>%</div>
                    <div class="metric-label">Tasa de Cancelación</div>
                </div>
                <div class="icon"><i class="fas fa-user-times"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3 card-metric" style="cursor: pointer;" data-toggle="modal"
            data-target="#ltvConfigModal" title="Clic para configurar">
            <div class="card-body">
                <div>
                    <div class="metric-value">$<?php echo number_format(calculateLTV(), 2); ?></div>
                    <div class="metric-label">LTV (Valor de Vida del Cliente) <i class="fas fa-cog fa-xs"></i></div>
                </div>
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Fila de Métricas Clave -->
<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-metric border-left-primary">
            <div class="card-body">
                <div>
                    <div class="metric-value text-primary"><?php echo $total_clients; ?></div>
                    <div class="metric-label">Clientes Totales</div>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-metric border-left-success">
            <div class="card-body">
                <div>
                    <div class="metric-value text-success">$<?php echo number_format($total_payments_month, 2); ?></div>
                    <div class="metric-label">Pagos (Este Mes)</div>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-metric border-left-danger">
            <div class="card-body">
                <div>
                    <div class="metric-value text-danger">$<?php echo number_format($total_pending_debt, 2); ?></div>
                    <div class="metric-label">Deuda Pendiente</div>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-metric border-left-info">
            <div class="card-body">
                <div>
                    <div class="metric-value text-info"><?php echo $total_users; ?></div>
                    <div class="metric-label">Usuarios Activos</div>
                </div>
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seccion de Facturacion Mensual (Solo Admin) -->
<?php
if ($_SESSION['rol'] === 'administrador'):
    $invoiceStatus = getInvoiceGenerationStatus();
?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-file-invoice-dollar"></i> Facturacion Mensual</h6>
                    <span class="badge bg-light text-primary" id="invoiceStatusBadge">
                        <?php echo $invoiceStatus['already_generated']; ?> de <?php echo $invoiceStatus['total_active']; ?> generadas
                    </span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-1" id="invoiceStatusText">
                                <?php if ($invoiceStatus['pending'] > 0): ?>
                                    <i class="fas fa-info-circle text-warning"></i>
                                    Hay <strong><?php echo $invoiceStatus['pending']; ?></strong> suscripciones activas pendientes de facturar este mes
                                    (<?php echo date('F Y'); ?>).
                                <?php else: ?>
                                    <i class="fas fa-check-circle text-success"></i>
                                    Todas las suscripciones activas ya tienen factura generada para <?php echo date('F Y'); ?>.
                                <?php endif; ?>
                            </p>
                            <small class="text-muted">
                                Total activas: <?php echo $invoiceStatus['total_active']; ?> |
                                Ya generadas: <?php echo $invoiceStatus['already_generated']; ?> |
                                Pendientes: <?php echo $invoiceStatus['pending']; ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success btn-lg" id="btnGenerateInvoices"
                                <?php echo $invoiceStatus['pending'] === 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cogs" id="btnGenerateIcon"></i>
                                <span id="btnGenerateText">Generar Facturas del Mes</span>
                            </button>
                        </div>
                    </div>
                    <div id="invoiceResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Fila de Clientes Morosos -->
<?php if (!empty($overdue_clients)): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white py-3">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Clientes con Deudas
                        Vencidas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Concepto de Deuda</th>
                                    <th>Monto Pendiente</th>
                                    <th>Fecha de Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_clients as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nombre_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($user['concepto']); ?></td>
                                        <td>$<?php echo number_format((float)$user['monto_pendiente'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($user['fecha_vencimiento']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Fila de Facturas Pendientes (Próximos Cobros) -->
<?php
$pending_invoices = getPendingInvoices(5);
if (!empty($pending_invoices)):
    ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark py-3">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-clock"></i> Facturas Pendientes de Cobro</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Monto Total</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Vence</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_invoices as $inv):
                                    // Calcular saldo real
                                    require_once 'payment_model.php';
                                    $saldo = getInvoiceBalance($inv['id']);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inv['nombre'] . ' ' . $inv['apellido']); ?></td>
                                        <td>$<?php echo number_format((float)$inv['monto'], 2); ?></td>
                                        <td class="font-weight-bold">$<?php echo number_format((float)$saldo, 2); ?></td>
                                        <td><?php echo htmlspecialchars($inv['fecha_vencimiento']); ?></td>
                                        <td>
                                            <a href="payments_ui.php?prefill_client_id=<?php echo $inv['cliente_id']; ?>"
                                                class="btn btn-primary btn-sm">Ir a Pagar</a>
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
<?php endif; ?>

<!-- Fila de Pagos Recientes y Nuevos Clientes -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Pagos Recientes y Nuevos Clientes</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Fecha de Pago</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_payments as $payment):
                                $payment_date = new DateTime($payment['fecha_pago']);
                                $registration_date = new DateTime($payment['fecha_registro']);
                                $interval = $payment_date->diff($registration_date);
                                $is_new_client_payment = $interval->days <= 1; // Consideramos nuevo si el pago es hasta 1 día después del registro
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['nombre'] . ' ' . $payment['apellido']); ?>
                                    </td>
                                    <td>$<?php echo number_format((float)$payment['monto'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['fecha_pago']); ?></td>
                                    <td>
                                        <?php if ($is_new_client_payment): ?>
                                            <span class="badge badge-success">Nuevo Cliente</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Pago Regular</span>
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

</div>

</div>

<!-- Modal de Configuración LTV -->
<div class="modal fade" id="ltvConfigModal" tabindex="-1" aria-labelledby="ltvConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="ltvConfigModalLabel"><i class="fas fa-calculator"></i> Configuración de LTV
                    (Lifetime Value)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="save_ltv_config.php" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="font-weight-bold text-primary">Configuración</h6>
                            <div class="mb-3">
                                <label for="churn_rate" class="form-label">Tasa de Cancelación Mensual (Churn Rate)
                                    %</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.1" max="100" class="form-control"
                                        id="churn_rate" name="churn_rate" value="<?php echo calculateChurnRate(); ?>"
                                        required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="form-text text-muted">
                                    Porcentaje de clientes que cancelan el servicio cada mes. Un valor más bajo aumenta
                                    el LTV.
                                </small>
                            </div>
                            <div class="alert alert-info mt-3">
                                <strong>Valor Actual:</strong> $<?php echo number_format(calculateLTV(), 2); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary">Manual de Uso</h6>
                            <div style="max-height: 300px; overflow-y: auto; font-size: 0.9rem;">
                                <p><strong>¿Qué es el LTV?</strong></p>
                                <p>El <em>Lifetime Value</em> (Valor de Vida del Cliente) estima cuánto dinero generará
                                    un cliente promedio para la empresa durante toda su relación comercial.</p>

                                <p><strong>¿Cómo se calcula?</strong></p>
                                <p>La fórmula utilizada es:</p>
                                <code
                                    class="d-block bg-light p-2 mb-2 rounded text-center">LTV = ARPU / Churn Rate</code>
                                <ul>
                                    <li><strong>ARPU:</strong> Ingreso Promedio por Usuario (Total Ingresos / Total
                                        Clientes).</li>
                                    <li><strong>Churn Rate:</strong> Tasa de cancelación mensual (configurada aquí).
                                    </li>
                                </ul>

                                <p><strong>Ejemplo:</strong></p>
                                <p>Si un cliente paga $18,333 al mes y la tasa de cancelación es del 5% (0.05):</p>
                                <p class="text-center"><em>$18,333 / 0.05 = $366,666</em></p>

                                <p><strong>¿Para qué sirve?</strong></p>
                                <p>Ayuda a decidir cuánto puedes gastar en adquirir un nuevo cliente (CAC). Si tu LTV es
                                    alto, puedes invertir más en marketing y ventas.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($_SESSION['rol'] === 'administrador'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnGenerateInvoices');
    if (!btn) return;

    btn.addEventListener('click', function() {
        if (!confirm('¿Desea generar las facturas del mes para todas las suscripciones activas?\n\nEsta accion creara facturas pendientes para el mes actual.')) {
            return;
        }

        const icon = document.getElementById('btnGenerateIcon');
        const text = document.getElementById('btnGenerateText');
        const resultDiv = document.getElementById('invoiceResult');

        // Mostrar spinner
        btn.disabled = true;
        icon.className = 'fas fa-spinner fa-spin';
        text.textContent = 'Generando...';

        const formData = new FormData();
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
        formData.append('action', 'generate');

        fetch('ajax_generate_invoices.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            icon.className = 'fas fa-cogs';
            text.textContent = 'Generar Facturas del Mes';

            if (data.success) {
                const d = data.data;
                let alertClass = d.errors.length > 0 ? 'alert-warning' : 'alert-success';
                let html = '<div class="alert ' + alertClass + '">';
                html += '<strong><i class="fas fa-check-circle"></i> Proceso completado</strong><br>';
                html += 'Facturas creadas: <strong>' + d.created + '</strong><br>';
                html += 'Omitidas (ya existian): <strong>' + d.skipped + '</strong><br>';
                html += 'Monto total facturado: <strong>$' + parseFloat(d.total_amount).toFixed(2) + '</strong><br>';
                html += 'Facturas marcadas vencidas: <strong>' + d.overdue_updated + '</strong>';

                if (d.errors.length > 0) {
                    html += '<br><br><strong>Errores:</strong><ul>';
                    d.errors.forEach(function(err) {
                        html += '<li>' + err + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
                resultDiv.innerHTML = html;
                resultDiv.style.display = 'block';

                // Actualizar badge y texto
                document.getElementById('invoiceStatusBadge').textContent =
                    (parseInt(<?php echo $invoiceStatus['already_generated']; ?>) + d.created) + ' de ' + <?php echo $invoiceStatus['total_active']; ?> + ' generadas';
                document.getElementById('invoiceStatusText').innerHTML =
                    '<i class="fas fa-check-circle text-success"></i> Todas las suscripciones activas ya tienen factura generada para <?php echo date("F Y"); ?>.';

                if (d.created === 0 && d.skipped > 0) {
                    btn.disabled = true;
                }
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Error: ' + (data.error || 'Error desconocido') + '</div>';
                resultDiv.style.display = 'block';
                btn.disabled = false;
            }
        })
        .catch(error => {
            icon.className = 'fas fa-cogs';
            text.textContent = 'Generar Facturas del Mes';
            btn.disabled = false;
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Error de conexion: ' + error.message + '</div>';
            resultDiv.style.display = 'block';
        });
    });
});
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>