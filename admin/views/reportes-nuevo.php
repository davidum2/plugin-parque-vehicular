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
// Modificación a admin/views/reportes-nuevo.php - sección de procesamiento del formulario
if (isset($_POST['gpv_reporte_submit']) && isset($_POST['gpv_reporte_nonce']) && wp_verify_nonce($_POST['gpv_reporte_nonce'], 'gpv_reporte_action')) {
    // Procesar el formulario
    $fecha_reporte = sanitize_text_field($_POST['fecha_reporte']);
    $numero_mensaje = sanitize_text_field($_POST['numero_mensaje']);
    $firmante_id = intval($_POST['firmante_id']);
    $firmante2_id = isset($_POST['firmante2_id']) ? intval($_POST['firmante2_id']) : 0;

    // Verificar movimientos seleccionados
    if (!isset($_POST['movimientos']) || empty($_POST['movimientos'])) {
        $mensaje = '<div class="notice notice-error"><p>' . __('Debe seleccionar al menos un movimiento para generar el reporte.', 'gestion-parque-vehicular') . '</p></div>';
    } else {
        // Procesar movimientos seleccionados
        $movimientos_seleccionados = $_POST['movimientos'];
        $movimiento_ids = implode(',', array_map('intval', $movimientos_seleccionados));

        // Procesar horas y conductores personalizados
        $horas_salida = isset($_POST['hora_salida']) ? $_POST['hora_salida'] : [];
        $horas_regreso = isset($_POST['hora_regreso']) ? $_POST['hora_regreso'] : [];
        $conductores = isset($_POST['conductor']) ? $_POST['conductor'] : [];

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
                'hora_inicial' => isset($horas_salida[0]) ? $horas_salida[0] : date('H:i:s', strtotime($movimiento->hora_salida)),
                'fecha_final' => date('Y-m-d', strtotime($movimiento->hora_entrada)),
                'hora_final' => isset($horas_regreso[0]) ? $horas_regreso[0] : date('H:i:s', strtotime($movimiento->hora_entrada)),
                'distancia_total' => $movimiento->distancia_recorrida,
                'conductor_id' => $movimiento->conductor_id,
                'conductor' => isset($conductores[0]) ? sanitize_text_field($conductores[0]) : $movimiento->conductor,
                'movimientos_incluidos' => $movimiento_ids,
                'numero_mensaje' => $numero_mensaje,
                'firmante_id' => $firmante_id,
                'firmante2_id' => $firmante2_id, // Nuevo campo
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

    <form method="post" id="gpv-reporte-form" class="gpv-admin-form">
        <?php wp_nonce_field('gpv_reporte_action', 'gpv_reporte_nonce'); ?>

        <div class="gpv-form-container">
            <!-- Selector de fecha y número de mensaje -->
            <div class="gpv-form-section">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="fecha_reporte"><?php esc_html_e('Fecha del Reporte:', 'gestion-parque-vehicular'); ?></label>
                        <input type="date" id="fecha_reporte" name="fecha_reporte" value="<?php echo esc_attr($fecha_reporte); ?>" required class="regular-text">
                    </div>

                    <div class="form-group-half">
                        <label for="numero_mensaje"><?php esc_html_e('Número de Mensaje:', 'gestion-parque-vehicular'); ?></label>
                        <input type="text" id="numero_mensaje" name="numero_mensaje" value="Tptes. 03" required class="regular-text">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-half">
                        <label for="firmante_id"><?php esc_html_e('Firmante Principal:', 'gestion-parque-vehicular'); ?></label>
                        <select id="firmante_id" name="firmante_id" required class="regular-text">
                            <option value=""><?php esc_html_e('-- Seleccionar Firmante --', 'gestion-parque-vehicular'); ?></option>
                            <?php foreach ($firmantes as $firmante): ?>
                                <option value="<?php echo esc_attr($firmante->id); ?>">
                                    <?php echo esc_html($firmante->nombre . ' - ' . $firmante->cargo); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-half">
                        <label for="firmante2_id"><?php esc_html_e('Firmante Secundario:', 'gestion-parque-vehicular'); ?></label>
                        <select id="firmante2_id" name="firmante2_id" class="regular-text">
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
                    <div class="gpv-notice"><?php esc_html_e('No hay movimientos elegibles para reportar en esta fecha.', 'gestion-parque-vehicular'); ?></div>
                <?php else: ?>
                    <div class="gpv-table-responsive">
                        <table class="wp-list-table widefat fixed striped" id="gpv-movimientos-table">
                            <thead>
                                <tr>
                                    <th scope="col" width="30"><input type="checkbox" id="select-all"></th>
                                    <th scope="col"><?php esc_html_e('Vehículo', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Odóm. Inicial', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Odóm. Final', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Distancia', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Fecha Salida', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Hora Salida', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Fecha Regreso', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Hora Regreso', 'gestion-parque-vehicular'); ?></th>
                                    <th scope="col"><?php esc_html_e('Conductor', 'gestion-parque-vehicular'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos_elegibles as $index => $mov):
                                    // Formatear fecha y hora de salida
                                    $fecha_salida = date_i18n(get_option('date_format'), strtotime($mov->fecha_inicial));
                                    $hora_salida = date_i18n('H:i', strtotime($mov->hora_inicial));

                                    // Formatear fecha y hora de regreso
                                    $fecha_regreso = date_i18n(get_option('date_format'), strtotime($mov->fecha_final));
                                    $hora_regreso = date_i18n('H:i', strtotime($mov->hora_final));
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
                                        <td><?php echo esc_html($fecha_salida); ?></td>
                                        <td>
                                            <input type="time" name="hora_salida[<?php echo $index; ?>]" value="<?php echo esc_attr($hora_salida); ?>" class="gpv-time-field">
                                        </td>
                                        <td><?php echo esc_html($fecha_regreso); ?></td>
                                        <td>
                                            <input type="time" name="hora_regreso[<?php echo $index; ?>]" value="<?php echo esc_attr($hora_regreso); ?>" class="gpv-time-field">
                                        </td>
                                        <td>
                                            <input type="text" name="conductor[<?php echo $index; ?>]" value="<?php echo esc_attr($mov->conductor); ?>" class="gpv-editable-field">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

<style>
    .gpv-form-container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-top: 20px;
    }

    .gpv-form-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .gpv-form-section h2 {
        margin-top: 0;
        color: #333;
        font-size: 1.3em;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -10px;
        margin-left: -10px;
    }

    .form-group-half {
        flex: 0 0 50%;
        max-width: 50%;
        padding: 0 10px;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .form-group-half {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    .gpv-form-section label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .gpv-table-responsive {
        overflow-x: auto;
        margin-bottom: 20px;
    }

    #gpv-movimientos-table {
        width: 100%;
        border-collapse: collapse;
    }

    #gpv-movimientos-table th,
    #gpv-movimientos-table td {
        padding: 10px;
        vertical-align: middle;
    }

    .gpv-time-field,
    .gpv-editable-field {
        width: 100%;
        padding: 5px;
    }

    .gpv-notice {
        padding: 15px;
        background-color: #fff8e5;
        border-left: 4px solid #ffba00;
        margin-bottom: 20px;
    }

    .gpv-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
</style>

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
