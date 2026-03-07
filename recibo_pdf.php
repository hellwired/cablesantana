<?php
/**
 * recibo_pdf.php
 *
 * Genera un recibo de pago en PDF usando Dompdf.
 * Acceso:
 *   - Con sesión activa (admin/editor/visor): solo pago_id
 *   - Sin sesión (cliente via link WhatsApp): requiere token HMAC válido
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db_connection.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// --- Obtener y validar parámetros ---
$pago_id = (int)($_GET['pago_id'] ?? 0);
$token   = trim($_GET['token'] ?? '');

if ($pago_id <= 0) {
    http_response_code(400);
    exit('Recibo no encontrado.');
}

// --- Control de acceso ---
$app_secret = $_ENV['APP_SECRET'] ?? 'cable_santana_secret';
$token_esperado = hash_hmac('sha256', "recibo-{$pago_id}", $app_secret);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tiene_sesion = isset($_SESSION['user_id']);
$token_valido = hash_equals($token_esperado, $token);

if (!$tiene_sesion && !$token_valido) {
    http_response_code(403);
    exit('Acceso no autorizado. El link puede ser inválido.');
}

// --- Obtener datos del pago ---
$conn = connectDB();
if (!$conn) {
    http_response_code(500);
    exit('Error de conexión a la base de datos.');
}

$stmt = $conn->prepare(
    "SELECT
        p.id AS pago_id,
        p.monto,
        p.fecha_pago,
        p.estado,
        p.referencia_pago,
        p.descripcion,
        mp.nombre AS metodo_pago,
        f.id AS factura_id,
        f.monto AS monto_factura,
        f.fecha_emision,
        f.fecha_vencimiento,
        c.nombre,
        c.apellido,
        c.dni,
        c.telefono,
        pl.nombre_plan,
        u.nombre_usuario AS cobrador
     FROM pagos p
     JOIN facturas f   ON p.factura_id = f.id
     JOIN clientes c   ON f.cliente_id = c.id
     JOIN suscripciones s ON f.suscripcion_id = s.id
     JOIN planes pl    ON s.plan_id = pl.id
     LEFT JOIN metodos_pago mp ON p.metodo_pago_id = mp.id
     LEFT JOIN usuarios u      ON p.registrado_por = u.id
     WHERE p.id = ? AND p.estado = 'exitoso'"
);

if (!$stmt) {
    http_response_code(500);
    exit('Error al preparar consulta.');
}

$stmt->bind_param("i", $pago_id);
$stmt->execute();
$pago = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pago) {
    http_response_code(404);
    exit('Recibo no encontrado o pago no exitoso.');
}

// --- Calcular saldo pendiente ---
$stmt_sal = $conn->prepare(
    "SELECT (f.monto - COALESCE(SUM(pg.monto), 0)) AS saldo
     FROM facturas f
     LEFT JOIN pagos pg ON pg.factura_id = f.id AND pg.estado = 'exitoso'
     WHERE f.id = ?
     GROUP BY f.id"
);
$saldo_pendiente = 0.0;
if ($stmt_sal) {
    $stmt_sal->bind_param("i", $pago['factura_id']);
    $stmt_sal->execute();
    $res = $stmt_sal->get_result()->fetch_assoc();
    $stmt_sal->close();
    if ($res) {
        $saldo_pendiente = max(0, (float)$res['saldo']);
    }
}

closeDB($conn);

// --- Formatear datos ---
$nombre_cliente    = htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']);
$dni               = htmlspecialchars($pago['dni'] ?? 'N/A');
$monto             = number_format((float)$pago['monto'], 2, ',', '.');
$monto_factura     = number_format((float)$pago['monto_factura'], 2, ',', '.');
$saldo_fmt         = number_format($saldo_pendiente, 2, ',', '.');
$fecha_pago        = date('d/m/Y H:i', strtotime($pago['fecha_pago']));
$fecha_emision     = date('d/m/Y', strtotime($pago['fecha_emision']));
$metodo            = htmlspecialchars($pago['metodo_pago'] ?? 'N/A');
$referencia        = htmlspecialchars($pago['referencia_pago'] ?? '');
$descripcion       = htmlspecialchars($pago['descripcion'] ?? '');
$plan              = htmlspecialchars($pago['nombre_plan']);
$cobrador          = htmlspecialchars($pago['cobrador'] ?? 'Sistema');
$fecha_generacion  = date('d/m/Y H:i');
$saldo_color       = $saldo_pendiente > 0 ? '#dc3545' : '#198754';
$saldo_label       = $saldo_pendiente > 0 ? 'Saldo Pendiente' : 'Factura Saldada';

// --- Generar HTML del recibo ---
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 12px;
    color: #222;
    background: #fff;
  }

  /* ENCABEZADO */
  .header {
    background-color: #0d1b2a;
    color: #fff;
    padding: 20px 30px;
    border-bottom: 4px solid #1a73e8;
  }
  .header-empresa {
    font-size: 26px;
    font-weight: bold;
    letter-spacing: 2px;
  }
  .header-subtitulo {
    font-size: 13px;
    color: #a0b4c8;
    margin-top: 3px;
  }
  .header-recibo {
    font-size: 14px;
    font-weight: bold;
    color: #90caf9;
    margin-top: 6px;
  }

  /* CUERPO */
  .body { padding: 24px 30px; }

  /* MONTO DESTACADO */
  .monto-box {
    background: #f0f7ff;
    border: 2px solid #1a73e8;
    border-radius: 6px;
    text-align: center;
    padding: 16px;
    margin-bottom: 20px;
  }
  .monto-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .monto-valor {
    font-size: 36px;
    font-weight: bold;
    color: #1a73e8;
    line-height: 1.2;
  }
  .monto-fecha {
    font-size: 11px;
    color: #888;
    margin-top: 4px;
  }

  /* SECCIONES */
  .seccion {
    margin-bottom: 16px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
  }
  .seccion-titulo {
    background: #f5f5f5;
    padding: 7px 14px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #555;
    border-bottom: 1px solid #e0e0e0;
  }
  .seccion-body {
    padding: 10px 14px;
  }

  /* TABLA DE DATOS */
  .datos-table {
    width: 100%;
    border-collapse: collapse;
  }
  .datos-table td {
    padding: 4px 0;
    vertical-align: top;
  }
  .datos-table td:first-child {
    color: #777;
    width: 38%;
    font-size: 11px;
  }
  .datos-table td:last-child {
    font-weight: bold;
    font-size: 12px;
  }

  /* SALDO */
  .saldo-box {
    border: 2px solid {$saldo_color};
    border-radius: 5px;
    padding: 12px 14px;
    text-align: center;
    margin-bottom: 16px;
  }
  .saldo-label {
    font-size: 11px;
    color: {$saldo_color};
    text-transform: uppercase;
    font-weight: bold;
    letter-spacing: 1px;
  }
  .saldo-valor {
    font-size: 22px;
    font-weight: bold;
    color: {$saldo_color};
  }

  /* FOOTER */
  .footer {
    margin-top: 24px;
    border-top: 1px solid #ddd;
    padding-top: 12px;
    text-align: center;
    font-size: 10px;
    color: #aaa;
  }
  .footer strong {
    color: #888;
  }

  /* STAMP */
  .stamp {
    text-align: center;
    margin: 10px 0;
  }
  .stamp-text {
    display: inline-block;
    border: 3px solid #198754;
    border-radius: 5px;
    color: #198754;
    font-size: 14px;
    font-weight: bold;
    padding: 4px 18px;
    letter-spacing: 2px;
    transform: rotate(-3deg);
  }
