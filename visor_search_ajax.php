<?php
session_start();
require_once 'db_connection.php';
require_once 'client_model.php';

// Validar que sea petición Ajax y usuario autorizado
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['visor', 'administrador', 'editor'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acceso denegado.</div>';
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo ''; // No buscar cadenas muy cortas
    exit();
}

$clients = searchClients($query);

if (empty($clients)) {
    echo '<div class="text-center text-muted mt-5">';
    echo '<i class="fas fa-user-slash fa-3x mb-3 text-light"></i>';
    echo '<p>No se encontraron clientes coincidiendo con "<strong>' . htmlspecialchars($query) . '</strong>".</p>';
    echo '</div>';
    exit();
}

// Generar Cards HTML para cada cliente
foreach ($clients as $client) {
    // Determinar estado de deuda (simplificado para UI rapida)
    $estado_clase = 'success';
    $border_color = '#198754';
    $deuda_str = 'Al día';

    // Si tiene deuda real calculada en el query (asumiendo que searchClients devuelve monto_deuda si se modifica, 
    // o calcularemos un aproximado del balance rojo/verde).
    // Si searchClients no devuelve monto_deuda optimizado, verificamos 'estado_servicio'

    if (isset($client['estado_servicio']) && $client['estado_servicio'] === 'suspendido') {
        $estado_clase = 'danger';
        $border_color = '#dc3545';
        $deuda_str = 'Suspendido';
    } elseif (isset($client['estado_servicio']) && $client['estado_servicio'] === 'pendiente') {
        $estado_clase = 'warning';
        $border_color = '#ffc107';
        $deuda_str = 'Pendiente';
    }

    // Nota: Para mobile-first de cobranza, necesitamos el *saldo* exacto. 
    // En el futuro, optimizar searchClients para devolver SUM(monto_total) de facturas pendientes o morosas.
    ?>

    <div class="client-card position-relative" style="border-left-color: <?php echo $border_color; ?>;">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="client-name mb-1">
                    <?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?>
                </h3>
                <div class="client-detail"><i class="fas fa-id-card text-muted"></i>
                    <?php echo htmlspecialchars($client['dni']); ?>
                </div>
                <div class="client-detail text-truncate" style="max-width:200px;"><i
                        class="fas fa-map-marker-alt text-muted"></i>
                    <?php echo htmlspecialchars($client['direccion']); ?>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-<?php echo $estado_clase; ?> mb-1">
                    <?php echo $deuda_str; ?>
                </span>
            </div>
        </div>

        <div class="mt-3">
            <a href="payments_ui.php?client_id=<?php echo $client['id']; ?>" class="btn btn-primary btn-cobrar shadow-sm">
                <i class="fas fa-cash-register me-2"></i> Cobrar / Detalles
            </a>
        </div>
    </div>

    <?php
}
?>