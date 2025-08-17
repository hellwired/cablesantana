<?php
/**
 * audit_ui.php
 *
 * Este archivo proporciona la interfaz de usuario para visualizar el log de auditoría.
 * Utiliza Bootstrap 4 para el diseño.
 *
 * Requiere autenticación de usuario y rol 'administrador' para acceder.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado y tiene rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    // Si no está logueado o no es administrador, redirigir al login
    header('Location: login.php');
    exit();
}

require_once 'audit_model.php'; // Incluir el modelo de auditoría

// Obtener todos los registros de auditoría
$audit_logs = getAllAuditLogs();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Log de Auditoría - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
            margin-bottom: 30px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #343a40;
            margin-bottom: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .alert {
            border-radius: 8px;
        }
        .navbar {
            border-radius: 0 0 15px 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        pre {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap; /* Permite que el texto se ajuste */
            word-wrap: break-word; /* Rompe palabras largas */
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="dashboard.php">Cable Santana</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users_ui.php">Gestión de Usuarios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments_ui.php">Gestión de Pagos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="debts_ui.php">Gestión de Deudas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clients_ui.php">Gestión de Clientes</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="audit_ui.php">Log de Auditoría <span class="sr-only">(current)</span></a>
                </li>
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

    <div class="container">
        <h1 class="text-center">Log de Auditoría</h1>

        <?php if (!empty($audit_logs)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha Acción</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla Afectada</th>
                            <th>ID Registro</th>
                            <th>Detalle Anterior</th>
                            <th>Detalle Nuevo</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['id']); ?></td>
                                <td><?php echo htmlspecialchars($log['fecha_accion']); ?></td>
                                <td><?php echo htmlspecialchars($log['nombre_usuario'] ?? 'N/A (ID: ' . $log['usuario_id'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars($log['accion']); ?></td>
                                <td><?php echo htmlspecialchars($log['tabla_afectada'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['registro_afectado_id'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($log['detalle_anterior'])): ?>
                                        <pre><?php print_r(json_decode($log['detalle_anterior'], true) ?? $log['detalle_anterior']); ?></pre>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['detalle_nuevo'])): ?>
                                        <pre><?php print_r(json_decode($log['detalle_nuevo'], true) ?? $log['detalle_nuevo']); ?></pre>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['direccion_ip'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No hay registros de auditoría.</p>
        <?php endif; ?>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
