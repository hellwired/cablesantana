<?php
require_once 'db_connection.php';

/**
 * Obtener clientes con 2 o más facturas pendientes/vencidas.
 * 
 * @return array Lista de clientes con deuda.
 */
function getClientsForCutoff()
{
    $conn = connectDB();
    $clients = [];

    $sql = "SELECT * FROM vista_cortes_servicio ORDER BY facturas_adeudadas DESC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    } else {
        error_log("Error en getClientsForCutoff: " . $conn->error);
    }

    closeDB($conn);
    return $clients;
}
?>