<?php
/**
 * notificaciones_ui.php
 *
 * Pagina de historial de notificaciones enviadas.
 * Muestra tabla con: fecha, cliente, mensaje, estado, error.
 * Filtros por fecha y estado.
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

require_once 'notificacion_model.php';

$history = getNotificationHistory(200);

require_once 'header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
    <h1 class="h3 mb-0 text-gray-800"><i class="fab fa-whatsapp"></i> Historial de Notificaciones</h1>
    <a href="morosos_ui.php" class="btn btn-primary btn-sm">
        <i class="fas fa-arrow-left"></i> Volver a Morosos
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-success text-white">
        <h6 class="m-0 font-weight-bold">Notificaciones Enviadas</h6>
    </div>
    <div class="card-body">
        <?php if (empty($history)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay notificaciones registradas aun.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="notificationsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Telefono</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Mensaje</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $notif): ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($notif['fecha_envio']); ?></small></td>
                                <td><strong><?php echo htmlspecialchars($notif['nombre'] . ' ' . $notif['apellido']); ?></strong></td>
                                <td><?php echo htmlspecialchars($notif['telefono'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($notif['tipo'] === 'whatsapp'): ?>
                                        <span class="badge bg-success"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($notif['tipo']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($notif['estado'] === 'enviado'): ?>
                                        <span class="badge bg-success">Enviado</span>
                                    <?php elseif ($notif['estado'] === 'fallido'): ?>
                                        <span class="badge bg-danger">Fallido</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars(mb_substr($notif['mensaje'], 0, 80)) . (mb_strlen($notif['mensaje']) > 80 ? '...' : ''); ?></small></td>
                                <td><small class="text-danger"><?php echo htmlspecialchars($notif['error_detalle'] ?? '-'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#notificationsTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                order: [[0, "desc"]]
            });
        }
    });
</script>
