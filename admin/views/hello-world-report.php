<?php

/**
 * Vista para el botón de generación del reporte Hello World
 *
 * @package GPV
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mostrar botón para generar reporte Hello World
 */
function gpv_hello_world_view()
{
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.', 'gestion-parque-vehicular'));
    }
?>
    <div class="wrap">
        <h1><?php _e('Reporte Hello World', 'gestion-parque-vehicular'); ?></h1>

        <div class="gpv-admin-page">
            <p><?php _e('Este es un ejemplo simple de cómo generar un PDF con la biblioteca TC-PDF.', 'gestion-parque-vehicular'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="gpv_generate_hello_world">
                <?php wp_nonce_field('gpv_hello_world', 'gpv_hello_world_nonce'); ?>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Generar PDF Hello World', 'gestion-parque-vehicular'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
<?php
}
