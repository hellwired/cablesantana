<?php
require_once 'db_connection.php';

$conn = connectDB();

$key = 'dias_gracia';
$value = '5';
$description = 'Días después del vencimiento antes de considerar mora';

// Check if exists
$checkSql = "SELECT id FROM configuracion WHERE clave = '$key'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows == 0) {
    // Insert
    $sql = "INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $key, $value, $description);
    if ($stmt->execute()) {
        echo "Configuración '$key' agregada con éxito (Valor: $value).<br>";
    } else {
        echo "Error al agregar '$key': " . $conn->error . "<br>";
    }
} else {
    echo "Configuración '$key' ya existe.<br>";
}

closeDB($conn);
?>