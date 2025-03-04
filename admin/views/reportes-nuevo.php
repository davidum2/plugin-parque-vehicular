<?php
// Salir si se accede directamente
if (!defined('ABSPATH')) {
    exit;
}

global $GPV_Database;

// Obtener la fecha actual por defecto
$fecha_reporte = date('Y-m-d');

// Obtener firmantes para el selector
$firmantes = $GPV_Database->get_firmantes(['activo' => 1]);

// Verificar si se envió el formulario
$mensaje = '';
if (isset($_POST['gpv_reporte_submit']) && isset($_POST['gpv_reporte_nonce']) && wp_verify_nonce($_POST['gpv_reporte_nonce'], 'gpv_reporte_action')) {
    // Procesar el formulario
    $fecha_reporte = sanitize_text_field($_POST['fecha_reporte']);
    $numero_mensaje = sanitize_text_field($_POST['numero_mensaje']);
    $firmante_id = intval($_POST['firmante_id']);

    // Verificar movimientos seleccionados
    if (!isset($_POST['movimientos']) || empty($_POST['movimientos'])) {
        $mensaje = '<div class="notice notice-error"><p>' . __('Debe seleccionar al menos un movimiento para generar el reporte.', 'gestion-parque-vehicular') . '</p></div>';
    } else {
        // Procesar cada movimiento seleccionado
        $movimientos_seleccionados = $_POST['movimientos'];
        $movimiento_ids = implode(',', array_map('intval', $movimientos_seleccionados));

        // Obtener detalles del primer movimiento para la información básica
        $primer_movimiento_id = intval($movimientos_seleccionados[0]);
        $movimiento = $GPV_Database->get_movement($primer_movimiento_id);

        if ($movimiento) {
            // Preparar datos para el reporte
            $reporte_data = [
                'vehiculo_id' => $movimiento->vehiculo_id,
                'vehiculo_siglas' => $movimiento->vehiculo_siglas,
                'vehiculo_nombre' => $movimiento->vehiculo_nombre,
                'odometro_inicial' => $movimiento->odometro_salida,
                'odometro_final' => $movimiento->odometro_entrada,
                'fecha_inicial' => date('Y-m-d', strtotime($movimiento->hora_salida)),
                'hora_inicial' => date('H:i:s', strtotime($movimiento->hora_salida)),
                'fecha_final' => date('Y-m-d', strtotime($movimiento->hora_entrada)),
                'hora_final' => date('H:i:s', strtotime($movimiento->hora_entrada)),
                'distancia_total' => $movimiento->distancia_recorrida,
                'conductor_id' => $movimiento->conductor_id,
                'conductor' => $movimiento->conductor,
                'movimientos_incluidos' => $movimiento_ids,
                'numero_mensaje' => $numero_mensaje,
                'firmante_id' => $firmante_id,
                'fecha_reporte' => $fecha_reporte,
                'estado' => 'pendiente'
            ];

            // Insertar reporte
            $reporte_id = $GPV_Database->insert_reporte_movimiento($reporte_data);

            if ($reporte_id) {
                // Redireccionar al listado de reportes con mensaje de éxito
                wp_redirect(admin_url('admin.php?page=gpv_reportes&message=created'));
                exit;
            } else {
                $mensaje = '<div class="notice notice-error"><p>' . __('Error al crear el reporte. Por favor, intente de nuevo.', 'gestion-parque-vehicular') . '</p></div>';
            }
        } else {
            $mensaje = '<div class="notice notice-error"><p>' . __('No se pudo obtener información del movimiento seleccionado.', 'gestion-parque-vehicular') . '</p></div>';
        }
    }
}

// Obtener todos los movimientos elegibles para reporte (no reportados y completos)
$movimientos_elegibles = $GPV_Database->get_movimientos_para_reporte($fecha_reporte);
?>

<!-- El resto del formulario HTML se mantiene igual -->

<?php
// Salir si se accede directamente
if (!defined('ABSPATH')) {
    exit;
}

global $GPV_Database;

// Obtener la fecha actual por defecto
$fecha_reporte = date('Y-m-d');

// Obtener firmantes para el selector
$firmantes = $GPV_Database->get_firmantes(['activo' => 1]);

// Verificar si se envió el formulario
$mensaje = '';
if (isset($_POST['gpv_reporte_submit']) && isset($_POST['gpv_reporte_nonce']) && wp_verify_nonce($_POST['gpv_reporte_nonce'], 'gpv_reporte_action')) {
    // Procesar el formulario...
    $fecha_reporte = sanitize_text_field($_POST['fecha_reporte']);
    // Más procesamiento aquí...
}

// Obtener todos los movimientos elegibles para reporte (no reportados y >30 km o acumulados)
$movimientos_elegibles = $GPV_Database->get_movimientos_para_reporte($fecha_reporte);
?>

