<?php
// client_rows_partial.php
// Expects $clients array and $_SESSION variables to be available.

if (empty($clients)): ?>
    <tr>
        <td colspan="8" class="text-center">No hay clientes registrados o encontrados.</td>
    </tr>
<?php else:
    foreach ($clients as $client): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($client['id']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['dni']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['nombre']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['apellido']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['direccion'] ?? 'N/A'); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['correo_electronico'] ?? 'N/A'); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($client['fecha_registro']); ?>
            </td>
            <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'editor')): ?>
                <td>
                    <button type="button" class="btn btn-info btn-sm rounded-pill" data-bs-toggle="modal"
                        data-bs-target="#editClientModal" data-id="<?php echo $client['id']; ?>"
                        data-dni="<?php echo htmlspecialchars($client['dni'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-nombre="<?php echo htmlspecialchars($client['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-apellido="<?php echo htmlspecialchars($client['apellido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-direccion="<?php echo htmlspecialchars($client['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-correo_electronico="<?php echo htmlspecialchars($client['correo_electronico'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-notas_cliente="<?php echo htmlspecialchars($client['notas_cliente'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-plan_id="<?php echo htmlspecialchars($client['plan_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-telefono="<?php echo htmlspecialchars($client['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-whatsapp_apikey="<?php echo htmlspecialchars($client['whatsapp_apikey'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <form action="clients_ui.php" method="POST" style="display:inline-block;"
                            onsubmit="return confirm('¿Estás seguro de que quieres eliminar este cliente? Esta acción es irreversible.');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                            <input type="hidden" name="action" value="delete_client">
                            <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm rounded-pill">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach;
endif; ?>