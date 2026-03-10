<?php
/**
 * Import Clients Script
 * 
 * Reads 'clientes_import.csv' and imports clients into the database.
 * Usage: Run via browser or CLI.
 */

// Define absolute path to avoid inclusion errors
define('BASE_PATH', dirname(__DIR__) . '/');

// Include DB connection
require_once BASE_PATH . 'db_connection.php';
require_once BASE_PATH . 'client_model.php';

// Configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ini_set('auto_detect_line_endings', true); // Deprecated in PHP 8.1+

$csvFile = __DIR__ . '/clientes_import.csv';
$dryRun = false; // Set to true to test without inserting

echo "<h1>Importación de Clientes desde CSV</h1>";
echo "<p><a href='check_clients.php' target='_blank'>Ver Tabla de Clientes (Diagnóstico)</a></p>";

if (!file_exists($csvFile)) {
    die("<div style='color:red; font-weight:bold;'>Error: No se encontró el archivo '$csvFile'.<br>Por favor, guarde el Excel como 'clientes_import.csv' en la carpeta 'basedatos'.</div>");
}

$handle = fopen($csvFile, "r");
if ($handle === FALSE) {
    die("Error opening CSV file.");
}

$conn = connectDB();
if (!$conn) {
    die("Error connecting to database.");
}

echo "<pre>";
echo "Iniciando importación...\n";

// Detect Delimiter
$delimiter = ",";
$preview = fgets($handle);
if (substr_count($preview, ";") > substr_count($preview, ",")) {
    $delimiter = ";";
}
rewind($handle);
echo "Delimitador detectado: '$delimiter'\n\n";

// Scan first 15 rows to find the Header row
$headerRowFound = false;
$maxScanRows = 15;
$currentRow = 0;

echo "Escaneando filas en busca de encabezados:\n";

while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE && $currentRow < $maxScanRows) {
    $currentRow++;

    // Debug: show row content
    $rowString = strtolower(implode(' ', $row));
    echo "Fila $currentRow: " . htmlspecialchars($rowString) . "\n";

    $score = 0;
    if (strpos($rowString, 'nombre') !== false || strpos($rowString, 'cliente') !== false)
        $score++;
    if (strpos($rowString, 'apellido') !== false)
        $score++;
    if (strpos($rowString, 'dni') !== false || strpos($rowString, 'documento') !== false || strpos($rowString, 'cedula') !== false)
        $score++;
    if (strpos($rowString, 'direccion') !== false || strpos($rowString, 'domicilio') !== false)
        $score++;
    if (strpos($rowString, 'email') !== false || strpos($rowString, 'correo') !== false)
        $score++;

    // If we find enough matches, assume this is the header
    if ($score >= 2) {
        $headers = $row;
        $headerRowFound = true;
        echo ">> ¡Encabezados encontrados en la fila $currentRow! <<\n\n";
        break;
    }
}

if (!$headerRowFound) {
    // If not found, reset pointer and use the first row? Or maybe the file is empty?
    rewind($handle);
    $headers = fgetcsv($handle, 1000, $delimiter);
    echo "Advertencia: No se detectaron encabezados conocidos. Usando la primera fila.\n\n";
}

// Normalize headers found in CSV
// We saw that 'correo electrónico' became 'correoelectrnico' and 'id (dni/cuit)' became 'iddnicuit'
$normalizedHeaders = array_map(function ($h) {
    // Remove accents manually to be safer or just accept what happened. 
    // Let's rely on the same normalization that produced the output the user saw, but add those keys to the map.
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h)));
}, $headers);

echo "Encabezados normalizados: " . implode(', ', $normalizedHeaders) . "\n";

// Map CSV columns to Database fields
$columnMap = [
    'nombre' => ['nombre', 'nombres', 'firstname', 'cliente', 'razonsocial'],
    'apellido' => ['apellido', 'apellidos', 'lastname'],
    'dni' => ['dni', 'documento', 'cedula', 'cuil', 'cuit', 'nrodocumento', 'iddnicuit', 'id'],
    'direccion' => ['direccion', 'domicilio', 'calle', 'address', 'direccin'],
    'email' => ['email', 'correo', 'mail', 'correoelectronico', 'correoelectrnico'],
    'nota' => ['nota', 'notas', 'observacion', 'observaciones', 'comentario'],
    // 'plan' => ['plan', 'servicio', 'paquete'] 
];

$mapIndices = [];

foreach ($columnMap as $dbField => $possibleNames) {
    foreach ($possibleNames as $name) {
        $index = array_search($name, $normalizedHeaders);
        if ($index !== false) {
            $mapIndices[$dbField] = $index;
            echo "Mapeado: '$dbField' -> Columna " . ($index + 1) . " ('{$headers[$index]}')\n";
            break;
        }
    }
}

