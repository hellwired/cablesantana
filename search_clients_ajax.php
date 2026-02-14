<?php
// search_clients_ajax.php
session_start();

// Verify authentication and role
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$allowed_roles = ['administrador', 'editor', 'visor'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'client_model.php';

$term = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($term !== '') {
    $clients = searchClients($term);
} else {
    $clients = getAllClients();
}

// Ensure csrf_token is available for the partial if needed (it uses $_SESSION['csrf_token'])
// $clients variable is now available for the partial.

require 'client_rows_partial.php';
?>