<?php
session_start();

// 1. Include necessary models
require_once 'registration_model.php';
require_once 'user_model.php';
require_once 'audit_model.php';
require_once 'client_model.php';

// 2. Security Checks
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// CSRF Token validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Error de validación CSRF.');
}

// 3. Data Retrieval and Sanitization
$input_data = [
    'plan_id'            => filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT),
    'nombre'             => trim($_POST['nombre'] ?? ''),
    'apellido'           => trim($_POST['apellido'] ?? ''),
    'dni'                => trim($_POST['dni'] ?? ''),
    'direccion'          => trim($_POST['direccion'] ?? ''),
    'correo_electronico' => trim($_POST['correo_electronico'] ?? ''),
    'contrasena'         => $_POST['contrasena'] ?? '',
    'confirmar_contrasena' => $_POST['confirmar_contrasena'] ?? ''
];

// 4. Validation Logic
$errors = [];

// Check for empty required fields
if (empty($input_data['plan_id'])) {
    $errors[] = "El plan seleccionado no es válido.";
}
if (empty($input_data['nombre'])) {
    $errors[] = "El nombre es obligatorio.";
}
if (empty($input_data['apellido'])) {
    $errors[] = "El apellido es obligatorio.";
}
if (empty($input_data['dni'])) {
    $errors[] = "El DNI es obligatorio.";
}
if (empty($input_data['direccion'])) {
    $errors[] = "La dirección es obligatoria.";
}
if (empty($input_data['correo_electronico'])) {
    $errors[] = "El correo electrónico es obligatorio.";
}
if (empty($input_data['contrasena'])) {
    $errors[] = "La contraseña es obligatoria.";
}

// Specific format validation
if (!filter_var($input_data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "El formato del correo electrónico no es válido.";
}

if ($input_data['contrasena'] !== $input_data['confirmar_contrasena']) {
    $errors[] = "Las contraseñas no coinciden.";
}

if (strlen($input_data['contrasena']) < 8) {
    $errors[] = "La contraseña debe tener al menos 8 caracteres.";
}

// Check for duplicates in the database
if (getClientByDni($input_data['dni'])) {
    $errors[] = "El DNI introducido ya está registrado.";
}
if (getClientByEmail($input_data['correo_electronico'])) {
    $errors[] = "El correo electrónico introducido ya está registrado.";
}
// Also check the users table for the email, as it's used as username
if (getUserByEmail($input_data['correo_electronico'])) {
    $errors[] = "El correo electrónico introducido ya está en uso por otro usuario.";
}


// 5. Process based on validation
if (!empty($errors)) {
    // If there are errors, save them and the submitted data to the session
    $_SESSION['errors'] = $errors;
    // Don't save passwords back to the form
    unset($input_data['contrasena'], $input_data['confirmar_contrasena']);
    $_SESSION['old_data'] = $input_data;

    // Redirect back to the registration form
    header('Location: registro.php?plan_id=' . $input_data['plan_id']);
    exit();
}

// 6. Execute Registration
// If validation passes, attempt to register the client
$invoice_id = registerClient($input_data);

if ($invoice_id) {
    // SUCCESS!
    // Clear any old session data
    unset($_SESSION['errors'], $_SESSION['old_data']);
    
    // TODO: Log the user in automatically
    // $user = verifyUser($input_data['correo_electronico'], $input_data['contrasena']);
    // if ($user) {
    //     $_SESSION['user_id'] = $user['id'];
    //     $_SESSION['username'] = $user['nombre_usuario'];
    //     $_SESSION['rol'] = $user['rol'];
    // }

    // Redirect to the payment page
    header('Location: pagar.php?factura_id=' . $invoice_id);
    exit();
} else {
    // If registration fails at the DB level
    $_SESSION['errors'] = ["Hubo un error inesperado durante el registro. Por favor, inténtelo de nuevo."];
    unset($input_data['contrasena'], $input_data['confirmar_contrasena']);
    $_SESSION['old_data'] = $input_data;

    header('Location: registro.php?plan_id=' . $input_data['plan_id']);
    exit();
}

?>