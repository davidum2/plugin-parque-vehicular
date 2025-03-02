<?php
// frontend/views/form-retorno.php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

/**
 * Shortcode para mostrar el formulario de retorno de vehículo
 *
 * @return string HTML del formulario o mensaje de error/éxito.
 */
function gpv_formulario_retorno()
{
    // Verificar que el usuario esté logueado
    if (!is_user_logged_in()) {
        return '<div class="error">' . esc_html__('Debe iniciar sesión para registrar el retorno de un vehículo.', 'gestion-parque-vehicular') . '</div>';
    }

    global $wpdb, $GPV_Database;

    // Mensaje de éxito o error
    $mensaje = '';

    // Obtener el ID del usuario actual
    $current_user_id = get_current_user_id();

    // Obtener movimientos activos del usuario actual
    $tabla_movimientos = $wpdb->prefix . 'gpv_movimientos';
    $movimientos_activos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tabla_movimientos
            WHERE conductor_id = %d
            AND estado = 'en_progreso'
            ORDER BY hora_salida DESC",
            $current_user_id
        )
    );

    // Si no hay movimientos activos, mostrar mensaje
    if (empty($movimientos_activos)) {
        return '<div class="notice">' . esc_html__('No tiene movimientos activos para registrar retorno.', 'gestion-parque-vehicular') . '</div>';
    }

    // Procesar formulario si se envió
    if (isset($_POST['gpv_retorno_submit'])) {
        // Verificar nonce
        if (!isset($_POST['gpv_retorno_nonce']) || !wp_verify_nonce($_POST['gpv_retorno_nonce'], 'gpv_retorno_action')) {
            $mensaje = '<div class="error">' . esc_html__('Error de seguridad.', 'gestion-parque-vehicular') . '</div>';
        } else {
            // Obtener y validar datos
            $movimiento_id = intval($_POST['movimiento_id']);
            $odometro_entrada = floatval($_POST['odometro_entrada']);
            $hora_entrada = sanitize_text_field($_POST['hora_entrada']);
            $nivel_combustible = floatval($_POST['nivel_combustible']);
            $notas = sanitize_textarea_field($_POST['notas']);

            // Verificar que el movimiento existe y pertenece al usuario
            $movimiento = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_movimientos WHERE id = %d AND conductor_id = %d AND estado = 'en_progreso'",
                    $movimiento_id,
                    $current_user_id
                )
            );

            if (!$movimiento) {
                $mensaje = '<div class="error">' . esc_html__('Movimiento no encontrado o no autorizado.', 'gestion-parque-vehicular') . '</div>';
            } else {
                // Verificar que el odómetro de entrada sea mayor que el de salida
                if ($odometro_entrada <= $movimiento->odometro_salida) {
                    $mensaje = '<div class="error">' . esc_html__('El odómetro de entrada debe ser mayor que el de salida.', 'gestion-parque-vehicular') . '</div>';
                } else {
                    // Calcular distancia recorrida
                    $distancia_recorrida = $odometro_entrada - $movimiento->odometro_salida;

                    // Obtener vehículo para calcular consumo
                    $tabla_vehiculos = $wpdb->prefix . 'gpv_vehiculos';
                    $vehiculo = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM $tabla_vehiculos WHERE id = %d",
                            $movimiento->vehiculo_id
                        )
                    );

                    if ($vehiculo && $vehiculo->factor_consumo > 0) {
                        // Calcular combustible consumido
                        $combustible_consumido = $distancia_recorrida / $vehiculo->factor_consumo;
                    } else {
                        $combustible_consumido = 0;
                    }

                    // Datos para actualizar
                    $datos_actualizacion = array(
                        'odometro_entrada' => $odometro_entrada,
                        'hora_entrada' => date('Y-m-d H:i:s', strtotime($hora_entrada)),
                        'distancia_recorrida' => $distancia_recorrida,
                        'combustible_consumido' => $combustible_consumido,
                        'nivel_combustible' => $nivel_combustible,
                        'estado' => 'completado',
                        'notas' => $notas
                    );

                    // Actualizar movimiento
                    $resultado = $GPV_Database->update_movement($movimiento_id, $datos_actualizacion);

                    if ($resultado !== false) {
                        // Actualizar odómetro y nivel de combustible del vehículo
                        $datos_vehiculo = array(
                            'odometro_actual' => $odometro_entrada,
                            'nivel_combustible' => $nivel_combustible,
                            'estado' => 'disponible',
                            'ultima_actualizacion' => current_time('mysql')
                        );

                        $GPV_Database->update_vehicle($vehiculo->id, $datos_vehiculo);

                        $mensaje = '<div class="success">' . esc_html__('Retorno registrado correctamente.', 'gestion-parque-vehicular') . '</div>';

                        // Recargar movimientos activos (deberían ser menos o ninguno)
                        $movimientos_activos = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM $tabla_movimientos
                                WHERE conductor_id = %d
                                AND estado = 'en_progreso'
                                ORDER BY hora_salida DESC",
                                $current_user_id
                            )
                        );
                    } else {
                        $mensaje = '<div class="error">' . esc_html__('Error al registrar el retorno.', 'gestion-parque-vehicular') . '</div>';
                    }
                }
            }
        }
    }

    // Si no quedan movimientos activos después de procesar
    if (empty($movimientos_activos)) {
        return $mensaje . '<div class="notice">' . esc_html__('No tiene movimientos activos para registrar retorno.', 'gestion-parque-vehicular') . '</div>';
    }

    // Renderizar formulario
    ob_start();
    echo $mensaje;
