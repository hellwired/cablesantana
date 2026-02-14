# Documentación de Normalización y Mejoras - Cable Santana

Esta documentación detalla los cambios realizados en el sistema para la normalización de la base de datos de clientes, la importación de datos históricos y las mejoras en la gestión de clientes (CRUD).

## 1. Importación y Normalización de Clientes

### Objetivo
Migrar los datos de clientes desde un archivo Excel (`Clientes - Santa Ana Cable Color.xlsx`) a la base de datos MySQL, normalizando la estructura y asignando planes de servicio correctos.

### Scripts Desarrollados
*   **`basedatos/import_clients.php`**: Script principal de importación.
    *   Lee el archivo Excel usando `PhpSpreadsheet`.
    *   Mapea las columnas del Excel a la tabla `clientes`.
    *   **Manejo de Planes**: Detecta el precio en el Excel y asigna el `plan_id` correspondiente (1 para Normal, 2 para Jubilado, 3 para Internet, etc.) consultando la tabla `planes`.
    *   **Suscripciones**: Crea automáticamente una suscripción activa en la tabla `suscripciones` vinculada al cliente y al plan.
    *   **Facturas**: Genera una factura inicial "pendiente" para el mes en curso.

### Cambios en Base de Datos
*   Se verificó la estructura de las tablas `clientes`, `suscripciones`, `planes` y `facturas` en `cable_santana.sql`.
*   Se identificó que `suscripciones` requiere un `plan_id` válido (FK).

---

## 2. Mejoras en Gestión de Clientes (CRUD)

### Edición de Plan de Servicio
Anteriormente, no era posible cambiar el plan contratado desde la interfaz de edición de clientes.

*   **Backend (`client_model.php`)**:
    *   Se actualizó `getAllClients()` para obtener el `plan_id` de la suscripción más reciente del cliente.
    *   Se creó la función `updateClientSubscriptionPlan($client_id, $plan_id)` para actualizar la suscripción existente o crear una nueva si se cambia el plan.
    *   Se corrigió un error de `bind_param` (referencias) en la función `updateClient`.
*   **Frontend (`clients_ui.php`)**:
    *   Se añadió el campo `<select>` "Plan Contratado" en el Modal de Edición.
    *   Se añadió lógica JavaScript para pre-cargar el plan actual del cliente al abrir el modal.
    *   Se actualizó el controlador POST para procesar el cambio de plan.

---

## 3. Buscador de Clientes en Vivo (Live Search)

### Objetivo
Mejorar la usabilidad y rendimiento permitiendo filtrar clientes en tiempo real sin recargar la página.

### Implementación
1.  **Refactorización (`client_rows_partial.php`)**:
    *   Se extrajo el código HTML que genera las filas de la tabla (`<tr>...</tr>`) a un archivo parcial reutilizable.
2.  **Endpoint AJAX (`search_clients_ajax.php`)**:
    *   Recibe un parámetro `search` vía GET.
    *   Llama a la nueva función `searchClients($term)` en el modelo.
    *   Devuelve el HTML generado usando `client_rows_partial.php`.
3.  **Modelo (`client_model.php`)**:
    *   Se añadió la función `searchClients($term)` que realiza una búsqueda `LIKE` sobre los campos: `dni`, `nombre`, `apellido`, `direccion` y `correo_electronico`.
4.  **Frontend (`clients_ui.php`)**:
    *   Se añadió un `input` de búsqueda.
    *   Se implementó JavaScript con "debounce" (retraso de 300ms) para escuchar lo que el usuario escribe y hacer peticiones AJAX a `search_clients_ajax.php`.
    *   Se actualiza el `<tbody>` de la tabla dinámicamente con los resultados.

---

## 4. Correcciones de Errores (Bug Fixes)

*   **Validación de Email**: Se reemplazó una expresión regular compleja que causaba errores en JavaScript (`SyntaxError: Invalid regular expression`) por una validación estándar y robusta.
*   **Estilos de Editor (Quill)**: Se añadieron los estilos faltantes (`quill.snow.css`) para corregir el problema visual de los iconos gigantes en el editor de notas.
*   **Errores PHP**: Se solucionó el error fatal por "función no definida" moviendo `searchClients` al ámbito global en `client_model.php`.

---

## 5. Configuración de Días de Gracia

### Objetivo
Permitir un margen de tiempo (días de gracia) después del vencimiento de una factura antes de que se considere "vencida" y se apliquen recargos por mora.

