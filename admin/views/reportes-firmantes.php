<?php

/**
 * Vista para la página de gestión de firmantes autorizados para reportes.
 *
 * Permite a los administradores listar, añadir, editar y eliminar firmantes
 * que pueden autorizar los reportes de movimientos de vehículos.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza la página de gestión de firmantes autorizados.
 *
 * Muestra la lista de firmantes existentes, formularios para añadir nuevos
 * firmantes y opciones para editar o eliminar los firmantes existentes.
 *
 * @return void
 */
function gpv_reportes_firmantes_view()
{
    global $GPV_Database;

    // Procesar formulario de nuevo firmante
    if (isset($_POST['gpv_save_firmante']) && isset($_POST['gpv_firmante_nonce']) && wp_verify_nonce($_POST['gpv_firmante_nonce'], 'gpv_save_firmante')) {
        $nombre = sanitize_text_field($_POST['nombre']);
        $cargo = sanitize_text_field($_POST['cargo']);
        $grado = isset($_POST['grado']) ? sanitize_text_field($_POST['grado']) : '';
        $numero_empleado = isset($_POST['numero_empleado']) ? sanitize_text_field($_POST['numero_empleado']) : '';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $notas = isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '';

        if (!empty($nombre) && !empty($cargo)) {
            $data = [
                'nombre' => $nombre,
                'cargo' => $cargo,
                'grado' => $grado,
                'numero_empleado' => $numero_empleado,
                'activo' => $activo,
                'notas' => $notas
            ];

            // Si hay ID, actualizar; si no, insertar
            if (isset($_POST['firmante_id']) && !empty($_POST['firmante_id'])) {
                $firmante_id = intval($_POST['firmante_id']);
                $result = $GPV_Database->update_firmante($firmante_id, $data);
                $message = $result !== false ? 'updated' : 'error';
            } else {
                $result = $GPV_Database->insert_firmante($data);
                $message = $result ? 'created' : 'error';
            }

            // Redireccionar para evitar reenvío del formulario
            if ($message) {
                echo '<script type="text/javascript">
                    window.location.href = "' . esc_url(admin_url('admin.php?page=gpv_reportes&action=firmantes&message=' . $message)) . '";
                </script>';
                exit;
            }
            exit;
        }
    }

    // Procesar eliminación de firmante
    if (isset($_GET['delete_firmante']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_firmante_' . $_GET['delete_firmante'])) {
        $firmante_id = intval($_GET['delete_firmante']);
        $result = $GPV_Database->delete_firmante($firmante_id);

        wp_redirect(admin_url('admin.php?page=gpv_reportes&action=firmantes&message=' . ($result ? 'deleted' : 'error')));
        exit;
    }

    // Obtener firmante para edición si se especifica
    $firmante_edit = null;
    if (isset($_GET['edit_firmante'])) {
        $firmante_id = intval($_GET['edit_firmante']);
        $firmantes = $GPV_Database->obtener_firmantes(['id' => $firmante_id]);
        if (!empty($firmantes)) {
            $firmante_edit = $firmantes[0];
        }
    }

    // Obtener todos los firmantes
    $firmantes = $GPV_Database->obtener_firmantes();

    // Mostrar mensajes
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);

        if ($message === 'created') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Firmante creado exitosamente.', 'gestion-parque-vehicular') . '</p></div>';
        } elseif ($message === 'updated') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Firmante actualizado exitosamente.', 'gestion-parque-vehicular') . '</p></div>';
        } elseif ($message === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Firmante eliminado exitosamente.', 'gestion-parque-vehicular') . '</p></div>';
        } elseif ($message === 'error') {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Ocurrió un error al procesar la solicitud.', 'gestion-parque-vehicular') . '</p></div>';
        }
    }