?>
    <div class="gpv-form-container">
        <h2><?php esc_html_e('Registrar Retorno de Vehículo', 'gestion-parque-vehicular'); ?></h2>

        <form method="post" class="gpv-form" id="gpv-retorno-form">
            <?php wp_nonce_field('gpv_retorno_action', 'gpv_retorno_nonce'); ?>

            <div class="form-group">
                <label for="movimiento_id"><?php esc_html_e('Seleccionar Movimiento:', 'gestion-parque-vehicular'); ?></label>
                <select name="movimiento_id" id="movimiento_id" required>
                    <option value=""><?php esc_html_e('-- Seleccionar Movimiento --', 'gestion-parque-vehicular'); ?></option>
                    <?php foreach ($movimientos_activos as $mov): ?>
                        <option value="<?php echo esc_attr($mov->id); ?>"
                            data-vehiculo="<?php echo esc_attr($mov->vehiculo_siglas . ' - ' . $mov->vehiculo_nombre); ?>"
                            data-salida="<?php echo esc_attr($mov->odometro_salida); ?>"
                            data-fecha-salida="<?php echo esc_attr($mov->hora_salida); ?>">
                            <?php
                            echo esc_html($mov->vehiculo_siglas . ' - ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($mov->hora_salida)));
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="detalles-movimiento" style="display: none;">
                <div class="info-movimiento">
                    <p><strong><?php esc_html_e('Vehículo:', 'gestion-parque-vehicular'); ?></strong> <span id="info-vehiculo"></span></p>
                    <p><strong><?php esc_html_e('Fecha/Hora Salida:', 'gestion-parque-vehicular'); ?></strong> <span id="info-fecha-salida"></span></p>
                    <p><strong><?php esc_html_e('Odómetro Salida:', 'gestion-parque-vehicular'); ?></strong> <span id="info-odometro-salida"></span></p>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="odometro_entrada"><?php esc_html_e('Odómetro de Entrada:', 'gestion-parque-vehicular'); ?></label>
                        <input type="number" name="odometro_entrada" id="odometro_entrada" step="0.01" required>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="hora_entrada"><?php esc_html_e('Hora de Entrada:', 'gestion-parque-vehicular'); ?></label>
                        <input type="datetime-local" name="hora_entrada" id="hora_entrada" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nivel_combustible"><?php esc_html_e('Nivel de Combustible (%):', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="nivel_combustible" id="nivel_combustible" min="0" max="100" value="50" required>
                </div>

                <div class="form-group">
                    <label for="notas"><?php esc_html_e('Notas:', 'gestion-parque-vehicular'); ?></label>
                    <textarea name="notas" id="notas"></textarea>
                </div>

                <div class="form-group">
                    <label><?php esc_html_e('Información Calculada:', 'gestion-parque-vehicular'); ?></label>
                    <div class="info-calculada">
                        <p><strong><?php esc_html_e('Distancia recorrida:', 'gestion-parque-vehicular'); ?></strong> <span id="distancia_calculada">0</span> km</p>
                    </div>
                </div>

                <div class="form-group">
                    <input type="submit" name="gpv_retorno_submit" value="<?php esc_html_e('Registrar Retorno', 'gestion-parque-vehicular'); ?>">
                </div>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Inicializar fecha y hora actual
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            $('#hora_entrada').val(formattedDateTime);

            // Cuando se selecciona un movimiento
            $('#movimiento_id').change(function() {
                const selectedOption = $(this).find('option:selected');

                if ($(this).val()) {
                    // Mostrar sección de detalles
                    $('#detalles-movimiento').show();

                    // Llenar información del movimiento
                    $('#info-vehiculo').text(selectedOption.data('vehiculo'));
                    $('#info-odometro-salida').text(selectedOption.data('salida'));

                    // Formatear fecha de salida
                    const fechaSalida = new Date(selectedOption.data('fecha-salida'));
                    $('#info-fecha-salida').text(fechaSalida.toLocaleString());

                    // Establecer valor mínimo para odómetro entrada
                    const odometroSalida = parseFloat(selectedOption.data('salida'));
                    $('#odometro_entrada').attr('min', odometroSalida + 0.01);

                    // Sugerir un valor para odómetro entrada (un poco más que el de salida)
                    $('#odometro_entrada').val((odometroSalida + 1).toFixed(2));

                    // Calcular distancia inicial
                    calcularDistancia();
                } else {
                    // Ocultar sección de detalles si no hay selección
                    $('#detalles-movimiento').hide();
                }
            });

            // Recalcular distancia cuando cambia el odómetro de entrada
            $('#odometro_entrada').on('input', function() {
                calcularDistancia();
            });

            // Función para calcular distancia
            function calcularDistancia() {
                const odometroSalida = parseFloat($('#info-odometro-salida').text()) || 0;
                const odometroEntrada = parseFloat($('#odometro_entrada').val()) || 0;

                const distancia = odometroEntrada - odometroSalida;
                $('#distancia_calculada').text(distancia.toFixed(2));

                // Alerta visual si la distancia es negativa o cero
                if (distancia <= 0) {
                    $('#distancia_calculada').css('color', 'red');
                } else {
                    $('#distancia_calculada').css('color', '');
                }
            }

            // Validación del formulario
            $('#gpv-retorno-form').submit(function(e) {
                const odometroSalida = parseFloat($('#info-odometro-salida').text()) || 0;
                const odometroEntrada = parseFloat($('#odometro_entrada').val()) || 0;

                if (odometroEntrada <= odometroSalida) {
                    alert('El odómetro de entrada debe ser mayor que el de salida.');
                    e.preventDefault();
                    $('#odometro_entrada').focus();
                    return false;
                }

                return true;
            });
        });
    </script>
<?php
    return ob_get_clean();
}
// Registrar shortcode
add_shortcode('gpv_form_retorno', 'gpv_formulario_retorno');
