<?php
/**
 * notificacion_model.php
 *
 * Modelo para el envio de notificaciones WhatsApp a morosos via CallMeBot API.
 * Funciones para obtener morosos, enviar mensajes, registrar historial.
 */

require_once 'db_connection.php';

/**
 * Obtiene morosos con telefono y apikey configurados,
 * que no hayan sido notificados en las ultimas 24 horas por la misma factura.
 *
 * @return array Lista de morosos con datos para notificacion
 */
function getDebtorsForNotification(): array
{
    $conn = connectDB();
    if (!$conn) return [];

    $sql = "SELECT
                c.id AS cliente_id,
                c.nombre,
                c.apellido,
                c.telefono,
                c.whatsapp_apikey,
                COUNT(f.id) AS facturas_pendientes,
                SUM(f.monto) AS total_deuda,
                GROUP_CONCAT(f.id) AS factura_ids
            FROM clientes c
            JOIN facturas f ON f.cliente_id = c.id
            WHERE f.estado IN ('pendiente', 'vencida')
              AND c.telefono IS NOT NULL AND c.telefono != ''
              AND c.whatsapp_apikey IS NOT NULL AND c.whatsapp_apikey != ''
            GROUP BY c.id
            HAVING facturas_pendientes > 0
            ORDER BY total_deuda DESC";

    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error en getDebtorsForNotification: " . $conn->error);
        closeDB($conn);
        return [];
    }

    $debtors = [];
    while ($row = $result->fetch_assoc()) {
        // Verificar si ya fue notificado en las ultimas 24 horas
        $factura_ids = explode(',', $row['factura_ids']);
        $was_notified_recently = false;

        foreach ($factura_ids as $fid) {
            $stmt = $conn->prepare(
                "SELECT id FROM notificaciones
                 WHERE cliente_id = ? AND factura_id = ? AND estado = 'enviado'
                   AND fecha_envio > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $fid_int = (int) $fid;
            $cliente_id = (int) $row['cliente_id'];
            $stmt->bind_param("ii", $cliente_id, $fid_int);
            $stmt->execute();
            $check = $stmt->get_result();
            if ($check->num_rows > 0) {
                $was_notified_recently = true;
            }
            $stmt->close();
        }

        if (!$was_notified_recently) {
            $debtors[] = $row;
        }
    }

    closeDB($conn);
    return $debtors;
}

/**
 * Envia un mensaje de WhatsApp via CallMeBot API.
 *
 * @param string $phone Numero de telefono (formato internacional, ej: +5491234567890)
 * @param string $apikey API key del cliente en CallMeBot
 * @param string $message Mensaje a enviar
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendWhatsAppMessage(string $phone, string $apikey, string $message): array
{
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $encoded_message = urlencode($message);

    $url = "https://api.callmebot.com/whatsapp.php?phone=" . $phone
         . "&text=" . $encoded_message
         . "&apikey=" . urlencode($apikey);

    // Intentar con curl primero, fallback a file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'CableColor-Notifications/1.0'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['success' => false, 'error' => 'cURL error: ' . $curl_error];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'error' => null];
        }

        return ['success' => false, 'error' => "HTTP $http_code: $response"];
    }

    // Fallback: file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'CableColor-Notifications/1.0'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return ['success' => false, 'error' => 'Error al conectar con CallMeBot API'];
    }

    return ['success' => true, 'error' => null];
}

/**
 * Construye el mensaje de notificacion reemplazando placeholders.
 *
 * @param array $client Datos del cliente moroso
 * @param string $template Plantilla del mensaje
 * @return string Mensaje construido
 */
function buildNotificationMessage(array $client, string $template): string
{
    $replacements = [
        '{nombre}' => ($client['nombre'] ?? '') . ' ' . ($client['apellido'] ?? ''),
        '{facturas}' => $client['facturas_pendientes'] ?? '0',
        '{monto}' => number_format((float) ($client['total_deuda'] ?? 0), 2)
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Registra una notificacion en la tabla notificaciones.
 *
 * @param int $cliente_id ID del cliente
 * @param string $tipo Tipo de notificacion (whatsapp, email, sms)
 * @param string $mensaje Texto del mensaje enviado
 * @param string $estado Estado (enviado, fallido, pendiente)
 * @param string|null $error Detalle del error si hubo
 * @param int|null $factura_id ID de la factura asociada
 * @return int|false ID del registro insertado o false
 */
function logNotification(int $cliente_id, string $tipo, string $mensaje, string $estado, ?string $error = null, ?int $factura_id = null)
{
    $conn = connectDB();
    if (!$conn) return false;

    $stmt = $conn->prepare(
        "INSERT INTO notificaciones (cliente_id, tipo, mensaje, estado, error_detalle, factura_id)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        error_log("Error preparando logNotification: " . $conn->error);
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("issssi", $cliente_id, $tipo, $mensaje, $estado, $error, $factura_id);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        closeDB($conn);
        return $id;
    }

    error_log("Error ejecutando logNotification: " . $stmt->error);
    $stmt->close();
    closeDB($conn);
    return false;
}

/**
 * Obtiene el historial de notificaciones enviadas.
 *
 * @param int $limit Cantidad de registros a retornar
 * @return array
 */
function getNotificationHistory(int $limit = 100): array
{
    $conn = connectDB();
    if (!$conn) return [];

    $stmt = $conn->prepare(
        "SELECT n.*, c.nombre, c.apellido, c.telefono
         FROM notificaciones n
         JOIN clientes c ON n.cliente_id = c.id
         ORDER BY n.fecha_envio DESC
         LIMIT ?"
    );
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();
    closeDB($conn);
    return $history;
}

/**
 * Obtiene el template de WhatsApp desde la tabla configuracion.
 *
 * @return string Template del mensaje
 */
function getWhatsAppTemplate(): string
{
    $conn = connectDB();
    if (!$conn) return '';

    $result = $conn->query("SELECT valor FROM configuracion WHERE clave = 'whatsapp_template'");
    $template = '';
    if ($result && $result->num_rows > 0) {
        $template = $result->fetch_assoc()['valor'];
    }

    closeDB($conn);
    return $template;
}

/**
 * Actualiza el template de WhatsApp en configuracion.
 *
 * @param string $template Nuevo template
 * @return bool
 */
function updateWhatsAppTemplate(string $template): bool
{
    $conn = connectDB();
    if (!$conn) return false;

    $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'whatsapp_template'");
    if (!$stmt) {
        closeDB($conn);
        return false;
    }

    $stmt->bind_param("s", $template);
    $success = $stmt->execute();
    $stmt->close();
    closeDB($conn);
    return $success;
}
