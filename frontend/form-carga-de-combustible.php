<?php

/**
 * Formulario para registrar carga de combustible
 *
 * @return string HTML del formulario
 */
function gpv_form_carga_combustible()
{
    // Verificar permisos de usuario
    if (!is_user_logged_in() || (!current_user_can('gpv_register_fuel') && !current_user_can('manage_options'))) {
        return '<div class="gpv-error">' . __('No tienes permiso para registrar cargas de combustible.', 'gestion-parque-vehicular') . '</div>';
    }

    global $GPV_Database;
    $mensaje = '';
    $exito = false;

    // Procesar el formulario cuando se envía
    if (isset($_POST['gpv_carga_submit'])) {
        // Verificar nonce para seguridad
        if (!isset($_POST['gpv_carga_nonce']) || !wp_verify_nonce($_POST['gpv_carga_nonce'], 'gpv_carga_action')) {
            $mensaje = '<div class="gpv-error">' . __('Error de seguridad. Por favor, intenta de nuevo.', 'gestion-parque-vehicular') . '</div>';
        } else {
            // Sanear y validar datos del formulario
            $vehiculo_id = intval($_POST['vehiculo_id']);
            $odometro_carga = floatval($_POST['odometro_carga']);
            $litros_cargados = floatval($_POST['litros_cargados']);
            $precio = floatval($_POST['precio']);
            $km_desde_ultima_carga = isset($_POST['km_desde_ultima_carga']) ? floatval($_POST['km_desde_ultima_carga']) : 0;

            // Obtener datos del vehículo
            $vehiculo = $GPV_Database->get_vehicle($vehiculo_id);

            if (!$vehiculo) {
                $mensaje = '<div class="gpv-error">' . __('El vehículo seleccionado no existe.', 'gestion-parque-vehicular') . '</div>';
            } else {
                // Calcular factor de consumo si tenemos km recorridos
                $factor_consumo = 0;
                if ($km_desde_ultima_carga > 0 && $litros_cargados > 0) {
                    $factor_consumo = $km_desde_ultima_carga / $litros_cargados;
                }

                // Preparar datos para inserción
                $data = array(
                    'vehiculo_id' => $vehiculo_id,
                    'vehiculo_siglas' => $vehiculo->siglas,
                    'vehiculo_nombre' => $vehiculo->nombre_vehiculo,
                    'odometro_carga' => $odometro_carga,
                    'litros_cargados' => $litros_cargados,
                    'precio' => $precio,
                    'km_desde_ultima_carga' => $km_desde_ultima_carga,
                    'factor_consumo' => $factor_consumo,
                    'conductor_id' => get_current_user_id(),
                    'fecha_carga' => current_time('mysql')
                );

                // Insertar registro
                $result = $GPV_Database->insert_fuel($data);

                if ($result) {
                    // Actualizar nivel de combustible del vehículo
                    $nuevo_nivel = min(100, $vehiculo->nivel_combustible + ($litros_cargados / $vehiculo->capacidad_tanque * 100));

                    $GPV_Database->update_vehicle($vehiculo_id, array(
                        'odometro_actual' => $odometro_carga,
                        'nivel_combustible' => $nuevo_nivel,
                        'factor_consumo' => $factor_consumo > 0 ? $factor_consumo : $vehiculo->factor_consumo,
                        'ultima_actualizacion' => current_time('mysql')
                    ));

                    $mensaje = '<div class="gpv-success">' . __('Carga de combustible registrada correctamente.', 'gestion-parque-vehicular') . '</div>';
                    $exito = true;
                } else {
                    $mensaje = '<div class="gpv-error">' . __('Error al registrar la carga de combustible.', 'gestion-parque-vehicular') . '</div>';
                }
            }
        }
    }

    // Obtener vehículos disponibles
    $vehiculos = $GPV_Database->get_vehicles(['estado' => ['disponible', 'en_uso']]);

    // Si el usuario no es admin, filtrar solo sus vehículos asignados
    if (!current_user_can('manage_options')) {
        $user_id = get_current_user_id();
        $vehiculos = array_filter($vehiculos, function ($v) use ($user_id) {
            return $v->conductor_asignado == 0 || $v->conductor_asignado == $user_id;
        });
    }

    // Si no hay vehículos disponibles
    if (empty($vehiculos)) {
        return '<div class="gpv-notice">' . __('No hay vehículos disponibles para registrar cargas de combustible.', 'gestion-parque-vehicular') . '</div>';
    }

    // Si el formulario se envió con éxito y queremos mostrar un formulario limpio
    if ($exito) {
        $vehiculo_id = '';
        $odometro_carga = '';
        $litros_cargados = '';
        $precio = '';
        $km_desde_ultima_carga = '';
    } else {
        // Recuperar valores enviados en caso de error
        $vehiculo_id = isset($_POST['vehiculo_id']) ? intval($_POST['vehiculo_id']) : '';
        $odometro_carga = isset($_POST['odometro_carga']) ? floatval($_POST['odometro_carga']) : '';
        $litros_cargados = isset($_POST['litros_cargados']) ? floatval($_POST['litros_cargados']) : '';
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : '';
        $km_desde_ultima_carga = isset($_POST['km_desde_ultima_carga']) ? floatval($_POST['km_desde_ultima_carga']) : '';
    }

    // Construir el formulario HTML
    $output = '<div class="gpv-form-container">';
    $output .= $mensaje;

    $output .= '<h2>' . __('Registrar Carga de Combustible', 'gestion-parque-vehicular') . '</h2>';
    $output .= '<form method="post" class="gpv-form" id="gpv-carga-form">';
    $output .= wp_nonce_field('gpv_carga_action', 'gpv_carga_nonce', true, false);

    // Selección de vehículo
    $output .= '<div class="form-group">';
    $output .= '<label for="vehiculo_id">' . __('Selecciona el Vehículo:', 'gestion-parque-vehicular') . ' <span class="required">*</span></label>';
    $output .= '<select name="vehiculo_id" id="vehiculo_id" required>';
    $output .= '<option value="">' . __('-- Seleccionar Vehículo --', 'gestion-parque-vehicular') . '</option>';

    foreach ($vehiculos as $vehiculo) {
        $selected = ($vehiculo_id == $vehiculo->id) ? 'selected' : '';
        $output .= '<option value="' . esc_attr($vehiculo->id) . '" ' . $selected . '
                    data-odometro="' . esc_attr($vehiculo->odometro_actual) . '"
                    data-ultima-carga="' . esc_attr($vehiculo->ultima_carga_odometro) . '"
                    data-capacidad="' . esc_attr($vehiculo->capacidad_tanque) . '">';
        $output .= esc_html($vehiculo->siglas . ' - ' . $vehiculo->nombre_vehiculo);
        $output .= '</option>';
    }

    $output .= '</select>';
    $output .= '</div>';

    // Campos del formulario
    $output .= '<div class="form-row">';
    $output .= '<div class="form-group form-group-half">';
    $output .= '<label for="odometro_carga">' . __('Odómetro Actual:', 'gestion-parque-vehicular') . ' <span class="required">*</span></label>';
    $output .= '<input type="number" name="odometro_carga" id="odometro_carga" step="0.1" value="' . esc_attr($odometro_carga) . '" required>';
    $output .= '</div>';

    $output .= '<div class="form-group form-group-half">';
    $output .= '<label for="km_desde_ultima_carga">' . __('Km desde Última Carga:', 'gestion-parque-vehicular') . '</label>';
    $output .= '<input type="number" name="km_desde_ultima_carga" id="km_desde_ultima_carga" step="0.1" value="' . esc_attr($km_desde_ultima_carga) . '">';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="form-row">';
    $output .= '<div class="form-group form-group-half">';
    $output .= '<label for="litros_cargados">' . __('Litros Cargados:', 'gestion-parque-vehicular') . ' <span class="required">*</span></label>';
    $output .= '<input type="number" name="litros_cargados" id="litros_cargados" step="0.01" value="' . esc_attr($litros_cargados) . '" required>';
    $output .= '</div>';

    $output .= '<div class="form-group form-group-half">';
    $output .= '<label for="precio">' . __('Precio por Litro:', 'gestion-parque-vehicular') . ' <span class="required">*</span></label>';
    $output .= '<input type="number" name="precio" id="precio" step="0.01" value="' . esc_attr($precio) . '" required>';
    $output .= '</div>';
    $output .= '</div>';

    // Información calculada
    $output .= '<div class="form-group">';
    $output .= '<label>' . __('Información Calculada:', 'gestion-parque-vehicular') . '</label>';
    $output .= '<div class="info-calculada">';
    $output .= '<p><strong>' . __('Costo Total:', 'gestion-parque-vehicular') . '</strong> $<span id="costo_total">0.00</span></p>';
    $output .= '<p><strong>' . __('Rendimiento:', 'gestion-parque-vehicular') . '</strong> <span id="rendimiento">0.00</span> km/L</p>';
    $output .= '<p><strong>' . __('Nivel de Combustible Estimado:', 'gestion-parque-vehicular') . '</strong> <span id="nivel_combustible">0</span>%</p>';
    $output .= '</div>';
    $output .= '</div>';

    // Botón de envío
    $output .= '<div class="form-group">';
    $output .= '<input type="submit" name="gpv_carga_submit" value="' . __('Registrar Carga', 'gestion-parque-vehicular') . '" class="button button-primary">';
    $output .= '</div>';

    $output .= '</form>';
    $output .= '</div>';

    // JavaScript para cálculos en tiempo real
    $output .= '
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const vehiculoSelect = document.getElementById("vehiculo_id");
        const odometroCarga = document.getElementById("odometro_carga");
        const kmDesdeUltimaCarga = document.getElementById("km_desde_ultima_carga");
        const litrosCargados = document.getElementById("litros_cargados");
        const precio = document.getElementById("precio");
        const costoTotal = document.getElementById("costo_total");
        const rendimiento = document.getElementById("rendimiento");
        const nivelCombustible = document.getElementById("nivel_combustible");

        // Cuando se selecciona un vehículo
        vehiculoSelect.addEventListener("change", function() {
            const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];

            if (selectedOption.value) {
                // Establecer odómetro actual
                const odometroActual = parseFloat(selectedOption.dataset.odometro) || 0;
                odometroCarga.value = odometroActual.toFixed(1);

                // Calcular kilómetros desde última carga
                const ultimaCargaOdometro = parseFloat(selectedOption.dataset.ultimaCarga) || 0;
                if (ultimaCargaOdometro > 0) {
                    const distancia = odometroActual - ultimaCargaOdometro;
                    kmDesdeUltimaCarga.value = distancia > 0 ? distancia.toFixed(1) : 0;
                } else {
                    kmDesdeUltimaCarga.value = "";
                }

                // Actualizar cálculos
                calcularValores();
            }
        });

        // Recalcular al cambiar valores
        odometroCarga.addEventListener("input", calcularValores);
        kmDesdeUltimaCarga.addEventListener("input", calcularValores);
        litrosCargados.addEventListener("input", calcularValores);
        precio.addEventListener("input", calcularValores);

        // Función para calcular valores
        function calcularValores() {
            // Calcular costo total
            const litros = parseFloat(litrosCargados.value) || 0;
            const precioLitro = parseFloat(precio.value) || 0;
            const total = litros * precioLitro;
            costoTotal.textContent = total.toFixed(2);

            // Calcular rendimiento
            const km = parseFloat(kmDesdeUltimaCarga.value) || 0;
            let rendimientoValue = 0;
            if (litros > 0 && km > 0) {
                rendimientoValue = km / litros;
            }
            rendimiento.textContent = rendimientoValue.toFixed(2);

            // Calcular nivel de combustible estimado
            if (vehiculoSelect.value) {
                const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];
                const capacidadTanque = parseFloat(selectedOption.dataset.capacidad) || 0;

                if (capacidadTanque > 0 && litros > 0) {
                    const porcentajeRecarga = (litros / capacidadTanque) * 100;
                    // Asumimos que el tanque está al 50% antes de la recarga (esto es solo una estimación)
                    const nivelEstimado = Math.min(100, 50 + porcentajeRecarga);
                    nivelCombustible.textContent = nivelEstimado.toFixed(0);
                } else {
                    nivelCombustible.textContent = "0";
                }
            }
        }
    });
    </script>';

    return $output;
}

// Registrar el shortcode
add_shortcode('gpv_form_carga_combustible', 'gpv_form_carga_combustible');
