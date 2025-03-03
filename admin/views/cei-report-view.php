<?php

/**
 * Vista para el formulario de generación de reportes C.E.I.
 *
 * @package GPV
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mostrar formulario para generar reportes C.E.I.
 */
function gpv_cei_report_view()
{
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.', 'gestion-parque-vehicular'));
    }

    global $GPV_Database;

    // Obtener últimos movimientos
    $movements = $GPV_Database->get_movements(array(
        'limit' => 20,
        'orderby' => 'id',
        'order' => 'DESC'
    ));
?>
    <div class="wrap">
        <h1><?php _e('Reporte C.E.I.', 'gestion-parque-vehicular'); ?></h1>

        <div class="gpv-admin-page">
            <p><?php _e('Seleccione un movimiento para generar un reporte C.E.I.', 'gestion-parque-vehicular'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gpv-admin-form">
                <input type="hidden" name="action" value="gpv_generate_cei_report">
                <?php wp_nonce_field('gpv_cei_report', 'gpv_cei_report_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="movement_id"><?php _e('Movimiento:', 'gestion-parque-vehicular'); ?></label></th>
                        <td>
                            <select name="movement_id" id="movement_id" class="regular-text" required>
                                <option value=""><?php _e('-- Seleccionar movimiento --', 'gestion-parque-vehicular'); ?></option>
                                <?php foreach ($movements as $movement) :
                                    // Formatear fechas para mostrar
                                    $fecha_salida = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movement->hora_salida));
                                    $fecha_entrada = empty($movement->hora_entrada) ? 'En progreso' :
                                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movement->hora_entrada));
                                ?>
                                    <option value="<?php echo esc_attr($movement->id); ?>">
                                        #<?php echo esc_html($movement->id); ?> -
                                        <?php echo esc_html($movement->vehiculo_siglas); ?> -
                                        <?php echo esc_html($movement->conductor); ?> -
                                        <?php echo esc_html($fecha_salida); ?> a <?php echo esc_html($fecha_entrada); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Seleccione el movimiento para generar el reporte.', 'gestion-parque-vehicular'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mensaje_numero"><?php _e('Número de mensaje:', 'gestion-parque-vehicular'); ?></label></th>
                        <td>
                            <input type="text" name="mensaje_numero" id="mensaje_numero" value="Tptes. 03" class="regular-text" required>
                            <p class="description"><?php _e('Número de referencia del mensaje.', 'gestion-parque-vehicular'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php _e('Generar Reporte C.E.I.', 'gestion-parque-vehicular'); ?>
                    </button>
                </p>
            </form>

            <div class="gpv-info-box">
                <h3><span class="dashicons dashicons-info"></span> <?php _e('Información', 'gestion-parque-vehicular'); ?></h3>
                <p><?php _e('El reporte C.E.I. incluirá:', 'gestion-parque-vehicular'); ?></p>
                <ul>
                    <li><?php _e('Encabezado con número de mensaje, fecha y referencia.', 'gestion-parque-vehicular'); ?></li>
                    <li><?php _e('Destinatarios predefinidos.', 'gestion-parque-vehicular'); ?></li>
                    <li><?php _e('Tabla con información del vehículo y movimiento.', 'gestion-parque-vehicular'); ?></li>
                    <li><?php _e('Sección de firmas y referencias.', 'gestion-parque-vehicular'); ?></li>
                </ul>
                <p><strong><?php _e('Nota:', 'gestion-parque-vehicular'); ?></strong> <?php _e('Se usarán los datos reales del movimiento seleccionado.', 'gestion-parque-vehicular'); ?></p>
            </div>
        </div>
    </div>
<?php
}