if (!isset($mapIndices['dni'])) {
    echo "<strong style='color:orange'>Advertencia: No se encontró columna para DNI. Se generarán DNIs temporales o fallará si es requerido.</strong>\n";
}

$stats = [
    'processed' => 0,
    'inserted' => 0,
    'skipped_duplicate' => 0,
    'error' => 0
];

$rowNum = $currentRow; // Start from where we left off
while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
    $rowNum++;

    // Debug data
    if ($stats['processed'] <= 5) {
        echo "Procesando Fila $rowNum: " . htmlspecialchars(implode('|', $data)) . "<br>";
    }

    // Skip empty rows
    if (implode('', $data) == '') {
        echo "Fila $rowNum vacía. Saltando.<br>";
        continue;
    }

    $stats['processed']++;

    // Extract Data
    $nombreFull = isset($mapIndices['nombre']) ? trim($data[$mapIndices['nombre']]) : '';
    $apellido = isset($mapIndices['apellido']) ? trim($data[$mapIndices['apellido']]) : '';

    // Logic to split name if surname is missing
    if (empty($apellido) && !empty($nombreFull)) {
        $parts = explode(' ', $nombreFull);
        if (count($parts) > 1) {
            $apellido = array_pop($parts); // Take last part as surname
            $nombre = implode(' ', $parts); // Join the rest as name
        } else {
            $nombre = $nombreFull;
            $apellido = '.'; // Default char if no surname
        }
    } else {
        $nombre = $nombreFull;
    }

    $dni = isset($mapIndices['dni']) ? trim($data[$mapIndices['dni']]) : '';
    $direccion = isset($mapIndices['direccion']) ? trim($data[$mapIndices['direccion']]) : '';
    if ($direccion === '')
        $direccion = null;

    $email = isset($mapIndices['email']) ? trim($data[$mapIndices['email']]) : '';
    if ($email === '') {
        // DB requires UNIQUE and NOT NULL email. Generate a dummy one.
        $sanitizedDni = preg_replace('/[^a-zA-Z0-9]/', '', $dni);
        $email = "sin_correo_{$sanitizedDni}@cablecolor.local";
        echo "Fila $rowNum: Email vacío. Se generó: $email. ";
    }

    $nota = isset($mapIndices['nota']) ? trim($data[$mapIndices['nota']]) : '';

    // Validation & Handling
    if (empty($dni)) {
        // Generate random unique DNI as requested
        do {
            $generatedDni = 'TMP-' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            $check = getClientByDni($generatedDni);
        } while ($check);

        $dni = $generatedDni;
        echo "Fila $rowNum: DNI vacío. Se asignó ID temporal: $dni. ";
    }

    // Check if DNI exists
    if (!empty($dni)) {
        $existing = getClientByDni($dni);
        if ($existing) {
            echo "Fila $rowNum: Cliente con DNI $dni ya existe (ID: {$existing['id']}). Saltando.\n";
            $stats['skipped_duplicate']++;
            continue;
        }
    }

    // Check if Email exists
    if (!empty($email)) {
        $existingEmail = getClientByEmail($email);
        if ($existingEmail) {
            echo "Fila $rowNum: Cliente con Email $email ya existe. Saltando.\n";
            $stats['skipped_duplicate']++;
            continue;
        }
    }

    // Prepare Insert
    if ($dryRun) {
        echo "Fila $rowNum: Se insertaría -> $nombre $apellido ($dni)\n";
        $stats['inserted']++;
    } else {
        // createClient($dni, $nombre, $apellido, $direccion = null, $correo_electronico = null, $plan_id = 0, $notas_cliente = null)
        $newId = createClient($dni, $nombre, $apellido, $direccion, $email, 0, $nota);

        if ($newId && is_numeric($newId)) {
            echo "Fila $rowNum: Insertado correctamente. ID: $newId\n";
            $stats['inserted']++;
        } else {
            echo "Fila $rowNum: <span style='color:red'>Error al insertar: $newId</span>\n";
            $stats['error']++;
        }
    }
}

fclose($handle);
closeDB($conn);

echo "\n-------------------------------------------------\n";
echo "Resumen de Importación:\n";
echo "Procesados: {$stats['processed']}\n";
echo "Insertados: {$stats['inserted']}\n";
echo "Duplicados (saltados): {$stats['skipped_duplicate']}\n";
echo "Errores: {$stats['error']}\n";
echo "-------------------------------------------------\n";

echo "</pre>";
?>