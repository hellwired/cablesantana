<?php
session_start();
require_once 'db_connection.php';
require_once 'payment_model.php';
require_once 'client_model.php';

// 1. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = connectDB();

// 2. Get Client Data linked to this User
$stmt = $conn->prepare("SELECT * FROM clientes WHERE correo_electronico = (SELECT email FROM usuarios WHERE id = ?)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    die("Error: No se encontró el perfil de cliente asociado.");
}

// 3. Get Invoices
$stmt = $conn->prepare("SELECT f.*, p.nombre_plan FROM facturas f JOIN suscripciones s ON f.suscripcion_id = s.id JOIN planes p ON s.plan_id = p.id WHERE f.cliente_id = ? ORDER BY f.fecha_emision DESC");
$stmt->bind_param("i", $client['id']);
$stmt->execute();
$result = $stmt->get_result();
$invoices = [];
while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt->close();
closeDB($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Cable Color</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Cable Color - Mi Cuenta</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">Hola, <?php echo htmlspecialchars($client['nombre']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <!-- Sidebar / Info -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-4x text-secondary"></i>
                        </div>
                        <h4 class="card-title"><?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($client['correo_electronico']); ?></p>
                        <hr>
                        <div class="text-start">
                            <p><strong>DNI:</strong> <?php echo htmlspecialchars($client['dni']); ?></p>
                            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($client['direccion']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content / Invoices -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Mis Facturas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($invoices)): ?>
                            <div class="alert alert-info">No tienes facturas registradas.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th># Factura</th>
                                            <th>Plan</th>
                                            <th>Fecha</th>
                                            <th>Monto Total</th>
                                            <th>Saldo Pendiente</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $inv): 
                                            // Calcular saldo pendiente en tiempo real
                                            $saldo_pendiente = getInvoiceBalance($inv['id']);
                                            
                                            // Determinar estado visual basado en saldo
                                            $estado_visual = $inv['estado'];
                                            if ($saldo_pendiente <= 0) {
                                                $estado_visual = 'pagada';
                                            } elseif ($saldo_pendiente < $inv['monto']) {
                                                $estado_visual = 'parcial';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $inv['id']; ?></td>
                                                <td><?php echo htmlspecialchars($inv['nombre_plan']); ?></td>
                                                <td><?php echo $inv['fecha_emision']; ?></td>
                                                <td>$<?php echo number_format($inv['monto'], 2); ?></td>
                                                <td class="fw-bold text-dark">$<?php echo number_format($saldo_pendiente, 2); ?></td>
                                                <td>
                                                    <?php if ($estado_visual == 'pagada'): ?>
                                                        <span class="badge bg-success">Pagada</span>
                                                    <?php elseif ($estado_visual == 'parcial'): ?>
                                                        <span class="badge bg-info text-dark">Parcial</span>
                                                    <?php elseif ($inv['estado'] == 'vencida'): ?>
                                                        <span class="badge bg-danger">Vencida</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($saldo_pendiente > 0): ?>
                                                        <a href="pagar.php?factura_id=<?php echo $inv['id']; ?>" class="btn btn-primary btn-sm">
                                                            Pagar
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" disabled>Pagado</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
