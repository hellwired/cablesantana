<?php
/**
 * cortes_ui.php
 *
 * Muestra el listado de clientes que deben 2 o más facturas,
 * candidatos para el corte de servicio.
 */

session_start();

// Verificar autenticación
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

// Requerir el modelo
require_once 'cortes_model.php';

// Obtener datos
$clients_cutoff = getClientsForCutoff();

require_once 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div
                class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-danger text-white">
                <h6 class="m-0 font-weight-bold">Informe de Cortes de Servicio (Deuda >= 2 Meses)</h6>
                <button class="btn btn-light btn-sm text-danger" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir Informe
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($clients_cutoff)): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-3"></i><br>
                        ¡Excelente! No hay clientes que cumplan con los criterios de corte (2 o más facturas pendientes).
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="dataTableCortes" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Cliente</th>
                                    <th>DNI</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Meses Deuda</th>
                                    <th class="text-end">Total Deuda</th>
                                    <th>Vencimiento Más Antiguo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients_cutoff as $client): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                                            </strong><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($client['correo_electronico']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($client['dni']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($client['direccion']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger rounded-pill px-3">
                                                <?php echo $client['facturas_adeudadas']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            $
                                            <?php echo number_format($client['total_deuda'], 2, ',', '.'); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($client['fecha_vencimiento_mas_antigua'])); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="payments_ui.php?search_term=<?php echo urlencode($client['dni']); ?>"
                                                class="btn btn-primary btn-sm" title="Ir a Pagos">
                                                <i class="fas fa-money-bill-wave"></i> Cobrar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-muted small">
                        * Este listado muestra únicamente clientes con 2 o más facturas en estado 'pendiente' o 'vencida'.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<script>
    $(document).ready(function () {
        // Inicializar DataTables si está disponible
        if ($.fn.DataTable) {
            $('#dataTableCortes').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [[3, "desc"]] // Ordenar por meses de deuda descendente
            });
        }
    });
</script>