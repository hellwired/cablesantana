<?php
/**
 * reactivacion_ui.php
 *
 * Gestión de reactivación para clientes con servicio cortado (>3 meses).
 * Calcula el pago de las últimas 2 facturas + recargo total.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar roles permitidos
$allowed_roles = ['administrador', 'editor', 'visor'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: dashboard.php');
    exit();
}

require_once 'reactivacion_model.php';

$searchTerm = $_GET['search'] ?? '';
$cutClients = getCutoffClients($searchTerm);
$surchargeVal = getSurchargeValue();

require_once 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4 border-left-danger">
            <div class="card-header py-3 bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-power-off text-danger me-2"></i>Reactivación de
                    Servicio (> 3 Meses Vencido)</h6>
            </div>
            <div class="card-body">

                <div class="alert alert-info border-0 shadow-sm">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Política de Reactivación:</strong> El cliente debe abonar las <strong>últimas 2
                        facturas</strong> más el <strong>recargo por mora</strong> acumulado de todos los meses
                    adeudados.
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>¡Reactivación Exitosa!</strong> Se han registrado los pagos y el recargo correctamente. El
                        cliente ya no figura como cortado.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <form method="GET" class="mb-4 mt-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Buscar cliente cortado..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-dark" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <?php if ($searchTerm): ?>
                            <a href="reactivacion_ui.php" class="btn btn-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle dt-responsive nowrap"
                        id="dataTableReact" width="100%" cellspacing="0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Cliente</th>
                                <th>DNI</th>
                                <th class="text-center">Meses Deuda</th>
                                <th class="text-end">Deuda Total Acumulada</th>
                                <th class="text-end bg-danger text-white border-danger">Monto Reactivación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cutClients)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-2x mb-3 text-success"></i><br>
                                        No se encontraron clientes con servicio cortado (deuda > 3 meses).
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cutClients as $client):
                                    // Calcular costo específico de reactivación
                                    $costInfo = calculateReactivationCost($client['cliente_id'], $client['facturas_adeudadas']);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                                            </strong><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($client['direccion']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($client['dni']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger rounded-pill px-3" style="font-size: 1em;">
                                                <?php echo $client['facturas_adeudadas']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-muted">
                                            $
                                            <?php echo number_format($client['total_deuda_acumulada'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-end bg-light border-start border-danger">
                                            <span class="fw-bold text-danger" style="font-size: 1.1em;">
                                                $
                                                <?php echo number_format($costInfo['total_reactivacion'], 2, ',', '.'); ?>
                                            </span>
                                            <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                                2 Fac: $
                                                <?php echo number_format($costInfo['monto_facturas'], 2, ',', '.'); ?><br>
                                                + Recargo: $
                                                <?php echo number_format($costInfo['recargo_total'], 2, ',', '.'); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <form action="process_reactivacion.php" method="POST"
                                                onsubmit="return confirm('¿Está seguro de REACTIVAR este servicio? Se generarán los pagos correspondientes y el recargo.');">
                                                <input type="hidden" name="cliente_id"
                                                    value="<?php echo $client['cliente_id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-plug me-1"></i> Reactivar
                                                </button>
                                            </form>
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

<?php require_once 'footer.php'; ?>
<script>
    $(document).ready(function () {
        if ($.fn.DataTable) {
            $('#dataTableReact').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                responsive: true
            });
        }
    });
</script>