<?php
session_start();

require_once 'payment_model.php';

// 1. Validate Invoice ID
$factura_id = filter_input(INPUT_GET, 'factura_id', FILTER_VALIDATE_INT);
$invoice = null;

if (!$factura_id || $factura_id <= 0) {
    die('Error: Factura no especificada o inválida.');
}

// 2. Fetch Invoice Details
$invoice = getInvoiceWithDetailsById($factura_id);

if (!$invoice) {
    die('Error: La factura que intentas pagar no existe.');
}

// Calcular saldo pendiente real
$saldo_pendiente = getInvoiceBalance($invoice['id']);

if ($saldo_pendiente <= 0) {
    die('Esta factura ya ha sido pagada completamente. Gracias.');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pago - Cable Color Santa Ana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h1 class="h3 mb-0">Realizar Pago</h1>
                    </div>
                    <div class="card-body p-4">
                        <h2 class="h4">Resumen de tu Compra</h2>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Cliente:
                                <strong><?php echo htmlspecialchars($invoice['nombre'] . ' ' . $invoice['apellido']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Plan Contratado:
                                <strong><?php echo htmlspecialchars($invoice['nombre_plan']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Número de Factura:
                                <strong>#<?php echo htmlspecialchars($invoice['id']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Fecha de Emisión:
                                <strong><?php echo htmlspecialchars($invoice['fecha_emision']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Monto Total Original:
                                <span class="text-muted">$<?php echo number_format($invoice['monto'], 2); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center h4 bg-light p-3 rounded">
                                Total a Pagar Ahora:
                                <strong class="text-success">$<?php echo number_format($saldo_pendiente, 2); ?></strong>
                            </li>
                        </ul>

                        <hr>

                        <!-- =================================================================== -->
                        <!-- ==                INTEGRACIÓN DE PASARELA DE PAGO                == -->
                        <!-- =================================================================== -->
                        <!--                                                                   -->
                        <!--  AQUÍ es donde se debe integrar la pasarela de pago real, como   -->
                        <!--  Mercado Pago, Stripe, etc. Esto usualmente implica:             -->
                        <!--  1. Incluir el SDK de Javascript de la pasarela.                  -->
                        <!--  2. Crear un contenedor para el botón o formulario de pago.       -->
                        <!--  3. Usar Javascript para inicializar la pasarela con el ID de     -->
                        <!--     la preferencia de pago (que se crearía en el backend).        -->
                        <!--                                                                   -->
                        <!--  Como simulación, usaremos un simple formulario.                  -->
                        <!--                                                                   -->
                        <!-- =================================================================== -->

                        <div class="text-center p-4 border rounded bg-light">
                            <h5 class="mb-3">Pagar con Tarjeta</h5>
                            <p class="text-muted">Serás redirigido a Stripe para completar el pago de forma segura.</p>
                            
                            <?php
                            require_once 'classes/StripePaymentGateway.php';
                            use CableColor\StripePaymentGateway;

                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_stripe'])) {
                                $gateway = new StripePaymentGateway();
                                $successUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/confirmar_pago.php?factura_id=" . $invoice['id'];
                                $cancelUrl = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                                
                                // Usar el saldo pendiente en lugar del monto total
                                $session = $gateway->createCheckoutSession(
                                    $saldo_pendiente, 
                                    'usd', 
                                    "Pago Factura #" . $invoice['id'], 
                                    $successUrl, 
                                    $cancelUrl,
                                    ['factura_id' => $invoice['id']]
                                );

                                if ($session) {
                                    header("Location: " . $session['url']);
                                    exit();
                                } else {
                                    echo '<div class="alert alert-danger">Error al iniciar el pago. Intente nuevamente.</div>';
                                }
                            }
                            ?>

                            <form action="pagar.php?factura_id=<?php echo $invoice['id']; ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <button type="submit" name="pay_stripe" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-credit-card me-2"></i> Pagar Ahora con Stripe
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>