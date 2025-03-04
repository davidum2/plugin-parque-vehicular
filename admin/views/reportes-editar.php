<?php

/**
 * Vista para la página de edición de reportes de movimientos.
 *
 * Permite a los administradores editar un reporte de movimiento existente,
 * modificando sus detalles, firmantes y movimientos incluidos.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza la página de edición de un reporte de movimientos.
 *
 * Obtiene los datos del reporte a editar, procesa la actualización del reporte
 * si se envió el formulario y muestra el formulario de edición con los campos
 * y opciones correspondientes.
 *
 * @return void
 */
function gpv_reportes_editar_view()
{
    global $GPV_Database;

    // --- Lógica para obtener el ID del reporte a editar ---
    $reporte_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$reporte_id) {
        echo '<div class="notice notice-error"><p>' . esc_html__('ID de reporte inválido.', 'gestion-parque-vehicular') . '</p></div>';
        return; // Salir si no hay ID válido
    }

    // --- Lógica para obtener los datos del reporte desde la base de datos ---
    $reporte = $GPV_Database->obtener_reporte($reporte_id);
    if (!$reporte) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Reporte no encontrado.', 'gestion-parque-vehicular') . '</p></div>';
        return; // Salir si el reporte no existe
    }

    // --- Lógica para procesar la actualización del formulario si se envía ---
    if (isset($_POST['gpv_editar_reporte_submit']) && check_admin_referer('gpv_editar_reporte_action', 'gpv_editar_reporte_nonce')) {
        // ... (Aquí iría la lógica para procesar la edición del reporte) ...
        // ... (Llamar a funciones de $GPV_Database para actualizar el reporte) ...
        // ... (Mostrar mensaje de éxito o error) ...
    }

    // --- HTML del formulario de edición ---
?>
    <div class="wrap gpv-admin-page">
        <h1><?php esc_html_e('Editar Reporte de Movimientos', 'gestion-parque-vehicular'); ?></h1>

        <form method="post" action="" class="gpv-admin-form">
            <?php wp_nonce_field('gpv_editar_reporte_action', 'gpv_editar_reporte_nonce'); ?>

            <div class="gpv-form-container">
                <p><?php esc_html_e('Aquí irían los campos para editar el reporte (Fecha, Número de Mensaje, Firmantes, Movimientos, etc.).', 'gestion-parque-vehicular'); ?></p>
                <p><?php esc_html_e('Recuerda rellenar este formulario con los campos necesarios para editar un reporte existente.', 'gestion-parque-vehicular'); ?></p>

                <div class="gpv-form-actions">
                    <button type="submit" name="gpv_editar_reporte_submit" class="button button-primary">
                        <?php esc_html_e('Guardar Cambios', 'gestion-parque-vehicular'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes')); ?>" class="button">
                        <?php esc_html_e('Cancelar', 'gestion-parque-vehicular'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
<?php
}
