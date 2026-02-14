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
