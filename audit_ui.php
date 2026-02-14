<?php
/**
 * audit_ui.php
 *
 * Muestra el log de auditoría del sistema.
 * Utiliza DataTables para búsquedas y filtros rápidos.
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit();
}

require_once 'audit_model.php';
$audit_logs = getAllAuditLogs();

/**
 * Función auxiliar para renderizar los detalles (anterior/nuevo)
 * Intenta decodificar JSON y muestra un botón colapsable si es complejo.
 */
function renderAuditDetails(?string $jsonString): string {
    if (empty($jsonString)) {
        return '';
    }

    $data = json_decode($jsonString, true);
    
    // Si es JSON válido y es un array/objeto
    if (is_array($data)) {
        // Generar un ID único para el collapse basado en un hash
        $uniqId = substr(md5($jsonString . rand()), 0, 8);
        
        $html = '<button class="btn btn-sm btn-info text-white mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#desc_' . $uniqId . '" aria-expanded="false">';
        $html .= '<i class="fas fa-eye"></i> Ver JSON';
        $html .= '</button>';
        
        $html .= '<div class="collapse" id="desc_' . $uniqId . '">';
        $html .= '<pre style="font-size: 0.75em; max-height: 150px; overflow-y: auto;" class="bg-light p-2 border rounded mt-1">';
        $html .= htmlspecialchars(print_r($data, true));
        $html .= '</pre>';
        $html .= '</div>';
        
        return $html;
    }

    // Texto plano
    if (strlen($jsonString) > 50) {
        return '<span title="'.htmlspecialchars($jsonString).'">'.htmlspecialchars(substr($jsonString, 0, 50)).'...</span>';
    }
    
    return htmlspecialchars($jsonString);
}


require_once 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-secondary text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Log de Auditoría</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle" id="dataTableAudit"
                        width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Tabla</th>
                                <th>Reg. ID</th>
                                <th>Detalles (Ant / Nuevo)</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td style="white-space: nowrap;">
                                        <?php echo date('d/m/Y H:i', strtotime($log['fecha_accion'])); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-user-circle me-1"></i>
                                            <?php echo htmlspecialchars($log['nombre_usuario'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($log['accion']); ?></td>
                                    <td><?php echo htmlspecialchars($log['tabla_afectada'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['registro_afectado_id'] ?? '-'); ?></td>
                                    <td>
                                        <?php if($log['detalle_anterior'] || $log['detalle_nuevo']): ?>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if($log['detalle_anterior']): ?>
                                                    <div class="small text-muted border-bottom pb-1 mb-1">
                                                        <strong>Ant:</strong> <?php echo renderAuditDetails($log['detalle_anterior']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($log['detalle_nuevo']): ?>
                                                    <div class="small">
                                                        <strong>New:</strong> <?php echo renderAuditDetails($log['detalle_nuevo']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($log['direccion_ip']); ?></td>
                                </tr>
                            <?php endforeach; ?>
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
        // Inicializar DataTables con ordenamiento descendente por ID
        $('#dataTableAudit').DataTable({
            "order": [[0, "desc"]],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
        });
    });
</script>