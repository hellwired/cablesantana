<?php
/**
 * db_connection.php
 *
 * Este archivo contiene la configuración y la lógica para conectar
 * a la base de datos MariaDB.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Establecer la zona horaria predeterminada para PHP
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // If .env is missing or unreadable, we can either die or continue with defaults.
    // For now, let's log it and continue, assuming defaults might work or environment variables are set at server level.
    error_log("Error loading .env file: " . $e->getMessage());
}

// Definir las constantes de conexión a la base de datos usando variables de entorno
define('DB_SERVER', $_ENV['DB_SERVER'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? 'Admin001');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'cable_santana');

/**
 * Función para establecer la conexión a la base de datos.
 *
 * @return mysqli|false Objeto de conexión MySQLi si es exitoso, false en caso de error.
 */
/**
 * Función para establecer la conexión a la base de datos (Singleton).
 *
 * @return mysqli|false Objeto de conexión MySQLi si es exitoso, false en caso de error.
 */
function connectDB() {
    static $conn = null;

    // Si ya existe una conexión, retornarla
    if ($conn !== null) {
        return $conn;
    }

    // Crear una nueva conexión MySQLi
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Verificar si la conexión fue exitosa
    if ($conn->connect_error) {
        error_log("Error de conexión a la base de datos: " . $conn->connect_error);
        $conn = null; // Reset on failure
        // die("Error de conexión a la base de datos."); 
        return false;
    }

    // Establecer el juego de caracteres a UTF-8 para evitar problemas con caracteres especiales
    $conn->set_charset("utf8mb4");

    // Establecer la zona horaria de la conexión MySQL a Argentina (-03:00)
    $conn->query("SET time_zone = '-03:00'");

    return $conn;
}

/**
 * Función para cerrar la conexión a la base de datos.
 * 
 * En el patrón Singleton para scripts web PHP, generalmente dejamos que PHP 
 * cierre la conexión al finalizar el script. Esta función se mantiene para
 * compatibilidad hacia atrás pero no cierra la conexión estática para permitir reutilización.
 *
 * @param mysqli $conn Objeto de conexión MySQLi (opcional).
 * @param bool $force Si es true, fuerza el cierre de la conexión.
 */
function closeDB($conn = null, $force = false) {
    if ($force && $conn) {
        $conn->close();
    }
    // De lo contrario, no hacemos nada para permitir que connectDB() reutilice la conexión
    // en llamadas subsiguientes dentro del mismo request.
}
?>
