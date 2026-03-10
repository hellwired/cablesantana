<?php
// ajax_search_clients.php
// Start output buffering to prevent header errors or debris
ob_start();

// Disable display errors shown to user
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Prepare default JSON structure
header('Content-Type: application/json; charset=utf-8');

// Security check
/* if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['results' => [], 'error' => 'Unauthorized']);
    exit;
} */

require_once 'client_model.php';

try {
    $term = $_GET['q'] ?? '';
    $results = [];

    if (strlen($term) >= 1) {
        $clientsData = searchClients($term);
    } else {
        // Limit default load to 50 to avoid performance issues
        // We can use a custom query or limit the getAllClients array
        $all = getAllClients();
        $clientsData = array_slice($all, 0, 50);
    }

    foreach ($clientsData as $client) {
        $nombre_plan = $client['nombre_plan'] ?? 'Sin Plan';
        $precio_mensual = $client['precio_mensual'] ?? 0;
        
        $displayText = $client['nombre'] . ' ' . $client['apellido'] . ' - DNI: ' . $client['dni'];

        $results[] = [
            'id' => $client['id'],
            'text' => $displayText,
            'nombre' => $client['nombre'],
            'apellido' => $client['apellido'],
            'dni' => $client['dni'],
            'precio_mensual' => $precio_mensual,
            'plan_id' => $client['plan_id'] ?? 0,
            'plan_nombre' => $nombre_plan
        ];
    }
    
    // Clear buffer and send JSON
    ob_end_clean();
    echo json_encode(['results' => $results]);

} catch (Exception $e) {
    ob_end_clean();
    // Return empty results on error to not break Select2
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}
?>