</style>
</head>
<body>

<!-- ENCABEZADO -->
<div class="header">
  <div class="header-empresa">📡 CABLE SANTANA</div>
  <div class="header-subtitulo">Sistema de Gestión de Suscripciones</div>
  <div class="header-recibo">RECIBO DE PAGO N° {$pago['pago_id']}</div>
</div>

<div class="body">

  <!-- MONTO DESTACADO -->
  <div class="monto-box">
    <div class="monto-label">Monto Pagado</div>
    <div class="monto-valor">$ {$monto}</div>
    <div class="monto-fecha">{$fecha_pago}</div>
  </div>

  <!-- SELLO PAGADO -->
  <div class="stamp">
    <span class="stamp-text">✓ PAGADO</span>
  </div>

  <br>

  <!-- DATOS DEL CLIENTE -->
  <div class="seccion">
    <div class="seccion-titulo">👤 Datos del Cliente</div>
    <div class="seccion-body">
      <table class="datos-table">
        <tr>
          <td>Nombre:</td>
          <td>{$nombre_cliente}</td>
        </tr>
        <tr>
          <td>DNI:</td>
          <td>{$dni}</td>
        </tr>
        <tr>
          <td>Teléfono:</td>
          <td>{$pago['telefono']}</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- DATOS DEL PAGO -->
  <div class="seccion">
    <div class="seccion-titulo">💳 Datos del Pago</div>
    <div class="seccion-body">
      <table class="datos-table">
        <tr>
          <td>Factura N°:</td>
          <td>{$pago['factura_id']}</td>
        </tr>
        <tr>
          <td>Fecha de Pago:</td>
          <td>{$fecha_pago}</td>
        </tr>
        <tr>
          <td>Método de Pago:</td>
          <td>{$metodo}</td>
        </tr>
HTML;

if (!empty($referencia)) {
    $html .= <<<HTML
        <tr>
          <td>Referencia:</td>
          <td>{$referencia}</td>
        </tr>
HTML;
}
if (!empty($descripcion)) {
    $html .= <<<HTML
        <tr>
          <td>Descripción:</td>
          <td>{$descripcion}</td>
        </tr>
HTML;
}

$html .= <<<HTML
      </table>
    </div>
  </div>

  <!-- DATOS DEL SERVICIO -->
  <div class="seccion">
    <div class="seccion-titulo">📺 Servicio Contratado</div>
    <div class="seccion-body">
      <table class="datos-table">
        <tr>
          <td>Plan:</td>
          <td>{$plan}</td>
        </tr>
        <tr>
          <td>Monto Factura:</td>
          <td>$ {$monto_factura}</td>
        </tr>
        <tr>
          <td>Fecha Emisión:</td>
          <td>{$fecha_emision}</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- COBRADOR -->
  <div class="seccion">
    <div class="seccion-titulo">👷 Cobrador</div>
    <div class="seccion-body">
      <table class="datos-table">
        <tr>
          <td>Registrado por:</td>
          <td>{$cobrador}</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- SALDO PENDIENTE -->
  <div class="saldo-box">
    <div class="saldo-label">{$saldo_label}</div>
    <div class="saldo-valor">$ {$saldo_fmt}</div>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <p>Documento generado el <strong>{$fecha_generacion}</strong></p>
    <p>Este comprobante es válido como constancia de pago — <strong>Cable Santana</strong></p>
  </div>

</div>
</body>
</html>
HTML;

// --- Generar PDF con Dompdf ---
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Inline: abre en el navegador para ver e imprimir
$dompdf->stream("recibo-pago-{$pago_id}.pdf", ['Attachment' => 0]);
