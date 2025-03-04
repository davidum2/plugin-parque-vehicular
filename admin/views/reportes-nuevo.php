<?php

/**
 * Vista para la página de creación de nuevos reportes de movimientos.
 *
 * Permite a los administradores crear un nuevo reporte seleccionando la fecha,
 * firmantes, y movimientos de vehículos a incluir en el reporte.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función principal para renderizar la página de creación de reportes.
 *
 * Esta función se encarga de inicializar variables, procesar el formulario
 * de creación de reportes y renderizar la vista con el formulario y los
 * mensajes correspondientes.
 *
 * @return void
 */
function gpv_reportes_nuevo_view()
{
    global $GPV_Database;

    // Inicialización de variables.
    $fecha_reporte = date('Y-m-d');
    $firmantes = $GPV_Database->obtener_firmantes(['activo' => 1]);
    $mensaje = ''; // Inicializar mensaje para retroalimentación al usuario.
    $reporte_id = false; // Inicializar $reporte_id fuera del bloque condicional.
    $movimientos_elegibles = $GPV_Database->obtener_movimientos_para_reporte($fecha_reporte);

    // Procesamiento del formulario sólo si se envía y el nonce es válido.
    if (isset($_POST['gpv_reporte_submit']) && check_admin_referer('gpv_reporte_action', 'gpv_reporte_nonce')) {
        $mensaje = gpv_procesar_formulario_reporte($GPV_Database);
        if (empty($mensaje)) {
            // Redirección en caso de éxito o error, usando la función centralizada.
            gpv_redireccionar_con_mensaje('created');
            return; // Importante: Salir para evitar procesamiento adicional.
        } else {
            // Si hay un mensaje de error, no redirigir, mostrará el mensaje en la página actual.
        }
    }


    // HTML del formulario y la página.
?>
    <div class="wrap gpv-admin-page">
        <h1><?php esc_html_e('Crear Nuevo Reporte', 'gestion-parque-vehicular'); ?></h1>

        <?php if (!empty($mensaje)) : ?>
            <div class="notice notice-error">
                <p><?php echo wp_kses_post($mensaje); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" id="gpv-reporte-form" class="gpv-admin-form">
            <?php wp_nonce_field('gpv_reporte_action', 'gpv_reporte_nonce'); ?>

            <div class="gpv-form-container">
                <?php gpv_render_seccion_fecha_firmantes($fecha_reporte, $firmantes); ?>
                <?php gpv_render_seccion_movimientos($movimientos_elegibles); ?>
                <?php gpv_render_seccion_botones(); ?>
            </div>
        </form>
    </div>

    <script>
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

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    if (selectAll) {
                        selectAll.checked = allChecked;
                    }
                });
            });
        });
    </script>
<?php
}

/**
 * Procesa el formulario de reporte al ser enviado.
 *
 * @global $GPV_Database
 * @return string Mensaje de error, vacío si no hay error.
 */
function gpv_procesar_formulario_reporte($GPV_Database)
{
    // Verificar que no se hayan enviado ya cabeceras (innecesario aquí ya que se maneja con redirección JS en el original, y ahora con función centralizada).
    // Este control ya no es necesario con la redirección centralizada y manejo de errores mejorado.

    $fecha_reporte = sanitize_text_field($_POST['fecha_reporte']);
    $numero_mensaje = sanitize_text_field($_POST['numero_mensaje']);
    $firmante_id = intval($_POST['firmante_id']);
    $firmante2_id = isset($_POST['firmante2_id']) ? intval($_POST['firmante2_id']) : 0;

    try {
        // Validar movimientos seleccionados.
        if (!isset($_POST['movimientos']) || empty($_POST['movimientos'])) {
            throw new Exception(__('Debe seleccionar al menos un movimiento para generar el reporte.', 'gestion-parque-vehicular'));
        }

        $movimientos_seleccionados = $_POST['movimientos'];
        $movimiento_ids = implode(',', array_map('intval', $movimientos_seleccionados));

        // Procesar horas y conductores personalizados.
        $horas_salida = $_POST['hora_salida'] ?? []; // Usar operador null coalescente para evitar errores si no están definidos.
        $horas_regreso = $_POST['hora_regreso'] ?? [];
        $conductores = $_POST['conductor'] ?? [];

        // Obtener detalles del primer movimiento para la información básica.
        $primer_movimiento_id = intval($movimientos_seleccionados[0]);
        $movimiento = $GPV_Database->get_movement($primer_movimiento_id);

        if (!$movimiento) {
            throw new Exception(__('No se pudo obtener información del movimiento seleccionado.', 'gestion-parque-vehicular'));
        }

        // Preparar datos para el reporte.
        $reporte_data = [
            'vehiculo_id' => $movimiento->vehiculo_id,
            'vehiculo_siglas' => $movimiento->vehiculo_siglas,
            'vehiculo_nombre' => $movimiento->vehiculo_nombre,
            'odometro_inicial' => $movimiento->odometro_salida,
            'odometro_final' => $movimiento->odometro_entrada,
            'fecha_inicial' => date('Y-m-d', strtotime($movimiento->hora_salida)),
            'hora_inicial' => $horas_salida[0] ?? date('H:i:s', strtotime($movimiento->hora_salida)), // Operador null coalescente para horas.
            'fecha_final' => date('Y-m-d', strtotime($movimiento->hora_entrada)),
            'hora_final' => $horas_regreso[0] ?? date('H:i:s', strtotime($movimiento->hora_entrada)),
            'distancia_total' => $movimiento->distancia_recorrida,
            'conductor_id' => $movimiento->conductor_id,
            'conductor' => sanitize_text_field($conductores[0] ?? $movimiento->conductor), // Operador null coalescente y sanitización.
            'movimientos_incluidos' => $movimiento_ids,
            'numero_mensaje' => $numero_mensaje,
            'firmante_id' => $firmante_id,
            'firmante2_id' => $firmante2_id,
            'fecha_reporte' => $fecha_reporte,
            'estado' => 'pendiente'
        ];

        // Insertar reporte.
        $reporte_id = $GPV_Database->insert_reporte_movimiento($reporte_data);

        if (!$reporte_id) {
            throw new Exception(__('Error al crear el reporte. Por favor, intente de nuevo.', 'gestion-parque-vehicular'));
        }


        return ''; // Retorna vacío en caso de éxito.


    } catch (Exception $e) {
        return $e->getMessage(); // Retorna el mensaje de error para mostrar en la página.
    }
}

