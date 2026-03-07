<?php
/**
 * arqueo_ui.php
 *
 * Arqueo de Caja Diario.
 * Permite al administrador verificar cuánto dinero en efectivo
 * debería tener un cobrador (visor) al final del día,
 * comparando contra lo que entregó físicamente.
 *
 * Acceso: solo 'administrador'.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['rol'] !== 'administrador') {
    header('Location: dashboard.php');
    exit();
}

require_once 'arqueo_model.php';

$message = ['type' => '', 'text' => ''];

// --- Variables de resultado de cálculo ---
$resultado_calculo = null;
$visor_id_calc     = 0;
$fecha_calc        = '';
$visor_nombre_calc = '';

// --- Manejar Calcular Arqueo (solo muestra, no guarda) ---
if (isset($_POST['action']) && $_POST['action'] === 'calcular_arqueo') {
    $visor_id_calc = (int)($_POST['visor_id'] ?? 0);
    $fecha_calc    = trim($_POST['fecha_arqueo'] ?? '');

    if ($visor_id_calc > 0 && !empty($fecha_calc)) {
        $resultado_calculo = calcularArqueoEfectivo($visor_id_calc, $fecha_calc);

        // Obtener nombre del visor para mostrar
        $visores = getVisorUsers();
        foreach ($visores as $v) {
            if ((int)$v['id'] === $visor_id_calc) {
                $visor_nombre_calc = $v['nombre_usuario'];
                break;
            }
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Seleccione un cobrador y una fecha.'];
    }
}

// --- Manejar Guardar Arqueo ---
if (isset($_POST['action']) && $_POST['action'] === 'guardar_arqueo') {
    $visor_id_g  = (int)($_POST['visor_id_hidden'] ?? 0);
    $fecha_g     = trim($_POST['fecha_hidden'] ?? '');
    $esperado_g  = (float)($_POST['total_esperado_hidden'] ?? 0);
    $real_g      = (float)($_POST['monto_real'] ?? 0);
    $diferencia_g = (float)($_POST['diferencia_hidden'] ?? 0);
    $estado_g    = trim($_POST['estado_hidden'] ?? '');
    $obs_g       = trim($_POST['observaciones'] ?? '');

    $estados_validos = ['cuadrado', 'faltante', 'sobrante'];
    if ($visor_id_g > 0 && !empty($fecha_g) && in_array($estado_g, $estados_validos)) {
        $id = registrarArqueo(
            (int)$_SESSION['user_id'],
            $visor_id_g,
            $fecha_g,
            $esperado_g,
            $real_g,
            $estado_g,
            $diferencia_g,
            $obs_g
        );
        if ($id) {
            $_SESSION['message'] = ['type' => 'success', 'text' => "Arqueo registrado correctamente (ID #$id)."];
            header('Location: arqueo_ui.php');
            exit();
        } else {
            $message = ['type' => 'danger', 'text' => 'Error al guardar el arqueo. Intente nuevamente.'];
        }
    } else {
        $message = ['type' => 'warning', 'text' => 'Datos incompletos para guardar el arqueo.'];
    }
}

// Mensaje de sesión
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$visores  = getVisorUsers();
$historial = getArqueosHistorial(200);

require_once 'header.php';
?>

<h1 class="text-center"><i class="fas fa-cash-register me-2"></i>Arqueo de Caja Diario</h1>

<?php if (!empty($message['text'])): ?>
    <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message['text']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FORMULARIO DE CALCULO ===== -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-calculator me-1"></i> Calcular Arqueo</h5>
    </div>
    <div class="card-body">
        <form action="arqueo_ui.php" method="POST">
            <input type="hidden" name="action" value="calcular_arqueo">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="visor_id" class="form-label fw-bold">Cobrador:</label>
                    <select class="form-select" id="visor_id" name="visor_id" required>
                        <option value="">-- Seleccione un cobrador --</option>
                        <?php foreach ($visores as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= ($visor_id_calc === (int)$v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nombre_usuario']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($visores)): ?>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-circle"></i>
                            No hay cobradores (rol=visor) activos en el sistema.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="fecha_arqueo" class="form-label fw-bold">Fecha:</label>
                    <input type="date" class="form-select" id="fecha_arqueo" name="fecha_arqueo"
                           value="<?= $fecha_calc ?: date('Y-m-d', strtotime('-1 day')) ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calculator me-1"></i> Calcular
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===== RESULTADO DEL CALCULO ===== -->
<?php if ($resultado_calculo !== null): ?>
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-receipt me-1"></i>
            Resultado: <?= htmlspecialchars($visor_nombre_calc) ?> — <?= htmlspecialchars($fecha_calc) ?>
        </h5>
    </div>
    <div class="card-body">

        <?php if ($resultado_calculo['cantidad_cobros'] === 0): ?>
            <div class="alert alert-secondary">
                <i class="fas fa-info-circle"></i>
                No se encontraron cobros en efectivo registrados por este cobrador en esa fecha.
            </div>
        <?php else: ?>
            <!-- Tabla de detalle -->
            <h6 class="fw-bold mb-2">Detalle de Cobros en Efectivo:</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Pago #</th>
                            <th>Cliente</th>
                            <th>DNI</th>
                            <th>Hora</th>
                            <th>Factura #</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultado_calculo['detalle'] as $cobro): ?>
                            <tr>
                                <td><?= $cobro['pago_id'] ?></td>
                                <td><?= htmlspecialchars($cobro['nombre'] . ' ' . $cobro['apellido']) ?></td>
                                <td><?= htmlspecialchars($cobro['dni'] ?? '-') ?></td>
                                <td><?= date('H:i', strtotime($cobro['fecha_pago'])) ?></td>
                                <td><?= $cobro['factura_id'] ?></td>
                                <td class="text-end">$<?= number_format((float)$cobro['monto'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">Total Sistema:</td>
                            <td class="text-end text-primary fs-5">
                                $<?= number_format($resultado_calculo['total_esperado'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <!-- Formulario para guardar el arqueo -->
        <form action="arqueo_ui.php" method="POST" id="form-guardar-arqueo">
            <input type="hidden" name="action" value="guardar_arqueo">
            <input type="hidden" name="visor_id_hidden" value="<?= $visor_id_calc ?>">
            <input type="hidden" name="fecha_hidden" value="<?= htmlspecialchars($fecha_calc) ?>">
            <input type="hidden" name="total_esperado_hidden" id="total_esperado_hidden"
                   value="<?= $resultado_calculo['total_esperado'] ?>">
            <input type="hidden" name="diferencia_hidden" id="diferencia_hidden" value="0">
            <input type="hidden" name="estado_hidden" id="estado_hidden" value="cuadrado">

            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Total calculado por el sistema:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control bg-light fw-bold text-primary"
                               value="<?= number_format($resultado_calculo['total_esperado'], 2, ',', '.') ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="monto_real" class="form-label fw-bold">
                        Efectivo recibido del cobrador: <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="monto_real" name="monto_real" required
                               placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Diferencia:</label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5 fw-bold" id="diferencia_display">$0.00</span>
                        <span class="badge fs-6" id="estado_display">CUADRADO</span>
                    </div>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label for="observaciones" class="form-label fw-bold">Observaciones:</label>
                <textarea class="form-control" id="observaciones" name="observaciones"
                          rows="2" placeholder="Notas adicionales sobre el arqueo..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Guardar Arqueo
                </button>
                <a href="arqueo_ui.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== HISTORIAL DE ARQUEOS ===== -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-history me-1"></i> Historial de Arqueos</h5>
    </div>
    <div class="card-body">
        <?php if (empty($historial)): ?>
            <p class="text-center text-muted">No hay arqueos registrados aún.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaArqueos">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cobrador</th>
                            <th>Admin</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Real</th>
                            <th class="text-end">Diferencia</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th>Registrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $a): ?>
                            <?php
                            $badge = match($a['estado']) {
                                'cuadrado' => 'success',
                                'faltante' => 'danger',
                                'sobrante' => 'warning',
                                default    => 'secondary'
                            };
                            ?>
                            <tr>
                                <td><?= $a['id'] ?></td>
                                <td><?= htmlspecialchars($a['fecha_arqueo']) ?></td>
                                <td><?= htmlspecialchars($a['cobrador_nombre'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['admin_nombre'] ?? '-') ?></td>
                                <td class="text-end">$<?= number_format((float)$a['monto_esperado'], 2, ',', '.') ?></td>
                                <td class="text-end">$<?= number_format((float)$a['monto_real'], 2, ',', '.') ?></td>
                                <td class="text-end <?= (float)$a['diferencia'] < 0 ? 'text-danger fw-bold' : '' ?>">
                                    $<?= number_format((float)$a['diferencia'], 2, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= strtoupper($a['estado']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($a['observaciones'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['fecha_registro']))) ?></td>
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
$(document).ready(function () {
    // Inicializar DataTable en historial
    if ($('#tablaArqueos').length) {
        $('#tablaArqueos').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    }

    // Cálculo de diferencia y estado en tiempo real
    const esperado = parseFloat($('#total_esperado_hidden').val()) || 0;

    $('#monto_real').on('input', function () {
        const real = parseFloat($(this).val()) || 0;
        const diff = real - esperado;

        $('#diferencia_display').text('$' + diff.toFixed(2).replace('.', ','));
        $('#diferencia_hidden').val(diff.toFixed(2));

        let estado     = 'cuadrado';
        let badgeClass = 'bg-success';
        let label      = 'CUADRADO ✓';

        if (Math.abs(diff) < 0.01) {
            estado = 'cuadrado'; badgeClass = 'bg-success'; label = 'CUADRADO ✓';
        } else if (diff < 0) {
            estado = 'faltante'; badgeClass = 'bg-danger'; label = 'FALTANTE ✗';
        } else {
            estado = 'sobrante'; badgeClass = 'bg-warning text-dark'; label = 'SOBRANTE ⚠';
        }

        $('#estado_hidden').val(estado);
        $('#estado_display').text(label).attr('class', 'badge fs-6 ' + badgeClass);
    });
});
</script>
</body>
</html>