?>

    <div class="wrap gpv-admin-page">
        <h1 class="wp-heading-inline"><?php echo $firmante_edit ? __('Editar Firmante', 'gestion-parque-vehicular') : __('Gestionar Firmantes Autorizados', 'gestion-parque-vehicular'); ?></h1>

        <?php if (!$firmante_edit): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes')); ?>" class="page-title-action">
                <?php esc_html_e('Volver a Reportes', 'gestion-parque-vehicular'); ?>
            </a>
        <?php endif; ?>

        <hr class="wp-header-end">

        <div class="gpv-form-container">
            <form method="post" action="" class="gpv-admin-form">
                <?php wp_nonce_field('gpv_save_firmante', 'gpv_firmante_nonce'); ?>

                <?php if ($firmante_edit): ?>
                    <input type="hidden" name="firmante_id" value="<?php echo esc_attr($firmante_edit->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nombre"><?php esc_html_e('Nombre completo', 'gestion-parque-vehicular'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="nombre" id="nombre" value="<?php echo $firmante_edit ? esc_attr($firmante_edit->nombre) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cargo"><?php esc_html_e('Cargo', 'gestion-parque-vehicular'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="cargo" id="cargo" value="<?php echo $firmante_edit ? esc_attr($firmante_edit->cargo) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="grado"><?php esc_html_e('Grado o rango', 'gestion-parque-vehicular'); ?></label></th>
                        <td>
                            <input type="text" name="grado" id="grado" value="<?php echo $firmante_edit ? esc_attr($firmante_edit->grado) : ''; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="numero_empleado"><?php esc_html_e('Número de empleado', 'gestion-parque-vehicular'); ?></label></th>
                        <td>
                            <input type="text" name="numero_empleado" id="numero_empleado" value="<?php echo $firmante_edit ? esc_attr($firmante_edit->numero_empleado) : ''; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Estado', 'gestion-parque-vehicular'); ?></th>
                        <td>
                            <label for="activo">
                                <input type="checkbox" name="activo" id="activo" value="1" <?php checked($firmante_edit ? $firmante_edit->activo : 1, 1); ?>>
                                <?php esc_html_e('Activo', 'gestion-parque-vehicular'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notas"><?php esc_html_e('Notas', 'gestion-parque-vehicular'); ?></label></th>
                        <td>
                            <textarea name="notas" id="notas" rows="4" class="large-text"><?php echo $firmante_edit ? esc_textarea($firmante_edit->notas) : ''; ?></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="gpv_save_firmante" class="button button-primary" value="<?php echo $firmante_edit ? esc_attr__('Actualizar Firmante', 'gestion-parque-vehicular') : esc_attr__('Agregar Firmante', 'gestion-parque-vehicular'); ?>">

                    <?php if ($firmante_edit): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=firmantes')); ?>" class="button"><?php esc_html_e('Cancelar', 'gestion-parque-vehicular'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <?php if (!$firmante_edit && !empty($firmantes)): ?>
            <h2><?php esc_html_e('Firmantes registrados', 'gestion-parque-vehicular'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" width="30"><?php esc_html_e('ID', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Nombre', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Cargo', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Grado', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Estado', 'gestion-parque-vehicular'); ?></th>
                        <th scope="col"><?php esc_html_e('Acciones', 'gestion-parque-vehicular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firmantes as $firmante): ?>
                        <tr>
                            <td><?php echo esc_html($firmante->id); ?></td>
                            <td><?php echo esc_html($firmante->nombre); ?></td>
                            <td><?php echo esc_html($firmante->cargo); ?></td>
                            <td><?php echo esc_html($firmante->grado); ?></td>
                            <td>
                                <?php if ($firmante->activo): ?>
                                    <span class="dashicons dashicons-yes" style="color:green;"></span> <?php esc_html_e('Activo', 'gestion-parque-vehicular'); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" style="color:red;"></span> <?php esc_html_e('Inactivo', 'gestion-parque-vehicular'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_reportes&action=firmantes&edit_firmante=' . $firmante->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Editar', 'gestion-parque-vehicular'); ?>
                                </a>

                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gpv_reportes&action=firmantes&delete_firmante=' . $firmante->id), 'delete_firmante_' . $firmante->id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de eliminar este firmante?', 'gestion-parque-vehicular'); ?>')">
                                    <?php esc_html_e('Eliminar', 'gestion-parque-vehicular'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!$firmante_edit): ?>
            <p><?php esc_html_e('No hay firmantes registrados. Añade un nuevo firmante utilizando el formulario anterior.', 'gestion-parque-vehicular'); ?></p>
        <?php endif; ?>
    </div>
<?php
}