/**
 * Redirecciona a la página de reportes con un mensaje (éxito o error).
 *
 * @param string $message_type Tipo de mensaje ('created' para éxito, 'error' para error).
 */
function gpv_redireccionar_con_mensaje($message_type)
{
    $redirect_url = admin_url('admin.php?page=gpv_reportes&message=' . $message_type);
    echo '<script type="text/javascript">window.location.href = "' . esc_url_raw($redirect_url) . '";</script>';
    exit;
}


/**
 * Renderiza la sección de fecha y selección de firmantes del formulario.
 *
 * @param string $fecha_reporte Fecha actual del reporte.
 * @param array $firmantes Lista de firmantes activos.
 */
function gpv_render_seccion_fecha_firmantes($fecha_reporte, $firmantes)
{
?>
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
<?php
}


/**
 * Renderiza la sección de movimientos elegibles en forma de tabla.
 *
 * @param array $movimientos_elegibles Listado de movimientos elegibles para reportar.
 */
function gpv_render_seccion_movimientos($movimientos_elegibles)
{
?>
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
                            <th scope="col"><?php esc_html_e('Propósito', 'gestion-parque-vehicular'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_elegibles as $index => $mov):
                            $fecha_salida = date_i18n(get_option('date_format'), strtotime($mov->fecha_inicial));
                            $hora_salida = date_i18n('H:i', strtotime($mov->hora_inicial));
                            $fecha_regreso = date_i18n(get_option('date_format'), strtotime($mov->fecha_final));
                            $hora_regreso = date_i18n('H:i', strtotime($mov->hora_final));
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="movimientos[]" value="<?php echo esc_attr($mov->id); ?>" checked>
                                    <input type="hidden" name="movimiento_ids[]" value="<?php echo esc_attr($mov->id); ?>">
                                </td>
                                <td><?php echo esc_html($mov->vehiculo_siglas . ' - ' . $mov->vehiculo_nombre); ?></td>
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
                                <td>
                                    <select name="proposito[<?php echo $index; ?>]" class="gpv-editable-field">
                                        <option value=""><?php esc_html_e('-- Seleccionar --', 'gestion-parque-vehicular'); ?></option>
                                        <option value="Trasladar al Cmte."><?php esc_html_e('Trasladar al Cmte.', 'gestion-parque-vehicular'); ?></option>
                                        <option value="Trasladar Personal"><?php esc_html_e('Trasladar Personal', 'gestion-parque-vehicular'); ?></option>
                                        <option value="Abastecer de Agua"><?php esc_html_e('Abastecer de Agua', 'gestion-parque-vehicular'); ?></option>
                                        <option value="Comision del Servicio"><?php esc_html_e('Comision del Servicio', 'gestion-parque-vehicular'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php
}

/**
 * Renderiza la sección de botones de acción del formulario.
 */
function gpv_render_seccion_botones()
{
?>
    <div class="gpv-form-actions">
        <button type="submit" name="gpv_reporte_submit" class="button button-primary">
            <?php esc_html_e('Crear Reporte', 'gestion-parque-vehicular'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes')); ?>" class="button">
            <?php esc_html_e('Cancelar', 'gestion-parque-vehicular'); ?>
        </a>
    </div>
<?php
}