### Implementación
1.  **Base de Datos**: Se agregó la clave `dias_gracia` en la tabla `configuracion` (valor por defecto: 5).
2.  **Interfaz (`morosos_ui.php`)**: Se añadió un campo en el panel de "Configuración de Cobranza" para modificar los días de gracia.
3.  **Lógica (`check_client_status.php`)**:
    *   Al analizar las facturas de un cliente, el sistema calcula si la fecha actual supera `fecha_vencimiento + dias_gracia`.
    *   Si la factura está vencida pero dentro del periodo de gracia:
        *   No se cuenta como "morosa".
        *   No suma para el cálculo de bloqueos o cortes.
        *   El cliente puede ver el monto original sin recargos.
    *   Solo cuando pasan los días de gracia se aplica el estado de morosidad y los recargos correspondientes.

### Archivos Afectados
*   `/morosos_ui.php` (UI de configuración)
*   `/morosos_model.php` (Funciones `getGraceDays`, `updateGraceDays`)
*   `/check_client_status.php` (Lógica de negocio principal)
*   `/update_config_grace.php` (Script de migración DB)

---

## 6. Facturación Automática Mensual

### Objetivo
Eliminar la generación manual de facturas mes a mes. Un botón en el dashboard permite al administrador generar todas las facturas del mes con un solo clic.

### Implementación

1.  **Modelo (`facturacion_model.php`)** — Archivo nuevo:
    *   `generateMonthlyInvoices()`: Consulta todas las suscripciones activas, verifica si ya existe factura del mes actual (previene duplicados), crea facturas con `estado = 'pendiente'` y `fecha_vencimiento = último día del mes`. Actualiza `fecha_proximo_cobro` al 1ro del mes siguiente. Marca facturas pasadas de `pendiente` → `vencida` si ya vencieron. Todo ejecutado en una transacción atómica. Retorna resumen: `{created, skipped, total_amount, errors, overdue_updated}`.
    *   `getInvoiceGenerationStatus()`: Retorna cuántas suscripciones activas hay vs cuántas ya tienen factura este mes, para mostrar el estado antes de presionar el botón.

2.  **Endpoint AJAX (`ajax_generate_invoices.php`)** — Archivo nuevo:
    *   POST protegido con sesión + rol `administrador` + token CSRF.
    *   Acción `generate`: ejecuta `generateMonthlyInvoices()` y registra en auditoría.
    *   Acción `status`: retorna el estado actual de generación.

3.  **Dashboard (`dashboard.php`)** — Modificado:
    *   Nueva card "Facturación Mensual" visible solo para administradores.
    *   Muestra estado actual: "X de Y suscripciones ya tienen factura este mes".
    *   Botón "Generar Facturas del Mes" con confirmación JavaScript.
    *   Al hacer clic: llamada AJAX → spinner → resultado con detalle (creadas, omitidas, monto total, errores).
    *   Botón se deshabilita automáticamente si todas las facturas ya fueron generadas.

### Archivos Afectados
*   `/facturacion_model.php` (Nuevo — modelo de facturación masiva)
*   `/ajax_generate_invoices.php` (Nuevo — endpoint AJAX)
*   `/dashboard.php` (Modificado — card de facturación + JS)

---

## 7. Notificaciones WhatsApp a Morosos

### Objetivo
Permitir el envío de mensajes de WhatsApp a clientes morosos mediante la API de CallMeBot, con preview previo, registro de historial y template configurable.

### Migración de Base de Datos
Script: `basedatos/migration_notificaciones.sql` (ejecución manual):
*   `ALTER TABLE clientes`: Agrega columnas `telefono` (VARCHAR 20) y `whatsapp_apikey` (VARCHAR 100).
*   `CREATE TABLE notificaciones`: Registra cada notificación enviada con `cliente_id`, `tipo` (whatsapp/email/sms), `mensaje`, `estado` (enviado/fallido/pendiente), `error_detalle`, `factura_id`.
*   `INSERT INTO configuracion`: Template por defecto con placeholders `{nombre}`, `{facturas}`, `{monto}`.

### Implementación

