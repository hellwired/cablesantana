<?php
session_start();

require_once 'plan_model.php';

// 1. Validate Plan ID
$plan_id = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$plan = null;

if (!$plan_id || $plan_id <= 0) {
    // If no valid plan ID, redirect or show a generic error
    // For now, we'll die, but a user-friendly page is better.
    die('Error: Plan no especificado o inválido.');
} else {
    // 2. Fetch Plan Details
    $plan = getPlanById($plan_id);
    if (!$plan) {
        die('Error: El plan seleccionado no existe.');
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// We will process the form in 'procesar_registro.php'
// For now, this file just displays the form.
$errors = $_SESSION['errors'] ?? [];
$old_data = $_SESSION['old_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_data']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Contratar Plan - Cable Color Santa Ana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">Formulario de Registro</h1>
                    </div>
                    <div class="card-body p-4">
                        <h2 class="h4">Plan Seleccionado: <span class="text-primary"><?php echo htmlspecialchars($plan['nombre_plan']); ?></span></h2>
                        <p class="lead">Precio: <strong class="text-success">$<?php echo number_format($plan['precio_mensual'], 2); ?>/mes</strong></p>
                        <hr class="my-4">

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <p class="mb-0"><strong>Por favor, corrija los siguientes errores:</strong></p>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="procesar_registro.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">

                            <h5 class="mt-4 mb-3">Datos Personales</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($old_data['nombre'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($old_data['apellido'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" name="dni" value="<?php echo htmlspecialchars($old_data['dni'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección de Instalación</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($old_data['direccion'] ?? ''); ?>" required>
                            </div>

                            <h5 class="mt-4 mb-3">Datos de la Cuenta</h5>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" value="<?php echo htmlspecialchars($old_data['correo_electronico'] ?? ''); ?>" required>
                                <div class="form-text">Usarás este correo para iniciar sesión.</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contrasena" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Registrarse y Proceder al Pago</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php">Volver a la página principal</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>