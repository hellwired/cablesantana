<?php
/**
 * index.php
 *
 * Este archivo es el punto de entrada principal de la aplicación.
 * Redirige automáticamente al usuario a la página de inicio de sesión (login.php).
 */

// Redirigir al navegador a login.php
header('Location: index.html');
exit(); // Asegura que el script se detenga después de la redirección
?>
