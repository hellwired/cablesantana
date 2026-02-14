<?php
/**
 * save_ltv_config.php
 *
 * Procesa el formulario de configuración del LTV (Churn Rate).
 */

session_start();

// Verificar permisos (solo administradores deberían poder cambiar esto)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit();
}

require_once 'dashboard_model.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $churn_rate = filter_input(INPUT_POST, 'churn_rate', FILTER_VALIDATE_FLOAT);

    if ($churn_rate !== false && $churn_rate > 0) {
        if (updateChurnRate($churn_rate)) {
            $_SESSION['message'] = "Configuración de LTV actualizada correctamente.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error al actualizar la configuración.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Por favor ingrese una tasa de cancelación válida (mayor a 0).";
        $_SESSION['message_type'] = "warning";
    }
}

header('Location: dashboard.php');
exit();
?>
