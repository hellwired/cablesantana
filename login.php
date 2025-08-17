<?php
/**
 * login.php
 *
 * Esta es la página de inicio de sesión para el sistema Cable Santana.
 * Permite a los usuarios autenticarse y redirige a la página principal
 * si las credenciales son correctas.
 */

session_start(); // Iniciar la sesión al principio del script

// Verificar si user_model.php existe antes de incluirlo
// Usamos __DIR__ y DIRECTORY_SEPARATOR para asegurar una ruta absoluta y compatible con diferentes sistemas operativos.
$user_model_path = __DIR__ . DIRECTORY_SEPARATOR . 'user_model.php';
if (!file_exists($user_model_path)) {
    die("Error: El archivo 'user_model.php' no se encuentra en la ruta esperada. Asegúrate de que esté en el mismo directorio que 'login.php'.");
}
require_once $user_model_path; // Incluir el modelo de usuario para la función verifyUser

$login_message = ['type' => '', 'text' => ''];

// Si el usuario ya está logueado, redirigir a la página principal
if (isset($_SESSION['user_id'])) {
    header('Location: users_ui.php'); // Redirigir a la página de gestión de usuarios
    exit();
}

// Procesar el formulario de login
if (isset($_POST['login'])) {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (!empty($nombre_usuario) && !empty($contrasena)) {
        // Asegurarse de que la función verifyUser esté definida antes de llamarla.
        // Esto ayuda a diagnosticar si user_model.php se cargó pero la función no se definió (ej. por un error de sintaxis dentro de user_model.php).
        if (!function_exists('verifyUser')) {
            die("Error: La función 'verifyUser' no está definida. Esto podría indicar un problema en 'user_model.php' que impide su carga completa.");
        }
        $user = verifyUser($nombre_usuario, $contrasena); // Línea donde ocurre el error original

        if ($user) {
            // Credenciales válidas, iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['nombre_usuario'];
            $_SESSION['rol'] = $user['rol'];
            // Redirigir al usuario a la página principal (users_ui.php por ahora)
            header('Location: users_ui.php');
            exit();
        } else {
            $login_message = ['type' => 'danger', 'text' => 'Nombre de usuario o contraseña incorrectos, o usuario inactivo.'];
        }
    } else {
        $login_message = ['type' => 'warning', 'text' => 'Por favor, ingrese su nombre de usuario y contraseña.'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Iniciar Sesión - Cable Santana</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #343a40;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-control, .btn {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Iniciar Sesión</h1>

        <?php if (!empty($login_message['text'])): ?>
            <div class="alert alert-<?php echo $login_message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $login_message['text']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary btn-block">Ingresar</button>
        </form>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>