1.  **Modelo (`notificacion_model.php`)** — Archivo nuevo:
    *   `getDebtorsForNotification()`: Obtiene morosos con teléfono y apikey configurados, excluyendo los que ya fueron notificados en las últimas 24 horas por la misma factura.
    *   `sendWhatsAppMessage($phone, $apikey, $message)`: Envía mensaje vía CallMeBot API (soporta cURL con fallback a `file_get_contents`).
    *   `buildNotificationMessage($client, $template)`: Reemplaza placeholders `{nombre}`, `{facturas}`, `{monto}` en el template.
    *   `logNotification(...)`: Registra cada notificación en la tabla `notificaciones`.
    *   `getNotificationHistory($limit)`: Historial de notificaciones con datos del cliente.
    *   `getWhatsAppTemplate()` / `updateWhatsAppTemplate($template)`: Lectura y escritura del template en `configuracion`.

2.  **Endpoint AJAX (`ajax_send_notifications.php`)** — Archivo nuevo:
    *   POST protegido con sesión + rol `administrador`/`editor` + CSRF.
    *   Acción `preview`: Retorna lista de clientes que recibirían notificación con el mensaje exacto.
    *   Acción `send`: Envía mensajes uno a uno con `sleep(2)` entre cada uno (rate limit de CallMeBot). Registra resultado en tabla `notificaciones` y en auditoría.
    *   Acción `update_template`: Actualiza el template (solo admin).

3.  **Morosos (`morosos_ui.php`)** — Modificado:
    *   Botón "Enviar Notificaciones WhatsApp" en el header de la card de morosos.
    *   Link "Historial" para acceder a `notificaciones_ui.php`.
    *   Modal de preview: muestra tabla con cliente, teléfono, facturas, deuda y mensaje antes de enviar.
    *   Botón "Confirmar y Enviar" con progreso visual y tabla de resultados.
    *   Sección de configuración del template de mensaje (solo admin), con variables disponibles documentadas.

4.  **Historial (`notificaciones_ui.php`)** — Archivo nuevo:
    *   Página de historial de notificaciones enviadas.
    *   Tabla DataTables con: fecha, cliente, teléfono, tipo, estado (badges de colores), mensaje truncado, error.
    *   Accesible desde `morosos_ui.php`.

### Archivos Afectados
*   `/basedatos/migration_notificaciones.sql` (Nuevo — migración SQL)
*   `/notificacion_model.php` (Nuevo — modelo de notificaciones)
*   `/ajax_send_notifications.php` (Nuevo — endpoint AJAX)
*   `/notificaciones_ui.php` (Nuevo — historial de notificaciones)
*   `/morosos_ui.php` (Modificado — botones, modal, config template)

---

## 8. Campos de Teléfono y API Key en Gestión de Clientes

### Objetivo
Permitir registrar el teléfono de WhatsApp y la API Key de CallMeBot de cada cliente, necesarios para el envío de notificaciones.

### Implementación

1.  **Modelo (`client_model.php`)** — Modificado:
    *   `createClient()`: Acepta parámetros `$telefono` y `$whatsapp_apikey`. INSERT actualizado a 8 campos.
    *   `getClientById()`: SELECT actualizado para incluir `telefono` y `whatsapp_apikey`.
    *   `getAllClients()` y `searchClients()`: Queries actualizados para incluir los nuevos campos.
    *   `updateClient()` ya soporta los nuevos campos dinámicamente (acepta cualquier clave en `$data`).

2.  **Formulario de Crear (`clients_ui.php`)** — Modificado:
    *   Nuevos campos "Teléfono (WhatsApp)" con placeholder de formato internacional.
    *   Campo "WhatsApp API Key" con tooltip explicativo sobre cómo obtenerlo de CallMeBot.
    *   POST handler actualizado para pasar los nuevos campos a `createClient()`.

3.  **Modal de Editar (`clients_ui.php`)** — Modificado:
    *   Mismos campos agregados al modal de edición.
    *   JavaScript actualizado para pre-cargar `telefono` y `whatsapp_apikey` al abrir el modal.
    *   POST handler de `update_client` incluye los nuevos campos en `$data_to_update`.

4.  **Tabla de Clientes (`client_rows_partial.php`)** — Modificado:
    *   Atributos `data-telefono` y `data-whatsapp_apikey` agregados al botón de editar para pasar datos al modal.

### Archivos Afectados
*   `/client_model.php` (Modificado — createClient, getClientById, getAllClients, searchClients)
*   `/clients_ui.php` (Modificado — formularios crear/editar, handlers POST, JS modal)
*   `/client_rows_partial.php` (Modificado — data-attributes para edición)
