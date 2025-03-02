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
    public function formulario_movimiento()
    {
        // Verificar que el usuario esté autenticado y tenga permisos
        if (!is_user_logged_in() || (!current_user_can('gpv_register_movements') && !current_user_can('manage_options'))) {
            return '<div class="error">' . __('No tienes permiso para registrar movimientos.', 'gestion-parque-vehicular') . '</div>';
        }

        // Obtener la lista de vehículos para el selector
        $vehiculos = $this->database->get_vehicles();

        // Filtrar vehículos según permisos del usuario
        if (!current_user_can('manage_options')) {
            $user_id = get_current_user_id();
            // Si no es admin, mostrar solo vehículos disponibles o asignados al usuario
            $vehiculos = array_filter($vehiculos, function ($v) use ($user_id) {
                return $v->estado === 'disponible' || $v->conductor_asignado == $user_id;
            });
        }

        // Si no hay vehículos disponibles
        if (empty($vehiculos)) {
            return '<div class="notice">' . __('No hay vehículos disponibles para registrar movimientos.', 'gestion-parque-vehicular') . '</div>';
        }

        // Mensaje de éxito o error
        $mensaje = '';

        if (isset($_POST['gpv_movimiento_submit'])) {
            // Verificar nonce
            if (!isset($_POST['gpv_mov_nonce']) || !wp_verify_nonce($_POST['gpv_mov_nonce'], 'gpv_mov_action')) {
                $mensaje = '<div class="error">' . __('Error de seguridad.', 'gestion-parque-vehicular') . '</div>';
            } else {
                // Obtener datos del vehículo seleccionado
                $vehiculo_id = intval($_POST['vehiculo_id']);
                $vehiculo = null;

                foreach ($vehiculos as $v) {
                    if ($v->id == $vehiculo_id) {
                        $vehiculo = $v;
                        break;
                    }
                }

                if (!$vehiculo) {
                    $mensaje = '<div class="error">' . __('Vehículo no encontrado.', 'gestion-parque-vehicular') . '</div>';
                } else {
                    // Sanear datos
                    $vehiculo_siglas = $vehiculo->siglas;
                    $vehiculo_nombre = $vehiculo->nombre_vehiculo;
                    $odometro_salida = floatval($_POST['odometro_salida']);

                    // Convertir datetime-local a formato MySQL
                    $hora_salida_input = sanitize_text_field($_POST['hora_salida']);
                    $hora_salida = date('Y-m-d H:i:s', strtotime($hora_salida_input));

                    $odometro_entrada = !empty($_POST['odometro_entrada']) ? floatval($_POST['odometro_entrada']) : null;
                    $hora_entrada = null;
                    $distancia_recorrida = null;
                    $combustible_consumido = null;
                    $nivel_combustible = $vehiculo->nivel_combustible;
                    $estado = 'en_progreso';

                    // Si se está registrando también la entrada (movimiento completo)
                    if (!empty($_POST['odometro_entrada']) && !empty($_POST['hora_entrada'])) {
                        $hora_entrada_input = sanitize_text_field($_POST['hora_entrada']);
                        $hora_entrada = date('Y-m-d H:i:s', strtotime($hora_entrada_input));

                        // Verificar que odómetro de entrada sea mayor que el de salida
                        if ($odometro_entrada <= $odometro_salida) {
                            $mensaje = '<div class="error">' . __('El odómetro de entrada debe ser mayor que el de salida.', 'gestion-parque-vehicular') . '</div>';
                            // Continuar con el formulario para corregir
                        } else {
                            // Calcular distancia recorrida
                            $distancia_recorrida = $odometro_entrada - $odometro_salida;

                            // Calcular combustible consumido basado en el factor de consumo del vehículo
                            $factor_consumo = floatval($vehiculo->factor_consumo);
                            if ($factor_consumo > 0) {
                                $combustible_consumido = $distancia_recorrida / $factor_consumo;
                            }

                            // Calcular nivel de combustible restante como porcentaje
                            $capacidad_tanque = floatval($vehiculo->capacidad_tanque);
                            if ($capacidad_tanque > 0 && $combustible_consumido > 0) {
                                $porcentaje_consumido = ($combustible_consumido / $capacidad_tanque) * 100;
                                $nivel_combustible = max(0, $vehiculo->nivel_combustible - $porcentaje_consumido);
                            }

                            $estado = 'completado';
                        }
                    }

                    // Si no hay error de validación
                    if (strpos($mensaje, 'error') === false) {
                        $conductor = sanitize_text_field($_POST['conductor']);
                        $proposito = isset($_POST['proposito']) ? sanitize_text_field($_POST['proposito']) : '';

                        $data = array(
                            'vehiculo_id' => $vehiculo_id,
                            'vehiculo_siglas' => $vehiculo_siglas,
                            'vehiculo_nombre' => $vehiculo_nombre,
                            'odometro_salida' => $odometro_salida,
                            'hora_salida' => $hora_salida,
                            'odometro_entrada' => $odometro_entrada,
                            'hora_entrada' => $hora_entrada,
                            'distancia_recorrida' => $distancia_recorrida,
                            'combustible_consumido' => $combustible_consumido,
                            'nivel_combustible' => $nivel_combustible,
                            'conductor' => $conductor,
                            'conductor_id' => get_current_user_id(),
                            'proposito' => $proposito,
                            'estado' => $estado
                        );

                        $insert = $this->database->insert_movement($data);

                        if ($insert) {
                            // Actualizar el odómetro y nivel de combustible del vehículo
                            global $wpdb;
                            $tabla_vehiculos = $wpdb->prefix . 'gpv_vehiculos';

                            $update_data = array(
                                'odometro_actual' => $estado === 'completado' ? $odometro_entrada : $odometro_salida,
                                'estado' => $estado === 'completado' ? 'disponible' : 'en_uso'
                            );

                            // Actualizar nivel de combustible solo si el movimiento está completado
                            if ($estado === 'completado') {
                                $update_data['nivel_combustible'] = $nivel_combustible;
                            }

                            $wpdb->update(
                                $tabla_vehiculos,
                                $update_data,
                                array('id' => $vehiculo_id)
                            );

                            $mensaje = '<div class="success">' . __('Movimiento registrado correctamente.', 'gestion-parque-vehicular') . '</div>';

                            // Limpiar formulario después de envío exitoso
                            $_POST = array();
                        } else {
                            $mensaje = '<div class="error">' . __('Error al registrar el movimiento.', 'gestion-parque-vehicular') . '</div>';
                        }
                    }
                }
            }
        }

        ob_start();
        echo $mensaje;
?>
        <div class="gpv-form-container">
            <h2><?php esc_html_e('Registrar Movimiento de Vehículo', 'gestion-parque-vehicular') ?></h2>

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
                                <?php if (isset($_POST['vehiculo_id']) && $_POST['vehiculo_id'] == $vehiculo->id) echo 'selected'; ?>
                                <?php echo ($vehiculo->estado !== 'disponible') ? 'disabled' : ''; ?>>
                                <?php echo esc_html($vehiculo->siglas . ' - ' . $vehiculo->nombre_vehiculo); ?>
                                <?php echo ($vehiculo->estado !== 'disponible') ? ' (' . esc_html($vehiculo->estado) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="gpv-field-description"><?php esc_html_e('Solo se muestran los vehículos disponibles.', 'gestion-parque-vehicular') ?></p>
                </div>

                <div class="form-group">
                    <label for="conductor"><?php esc_html_e('Conductor:', 'gestion-parque-vehicular') ?></label>
                    <input type="text" name="conductor" id="conductor" value="<?php echo esc_attr(isset($_POST['conductor']) ? $_POST['conductor'] : wp_get_current_user()->display_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="proposito"><?php esc_html_e('Propósito del viaje:', 'gestion-parque-vehicular') ?></label>
                    <textarea name="proposito" id="proposito"><?php echo esc_textarea(isset($_POST['proposito']) ? $_POST['proposito'] : ''); ?></textarea>
                    <p class="gpv-field-description"><?php esc_html_e('Describa brevemente el propósito de este movimiento.', 'gestion-parque-vehicular') ?></p>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="odometro_salida"><?php esc_html_e('Odómetro de Salida:', 'gestion-parque-vehicular') ?></label>
                        <input type="number" name="odometro_salida" id="odometro_salida" step="0.01" value="<?php echo esc_attr(isset($_POST['odometro_salida']) ? $_POST['odometro_salida'] : ''); ?>" required>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="hora_salida"><?php esc_html_e('Hora de Salida:', 'gestion-parque-vehicular') ?></label>
                        <input type="datetime-local" name="hora_salida" id="hora_salida" value="<?php echo esc_attr(isset($_POST['hora_salida']) ? $_POST['hora_salida'] : ''); ?>" required>
                    </div>
                </div>

                <!-- Sección opcional para registrar también la entrada -->
                <div class="gpv-section-toggle">
                    <h3>
                        <input type="checkbox" id="toggle_entrada" name="toggle_entrada" <?php echo (isset($_POST['toggle_entrada'])) ? 'checked' : ''; ?>>
                        <label for="toggle_entrada"><?php esc_html_e('¿Registrar también la entrada?', 'gestion-parque-vehicular') ?></label>
                    </h3>

                    <div id="entrada_section" class="<?php echo (isset($_POST['toggle_entrada'])) ? '' : 'hidden'; ?>">
                        <div class="form-row">
                            <div class="form-group form-group-half">
                                <label for="odometro_entrada"><?php esc_html_e('Odómetro de Entrada:', 'gestion-parque-vehicular') ?></label>
                                <input type="number" name="odometro_entrada" id="odometro_entrada" step="0.01" value="<?php echo esc_attr(isset($_POST['odometro_entrada']) ? $_POST['odometro_entrada'] : ''); ?>">
                            </div>

                            <div class="form-group form-group-half">
                                <label for="hora_entrada"><?php esc_html_e('Hora de Entrada:', 'gestion-parque-vehicular') ?></label>
                                <input type="datetime-local" name="hora_entrada" id="hora_entrada" value="<?php echo esc_attr(isset($_POST['hora_entrada']) ? $_POST['hora_entrada'] : ''); ?>">
                            </div>
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
                    <input type="submit" name="gpv_movimiento_submit" value="<?php esc_html_e('Registrar Movimiento', 'gestion-parque-vehicular') ?>">
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

                // Guardar borrador en localStorage
                const form = document.getElementById('gpv-movimiento-form');
                const formInputs = form.querySelectorAll('input, select, textarea');

                // Cargar datos guardados si existen
                const savedData = localStorage.getItem('gpv_movement_draft');
                if (savedData) {
                    const parsedData = JSON.parse(savedData);
                    for (const input of formInputs) {
                        if (input.name && parsedData[input.name]) {
                            if (input.type === 'checkbox') {
                                input.checked = parsedData[input.name];
                            } else {
                                input.value = parsedData[input.name];
                            }
                        }
                    }

                    // Mostrar/ocultar sección de entrada si es necesario
                    if (parsedData.toggle_entrada) {
                        toggleEntrada.checked = true;
                        entradaSection.classList.remove('hidden');
                    }

                    calcularValores();
                }

                // Guardar datos al cambiar cualquier campo
                for (const input of formInputs) {
                    input.addEventListener('change', saveDraft);
                    if (input.tagName === 'TEXTAREA' || input.type === 'text' || input.type === 'number') {
                        input.addEventListener('input', saveDraft);
                    }
                }

                function saveDraft() {
                    const formData = {};
                    for (const input of formInputs) {
                        if (input.name && input.name !== 'gpv_mov_nonce' && input.name !== 'gpv_movimiento_submit') {
                            if (input.type === 'checkbox') {
                                formData[input.name] = input.checked;
                            } else {
                                formData[input.name] = input.value;
                            }
                        }
                    }
                    localStorage.setItem('gpv_movement_draft', JSON.stringify(formData));
                }

                // Limpiar localStorage al enviar el formulario
                form.addEventListener('submit', function() {
                    localStorage.removeItem('gpv_movement_draft');
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
