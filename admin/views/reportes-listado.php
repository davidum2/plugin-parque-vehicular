<?php

/**
 * Vista para listar los reportes de movimientos en el panel de administración de WordPress.
 *
 * Este archivo muestra una tabla con todos los reportes de movimientos creados,
 * permitiendo al usuario visualizarlos, editarlos, generarlos en PDF y eliminarlos.
 * También incluye enlaces para crear nuevos reportes y gestionar firmantes autorizados.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit; // Salir si accessed directly
}

/**
 * Renderiza la página de listado de reportes de movimientos.
 *
 * Muestra la tabla de reportes, mensajes de éxito/info, y los enlaces para acciones relacionadas con reportes.
 *
 * @return void
 */
function gpv_reportes_listado_view()
{
    // Obtener la instancia global de la clase de base de datos GPV_Database.
    global $GPV_Database;

    // Recuperar todos los reportes de movimientos desde la base de datos.
    $movement_reports = $GPV_Database->obtener_reportes_movimientos();
?>

    <div class="wrap">

        <h1 class="wp-heading-inline"><?php esc_html_e('Reportes de Movimientos', 'gestion-parque-vehicular'); ?></h1>

        <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=new')); ?>" class="page-title-action">
            <?php esc_html_e('Crear Nuevo Reporte', 'gestion-parque-vehicular'); ?>
        </a>

        <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=firmantes')); ?>" class="page-title-action">
            <?php esc_html_e('Gestionar Firmantes', 'gestion-parque-vehicular'); ?>
        </a>

        <hr class="wp-header-end">

        <?php
        // Mostrar mensaje de éxito si existe en la sesión.
        if (isset($_SESSION['gpv_report_message'])) {
            $message_type = $_SESSION['gpv_report_message'];
            if ($message_type === 'created') {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html__('Reporte creado exitosamente.', 'gestion-parque-vehicular') . '</p>';
                echo '</div>';
            }
            // Limpiar el mensaje de sesión para no mostrarlo nuevamente.
            unset($_SESSION['gpv_report_message']);
        }
        ?>

        <?php
        // Mensaje informativo si no hay reportes creados.
        if (empty($movement_reports)) : ?>
            <div class="notice notice-info">
                <p><?php esc_html_e('No hay reportes disponibles. Crea uno nuevo para comenzar.', 'gestion-parque-vehicular'); ?></p>
            </div>

        <?php
        // Si existen reportes, mostrar la tabla.
        else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('ID', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Fecha Reporte', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Número Mensaje', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Vehículos', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Firmante', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Estado', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col" class="column-primary"><?php esc_html_e('Acciones', 'gestion-parque-vehicular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop a través de cada reporte de movimiento.
                    foreach ($movement_reports as $reporte) :
                        // Recuperar el nombre del firmante si existe un ID de firmante.
                        $firmante_nombre = '';
                        if ($reporte->firmante_id) {
                            $firmantes = $GPV_Database->obtener_firmantes(['id' => $reporte->firmante_id]);
                            if (!empty($firmantes)) {
                                $firmante_nombre = $firmantes[0]->nombre;
                            }
                        }

                        // Formatear la fecha del reporte al formato de WordPress.
                        $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reporte->fecha_reporte));
                    ?>
                        <tr>
                            <td><?php echo esc_html($reporte->id); ?></td>
                            <td><?php echo esc_html($fecha_formateada); ?></td>
                            <td><?php echo esc_html($reporte->numero_mensaje); ?></td>
                            <td><?php
                                // Contar y mostrar la cantidad de vehículos incluidos en el reporte.
                                $movimiento_ids = explode(',', $reporte->movimientos_incluidos);
                                echo esc_html(count($movimiento_ids));
                                ?></td>
                            <td><?php echo esc_html($firmante_nombre); ?></td>
                            <td>
                                <?php
                                // Mostrar el estado del reporte con una clase CSS para estilizar.
                                $estado_reporte = esc_html($reporte->estado);
                                if ($estado_reporte === 'pendiente') : ?>
                                    <span class="gpv-estado-pendiente"><?php esc_html_e('Pendiente', 'gestion-parque-vehicular'); ?></span>
                                <?php elseif ($estado_reporte === 'generado') : ?>
                                    <span class="gpv-estado-generado"><?php esc_html_e('Generado', 'gestion-parque-vehicular'); ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="column-primary">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=edit&id=' . $reporte->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Editar', 'gestion-parque-vehicular'); ?>
                                </a>

                                <?php
                                // Mostrar botón "Generar PDF" si el estado es pendiente, o "Descargar PDF" si ya está generado.
                                if ($reporte->estado === 'pendiente') : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpv_generate_movement_report&id=' . $reporte->id), 'gpv_generate_report', 'gpv_report_nonce')); ?>" class="button button-primary button-small">
                                        <?php esc_html_e('Generar PDF', 'gestion-parque-vehicular'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpv_download_movement_report&id=' . $reporte->id), 'gpv_download_report', 'gpv_report_nonce')); ?>" class="button button-small">
                                        <?php esc_html_e('Descargar PDF', 'gestion-parque-vehicular'); ?>
                                    </a>
                                <?php endif; ?>

                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpv_delete_movement_report&id=' . $reporte->id), 'delete_reporte_' . $reporte->id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de eliminar este reporte?', 'gestion-parque-vehicular'); ?>')">
                                    <?php esc_html_e('Eliminar', 'gestion-parque-vehicular'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
<?php
}
