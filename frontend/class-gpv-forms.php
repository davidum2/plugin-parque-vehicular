<?php
// frontend/class-gpv-forms.php

if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class GPV_Forms
{

    private $database;

    public function __construct()
    {
        global $GPV_Database, $wpdb;

        // Si $GPV_Database no está inicializado, intenta inicializarlo
        if (!$GPV_Database) {
            // Asegurarse de que la clase GPV_Database está incluida
            if (!class_exists('GPV_Database')) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-gpv-database.php';
            }
            $GPV_Database = new GPV_Database();
        }

        $this->database = $GPV_Database;

        // Asegurar que la base de datos está configurada correctamente
        if (!$this->database) {
            // Registrar error
            error_log('GPV_Forms: Error inicializando la base de datos');
        }
    }
    // En class-gpv-forms.php
    public function set_database($database)
    {
        $this->database = $database;
    }

    public function get_database()
    {
        return $this->database;
    }
    /**
     * Genera el formulario para registrar un movimiento de vehículo.
     *
     * @return string HTML del formulario o mensaje de error/éxito.
     */



    /**
     * Formulario para registrar movimientos de vehículos (salidas, entradas o completos)
     *
     * @return string HTML del formulario o mensaje de error/éxito
     */
    public function formulario_movimiento($atts = [])
    {
        // Parámetros por defecto
        $args = shortcode_atts([
            'tipo' => 'auto', // 'salida', 'entrada', 'completo' o 'auto' (detecta automáticamente)
            'movimiento_id' => 0,  // Para entrada, ID del movimiento de salida
        ], $atts);

        // Verificar permisos de usuario
        if (!is_user_logged_in() || (!current_user_can('gpv_register_movements') && !current_user_can('manage_options'))) {
            return '<div class="error">' . __('No tienes permiso para registrar movimientos.', 'gestion-parque-vehicular') . '</div>';
        }

        // Obtener el tipo de formulario (salida, entrada o completo)
        $tipo_form = $args['tipo'];

        // Si es 'auto', detectar según parámetros URL o POST
        if ($tipo_form === 'auto') {
            if (isset($_GET['action']) && $_GET['action'] === 'entrada' && isset($_GET['id'])) {
                $tipo_form = 'entrada';
                $args['movimiento_id'] = intval($_GET['id']);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'completo') {
                $tipo_form = 'completo';
            } else {
                $tipo_form = 'salida';
            }
        }

        $user_id = get_current_user_id();
        $mensaje = '';
        $datos_form = [];
        $vehiculo_actual = null;
        $movimiento_entrada = null;

        // Si es formulario de entrada, cargar datos del movimiento de salida
        if ($tipo_form === 'entrada' && $args['movimiento_id'] > 0) {
            $movimiento = $this->database->get_movement($args['movimiento_id']);

            // Verificar que el movimiento existe y está en progreso
            if (!$movimiento || $movimiento->estado !== 'en_progreso') {
                return '<div class="error">' . __('El movimiento especificado no existe o ya ha sido completado.', 'gestion-parque-vehicular') . '</div>';
            }

            // Verificar que el usuario actual es el mismo que registró la salida
            if ($movimiento->conductor_id != $user_id && !current_user_can('manage_options')) {
                return '<div class="error">' . __('No puedes registrar la entrada de un movimiento que no iniciaste.', 'gestion-parque-vehicular') . '</div>';
            }

            $movimiento_entrada = $movimiento;
            $vehiculo_actual = $this->database->get_vehicle($movimiento->vehiculo_id);

            if (!$vehiculo_actual) {
                return '<div class="error">' . __('El vehículo asociado a este movimiento no existe.', 'gestion-parque-vehicular') . '</div>';
            }
        }

        // Procesar formulario si se envió
        if (isset($_POST['gpv_movimiento_submit'])) {
            // Verificar nonce
            if (!isset($_POST['gpv_mov_nonce']) || !wp_verify_nonce($_POST['gpv_mov_nonce'], 'gpv_mov_action')) {
                $mensaje = '<div class="error">' . __('Error de seguridad.', 'gestion-parque-vehicular') . '</div>';
            } else {
                // Determinar qué acción procesar en base al tipo de formulario y datos enviados
                $accion_form = '';

                if ($tipo_form === 'salida' || ($tipo_form === 'auto' && !isset($_POST['movimiento_id']))) {
                    $accion_form = 'salida';
                } elseif ($tipo_form === 'entrada' || ($tipo_form === 'auto' && isset($_POST['movimiento_id']))) {
                    $accion_form = 'entrada';
                } elseif ($tipo_form === 'completo' || isset($_POST['toggle_entrada'])) {
                    $accion_form = 'completo';
                }

                // Procesar según la acción determinada
                switch ($accion_form) {
                    case 'salida':
                        $mensaje = $this->procesar_salida($_POST);
                        break;
                    case 'entrada':
                        $mensaje = $this->procesar_entrada($_POST);
                        break;
                    case 'completo':
                        $mensaje = $this->procesar_completo($_POST);
                        break;
                }
            }
        }

        // Iniciar buffer de salida para el HTML
        ob_start();

        // Mostrar mensaje de éxito o error si existe
        echo $mensaje;

        // Renderizar el formulario según el tipo
        switch ($tipo_form) {
            case 'entrada':
                echo $this->renderizar_formulario_entrada($movimiento_entrada, $vehiculo_actual);
                break;
            case 'completo':
                echo $this->renderizar_formulario_completo();
                break;
            case 'salida':
            default:
                echo $this->renderizar_formulario_salida();
                break;
        }

        // Retornar contenido del buffer
        return ob_get_clean();
    }

    /**
     * Procesa el registro de salida
     */
    private function procesar_salida($post_data)
    {
        // Obtener y validar el vehículo seleccionado
        $vehiculo_id = intval($post_data['vehiculo_id']);
        $vehiculo = $this->database->get_vehicle($vehiculo_id);

        if (!$vehiculo) {
            return '<div class="error">' . __('Vehículo no encontrado.', 'gestion-parque-vehicular') . '</div>';
        }

        // Sanear datos
        $odometro_salida = floatval($post_data['odometro_salida']);
        $hora_salida = date('Y-m-d H:i:s', strtotime(sanitize_text_field($post_data['hora_salida'])));
        $conductor = sanitize_text_field($post_data['conductor']);
        $proposito = isset($post_data['proposito']) ? sanitize_text_field($post_data['proposito']) : '';

        // Preparar datos para inserción
        $data = array(
            'vehiculo_id' => $vehiculo_id,
            'vehiculo_siglas' => $vehiculo->siglas,
            'vehiculo_nombre' => $vehiculo->nombre_vehiculo,
            'odometro_salida' => $odometro_salida,
            'hora_salida' => $hora_salida,
            'conductor' => $conductor,
            'conductor_id' => get_current_user_id(),
            'proposito' => $proposito,
            'estado' => 'en_progreso'
        );

        // Insertar el movimiento
        $result = $this->database->insert_movement($data);

        if ($result) {
            // Actualizar estado y odómetro del vehículo
            $this->database->update_vehicle($vehiculo_id, [
                'odometro_actual' => $odometro_salida,
                'estado' => 'en_uso',
                'ultima_actualizacion' => current_time('mysql')
            ]);

            return '<div class="success">' . __('Salida registrada correctamente.', 'gestion-parque-vehicular') . '</div>';
        } else {
            return '<div class="error">' . __('Error al registrar la salida.', 'gestion-parque-vehicular') . '</div>';
        }
    }

    /**
     * Procesa el registro de entrada
     */
    private function procesar_entrada($post_data)
    {
        // Obtener ID del movimiento y validar
        $movimiento_id = intval($post_data['movimiento_id']);
        $movimiento = $this->database->get_movement($movimiento_id);

        if (!$movimiento || $movimiento->estado !== 'en_progreso') {
            return '<div class="error">' . __('El movimiento especificado no existe o ya ha sido completado.', 'gestion-parque-vehicular') . '</div>';
        }

        // Verificar que el usuario actual es el mismo que registró la salida (o es admin)
        $user_id = get_current_user_id();
        if ($movimiento->conductor_id != $user_id && !current_user_can('manage_options')) {
            return '<div class="error">' . __('No puedes registrar la entrada de un movimiento que no iniciaste.', 'gestion-parque-vehicular') . '</div>';
        }

        // Obtener vehículo
        $vehiculo = $this->database->get_vehicle($movimiento->vehiculo_id);
        if (!$vehiculo) {
            return '<div class="error">' . __('El vehículo asociado a este movimiento no existe.', 'gestion-parque-vehicular') . '</div>';
        }

        // Sanear datos
        $odometro_entrada = floatval($post_data['odometro_entrada']);
        $hora_entrada = date('Y-m-d H:i:s', strtotime(sanitize_text_field($post_data['hora_entrada'])));

        // Validar que el odómetro de entrada sea mayor que el de salida
        if ($odometro_entrada <= $movimiento->odometro_salida) {
            return '<div class="error">' . __('El odómetro de entrada debe ser mayor que el de salida.', 'gestion-parque-vehicular') . '</div>';
        }

        // Calcular distancia recorrida
        $distancia_recorrida = $odometro_entrada - $movimiento->odometro_salida;

        // Calcular combustible consumido basado en el factor de consumo del vehículo
        $combustible_consumido = 0;
        if ($vehiculo->factor_consumo > 0) {
            $combustible_consumido = $distancia_recorrida / $vehiculo->factor_consumo;
        }

        // Calcular nivel de combustible restante en litros
        $nivel_combustible = $vehiculo->nivel_combustible;
        if ($combustible_consumido > 0) {
            $nivel_combustible = max(0, $vehiculo->nivel_combustible - $combustible_consumido);
        }

        // Notas adicionales si se proporcionaron
        $notas = isset($post_data['notas']) ? sanitize_textarea_field($post_data['notas']) : '';

        // Preparar datos para actualización
        $data = array(
            'odometro_entrada' => $odometro_entrada,
            'hora_entrada' => $hora_entrada,
            'distancia_recorrida' => $distancia_recorrida,
            'combustible_consumido' => $combustible_consumido,
            'nivel_combustible' => $nivel_combustible,
            'estado' => 'completado',
            'notas' => $notas
        );

        // Actualizar el movimiento
        $result = $this->database->update_movement($movimiento_id, $data);

        if ($result !== false) {
            // Actualizar el vehículo
            $this->database->update_vehicle($movimiento->vehiculo_id, [
                'odometro_actual' => $odometro_entrada,
                'nivel_combustible' => $nivel_combustible,
                'estado' => 'disponible',
                'ultima_actualizacion' => current_time('mysql')
            ]);

            return '<div class="success">' . __('Entrada registrada correctamente.', 'gestion-parque-vehicular') . '</div>';
        } else {
            return '<div class="error">' . __('Error al registrar la entrada.', 'gestion-parque-vehicular') . '</div>';
        }
    }

    /**
     * Procesa el registro completo (salida y entrada)
     */
    private function procesar_completo($post_data)
    {
        // Primero, procesamos la salida
        $resultado_salida = $this->procesar_salida($post_data);

        // Si hubo un error en la salida, retornamos el error
        if (strpos($resultado_salida, 'error') !== false) {
            return $resultado_salida;
        }

        // Obtenemos el ID del movimiento creado (último registrado para este vehículo)
        $vehiculo_id = intval($post_data['vehiculo_id']);
        global $wpdb;
        $tabla = $wpdb->prefix . 'gpv_movimientos';
        $movimiento_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $tabla WHERE vehiculo_id = %d ORDER BY id DESC LIMIT 1",
                $vehiculo_id
            )
        );

        if (!$movimiento_id) {
            return '<div class="error">' . __('Error al procesar el movimiento completo.', 'gestion-parque-vehicular') . '</div>';
        }

        // Preparamos los datos para procesar la entrada
        $datos_entrada = array(
            'movimiento_id' => $movimiento_id,
            'odometro_entrada' => floatval($post_data['odometro_entrada']),
            'hora_entrada' => $post_data['hora_entrada'],
            'notas' => isset($post_data['notas']) ? $post_data['notas'] : ''
        );

        // Procesamos la entrada
        $resultado_entrada = $this->procesar_entrada($datos_entrada);

        // Retornamos el resultado de la entrada
        return $resultado_entrada;
    }

    /**
     * Renderiza el formulario de salida
     */
    private function renderizar_formulario_salida()
    {
        // Obtener vehículos disponibles
        $vehiculos = $this->database->get_vehicles(['estado' => 'disponible']);

        // Si el usuario no es admin, filtrar solo sus vehículos asignados
        if (!current_user_can('manage_options')) {
            $user_id = get_current_user_id();
            $vehiculos = array_filter($vehiculos, function ($v) use ($user_id) {
                return $v->conductor_asignado == 0 || $v->conductor_asignado == $user_id;
            });
        }

        // Si no hay vehículos disponibles
        if (empty($vehiculos)) {
            return '<div class="notice">' . __('No hay vehículos disponibles para registrar movimientos.', 'gestion-parque-vehicular') . '</div>';
        }

        ob_start();
?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Salida de Vehículo', 'gestion-parque-vehicular') ?></h2>

            <form method="post" class="gpv-form" id="gpv-movimiento-form">
                <?php wp_nonce_field('gpv_mov_action', 'gpv_mov_nonce'); ?>

                <div class="form-group">
                    <label for="vehiculo_id"><?php esc_html_e('Seleccionar Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <select name="vehiculo_id" id="vehiculo_id" required>
                        <option value=""><?php esc_html_e('-- Seleccionar Vehículo --', 'gestion-parque-vehicular') ?></option>
                        <?php foreach ($vehiculos as $vehiculo) : ?>
                            <option value="<?php echo esc_attr($vehiculo->id); ?>"
                                data-odometro="<?php echo esc_attr($vehiculo->odometro_actual); ?>"
                                data-combustible="<?php echo esc_attr($vehiculo->nivel_combustible); ?>"
                                data-factor="<?php echo esc_attr($vehiculo->factor_consumo); ?>"
                                data-capacidad="<?php echo esc_attr($vehiculo->capacidad_tanque); ?>"
                                <?php echo (isset($_POST['vehiculo_id']) && $_POST['vehiculo_id'] == $vehiculo->id) ? 'selected' : ''; ?>>
                                <?php echo esc_html($vehiculo->siglas . ' - ' . $vehiculo->nombre_vehiculo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="gpv-field-description"><?php esc_html_e('Solo se muestran los vehículos disponibles.', 'gestion-parque-vehicular') ?></p>
                </div>

                <div class="form-group">
                    <label for="conductor"><?php esc_html_e('Conductor:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="conductor" id="conductor" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="proposito"><?php esc_html_e('Propósito del viaje:', 'gestion-parque-vehicular') ?></label>
                    <textarea name="proposito" id="proposito"><?php echo isset($_POST['proposito']) ? esc_textarea($_POST['proposito']) : ''; ?></textarea>
                    <p class="gpv-field-description"><?php esc_html_e('Describa brevemente el propósito de este movimiento.', 'gestion-parque-vehicular') ?></p>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="odometro_salida"><?php esc_html_e('Odómetro de Salida:', 'gestion-parque-vehicular') ?></label>
                        <input type="number" name="odometro_salida" id="odometro_salida" step="0.01" value="<?php echo isset($_POST['odometro_salida']) ? esc_attr($_POST['odometro_salida']) : ''; ?>" required>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="hora_salida"><?php esc_html_e('Hora de Salida:', 'gestion-parque-vehicular') ?></label>
                        <input type="datetime-local" name="hora_salida" id="hora_salida" value="<?php echo isset($_POST['hora_salida']) ? esc_attr($_POST['hora_salida']) : ''; ?>" required>
                    </div>
                </div>

                <!-- Sección opcional para registrar también la entrada -->
                <div class="gpv-section-toggle">
                    <h3>
                        <input type="checkbox" id="toggle_entrada" name="toggle_entrada" <?php echo isset($_POST['toggle_entrada']) ? 'checked' : ''; ?>>
                        <label for="toggle_entrada"><?php esc_html_e('¿Registrar también la entrada?', 'gestion-parque-vehicular') ?></label>
                    </h3>

                    <div id="entrada_section" class="<?php echo isset($_POST['toggle_entrada']) ? '' : 'hidden'; ?>">
                        <div class="form-row">
                            <div class="form-group form-group-half">
                                <label for="odometro_entrada"><?php esc_html_e('Odómetro de Entrada:', 'gestion-parque-vehicular') ?></label>
                                <input type="number" name="odometro_entrada" id="odometro_entrada" step="1" value="<?php echo isset($_POST['odometro_entrada']) ? esc_attr($_POST['odometro_entrada']) : ''; ?>">
                            </div>

                            <div class="form-group form-group-half">
                                <label for="hora_entrada"><?php esc_html_e('Hora de Entrada:', 'gestion-parque-vehicular') ?></label>
                                <input type="datetime-local" name="hora_entrada" id="hora_entrada" value="<?php echo isset($_POST['hora_entrada']) ? esc_attr($_POST['hora_entrada']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notas"><?php esc_html_e('Notas:', 'gestion-parque-vehicular') ?></label>
                            <textarea name="notas" id="notas"><?php echo isset($_POST['notas']) ? esc_textarea($_POST['notas']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php esc_html_e('Información Calculada:', 'gestion-parque-vehicular') ?></label>
                    <div class="info-calculada">
                        <p><strong><?php esc_html_e('Distancia a recorrer:', 'gestion-parque-vehicular') ?></strong> <span id="distancia_calculada">0</span> km</p>
                        <p><strong><?php esc_html_e('Combustible a consumir:', 'gestion-parque-vehicular') ?></strong> <span id="combustible_calculado">0</span> L</p>
                        <p><strong><?php esc_html_e('Nivel de combustible final:', 'gestion-parque-vehicular') ?></strong> <span id="nivel_final">0</span> %</p>
                        <p><strong><?php esc_html_e('Capacidad del tanque:', 'gestion-parque-vehicular') ?></strong> <span id="capacidad_tanque">0</span> L</p>
                    </div>
                </div>

                <div class="form-group">
                    <input type="submit" name="gpv_movimiento_submit" value="<?php esc_html_e('Registrar Salida', 'gestion-parque-vehicular') ?>">
                </div>
            </form>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Referencias a elementos del formulario
                const vehiculoSelect = document.getElementById('vehiculo_id');
                const odometroSalida = document.getElementById('odometro_salida');
                const odometroEntrada = document.getElementById('odometro_entrada');
                const toggleEntrada = document.getElementById('toggle_entrada');
                const entradaSection = document.getElementById('entrada_section');
                const distanciaCalculada = document.getElementById('distancia_calculada');
                const combustibleCalculado = document.getElementById('combustible_calculado');
                const nivelFinal = document.getElementById('nivel_final');
                const capacidadTanque = document.getElementById('capacidad_tanque');
                const horaSalida = document.getElementById('hora_salida');
                const horaEntrada = document.getElementById('hora_entrada');

                // Establecer fecha y hora actual en los campos datetime-local si están vacíos
                if (!horaSalida.value) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                    horaSalida.value = formattedDateTime;
                    horaEntrada.value = formattedDateTime;
                }

                // Manejar cambio en mostrar/ocultar sección de entrada
                toggleEntrada.addEventListener('change', function() {
                    if (this.checked) {
                        entradaSection.classList.remove('hidden');
                        // Hacer los campos de entrada requeridos
                        odometroEntrada.setAttribute('required', 'required');
                        horaEntrada.setAttribute('required', 'required');
                    } else {
                        entradaSection.classList.add('hidden');
                        // Quitar requerido de campos de entrada
                        odometroEntrada.removeAttribute('required');
                        horaEntrada.removeAttribute('required');
                    }
                });

                // Cuando se selecciona un vehículo
                vehiculoSelect.addEventListener('change', function() {
                    const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];

                    if (selectedOption.value) {
                        const vehiculoOdometro = parseFloat(selectedOption.dataset.odometro) || 0;
                        const vehiculoCombustible = parseFloat(selectedOption.dataset.combustible) || 0;
                        const vehiculoCapacidad = parseFloat(selectedOption.dataset.capacidad) || 0;

                        // Establecer odómetro de salida con el valor actual del vehículo
                        odometroSalida.value = vehiculoOdometro;

                        // Actualizar capacidad del tanque mostrada
                        capacidadTanque.textContent = vehiculoCapacidad.toFixed(2);

                        // Calcular valores iniciales
                        calcularValores();
                    }
                });

                // Cuando cambian los odómetros
                odometroSalida.addEventListener('input', calcularValores);
                odometroEntrada.addEventListener('input', calcularValores);

                // Función para calcular valores
                function calcularValores() {
                    const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];

                    if (selectedOption.value) {
                        const vehiculoOdometroActual = parseFloat(selectedOption.dataset.odometro) || 0;
                        const nivelCombustible = parseFloat(selectedOption.dataset.combustible) || 0;
                        const factorConsumo = parseFloat(selectedOption.dataset.factor) || 0;
                        const capacidadTanqueValue = parseFloat(selectedOption.dataset.capacidad) || 0;

                        const odometroSalidaValue = parseFloat(odometroSalida.value) || vehiculoOdometroActual;
                        const odometroEntradaValue = parseFloat(odometroEntrada.value) || odometroSalidaValue;

                        // Calcular distancia
                        const distancia = Math.max(0, odometroEntradaValue - odometroSalidaValue);
                        distanciaCalculada.textContent = distancia.toFixed(2);

                        // Calcular combustible consumido
                        let combustibleConsumido = 0;
                        if (factorConsumo > 0) {
                            combustibleConsumido = distancia / factorConsumo;
                        }
                        combustibleCalculado.textContent = combustibleConsumido.toFixed(2);

                        // Calcular nivel final de combustible como porcentaje
                        let porcentajeConsumido = 0;
                        let nuevoNivel = nivelCombustible;

                        if (capacidadTanqueValue > 0 && combustibleConsumido > 0) {
                            porcentajeConsumido = (combustibleConsumido / capacidadTanqueValue) * 100;
                            nuevoNivel = Math.max(0, nivelCombustible - porcentajeConsumido);
                        }

                        nivelFinal.textContent = nuevoNivel.toFixed(2);
                        capacidadTanque.textContent = capacidadTanqueValue.toFixed(2);

                        // Mostrar advertencia si el nivel de combustible es bajo
                        if (nuevoNivel < 20) {
                            nivelFinal.classList.add('gpv-nivel-bajo');
                        } else {
                            nivelFinal.classList.remove('gpv-nivel-bajo');
                        }
                    }
                }

                // Inicializar cálculos si hay un vehículo seleccionado
                if (vehiculoSelect.value) {
                    calcularValores();
                }

                // Validación del formulario
                const form = document.getElementById('gpv-movimiento-form');
                form.addEventListener('submit', function(e) {
                    const entradaActiva = toggleEntrada.checked;

                    if (entradaActiva) {
                        const salidaOdometro = parseFloat(odometroSalida.value) || 0;
                        const entradaOdometro = parseFloat(odometroEntrada.value) || 0;

                        if (entradaOdometro <= salidaOdometro) {
                            e.preventDefault();
                            alert('El odómetro de entrada debe ser mayor que el de salida.');
                            odometroEntrada.focus();
                        }
                    }
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de entrada
     */
    private function renderizar_formulario_entrada($movimiento, $vehiculo)
    {
        ob_start();
    ?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Entrada de Vehículo', 'gestion-parque-vehicular') ?></h2>

            <div class="gpv-info-box">
                <h3><?php esc_html_e('Información del movimiento', 'gestion-parque-vehicular') ?></h3>
                <p><strong><?php esc_html_e('Vehículo:', 'gestion-parque-vehicular') ?></strong> <?php echo esc_html($movimiento->vehiculo_siglas . ' - ' . $movimiento->vehiculo_nombre); ?></p>
                <p><strong><?php esc_html_e('Conductor:', 'gestion-parque-vehicular') ?></strong> <?php echo esc_html($movimiento->conductor); ?></p>
                <p><strong><?php esc_html_e('Salida:', 'gestion-parque-vehicular') ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_salida))); ?></p>
                <p><strong><?php esc_html_e('Odómetro de salida:', 'gestion-parque-vehicular') ?></strong> <?php echo esc_html($movimiento->odometro_salida); ?></p>
                <?php if (!empty($movimiento->proposito)): ?>
                    <p><strong><?php esc_html_e('Propósito:', 'gestion-parque-vehicular') ?></strong> <?php echo esc_html($movimiento->proposito); ?></p>
                <?php endif; ?>
            </div>

            <form method="post" class="gpv-form" id="gpv-entrada-form">
                <?php wp_nonce_field('gpv_mov_action', 'gpv_mov_nonce'); ?>
                <input type="hidden" name="movimiento_id" value="<?php echo esc_attr($movimiento->id); ?>">

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="odometro_entrada"><?php esc_html_e('Odómetro de Entrada:', 'gestion-parque-vehicular') ?></label>
                        <input type="number" name="odometro_entrada" id="odometro_entrada" step="1" value="<?php echo isset($_POST['odometro_entrada']) ? esc_attr($_POST['odometro_entrada']) : ''; ?>" required>
                        <p class="gpv-field-description"><?php esc_html_e('Debe ser mayor que el odómetro de salida.', 'gestion-parque-vehicular') ?></p>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="hora_entrada"><?php esc_html_e('Hora de Entrada:', 'gestion-parque-vehicular') ?></label>
                        <input type="datetime-local" name="hora_entrada" id="hora_entrada" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notas"><?php esc_html_e('Notas:', 'gestion-parque-vehicular') ?></label>
                    <textarea name="notas" id="notas"><?php echo isset($_POST['notas']) ? esc_textarea($_POST['notas']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label><?php esc_html_e('Información Calculada:', 'gestion-parque-vehicular') ?></label>
                    <div class="info-calculada">
                        <p><strong><?php esc_html_e('Distancia recorrida:', 'gestion-parque-vehicular') ?></strong> <span id="distancia_calculada">0</span> km</p>
                        <p><strong><?php esc_html_e('Combustible consumido:', 'gestion-parque-vehicular') ?></strong> <span id="combustible_calculado">0</span> L</p>
                        <p><strong><?php esc_html_e('Nivel de combustible final:', 'gestion-parque-vehicular') ?></strong> <span id="nivel_final">0</span> %</p>
                    </div>
                </div>

                <div class="form-group">
                    <input type="submit" name="gpv_movimiento_submit" value="<?php esc_html_e('Registrar Entrada', 'gestion-parque-vehicular') ?>">
                </div>
            </form>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Referencias a elementos del formulario
                const odometroEntrada = document.getElementById('odometro_entrada');
                const horaEntrada = document.getElementById('hora_entrada');
                const distanciaCalculada = document.getElementById('distancia_calculada');
                const combustibleCalculado = document.getElementById('combustible_calculado');
                const nivelFinal = document.getElementById('nivel_final');

                // Datos del vehículo y el movimiento
                const odometroSalida = <?php echo floatval($movimiento->odometro_salida); ?>;
                const nivelCombustible = <?php echo floatval($vehiculo->nivel_combustible); ?>;
                const factorConsumo = <?php echo floatval($vehiculo->factor_consumo); ?>;
                const capacidadTanque = <?php echo floatval($vehiculo->capacidad_tanque); ?>;

                // Establecer fecha y hora actual en el campo datetime-local
                if (!horaEntrada.value) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                    horaEntrada.value = formattedDateTime;
                }

                // Sugiere un valor inicial para el odómetro de entrada (un poco más que el de salida)
                if (!odometroEntrada.value) {
                    odometroEntrada.value = (odometroSalida + 1).toFixed(2);
                    calcularValores();
                }

                // Cuando cambia el odómetro
                odometroEntrada.addEventListener('input', calcularValores);

                // Función para calcular valores
                function calcularValores() {
                    const odometroEntradaValue = parseFloat(odometroEntrada.value) || odometroSalida;

                    // Calcular distancia
                    const distancia = Math.max(0, odometroEntradaValue - odometroSalida);
                    distanciaCalculada.textContent = distancia.toFixed(2);

                    // Calcular combustible consumido
                    let combustibleConsumido = 0;
                    if (factorConsumo > 0) {
                        combustibleConsumido = distancia / factorConsumo;
                    }
                    combustibleCalculado.textContent = combustibleConsumido.toFixed(2);

                    // Calcular nivel final de combustible
                    let nuevoNivel = nivelCombustible;
                    if (combustibleConsumido > 0) {
                        nuevoNivel = Math.max(0, nivelCombustible - combustibleConsumido);
                    }
                    nivelFinal.textContent = nuevoNivel.toFixed(2);

                    // Mostrar advertencia si el nivel de combustible es bajo
                    if (nuevoNivel < 20) {
                        nivelFinal.classList.add('gpv-nivel-bajo');
                    } else {
                        nivelFinal.classList.remove('gpv-nivel-bajo');
                    }
                }

                // Inicializar cálculos
                calcularValores();

                // Validación del formulario
                const form = document.getElementById('gpv-entrada-form');
                form.addEventListener('submit', function(e) {
                    const entradaOdometro = parseFloat(odometroEntrada.value) || 0;

                    if (entradaOdometro <= odometroSalida) {
                        e.preventDefault();
                        alert('El odómetro de entrada debe ser mayor que el de salida.');
                        odometroEntrada.focus();
                    }
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario completo (igual que el de salida pero con opciones de entrada ya visibles)
     */
    private function renderizar_formulario_completo()
    {
        // Obtener vehículos disponibles
        $vehiculos = $this->database->get_vehicles(['estado' => 'disponible']);

        // Si el usuario no es admin, filtrar solo sus vehículos asignados
        if (!current_user_can('manage_options')) {
            $user_id = get_current_user_id();
            $vehiculos = array_filter($vehiculos, function ($v) use ($user_id) {
                return $v->conductor_asignado == 0 || $v->conductor_asignado == $user_id;
            });
        }

        // Si no hay vehículos disponibles
        if (empty($vehiculos)) {
            return '<div class="notice">' . __('No hay vehículos disponibles para registrar movimientos.', 'gestion-parque-vehicular') . '</div>';
        }

        ob_start();
    ?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Movimiento Completo', 'gestion-parque-vehicular') ?></h2>
            <p class="gpv-info-message"><?php esc_html_e('Este formulario permite registrar la salida y entrada de un vehículo en un solo paso.', 'gestion-parque-vehicular') ?></p>

            <form method="post" class="gpv-form" id="gpv-movimiento-completo-form">
                <?php wp_nonce_field('gpv_mov_action', 'gpv_mov_nonce'); ?>
                <input type="hidden" name="toggle_entrada" value="1">

                <div class="form-group">
                    <label for="vehiculo_id"><?php esc_html_e('Seleccionar Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <select name="vehiculo_id" id="vehiculo_id" required>
                        <option value=""><?php esc_html_e('-- Seleccionar Vehículo --', 'gestion-parque-vehicular') ?></option>
                        <?php foreach ($vehiculos as $vehiculo) : ?>
                            <option value="<?php echo esc_attr($vehiculo->id); ?>"
                                data-odometro="<?php echo esc_attr($vehiculo->odometro_actual); ?>"
                                data-combustible="<?php echo esc_attr($vehiculo->nivel_combustible); ?>"
                                data-factor="<?php echo esc_attr($vehiculo->factor_consumo); ?>"
                                data-capacidad="<?php echo esc_attr($vehiculo->capacidad_tanque); ?>"
                                <?php echo (isset($_POST['vehiculo_id']) && $_POST['vehiculo_id'] == $vehiculo->id) ? 'selected' : ''; ?>>
                                <?php echo esc_html($vehiculo->siglas . ' - ' . $vehiculo->nombre_vehiculo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="conductor"><?php esc_html_e('Conductor:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="conductor" id="conductor" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="proposito"><?php esc_html_e('Propósito del viaje:', 'gestion-parque-vehicular') ?></label>
                    <textarea name="proposito" id="proposito"><?php echo isset($_POST['proposito']) ? esc_textarea($_POST['proposito']) : ''; ?></textarea>
                </div>

                <div class="gpv-section">
                    <h3><?php esc_html_e('Datos de Salida', 'gestion-parque-vehicular') ?></h3>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="odometro_salida"><?php esc_html_e('Odómetro de Salida:', 'gestion-parque-vehicular') ?></label>
                            <input type="number" name="odometro_salida" id="odometro_salida" step="0.01" required>
                        </div>

                        <div class="form-group form-group-half">
                            <label for="hora_salida"><?php esc_html_e('Hora de Salida:', 'gestion-parque-vehicular') ?></label>
                            <input type="datetime-local" name="hora_salida" id="hora_salida" required>
                        </div>
                    </div>
                </div>

                <div class="gpv-section">
                    <h3><?php esc_html_e('Datos de Entrada', 'gestion-parque-vehicular') ?></h3>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="odometro_entrada"><?php esc_html_e('Odómetro de Entrada:', 'gestion-parque-vehicular') ?></label>
                            <input type="number" name="odometro_entrada" id="odometro_entrada" step="1" required>
                            <p class="gpv-field-description"><?php esc_html_e('Debe ser mayor que el odómetro de salida.', 'gestion-parque-vehicular') ?></p>
                        </div>

                        <div class="form-group form-group-half">
                            <label for="hora_entrada"><?php esc_html_e('Hora de Entrada:', 'gestion-parque-vehicular') ?></label>
                            <input type="datetime-local" name="hora_entrada" id="hora_entrada" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notas"><?php esc_html_e('Notas:', 'gestion-parque-vehicular') ?></label>
                        <textarea name="notas" id="notas"><?php echo isset($_POST['notas']) ? esc_textarea($_POST['notas']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php esc_html_e('Información Calculada:', 'gestion-parque-vehicular') ?></label>
                    <div class="info-calculada">
                        <p><strong><?php esc_html_e('Distancia recorrida:', 'gestion-parque-vehicular') ?></strong> <span id="distancia_calculada">0</span> km</p>
                        <p><strong><?php esc_html_e('Combustible consumido:', 'gestion-parque-vehicular') ?></strong> <span id="combustible_calculado">0</span> L</p>
                        <p><strong><?php esc_html_e('Nivel de combustible final:', 'gestion-parque-vehicular') ?></strong> <span id="nivel_final">0</span> %</p>
                        <p><strong><?php esc_html_e('Capacidad del tanque:', 'gestion-parque-vehicular') ?></strong> <span id="capacidad_tanque">0</span> L</p>
                    </div>
                </div>

                <div class="form-group">
                    <input type="submit" name="gpv_movimiento_submit" value="<?php esc_html_e('Registrar Movimiento Completo', 'gestion-parque-vehicular') ?>">
                </div>
            </form>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Referencias a elementos del formulario
                const vehiculoSelect = document.getElementById('vehiculo_id');
                const odometroSalida = document.getElementById('odometro_salida');
                const odometroEntrada = document.getElementById('odometro_entrada');
                const distanciaCalculada = document.getElementById('distancia_calculada');
                const combustibleCalculado = document.getElementById('combustible_calculado');
                const nivelFinal = document.getElementById('nivel_final');
                const capacidadTanque = document.getElementById('capacidad_tanque');
                const horaSalida = document.getElementById('hora_salida');
                const horaEntrada = document.getElementById('hora_entrada');

                // Establecer fecha y hora actual en los campos datetime-local
                if (!horaSalida.value) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                    horaSalida.value = formattedDateTime;

                    // Para la hora de entrada, sugerimos una hora después
                    const laterDate = new Date(now.getTime() + 60 * 60 * 1000); // 1 hora después
                    const laterHours = String(laterDate.getHours()).padStart(2, '0');
                    const laterMinutes = String(laterDate.getMinutes()).padStart(2, '0');

                    const laterFormattedDateTime = `${year}-${month}-${day}T${laterHours}:${laterMinutes}`;
                    horaEntrada.value = laterFormattedDateTime;
                }

                // Cuando se selecciona un vehículo
                vehiculoSelect.addEventListener('change', function() {
                    const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];

                    if (selectedOption.value) {
                        const vehiculoOdometro = parseFloat(selectedOption.dataset.odometro) || 0;
                        const vehiculoCombustible = parseFloat(selectedOption.dataset.combustible) || 0;
                        const vehiculoCapacidad = parseFloat(selectedOption.dataset.capacidad) || 0;

                        // Establecer odómetro de salida con el valor actual del vehículo
                        odometroSalida.value = vehiculoOdometro;

                        // Sugerir un valor para odómetro de entrada (10 km más)
                        odometroEntrada.value = (vehiculoOdometro + 10).toFixed(2);

                        // Actualizar capacidad del tanque mostrada
                        capacidadTanque.textContent = vehiculoCapacidad.toFixed(2);

                        // Calcular valores iniciales
                        calcularValores();
                    }
                });

                // Cuando cambian los odómetros
                odometroSalida.addEventListener('input', calcularValores);
                odometroEntrada.addEventListener('input', calcularValores);

                // Función para calcular valores
                function calcularValores() {
                    const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];

                    if (selectedOption.value) {
                        const vehiculoOdometroActual = parseFloat(selectedOption.dataset.odometro) || 0;
                        const nivelCombustible = parseFloat(selectedOption.dataset.combustible) || 0;
                        const factorConsumo = parseFloat(selectedOption.dataset.factor) || 0;
                        const capacidadTanqueValue = parseFloat(selectedOption.dataset.capacidad) || 0;

                        const odometroSalidaValue = parseFloat(odometroSalida.value) || vehiculoOdometroActual;
                        const odometroEntradaValue = parseFloat(odometroEntrada.value) || odometroSalidaValue;

                        // Calcular distancia
                        const distancia = Math.max(0, odometroEntradaValue - odometroSalidaValue);
                        distanciaCalculada.textContent = distancia.toFixed(2);

                        // Calcular combustible consumido
                        let combustibleConsumido = 0;
                        if (factorConsumo > 0) {
                            combustibleConsumido = distancia / factorConsumo;
                        }
                        combustibleCalculado.textContent = combustibleConsumido.toFixed(2);

                        // Calcular nivel final de combustible como porcentaje
                        let porcentajeConsumido = 0;
                        let nuevoNivel = nivelCombustible;
                        if (combustibleConsumido > 0) {
                            nuevoNivel = Math.max(0, nivelCombustible - combustibleConsumido);
                        }

                        nivelFinal.textContent = nuevoNivel.toFixed(2);
                        capacidadTanque.textContent = capacidadTanqueValue.toFixed(2);

                        // Mostrar advertencia si el nivel de combustible es bajo
                        if (nuevoNivel < 20) {
                            nivelFinal.classList.add('gpv-nivel-bajo');
                        } else {
                            nivelFinal.classList.remove('gpv-nivel-bajo');
                        }
                    }
                }

                // Inicializar cálculos si hay un vehículo seleccionado
                if (vehiculoSelect.value) {
                    calcularValores();
                }

                // Validación del formulario
                const form = document.getElementById('gpv-movimiento-completo-form');
                form.addEventListener('submit', function(e) {
                    const salidaOdometro = parseFloat(odometroSalida.value) || 0;
                    const entradaOdometro = parseFloat(odometroEntrada.value) || 0;

                    if (entradaOdometro <= salidaOdometro) {
                        e.preventDefault();
                        alert('El odómetro de entrada debe ser mayor que el de salida.');
                        odometroEntrada.focus();
                    }
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }





















    /**
     * Genera el formulario para registrar una carga de combustible.
     *
     * @return string HTML del formulario o mensaje de error/éxito.
     */
    public function formulario_carga()
    {
        if (isset($_POST['gpv_carga_submit'])) {
            if (! isset($_POST['gpv_carga_nonce']) || ! wp_verify_nonce($_POST['gpv_carga_nonce'], 'gpv_carga_action')) {
                return '<div class="error">Error de seguridad.</div>';
            }


            $vehiculo_siglas = sanitize_text_field($_POST['vehiculo_siglas']);
            $vehiculo_nombre = sanitize_text_field($_POST['vehiculo_nombre']);
            $odometro_carga = floatval($_POST['odometro_carga']);
            $litros_cargados = floatval($_POST['litros_cargados']);
            $precio = floatval($_POST['precio']);
            $km_desde_ultima_carga = floatval($_POST['km_desde_ultima_carga']);
            $factor_consumo = floatval($_POST['factor_consumo']);


            $data = array(
                'vehiculo_siglas' => $vehiculo_siglas,
                'vehiculo_nombre' => $vehiculo_nombre,
                'odometro_carga' => $odometro_carga,
                'litros_cargados' => $litros_cargados,
                'precio' => $precio,
                'km_desde_ultima_carga' => $km_desde_ultima_carga,
                'factor_consumo' => $factor_consumo,
            );


            $insert =  $this->database->insert_fuel($data);


            if ($insert) {
                return '<div class="success">Carga registrada correctamente.</div>';
            } else {
                return '<div class="error">Error al registrar la carga.</div>';
            }
        }

        ob_start();
    ?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Carga de Combustible', 'gestion-parque-vehicular') ?></h2>
            <form method="post" class="gpv-form">
                <?php wp_nonce_field('gpv_carga_action', 'gpv_carga_nonce'); ?>
                <div class="form-group">
                    <label for="vehiculo_siglas"><?php esc_html_e('Vehículo Siglas:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="vehiculo_siglas" required>
                </div>
                <div class="form-group">
                    <label for="vehiculo_nombre"><?php esc_html_e('Nombre del Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="vehiculo_nombre" required>
                </div>
                <div class="form-group">
                    <label for="odometro_carga"><?php esc_html_e('Odómetro de Carga:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="odometro_carga" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="litros_cargados"><?php esc_html_e('Litros Cargados:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="litros_cargados" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="precio"><?php esc_html_e('Precio:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="precio" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="km_desde_ultima_carga"><?php esc_html_e('Kilómetros desde la última carga:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="km_desde_ultima_carga" step="0.01">
                </div>
                <div class="form-group">
                    <label for="factor_consumo"><?php esc_html_e('Factor de Consumo:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="factor_consumo" step="0.01">
                </div>
                <div class="form-group">
                    <input type="submit" name="gpv_carga_submit" value="<?php esc_html_e('Registrar Carga', 'gestion-parque-vehicular') ?>">
                </div>
            </form>
        </div>
    <?php
        return ob_get_clean();
    }


    /**
     * Genera el formulario para registrar un vehículo.
     *
     * @return string HTML del formulario o mensaje de error/éxito.
     */
    public function formulario_vehiculo()
    {
        if (isset($_POST['gpv_vehiculo_submit'])) {
            if (! isset($_POST['gpv_vehiculo_nonce']) || ! wp_verify_nonce($_POST['gpv_vehiculo_nonce'], 'gpv_vehiculo_action')) {
                return '<div class="error">Error de seguridad.</div>';
            }

            $siglas = sanitize_text_field($_POST['vehiculo_siglas']);
            $anio = intval($_POST['vehiculo_anio']);
            $nombre_vehiculo = sanitize_text_field($_POST['vehiculo_nombre']);
            $odometro_actual = floatval($_POST['odometro_actual']);
            $nivel_combustible = floatval($_POST['nivel_combustible']);
            $tipo_combustible = sanitize_text_field($_POST['tipo_combustible']);
            $medida_odometro = sanitize_text_field($_POST['medida_odometro']);
            $factor_consumo = floatval($_POST['factor_consumo']);
            $capacidad_tanque = floatval($_POST['capacidad_tanque']);
            $ubicacion_actual = sanitize_text_field($_POST['ubicacion_actual']);
            $categoria = sanitize_text_field($_POST['categoria']);

            $data = array(
                'siglas' => $siglas,
                'anio' => $anio,
                'nombre_vehiculo' => $nombre_vehiculo,
                'odometro_actual' => $odometro_actual,
                'nivel_combustible' => $nivel_combustible,
                'tipo_combustible' => $tipo_combustible,
                'medida_odometro' => $medida_odometro,
                'factor_consumo' => $factor_consumo,
                'capacidad_tanque' => $capacidad_tanque,
                'ubicacion_actual' => $ubicacion_actual,
                'categoria' => $categoria,
            );



            $insert = $this->database->insert_vehicle($data);

            if ($insert) {
                return '<div class="success">Vehículo registrado correctamente.</div>';
            } else {
                return '<div class="error">Error al registrar el vehículo.</div>';
            }
        }

        ob_start();
    ?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Nuevo Vehículo', 'gestion-parque-vehicular') ?></h2>
            <form method="post" class="gpv-form">
                <?php wp_nonce_field('gpv_vehiculo_action', 'gpv_vehiculo_nonce'); ?>
                <div class="form-group">
                    <label for="vehiculo_siglas"><?php esc_html_e('Siglas del Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="vehiculo_siglas" required>
                </div>
                <div class="form-group">
                    <label for="vehiculo_anio"><?php esc_html_e('Año del Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="vehiculo_anio" required>
                </div>
                <div class="form-group">
                    <label for="vehiculo_nombre"><?php esc_html_e('Nombre del Vehículo:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="vehiculo_nombre" required>
                </div>
                <div class="form-group">
                    <label for="odometro_actual"><?php esc_html_e('Odómetro Actual:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="odometro_actual" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="nivel_combustible"><?php esc_html_e('Nivel de Combustible:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="nivel_combustible" step="0.01">
                </div>
                <div class="form-group">
                    <label for="tipo_combustible"><?php esc_html_e('Tipo de Combustible:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="tipo_combustible">
                </div>
                <div class="form-group">
                    <label for="medida_odometro"><?php esc_html_e('Medida del Odómetro:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="medida_odometro">
                </div>
                <div class="form-group">
                    <label for="factor_consumo"><?php esc_html_e('Factor de Consumo:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="factor_consumo" step="0.01">
                </div>
                <div class="form-group">
                    <label for="capacidad_tanque"><?php esc_html_e('Capacidad del Tanque:', 'gestion-parque-vehicular') ?></label>
                    <input type="number" name="capacidad_tanque" step="0.01">
                </div>
                <div class="form-group">
                    <label for="ubicacion_actual"><?php esc_html_e('Ubicación Actual:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="ubicacion_actual">
                </div>
                <div class="form-group">
                    <label for="categoria"><?php esc_html_e('Categoría:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="categoria">
                </div>
                <div class="form-group">
                    <input type="submit" name="gpv_vehiculo_submit" value="<?php esc_html_e('Registrar Vehículo', 'gestion-parque-vehicular') ?>">
                </div>
            </form>
        </div>
<?php
        return ob_get_clean();
    }
}
