session_start();
session_unset();
session_destroy();
echo "Sesión borrada. Ahora puedes intentar acceder a login.php";