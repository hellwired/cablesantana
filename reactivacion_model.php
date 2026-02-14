<?php
require_once 'db_connection.php';
require_once 'morosos_model.php'; // Reutilizamos getSurchargeValue()

/**
 * Obtener clientes con servicio cortado (> 3 facturas pendientes).
 * 
 * @param string $searchTerm Término de búsqueda.
 * @return array Lista de clientes.
 */
function getCutoffClients($searchTerm = '')
{
    $conn = connectDB();
    $clients = [];

    $sql = "SELECT * FROM vista_servicio_cortado";

    if (!empty($searchTerm)) {
        $term = "%" . $conn->real_escape_string($searchTerm) . "%";
        $sql .= " WHERE dni LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR CONCAT(nombre, ' ', apellido) LIKE ?";
    }

    $sql .= " ORDER BY facturas_adeudadas DESC";

    if (!empty($searchTerm)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $term, $term, $term, $term);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }

    closeDB($conn);
    return $clients;
}

/**
 * Calcular costo de reactivación:
 * Suma de las 2 facturas más recientes + (Recargo Configurado * Total Meses Deuda).
 * 
 * @param int $clientId ID del cliente.
 * @param int $totalMonths Total de meses que debe (para calcular recargo).
 * @return array ['monto_facturas' => float, 'recargo_total' => float, 'total_reactivacion' => float]
 */
function calculateReactivationCost($clientId, $totalMonths)
{
    $conn = connectDB();

    // 1. Obtener las 2 facturas más recientes (por fecha_vencimiento DESC)
    $sql = "SELECT monto FROM facturas 
            WHERE cliente_id = ? AND estado IN ('pendiente', 'vencida') 
            ORDER BY fecha_vencimiento DESC 
            LIMIT 2";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $montoFacturas = 0.00;
    while ($row = $result->fetch_assoc()) {
        $montoFacturas += $row['monto'];
    }
    $stmt->close();
    closeDB($conn);

    // 2. Calcular Recargo
    $surchargePerMonth = getSurchargeValue();
    $totalSurcharge = $surchargePerMonth * $totalMonths;

    return [
        'monto_facturas' => $montoFacturas,
        'recargo_total' => $totalSurcharge,
        'total_reactivacion' => $montoFacturas + $totalSurcharge
    ];
}
?>