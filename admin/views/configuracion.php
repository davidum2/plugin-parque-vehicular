<?php

/**
 * Vista para la página de configuración del plugin GPV.
 *
 * Muestra el formulario de configuración con las diferentes opciones del plugin,
 * permitiendo al administrador modificar los ajustes y guardarlos en la base de datos.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza la página de configuración del plugin.
 *
 * Recupera la configuración actual desde la base de datos, procesa el guardado de cambios
 * si se envió el formulario, y muestra el formulario de configuración con las opciones disponibles.
 *
 * @return void
 */
function gpv_configuracion_view()
{
    global $GPV_Database;

    // Guardar cambios si se envió el formulario
    if (isset($_POST['gpv_save_settings']) && isset($_POST['gpv_nonce']) && wp_verify_nonce($_POST['gpv_nonce'], 'gpv_save_settings')) {
        // Procesar cada configuración individualmente
        if (isset($_POST['calcular_consumo_automatico'])) {
            $GPV_Database->actualizar_configuracion('calcular_consumo_automatico', 1);
        } else {
            $GPV_Database->actualizar_configuracion('calcular_consumo_automatico', 0);
        }

        if (isset($_POST['umbral_nivel_combustible_bajo'])) {
            $GPV_Database->actualizar_configuracion('umbral_nivel_combustible_bajo', sanitize_text_field($_POST['umbral_nivel_combustible_bajo']));
        }

        if (isset($_POST['mostrar_dashboard_publico'])) {
            $GPV_Database->actualizar_configuracion('mostrar_dashboard_publico', 1);
        } else {
            $GPV_Database->actualizar_configuracion('mostrar_dashboard_publico', 0);
        }

        if (isset($_POST['sincronizacion_offline_habilitada'])) {
            $GPV_Database->actualizar_configuracion('sincronizacion_offline_habilitada', 1);
        } else {
            $GPV_Database->actualizar_configuracion('sincronizacion_offline_habilitada', 0);
        }

        if (isset($_POST['logo_url'])) {
            $GPV_Database->actualizar_configuracion('logo_url', esc_url_raw($_POST['logo_url']));
        }

        // Mostrar mensaje de éxito
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', 'gestion-parque-vehicular') . '</p></div>';
    }

    // Obtener configuración actual para rellenar el formulario
    $calcular_consumo_automatico = $GPV_Database->obtener_configuracion('calcular_consumo_automatico');
    $umbral_nivel_combustible_bajo = $GPV_Database->obtener_configuracion('umbral_nivel_combustible_bajo');
    $mostrar_dashboard_publico = $GPV_Database->obtener_configuracion('mostrar_dashboard_publico');
    $sincronizacion_offline_habilitada = $GPV_Database->obtener_configuracion('sincronizacion_offline_habilitada');
    $logo_url = $GPV_Database->obtener_configuracion('logo_url');

?>
    <div class="wrap gpv-admin-page">
        <h2><?php esc_html_e('Configuración del Plugin', 'gestion-parque-vehicular'); ?></h2>

        <form method="post" action="" class="gpv-admin-form">
            <?php wp_nonce_field('gpv_save_settings', 'gpv_nonce'); ?>

            <table class="form-table">

                <tr>
                    <th scope="row"><?php esc_html_e('Cálculo automático de consumo', 'gestion-parque-vehicular'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Cálculo automático de consumo', 'gestion-parque-vehicular'); ?></span></legend>
                            <label for="calcular_consumo_automatico">
                                <input type="checkbox" name="calcular_consumo_automatico" id="calcular_consumo_automatico" value="1" <?php checked($calcular_consumo_automatico, 1, false); ?>>
                                <?php esc_html_e('Calcular automáticamente el consumo de combustible en cada movimiento', 'gestion-parque-vehicular'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="umbral_nivel_combustible_bajo"><?php esc_html_e('Umbral de nivel bajo de combustible (%)', 'gestion-parque-vehicular'); ?></label></th>
                    <td>
                        <input type="number" name="umbral_nivel_combustible_bajo" id="umbral_nivel_combustible_bajo" value="<?php echo esc_attr($umbral_nivel_combustible_bajo); ?>" min="1" max="50" step="1" class="small-text">
                        <p class="description"><?php esc_html_e('Porcentaje para considerar nivel bajo de combustible y mostrar alertas.', 'gestion-parque-vehicular'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Dashboard público', 'gestion-parque-vehicular'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Dashboard público', 'gestion-parque-vehicular'); ?></span></legend>
                            <label for="mostrar_dashboard_publico">
                                <input type="checkbox" name="mostrar_dashboard_publico" id="mostrar_dashboard_publico" value="1" <?php checked($mostrar_dashboard_publico, 1, false); ?>>
                                <?php esc_html_e('Mostrar dashboard público a usuarios no logueados', 'gestion-parque-vehicular'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Funcionalidad offline', 'gestion-parque-vehicular'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Funcionalidad offline', 'gestion-parque-vehicular'); ?></span></legend>
                            <label for="sincronizacion_offline_habilitada">
                                <input type="checkbox" name="sincronizacion_offline_habilitada" id="sincronizacion_offline_habilitada" value="1" <?php checked($sincronizacion_offline_habilitada, 1, false); ?>>
                                <?php esc_html_e('Habilitar sincronización offline (PWA)', 'gestion-parque-vehicular'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="logo_url"><?php esc_html_e('URL del Logo', 'gestion-parque-vehicular'); ?></label></th>
                    <td>
                        <input type="url" name="logo_url" id="logo_url" value="<?php echo esc_url($logo_url); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('URL de la imagen del logo para la aplicación (proporción 3:1 recomendada).', 'gestion-parque-vehicular'); ?></p>
                    </td>
                </tr>

            </table>

            <p class="submit">
                <input type="submit" name="gpv_save_settings" class="button button-primary" value="<?php esc_attr_e('Guardar Cambios', 'gestion-parque-vehicular'); ?>">
            </p>
        </form>

    </div>
    <div class="gpv-admin-section">
        <h3><?php esc_html_e('Herramientas de Base de Datos', 'gestion-parque-vehicular'); ?></h3>
        <p><?php esc_html_e('Utiliza estas herramientas para actualizar la estructura de la base de datos si encuentras errores.', 'gestion-parque-vehicular'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('gpv_update_db', 'gpv_update_db_nonce'); ?>
            <p><input type="submit" name="gpv_update_db" class="button button-secondary" value="<?php esc_attr_e('Actualizar Estructura de Base de Datos', 'gestion-parque-vehicular'); ?>"></p>
        </form>

        <?php
        // Procesar actualización de BD si se solicitó
        if (isset($_POST['gpv_update_db']) && isset($_POST['gpv_update_db_nonce']) && wp_verify_nonce($_POST['gpv_update_db_nonce'], 'gpv_update_db')) {
            global $GPV_Database;
            $result = $GPV_Database->update_database_structure();

            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Base de datos actualizada correctamente.', 'gestion-parque-vehicular') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error al actualizar la base de datos.', 'gestion-parque-vehicular') . '</p></div>';
            }
        }
        ?>
    </div>
<?php
}
