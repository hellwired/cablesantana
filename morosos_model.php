<?php
require_once 'db_connection.php';

/**
 * Obtener clientes con 1 o más facturas pendientes/vencidas (Vista: vista_deudores).
 * 
 * @param string $searchTerm Término de búsqueda (DNI, Nombre o Apellido).
 * @return array Lista de deudores.
 */
function getDebtors($searchTerm = '')
{
    $conn = connectDB();
    $debtors = [];

    $sql = "SELECT * FROM vista_deudores";

    // Build WHERE clause dynamically
    $whereClauses = [];
    $types = "";
    $params = [];

    // Filter: Only up to 2 months of debt (Warning Stage)
    $whereClauses[] = "facturas_adeudadas <= 2";

    if (!empty($searchTerm)) {
        $term = "%" . $conn->real_escape_string($searchTerm) . "%";
        $whereClauses[] = "(dni LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR CONCAT(nombre, ' ', apellido) LIKE ?)";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $types .= "ssss";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " ORDER BY total_deuda DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $debtors[] = $row;
        }
    }

    // Si se usó statement, cerrarlo
    if (isset($stmt)) {
        $stmt->close();
    }

    closeDB($conn);
    return $debtors;
}

/**
 * Obtener el valor actual del recargo por mora.
 * 
 * @return float Valor del recargo (0 si no está configurado).
 */
function getSurchargeValue()
{
    $conn = connectDB();
    $val = 0.00;

    $sql = "SELECT valor FROM configuracion WHERE clave = 'recargo_mora'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $val = (float) $row['valor'];
    }

    closeDB($conn);
    return $val;
}

/**
 * Actualizar el valor del recargo por mora.
 * 
 * @param float $newValue Nuevo valor.
 * @return bool True si tuvo éxito.
 */
function updateSurchargeValue($newValue)
{
    if ($_SESSION['rol'] !== 'administrador')
        return false;

    $conn = connectDB();
    $val = (float) $newValue;

    $sql = "UPDATE configuracion SET valor = ? WHERE clave = 'recargo_mora'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("d", $val);

    $success = $stmt->execute();

    $stmt->close();
    closeDB($conn);

    return $success;
}

/**
 * Obtener dias de gracia configurados.
 * 
 * @return int Días de gracia.
 */
function getGraceDays()
{
    $conn = connectDB();
    $val = 0;

    $sql = "SELECT valor FROM configuracion WHERE clave = 'dias_gracia'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $val = (int) $row['valor'];
    }

    closeDB($conn);
    return $val;
}

/**
 * Actualizar dias de gracia.
 * 
 * @param int $days Nuevos días.
 * @return bool Success.
 */
function updateGraceDays($days)
{
    if ($_SESSION['rol'] !== 'administrador')
        return false;

    $conn = connectDB();
    $val = (int) $days;

    $sql = "UPDATE configuracion SET valor = ? WHERE clave = 'dias_gracia'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $val); // storing as string/text in DB usually but 's' works for int too

    $success = $stmt->execute();

    $stmt->close();
    closeDB($conn);

    return $success;
}
?>