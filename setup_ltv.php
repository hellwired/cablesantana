<?php
/**
 * setup_ltv.php
 *
 * Script para inicializar la tabla de configuración necesaria para el cálculo del LTV.
 * Ejecutar este script una vez desde el navegador.
 */

require_once 'db_connection.php';

echo "<h1>Configuración de Base de Datos para LTV</h1>";

$conn = connectDB();

if ($conn) {
    // 1. Crear tabla 'configuracion' si no existe
    $sql_create_table = "CREATE TABLE IF NOT EXISTS configuracion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(50) NOT NULL UNIQUE,
        valor TEXT NOT NULL,
        descripcion VARCHAR(255),
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql_create_table) === TRUE) {
        echo "<p style='color: green;'>✓ Tabla 'configuracion' verificada/creada correctamente.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al crear la tabla: " . $conn->error . "</p>";
    }

    // 2. Insertar valor por defecto para 'churn_rate' si no existe
    $clave = 'churn_rate';
    $valor_defecto = '5.0'; // 5%
    $descripcion = 'Tasa de cancelación mensual estimada (%)';

    // Verificar si ya existe
    $sql_check = "SELECT id FROM configuracion WHERE clave = '$clave'";
    $result = $conn->query($sql_check);

    if ($result->num_rows == 0) {
        $sql_insert = "INSERT INTO configuracion (clave, valor, descripcion) VALUES ('$clave', '$valor_defecto', '$descripcion')";
        if ($conn->query($sql_insert) === TRUE) {
            echo "<p style='color: green;'>✓ Configuración inicial de 'churn_rate' insertada (Valor: $valor_defecto%).</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al insertar configuración inicial: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ La configuración para 'churn_rate' ya existe.</p>";
    }

    closeDB($conn);
} else {
    echo "<p style='color: red;'>✗ No se pudo conectar a la base de datos.</p>";
}

echo "<br><a href='dashboard.php'>Ir al Dashboard</a>";
?>
