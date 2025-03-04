<?php

/**

 * Este archivo muestra una tabla con todos los reportes de movimientos creados,
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit; // Salir si accessed directly
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
