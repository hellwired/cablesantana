<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

echo "<h1>Seeding Payment Methods (Robust)</h1>";

$conn = connectDB();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$methods = [
    1 => 'Efectivo',
    2 => 'Transferencia',
    3 => 'Mercado Pago',
    4 => 'Tarjeta de Débito',
    5 => 'Tarjeta de Crédito'
];

foreach ($methods as $id => $nombre) {
    // Check if exists
    $check_sql = "SELECT id FROM metodos_pago WHERE id = $id";
    $check = $conn->query($check_sql);
    
    if ($check && $check->num_rows > 0) {
        echo "Method ID $id ($nombre) already exists.<br>";
    } else {
        // Insert using direct SQL to avoid potential prepare/bind issues in this specific environment
        $sql = "INSERT INTO metodos_pago (id, nombre) VALUES ($id, '$nombre')";
        if ($conn->query($sql) === TRUE) {
            echo "SUCCESS: Inserted ID $id: $nombre<br>";
        } else {
            echo "ERROR inserting ID $id: " . $conn->error . "<br>";
        }
    }
}

echo "<h2>Done.</h2>";
closeDB($conn);
?>
