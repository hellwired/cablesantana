<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in (Basic security for included header)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Helper function to set active class
function isActive($page)
{
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cable Santana - Panel de Control</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- DataTables for Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Utilidad para asegurar que el footer quede al final */

        /* === MOBILE BOTTOM NAVIGATION (VISOR) === */
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'visor'): ?>
            .navbar {
                display: none !important;
                /* Ocultar navbar superior para visores */
            }

            body {
                padding-bottom: 70px;
                /* Espacio para el menu inferior */
                background-color: #f4f6f9;
            }

            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: #ffffff;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                display: flex;
                justify-content: space-around;
                padding: 10px 0;
                z-index: 1030;
                border-top: 1px solid #e0e0e0;
            }

            .bottom-nav-item {
                text-align: center;
                color: #6c757d;
                text-decoration: none;
                flex-grow: 1;
                font-size: 0.75rem;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .bottom-nav-item i {
                font-size: 1.4rem;
                margin-bottom: 4px;
            }

            .bottom-nav-item.active {
                color: #0d6efd;
                font-weight: 600;
            }

            .bottom-nav-item:hover {
                color: #0b5ed7;
            }

        <?php endif; ?>
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-network-wired me-2"></i>Cable Santana
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('users_ui.php'); ?>" href="users_ui.php">
                            <i class="fas fa-users-cog me-1"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('clients_ui.php'); ?>" href="clients_ui.php">
                            <i class="fas fa-users me-1"></i> Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('payments_ui.php'); ?>" href="payments_ui.php">
                            <i class="fas fa-money-bill-wave me-1"></i> Pagos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('plans_ui.php'); ?>" href="plans_ui.php">
                            <i class="fas fa-list-ul me-1"></i> Planes
                        </a>
                    </li>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('cortes_ui.php'); ?>" href="cortes_ui.php">
                                <i class="fas fa-cut me-1"></i> Cortes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('morosos_ui.php'); ?>" href="morosos_ui.php">
                                <i class="fas fa-exclamation-triangle me-1"></i> Morosos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('reactivacion_ui.php'); ?>" href="reactivacion_ui.php">
                                <i class="fas fa-plug me-1"></i> Reactivación
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('audit_ui.php'); ?>" href="audit_ui.php">
                                <i class="fas fa-clipboard-list me-1"></i> Auditoría
                            </a>
                        </li>
                        <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('arqueo_ui.php'); ?>" href="arqueo_ui.php">
                                <i class="fas fa-cash-register me-1"></i> Arqueo
                            </a>
                        </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><span class="dropdown-item-text text-muted small">Rol:
                                    <?php echo htmlspecialchars($_SESSION['rol'] ?? 'N/A'); ?>
                                </span></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>
                                    Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-2 px-md-4 mt-3 mt-md-0">
        <!-- Start of Main Content -->

        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'visor'): ?>
            <!-- Bottom Navigation Bar for 'Visor' Mobile UI -->
            <nav class="bottom-nav">
                <a href="visor_dashboard.php" class="bottom-nav-item <?php echo isActive('visor_dashboard.php'); ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="morosos_ui.php" class="bottom-nav-item <?php echo isActive('morosos_ui.php'); ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Morosos</span>
                </a>
                <a href="cortes_ui.php" class="bottom-nav-item <?php echo isActive('cortes_ui.php'); ?>">
                    <i class="fas fa-cut"></i>
                    <span>Cortes</span>
                </a>
                <a href="reactivacion_ui.php" class="bottom-nav-item <?php echo isActive('reactivacion_ui.php'); ?>">
                    <i class="fas fa-plug"></i>
                    <span>Reactivar</span>
                </a>
                <a href="logout.php" class="bottom-nav-item">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                    <span class="text-danger">Salir</span>
                </a>
            </nav>
        <?php endif; ?>