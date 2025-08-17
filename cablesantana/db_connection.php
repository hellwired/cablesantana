<?php
/**
 * db_connection.php
 *
 * Este archivo contiene la configuración y la lógica para conectar
 * a la base de datos MariaDB.
 */

// Definir las constantes de conexión a la base de datos
define('DB_SERVER', 'localhost'); // Servidor de la base de datos (XAMPP por defecto)
define('DB_USERNAME', 'root');    // Usuario de la base de datos (root en XAMPP)
define('DB_PASSWORD', '');        // Contraseña del usuario (vacía en XAMPP)
define('DB_NAME', 'cable_santana'); // Nombre de la base de datos

/**
 * Función para establecer la conexión a la base de datos.
 *
 * @return mysqli|false Objeto de conexión MySQLi si es exitoso, false en caso de error.
 */
function connectDB() {
    // Crear una nueva conexión MySQLi
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Verificar si la conexión fue exitosa
    if ($conn->connect_error) {
        // Si hay un error, mostrarlo y terminar la ejecución
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    // Establecer el juego de caracteres a UTF-8 para evitar problemas con caracteres especiales
    $conn->set_charset("utf8mb4");

    return $conn;
}

/**
 * Función para cerrar la conexión a la base de datos.
 *
 * @param mysqli $conn Objeto de conexión MySQLi a cerrar.
 */
function closeDB($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
