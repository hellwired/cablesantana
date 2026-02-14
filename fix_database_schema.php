<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

echo "<h1>Fixing Database Schema</h1>";

$conn = connectDB();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Drop Foreign Key on pagos
echo "<h2>1. Dropping Foreign Key 'fk_pagos_metodo'</h2>";
$sql = "ALTER TABLE pagos DROP FOREIGN KEY fk_pagos_metodo";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS: Foreign key dropped.<br>";
} else {
    echo "WARNING: Could not drop FK (might not exist or different name): " . $conn->error . "<br>";
}

// 2. Rename existing incorrect table
echo "<h2>2. Renaming incorrect 'metodos_pago' table</h2>";
$sql = "RENAME TABLE metodos_pago TO metodos_pago_archivado";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS: Table renamed to 'metodos_pago_archivado'.<br>";
} else {
    echo "WARNING: Could not rename table (might not exist): " . $conn->error . "<br>";
}

// 3. Create new correct table
echo "<h2>3. Creating new 'metodos_pago' table</h2>";
$sql = "CREATE TABLE IF NOT EXISTS metodos_pago (
    id INT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS: New table 'metodos_pago' created.<br>";
} else {
    echo "ERROR: Could not create table: " . $conn->error . "<br>";
}

// 4. Seed Data
echo "<h2>4. Seeding Data</h2>";
$methods = [
    1 => 'Efectivo',
    2 => 'Transferencia',
    3 => 'Mercado Pago',
    4 => 'Tarjeta de Débito',
    5 => 'Tarjeta de Crédito'
];

foreach ($methods as $id => $nombre) {
    $sql = "INSERT INTO metodos_pago (id, nombre) VALUES ($id, '$nombre') ON DUPLICATE KEY UPDATE nombre='$nombre'";
    if ($conn->query($sql) === TRUE) {
        echo "Inserted/Updated ID $id: $nombre<br>";
    } else {
        echo "Error inserting ID $id: " . $conn->error . "<br>";
    }
}

// 5. Restore Foreign Key
echo "<h2>5. Restoring Foreign Key</h2>";
// Note: We need to ensure all existing pagos have valid metodo_pago_id before adding FK.
// We'll update any invalid ones to 1 (Efectivo) just in case.
$conn->query("UPDATE pagos SET metodo_pago_id = 1 WHERE metodo_pago_id NOT IN (SELECT id FROM metodos_pago)");

$sql = "ALTER TABLE pagos ADD CONSTRAINT fk_pagos_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id)";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS: Foreign key 'fk_pagos_metodo' restored.<br>";
} else {
    echo "ERROR: Could not restore FK: " . $conn->error . "<br>";
}

echo "<h2>Done.</h2>";
closeDB($conn);
?>
