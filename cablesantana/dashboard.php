<?php
/**
 * dashboard.php
 *
 * Esta es la página principal del panel de administración, que muestra métricas clave.
 */

session_start(); // Iniciar la sesión

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'dashboard_model.php';

// Obtener todas las métricas
$total_clients = getTotalClients();
$total_users = getTotalUsers();
$total_pending_debt = getTotalPendingDebt();
$total_payments_month = getTotalPaymentsThisMonth();
$recent_payments = getRecentPayments(5);
$recent_clients = getRecentClients(5);
$overdue_clients = getOverdueClients(); // Obtener clientes morosos

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding: 30px;
        }
        .card-metric {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-metric:hover {
            transform: translateY(-5px);
        }
        .card-metric .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-metric .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .card-metric .metric-label {
            font-size: 1.1rem;
            color: #6c757d;
        }
        .card-metric .icon {
            font-size: 3.5rem;
            color: rgba(0,0,0,0.15);
        }
        .table-responsive {
            margin-top: 20px;
        }
        .navbar {
            border-radius: 0 0 15px 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mx-4 mt-0">
        <a class="navbar-brand" href="dashboard.php">Cable Santana</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="dashboard.php">Dashboard <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users_ui.php">Usuarios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clients_ui.php">Clientes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments_ui.php">Pagos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="debts_ui.php">Deudas</a>
                </li>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="audit_ui.php">Auditoría</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="navbar-text mr-3">
                        Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['rol']); ?>)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light rounded-pill" href="logout.php">Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <h1 class="mb-4">Dashboard</h1>

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

        <!-- Fila de Clientes Morosos -->
        <?php if (!empty($overdue_clients)): ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white py-3">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Clientes con Deudas Vencidas</h6>
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
                                            <td>$<?php echo number_format(htmlspecialchars($user['monto_pendiente']), 2); ?></td>
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

        <!-- Fila de Listas Recientes -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pagos Recientes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Monto</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['nombre'] . ' ' . $payment['apellido']); ?></td>
                                            <td>$<?php echo number_format(htmlspecialchars($payment['monto']), 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['fecha_pago']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Clientes Nuevos</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>DNI</th>
                                        <th>Fecha Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['nombre'] . ' ' . $client['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($client['dni']); ?></td>
                                            <td><?php echo htmlspecialchars($client['fecha_registro']); ?></td>
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

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
