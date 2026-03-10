<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__) . '/');
require_once BASE_PATH . 'db_connection.php';
require_once BASE_PATH . 'client_model.php'; // For getClientByDni/Email

$csvFile = __DIR__ . '/clientes_import.csv';

echo "<h1>Importación de Deudas (Legacy)</h1>";

if (!file_exists($csvFile)) {
    die("<div style='color:red'>Error: No se encontró el archivo '$csvFile'.</div>");
}

$conn = connectDB();
if (!$conn)
    die("Error de conexión.");

$handle = fopen($csvFile, "r");

// Detect Delimiter check (same as before)
$delimiter = ",";
$preview = fgets($handle);
if (substr_count($preview, ";") > substr_count($preview, ",")) {
    $delimiter = ";";
}
rewind($handle);

// Scan for headers (simplified, assuming we know them or re-scan)
// Let's re-scan to be safe
$headerRowFound = false;
$headers = [];
$normalizedHeaders = [];
$currentRow = 0;

while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
    $currentRow++;
    $rowString = strtolower(implode(' ', $row));

    // Check for 'importe de deuda'
    if (strpos($rowString, 'importe de deuda') !== false || strpos($rowString, 'saldo') !== false) {
        $headers = $row;
        $headerRowFound = true;
        break;
    }
}

if (!$headerRowFound) {
    die("No se encontraron encabezados con 'Importe de Deuda'.<br>");
}

// Normalize headers
$normalizedHeaders = array_map(function ($h) {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h)));
}, $headers);

// Map indices
$idxDni = array_search('iddnicuit', $normalizedHeaders);
if ($idxDni === false)
    $idxDni = array_search('dni', $normalizedHeaders);

$idxDeuda = array_search('importededeuda', $normalizedHeaders);
if ($idxDeuda === false)
    $idxDeuda = array_search('deuda', $normalizedHeaders);

echo "Indice DNI: $idxDni<br>";
echo "Indice Deuda: $idxDeuda<br>";

if ($idxDni === false || $idxDeuda === false) {
    die("No se pudieron mapear las columnas DNI o DEUDA.<br>");
}

$processed = 0;
$invoices_created = 0;

// Scan data
while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
    if (implode('', $data) == '')
        continue;

    $dni = trim($data[$idxDni]);
    $deudaRaw = trim($data[$idxDeuda]);

    // Clean currency
    // Example: "$ 344.000,00" or "$ 344000" or "344000"
    // Remove $ and spaces
    $deudaClean = str_replace(['$', ' '], '', $deudaRaw);

    // Determine format. 
    // If it has ',' and '.' it's tricky. usually ',' is decimal in spanish.
    // Let's assume standard spanish format: 1.000,00
    // Replace . (thousands) with empty, Replace , (decimal) with .
    if (strpos($deudaClean, ',') !== false) {
        $deudaClean = str_replace('.', '', $deudaClean); // Remove thousands sep
        $deudaClean = str_replace(',', '.', $deudaClean); // Convert decimal
    }

    $monto = (float) $deudaClean;

    if ($monto <= 0) {
        continue; // No debt
    }

    $processed++;

    // Find Client
    // We generated dummy emails for some, checking DNI is safer if we have it?
    // Wait, some DNIs were missing and we generated generatedDni (TMP-...).
    // The CSV has EMPTY DNI for those rows.
    // If CSV DNI is empty, we must rely on... order? Impossible.
    // Update: In import_clients.php, we generated TMP- DNIs using random numbers.
    // We CANNOT match those clients back to the CSV unless we assumed sequential order, which is unsafe.
    // BUT allow me to correct: The import_clients script generated unique random IDs.
    // We cannot reliably match clients without DNI.
    // HOWEVER, for clients WITH DNI (e.g. 41419939), we can match.
    // For rows with empty DNI, we can't import debt unless we rely on Name?
    // Let's match by DNI first. If empty, check Name? 
    // (Name normalization might be tricky).
    // Let's start with DNI.

    $client = null;
    if (!empty($dni)) {
        $client = getClientByDni($dni);
    }

    if (!$client) {
        echo "Fila $currentRow: Cliente no encontrado (DNI: $dni). Saltando.<br>";
        continue;
    }

    // Check if invoice already exists to avoid duplicates
    // We can check if any invoice exists for this client with this amount?
    // Or just create it. Let's check state 'vencida' and amount.
    $checkSql = "SELECT id FROM facturas WHERE cliente_id = ? AND monto = ?";
    $stmtCheck = $conn->prepare($checkSql);
    $stmtCheck->bind_param("id", $client['id'], $monto);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        echo "Fila $currentRow: Factura de \$$monto ya existe para cliente {$client['id']}. Saltando.<br>";
        $stmtCheck->close();
        continue;
    }
    $stmtCheck->close();

    // Create Subscription (Dummy) if needed
    // Check if subscription exists
    $sqlSub = "SELECT id FROM suscripciones WHERE cliente_id = ? LIMIT 1";
    $stmtSub = $conn->prepare($sqlSub);
    $stmtSub->bind_param("i", $client['id']);
    $stmtSub->execute();
    $resSub = $stmtSub->get_result();
    $sub_id = 0;

    if ($rowSub = $resSub->fetch_assoc()) {
        $sub_id = $rowSub['id'];
    } else {
        // Create Dummy Subscription
        // Get valid plan ID from DB to satisfy Foreign Key
        $resPlan = $conn->query("SELECT id FROM planes LIMIT 1");
        if ($resPlan && $resPlan->num_rows > 0) {
            $plan_id_dummy = $resPlan->fetch_assoc()['id'];
        } else {
            die("<br>Error Crítico: No hay planes registrados en la tabla 'planes'. Cree uno primero para poder importar las deudas.");
        }

        $stmtInsSub = $conn->prepare("INSERT INTO suscripciones (cliente_id, plan_id, estado, fecha_inicio, fecha_proximo_cobro) VALUES (?, ?, 'activa', '2025-01-01', '2025-01-01')");
        $stmtInsSub->bind_param("ii", $client['id'], $plan_id_dummy);
        $stmtInsSub->execute();
        $sub_id = $stmtInsSub->insert_id;
        $stmtInsSub->close();
    }
    $stmtSub->close();

    // Create Invoice
    $stmtInv = $conn->prepare("INSERT INTO facturas (suscripcion_id, cliente_id, monto, fecha_emision, fecha_vencimiento, estado) VALUES (?, ?, ?, '2025-12-01', '2025-12-01', 'vencida')");
    $stmtInv->bind_param("iid", $sub_id, $client['id'], $monto);

    if ($stmtInv->execute()) {
        echo "Fila $currentRow: Factura de \$$monto creada para cliente {$client['nombre']} ({$client['id']}).<br>";
        $invoices_created++;
    } else {
        echo "Error al crear factura: " . $conn->error . "<br>";
    }
    $stmtInv->close();
}

fclose($handle);
echo "<h3>Proceso Terminado. Facturas Creadas: $invoices_created</h3>";
?>