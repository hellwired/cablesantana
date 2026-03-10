<?php
$factura_id = filter_input(INPUT_GET, 'factura_id', FILTER_VALIDATE_INT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por su compra! - Cable Color Santa Ana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h1 class="display-4 text-success">¡Gracias!</h1>
                        <p class="lead">Tu pago ha sido procesado exitosamente.</p>
                        <hr>
                        <p>Tu registro está completo y tu servicio ha sido activado. Ya puedes iniciar sesión en tu cuenta.</p>
                        <?php if ($factura_id): ?>
                            <p>Tu número de factura es: <strong>#<?php echo htmlspecialchars($factura_id); ?></strong></p>
                        <?php endif; ?>
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-primary btn-lg">Iniciar Sesión</a>
                            <a href="index.php" class="btn btn-secondary btn-lg">Volver al Inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>