<div class="wrap">
    <h1><?php esc_html_e('Crear Nuevo Reporte', 'gestion-parque-vehicular'); ?></h1>

    <?php echo $mensaje; ?>

    <form method="post" id="gpv-reporte-form">
        <?php wp_nonce_field('gpv_reporte_action', 'gpv_reporte_nonce'); ?>

        <div class="gpv-form-container">
            <!-- Selector de fecha y número de mensaje -->
            <div class="gpv-form-section">
                <div class="gpv-form-row">
                    <div class="gpv-form-field">
                        <label for="fecha_reporte"><?php esc_html_e('Fecha del Reporte:', 'gestion-parque-vehicular'); ?></label>
                        <input type="date" id="fecha_reporte" name="fecha_reporte" value="<?php echo esc_attr($fecha_reporte); ?>" required>
                    </div>

                    <div class="gpv-form-field">
                        <label for="numero_mensaje"><?php esc_html_e('Número de Mensaje:', 'gestion-parque-vehicular'); ?></label>
                        <input type="text" id="numero_mensaje" name="numero_mensaje" value="Tptes. 03" required>
                    </div>

                    <div class="gpv-form-field">
                        <label for="firmante_id"><?php esc_html_e('Firmante:', 'gestion-parque-vehicular'); ?></label>
                        <select id="firmante_id" name="firmante_id" required>
                            <option value=""><?php esc_html_e('-- Seleccionar Firmante --', 'gestion-parque-vehicular'); ?></option>
                            <?php foreach ($firmantes as $firmante): ?>
                                <option value="<?php echo esc_attr($firmante->id); ?>">
                                    <?php echo esc_html($firmante->nombre . ' - ' . $firmante->cargo); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tabla de movimientos a reportar -->
            <div class="gpv-form-section">
                <h2><?php esc_html_e('Movimientos a Reportar', 'gestion-parque-vehicular'); ?></h2>

                <?php if (empty($movimientos_elegibles)): ?>
                    <p class="gpv-notice"><?php esc_html_e('No hay movimientos elegibles para reportar en esta fecha.', 'gestion-parque-vehicular'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped" id="gpv-movimientos-table">
                        <thead>
                            <tr>
                                <th scope="col" width="30"><input type="checkbox" id="select-all"></th>
                                <th scope="col"><?php esc_html_e('Vehículo', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Odóm. Inicial', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Odóm. Final', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Distancia', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Fecha', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Hora', 'gestion-parque-vehicular'); ?></th>
                                <th scope="col"><?php esc_html_e('Conductor', 'gestion-parque-vehicular'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos_elegibles as $index => $mov):
                                // Formatear fecha y hora
                                $fecha = date_i18n(get_option('date_format'), strtotime($mov->fecha_inicial));
                                $hora = date_i18n('H:i', strtotime($mov->hora_inicial));
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="movimientos[]" value="<?php echo esc_attr($mov->id); ?>" checked>
                                        <input type="hidden" name="movimiento_ids[]" value="<?php echo esc_attr($mov->id); ?>">
                                    </td>
                                    <td>
                                        <?php echo esc_html($mov->vehiculo_siglas . ' - ' . $mov->vehiculo_nombre); ?>
                                    </td>
                                    <td><?php echo esc_html($mov->odometro_inicial); ?></td>
                                    <td><?php echo esc_html($mov->odometro_final); ?></td>
                                    <td><?php echo esc_html(number_format($mov->distancia_total, 2)); ?> km</td>
                                    <td><?php echo esc_html($fecha); ?></td>
                                    <td>
                                        <input type="time" name="hora[<?php echo $index; ?>]" value="<?php echo esc_attr($hora); ?>" class="gpv-editable-field">
                                    </td>
                                    <td>
                                        <input type="text" name="conductor[<?php echo $index; ?>]" value="<?php echo esc_attr($mov->conductor); ?>" class="gpv-editable-field">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Botones de acción -->
            <div class="gpv-form-actions">
                <button type="submit" name="gpv_reporte_submit" class="button button-primary">
                    <?php esc_html_e('Crear Reporte', 'gestion-parque-vehicular'); ?>
                </button>

                <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes')); ?>" class="button">
                    <?php esc_html_e('Cancelar', 'gestion-parque-vehicular'); ?>
                </a>
            </div>
        </div>
    </form>
</div>

<script>
    // JavaScript para manejar la selección/deselección de todos los movimientos
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('input[name="movimientos[]"]');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                checkboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
        }

        // También actualizar "select all" si se deselecciona manualmente alguno
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = [...checkboxes].every(c => c.checked);
                if (selectAll) {
                    selectAll.checked = allChecked;
                }
            });
        });
    });
</script>
