<?php
// Salir si se accede directamente
if (!defined('ABSPATH')) {
    exit;
}

// Obtener lista de reportes
global $GPV_Database;
$reportes = $GPV_Database->get_reportes_movimientos();
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

    <?php if (empty($reportes)): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No hay reportes disponibles. Crea uno nuevo para comenzar.', 'gestion-parque-vehicular'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('ID', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Fecha Reporte', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Número Mensaje', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Vehículos', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Firmante', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Estado', 'gestion-parque-vehicular'); ?></th>
                    <th scope="col"><?php esc_html_e('Acciones', 'gestion-parque-vehicular'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportes as $reporte):
                    // Obtener nombre del firmante
                    $firmante = '';
                    if ($reporte->firmante_id) {
                        $firmantes = $GPV_Database->get_firmantes(['id' => $reporte->firmante_id]);
                        if (!empty($firmantes)) {
                            $firmante = $firmantes[0]->nombre;
                        }
                    }

                    // Formatear fecha
                    $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reporte->fecha_reporte));
                ?>
                    <tr>
                        <td><?php echo esc_html($reporte->id); ?></td>
                        <td><?php echo esc_html($fecha_formateada); ?></td>
                        <td><?php echo esc_html($reporte->numero_mensaje); ?></td>
                        <td><?php
                            // Contar vehículos incluidos
                            $movimientos_ids = explode(',', $reporte->movimientos_incluidos);
                            echo count($movimientos_ids);
                            ?></td>
                        <td><?php echo esc_html($firmante); ?></td>
                        <td>
                            <?php if ($reporte->estado === 'pendiente'): ?>
                                <span class="gpv-estado-pendiente"><?php esc_html_e('Pendiente', 'gestion-parque-vehicular'); ?></span>
                            <?php elseif ($reporte->estado === 'generado'): ?>
                                <span class="gpv-estado-generado"><?php esc_html_e('Generado', 'gestion-parque-vehicular'); ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=edit&id=' . $reporte->id)); ?>" class="button button-small">
                                <?php esc_html_e('Editar', 'gestion-parque-vehicular'); ?>
                            </a>

                            <?php if ($reporte->estado === 'pendiente'): ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpv_generate_movement_report&id=' . $reporte->id), 'gpv_generate_report', 'gpv_report_nonce')); ?>" class="button button-primary button-small">
                                    <?php esc_html_e('Generar PDF', 'gestion-parque-vehicular'); ?>
                                </a>
                            <?php else: ?>
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
